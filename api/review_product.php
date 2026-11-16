<?php
/**
 * API ĐÁNH GIÁ SẢN PHẨM SAU MUA HÀNG & THƯỞNG NÓNG +10 ĐIỂM (api/review_product.php)
 * CSDL csdl_cua_hang_quan_ao
 * Lưu đánh giá vào ctdh_chi_tiet_dh, cộng 10 điểm vào ttd_the_tich_diem, ghi lsd_lich_su_diem
 * và tự động xét nâng hạng thành viên trong 1 Transaction an toàn.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

try {
    // 1. Kiểm tra đăng nhập
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

    $maCtdh  = (int)($data['ma_ctdh'] ?? 0);
    $maSp    = (int)($data['ma_sp'] ?? 0);
    $soSao   = (int)($data['so_sao'] ?? $data['so_sao_danh_gia'] ?? 5);
    $noiDung = trim($data['noi_dung_danh_gia'] ?? $data['noi_dung'] ?? '');

    if ($maCtdh <= 0 && $maSp <= 0) {
        throw new Exception('Vui lòng chỉ định sản phẩm hoặc chi tiết đơn hàng cần đánh giá.');
    }

    if ($soSao < 1 || $soSao > 5) {
        throw new Exception('Số sao đánh giá phải từ 1 đến 5 sao.');
    }

    if (empty($noiDung)) {
        throw new Exception('Vui lòng nhập nội dung đánh giá của bạn.');
    }

    $pdo = getDBConnection();

    // Bắt đầu Transaction để thực hiện Pessimistic Locking đồng bộ
    $pdo->beginTransaction();

    // 3. Khóa dòng chi tiết đơn hàng ctdh_chi_tiet_dh nối với dh_don_hang FOR UPDATE
    if ($maCtdh > 0) {
        $stmtCheck = $pdo->prepare("
            SELECT ct.ma_ctdh, ct.ma_dh, ct.so_sao_danh_gia, ct.da_cong_diem_dg, 
                   bt.mau_sac, bt.kich_co, sp.ten_sp, dh.ma_nd, dh.trang_thai_dh
            FROM ctdh_chi_tiet_dh ct
            INNER JOIN bt_bien_the_sp bt ON ct.ma_bt = bt.ma_bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
            WHERE ct.ma_ctdh = :ma_ctdh
            FOR UPDATE
        ");
        $stmtCheck->execute(['ma_ctdh' => $maCtdh]);
        $targetItem = $stmtCheck->fetch();

        if (!$targetItem) {
            throw new Exception('Chi tiết đơn hàng không tồn tại trong hệ thống.');
        }

        if ((int)$targetItem['ma_nd'] !== $currentUserId) {
            throw new Exception('Bạn không có quyền đánh giá đơn hàng của khách hàng khác.');
        }

        if ($targetItem['trang_thai_dh'] !== 'HOAN_THANH') {
            throw new Exception('Bạn chỉ có thể đánh giá sản phẩm sau khi đơn hàng đã chuyển sang trạng thái Hoàn thành.');
        }

        if ((int)$targetItem['da_cong_diem_dg'] === 1) {
            throw new Exception('Mục sản phẩm này trong đơn hàng đã được đánh giá và cộng điểm thưởng rồi.');
        }
    } else {
        // Fallback: Tìm dòng chi tiết đơn hàng chưa đánh giá theo ma_sp của khách
        $stmtCheckPurchased = $pdo->prepare("
            SELECT ct.ma_ctdh, ct.ma_dh, ct.so_sao_danh_gia, ct.da_cong_diem_dg, 
                   bt.mau_sac, bt.kich_co, sp.ten_sp, dh.ma_nd, dh.trang_thai_dh
            FROM ctdh_chi_tiet_dh ct
            INNER JOIN bt_bien_the_sp bt ON ct.ma_bt = bt.ma_bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
            WHERE dh.ma_nd = :ma_nd 
              AND bt.ma_sp = :ma_sp 
              AND dh.trang_thai_dh = 'HOAN_THANH'
              AND ct.da_cong_diem_dg = 0
            ORDER BY ct.ma_ctdh DESC
            LIMIT 1
            FOR UPDATE
        ");
        $stmtCheckPurchased->execute([
            'ma_nd' => $currentUserId,
            'ma_sp' => $maSp
        ]);
        $targetItem = $stmtCheckPurchased->fetch();

        if (!$targetItem) {
            throw new Exception('Bạn chỉ có thể đánh giá sau khi đã mua sản phẩm này và đơn hàng đã hoàn thành (hoặc bạn đã đánh giá rồi).');
        }
    }

    $maCtdh = (int)$targetItem['ma_ctdh'];
    $maDh   = (int)$targetItem['ma_dh'];
    $tenSp  = $targetItem['ten_sp'];

    // 4.1 Khóa thẻ tích điểm của khách hàng (Pessimistic Locking)
    $stmtCard = $pdo->prepare("SELECT * FROM ttd_the_tich_diem WHERE ma_nd = :ma_nd FOR UPDATE");
    $stmtCard->execute(['ma_nd' => $currentUserId]);
    $card = $stmtCard->fetch();

    if (!$card) {
        // Tự động tạo thẻ nếu chưa có
        $cardNo = 'AUR' . str_pad((string)$currentUserId, 5, '0', STR_PAD_LEFT);
        $stmtCreateCard = $pdo->prepare("
            INSERT INTO ttd_the_tich_diem (ma_nd, ma_hang, ma_so_the, tong_diem_tich_luy, diem_kha_dung, ngay_cap, ngay_cap_nhat)
            VALUES (?, 1, ?, 0, 0, NOW(), NOW())
        ");
        $stmtCreateCard->execute([$currentUserId, $cardNo]);

        $stmtCard->execute(['ma_nd' => $currentUserId]);
        $card = $stmtCard->fetch();
    }

    $maThe = (int)$card['ma_the'];
    $oldTongDiem = (int)$card['tong_diem_tich_luy'];
    $oldDiemKhaDung = (int)$card['diem_kha_dung'];
    $oldMaHang = (int)$card['ma_hang'];

    $diemThuong = 10;
    $newTongDiem = $oldTongDiem + $diemThuong;
    $newDiemKhaDung = $oldDiemKhaDung + $diemThuong;

    // 4.2 Cập nhật nội dung đánh giá vào ctdh_chi_tiet_dh
    $stmtUpdateCtdh = $pdo->prepare("
        UPDATE ctdh_chi_tiet_dh
        SET so_sao_danh_gia = :so_sao,
            noi_dung_danh_gia = :noi_dung,
            da_cong_diem_dg = 1
        WHERE ma_ctdh = :ma_ctdh
    ");
    $stmtUpdateCtdh->execute([
        'so_sao'    => $soSao,
        'noi_dung'  => $noiDung,
        'ma_ctdh'   => $maCtdh
    ]);

    // 4.3 Kiểm tra thăng hạng thành viên dựa trên newTongDiem
    $stmtTier = $pdo->prepare("
        SELECT ma_hang, ten_hang 
        FROM htv_hang_thanh_vien 
        WHERE diem_toi_thieu <= ? 
        ORDER BY diem_toi_thieu DESC 
        LIMIT 1
    ");
    $stmtTier->execute([$newTongDiem]);
    $tier = $stmtTier->fetch();

    $newMaHang = $tier ? (int)$tier['ma_hang'] : $oldMaHang;
    $tenHangMoi = $tier ? $tier['ten_hang'] : 'Hạng Đồng';
    $isUpgraded = ($newMaHang > $oldMaHang);

    // 4.4 Cập nhật thẻ tích điểm: cả 2 quỹ điểm
    $stmtUpdateCard = $pdo->prepare("
        UPDATE ttd_the_tich_diem
        SET tong_diem_tich_luy = :tong_diem,
            diem_kha_dung = :diem_kd,
            ma_hang = :ma_hang,
            ngay_cap_nhat = NOW()
        WHERE ma_the = :ma_the
    ");
    $stmtUpdateCard->execute([
        'tong_diem' => $newTongDiem,
        'diem_kd'   => $newDiemKhaDung,
        'ma_hang'   => $newMaHang,
        'ma_the'    => $maThe
    ]);

    // 4.5 Ghi sổ cái lịch sử biến động điểm (lsd_lich_su_diem)
    $noiDungLsd = sprintf('Thưởng +10 điểm đánh giá %d sao sản phẩm %s', $soSao, $tenSp);
    $stmtLsd = $pdo->prepare("
        INSERT INTO lsd_lich_su_diem (ma_the, ma_dh, loai_giao_dich, so_diem_thay_doi, so_du_sau_gd, noi_dung, thoi_gian)
        VALUES (?, ?, 'CONG_DANH_GIA', ?, ?, ?, NOW())
    ");
    $stmtLsd->execute([
        $maThe,
        $maDh,
        $diemThuong,
        $newDiemKhaDung,
        $noiDungLsd
    ]);

    $pdo->commit();

    // 5. Lấy thông tin người dùng để phản hồi
    $user = getCurrentUser();
    $hoTen = $user['ho_ten'] ?? 'Bạn';

    echo json_encode([
        'success'           => true,
        'message'           => 'Gửi đánh giá thành công! Bạn nhận được +10 điểm thưởng vào Thẻ thành viên.',
        'points_added'      => $diemThuong,
        'diem_kha_dung'     => $newDiemKhaDung,
        'tong_diem_tich_luy'=> $newTongDiem,
        'ten_hang'          => $tenHangMoi,
        'is_upgraded'       => $isUpgraded,
        'review'            => [
            'ma_ctdh'           => $maCtdh,
            'so_sao_danh_gia'   => $soSao,
            'noi_dung_danh_gia' => $noiDung,
            'da_cong_diem_dg'   => 1,
            'ho_ten'            => $hoTen,
            'ten_hang'          => $tenHangMoi,
            'mau_sac'           => $targetItem['mau_sac'] ?? '',
            'kich_co'           => $targetItem['kich_co'] ?? '',
            'ngay_dat'          => date('Y-m-d H:i:s'),
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
}
