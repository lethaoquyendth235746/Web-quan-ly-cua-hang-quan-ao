<?php
/**
 * TRANG ĐĂNG KÝ THÀNH VIÊN & TỰ ĐỘNG MỞ THẺ HẠNG ĐỒNG (register.php)
 * Giao diện Bootstrap 5 chuẩn hóa, thực thi trong PDO Transaction an toàn tuyệt đối
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = getDBConnection();
$error = '';

// Nếu đã đăng nhập, chuyển hướng về trang cá nhân
if (!empty($_SESSION['user'])) {
    header('Location: profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen          = trim($_POST['ho_ten'] ?? '');
    $tenDangNhap    = trim($_POST['ten_dang_nhap'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $sdt            = trim($_POST['sdt'] ?? '');
    $matKhau        = $_POST['mat_khau'] ?? '';
    $matKhauXacNhan = $_POST['mat_khau_xac_nhan'] ?? '';

    // 1. Validate bắt buộc
    if ($hoTen === '' || $tenDangNhap === '' || $email === '' || $sdt === '' || $matKhau === '') {
        $error = 'Vui lòng điền đầy đủ tất cả các trường thông tin bắt buộc.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $tenDangNhap)) {
        $error = 'Tên đăng nhập từ 3 - 30 ký tự, chỉ gồm chữ cái, số và dấu gạch dưới.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không đúng định dạng. Vui lòng kiểm tra lại.';
    } elseif (!preg_match('/^[0-9]{9,15}$/', $sdt)) {
        $error = 'Số điện thoại không hợp lệ (yêu cầu từ 9 đến 15 chữ số).';
    } elseif (strlen($matKhau) < 6) {
        $error = 'Mật khẩu phải có độ dài tối thiểu từ 6 ký tự trở lên.';
    } elseif ($matKhau !== $matKhauXacNhan) {
        $error = 'Mật khẩu xác nhận không trùng khớp.';
    } else {
        // 2. Kiểm tra trùng tên đăng nhập, email, số điện thoại
        $checkStmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN ten_dang_nhap = :tdn1 THEN 1 ELSE 0 END) AS exist_tdn,
                SUM(CASE WHEN email = :em1 THEN 1 ELSE 0 END) AS exist_email,
                SUM(CASE WHEN sdt = :sdt1 THEN 1 ELSE 0 END) AS exist_sdt
            FROM nd_nguoi_dung
            WHERE ten_dang_nhap = :tdn2 OR email = :em2 OR sdt = :sdt2
        ");
        $checkStmt->execute([
            'tdn1' => $tenDangNhap,
            'tdn2' => $tenDangNhap,
            'em1'  => $email,
            'em2'  => $email,
            'sdt1' => $sdt,
            'sdt2' => $sdt,
        ]);
        $check = $checkStmt->fetch();

        if (!empty($check['exist_tdn'])) {
            $error = 'Tên đăng nhập "' . htmlspecialchars($tenDangNhap) . '" đã được sử dụng. Vui lòng chọn tên khác.';
        } elseif (!empty($check['exist_email'])) {
            $error = 'Địa chỉ email "' . htmlspecialchars($email) . '" đã tồn tại trong hệ thống.';
        } elseif (!empty($check['exist_sdt'])) {
            $error = 'Số điện thoại "' . htmlspecialchars($sdt) . '" đã được đăng ký cho tài khoản khác.';
        } else {
            // 3. THỰC THI TRONG PDO TRANSACTION
            $pdo->beginTransaction();
            try {
                // Bước 3.1: Mã hóa mật khẩu
                $hashedPassword = password_hash($matKhau, PASSWORD_DEFAULT);

                // Bước 3.2: Insert người dùng mới (ma_vt = 3: Khách hàng thành viên)
                $insertUser = $pdo->prepare("
                    INSERT INTO nd_nguoi_dung (
                        ma_vt, ho_ten, ten_dang_nhap, mat_khau, sdt, email, anh_dai_dien, trang_thai, ngay_tao
                    ) VALUES (
                        :ma_vt, :ho_ten, :ten_dang_nhap, :mat_khau, :sdt, :email, 'default_avatar.png', 1, NOW()
                    )
                ");
                $insertUser->execute([
                    'ma_vt'         => ROLE_CUSTOMER,
                    'ho_ten'        => $hoTen,
                    'ten_dang_nhap' => $tenDangNhap,
                    'mat_khau'      => $hashedPassword,
                    'sdt'           => $sdt,
                    'email'         => $email,
                ]);

                $newUserId = (int)$pdo->lastInsertId();
                if ($newUserId <= 0) {
                    throw new Exception('Không thể tạo bản ghi người dùng mới.');
                }

                // Bước 3.3: Tự động sinh mã thẻ tích điểm định dạng THE-AGU-XXXXXX
                $cardSequence = str_pad((string)$newUserId, 6, '0', STR_PAD_LEFT);
                $maSoThe = 'THE-AGU-' . $cardSequence;

                // Kiểm tra đảm bảo duy nhất mã thẻ
                $checkCard = $pdo->prepare("SELECT COUNT(*) FROM ttd_the_tich_diem WHERE ma_so_the = :card");
                $checkCard->execute(['card' => $maSoThe]);
                if ((int)$checkCard->fetchColumn() > 0) {
                    $maSoThe = 'THE-AGU-' . date('y') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                }

                // Bước 3.4: Insert thẻ tích điểm vào ttd_the_tich_diem (ma_hang = 1, điểm = 0)
                $insertCard = $pdo->prepare("
                    INSERT INTO ttd_the_tich_diem (
                        ma_nd, ma_hang, ma_so_the, tong_diem_tich_luy, diem_kha_dung, ngay_cap
                    ) VALUES (
                        :ma_nd, :ma_hang, :ma_so_the, 0, 0, NOW()
                    )
                ");
                $insertCard->execute([
                    'ma_nd'     => $newUserId,
                    'ma_hang'   => TIER_BRONZE,
                    'ma_so_the' => $maSoThe,
                ]);

                // Bước 3.5: Commit Transaction thành công
                $pdo->commit();

                // Tự động đăng nhập cho khách hàng mới
                unset($_SESSION['explicit_logout']);
                $_SESSION['user'] = [
                    'ma_nd'         => $newUserId,
                    'ten_dang_nhap' => $tenDangNhap,
                    'ho_ten'        => $hoTen,
                    'email'         => $email,
                    'sdt'           => $sdt,
                    'ma_vt'         => ROLE_CUSTOMER,
                    'ten_vt'        => 'Khách hàng thành viên',
                    'anh_dai_dien'  => 'default_avatar.png',
                ];
                $_SESSION['current_user_id'] = $newUserId;
                unset($_SESSION['current_admin_id']);

                header('Location: profile.php?registered=1');
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Đăng ký thất bại: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Đăng ký thành viên & Mở thẻ Hạng Đồng | AURA STUDIO';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
      
      <!-- Card Đăng ký -->
      <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
        <div class="text-center mb-4">
          <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-3 py-1.5 rounded-pill small mb-2">
            <i class="bi bi-award me-1"></i>Đặc quyền Hội viên
          </span>
          <h2 class="h3 fw-bold text-dark">Đăng ký thành viên</h2>
          <p class="text-secondary small mb-0">Tự động kích hoạt <strong>Thẻ Hạng Đồng</strong> (Mã thẻ <code>THE-AGU-...</code>) để tích lũy điểm thưởng</p>
        </div>

        <!-- Banner quyền lợi Hạng Đồng -->
        <div class="alert alert-warning border-0 bg-warning-subtle p-3 rounded-3 small mb-4">
          <div class="fw-semibold text-dark mb-1">
            <i class="bi bi-shield-check me-1 text-warning"></i>Ưu đãi tự động mở thẻ ngay sau khi đăng ký:
          </div>
          <ul class="mb-0 ps-3 text-secondary" style="font-size: 12px;">
            <li>Tích lũy cơ bản: 10.000đ = 1 điểm cơ bản.</li>
            <li>Tham gia đổi các phiếu giảm giá (Voucher) trong kho quà tặng.</li>
            <li>Theo dõi lịch sử đơn hàng và tiến độ nâng hạng Bạc, Vàng, Kim Cương.</li>
          </ul>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show small" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <!-- Form Đăng ký -->
        <form method="POST" action="register.php">
          <div class="mb-3">
            <label for="ho_ten" class="form-label small fw-semibold text-dark">
              Họ và tên <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control shadow-none" id="ho_ten" name="ho_ten"
              required placeholder="VD: Nguyễn Văn An"
              value="<?= htmlspecialchars($_POST['ho_ten'] ?? '') ?>">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label for="ten_dang_nhap" class="form-label small fw-semibold text-dark">
                Tên đăng nhập <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control shadow-none" id="ten_dang_nhap" name="ten_dang_nhap"
                required placeholder="VD: van_an99"
                value="<?= htmlspecialchars($_POST['ten_dang_nhap'] ?? '') ?>">
            </div>
            <div class="col-sm-6">
              <label for="sdt" class="form-label small fw-semibold text-dark">
                Số điện thoại <span class="text-danger">*</span>
              </label>
              <input type="tel" class="form-control shadow-none" id="sdt" name="sdt"
                required placeholder="VD: 0912345678"
                value="<?= htmlspecialchars($_POST['sdt'] ?? '') ?>">
            </div>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label small fw-semibold text-dark">
              Địa chỉ Email <span class="text-danger">*</span>
            </label>
            <input type="email" class="form-control shadow-none" id="email" name="email"
              required placeholder="VD: vanan@gmail.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="row g-3 mb-4">
            <div class="col-sm-6">
              <label for="mat_khau" class="form-label small fw-semibold text-dark">
                Mật khẩu <span class="text-danger">*</span>
              </label>
              <input type="password" class="form-control shadow-none" id="mat_khau" name="mat_khau"
                required minlength="6" placeholder="Tối thiểu 6 ký tự">
            </div>
            <div class="col-sm-6">
              <label for="mat_khau_xac_nhan" class="form-label small fw-semibold text-dark">
                Xác nhận mật khẩu <span class="text-danger">*</span>
              </label>
              <input type="password" class="form-control shadow-none" id="mat_khau_xac_nhan" name="mat_khau_xac_nhan"
                required minlength="6" placeholder="Nhập lại mật khẩu">
            </div>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-aura py-2.5 rounded-3 fw-semibold">
              <i class="bi bi-person-check me-1"></i>Đăng ký &amp; Tự động mở thẻ Hạng Đồng
            </button>
          </div>
        </form>

        <div class="text-center mt-4 pt-3 border-top small text-secondary">
          Đã có tài khoản thành viên? 
          <a href="login.php" class="text-dark fw-bold text-decoration-none">
            Đăng nhập ngay &rarr;
          </a>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
