<?php
/**
 * BẢN MẪU CẤU HÌNH KẾT NỐI (SAMPLE / TEMPLATE)
 * Sao chép file này thành `db.php` và cập nhật thông tin kết nối CSDL của bạn.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cấu hình kết nối MySQL
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306'); // Hoặc 3308 tùy cấu hình XAMPP của bạn
define('DB_NAME', 'csdl_cua_hang_quan_ao');
define('DB_USER', 'root');
define('DB_PASS', '');
define('POINTS_PER_VND', 10000);

/**
 * Khởi tạo và trả về đối tượng kết nối PDO (Singleton)
 */
function getDBConnection(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;max-width:640px;margin:2rem auto;border:1px solid #fca5a5;background:#fef2f2;border-radius:12px;color:#991b1b;">'
                . '<h2 style="margin-top:0;">Không thể kết nối Cơ sở dữ liệu</h2>'
                . '<p>Vui lòng kiểm tra MySQL trong XAMPP Control Panel và đảm bảo đã nạp file <code>SQL/file_mau-duLieu.sql</code> vào CSDL <strong>' . htmlspecialchars(DB_NAME) . '</strong>.</p>'
                . '<p style="font-size:13px;color:#b91c1c;">Chi tiết lỗi: ' . htmlspecialchars($e->getMessage()) . '</p>'
                . '</div>');
        }
    }
    return $pdo;
}

if (isset($_GET['switch_user'])) {
    $uid = (int) $_GET['switch_user'];
    if ($uid > 0) {
        $_SESSION['current_user_id'] = $uid;
    }
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    $queryParams = $_GET;
    unset($queryParams['switch_user']);
    if (!empty($queryParams)) {
        $redirect .= '?' . http_build_query($queryParams);
    }
    header('Location: ' . $redirect);
    exit;
}

function getCurrentUserId(): int
{
    return isset($_SESSION['current_user_id']) ? (int) $_SESSION['current_user_id'] : 4;
}

function getCurrentAdminId(): int
{
    return isset($_SESSION['current_admin_id']) ? (int) $_SESSION['current_admin_id'] : 1;
}

function formatVND($amount): string
{
    return number_format((float) $amount, 0, ',', '.') . 'đ';
}

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
