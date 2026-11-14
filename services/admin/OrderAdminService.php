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
}

