<?php
/**
 * TẬP HỢP HÀM TIỆN ÍCH TOÀN CỤC (includes/helpers.php)
 * Cung cấp các helper truy vấn Sổ địa chỉ, Xác thực phiên và Flash messages
 */

/**
 * Lấy địa chỉ giao hàng mặc định của khách hàng
 *
 * @param PDO $pdo Kết nối CSDL PDO
 * @param int $ma_nd Mã người dùng
 * @return array|null Bản ghi địa chỉ hoặc null nếu chưa có
 */
function getDefaultAddress(PDO $pdo, int $ma_nd): ?array
{
    if ($ma_nd <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT * 
        FROM dc_dia_chi_kh 
        WHERE ma_nd = :ma_nd AND mac_dinh = 1 
        LIMIT 1
    ");
    $stmt->execute(['ma_nd' => $ma_nd]);
    $addr = $stmt->fetch();

    if ($addr) {
        return $addr;
    }

    // Nếu chưa đánh dấu mặc định, lấy địa chỉ mới nhất làm mặc định tạm thời
    $stmtFallback = $pdo->prepare("
        SELECT * 
        FROM dc_dia_chi_kh 
        WHERE ma_nd = :ma_nd 
        ORDER BY ma_dc DESC 
        LIMIT 1
    ");
    $stmtFallback->execute(['ma_nd' => $ma_nd]);
    $fallback = $stmtFallback->fetch();

    return $fallback ?: null;
}

/**
 * Lấy toàn bộ danh sách sổ địa chỉ của khách hàng (Ưu tiên địa chỉ mặc định lên đầu)
 *
 * @param PDO $pdo Kết nối CSDL PDO
 * @param int $ma_nd Mã người dùng
 * @return array Danh sách các địa chỉ từ bảng dc_dia_chi_kh
 */
function getUserAddresses(PDO $pdo, int $ma_nd): array
{
    if ($ma_nd <= 0) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT * 
        FROM dc_dia_chi_kh 
        WHERE ma_nd = :ma_nd 
        ORDER BY mac_dinh DESC, ma_dc DESC
    ");
    $stmt->execute(['ma_nd' => $ma_nd]);
    return $stmt->fetchAll();
}

/**
 * Thiết lập hoặc lấy thông báo Flash Message cho giao diện Bootstrap 5
 *
 * @param string $key Tên khóa thông báo
 * @param string $message Nội dung thông báo (để trống nếu muốn lấy ra)
 * @param string $type Kiểu alert Bootstrap: success, danger, warning, info
 * @return array|null
 */
function flash(string $key = '', string $message = '', string $type = 'success'): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($key !== '' && $message !== '') {
        $_SESSION['flash'][$key] = [
            'message' => $message,
            'type'    => $type,
        ];
        return null;
    }

    if ($key !== '' && isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }

    return null;
}

/**
 * Hiển thị thẻ Alert Bootstrap 5 từ Flash Message
 */
function renderFlash(string $key): void
{
    $flash = flash($key);
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $text = htmlspecialchars($flash['message']);
        echo "<div class=\"alert alert-{$type} alert-dismissible fade show my-3\" role=\"alert\">
                {$text}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
              </div>";
    }
}
