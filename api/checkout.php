<?php
/**
 * API TRANSACTION ĐẶT HÀNG & KHẤU TRỪ TỒN KHO (api/checkout.php)
 * Sử dụng PDO Database Transaction ($pdo->beginTransaction(), commit(), rollBack())
 * Khóa tồn kho (FOR UPDATE), tính toán xác thực đa tầng, trừ kho, voucher, điểm và làm trống giỏ hàng.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/store/CartCheckoutService.php';

try {
    // 1. Kiểm tra xác thực phiên đăng nhập của khách hàng
    $currentUserId = getCurrentUserId();
    if (!isset($_SESSION['current_user_id'])) {
        $_SESSION['current_user_id'] = $currentUserId;
    }

    if ($currentUserId <= 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng đăng nhập để tiến hành đặt hàng.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Tiếp nhận dữ liệu đặt hàng
    $rawInput = file_get_contents('php://input');
    $body = [];
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $body = $decoded;
        }
    }
    $data = array_merge($_POST, $body);

    $maDc = (int) ($data['ma_dc'] ?? 0);
    $ptThanhToan = strtoupper(trim($data['pt_thanh_toan'] ?? 'COD'));
    $maPgg = !empty($data['ma_pgg']) ? (int) $data['ma_pgg'] : null;
    $dungDiem = !empty($data['dung_diem']);

    if (!in_array($ptThanhToan, ['COD', 'CHUYEN_KHOAN'], true)) {
        throw new Exception('Phương thức thanh toán không hợp lệ.');
    }

    $pdo = getDBConnection();

    // 3. Khởi tạo PDO Database Transaction
    $pdo->beginTransaction();

    // 3.1. Xác thực địa chỉ nhận hàng thuộc quyền sở hữu của khách hàng
    $stmtAddr = $pdo->prepare("SELECT * FROM dc_dia_chi_kh WHERE ma_dc = ? AND ma_nd = ?");
    $stmtAddr->execute([$maDc, $currentUserId]);
    $address = $stmtAddr->fetch();
    if (!$address) {
        throw new Exception('Vui lòng chọn địa chỉ nhận hàng hợp lệ.');
    }

    // 3.2. Lấy danh sách sản phẩm trong giỏ hàng và khóa dòng dữ liệu (Pessimistic Locking)
    $stmtCart = $pdo->prepare("
        SELECT gh.ma_gh, gh.ma_nd, gh.ma_bt, gh.so_luong_mua,
               bt.ma_sku, bt.gia_ban_rieng, bt.so_luong_ton, bt.trang_thai,
               sp.ten_sp
        FROM gh_gio_hang gh
        INNER JOIN bt_bien_the_sp bt ON gh.ma_bt = bt.ma_bt
        INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
        WHERE gh.ma_nd = ?
        FOR UPDATE
    ");
    $stmtCart->execute([$currentUserId]);
    $cartItems = $stmtCart->fetchAll();

    if (empty($cartItems)) {
        throw new Exception('Giỏ hàng của bạn đang trống! Vui lòng chọn sản phẩm trước khi đặt hàng.');
    }

    // 3.3. Kiểm tra & Khóa tồn kho từng biến thể SKU chống xung đột race condition
    $tongTienHang = 0;
    $stmtLockVariant = $pdo->prepare("
        SELECT so_luong_ton, gia_ban_rieng, trang_thai 
        FROM bt_bien_the_sp 
        WHERE ma_bt = ? 
        FOR UPDATE
    ");

    foreach ($cartItems as $item) {
        $stmtLockVariant->execute([$item['ma_bt']]);
        $variant = $stmtLockVariant->fetch();

        if (!$variant || (int) $variant['trang_thai'] === 0) {
            throw new Exception("Sản phẩm \"{$item['ten_sp']}\" (SKU: {$item['ma_sku']}) hiện đã ngừng kinh doanh.");
        }

        $stock = (int) $variant['so_luong_ton'];
        $buyQty = (int) $item['so_luong_mua'];

        if ($stock < $buyQty) {
            throw new Exception("Sản phẩm \"{$item['ten_sp']}\" (SKU: {$item['ma_sku']}) chỉ còn {$stock} sản phẩm trong kho, không đủ số lượng đặt mua ({$buyQty}).");
        }

        $tongTienHang += (float) $variant['gia_ban_rieng'] * $buyQty;
    }

    // 3.4. Tính toán & Xác thực lại giá trị ở Backend (Server-side multi-tier calculation)
    // a. Chiết khấu Hạng thành viên (htv_hang_thanh_vien)
    $stmtCard = $pdo->prepare("
        SELECT ttd.ma_the, ttd.ma_hang, ttd.diem_kha_dung, ttd.tong_diem_tich_luy,
               htv.ten_hang, htv.phan_tram_giam, htv.he_so_tich_diem
        FROM ttd_the_tich_diem ttd
        INNER JOIN htv_hang_thanh_vien htv ON ttd.ma_hang = htv.ma_hang
        WHERE ttd.ma_nd = ?
        FOR UPDATE
    ");
    $stmtCard->execute([$currentUserId]);
    $loyaltyCard = $stmtCard->fetch();

    $tierDiscountPercent = (float) ($loyaltyCard['phan_tram_giam'] ?? 0);
    $tierMultiplier = (float) ($loyaltyCard['he_so_tich_diem'] ?? 1.0);
    $tienGiamHangTv = round($tongTienHang * ($tierDiscountPercent / 100));

    // b. Kiểm tra tính hợp lệ của Voucher trong ví cá nhân (vkh_vi_voucher_kh)
    $tienGiamVoucher = 0;
    $appliedMaPgg = null;
    if ($maPgg !== null && $maPgg > 0) {
        $stmtVch = $pdo->prepare("
            SELECT vkh.ma_vi_vch, vkh.trang_thai_su_dung, pgg.ma_pgg, pgg.ma_code, pgg.ten_pgg,
                   pgg.loai_giam, pgg.gia_tri_giam, pgg.don_toi_thieu, pgg.ngay_bd, pgg.ngay_kt
            FROM vkh_vi_voucher_kh vkh
            INNER JOIN pgg_phieu_giam_gia pgg ON vkh.ma_pgg = pgg.ma_pgg
            WHERE vkh.ma_nd = ? AND vkh.ma_pgg = ? AND vkh.trang_thai_su_dung = 0
            LIMIT 1
            FOR UPDATE
        ");
        $stmtVch->execute([$currentUserId, $maPgg]);
        $voucher = $stmtVch->fetch();

        if (!$voucher) {
            throw new Exception('Voucher không tồn tại trong Ví của bạn hoặc đã được sử dụng.');
        }

        $now = date('Y-m-d H:i:s');
        if ($now < $voucher['ngay_bd'] || $now > $voucher['ngay_kt']) {
            throw new Exception("Voucher \"{$voucher['ma_code']}\" hiện đã hết hạn sử dụng.");
        }

        if ($tongTienHang < (float) $voucher['don_toi_thieu']) {
            $minVnd = formatVND($voucher['don_toi_thieu']);
            throw new Exception("Đơn hàng phải đạt từ {$minVnd} để áp dụng voucher \"{$voucher['ma_code']}\".");
        }

        if ($voucher['loai_giam'] === 'PHAN_TRAM') {
            $tienGiamVoucher = round($tongTienHang * ((float) $voucher['gia_tri_giam'] / 100));
        } else {
            $tienGiamVoucher = (float) $voucher['gia_tri_giam'];
        }
        $appliedMaPgg = (int) $voucher['ma_pgg'];
    }

    // c. Kiểm tra và tính toán Điểm tích lũy tiêu dùng (ttd_the_tich_diem)
    $diemSuDung = 0;
    $tienGiamDiem = 0;
    if ($dungDiem && $loyaltyCard) {
        $availablePoints = (int) $loyaltyCard['diem_kha_dung'];
        if ($availablePoints > 0) {
            // Quy chuẩn: 1 điểm = 1.000đ, tối đa 50 điểm/đơn
            $diemSuDung = min(50, $availablePoints);
            $tienGiamDiem = $diemSuDung * 1000;
        }
    }

    // d. Thành tiền cuối cùng và điểm thưởng dự kiến
    $thanhTien = max(0, $tongTienHang - $tienGiamHangTv - $tienGiamVoucher - $tienGiamDiem);
    $diemCongDuKien = (int) floor(($thanhTien / POINTS_PER_VND) * $tierMultiplier);

    // 3.5. Tạo đơn đặt hàng mới vào bảng dh_don_hang
    $stmtOrder = $pdo->prepare("
        INSERT INTO dh_don_hang (
            ma_nd, ma_pgg, tong_tien_hang, tien_giam_hang_tv,
            tien_giam_voucher, diem_su_dung, thanh_tien,
            diem_cong_du_kien, pt_thanh_toan, trang_thai_dh, ngay_dat
        ) VALUES (
            :ma_nd, :ma_pgg, :tong_tien_hang, :tien_giam_hang_tv,
            :tien_giam_voucher, :diem_su_dung, :thanh_tien,
            :diem_cong_du_kien, :pt_thanh_toan, 'CHO_XAC_NHAN', NOW()
        )
    ");
    $stmtOrder->execute([
        'ma_nd'             => $currentUserId,
        'ma_pgg'            => $appliedMaPgg,
        'tong_tien_hang'    => $tongTienHang,
        'tien_giam_hang_tv' => $tienGiamHangTv,
        'tien_giam_voucher' => $tienGiamVoucher,
        'diem_su_dung'      => $diemSuDung,
        'thanh_tien'        => $thanhTien,
        'diem_cong_du_kien' => $diemCongDuKien,
        'pt_thanh_toan'     => $ptThanhToan,
    ]);
    $maDh = (int) $pdo->lastInsertId();

    // 3.6. Lưu chi tiết mặt hàng vào ctdh_chi_tiet_dh & Khấu trừ kho bt_bien_the_sp
    $stmtItem = $pdo->prepare("
        INSERT INTO ctdh_chi_tiet_dh (
            ma_dh, ma_bt, so_luong, don_gia_luc_mua, thanh_tien_muc
        ) VALUES (
            ?, ?, ?, ?, ?
        )
    ");
    $stmtDeductStock = $pdo->prepare("
        UPDATE bt_bien_the_sp 
        SET so_luong_ton = so_luong_ton - ? 
        WHERE ma_bt = ?
    ");

    foreach ($cartItems as $item) {
        $donGia = (float) $item['gia_ban_rieng'];
        $soLuong = (int) $item['so_luong_mua'];
        $thanhTienMuc = $donGia * $soLuong;

        $stmtItem->execute([$maDh, $item['ma_bt'], $soLuong, $donGia, $thanhTienMuc]);
        $stmtDeductStock->execute([$soLuong, $item['ma_bt']]);
    }

    // 3.7. Cập nhật trạng thái Voucher trong ví cá nhân nếu có dùng
    if ($appliedMaPgg !== null) {
        $stmtUseVch = $pdo->prepare("
            UPDATE vkh_vi_voucher_kh 
            SET trang_thai_su_dung = 1, ngay_su_dung = NOW() 
            WHERE ma_nd = ? AND ma_pgg = ? AND trang_thai_su_dung = 0 
            LIMIT 1
        ");
        $stmtUseVch->execute([$currentUserId, $appliedMaPgg]);
    }

    // 3.8. Cập nhật Điểm tích lũy và ghi nhận sổ cái lịch sử (lsd_lich_su_diem)
    if ($diemSuDung > 0 && $loyaltyCard) {
        $stmtDeductCard = $pdo->prepare("
            UPDATE ttd_the_tich_diem 
            SET diem_kha_dung = diem_kha_dung - ? 
            WHERE ma_the = ?
        ");
        $stmtDeductCard->execute([$diemSuDung, $loyaltyCard['ma_the']]);

        $newBalance = (int) $loyaltyCard['diem_kha_dung'] - $diemSuDung;
        $dhCode = 'DH' . str_pad((string) $maDh, 2, '0', STR_PAD_LEFT);

        $stmtLedger = $pdo->prepare("
            INSERT INTO lsd_lich_su_diem (
                ma_the, ma_dh, loai_giao_dich, so_diem_thay_doi,
                so_du_sau_gd, noi_dung, thoi_gian
            ) VALUES (
                ?, ?, 'TRU_TIEU_DIEM', ?,
                ?, ?, NOW()
            )
        ");
        $stmtLedger->execute([
            $loyaltyCard['ma_the'],
            $maDh,
            -$diemSuDung,
            $newBalance,
            "Tiêu dùng {$diemSuDung} điểm thanh toán đơn hàng #{$dhCode}"
        ]);
    }

    // 3.9. Làm trống giỏ hàng trực tuyến của khách hàng (gh_gio_hang)
    $stmtClearCart = $pdo->prepare("DELETE FROM gh_gio_hang WHERE ma_nd = ?");
    $stmtClearCart->execute([$currentUserId]);

    // 3.10. Hoàn tất và Commit Transaction
    $pdo->commit();

    // 4. Trả về kết quả JSON chuẩn
    $dhCode = 'DH' . str_pad((string) $maDh, 2, '0', STR_PAD_LEFT);
    $bankId = 'MB';
    $bankName = 'MB Bank (Ngân hàng TMCP Quân Đội)';
    $bankAccount = '0901235629';
    $bankAccountName = 'AURA STUDIO';

    $vietQrUrl = sprintf(
        'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s&accountName=%s',
        $bankId,
        $bankAccount,
        (int) $thanhTien,
        urlencode($dhCode),
        urlencode($bankAccountName)
    );

    echo json_encode([
        'success'         => true,
        'message'         => 'Đặt hàng thành công!',
        'ma_dh'           => $maDh,
        'ma_dh_code'      => $dhCode,
        'tong_tien_hang'  => $tongTienHang,
        'tien_giam_hang'  => $tienGiamHangTv,
        'tien_giam_vch'   => $tienGiamVoucher,
        'tien_giam_diem'  => $tienGiamDiem,
        'diem_su_dung'    => $diemSuDung,
        'thanh_tien'      => $thanhTien,
        'formatted_total' => formatVND($thanhTien),
        'expected_points' => $diemCongDuKien,
        'pt_thanh_toan'   => $ptThanhToan,
        'vietqr'          => [
            'bank_id'      => $bankId,
            'bank_name'    => $bankName,
            'account_no'   => $bankAccount,
            'account_name' => $bankAccountName,
            'amount'       => (int) $thanhTien,
            'add_info'     => $dhCode,
            'qr_url'       => $vietQrUrl,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
