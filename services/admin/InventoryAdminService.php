<?php
/**
 * DỊCH VỤ BÊN QUẢN TRỊ: Quản lý Tồn kho Biến thể SKU & Lịch sử Phiếu nhập kho
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class InventoryAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy danh sách tất cả 20 biến thể SKU cùng số lượng nhập, đã bán và tồn kho thực tế từ CSDL
     */
    public function getAllSkuInventory(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong) 
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            ORDER BY bt.ma_bt ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách chứng từ Phiếu nhập kho từ bảng pnk_phieu_nhap_kho
     */
    public function getImportReceipts(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                pnk.*,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                sp.ten_sp,
                nd.ho_ten AS nguoi_nhap_ten
            FROM pnk_phieu_nhap_kho pnk
            INNER JOIN bt_bien_the_sp bt ON pnk.ma_bt = bt.ma_bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN nd_nguoi_dung nd ON pnk.ma_nd_nhap = nd.ma_nd
            ORDER BY pnk.ngay_nhap DESC, pnk.ma_pnk DESC
        ");
        return $stmt->fetchAll();
    }
}

