<?php
/**
 * DỊCH VỤ ĐỘNG CƠ KHÁCH HÀNG THÂN THIẾT (services/admin/LoyaltyEngineService.php)
 * Loyalty Engine: Quản lý tích điểm, nâng hạng tự động, ghi sổ cái lsd_lich_su_diem
 * Đảm bảo tính nhất quán qua 1 PDO Transaction (SELECT ... FOR UPDATE)
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
     * Duyệt đơn hàng hoàn thành -> Tự động Cộng điểm & Xét nâng hạng
     * Thực thi trong 1 PDO Transaction an toàn với Pessimistic Locking
     *
     * @param int $maDh
     * @param string $newStatus Mặc định là 'HOAN_THANH'
     * @return array Kết quả xử lý đơn hàng và thăng hạng thành viên
     * @throws Exception
     */
    public function approveOrder(int $maDh, string $newStatus = 'HOAN_THANH'): array
    {
        if ($maDh <= 0) {
            throw new Exception("Mã đơn hàng không hợp lệ.");
        }

        $this->pdo->beginTransaction();

        try {
            // 1. Khóa dòng đơn hàng dh_don_hang
            $stmtOrder = $this->pdo->prepare("
                SELECT dh.*, nd.ho_ten, nd.email, nd.sdt
                FROM dh_don_hang dh
                INNER JOIN nd_nguoi_dung nd ON dh.ma_nd = nd.ma_nd
                WHERE dh.ma_dh = ? 
                FOR UPDATE
            ");
            $stmtOrder->execute([$maDh]);
            $order = $stmtOrder->fetch();

            if (!$order) {
                throw new Exception("Đơn hàng #DH" . sprintf('%02d', $maDh) . " không tồn tại.");
            }

            if ($order['trang_thai_dh'] === 'HOAN_THANH') {
                throw new Exception("Đơn hàng #DH" . sprintf('%02d', $maDh) . " đã ở trạng thái Hoàn thành.");
            }

            if ($order['trang_thai_dh'] === 'DA_HUY') {
                throw new Exception("Đơn hàng #DH" . sprintf('%02d', $maDh) . " đã bị hủy, không thể duyệt.");
            }

            // 2. Cập nhật trạng thái đơn hàng sang HOAN_THANH
            $stmtUpdateOrder = $this->pdo->prepare("UPDATE dh_don_hang SET trang_thai_dh = ? WHERE ma_dh = ?");
            $stmtUpdateOrder->execute([$newStatus, $maDh]);

            $diemCong = (int) $order['diem_cong_du_kien'];
            $maNd = (int) $order['ma_nd'];
            $tenKhach = $order['ho_ten'] ?? 'Khách hàng';
            $dhCode = sprintf('DH%02d', $maDh);

            $isUpgraded = false;
            $tenHangMoi = '';
            $newTongDiem = 0;
            $newDiemKhaDung = 0;

            if ($newStatus === 'HOAN_THANH') {
                // 3. Khóa dòng thẻ tích điểm của khách trong ttd_the_tich_diem (FOR UPDATE)
                $stmtCard = $this->pdo->prepare("SELECT * FROM ttd_the_tich_diem WHERE ma_nd = ? FOR UPDATE");
                $stmtCard->execute([$maNd]);
                $card = $stmtCard->fetch();

                if (!$card) {
                    // Tạo mới thẻ tích điểm nếu khách chưa có
                    $cardNo = 'AUR' . str_pad((string)$maNd, 5, '0', STR_PAD_LEFT);
                    $stmtCreateCard = $this->pdo->prepare("
                        INSERT INTO ttd_the_tich_diem (ma_nd, ma_hang, ma_so_the, tong_diem_tich_luy, diem_kha_dung, ngay_cap, ngay_cap_nhat)
                        VALUES (?, 1, ?, 0, 0, NOW(), NOW())
                    ");
                    $stmtCreateCard->execute([$maNd, $cardNo]);

                    $stmtCard->execute([$maNd]);
                    $card = $stmtCard->fetch();
                }

                $oldMaHang = (int) $card['ma_hang'];
                $newTongDiem = (int) $card['tong_diem_tich_luy'] + $diemCong;
                $newDiemKhaDung = (int) $card['diem_kha_dung'] + $diemCong;

                // 4. Đối chiếu tổng điểm tích lũy mới với bảng htv_hang_thanh_vien để xét nâng hạng
                $stmtTier = $this->pdo->prepare("
                    SELECT ma_hang, ten_hang, diem_toi_thieu 
                    FROM htv_hang_thanh_vien 
                    WHERE diem_toi_thieu <= ? 
                    ORDER BY diem_toi_thieu DESC 
                    LIMIT 1
                ");
                $stmtTier->execute([$newTongDiem]);
                $targetTier = $stmtTier->fetch();

                $targetMaHang = $targetTier ? (int) $targetTier['ma_hang'] : $oldMaHang;
                $tenHangMoi = $targetTier ? $targetTier['ten_hang'] : '';
                $isUpgraded = ($targetMaHang > $oldMaHang);

                // Cập nhật cả 2 quỹ điểm và hạng thành viên mới
                $stmtUpdateCard = $this->pdo->prepare("
                    UPDATE ttd_the_tich_diem 
                    SET tong_diem_tich_luy = ?, diem_kha_dung = ?, ma_hang = ?, ngay_cap_nhat = NOW() 
                    WHERE ma_the = ?
                ");
                $stmtUpdateCard->execute([$newTongDiem, $newDiemKhaDung, $targetMaHang, $card['ma_the']]);

                // 5. Ghi sổ cái biến động điểm vào bảng lsd_lich_su_diem
                if ($diemCong > 0) {
                    $noiDungLsd = "Cộng {$diemCong} điểm mua hàng khi hoàn thành đơn #{$dhCode}";
                    $stmtLedger = $this->pdo->prepare("
                        INSERT INTO lsd_lich_su_diem (
                            ma_the, ma_dh, loai_giao_dich, so_diem_thay_doi,
                            so_du_sau_gd, noi_dung, thoi_gian
                        ) VALUES (?, ?, 'CONG_MUA_HANG', ?, ?, ?, NOW())
                    ");
                    $stmtLedger->execute([
                        $card['ma_the'],
                        $maDh,
                        $diemCong,
                        $newDiemKhaDung,
                        $noiDungLsd
                    ]);
                }
            }

            $this->pdo->commit();

            // Soạn thông báo thân thiện cho Admin Flash Toast
            $msg = "Đã duyệt đơn #{$dhCode} sang Hoàn thành.";
            if ($diemCong > 0) {
                $msg .= " Cộng +{$diemCong} điểm cho {$tenKhach}.";
            }
            if ($isUpgraded) {
                $msg .= " 🎉 Thăng lên {$tenHangMoi}!";
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
                'ten_hang_moi'      => $tenHangMoi
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
