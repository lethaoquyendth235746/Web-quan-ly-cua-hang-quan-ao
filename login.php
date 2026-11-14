<?php
/**
 * TRANG ĐĂNG NHẬP & ĐĂNG KÝ THÀNH VIÊN (login.php)
 * Hỗ trợ xác thực với bảng nd_nguoi_dung (ten_dang_nhap/email + mat_khau)
 * Đăng ký tự động mở thẻ Hạng Đồng (ttd_the_tich_diem) qua Transaction
 * Tích hợp bộ nút chuyển đổi đăng nhập nhanh cho các tài khoản kiểm thử
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/services/store/AuthService.php';

$authService = new AuthService();

// Nếu người dùng đã đăng nhập từ trước và vào thẳng login.php
$currentUser = getCurrentUser();
$redirectUrl = $_GET['redirect'] ?? '';

// Xử lý thông báo Flash
$flashError = $_GET['error'] ?? $_SESSION['auth_error'] ?? null;
unset($_SESSION['auth_error']);

$flashSuccess = null;
if (isset($_GET['logout'])) {
    $flashSuccess = 'Bạn đã đăng xuất tài khoản thành công.';
} elseif (isset($_SESSION['auth_flash'])) {
    $flashSuccess = $_SESSION['auth_flash']['message'] ?? null;
    unset($_SESSION['auth_flash']);
}

// Xử lý gửi Form Đăng nhập truyền thống
$loginError = null;
$registerError = null;
$activeTab = $_GET['tab'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['action'] ?? '';

    if ($formAction === 'login') {
        $identifier = $_POST['identifier'] ?? '';
        $password   = $_POST['password'] ?? '';
        $res = $authService->login($identifier, $password);

        if ($res['success']) {
            $user = $res['user'];
            $maVt = (int)$user['ma_vt'];

            if (!empty($redirectUrl) && !preg_match('/^https?:\/\//i', $redirectUrl)) {
                $target = $redirectUrl;
            } elseif (in_array($maVt, [1, 2], true)) {
                $target = 'admin/index.php';
            } else {
                $target = 'index.php';
            }
            header('Location: ' . $target);
            exit;
        } else {
            $loginError = $res['message'];
            $activeTab = 'login';
        }
    } elseif ($formAction === 'register') {
        $res = $authService->register($_POST);

        if ($res['success']) {
            $_SESSION['auth_flash'] = [
                'type'    => 'success',
                'message' => $res['message'],
            ];
            header('Location: index.php?page=loyalty&registered=1');
            exit;
        } else {
            $registerError = $res['message'];
            $activeTab = 'register';
        }
    }
}

// Danh sách tài khoản mẫu để chấm điểm / kiểm thử nhanh
$demoAccounts = [
    [
        'label'    => 'Lê Minh Đức (Quản trị viên)',
        'username' => 'admin_duc',
        'role'     => 'Admin (ma_vt = 1)',
        'color'    => 'bg-purple-100 text-purple-800 border-purple-200',
        'desc'     => 'Toàn quyền truy cập phân hệ Quản trị',
    ],
    [
        'label'    => 'Lê Thảo Quyên (Nhân viên)',
        'username' => 'staff_quyen',
        'role'     => 'Nhân viên (ma_vt = 2)',
        'color'    => 'bg-blue-100 text-blue-800 border-blue-200',
        'desc'     => 'Quản lý sản phẩm, tồn kho và đơn hàng',
    ],
    [
        'label'    => 'Neàng Ra (Khách hàng - Đồng)',
        'username' => 'neangra',
        'role'     => 'Khách hàng (ma_vt = 3)',
        'color'    => 'bg-amber-100 text-amber-800 border-amber-200',
        'desc'     => '185/200 điểm - Chờ duyệt để lên Hạng Bạc',
    ],
    [
        'label'    => 'Hoàng Nam (Khách hàng - Bạc)',
        'username' => 'hoangnam_lx',
        'role'     => 'Khách hàng (ma_vt = 3)',
        'color'    => 'bg-slate-100 text-slate-800 border-slate-300',
        'desc'     => '303 điểm - Giảm 3% & x1.2 điểm thưởng',
    ],
    [
        'label'    => 'Bảo Trân (Khách hàng - Vàng)',
        'username' => 'baotran_vip',
        'role'     => 'Khách hàng (ma_vt = 3)',
        'color'    => 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'desc'     => '672 điểm - Giảm 5% & x1.5 điểm thưởng',
    ],
    [
        'label'    => 'Quốc Huy (Khách mới mở thẻ)',
        'username' => 'quochuy_99',
        'role'     => 'Khách hàng (ma_vt = 3)',
        'color'    => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'desc'     => '0 điểm - Mới kích hoạt thẻ thành viên',
    ],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng nhập &amp; Đăng ký tài khoản | AURA STUDIO</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['"Be Vietnam Pro"', 'system-ui', 'sans-serif'] },
          colors: {
            cream: '#FAF9F6',
            charcoal: '#2D2D2D',
            muted: '#71717A',
            accent: '#8C6D53',
            olive: '#5F6F52',
            gold: '#F7F1E3',
          },
        },
      },
    };
  </script>
  <style>
    .fade-up {
      animation: fadeUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .tap-feedback {
      transition: transform 0.15s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.2s ease;
    }
    .tap-feedback:active { transform: scale(0.98); }
  </style>
</head>
<body class="bg-cream font-sans text-charcoal antialiased min-h-screen flex flex-col justify-between">

  <!-- Top bar -->
  <header class="border-b border-stone-200 bg-white/80 backdrop-blur py-4 px-6 lg:px-12">
    <div class="max-w-6xl mx-auto flex items-center justify-between">
      <a href="index.php" class="text-sm font-semibold tracking-[0.25em] text-charcoal hover:text-accent transition">
        AURA STUDIO
      </a>
      <a href="index.php" class="text-xs text-muted hover:text-charcoal hover:underline transition">
        &larr; Quay lại Cửa hàng
      </a>
    </div>
  </header>

  <!-- Main Container -->
  <main class="flex-1 max-w-4xl w-full mx-auto px-4 py-10 sm:px-6">
    
    <!-- Flash alerts -->
    <?php if ($flashSuccess): ?>
      <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 flex items-center gap-3 fade-up">
        <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span><?= htmlspecialchars($flashSuccess) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($flashError || $loginError || $registerError): ?>
      <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 flex items-center gap-3 fade-up">
        <svg class="h-5 w-5 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span><?= htmlspecialchars($flashError ?: ($loginError ?: $registerError)) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($currentUser): ?>
      <div class="mb-8 rounded-xl border border-stone-200 bg-white p-5 flex flex-wrap items-center justify-between gap-4 shadow-sm fade-up">
        <div>
          <span class="text-xs uppercase tracking-wider text-muted font-semibold">Tài khoản đang đăng nhập:</span>
          <p class="text-base font-semibold text-charcoal">
            <?= htmlspecialchars($currentUser['ho_ten']) ?>
            <span class="ml-2 text-xs font-normal text-muted">(<?= htmlspecialchars($currentUser['ten_vt'] ?? '') ?>)</span>
          </p>
          <p class="text-xs text-muted"><?= htmlspecialchars($currentUser['email']) ?> &middot; @<?= htmlspecialchars($currentUser['ten_dang_nhap']) ?></p>
        </div>
        <div class="flex items-center gap-3">
          <?php if (in_array((int)$currentUser['ma_vt'], [1, 2], true)): ?>
            <a href="admin/index.php" class="tap-feedback rounded-lg bg-charcoal px-4 py-2 text-xs font-medium text-white hover:bg-accent transition">
              Vào Quản trị &rarr;
            </a>
          <?php else: ?>
            <a href="index.php?page=loyalty" class="tap-feedback rounded-lg bg-charcoal px-4 py-2 text-xs font-medium text-white hover:bg-accent transition">
              Thẻ thành viên &rarr;
            </a>
          <?php endif; ?>
          <a href="logout.php" class="tap-feedback rounded-lg border border-stone-300 bg-white px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-50 transition">
            Đăng xuất
          </a>
        </div>
      </div>
    <?php endif; ?>

    <div class="grid gap-8 lg:grid-cols-12">
      <!-- Form Box (Col 7) -->
      <div class="lg:col-span-7 bg-white rounded-2xl border border-stone-200 p-6 sm:p-8 shadow-sm">
        
        <!-- Tab selector -->
        <div class="flex border-b border-stone-200 mb-6">
          <button type="button" id="tabLoginBtn" onclick="switchAuthTab('login')"
            class="pb-3 text-sm font-semibold transition border-b-2 mr-6 <?= $activeTab === 'login' ? 'border-charcoal text-charcoal' : 'border-transparent text-muted hover:text-charcoal' ?>">
            Đăng nhập tài khoản
          </button>
          <button type="button" id="tabRegisterBtn" onclick="switchAuthTab('register')"
            class="pb-3 text-sm font-semibold transition border-b-2 <?= $activeTab === 'register' ? 'border-charcoal text-charcoal' : 'border-transparent text-muted hover:text-charcoal' ?>">
            Đăng ký thành viên mới
          </button>
        </div>

        <!-- TAB 1: FORM ĐĂNG NHẬP -->
        <div id="loginSection" class="<?= $activeTab === 'login' ? '' : 'hidden' ?>">
          <form method="POST" action="login.php<?= !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : '' ?>" class="space-y-4">
            <input type="hidden" name="action" value="login" />

            <div>
              <label for="loginIdentifier" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1.5">
                Tên đăng nhập hoặc Email <span class="text-red-500">*</span>
              </label>
              <input type="text" id="loginIdentifier" name="identifier" required
                placeholder="VD: admin_duc, neangra hoặc email..."
                value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                class="w-full rounded-xl border border-stone-300 bg-white px-4 py-2.5 text-sm text-charcoal placeholder:text-stone-400 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
            </div>

            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label for="loginPassword" class="block text-xs font-semibold uppercase tracking-wider text-charcoal">
                  Mật khẩu <span class="text-red-500">*</span>
                </label>
                <span class="text-[11px] text-muted">(Mặc định dữ liệu mẫu: <code>123456</code>)</span>
              </div>
              <input type="password" id="loginPassword" name="password" required
                placeholder="Nhập mật khẩu của bạn..."
                class="w-full rounded-xl border border-stone-300 bg-white px-4 py-2.5 text-sm text-charcoal placeholder:text-stone-400 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
            </div>

            <div class="pt-2">
              <button type="submit" class="tap-feedback w-full rounded-xl bg-charcoal py-3 text-sm font-semibold text-white hover:bg-accent transition shadow-sm">
                Đăng nhập hệ thống
              </button>
            </div>
          </form>

          <p class="mt-4 text-center text-xs text-muted">
            Chưa có tài khoản? 
            <button type="button" onclick="switchAuthTab('register')" class="font-semibold text-accent hover:underline">
              Đăng ký ngay &amp; nhận Thẻ Hạng Đồng
            </button>
          </p>
        </div>

        <!-- TAB 2: FORM ĐĂNG KÝ THÀNH VIÊN & TỰ ĐỘNG MỞ THẺ HẠNG ĐỒNG -->
        <div id="registerSection" class="<?= $activeTab === 'register' ? '' : 'hidden' ?>">
          <!-- Banner Loyalty tự động mở thẻ -->
          <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50/70 p-4">
            <div class="flex items-center gap-2 text-amber-900 font-semibold text-xs uppercase tracking-wider">
              <span class="h-2 w-2 rounded-full bg-amber-600"></span>
              Đặc quyền Thành viên AURA STUDIO
            </div>
            <p class="mt-1 text-xs text-amber-800 leading-relaxed">
              Tài khoản mới sẽ được tự động cấp <strong>Thẻ tích điểm Hạng Đồng</strong> (Mã thẻ dạng <code>THE-AGU-...</code>) để bắt đầu tích lũy điểm thưởng và hưởng ưu đãi ngay từ đơn hàng đầu tiên!
            </p>
          </div>

          <form method="POST" action="login.php" class="space-y-4">
            <input type="hidden" name="action" value="register" />

            <div>
              <label for="regHoTen" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
                Họ và tên <span class="text-red-500">*</span>
              </label>
              <input type="text" id="regHoTen" name="ho_ten" required
                placeholder="VD: Nguyễn Văn An"
                value="<?= htmlspecialchars($_POST['ho_ten'] ?? '') ?>"
                class="w-full rounded-xl border border-stone-300 bg-white px-4 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label for="regTenDangNhap" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
                  Tên đăng nhập <span class="text-red-500">*</span>
                </label>
                <input type="text" id="regTenDangNhap" name="ten_dang_nhap" required
                  placeholder="VD: van_an99"
                  value="<?= htmlspecialchars($_POST['ten_dang_nhap'] ?? '') ?>"
                  class="w-full rounded-xl border border-stone-300 bg-white px-4 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
              </div>
              <div>
                <label for="regSdt" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
                  Số điện thoại <span class="text-red-500">*</span>
                </label>
                <input type="tel" id="regSdt" name="sdt" required
                  placeholder="VD: 0912345678"
                  value="<?= htmlspecialchars($_POST['sdt'] ?? '') ?>"
                  class="w-full rounded-xl border border-stone-300 bg-white px-4 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
              </div>
            </div>

            <div>
              <label for="regEmail" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
                Địa chỉ Email <span class="text-red-500">*</span>
              </label>
              <input type="email" id="regEmail" name="email" required
                placeholder="VD: vanan@gmail.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                class="w-full rounded-xl border border-stone-300 bg-white px-4 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label for="regMatKhau" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
                  Mật khẩu <span class="text-red-500">*</span>
                </label>
                <input type="password" id="regMatKhau" name="mat_khau" required minlength="6"
                  placeholder="Tối thiểu 6 ký tự"
                  class="w-full rounded-xl border border-stone-300 bg-white px-4 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
              </div>
              <div>
                <label for="regMatKhauXacNhan" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
                  Xác nhận mật khẩu <span class="text-red-500">*</span>
                </label>
                <input type="password" id="regMatKhauXacNhan" name="mat_khau_xac_nhan" required minlength="6"
                  placeholder="Nhập lại mật khẩu"
                  class="w-full rounded-xl border border-stone-300 bg-white px-4 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
              </div>
            </div>

            <div class="pt-2">
              <button type="submit" class="tap-feedback w-full rounded-xl bg-olive py-3 text-sm font-semibold text-white hover:bg-olive/90 transition shadow-sm">
                Đăng ký &amp; Tự động mở thẻ Hạng Đồng
              </button>
            </div>
          </form>

          <p class="mt-4 text-center text-xs text-muted">
            Đã có tài khoản? 
            <button type="button" onclick="switchAuthTab('login')" class="font-semibold text-accent hover:underline">
              Đăng nhập tại đây
            </button>
          </p>
        </div>

      </div>

      <!-- Quick Demo Login Column (Col 5) -->
      <div class="lg:col-span-5 space-y-4">
        <div class="bg-white rounded-2xl border border-stone-200 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-charcoal">Kiểm thử nhanh (Demo One-Click)</h2>
            <span class="text-[10px] bg-stone-100 text-stone-600 px-2 py-0.5 rounded-full font-mono">Pass: 123456</span>
          </div>
          <p class="text-xs text-muted mb-4 leading-relaxed">
            Nhấp vào bất kỳ tài khoản nào bên dưới để tự động điền và đăng nhập ngay lập tức:
          </p>

          <div class="space-y-2">
            <?php foreach ($demoAccounts as $acc): ?>
              <button type="button"
                onclick="quickLogin('<?= htmlspecialchars($acc['username']) ?>', '123456')"
                class="tap-feedback w-full text-left p-3 rounded-xl border border-stone-200 hover:border-charcoal hover:bg-stone-50 transition flex items-center justify-between group">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-charcoal group-hover:text-accent transition">
                      <?= htmlspecialchars($acc['label']) ?>
                    </span>
                  </div>
                  <p class="text-[11px] text-muted mt-0.5"><?= htmlspecialchars($acc['desc']) ?></p>
                  <p class="text-[10px] font-mono text-stone-400 mt-0.5">User: <code><?= htmlspecialchars($acc['username']) ?></code></p>
                </div>
                <span class="text-xs text-charcoal shrink-0 font-medium ml-2 opacity-0 group-hover:opacity-100 transition">
                  Chọn &rarr;
                </span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="rounded-xl border border-dashed border-stone-300 p-4 text-xs text-muted leading-relaxed">
          <p class="font-semibold text-charcoal mb-1">Kiến trúc phân quyền (Middleware):</p>
          <ul class="list-disc pl-4 space-y-1">
            <li><strong>Admin (ma_vt = 1)</strong> và <strong>Nhân viên (ma_vt = 2)</strong> được truy cập thư mục <code>admin/</code>.</li>
            <li><strong>Khách hàng (ma_vt = 3)</strong> truy cập <code>admin/</code> sẽ bị chặn bởi trang lỗi <strong>HTTP 403 Forbidden</strong>.</li>
          </ul>
        </div>
      </div>
    </div>
  </main>

  <footer class="border-t border-stone-200 bg-white py-6 text-center text-xs text-muted">
    AURA STUDIO &middot; Phân hệ Xác thực, Tài khoản &amp; Sổ địa chỉ (Bảng <code>vt_vai_tro</code>, <code>nd_nguoi_dung</code>, <code>dc_dia_chi_kh</code>, <code>ttd_the_tich_diem</code>)
  </footer>

  <script>
    function switchAuthTab(tab) {
      const loginSection = document.getElementById('loginSection');
      const registerSection = document.getElementById('registerSection');
      const tabLoginBtn = document.getElementById('tabLoginBtn');
      const tabRegisterBtn = document.getElementById('tabRegisterBtn');

      if (tab === 'register') {
        loginSection.classList.add('hidden');
        registerSection.classList.remove('hidden');
        tabRegisterBtn.classList.add('border-charcoal', 'text-charcoal');
        tabRegisterBtn.classList.remove('border-transparent', 'text-muted');
        tabLoginBtn.classList.remove('border-charcoal', 'text-charcoal');
        tabLoginBtn.classList.add('border-transparent', 'text-muted');
      } else {
        registerSection.classList.add('hidden');
        loginSection.classList.remove('hidden');
        tabLoginBtn.classList.add('border-charcoal', 'text-charcoal');
        tabLoginBtn.classList.remove('border-transparent', 'text-muted');
        tabRegisterBtn.classList.remove('border-charcoal', 'text-charcoal');
        tabRegisterBtn.classList.add('border-transparent', 'text-muted');
      }
    }

    function quickLogin(username, password) {
      switchAuthTab('login');
      const idInput = document.getElementById('loginIdentifier');
      const passInput = document.getElementById('loginPassword');
      if (idInput && passInput) {
        idInput.value = username;
        passInput.value = password;
        // Tự động submit form
        idInput.form.submit();
      }
    }
  </script>
</body>
</html>
