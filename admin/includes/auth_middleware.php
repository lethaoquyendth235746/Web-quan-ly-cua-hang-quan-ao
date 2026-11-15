<?php
/**
 * PHÂN HỆ QUẢN TRỊ: Middleware Phân quyền truy cập (admin/includes/auth_middleware.php)
 * Quy tắc bắt buộc: Chỉ cho phép người dùng có ma_vt = 1 (Admin) hoặc ma_vt = 2 (Nhân viên) truy cập.
 * - Chưa đăng nhập -> Chuyển hướng sang trang login.php kèm tham số redirect.
 * - Đã đăng nhập nhưng là Khách hàng (ma_vt = 3) -> Chặn truy cập với mã lỗi HTTP 403 Forbidden.
 */

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
