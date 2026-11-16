<?php
/**
 * API ĐỔI ĐIỂM LẤY VOUCHER (api/redeem_voucher.php)
 * Thực thi trong 1 PDO Transaction: Khóa thẻ & voucher (Pessimistic Locking),
 * trừ điểm khả dụng, trừ số lượng voucher, lưu ví cá nhân và ghi sổ cái điểm.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

try {
    // 1. Kiểm tra xác thực đăng nhập khách hàng
    $currentUserId = getCurrentUserId();

    if ($currentUserId <= 0) {
        http_response_code(401);
        echo json_encode([
            'success'       => false,
            'require_login' => true,
            'message'       => 'Vui lòng đăng nhập để thực hiện thao tác này.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Tiếp nhận dữ liệu (JSON body hoặc POST)
    $rawInput = file_get_contents('php://input');
    $body = [];
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $body = $decoded;
        }
    }
    $data = array_merge($_POST, $body);

    $maPgg = (int) ($data['ma_pgg'] ?? 0);
    if ($maPgg <= 0) {
        throw new Exception("Vui lòng chọn phiếu giảm giá hợp lệ.");
    }

    $pdo = getDBConnection();

    // 3. Khởi tạo PDO Transaction
    $pdo->beginTransaction();

    // 3.1. Khóa và kiểm tra thẻ tích điểm của khách hàng
    $stmtCard = $pdo->prepare("SELECT * FROM ttd_the_tich_diem WHERE ma_nd = ? FOR UPDATE");
    $stmtCard->execute([$currentUserId]);
    $card = $stmtCard->fetch();

    if (!$card) {
        throw new Exception("Bạn chưa có thẻ tích điểm để thực hiện đổi quà.");
    }

    // 3.2. Khóa và kiểm tra thông tin Voucher
    $stmtVch = $pdo->prepare("
        SELECT pgg.*, htv.ten_hang AS ten_hang_yeu_cau 
        FROM pgg_phieu_giam_gia pgg
        INNER JOIN htv_hang_thanh_vien htv ON pgg.ma_hang_yeu_cau = htv.ma_hang
        WHERE pgg.ma_pgg = ? 
        FOR UPDATE
    ");
    $stmtVch->execute([$maPgg]);
    $voucher = $stmtVch->fetch();

    if (!$voucher) {
        throw new Exception("Phiếu giảm giá không tồn tại trong hệ thống.");
    }

    // 3.3. Kiểm tra thời hạn áp dụng
    $now = date('Y-m-d H:i:s');
    if ($now < $voucher['ngay_bd'] || $now >= $voucher['ngay_kt']) {
        throw new Exception("Voucher \"{$voucher['ma_code']}\" hiện chưa đến đợt hoặc đã hết hạn đổi.");
    }

    // 3.4. Kiểm tra số lượng voucher còn lại
    if ((int) $voucher['so_luong'] <= 0) {
        throw new Exception("Voucher \"{$voucher['ma_code']}\" đã hết lượt đổi hoặc đang tạm dừng tiếp nhận đổi mới.");
    }

    // 3.5. Kiểm tra hạng thành viên yêu cầu
    if ((int) $card['ma_hang'] < (int) $voucher['ma_hang_yeu_cau']) {
        throw new Exception("Voucher này chỉ dành cho thành viên từ {$voucher['ten_hang_yeu_cau']} trở lên.");
    }

    // 3.6. Kiểm tra số dư điểm khả dụng
    $diemCanDoi = (int) $voucher['diem_can_doi'];
    $diemHienCo = (int) $card['diem_kha_dung'];
    if ($diemHienCo < $diemCanDoi) {
        $thieu = $diemCanDoi - $diemHienCo;
        throw new Exception("Bạn không đủ điểm khả dụng để đổi voucher này (Còn thiếu {$thieu} điểm).");
    }

    // 3.7. Kiểm tra xem khách đã có voucher này trong ví chưa
    $stmtWallet = $pdo->prepare("
        SELECT ma_vi_vch, trang_thai_su_dung 
        FROM vkh_vi_voucher_kh 
        WHERE ma_nd = ? AND ma_pgg = ? 
        LIMIT 1 
        FOR UPDATE
    ");
    $stmtWallet->execute([$currentUserId, $maPgg]);
    $existing = $stmtWallet->fetch();

    if ($existing) {
        if ((int) $existing['trang_thai_su_dung'] === 0) {
            throw new Exception("Bạn đã có voucher \"{$voucher['ma_code']}\" trong Ví cá nhân chưa sử dụng.");
        } else {
            throw new Exception("Bạn đã từng đổi và sử dụng voucher \"{$voucher['ma_code']}\" này.");
        }
    }

    // 3.8. Trừ điểm khả dụng trong thẻ tích điểm
    $newDiemKhaDung = $diemHienCo - $diemCanDoi;
    $stmtDeductCard = $pdo->prepare("
        UPDATE ttd_the_tich_diem 
        SET diem_kha_dung = ?, ngay_cap_nhat = NOW() 
        WHERE ma_the = ?
    ");
    $stmtDeductCard->execute([$newDiemKhaDung, $card['ma_the']]);

    // 3.9. Trừ số lượng kho voucher
    $stmtDeductVch = $pdo->prepare("
        UPDATE pgg_phieu_giam_gia 
        SET so_luong = so_luong - 1 
        WHERE ma_pgg = ?
    ");
    $stmtDeductVch->execute([$maPgg]);

    // 3.10. Thêm voucher mới vào ví khách hàng (vkh_vi_voucher_kh)
    $stmtAddWallet = $pdo->prepare("
        INSERT INTO vkh_vi_voucher_kh (ma_nd, ma_pgg, ngay_doi, trang_thai_su_dung)
        VALUES (?, ?, NOW(), 0)
    ");
    $stmtAddWallet->execute([$currentUserId, $maPgg]);
    $maViVch = (int) $pdo->lastInsertId();

    // 3.11. Ghi 1 dòng giao dịch TRU_DOI_VOUCHER vào sổ cái lịch sử điểm (lsd_lich_su_diem)
    $stmtLedger = $pdo->prepare("
        INSERT INTO lsd_lich_su_diem (
            ma_the, ma_dh, loai_giao_dich, so_diem_thay_doi,
            so_du_sau_gd, noi_dung, thoi_gian
        ) VALUES (
            ?, NULL, 'TRU_DOI_VOUCHER', ?,
            ?, ?, NOW()
        )
    ");
    $stmtLedger->execute([
        $card['ma_the'],
        -$diemCanDoi,
        $newDiemKhaDung,
        "Đổi {$diemCanDoi} điểm lấy Voucher {$voucher['ma_code']} ({$voucher['ten_pgg']})"
    ]);

    // 3.12. Hoàn tất Commit Transaction
    $pdo->commit();

    echo json_encode([
        'success'           => true,
        'message'           => "Chúc mừng! Bạn đã đổi thành công Voucher \"{$voucher['ma_code']}\" (-{$diemCanDoi} điểm)!",
        'data'              => [
            'ma_vi_vch'         => $maViVch,
            'ma_pgg'            => $maPgg,
            'ma_code'           => $voucher['ma_code'],
            'ten_pgg'           => $voucher['ten_pgg'],
            'diem_can_doi'      => $diemCanDoi,
            'diem_kha_dung_moi' => $newDiemKhaDung,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
