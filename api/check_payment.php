<?php
/**
 * API KIỂM TRA TRẠNG THÁI THANH TOÁN ĐƠN HÀNG (api/check_payment.php)
 * Dùng cho cơ chế Polling tự động kiểm tra xem đơn hàng đã được chuyển khoản/xác nhận chưa
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

try {
    $currentUserId = getCurrentUserId();
    if ($currentUserId <= 0 || !isUserLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success'       => false,
            'require_login' => true,
            'message'       => 'Vui lòng đăng nhập để kiểm tra trạng thái thanh toán.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $maDh = (int) ($_GET['ma_dh'] ?? $_POST['ma_dh'] ?? 0);

    if ($maDh <= 0) {
        throw new Exception("Mã đơn hàng không hợp lệ.");
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT dh.*, nd.ho_ten, nd.sdt, nd.email
        FROM dh_don_hang dh
        INNER JOIN nd_nguoi_dung nd ON dh.ma_nd = nd.ma_nd
        WHERE dh.ma_dh = :ma_dh
    ");
    $stmt->execute(['ma_dh' => $maDh]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("Đơn hàng không tồn tại trong hệ thống.");
    }

    // Kiểm tra quyền: chỉ chủ đơn hàng hoặc admin mới được truy vấn
    if ((int) $order['ma_nd'] !== $currentUserId && !hasAdminAccess()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Bạn không có quyền truy vấn đơn hàng này.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $st = $order['trang_thai_dh'];
    $dhCode = 'DH' . str_pad((string) $order['ma_dh'], 2, '0', STR_PAD_LEFT);

    // Đơn được xem là đã thanh toán khi trạng thái đã sang HOAN_THANH hoặc DANG_GIAO
    $isPaid = ($st === 'HOAN_THANH' || $st === 'DANG_GIAO');

    $statusLabels = [
        'CHO_XAC_NHAN' => 'Chờ xác nhận',
        'DANG_GIAO'    => 'Đang giao hàng',
        'HOAN_THANH'   => 'Hoàn thành',
        'DA_HUY'       => 'Đã hủy',
    ];

    echo json_encode([
        'success'         => true,
        'paid'            => $isPaid,
        'ma_dh'           => (int) $order['ma_dh'],
        'ma_dh_code'      => $dhCode,
        'thanh_tien'      => (float) $order['thanh_tien'],
        'formatted_total' => formatVND($order['thanh_tien']),
        'pt_thanh_toan'   => $order['pt_thanh_toan'],
        'trang_thai_dh'   => $st,
        'status_label'    => $statusLabels[$st] ?? $st,
        'diem_cong'       => (int) $order['diem_cong_du_kien'],
        'ngay_dat'        => $order['ngay_dat'],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
