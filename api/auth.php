<?php
/**
 * API XÁC THỰC NGƯỜI DÙNG (api/auth.php)
 * Tiếp nhận yêu cầu Đăng nhập, Đăng ký thành viên mới & Đăng xuất
 * Hỗ trợ cả AJAX JSON (Fetch API) và Form POST truyền thống
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/store/AuthService.php';

$authService = new AuthService();

// Lấy dữ liệu gửi lên (Hỗ trợ cả application/x-www-form-urlencoded, multipart/form-data và JSON body)
$rawBody = file_get_contents('php://input');
$jsonBody = [];
if ($rawBody && $decoded = json_decode($rawBody, true)) {
    $jsonBody = $decoded;
}
$input = array_merge($_POST, $jsonBody);
$action = $input['action'] ?? $_GET['action'] ?? '';

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || !empty($jsonBody);

switch ($action) {
    case 'login':
        $identifier = $input['identifier'] ?? $input['ten_dang_nhap'] ?? $input['email'] ?? '';
        $password   = $input['password'] ?? $input['mat_khau'] ?? '';
        $redirectUrl = $input['redirect'] ?? $_GET['redirect'] ?? '';

        $result = $authService->login($identifier, $password);

        if ($result['success']) {
            $user = $result['user'];
            $maVt = (int)$user['ma_vt'];

            // Xác định đích chuyển hướng thông minh
            if (!empty($redirectUrl) && !preg_match('/^https?:\/\//i', $redirectUrl)) {
                $targetRedirect = $redirectUrl;
            } elseif (in_array($maVt, [1, 2], true)) {
                $targetRedirect = 'admin/index.php';
            } else {
                $targetRedirect = 'index.php';
            }

            $result['redirect'] = $targetRedirect;
        }

        if ($isAjax) {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($result['success']) {
            header('Location: ../' . ltrim($result['redirect'], '/'));
            exit;
        } else {
            $_SESSION['auth_error'] = $result['message'];
            header('Location: ../login.php?error=' . urlencode($result['message']));
            exit;
        }

    case 'register':
        $result = $authService->register($input);

        if ($result['success']) {
            $targetRedirect = 'index.php?page=loyalty&registered=1';
            $result['redirect'] = $targetRedirect;
        }

        if ($isAjax) {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($result['success']) {
            $_SESSION['auth_flash'] = [
                'type'    => 'success',
                'message' => $result['message'],
            ];
            header('Location: ../index.php?page=loyalty&registered=1');
            exit;
        } else {
            $_SESSION['auth_error'] = $result['message'];
            header('Location: ../login.php?tab=register&error=' . urlencode($result['message']));
            exit;
        }

    case 'logout':
        $authService->logout();
        $targetRedirect = 'login.php?logout=success';

        if ($isAjax) {
            echo json_encode([
                'success'  => true,
                'message'  => 'Đã đăng xuất tài khoản thành công.',
                'redirect' => $targetRedirect,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: ../' . $targetRedirect);
        exit;

    case 'check':
        $user = $authService->getCurrentUser();
        echo json_encode([
            'logged_in' => ($user !== null),
            'user'      => $user,
        ], JSON_UNESCAPED_UNICODE);
        exit;

    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Hành động (action) không hợp lệ.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
}
