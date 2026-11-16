<?php
/**
 * API KHÁCH HÀNG TỰ HỦY ĐƠN HÀNG (api/cancel_order.php)
 * Thực thi trong 1 PDO Transaction (SELECT ... FOR UPDATE):
 * 1. Khóa dòng đơn hàng: SELECT * FROM dh_don_hang WHERE ma_dh = :ma_dh AND ma_nd = :ma_nd FOR UPDATE
 * 2. Chỉ cho phép khách hàng tự hủy khi trang_thai_dh === 'CHO_XAC_NHAN'
 *    (nếu đơn đã chuyển sang DANG_GIAO, HOAN_THANH hoặc DA_HUY thì từ chối và báo lỗi
 *    "Đơn hàng đã được xử lý hoặc đang giao, không thể tự hủy")
 * 3. Cập nhật dh_don_hang.trang_thai_dh = 'DA_HUY'
 * 4. Hoàn trả tồn kho: ctdh_chi_tiet_dh -> bt_bien_the_sp.so_luong_ton
 * 5. Hoàn trả Voucher: vkh_vi_voucher_kh (trang_thai_su_dung = 0, ngay_su_dung = NULL)
 * 6. Hoàn trả điểm khả dụng đã tiêu (nếu có)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/store/LoyaltyStoreService.php';

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

    $maDh = (int) ($data['ma_dh'] ?? 0);
    if ($maDh <= 0) {
        throw new Exception("Mã đơn hàng không hợp lệ.");
    }

    // 3. Thực thi hủy đơn trong 1 PDO Transaction qua LoyaltyStoreService
    $loyaltyService = new LoyaltyStoreService();
    $result = $loyaltyService->cancelCustomerOrder($maDh, $currentUserId);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
