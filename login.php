<?php
/**
 * TRANG ĐĂNG NHẬP HỆ THỐNG (login.php)
 * Giao diện Bootstrap 5 chuẩn hóa, hỗ trợ xác thực bằng Tên đăng nhập HOẶC Email + Mật khẩu
 * Tự động chuyển đổi password plain-text sang password_hash() an toàn
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = getDBConnection();
$error = '';
$redirectUrl = $_GET['redirect'] ?? '';

// Nếu đã đăng nhập trước đó
if (!empty($_SESSION['user'])) {
    $role = (int)$_SESSION['user']['ma_vt'];
    if (in_array($role, [ROLE_ADMIN, ROLE_STAFF], true)) {
        header('Location: admin/index.php');
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}

// Xử lý gửi Form Đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập/email và mật khẩu.';
    } else {
        $stmt = $pdo->prepare("SELECT nd.*, vt.ten_vt, vt.mo_ta_quyen FROM nd_nguoi_dung nd INNER JOIN vt_vai_tro vt ON nd.ma_vt = vt.ma_vt WHERE (nd.ten_dang_nhap = :id1 OR nd.email = :id2) LIMIT 1");
        $stmt->execute(['id1' => $identifier, 'id2' => $identifier]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Tài khoản không tồn tại trong hệ thống.';
        } elseif ((int)$user['trang_thai'] !== 1) {
            $error = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
        } else {
            $valid = false;
            if (password_verify($password, $user['mat_khau'])) {
                $valid = true;
            } elseif ($user['mat_khau'] === $password) {
                $valid = true;
                try {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $up = $pdo->prepare("UPDATE nd_nguoi_dung SET mat_khau = :hash WHERE ma_nd = :id");
                    $up->execute(['hash' => $newHash, 'id' => $user['ma_nd']]);
                } catch (Exception $e) {
                    // ignore migration errors
                }
            }

            if ($valid) {
                unset($_SESSION['explicit_logout']);
                $_SESSION['user'] = [
                    'ma_nd' => (int)$user['ma_nd'],
                    'ten_dang_nhap' => $user['ten_dang_nhap'],
                    'ho_ten' => $user['ho_ten'],
                    'email' => $user['email'],
                    'sdt' => $user['sdt'] ?? '',
                    'ma_vt' => (int)$user['ma_vt'],
                    'ten_vt' => $user['ten_vt'] ?? 'Thành viên',
                    'anh_dai_dien' => $user['anh_dai_dien'] ?? 'default_avatar.png',
                ];
                $_SESSION['current_user_id'] = (int)$user['ma_nd'];
                $maVt = (int)$user['ma_vt'];

                if (!empty($redirectUrl) && !preg_match('/^https?:\/\//i', $redirectUrl)) {
                    header('Location: ' . $redirectUrl);
                    exit;
                }

                if (in_array($maVt, [ROLE_ADMIN, ROLE_STAFF], true)) {
                    $_SESSION['current_admin_id'] = (int)$user['ma_nd'];
                    header('Location: admin/index.php');
                    exit;
                } else {
                    unset($_SESSION['current_admin_id']);
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'Mật khẩu không chính xác. Vui lòng thử lại.';
            }
        }
    }
}

$pageTitle = 'Đăng nhập tài khoản | AURA STUDIO';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
      <!-- Card Đăng nhập -->
      <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
        <div class="text-center mb-4">
          <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill small mb-2"></span>
          <h2 class="h3 fw-bold text-dark">Đăng nhập</h2>
          <p class="text-secondary small mb-0">Hệ thống Quản lý Cửa hàng Quần áo &amp; Khách hàng Thân thiết</p>
        </div>
        <!-- Flash messages -->
        <?php if (isset($_GET['logout'])): ?>
          <div class="alert alert-success alert-dismissible fade show small" role="alert">
            <i class="bi bi-check-circle me-1"></i>Bạn đã đăng xuất tài khoản thành công.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
          <div class="alert alert-success alert-dismissible fade show small" role="alert">
            <i class="bi bi-gift me-1"></i>Đăng ký thành công! Bạn đã được tự động mở Thẻ thành viên Hạng Đồng.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        <?php if (!empty($error) || !empty($_GET['error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show small" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error ?: $_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        <!-- Form Đăng nhập -->
        <form method="POST" action="login.php<?= !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : '' ?>">
          <div class="mb-3">
            <label for="identifier" class="form-label small fw-semibold text-dark">Tên đăng nhập hoặc Email <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-person"></i></span>
              <input type="text" class="form-control border-start-0 ps-0 shadow-none" id="identifier" name="identifier" required placeholder="VD: admin_duc, neangra hoặc email..." value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label for="password" class="form-label small fw-semibold text-dark mb-0">Mật khẩu <span class="text-danger">*</span></label>
              <span class="text-muted small" style="font-size: 11px;"></code></span>
            </div>
            <div class="input-group">
              <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control border-start-0 ps-0 shadow-none" id="password" name="password" required placeholder="Nhập mật khẩu...">
            </div>
          </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-aura py-2.5 rounded-3 fw-semibold"><i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập ngay</button>
          </div>
        </form>
        <div class="text-center mt-4 pt-3 border-top small text-secondary">
          Chưa có tài khoản thành viên? <a href="register.php" class="text-dark fw-bold text-decoration-none">Đăng ký &amp; Mở thẻ Hạng Đồng →</a>
        </div>
      </div>

    </div>
  </div>
</div>



      

<script>
  function quickFill(u, p) {
    document.getElementById('identifier').value = u;
    document.getElementById('password').value = p;
    document.getElementById('identifier').form.submit();
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
>>>>>>> origin/main
