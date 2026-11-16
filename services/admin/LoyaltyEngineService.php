<?php
/**
 * DỊCH VỤ ĐỘNG CƠ KHÁCH HÀNG THÂN THIẾT (services/admin/LoyaltyEngineService.php)
 * Loyalty Engine: Quản lý tích điểm, nâng hạng tự động, ghi sổ cái lsd_lich_su_diem
 * Đảm bảo tính nhất quán qua 1 PDO Transaction (SELECT ... FOR UPDATE)
 *
 * ĐÃ KIỂM TOÁN & BỔ SUNG (Mục 1.1):
 *  - Quy tắc chuyển trạng thái một chiều: CHO_XAC_NHAN → DANG_GIAO/DA_HUY; DANG_GIAO → HOAN_THANH/DA_HUY
 *  - Chống cộng trùng điểm Idempotency (SELECT COUNT(*) FROM lsd_lich_su_diem WHERE ma_dh AND loai_giao_dich='CONG_MUA_HANG')
 *  - Khi hủy đơn (DA_HUY): hoàn trả tồn kho bt_bien_the_sp và mở khóa voucher vkh_vi_voucher_kh
 */

require_once __DIR__ . '/../../config/db.php';

class LoyaltyEngineService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: getDBConnection();
    }

    /**
     * Quy tắc chuyển trạng thái hợp lệ (một chiều, không đảo ngược)
     * CHO_XAC_NHAN → DANG_GIAO | DA_HUY
     * DANG_GIAO    → HOAN_THANH | DA_HUY
     * HOAN_THANH & DA_HUY = cuối (frozen)
     */
    private function isValidTransition(string $currentStatus, string $newStatus): bool
    {
        $allowedMap = [
            'CHO_XAC_NHAN' => ['DANG_GIAO', 'DA_HUY'],
            'DANG_GIAO'    => ['HOAN_THANH', 'DA_HUY'],
            'HOAN_THANH'   => [],
            'DA_HUY'       => [],
        ];
        return in_array($newStatus, $allowedMap[$currentStatus] ?? [], true);
    }

    /**
     * Cập nhật trạng thái đơn hàng và kích hoạt Loyalty Engine
     * - HOAN_THANH: Cộng điểm, xét nâng hạng, ghi sổ cái (có Idempotency guard)
     * - DA_HUY: Hoàn trả tồn kho, mở khóa voucher (nếu có)
     * - DANG_GIAO: Chỉ cập nhật trạng thái, không tác động điểm
     *
     * @param int    $maDh
     * @param string $newStatus Trạng thái mới (DANG_GIAO | HOAN_THANH | DA_HUY)
     * @return array Kết quả xử lý
     * @throws Exception
     */
    public function updateOrderStatus(int $maDh, string $newStatus): array
    {
        if ($maDh <= 0) {
            throw new Exception("Mã đơn hàng không hợp lệ.");
        }

        $validStatuses = ['DANG_GIAO', 'HOAN_THANH', 'DA_HUY'];
        if (!in_array($newStatus, $validStatuses, true)) {
            throw new Exception("Trạng thái '{$newStatus}' không được hỗ trợ.");
        }

        $this->pdo->beginTransaction();

        try {
            // 1. Khóa và lấy thông tin đơn hàng (FOR UPDATE)
            $stmtOrder = $this->pdo->prepare("
                SELECT dh.*, nd.ho_ten, nd.email, nd.sdt
                FROM dh_don_hang dh
                INNER JOIN nd_nguoi_dung nd ON dh.ma_nd = nd.ma_nd
                WHERE dh.ma_dh = :ma_dh
                FOR UPDATE
            ");
            $stmtOrder->execute(['ma_dh' => $maDh]);
            $order = $stmtOrder->fetch();

            if (!$order) {
                throw new Exception("Đơn hàng #DH" . sprintf('%02d', $maDh) . " không tồn tại.");
            }

            $currentStatus = $order['trang_thai_dh'];
            $dhCode        = sprintf('DH%02d', $maDh);

            // 2. Kiểm tra quy tắc chuyển trạng thái một chiều
            if (!$this->isValidTransition($currentStatus, $newStatus)) {
                if ($currentStatus === 'HOAN_THANH') {
                    throw new Exception("Đơn #{$dhCode} đã HOÀN THÀNH — trạng thái đóng băng, không thể thay đổi.");
                }
                if ($currentStatus === 'DA_HUY') {
                    throw new Exception("Đơn #{$dhCode} đã BỊ HỦY — trạng thái đóng băng, không thể thay đổi.");
                }
                throw new Exception("Không thể chuyển đơn #{$dhCode} từ '{$currentStatus}' sang '{$newStatus}'. Luồng không hợp lệ.");
            }

            $maNd       = (int) $order['ma_nd'];
            $tenKhach   = $order['ho_ten'] ?? 'Khách hàng';
            $diemCong   = (int) $order['diem_cong_du_kien'];
            $isUpgraded = false;
            $tenHangMoi = '';
            $newTongDiem = 0;
            $newDiemKhaDung = 0;

            // 3. Cập nhật trạng thái đơn hàng
            $this->pdo->prepare("UPDATE dh_don_hang SET trang_thai_dh = :st WHERE ma_dh = :id")
                      ->execute(['st' => $newStatus, 'id' => $maDh]);

            // =========================================================
            // NHÁNH HOAN_THANH: Cộng điểm, xét nâng hạng
            // =========================================================
            if ($newStatus === 'HOAN_THANH') {
                // 4. Chống cộng trùng điểm (Idempotency)
                $stmtIdempotency = $this->pdo->prepare("
                    SELECT COUNT(*) AS cnt
                    FROM lsd_lich_su_diem
                    WHERE ma_dh = :ma_dh AND loai_giao_dich = 'CONG_MUA_HANG'
                ");
                $stmtIdempotency->execute(['ma_dh' => $maDh]);
                $alreadyCredited = (int) $stmtIdempotency->fetchColumn();

                if ($alreadyCredited === 0 && $diemCong > 0) {
                    // 5. Khóa thẻ tích điểm (FOR UPDATE)
                    $stmtCard = $this->pdo->prepare("SELECT * FROM ttd_the_tich_diem WHERE ma_nd = :ma_nd FOR UPDATE");
                    $stmtCard->execute(['ma_nd' => $maNd]);
                    $card = $stmtCard->fetch();

                    if (!$card) {
                        $cardNo = 'AUR' . str_pad((string)$maNd, 5, '0', STR_PAD_LEFT);
                        $this->pdo->prepare("
                            INSERT INTO ttd_the_tich_diem (ma_nd, ma_hang, ma_so_the, tong_diem_tich_luy, diem_kha_dung, ngay_cap, ngay_cap_nhat)
                            VALUES (:ma_nd, 1, :card_no, 0, 0, NOW(), NOW())
                        ")->execute(['ma_nd' => $maNd, 'card_no' => $cardNo]);

                        $stmtCard->execute(['ma_nd' => $maNd]);
                        $card = $stmtCard->fetch();
                    }

                    $oldMaHang      = (int) $card['ma_hang'];
                    $newTongDiem    = (int) $card['tong_diem_tich_luy'] + $diemCong;
                    $newDiemKhaDung = (int) $card['diem_kha_dung'] + $diemCong;

                    // 6. Xét nâng hạng tự động
                    $stmtTier = $this->pdo->prepare("
                        SELECT ma_hang, ten_hang, diem_toi_thieu
                        FROM htv_hang_thanh_vien
                        WHERE diem_toi_thieu <= :diem
                        ORDER BY diem_toi_thieu DESC
                        LIMIT 1
                    ");
                    $stmtTier->execute(['diem' => $newTongDiem]);
                    $targetTier  = $stmtTier->fetch();
                    $targetMaHang = $targetTier ? (int) $targetTier['ma_hang'] : $oldMaHang;
                    $tenHangMoi   = $targetTier ? $targetTier['ten_hang'] : '';
                    $isUpgraded   = ($targetMaHang > $oldMaHang);

                    // 7. Cập nhật cả 2 quỹ điểm + hạng thành viên
                    $this->pdo->prepare("
                        UPDATE ttd_the_tich_diem
                        SET tong_diem_tich_luy = :tong, diem_kha_dung = :kd, ma_hang = :hang, ngay_cap_nhat = NOW()
                        WHERE ma_the = :ma_the
                    ")->execute([
                        'tong'    => $newTongDiem,
                        'kd'      => $newDiemKhaDung,
                        'hang'    => $targetMaHang,
                        'ma_the'  => $card['ma_the'],
                    ]);

                    // 8. Ghi sổ cái CONG_MUA_HANG
                    $this->pdo->prepare("
                        INSERT INTO lsd_lich_su_diem (ma_the, ma_dh, loai_giao_dich, so_diem_thay_doi, so_du_sau_gd, noi_dung, thoi_gian)
                        VALUES (:ma_the, :ma_dh, 'CONG_MUA_HANG', :delta, :balance, :note, NOW())
                    ")->execute([
                        'ma_the'  => $card['ma_the'],
                        'ma_dh'   => $maDh,
                        'delta'   => $diemCong,
                        'balance' => $newDiemKhaDung,
                        'note'    => "Cộng {$diemCong} điểm mua hàng khi hoàn thành đơn #{$dhCode}",
                    ]);
                }
            }

            // =========================================================
            // NHÁNH DA_HUY: Hoàn trả tồn kho + mở khóa Voucher
            // =========================================================
            if ($newStatus === 'DA_HUY') {
                // 9. Lấy danh sách chi tiết đơn hàng để hoàn kho
                $stmtItems = $this->pdo->prepare("
                    SELECT ma_bt, so_luong FROM ctdh_chi_tiet_dh WHERE ma_dh = :ma_dh
                ");
                $stmtItems->execute(['ma_dh' => $maDh]);
                $items = $stmtItems->fetchAll();

                foreach ($items as $item) {
                    $this->pdo->prepare("
                        UPDATE bt_bien_the_sp
                        SET so_luong_ton = so_luong_ton + :qty
                        WHERE ma_bt = :ma_bt
                    ")->execute(['qty' => (int)$item['so_luong'], 'ma_bt' => (int)$item['ma_bt']]);
                }

                // 10. Mở khóa Voucher nếu đơn có áp dụng
                if (!empty($order['ma_pgg'])) {
                    $this->pdo->prepare("
                        UPDATE vkh_vi_voucher_kh
                        SET trang_thai_su_dung = 0, ngay_su_dung = NULL
                        WHERE ma_nd = :ma_nd AND ma_pgg = :ma_pgg AND trang_thai_su_dung = 1
                    ")->execute(['ma_nd' => $maNd, 'ma_pgg' => (int)$order['ma_pgg']]);
                }
            }

            $this->pdo->commit();

            // Soạn thông báo Flash Toast
            $statusLabel = [
                'DANG_GIAO'  => 'Đang giao hàng',
                'HOAN_THANH' => 'Hoàn thành',
                'DA_HUY'     => 'Đã hủy',
            ][$newStatus] ?? $newStatus;

            $msg = "Đơn #{$dhCode} → {$statusLabel}.";
            if ($newStatus === 'HOAN_THANH' && $diemCong > 0) {
                $msg .= " Cộng +{$diemCong} điểm cho {$tenKhach}.";
            }
            if ($isUpgraded) {
                $msg .= " 🎉 Thăng lên {$tenHangMoi}!";
            }
            if ($newStatus === 'DA_HUY') {
                $msg .= " Đã hoàn trả tồn kho và mở khóa voucher (nếu có).";
            }

            return [
                'success'           => true,
                'message'           => $msg,
                'ma_dh'             => $maDh,
                'ma_dh_code'        => $dhCode,
                'ten_khach'         => $tenKhach,
                'new_status'        => $newStatus,
                'diem_cong'         => $diemCong,
                'tong_diem_moi'     => $newTongDiem,
                'diem_kha_dung_moi' => $newDiemKhaDung,
                'is_upgraded'       => $isUpgraded,
                'ten_hang_moi'      => $tenHangMoi,
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Alias backward-compatible: duyệt đơn sang HOAN_THANH (giữ nguyên để không phá API cũ)
     */
    public function approveOrder(int $maDh, string $newStatus = 'HOAN_THANH'): array
    {
        return $this->updateOrderStatus($maDh, $newStatus);
    }
}
