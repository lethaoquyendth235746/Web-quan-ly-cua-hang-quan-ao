<?php
/**
 * ĐĂNG XUẤT HỆ THỐNG AN TOÀN (logout.php)
 * Hủy toàn bộ $_SESSION, xóa session cookie và chuyển hướng về login.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Xóa sạch mảng $_SESSION
$_SESSION = [];

// 2. Xóa session cookie trên trình duyệt nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Hủy phiên làm việc
session_destroy();

// 4. Khởi tạo phiên mới để gắn cờ thông báo đăng xuất
session_start();
$_SESSION['explicit_logout'] = true;

// 5. Chuyển hướng an toàn về login.php
header('Location: login.php?logout=1');
exit;
