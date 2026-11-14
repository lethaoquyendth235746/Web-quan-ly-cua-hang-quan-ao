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

// 3. Phân quyền: Chỉ cho phép ma_vt = 1 (Admin) và ma_vt = 2 (Nhân viên)
if (!in_array($maVt, [1, 2], true)) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>403 - Truy cập bị từ chối | AURA STUDIO</title>
      <link rel="preconnect" href="https://fonts.googleapis.com" />
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
      <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet" />
      <script src="https://cdn.tailwindcss.com"></script>
      <script>
        tailwind.config = {
          theme: {
            extend: {
              fontFamily: { sans: ['"Be Vietnam Pro"', 'system-ui', 'sans-serif'] },
              colors: { canvas: '#F8FAFC' }
            }
          }
        };
      </script>
    </head>
    <body class="bg-canvas font-sans text-slate-800 antialiased min-h-screen flex items-center justify-center p-6">
      <div class="max-w-md w-full bg-white rounded-2xl border border-red-200 shadow-xl p-8 text-center">
        <div class="w-16 h-16 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-5">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        
        <span class="inline-block px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full uppercase tracking-wider mb-2">
          HTTP 403 Forbidden
        </span>
        <h1 class="text-xl font-bold text-slate-900">Truy cập Phân hệ Quản trị bị từ chối</h1>
        
        <p class="mt-3 text-sm text-slate-600 leading-relaxed">
          Tài khoản <strong><?= htmlspecialchars($currentUser['ho_ten']) ?></strong> đang mang vai trò 
          <span class="font-medium text-red-600"><?= htmlspecialchars($currentUser['ten_vt'] ?? 'Khách hàng') ?> (ma_vt = <?= $maVt ?>)</span>.
        </p>
        <p class="mt-2 text-xs text-slate-500 leading-relaxed bg-slate-50 p-3 rounded-lg border border-slate-100 text-left">
          <strong>Quy tắc phân quyền:</strong> Chỉ tài khoản thuộc vai trò <code>Quản trị viên (ma_vt = 1)</code> hoặc <code>Nhân viên cửa hàng (ma_vt = 2)</code> mới được phép truy cập thư mục <code>admin/</code>.
        </p>

        <div class="mt-6 flex flex-col gap-2.5">
          <a href="../index.php" class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded-lg transition">
            &larr; Quay về Cửa hàng mua sắm
          </a>
          <a href="../logout.php?redirect=login.php" class="w-full py-2.5 px-4 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-300 transition">
            Đăng xuất &amp; Đăng nhập tài khoản Quản trị
          </a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// 4. Nếu hợp lệ, gán chắc chắn $_SESSION['current_admin_id']
$_SESSION['current_admin_id'] = (int)$currentUser['ma_nd'];
