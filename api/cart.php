<?php
/**
 * API GIỎ HÀNG TRỰC TUYẾN (api/cart.php)
 * Xử lý các thao tác giỏ hàng: get, add, update, remove
 * Trả về định dạng JSON chuẩn (Content-Type: application/json)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/store/CartCheckoutService.php';

try {
    // 1. Kiểm tra xác thực khách hàng qua $_SESSION
    $currentUserId = getCurrentUserId();
    if (!isset($_SESSION['current_user_id'])) {
        $_SESSION['current_user_id'] = $currentUserId;
    }

    if ($currentUserId <= 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng đăng nhập để thao tác giỏ hàng!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Tiếp nhận dữ liệu (Hỗ trợ cả JSON payload qua Fetch API lẫn FormData / POST / GET)
    $rawInput = file_get_contents('php://input');
    $body = [];
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $body = $decoded;
        }
    }

    $requestData = array_merge($_GET, $_POST, $body);
    $action = strtolower(trim($requestData['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'GET' ? 'get' : 'add')));

    $cartService = new CartCheckoutService();

    switch ($action) {
        case 'get':
            $summary = $cartService->getCartSummary($currentUserId);
            echo json_encode([
                'success' => true,
                'message' => 'Lấy dữ liệu giỏ hàng thành công',
                'data'    => $summary,
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'add':
            $maBt = (int) ($requestData['ma_bt'] ?? 0);
            $soLuong = (int) ($requestData['so_luong_mua'] ?? $requestData['so_luong'] ?? 1);

            if ($maBt <= 0) {
                throw new Exception('Vui lòng chọn biến thể sản phẩm hợp lệ.');
            }
            if ($soLuong <= 0) {
                $soLuong = 1;
            }

            $result = $cartService->addToCart($currentUserId, $maBt, $soLuong);
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'data'    => $result['summary'],
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'update':
            $maBt = (int) ($requestData['ma_bt'] ?? 0);
            $soLuong = (int) ($requestData['so_luong_mua'] ?? $requestData['so_luong'] ?? 0);

            if ($maBt <= 0) {
                throw new Exception('Mã biến thể sản phẩm không hợp lệ.');
            }

            $result = $cartService->updateCartQuantity($currentUserId, $maBt, $soLuong);
            echo json_encode([
                'success' => true,
                'action'  => $result['action'],
                'message' => $result['message'],
                'data'    => $result['summary'],
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'remove':
            $maBt = (int) ($requestData['ma_bt'] ?? 0);
            if ($maBt <= 0) {
                throw new Exception('Mã biến thể sản phẩm không hợp lệ.');
            }

            $result = $cartService->removeFromCart($currentUserId, $maBt);
            echo json_encode([
                'success' => true,
                'action'  => $result['action'],
                'message' => $result['message'],
                'data'    => $result['summary'],
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception("Thao tác giỏ hàng '{$action}' không được hỗ trợ.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
