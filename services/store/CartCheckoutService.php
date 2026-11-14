<?php
/**
 * DỊCH VỤ BÊN CỬA HÀNG: Giỏ hàng trực tuyến, Sổ địa chỉ, Ví Voucher & Tính toán Thanh toán đa tầng
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/AddressService.php';

class CartCheckoutService
{
    private PDO $pdo;
    private AddressService $addressService;

    public function __construct()
    {
        $this->pdo = getDBConnection();
        $this->addressService = new AddressService();
    }

    public function getAddressService(): AddressService
    {
        return $this->addressService;
    }

    /**
     * Lấy danh sách sản phẩm trong giỏ hàng của khách hàng từ bảng gh_gio_hang
     */
    public function getCustomerCart(int $maNd): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                gh.ma_gh,
                gh.ma_nd,
                gh.ma_bt,
                gh.so_luong_mua,
                gh.ngay_them,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                sp.ten_sp,
                sp.gia_niem_yet,
                sp.gia_khuyen_mai,
                sp.anh_chinh,
                (
                    SELECT ha.duong_dan_anh 
                    FROM ha_hinh_anh_sp ha 
                    WHERE ha.ma_sp = sp.ma_sp AND ha.mau_sac_lien_ket = bt.mau_sac 
                    LIMIT 1
                ) AS anh_theo_mau
            FROM gh_gio_hang gh
            INNER JOIN bt_bien_the_sp bt ON gh.ma_bt = bt.ma_bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            WHERE gh.ma_nd = :ma_nd
            ORDER BY gh.ngay_them DESC
        ");
        $stmt->execute(['ma_nd' => $maNd]);
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $imgFile = $item['anh_theo_mau'] ?: $item['anh_chinh'];
            $item['image_url'] = resolveProductImage($imgFile);
            $item['thanh_tien'] = (float) $item['gia_ban_rieng'] * (int) $item['so_luong_mua'];
        }
        unset($item);

        return $items;
    }

    /**
     * Lấy sổ địa chỉ nhận hàng của khách hàng từ bảng dc_dia_chi_kh
     */
    public function getCustomerAddresses(int $maNd): array
    {
        return $this->addressService->getAddressesByUserId($maNd);
    }

    public function getAddressById(int $maDc, int $maNd): ?array
    {
        return $this->addressService->getAddressById($maDc, $maNd);
    }

    public function addAddress(int $maNd, array $data): array
    {
        return $this->addressService->createAddress($maNd, $data);
    }

    public function updateAddress(int $maDc, int $maNd, array $data): array
    {
        return $this->addressService->updateAddress($maDc, $maNd, $data);
    }

    public function deleteAddress(int $maDc, int $maNd): array
    {
        return $this->addressService->deleteAddress($maDc, $maNd);
    }

    public function setDefaultAddress(int $maDc, int $maNd): array
    {
        return $this->addressService->setDefaultAddress($maDc, $maNd);
    }

    /**
     * Lấy danh sách Voucher chưa sử dụng trong Ví cá nhân của khách hàng (vkh_vi_voucher_kh)
     */
    public function getCustomerAvailableVouchers(int $maNd): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                vkh.ma_vi_vch,
                vkh.ma_nd,
                vkh.ma_pgg,
                vkh.ngay_doi,
                vkh.trang_thai_su_dung,
                pgg.ma_code,
                pgg.ten_pgg,
                pgg.loai_giam,
                pgg.gia_tri_giam,
                pgg.don_toi_thieu,
                pgg.ngay_kt
            FROM vkh_vi_voucher_kh vkh
            INNER JOIN pgg_phieu_giam_gia pgg ON vkh.ma_pgg = pgg.ma_pgg
            WHERE vkh.ma_nd = :ma_nd
              AND vkh.trang_thai_su_dung = 0
            ORDER BY vkh.ngay_doi DESC
        ");
        $stmt->execute(['ma_nd' => $maNd]);
        return $stmt->fetchAll();
    }

    /**
     * Tính toán tổng kết đơn hàng đa tầng (Multi-layer Checkout Summary)
     * dựa trên giỏ hàng thực tế và hạng thẻ của khách trong CSDL
     */
    public function calculateCheckoutSummary(array $cartItems, ?array $loyaltyProfile, ?array $selectedVoucher = null, int $pointsToUse = 0): array
    {
        $subtotal = 0;
        $totalQty = 0;
        foreach ($cartItems as $item) {
            $subtotal += (float) $item['thanh_tien'];
            $totalQty += (int) $item['so_luong_mua'];
        }

        $tierDiscountPercent = (float) ($loyaltyProfile['phan_tram_giam'] ?? 0);
        $tierMultiplier = (float) ($loyaltyProfile['he_so_tich_diem'] ?? 1.0);
        $tierDiscountAmount = round($subtotal * ($tierDiscountPercent / 100));

        $voucherDiscountAmount = 0;
        if ($selectedVoucher && $subtotal >= (float) $selectedVoucher['don_toi_thieu']) {
            if ($selectedVoucher['loai_giam'] === 'PHAN_TRAM') {
                $voucherDiscountAmount = round($subtotal * ((float) $selectedVoucher['gia_tri_giam'] / 100));
            } else {
                $voucherDiscountAmount = (float) $selectedVoucher['gia_tri_giam'];
            }
        }

        // Quy đổi 1 điểm tiêu dùng = 1.000đ giảm trực tiếp (khớp Đơn #5 trong SQL mẫu: 50 điểm = 50.000đ)
        $availablePoints = (int) ($loyaltyProfile['diem_kha_dung'] ?? 0);
        $validPointsToUse = min($pointsToUse, $availablePoints);
        $pointsDiscountAmount = $validPointsToUse * 1000;

        $finalTotal = max(0, $subtotal - $tierDiscountAmount - $voucherDiscountAmount - $pointsDiscountAmount);
        $expectedPoints = (int) floor(($finalTotal / POINTS_PER_VND) * $tierMultiplier);

        return [
            'total_qty'               => $totalQty,
            'subtotal'                => $subtotal,
            'tier_name'               => $loyaltyProfile['ten_hang'] ?? 'Khách vãng lai',
            'tier_discount_percent'   => $tierDiscountPercent,
            'tier_discount_amount'    => $tierDiscountAmount,
            'tier_multiplier'         => $tierMultiplier,
            'voucher_discount_amount' => $voucherDiscountAmount,
            'points_used'             => $validPointsToUse,
            'points_discount_amount'  => $pointsDiscountAmount,
            'final_total'             => $finalTotal,
            'expected_points'         => $expectedPoints,
        ];
    }
}

