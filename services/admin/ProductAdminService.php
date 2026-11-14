<?php
/**
 * DỊCH VỤ BÊN QUẢN TRỊ: Quản lý Danh mục & Sản phẩm thời trang
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class ProductAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy danh sách toàn bộ danh mục sản phẩm (cả cha và con)
     */
    public function getAllCategories(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                dm.*,
                cha.ten_dm AS ten_dm_cha,
                (SELECT COUNT(*) FROM sp_san_pham sp WHERE sp.ma_dm = dm.ma_dm) AS so_san_pham
            FROM dm_danh_muc_sp dm
            LEFT JOIN dm_danh_muc_sp cha ON dm.ma_dm_cha = cha.ma_dm
            ORDER BY COALESCE(dm.ma_dm_cha, dm.ma_dm), dm.ma_dm_cha IS NOT NULL, dm.thu_tu_hien_thi ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách sản phẩm kèm danh mục, số lượng biến thể SKU và tổng tồn kho từ CSDL
     */
    public function getAllProducts(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                sp.*,
                dm.ten_dm,
                dm.duong_dan_tinh AS dm_slug,
                cha.ten_dm AS ten_dm_cha,
                (SELECT COUNT(*) FROM bt_bien_the_sp bt WHERE bt.ma_sp = sp.ma_sp) AS so_bien_the,
                (SELECT COALESCE(SUM(bt.so_luong_ton), 0) FROM bt_bien_the_sp bt WHERE bt.ma_sp = sp.ma_sp) AS tong_ton_kho
            FROM sp_san_pham sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            LEFT JOIN dm_danh_muc_sp cha ON dm.ma_dm_cha = cha.ma_dm
            ORDER BY sp.ma_sp ASC
        ");
        $products = $stmt->fetchAll();

        foreach ($products as &$p) {
            $p['anh_chinh_url'] = resolveProductImage($p['anh_chinh']);
            $p['ma_sp_code'] = sprintf('SP-%03d', $p['ma_sp']);
        }
        unset($p);

        return $products;
    }
}

