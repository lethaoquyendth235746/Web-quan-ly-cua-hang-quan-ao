<?php
/**
 * DỊCH VỤ BÊN CỬA HÀNG: Khách hàng Thân thiết (Loyalty), Thẻ tích điểm, Hạng thành viên, Kho Voucher & Lịch sử điểm
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class LoyaltyStoreService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy danh sách tài khoản khách hàng (ma_vt = 3) để phục vụ chuyển đổi kiểm thử trên Header
     */
    public function getDemoCustomers(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                nd.ma_nd,
                nd.ho_ten,
                nd.ten_dang_nhap,
                nd.email,
                ttd.ma_so_the,
                ttd.tong_diem_tich_luy,
                ttd.diem_kha_dung,
                htv.ma_hang,
                htv.ten_hang,
                htv.mau_sac_the
            FROM nd_nguoi_dung nd
            LEFT JOIN ttd_the_tich_diem ttd ON nd.ma_nd = ttd.ma_nd
            LEFT JOIN htv_hang_thanh_vien htv ON ttd.ma_hang = htv.ma_hang
            WHERE nd.ma_vt = 3 AND nd.trang_thai = 1
            ORDER BY nd.ma_nd ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy toàn bộ 4 bậc Hạng thành viên từ CSDL
     */
    public function getAllTiers(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM htv_hang_thanh_vien ORDER BY diem_toi_thieu ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy thông tin hồ sơ + Thẻ tích điểm + Tiến độ lên hạng tiếp theo của khách hàng hiện tại
     */
    public function getCustomerLoyaltyProfile(int $maNd): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                nd.ma_nd,
                nd.ho_ten,
                nd.ten_dang_nhap,
                nd.sdt,
                nd.email,
                nd.anh_dai_dien,
                ttd.ma_the,
                ttd.ma_so_the,
                ttd.tong_diem_tich_luy,
                ttd.diem_kha_dung,
                ttd.ngay_cap,
                htv.ma_hang,
                htv.ten_hang,
                htv.diem_toi_thieu,
                htv.phan_tram_giam,
                htv.he_so_tich_diem,
                htv.mo_ta_uu_dai,
                htv.mau_sac_the
            FROM nd_nguoi_dung nd
            LEFT JOIN ttd_the_tich_diem ttd ON nd.ma_nd = ttd.ma_nd
            LEFT JOIN htv_hang_thanh_vien htv ON ttd.ma_hang = htv.ma_hang
            WHERE nd.ma_nd = :ma_nd
            LIMIT 1
        ");
        $stmt->execute(['ma_nd' => $maNd]);
        $profile = $stmt->fetch();

        if (!$profile) {
            return null;
        }

        // Tìm hạng tiếp theo dựa trên tổng điểm tích lũy
        $tiers = $this->getAllTiers();
        $nextTier = null;
        foreach ($tiers as $t) {
            if ((int) $t['diem_toi_thieu'] > (int) $profile['tong_diem_tich_luy']) {
                $nextTier = $t;
                break;
            }
        }

        if ($nextTier) {
            $targetPoints = (int) $nextTier['diem_toi_thieu'];
            $currentPoints = (int) $profile['tong_diem_tich_luy'];
            $pointsNeeded = max(0, $targetPoints - $currentPoints);
            $progressPercent = $targetPoints > 0 ? min(100, round(($currentPoints / $targetPoints) * 100, 1)) : 100;
        } else {
            $targetPoints = (int) $profile['tong_diem_tich_luy'];
            $pointsNeeded = 0;
            $progressPercent = 100;
        }

        $profile['next_tier'] = $nextTier;
        $profile['points_needed'] = $pointsNeeded;
        $profile['progress_percent'] = $progressPercent;
        $profile['all_tiers'] = $tiers;

        return $profile;
    }

    /**
     * Lấy danh sách chiến dịch Phiếu giảm giá (Kho đổi điểm Voucher) kèm trạng thái đổi của khách
     */
    public function getVoucherCatalogForCustomer(int $maNd, int $customerTierId, int $availablePoints): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                pgg.*,
                htv.ten_hang AS ten_hang_yeu_cau,
                htv.diem_toi_thieu AS moc_diem_hang
            FROM pgg_phieu_giam_gia pgg
            INNER JOIN htv_hang_thanh_vien htv ON pgg.ma_hang_yeu_cau = htv.ma_hang
            ORDER BY pgg.diem_can_doi ASC
        ");
        $vouchers = $stmt->fetchAll();

        // Kiểm tra những voucher khách đã có trong ví
        $walletStmt = $this->pdo->prepare("
            SELECT ma_pgg, trang_thai_su_dung, ngay_doi, ngay_su_dung
            FROM vkh_vi_voucher_kh
            WHERE ma_nd = :ma_nd
        ");
        $walletStmt->execute(['ma_nd' => $maNd]);
        $ownedMap = [];
        foreach ($walletStmt->fetchAll() as $w) {
            $ownedMap[$w['ma_pgg']] = $w;
        }

        foreach ($vouchers as &$v) {
            $vid = $v['ma_pgg'];
            $v['owned_info'] = $ownedMap[$vid] ?? null;
            $v['is_tier_eligible'] = $customerTierId >= (int) $v['ma_hang_yeu_cau'];
            $v['is_points_eligible'] = $availablePoints >= (int) $v['diem_can_doi'];
            $v['is_in_stock'] = (int) $v['so_luong'] > 0;
            $v['can_redeem'] = $v['is_tier_eligible'] && $v['is_points_eligible'] && $v['is_in_stock'] && empty($v['owned_info']);
        }
        unset($v);

        return $vouchers;
    }

    /**
     * Lấy lịch sử biến động điểm từ bảng lsd_lich_su_diem của thẻ khách hàng
     */
    public function getPointLedgerByCard(?int $maThe): array
    {
        if (!$maThe) {
            return [];
        }
        $stmt = $this->pdo->prepare("
            SELECT 
                lsd.*,
                dh.tong_tien_hang,
                dh.thanh_tien,
                dh.trang_thai_dh
            FROM lsd_lich_su_diem lsd
            LEFT JOIN dh_don_hang dh ON lsd.ma_dh = dh.ma_dh
            WHERE lsd.ma_the = :ma_the
            ORDER BY lsd.thoi_gian DESC, lsd.ma_ls_diem DESC
        ");
        $stmt->execute(['ma_the' => $maThe]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách đơn hàng của khách hàng kèm điểm dự kiến / trạng thái
     */
    public function getCustomerOrders(int $maNd): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                dh.*,
                pgg.ma_code AS voucher_code,
                (SELECT COUNT(*) FROM ctdh_chi_tiet_dh ct WHERE ct.ma_dh = dh.ma_dh) AS so_mat_hang
            FROM dh_don_hang dh
            LEFT JOIN pgg_phieu_giam_gia pgg ON dh.ma_pgg = pgg.ma_pgg
            WHERE dh.ma_nd = :ma_nd
            ORDER BY dh.ngay_dat DESC
        ");
        $stmt->execute(['ma_nd' => $maNd]);
        return $stmt->fetchAll();
    }
}

