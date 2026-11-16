<?php
/**
 * API XỬ LÝ ĐƠN HÀNG DÀNH CHO ADMIN / NHÂN VIÊN (api/admin_order.php)
 * Xử lý duyệt đơn hàng, kích hoạt Loyalty Engine, cộng điểm và xét nâng hạng
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/admin/OrderAdminService.php';
require_once __DIR__ . '/../services/admin/LoyaltyEngineService.php';

try {
    // 1. Kiểm tra xác thực & quyền hạn Admin/Nhân viên
    if (!hasAdminAccess()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Bạn không có quyền thực hiện thao tác quản trị này.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Tiếp nhận dữ liệu (JSON body hoặc POST form)
    $rawInput = file_get_contents('php://input');
    $body = [];
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $body = $decoded;
        }
    }
    $data = array_merge($_POST, $body);

    $action = strtolower(trim($data['action'] ?? 'approve'));
    $maDh = (int) ($data['ma_dh'] ?? 0);
    $newStatus = trim($data['trang_thai_dh'] ?? 'HOAN_THANH');

    if ($maDh <= 0) {
        throw new Exception("Mã đơn hàng không hợp lệ.");
    }

    $loyaltyEngine = new LoyaltyEngineService();

    switch ($action) {
        case 'approve':
            $result = $loyaltyEngine->approveOrder($maDh, $newStatus);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception("Hành động '{$action}' không được hỗ trợ.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
