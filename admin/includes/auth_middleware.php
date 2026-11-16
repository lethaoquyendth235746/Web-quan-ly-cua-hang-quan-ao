<?php
/**
 * PHÂN HỆ QUẢN TRỊ: Middleware Phân quyền (admin/includes/auth_middleware.php)
 * Nối tiếp với admin/includes/auth_check.php để đảm bảo tính tương thích
 */
<<<<<<< HEAD
require_once __DIR__ . '/auth_check.php';
=======

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

// 1. Kiểm tra trạng thái đăng nhập
if (!isUserLoggedIn()) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ../login.php?redirect=' . $currentUrl . '&error=login_required');
    exit;
}

// 2. Lấy thông tin người dùng hiện tại
$currentUser = getCurrentUser();

if (!$currentUser) {
    header('Location: ../login.php?error=session_expired');
    exit;
}

$maVt = (int)($currentUser['ma_vt'] ?? 0);

// 3. Phân quyền: Chỉ cho phép ma_vt = 1 (Admin) và ma_vt = 2 (Nhân viên), nếu không chuyển hướng về index.php
if (!in_array($maVt, [1, 2], true)) {
    header('Location: ../index.php');
    exit;
}

// 4. Nếu hợp lệ, gán chắc chắn $_SESSION['current_admin_id']
$_SESSION['current_admin_id'] = (int)$currentUser['ma_nd'];

// Alias để các trang admin dùng thống nhất
$adminCurrentUser = $currentUser;

/**
 * Kiểm tra người dùng hiện tại có phải Quản trị viên cấp cao (ma_vt = 1) không.
 * Dùng để phân biệt quyền Super-Admin và Nhân viên.
 */
function isSuperAdminRole(): bool
{
    global $adminCurrentUser;
    return isset($adminCurrentUser['ma_vt']) && (int)$adminCurrentUser['ma_vt'] === 1;
}

>>>>>>> origin/main
