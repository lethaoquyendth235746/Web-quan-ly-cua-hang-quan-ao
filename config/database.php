<?php
/**
 * TẦNG KẾT NỐI CƠ SỞ DỮ LIỆU CHUẨN (config/database.php)
 * Kết nối CSDL bằng PDO (charset utf8mb4, ERRMODE_EXCEPTION)
 * Hỗ trợ cấu hình linh hoạt qua hằng số hoặc biến môi trường
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../includes/helpers.php';

// Cấu hình kết nối linh hoạt
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'csdl_cua_hang_quan_ao');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
}

/**
 * Lấy đối tượng kết nối PDO (Singleton)
 *
 * @return PDO Đối tượng kết nối PDO
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
            $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (str_contains($scriptName, '/api/') || str_contains($acceptHeader, 'application/json')) {
                if (!headers_sent()) {
                    http_response_code(500);
                    header('Content-Type: application/json; charset=utf-8');
                }
                die(json_encode([
                    'success' => false,
                    'message' => 'Không thể kết nối Cơ sở dữ liệu: ' . $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE));
            }
            die('<div style="font-family:sans-serif;padding:2rem;max-width:640px;margin:2rem auto;border:1px solid #fca5a5;background:#fef2f2;border-radius:12px;color:#991b1b;">'
                . '<h2 style="margin-top:0;">Không thể kết nối Cơ sở dữ liệu</h2>'
                . '<p>Vui lòng kiểm tra MySQL trong XAMPP Control Panel và đảm bảo đã nạp file <code>database/init.sql</code> vào CSDL <strong>' . htmlspecialchars(DB_NAME) . '</strong>.</p>'
                . '<p style="font-size:13px;color:#b91c1c;">Chi tiết lỗi: ' . htmlspecialchars($e->getMessage()) . '</p>'
                . '</div>');
        }
    }
    return $pdo;
}

/**
 * Class Database Wrapper phục vụ gọi OOP: Database::getInstance()->getConnection()
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = getDBConnection();
        }
        return self::$instance;
    }
}
