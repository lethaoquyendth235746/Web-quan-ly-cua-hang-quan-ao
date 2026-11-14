<?php
/**
 * DỊCH VỤ BÊN QUẢN TRỊ: Quản lý Chiến dịch Phiếu giảm giá (Voucher Loyalty) & Ví Voucher
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class VoucherAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy danh sách tất cả chiến dịch Voucher từ bảng pgg_phieu_giam_gia kèm số lượt đã đổi
     */
    public function getAllVouchers(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                pgg.*,
                htv.ten_hang AS ten_hang_yeu_cau,
                htv.mau_sac_the,
                (SELECT COUNT(*) FROM vkh_vi_voucher_kh vkh WHERE vkh.ma_pgg = pgg.ma_pgg) AS so_luot_da_doi,
                (SELECT COUNT(*) FROM vkh_vi_voucher_kh vkh WHERE vkh.ma_pgg = pgg.ma_pgg AND vkh.trang_thai_su_dung = 1) AS so_luot_da_dung
            FROM pgg_phieu_giam_gia pgg
            INNER JOIN htv_hang_thanh_vien htv ON pgg.ma_hang_yeu_cau = htv.ma_hang
            ORDER BY pgg.ma_pgg ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy lịch sử đổi voucher của khách hàng từ bảng vkh_vi_voucher_kh
     */
    public function getCustomerVoucherWalletLogs(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                vkh.*,
                nd.ho_ten,
                nd.sdt,
                pgg.ma_code,
                pgg.ten_pgg,
                pgg.diem_can_doi
            FROM vkh_vi_voucher_kh vkh
            INNER JOIN nd_nguoi_dung nd ON vkh.ma_nd = nd.ma_nd
            INNER JOIN pgg_phieu_giam_gia pgg ON vkh.ma_pgg = pgg.ma_pgg
            ORDER BY vkh.ngay_doi DESC
        ");
        return $stmt->fetchAll();
    }
}

