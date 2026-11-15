<?php
/**
 * API WEBHOOK THANH TOÁN NGÂN HÀNG & MÔ PHỎNG TÍN HIỆU BÁO CÓ (api/payment_webhook.php)
 * - Tương thích chuẩn Webhook ngân hàng (SePay / Casso / MBBank Gateway)
 * - Tiếp nhận tín hiệu tiền vào -> Tự động khớp đơn hàng -> Duyệt Hoàn thành -> Kích hoạt Loyalty Engine cộng điểm
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/admin/OrderAdminService.php';

try {
    $rawInput = file_get_contents('php://input');
    $body = [];
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $body = $decoded;
        }
    }
    $data = array_merge($_GET, $_POST, $body);

    $pdo = getDBConnection();

    // 1. Tìm mã đơn hàng từ payload
    $maDh = 0;
    if (!empty($data['ma_dh'])) {
        $maDh = (int) $data['ma_dh'];
    } elseif (!empty($data['order_id'])) {
        $maDh = (int) $data['order_id'];
    } elseif (!empty($data['content']) || !empty($data['description'])) {
        // Tự động bóc tách từ nội dung chuyển khoản của ngân hàng (VD: "DH04", "#DH04", "THANH TOAN DH12")
        $text = (string) ($data['content'] ?? $data['description'] ?? '');
        if (preg_match('/DH(\d+)/i', $text, $matches)) {
            $maDh = (int) $matches[1];
        }
    }

    if ($maDh <= 0) {
        throw new Exception("Không tìm thấy mã đơn hàng hợp lệ trong dữ liệu webhook.");
    }

    // 2. Kiểm tra thông tin đơn hàng trong CSDL
    $stmt = $pdo->prepare("SELECT * FROM dh_don_hang WHERE ma_dh = ?");
    $stmt->execute([$maDh]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("Đơn hàng #DH" . sprintf('%02d', $maDh) . " không tồn tại.");
    }

    $dhCode = 'DH' . str_pad((string) $order['ma_dh'], 2, '0', STR_PAD_LEFT);

    // Nếu đơn hàng đã hoàn thành trước đó
    if ($order['trang_thai_dh'] === 'HOAN_THANH') {
        echo json_encode([
            'success'    => true,
            'paid'       => true,
            'message'    => "Đơn hàng #{$dhCode} đã được thanh toán và duyệt hoàn thành trước đó.",
            'ma_dh'      => $maDh,
            'ma_dh_code' => $dhCode,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Thực hiện duyệt đơn sang HOAN_THANH và kích hoạt Loyalty Engine qua OrderAdminService
    $orderAdminService = new OrderAdminService();
    $result = $orderAdminService->approveOrder($maDh, 'HOAN_THANH');

    echo json_encode([
        'success'    => true,
        'paid'       => true,
        'message'    => "Xác nhận chuyển khoản thành công cho đơn hàng #{$dhCode}!",
        'ma_dh'      => $maDh,
        'ma_dh_code' => $dhCode,
        'thanh_tien' => (float) $order['thanh_tien'],
        'detail'     => $result
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
