<?php
/**
 * API QUẢN LÝ SỔ ĐỊA CHỈ NHẬN HÀNG (api/address.php)
 * CRUD sổ địa chỉ (dc_dia_chi_kh) & Đặt mặc định
 * Bắt buộc kiểm tra ma_nd để ngăn chặn lỗ hổng IDOR
 * Hỗ trợ cả AJAX JSON (Fetch API) và Form POST truyền thống
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/store/AddressService.php';

header('Content-Type: application/json; charset=utf-8');

$addressService = new AddressService();

// Lấy dữ liệu gửi lên (Hỗ trợ cả application/x-www-form-urlencoded, multipart/form-data và JSON body)
$rawBody = file_get_contents('php://input');
$jsonBody = [];
if ($rawBody && $decoded = json_decode($rawBody, true)) {
    $jsonBody = $decoded;
}
$input = array_merge($_POST, $jsonBody);
$action = $input['action'] ?? $_GET['action'] ?? 'list';

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || !empty($jsonBody);

// 1. Kiểm tra xác thực người dùng (Chống truy cập trái phép)
$currentUserId = getCurrentUserId();
if ($currentUserId <= 0 || !isUserLoggedIn()) {
    http_response_code(401);
    $resp = [
        'success'       => false,
        'require_login' => true,
        'message'       => 'Vui lòng đăng nhập tài khoản để quản lý sổ địa chỉ.',
    ];
    if ($isAjax) {
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: ../login.php?error=login_required');
    exit;
}

// 2. Xử lý các hành động
switch ($action) {
    case 'list':
        $addresses = $addressService->getAddressesByUserId($currentUserId);
        echo json_encode([
            'success'   => true,
            'addresses' => $addresses,
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'get':
        $maDc = (int)($input['ma_dc'] ?? $_GET['ma_dc'] ?? 0);
        $address = $addressService->getAddressById($maDc, $currentUserId);
        if ($address) {
            echo json_encode(['success' => true, 'address' => $address], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy địa chỉ hoặc bạn không có quyền xem.'], JSON_UNESCAPED_UNICODE);
        }
        exit;

    case 'create':
        $res = $addressService->createAddress($currentUserId, $input);
        if ($res['success']) {
            $res['addresses'] = $addressService->getAddressesByUserId($currentUserId);
        }

        if ($isAjax) {
            if (!$res['success']) http_response_code(400);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $redirect = $input['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '../index.php?page=checkout';
        header('Location: ' . $redirect);
        exit;

    case 'update':
        $maDc = (int)($input['ma_dc'] ?? 0);
        $res = $addressService->updateAddress($maDc, $currentUserId, $input);
        if ($res['success']) {
            $res['addresses'] = $addressService->getAddressesByUserId($currentUserId);
        }

        if ($isAjax) {
            if (!$res['success']) http_response_code(400);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $redirect = $input['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '../index.php?page=checkout';
        header('Location: ' . $redirect);
        exit;

    case 'delete':
        $maDc = (int)($input['ma_dc'] ?? $_GET['ma_dc'] ?? 0);
        $res = $addressService->deleteAddress($maDc, $currentUserId);
        if ($res['success']) {
            $res['addresses'] = $addressService->getAddressesByUserId($currentUserId);
        }

        if ($isAjax) {
            if (!$res['success']) http_response_code(400);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $redirect = $input['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '../index.php?page=checkout';
        header('Location: ' . $redirect);
        exit;

    case 'set_default':
        $maDc = (int)($input['ma_dc'] ?? $_GET['ma_dc'] ?? 0);
        $res = $addressService->setDefaultAddress($maDc, $currentUserId);
        if ($res['success']) {
            $res['addresses'] = $addressService->getAddressesByUserId($currentUserId);
        }

        if ($isAjax) {
            if (!$res['success']) http_response_code(400);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $redirect = $input['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '../index.php?page=checkout';
        header('Location: ' . $redirect);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.'], JSON_UNESCAPED_UNICODE);
        exit;
}
