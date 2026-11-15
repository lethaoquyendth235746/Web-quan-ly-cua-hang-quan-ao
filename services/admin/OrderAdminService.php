<?php
/**
 * DỊCH VỤ BÊN QUẢN TRỊ: Quản lý Đơn hàng, Chi tiết hóa đơn & Xét nâng hạng khách hàng
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class OrderAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy danh sách tất cả đơn hàng kèm thông tin khách hàng, thẻ tích điểm, hạng hiện tại và chi tiết sản phẩm
     */
    public function getAllOrders(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                dh.*,
                nd.ho_ten AS ten_khach_hang,
                nd.sdt AS sdt_khach_hang,
                nd.email AS email_khach_hang,
                ttd.ma_the,
                ttd.ma_so_the,
                ttd.tong_diem_tich_luy,
                ttd.diem_kha_dung,
                htv.ma_hang,
                htv.ten_hang,
                htv.he_so_tich_diem,
                htv.mau_sac_the,
                pgg.ma_code AS ma_voucher_code,
                pgg.ten_pgg AS ten_voucher
            FROM dh_don_hang dh
            INNER JOIN nd_nguoi_dung nd ON dh.ma_nd = nd.ma_nd
            LEFT JOIN ttd_the_tich_diem ttd ON nd.ma_nd = ttd.ma_nd
            LEFT JOIN htv_hang_thanh_vien htv ON ttd.ma_hang = htv.ma_hang
            LEFT JOIN pgg_phieu_giam_gia pgg ON dh.ma_pgg = pgg.ma_pgg
            ORDER BY 
                CASE WHEN dh.trang_thai_dh = 'CHO_XAC_NHAN' THEN 0 ELSE 1 END ASC,
                dh.ngay_dat DESC,
                dh.ma_dh DESC
        ");
        $orders = $stmt->fetchAll();

        if (empty($orders)) {
            return [];
        }

        // Lấy danh sách các bậc hạng để tính tiến trình lên hạng tiếp theo cho từng khách
        $tiersStmt = $this->pdo->query("SELECT * FROM htv_hang_thanh_vien ORDER BY diem_toi_thieu ASC");
        $tiers = $tiersStmt->fetchAll();

        // Lấy toàn bộ chi tiết đơn hàng
        $itemsStmt = $this->pdo->query("
            SELECT 
                ct.*,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                sp.ten_sp,
                sp.anh_chinh
            FROM ctdh_chi_tiet_dh ct
            INNER JOIN bt_bien_the_sp bt ON ct.ma_bt = bt.ma_bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            ORDER BY ct.ma_ctdh ASC
        ");
        $itemsByOrder = [];
        foreach ($itemsStmt->fetchAll() as $item) {
            $itemsByOrder[$item['ma_dh']][] = $item;
        }

        foreach ($orders as &$order) {
            $orderId = $order['ma_dh'];
            $order['ma_dh_code'] = sprintf('DH%02d', $orderId);
            $order['items'] = $itemsByOrder[$orderId] ?? [];

            $currentPoints = (int) ($order['tong_diem_tich_luy'] ?? 0);
            $nextTier = null;
            foreach ($tiers as $t) {
                if ((int) $t['diem_toi_thieu'] > $currentPoints) {
                    $nextTier = $t;
                    break;
                }
            }
            $order['next_tier'] = $nextTier;
            if ($nextTier) {
                $target = (int) $nextTier['diem_toi_thieu'];
                $order['points_to_next_tier'] = max(0, $target - $currentPoints);
                $order['progress_percent'] = $target > 0 ? min(100, round(($currentPoints / $target) * 100, 1)) : 100;
                $order['will_upgrade_on_approve'] = ($currentPoints + (int) $order['diem_cong_du_kien']) >= $target;
            } else {
                $order['points_to_next_tier'] = 0;
                $order['progress_percent'] = 100;
                $order['will_upgrade_on_approve'] = false;
            }
        }
        unset($order);

        return $orders;
    }

    /**
     * Duyệt đơn hàng & Kích hoạt Loyalty Engine (1 PDO Transaction)
     * - Cập nhật trạng thái đơn hàng sang HOAN_THANH
     * - Cộng điểm tích lũy & điểm khả dụng vào thẻ thành viên
     * - Ghi lịch sử giao dịch điểm CONG_MUA_HANG
     * - Tự động đối soát và thăng hạng thành viên
     */
    public function approveOrder(int $maDh, string $newStatus = 'HOAN_THANH'): array
    {
        if ($maDh <= 0) {
            throw new Exception("Mã đơn hàng không hợp lệ.");
        }

        $this->pdo->beginTransaction();

        try {
            // 1. Khóa và lấy thông tin đơn hàng
            $stmtOrder = $this->pdo->prepare("SELECT * FROM dh_don_hang WHERE ma_dh = ? FOR UPDATE");
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

            // 2. Cập nhật trạng thái đơn hàng
            $stmtUpdateOrder = $this->pdo->prepare("UPDATE dh_don_hang SET trang_thai_dh = ? WHERE ma_dh = ?");
            $stmtUpdateOrder->execute([$newStatus, $maDh]);

            $diemCong = (int) $order['diem_cong_du_kien'];
            $maNd = (int) $order['ma_nd'];
            $isUpgraded = false;
            $tenHangMoi = '';
            $newTongDiem = 0;
            $newDiemKhaDung = 0;

            // 3. Nếu chuyển sang HOAN_THANH -> Kích hoạt Loyalty Engine
            if ($newStatus === 'HOAN_THANH') {
                // Khóa thẻ tích điểm của khách hàng
                $stmtCard = $this->pdo->prepare("SELECT * FROM ttd_the_tich_diem WHERE ma_nd = ? FOR UPDATE");
                $stmtCard->execute([$maNd]);
                $card = $stmtCard->fetch();

                if (!$card) {
                    // Tạo mới thẻ thành viên nếu chưa có
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

                // Tự động kiểm tra bậc hạng mới nhất dựa trên tổng điểm tích lũy
                $stmtTier = $this->pdo->prepare("
                    SELECT ma_hang, ten_hang 
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

                // Cập nhật thẻ tích điểm
                $stmtUpdateCard = $this->pdo->prepare("
                    UPDATE ttd_the_tich_diem 
                    SET tong_diem_tich_luy = ?, diem_kha_dung = ?, ma_hang = ?, ngay_cap_nhat = NOW() 
                    WHERE ma_the = ?
                ");
                $stmtUpdateCard->execute([$newTongDiem, $newDiemKhaDung, $targetMaHang, $card['ma_the']]);

                // Ghi lịch sử giao dịch điểm nếu có cộng điểm
                if ($diemCong > 0) {
                    $dhCode = sprintf('DH%02d', $maDh);
                    $stmtLedger = $this->pdo->prepare("
                        INSERT INTO lsd_lich_su_diem (ma_the, ma_dh, loai_giao_dich, so_diem_thay_doi, so_du_sau_gd, noi_dung, thoi_gian)
                        VALUES (?, ?, 'CONG_MUA_HANG', ?, ?, ?, NOW())
                    ");
                    $stmtLedger->execute([
                        $card['ma_the'],
                        $maDh,
                        $diemCong,
                        $newDiemKhaDung,
                        "Cộng {$diemCong} điểm mua hàng khi hoàn thành đơn #{$dhCode}"
                    ]);
                }
            }

            $this->pdo->commit();

            $dhCode = sprintf('DH%02d', $maDh);
            $msg = "Đã duyệt đơn #{$dhCode} sang Hoàn thành!";
            if ($diemCong > 0) {
                $msg .= " Đã cộng +{$diemCong} điểm cho khách hàng.";
            }
            if ($isUpgraded) {
                $msg .= " 🎉 Khách hàng đã được tự động nâng lên {$tenHangMoi}!";
            }

            return [
                'success'           => true,
                'message'           => $msg,
                'ma_dh'             => $maDh,
                'ma_dh_code'        => $dhCode,
                'new_status'        => $newStatus,
                'diem_cong'         => $diemCong,
                'tong_diem_moi'     => $newTongDiem,
                'diem_kha_dung_moi' => $newDiemKhaDung,
                'is_upgraded'       => $isUpgraded,
                'ten_hang_moi'      => $tenHangMoi
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

