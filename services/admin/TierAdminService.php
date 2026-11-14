<?php
/**
 * DỊCH VỤ BÊN QUẢN TRỊ: Cấu hình 4 Bậc Hạng Thành Viên & Danh sách Thẻ Tích Điểm
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class TierAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy cấu hình 4 hạng thành viên kèm số lượng khách hàng đang ở mỗi hạng
     */
    public function getAllTiersWithMemberCount(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                htv.*,
                COUNT(ttd.ma_the) AS so_thanh_vien
            FROM htv_hang_thanh_vien htv
            LEFT JOIN ttd_the_tich_diem ttd ON htv.ma_hang = ttd.ma_hang
            GROUP BY htv.ma_hang
            ORDER BY htv.diem_toi_thieu ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách thẻ tích điểm của toàn bộ khách hàng thành viên từ CSDL
     */
    public function getAllMemberCards(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                ttd.*,
                nd.ho_ten,
                nd.ten_dang_nhap,
                nd.sdt,
                nd.email,
                htv.ten_hang,
                htv.phan_tram_giam,
                htv.he_so_tich_diem,
                htv.mau_sac_the
            FROM ttd_the_tich_diem ttd
            INNER JOIN nd_nguoi_dung nd ON ttd.ma_nd = nd.ma_nd
            INNER JOIN htv_hang_thanh_vien htv ON ttd.ma_hang = htv.ma_hang
            ORDER BY ttd.tong_diem_tich_luy DESC
        ");
        return $stmt->fetchAll();
    }
}

