<?php
/**
 * DỊCH VỤ BÊN CỬA HÀNG: Giỏ hàng trực tuyến, Sổ địa chỉ, Ví Voucher & Tính toán Thanh toán đa tầng
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class CartCheckoutService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
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
            $item['formatted_price'] = formatVND($item['gia_ban_rieng']);
            $item['formatted_total'] = formatVND($item['thanh_tien']);
        }
        unset($item);

        return $items;
    }

    /**
     * Lấy tổng kết giỏ hàng kèm thông tin ưu đãi hạng thành viên (Dùng cho API Giỏ hàng)
     */
    public function getCartSummary(int $maNd): array
    {
        $cartItems = $this->getCustomerCart($maNd);
        $totalQty = 0;
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $totalQty += (int) $item['so_luong_mua'];
            $subtotal += (float) $item['thanh_tien'];
        }

        // Lấy hệ số tích điểm từ thẻ tích điểm của khách hàng
        $stmtTier = $this->pdo->prepare("
            SELECT ttd.ma_hang, htv.ten_hang, htv.he_so_tich_diem, htv.phan_tram_giam
            FROM ttd_the_tich_diem ttd
            INNER JOIN htv_hang_thanh_vien htv ON ttd.ma_hang = htv.ma_hang
            WHERE ttd.ma_nd = :ma_nd
            LIMIT 1
        ");
        $stmtTier->execute(['ma_nd' => $maNd]);
        $tier = $stmtTier->fetch();

        $tierMultiplier = (float) ($tier['he_so_tich_diem'] ?? 1.0);
        $tierName = $tier['ten_hang'] ?? 'Hạng Đồng';
        $expectedPoints = (int) floor(($subtotal / POINTS_PER_VND) * $tierMultiplier);

        return [
            'items'              => $cartItems,
            'total_qty'          => $totalQty,
            'subtotal'           => $subtotal,
            'formatted_subtotal' => formatVND($subtotal),
            'expected_points'    => $expectedPoints,
            'tier_name'          => $tierName,
            'tier_multiplier'    => $tierMultiplier,
        ];
    }

    /**
     * Thêm sản phẩm biến thể vào giỏ hàng (Action: add)
     */
    public function addToCart(int $maNd, int $maBt, int $soLuongMua = 1): array
    {
        if ($soLuongMua <= 0) {
            $soLuongMua = 1;
        }

        // 1. Kiểm tra tồn tại biến thể và số lượng tồn kho trong bt_bien_the_sp
        $stmtBt = $this->pdo->prepare("
            SELECT bt.ma_bt, bt.ma_sp, bt.ma_sku, bt.kich_co, bt.mau_sac, bt.gia_ban_rieng, bt.so_luong_ton, bt.trang_thai, sp.ten_sp
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            WHERE bt.ma_bt = :ma_bt
        ");
        $stmtBt->execute(['ma_bt' => $maBt]);
        $variant = $stmtBt->fetch();

        if (!$variant || (int) $variant['trang_thai'] === 0) {
            throw new Exception("Biến thể sản phẩm không tồn tại hoặc đã ngừng kinh doanh.");
        }

        $stock = (int) $variant['so_luong_ton'];
        if ($stock <= 0) {
            throw new Exception("Sản phẩm \"{$variant['ten_sp']}\" ({$variant['mau_sac']} - {$variant['kich_co']}) hiện đã hết hàng.");
        }

        // 2. Kiểm tra xem biến thể đã có trong gh_gio_hang của khách hàng chưa
        $stmtCheck = $this->pdo->prepare("
            SELECT ma_gh, so_luong_mua
            FROM gh_gio_hang
            WHERE ma_nd = :ma_nd AND ma_bt = :ma_bt
        ");
        $stmtCheck->execute(['ma_nd' => $maNd, 'ma_bt' => $maBt]);
        $existing = $stmtCheck->fetch();

        $notice = null;
        if ($existing) {
            $currentQty = (int) $existing['so_luong_mua'];
            if ($currentQty >= $stock) {
                throw new Exception("Bạn đã có {$currentQty} sản phẩm trong giỏ, đạt mức tồn kho tối đa ({$stock}).");
            }

            $newQty = $currentQty + $soLuongMua;
            if ($newQty > $stock) {
                $newQty = $stock;
                $notice = "Đã giới hạn số lượng bằng tồn kho tối đa ({$stock} sản phẩm).";
            }

            $stmtUpdate = $this->pdo->prepare("
                UPDATE gh_gio_hang
                SET so_luong_mua = :so_luong, ngay_them = NOW()
                WHERE ma_gh = :ma_gh
            ");
            $stmtUpdate->execute(['so_luong' => $newQty, 'ma_gh' => $existing['ma_gh']]);
        } else {
            $buyQty = $soLuongMua;
            if ($buyQty > $stock) {
                $buyQty = $stock;
                $notice = "Số lượng yêu cầu vượt quá tồn kho. Đã thêm tối đa {$stock} sản phẩm.";
            }

            $stmtInsert = $this->pdo->prepare("
                INSERT INTO gh_gio_hang (ma_nd, ma_bt, so_luong_mua, ngay_them)
                VALUES (:ma_nd, :ma_bt, :so_luong, NOW())
            ");
            $stmtInsert->execute([
                'ma_nd'    => $maNd,
                'ma_bt'    => $maBt,
                'so_luong' => $buyQty,
            ]);
        }

        $summary = $this->getCartSummary($maNd);
        return [
            'message' => $notice ?: "Đã thêm sản phẩm \"{$variant['ten_sp']}\" vào giỏ hàng thành công!",
            'summary' => $summary,
        ];
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ (Action: update)
     * Nếu số lượng <= 0 thì tự động xóa dòng đó khỏi giỏ.
     */
    public function updateCartQuantity(int $maNd, int $maBt, int $soLuongMoi): array
    {
        if ($soLuongMoi <= 0) {
            $stmtDel = $this->pdo->prepare("DELETE FROM gh_gio_hang WHERE ma_nd = :ma_nd AND ma_bt = :ma_bt");
            $stmtDel->execute(['ma_nd' => $maNd, 'ma_bt' => $maBt]);
            return [
                'action'  => 'removed',
                'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
                'summary' => $this->getCartSummary($maNd),
            ];
        }

        // Kiểm tra tồn kho
        $stmtBt = $this->pdo->prepare("
            SELECT bt.so_luong_ton, sp.ten_sp
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            WHERE bt.ma_bt = :ma_bt
        ");
        $stmtBt->execute(['ma_bt' => $maBt]);
        $variant = $stmtBt->fetch();

        if (!$variant) {
            throw new Exception("Biến thể sản phẩm không tồn tại.");
        }

        $stock = (int) $variant['so_luong_ton'];
        $notice = null;
        $validQty = $soLuongMoi;
        if ($validQty > $stock) {
            $validQty = $stock;
            $notice = "Số lượng đã được tự động điều chỉnh về mức tồn kho tối đa ({$stock}).";
        }

        $stmtUp = $this->pdo->prepare("
            UPDATE gh_gio_hang
            SET so_luong_mua = :so_luong
            WHERE ma_nd = :ma_nd AND ma_bt = :ma_bt
        ");
        $stmtUp->execute(['so_luong' => $validQty, 'ma_nd' => $maNd, 'ma_bt' => $maBt]);

        return [
            'action'  => 'updated',
            'message' => $notice ?: 'Đã cập nhật số lượng thành công!',
            'summary' => $this->getCartSummary($maNd),
        ];
    }

    /**
     * Xóa sản phẩm khỏi giỏ hàng (Action: remove)
     */
    public function removeFromCart(int $maNd, int $maBt): array
    {
        $stmtDel = $this->pdo->prepare("DELETE FROM gh_gio_hang WHERE ma_nd = :ma_nd AND ma_bt = :ma_bt");
        $stmtDel->execute(['ma_nd' => $maNd, 'ma_bt' => $maBt]);

        return [
            'action'  => 'removed',
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
            'summary' => $this->getCartSummary($maNd),
        ];
    }

    /**
     * Làm trống toàn bộ giỏ hàng
     */
    public function clearCart(int $maNd): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM gh_gio_hang WHERE ma_nd = :ma_nd");
        return $stmt->execute(['ma_nd' => $maNd]);
    }

    /**
     * Lấy sổ địa chỉ nhận hàng của khách hàng từ bảng dc_dia_chi_kh
     */
    public function getCustomerAddresses(int $maNd): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM dc_dia_chi_kh
            WHERE ma_nd = :ma_nd
            ORDER BY mac_dinh DESC, ma_dc ASC
        ");
        $stmt->execute(['ma_nd' => $maNd]);
        return $stmt->fetchAll();
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
