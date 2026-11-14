<?php
/**
 * ĐĂNG XUẤT HỆ THỐNG (logout.php)
 * Xóa sạch phiên làm việc an toàn và điều hướng người dùng
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/services/store/AuthService.php';

$authService = new AuthService();
$authService->logout();

$redirect = $_GET['redirect'] ?? 'login.php?logout=success';

// Chỉ cho phép chuyển hướng nội bộ an toàn (chống Open Redirect)
if (preg_match('/^https?:\/\//i', $redirect)) {
    $redirect = 'index.php';
}

header('Location: ' . $redirect);
exit;
