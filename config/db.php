<?php
/**
 * TẦNG KẾT NỐI (CONNECTION LAYER)
 * Quản lý kết nối PDO MySQL tới CSDL: csdl_cua_hang_quan_ao
 * Kèm các hàm tiện ích định dạng và quản lý phiên người dùng từ CSDL
 */

require_once __DIR__ . '/database.php';

define('POINTS_PER_VND', 10000); // Quy chuẩn: 10.000đ = 1 điểm cơ bản

// Cấu hình tài khoản ngân hàng & VietQR
require_once __DIR__ . '/vietqr.php';

/**
 * Kiểm tra người dùng hiện tại đã đăng nhập hay chưa
 */
function isUserLoggedIn(): bool
{
    return !empty($_SESSION['current_user_id']) && empty($_SESSION['explicit_logout']);
}

/**
 * Lấy thông tin người dùng đang đăng nhập trong Session
 */
function getCurrentUser(): ?array
{
    if (!isUserLoggedIn()) {
        return null;
    }
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    // Nếu có ID nhưng chưa nạp đủ Session, nạp từ CSDL
    $stmt = getDBConnection()->prepare("
        SELECT nd.*, vt.ten_vt, vt.mo_ta_quyen
        FROM nd_nguoi_dung nd
        INNER JOIN vt_vai_tro vt ON nd.ma_vt = vt.ma_vt
        WHERE nd.ma_nd = :id AND nd.trang_thai = 1
        LIMIT 1
    ");
    $stmt->execute(['id' => (int)$_SESSION['current_user_id']]);
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['user'] = [
            'ma_nd'         => (int)$u['ma_nd'],
            'ma_vt'         => (int)$u['ma_vt'],
            'ten_vt'        => $u['ten_vt'],
            'ho_ten'        => $u['ho_ten'],
            'ten_dang_nhap' => $u['ten_dang_nhap'],
            'email'         => $u['email'],
            'sdt'           => $u['sdt'],
            'anh_dai_dien'  => $u['anh_dai_dien'],
        ];
        return $_SESSION['user'];
    }
    return null;
}

/**
 * Lấy mã người dùng hiện tại từ Session.
 * Trả về 0 nếu chưa đăng nhập hoặc đã đăng xuất chủ động.
 * KHÔNG tự động gán tài khoản demo mặc định.
 */
function getCurrentUserId(): int
{
    if (!empty($_SESSION['explicit_logout'])) {
        return 0; // Đã đăng xuất chủ động
    }
    if (!empty($_SESSION['current_user_id'])) {
        return (int) $_SESSION['current_user_id'];
    }
    return 0; // Khách vãng lai chưa đăng nhập
}

/**
 * Lấy mã quản trị viên/nhân viên hiện tại (chỉ trả về hợp lệ nếu có vai trò ma_vt = 1 hoặc 2)
 */
function getCurrentAdminId(): int
{
    $u = getCurrentUser();
    if ($u && in_array((int)$u['ma_vt'], [1, 2], true)) {
        return (int)$u['ma_nd'];
    }
    return isset($_SESSION['current_admin_id']) ? (int)$_SESSION['current_admin_id'] : 0;
}

/**
 * Kiểm tra xem người dùng hiện tại có quyền truy cập Quản trị không (Admin: 1, Nhân viên: 2)
 */
function hasAdminAccess(): bool
{
    $u = getCurrentUser();
    return $u && in_array((int)$u['ma_vt'], [1, 2], true);
}

/**
 * Định dạng tiền tệ VNĐ
 */
function formatVND($amount): string
{
    return number_format((float) $amount, 0, ',', '.') . 'đ';
}

/**
 * Định dạng ngày giờ từ CSDL
 */
function formatDateVN(?string $datetime, bool $includeTime = false): string
{
    if (!$datetime) {
        return '-';
    }
    $timestamp = strtotime($datetime);
    if (!$timestamp) {
        return $datetime;
    }
    return date($includeTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
}

/**
 * Ánh xạ mã màu HEX từ tên màu tiếng Việt trong CSDL (bt_bien_the_sp / ha_hinh_anh_sp)
 */
function getColorHex(string $colorName): string
{
    $map = [
        'Đen'           => '#2D2D2D',
        'Trắng'         => '#FFFFFF',
        'Trắng Kem'     => '#F5F0E6',
        'Trắng Ngọc'    => '#F8FAFC',
        'Màu Be'        => '#D8C3A5',
        'Be'            => '#D8C3A5',
        'Xanh Pastel'   => '#A7C7E7',
        'Xanh Cổ Điển'  => '#3B5998',
        'Xanh Rêu'      => '#5F6F52',
        'Đỏ Đô'         => '#7B1E26',
        'Nâu Tây'       => '#8C6D53',
        'Hồng Phấn'     => '#F4C2C2',
    ];
    return $map[$colorName] ?? '#94A3B8';
}

/**
 * Giải quyết đường dẫn ảnh sản phẩm từ tên file lưu trong CSDL (sp_san_pham / ha_hinh_anh_sp)
 * Nếu file nội bộ chưa có trong thư mục assets/images/, tự động ánh xạ sang ảnh minh họa tương ứng với đúng mã SP/màu trong CSDL
 */
function resolveProductImage(?string $dbFileName): string
{
    if (!$dbFileName) {
        return 'https://images.pexels.com/photos/9968526/pexels-photo-9968526.jpeg?auto=compress&cs=tinysrgb&h=650&w=940';
    }
    if (preg_match('/^https?:\/\//i', $dbFileName)) {
        return $dbFileName;
    }
    $localPath = __DIR__ . '/../assets/images/' . $dbFileName;
    if (file_exists($localPath)) {
        return 'assets/images/' . $dbFileName;
    }

    $photoMap = [
        'sp01_polo_coolmax.jpg'   => 'https://images.pexels.com/photos/24509672/pexels-photo-24509672.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp01_polo_den.jpg'       => 'https://images.pexels.com/photos/24509672/pexels-photo-24509672.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp01_polo_trang.jpg'     => 'https://images.pexels.com/photos/24509673/pexels-photo-24509673.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp02_tshirt_compact.jpg' => 'https://images.pexels.com/photos/8069692/pexels-photo-8069692.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp02_tshirt_den.jpg'     => 'https://images.pexels.com/photos/8069692/pexels-photo-8069692.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp02_tshirt_trang.jpg'   => 'https://images.pexels.com/photos/24446647/pexels-photo-24446647.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp03_kaki_slimfit.jpg'   => 'https://images.pexels.com/photos/9464625/pexels-photo-9464625.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp03_kaki_den.jpg'       => 'https://images.pexels.com/photos/9464625/pexels-photo-9464625.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp03_kaki_be.jpg'        => 'https://images.pexels.com/photos/29090947/pexels-photo-29090947.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp04_somi_oxford.jpg'    => 'https://images.pexels.com/photos/27584193/pexels-photo-27584193.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp04_somi_trang.jpg'     => 'https://images.pexels.com/photos/27584193/pexels-photo-27584193.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp04_somi_xanh.jpg'      => 'https://images.pexels.com/photos/19568392/pexels-photo-19568392.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp05_jeans_straight.jpg' => 'https://images.pexels.com/photos/20143795/pexels-photo-20143795.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp05_jeans_xanh.jpg'     => 'https://images.pexels.com/photos/20143795/pexels-photo-20143795.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp06_short_kaki.jpg'     => 'https://images.pexels.com/photos/9464625/pexels-photo-9464625.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp06_short_be.jpg'       => 'https://images.pexels.com/photos/9464625/pexels-photo-9464625.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp07_dam_taffeta.jpg'    => 'https://images.pexels.com/photos/17546554/pexels-photo-17546554.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp07_dam_dodo.jpg'       => 'https://images.pexels.com/photos/17546554/pexels-photo-17546554.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp07_dam_kem.jpg'        => 'https://images.pexels.com/photos/5199942/pexels-photo-5199942.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp08_bomber_gio.jpg'     => 'https://images.pexels.com/photos/39915436/pexels-photo-39915436.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp08_bomber_den.jpg'     => 'https://images.pexels.com/photos/39915436/pexels-photo-39915436.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp08_bomber_reu.jpg'     => 'https://images.pexels.com/photos/19287856/pexels-photo-19287856.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp09_chanvay_xeply.jpg'  => 'https://images.pexels.com/photos/31046832/pexels-photo-31046832.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp09_chanvay_nau.jpg'    => 'https://images.pexels.com/photos/31046832/pexels-photo-31046832.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp09_chanvay_den.jpg'    => 'https://images.pexels.com/photos/29090947/pexels-photo-29090947.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp10_somi_lua_nu.jpg'    => 'https://images.pexels.com/photos/5199942/pexels-photo-5199942.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp10_somi_lua_trang.jpg' => 'https://images.pexels.com/photos/5199942/pexels-photo-5199942.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
        'sp10_somi_lua_hong.jpg'  => 'https://images.pexels.com/photos/17546554/pexels-photo-17546554.jpeg?auto=compress&cs=tinysrgb&h=650&w=940',
    ];

    return $photoMap[$dbFileName] ?? 'https://images.pexels.com/photos/9968526/pexels-photo-9968526.jpeg?auto=compress&cs=tinysrgb&h=650&w=940';
}

