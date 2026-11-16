<?php
/**
 * HẰNG SỐ TOÀN CỤC CỦA HỆ THỐNG (config/constants.php)
 * Khai báo BASE_URL và các hằng số vai trò, hạng thành viên
 */

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseDir = rtrim($scriptDir, '/');
    if (preg_match('#/(admin|api|store)$#', $baseDir)) {
        $baseDir = preg_replace('#/(admin|api|store)$#', '', $baseDir);
    }
    define('BASE_URL', $protocol . $host . $baseDir);
}

// Phân quyền vai trò hệ thống (bảng vt_vai_tro)
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 1);       // 1: Quản trị viên (Admin)
}
if (!defined('ROLE_STAFF')) {
    define('ROLE_STAFF', 2);       // 2: Nhân viên cửa hàng
}
if (!defined('ROLE_CUSTOMER')) {
    define('ROLE_CUSTOMER', 3);    // 3: Khách hàng thành viên
}

// Hạng thành viên (bảng htv_hang_thanh_vien)
if (!defined('TIER_BRONZE')) {
    define('TIER_BRONZE', 1);      // Hạng Đồng
}
if (!defined('TIER_SILVER')) {
    define('TIER_SILVER', 2);      // Hạng Bạc
}
if (!defined('TIER_GOLD')) {
    define('TIER_GOLD', 3);        // Hạng Vàng
}
if (!defined('TIER_DIAMOND')) {
    define('TIER_DIAMOND', 4);     // Hạng Kim Cương
}
