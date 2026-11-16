<?php
/**
 * PHÂN HỆ QUẢN TRỊ: KIỂM TRA QUYỀN TRUY CẬP (admin/includes/auth_check.php)
 * Bắt buộc nhúng tại đầu mọi trang trong thư mục admin/
 * 
 * Quy tắc bảo mật:
 * 1. Chưa đăng nhập -> Chuyển hướng về login.php kèm tham số redirect.
 * 2. Đã đăng nhập nhưng ma_vt = 3 (Khách hàng) -> Chặn truy cập với lỗi 403 Forbidden.
 * 3. ma_vt = 1 (Admin) và ma_vt = 2 (Nhân viên) -> Được phép truy cập.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';

// 1. Kiểm tra trạng thái đăng nhập
if (empty($_SESSION['user']) && empty($_SESSION['current_user_id'])) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'admin/index.php');
    header('Location: ../login.php?redirect=' . $redirectUrl . '&error=' . urlencode('Vui lòng đăng nhập để truy cập khu vực Quản trị.'));
    exit;
}

// 2. Lấy thông tin người dùng từ session
$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser && !empty($_SESSION['current_user_id'])) {
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
        $currentUser = $_SESSION['user'];
    }
}

$maVt = (int)($currentUser['ma_vt'] ?? 0);

// 3. Phân quyền: Chặn khách hàng (ma_vt = 3)
if ($maVt === ROLE_CUSTOMER || !in_array($maVt, [ROLE_ADMIN, ROLE_STAFF], true)) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>403 - Không có quyền truy cập | AURA STUDIO</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
      <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; background-color: #f8fafc; }
        .error-card { max-width: 520px; border-radius: 16px; border: 1px solid #fecaca; box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
      </style>
    </head>
    <body class="d-flex align-items-center justify-content-center min-vh-100 p-3">
      <div class="card error-card bg-white p-4 p-md-5 text-center">
        <div class="mb-3">
          <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fs-6 fw-semibold">
            HTTP 403 FORBIDDEN
          </span>
        </div>
        <h2 class="h4 fw-bold text-dark mb-2">Không có quyền truy cập Quản trị</h2>
        <p class="text-secondary small mb-3">
          Tài khoản <strong><?= htmlspecialchars($currentUser['ho_ten'] ?? 'Khách hàng') ?></strong> đang mang vai trò 
          <span class="badge bg-secondary"><?= htmlspecialchars($currentUser['ten_vt'] ?? 'Khách hàng thành viên') ?> (ma_vt = <?= $maVt ?>)</span>.
        </p>
        <div class="alert alert-warning text-start small mb-4">
          <strong>Lưu ý bảo mật:</strong> Khu vực Quản trị (<code>admin/</code>) chỉ dành riêng cho 
          <strong>Quản trị viên (Admin - ma_vt = 1)</strong> và <strong>Nhân viên (ma_vt = 2)</strong>.
        </div>
        <div class="d-grid gap-2">
          <a href="../index.php" class="btn btn-dark py-2 fw-medium">
            &larr; Quay về Trang chủ Cửa hàng
          </a>
          <a href="../logout.php?redirect=login.php" class="btn btn-outline-secondary py-2">
            Đăng xuất &amp; Đăng nhập bằng tài khoản Quản trị
          </a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// 4. Biến cờ phân biệt quyền Admin để ẩn/hiển thị menu phù hợp
$isAdmin = ($maVt === ROLE_ADMIN);
$isStaff = ($maVt === ROLE_STAFF);

$_SESSION['current_admin_id'] = (int)$currentUser['ma_nd'];
