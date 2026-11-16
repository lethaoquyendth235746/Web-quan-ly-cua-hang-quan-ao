<?php
/**
 * TRANG QUẢN LÝ TÀI KHOẢN, THẺ TÍCH ĐIỂM & SỔ ĐỊA CHỈ (profile.php)
 * Giao diện Bootstrap 5 chuẩn hóa, tích hợp đầy đủ:
 * 1. Cập nhật thông tin cá nhân & Đổi mật khẩu
 * 2. Hiển thị thẻ tích điểm từ ttd_the_tich_diem & hạng thành viên
 * 3. Quản lý sổ địa chỉ (dc_dia_chi_kh) với logic Transaction mặc định & chống IDOR
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = getDBConnection();

// 1. Kiểm tra đăng nhập
if (empty($_SESSION['user'])) {
    header('Location: login.php?redirect=profile.php&error=' . urlencode('Vui lòng đăng nhập để xem thông tin tài khoản.'));
    exit;
}

$currentUserId = (int)$_SESSION['user']['ma_nd'];
$errorMsg = '';
$successMsg = '';

if (isset($_GET['registered'])) {
    $successMsg = 'Chúc mừng bạn đã mở thẻ Hạng Đồng thành công!';
}

// 2. Xử lý các Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 2.1 Cập nhật thông tin cá nhân
    if ($action === 'update_profile') {
        $hoTen = trim($_POST['ho_ten'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $sdt   = trim($_POST['sdt'] ?? '');

        if ($hoTen === '' || $email === '' || $sdt === '') {
            $errorMsg = 'Vui lòng điền đầy đủ họ tên, email và số điện thoại.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Địa chỉ email không hợp lệ.';
        } elseif (!preg_match('/^[0-9]{9,15}$/', $sdt)) {
            $errorMsg = 'Số điện thoại không hợp lệ (9-15 số).';
        } else {
            // Kiểm tra trùng email hoặc sdt với tài khoản khác
            $chk = $pdo->prepare("
                SELECT COUNT(*) FROM nd_nguoi_dung 
                WHERE (email = :em OR sdt = :sdt) AND ma_nd != :uid
            ");
            $chk->execute(['em' => $email, 'sdt' => $sdt, 'uid' => $currentUserId]);
            if ((int)$chk->fetchColumn() > 0) {
                $errorMsg = 'Email hoặc số điện thoại đã được tài khoản khác sử dụng.';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE nd_nguoi_dung 
                    SET ho_ten = :ho_ten, email = :email, sdt = :sdt 
                    WHERE ma_nd = :uid
                ");
                $stmt->execute(['ho_ten' => $hoTen, 'email' => $email, 'sdt' => $sdt, 'uid' => $currentUserId]);

                $_SESSION['user']['ho_ten'] = $hoTen;
                $_SESSION['user']['email']  = $email;
                $_SESSION['user']['sdt']    = $sdt;
                $successMsg = 'Cập nhật thông tin cá nhân thành công.';
            }
        }
    }

    // 2.2 Đổi mật khẩu (xác thực mật khẩu cũ)
    elseif ($action === 'change_password') {
        $matKhauCu  = $_POST['mat_khau_cu'] ?? '';
        $matKhauMoi = $_POST['mat_khau_moi'] ?? '';
        $xacNhanMk  = $_POST['xac_nhan_mk'] ?? '';

        if ($matKhauCu === '' || $matKhauMoi === '' || $xacNhanMk === '') {
            $errorMsg = 'Vui lòng điền đầy đủ các thông tin đổi mật khẩu.';
        } elseif (strlen($matKhauMoi) < 6) {
            $errorMsg = 'Mật khẩu mới phải có tối thiểu 6 ký tự.';
        } elseif ($matKhauMoi !== $xacNhanMk) {
            $errorMsg = 'Xác nhận mật khẩu mới không trùng khớp.';
        } else {
            // Lấy hash mật khẩu hiện tại trong CSDL
            $stmtUser = $pdo->prepare("SELECT mat_khau FROM nd_nguoi_dung WHERE ma_nd = :uid");
            $stmtUser->execute(['uid' => $currentUserId]);
            $currentHash = $stmtUser->fetchColumn();

            $isOldPassValid = false;
            if (password_verify($matKhauCu, $currentHash) || $currentHash === $matKhauCu) {
                $isOldPassValid = true;
            }

            if (!$isOldPassValid) {
                $errorMsg = 'Mật khẩu hiện tại không chính xác.';
            } else {
                $newHash = password_hash($matKhauMoi, PASSWORD_DEFAULT);
                $up = $pdo->prepare("UPDATE nd_nguoi_dung SET mat_khau = :hash WHERE ma_nd = :uid");
                $up->execute(['hash' => $newHash, 'uid' => $currentUserId]);
                $successMsg = 'Đổi mật khẩu thành công! Mật khẩu mới đã được kích hoạt.';
            }
        }
    }

    // 2.3 Thêm mới địa chỉ nhận hàng
    elseif ($action === 'add_address') {
        $tenNguoiNhan = trim($_POST['ten_nguoi_nhan'] ?? '');
        $sdtNhan      = trim($_POST['sdt_nhan'] ?? '');
        $tinhThanh    = trim($_POST['tinh_thanh'] ?? '');
        $quanHuyen    = trim($_POST['quan_huyen'] ?? '');
        $phuongXa     = trim($_POST['phuong_xa'] ?? '');
        $diaChiCuThe  = trim($_POST['dia_chi_cu_the'] ?? '');
        $macDinhReq   = !empty($_POST['mac_dinh']) ? 1 : 0;

        if ($tenNguoiNhan === '' || $sdtNhan === '' || $tinhThanh === '' || $quanHuyen === '' || $phuongXa === '' || $diaChiCuThe === '') {
            $errorMsg = 'Vui lòng điền đầy đủ thông tin địa chỉ.';
        } elseif (!preg_match('/^[0-9]{9,15}$/', $sdtNhan)) {
            $errorMsg = 'Số điện thoại nhận hàng không hợp lệ (9-15 số).';
        } else {
            $pdo->beginTransaction();
            try {
                // Kiểm tra số lượng địa chỉ hiện tại
                $cnt = $pdo->prepare("SELECT COUNT(*) FROM dc_dia_chi_kh WHERE ma_nd = :uid");
                $cnt->execute(['uid' => $currentUserId]);
                $count = (int)$cnt->fetchColumn();

                $macDinh = ($count === 0 || $macDinhReq === 1) ? 1 : 0;

                if ($macDinh === 1 && $count > 0) {
                    $reset = $pdo->prepare("UPDATE dc_dia_chi_kh SET mac_dinh = 0 WHERE ma_nd = :uid");
                    $reset->execute(['uid' => $currentUserId]);
                }

                $ins = $pdo->prepare("
                    INSERT INTO dc_dia_chi_kh (
                        ma_nd, ten_nguoi_nhan, sdt_nhan, tinh_thanh, quan_huyen, phuong_xa, dia_chi_cu_the, mac_dinh
                    ) VALUES (
                        :uid, :ten, :sdt, :tinh, :quan, :phuong, :dc, :md
                    )
                ");
                $ins->execute([
                    'uid'    => $currentUserId,
                    'ten'    => $tenNguoiNhan,
                    'sdt'    => $sdtNhan,
                    'tinh'   => $tinhThanh,
                    'quan'   => $quanHuyen,
                    'phuong' => $phuongXa,
                    'dc'     => $diaChiCuThe,
                    'md'     => $macDinh,
                ]);

                $pdo->commit();
                $successMsg = 'Đã thêm địa chỉ nhận hàng mới thành công.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errorMsg = 'Lỗi khi thêm địa chỉ: ' . $e->getMessage();
            }
        }
    }

    // 2.4 Chỉnh sửa địa chỉ nhận hàng (Chống IDOR: ràng buộc ma_nd)
    elseif ($action === 'edit_address') {
        $maDc         = (int)($_POST['ma_dc'] ?? 0);
        $tenNguoiNhan = trim($_POST['ten_nguoi_nhan'] ?? '');
        $sdtNhan      = trim($_POST['sdt_nhan'] ?? '');
        $tinhThanh    = trim($_POST['tinh_thanh'] ?? '');
        $quanHuyen    = trim($_POST['quan_huyen'] ?? '');
        $phuongXa     = trim($_POST['phuong_xa'] ?? '');
        $diaChiCuThe  = trim($_POST['dia_chi_cu_the'] ?? '');
        $macDinhReq   = !empty($_POST['mac_dinh']) ? 1 : 0;

        // Ràng buộc quyền sở hữu
        $chkOwner = $pdo->prepare("SELECT * FROM dc_dia_chi_kh WHERE ma_dc = :dc AND ma_nd = :uid");
        $chkOwner->execute(['dc' => $maDc, 'uid' => $currentUserId]);
        $existing = $chkOwner->fetch();

        if (!$existing) {
            $errorMsg = 'Không tìm thấy địa chỉ hoặc bạn không có quyền sửa địa chỉ này.';
        } elseif ($tenNguoiNhan === '' || $sdtNhan === '' || $tinhThanh === '' || $quanHuyen === '' || $phuongXa === '' || $diaChiCuThe === '') {
            $errorMsg = 'Vui lòng điền đầy đủ các thông tin địa chỉ.';
        } else {
            $pdo->beginTransaction();
            try {
                if ($macDinhReq === 1) {
                    $reset = $pdo->prepare("UPDATE dc_dia_chi_kh SET mac_dinh = 0 WHERE ma_nd = :uid");
                    $reset->execute(['uid' => $currentUserId]);
                    $macDinh = 1;
                } else {
                    // Nếu đây là địa chỉ duy nhất thì bắt buộc giữ 1
                    $cnt = $pdo->prepare("SELECT COUNT(*) FROM dc_dia_chi_kh WHERE ma_nd = :uid");
                    $cnt->execute(['uid' => $currentUserId]);
                    $macDinh = ((int)$cnt->fetchColumn() <= 1) ? 1 : 0;
                }

                $up = $pdo->prepare("
                    UPDATE dc_dia_chi_kh 
                    SET ten_nguoi_nhan = :ten, sdt_nhan = :sdt, tinh_thanh = :tinh, quan_huyen = :quan,
                        phuong_xa = :phuong, dia_chi_cu_the = :dc, mac_dinh = :md
                    WHERE ma_dc = :ma_dc AND ma_nd = :uid
                ");
                $up->execute([
                    'ten'    => $tenNguoiNhan,
                    'sdt'    => $sdtNhan,
                    'tinh'   => $tinhThanh,
                    'quan'   => $quanHuyen,
                    'phuong' => $phuongXa,
                    'dc'     => $diaChiCuThe,
                    'md'     => $macDinh,
                    'ma_dc'  => $maDc,
                    'uid'    => $currentUserId,
                ]);

                // Đảm bảo luôn có 1 địa chỉ mặc định
                $chkDef = $pdo->prepare("SELECT COUNT(*) FROM dc_dia_chi_kh WHERE ma_nd = :uid AND mac_dinh = 1");
                $chkDef->execute(['uid' => $currentUserId]);
                if ((int)$chkDef->fetchColumn() === 0) {
                    $fix = $pdo->prepare("UPDATE dc_dia_chi_kh SET mac_dinh = 1 WHERE ma_nd = :uid ORDER BY ma_dc DESC LIMIT 1");
                    $fix->execute(['uid' => $currentUserId]);
                }

                $pdo->commit();
                $successMsg = 'Cập nhật địa chỉ nhận hàng thành công.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errorMsg = 'Lỗi khi cập nhật địa chỉ: ' . $e->getMessage();
            }
        }
    }

    // 2.5 Đặt làm địa chỉ mặc định (Transaction)
    elseif ($action === 'set_default_address') {
        $maDc = (int)($_POST['ma_dc'] ?? 0);

        $chkOwner = $pdo->prepare("SELECT COUNT(*) FROM dc_dia_chi_kh WHERE ma_dc = :dc AND ma_nd = :uid");
        $chkOwner->execute(['dc' => $maDc, 'uid' => $currentUserId]);
        if ((int)$chkOwner->fetchColumn() === 0) {
            $errorMsg = 'Không tìm thấy địa chỉ hoặc bạn không có quyền thao tác.';
        } else {
            $pdo->beginTransaction();
            try {
                // Đưa tất cả địa chỉ của khách về 0
                $r = $pdo->prepare("UPDATE dc_dia_chi_kh SET mac_dinh = 0 WHERE ma_nd = :uid");
                $r->execute(['uid' => $currentUserId]);

                // Đặt địa chỉ được chọn thành 1
                $s = $pdo->prepare("UPDATE dc_dia_chi_kh SET mac_dinh = 1 WHERE ma_dc = :dc AND ma_nd = :uid");
                $s->execute(['dc' => $maDc, 'uid' => $currentUserId]);

                $pdo->commit();
                $successMsg = 'Đã đặt địa chỉ làm mặc định thành công.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errorMsg = 'Lỗi khi đặt địa chỉ mặc định: ' . $e->getMessage();
            }
        }
    }

    // 2.6 Xóa địa chỉ nhận hàng (Chống IDOR & Đôn địa chỉ còn lại)
    elseif ($action === 'delete_address') {
        $maDc = (int)($_POST['ma_dc'] ?? 0);

        $chk = $pdo->prepare("SELECT mac_dinh FROM dc_dia_chi_kh WHERE ma_dc = :dc AND ma_nd = :uid");
        $chk->execute(['dc' => $maDc, 'uid' => $currentUserId]);
        $row = $chk->fetch();

        if (!$row) {
            $errorMsg = 'Không tìm thấy địa chỉ hoặc bạn không có quyền xóa.';
        } else {
            $wasDefault = ((int)$row['mac_dinh'] === 1);
            $pdo->beginTransaction();
            try {
                $del = $pdo->prepare("DELETE FROM dc_dia_chi_kh WHERE ma_dc = :dc AND ma_nd = :uid");
                $del->execute(['dc' => $maDc, 'uid' => $currentUserId]);

                // Nếu xóa địa chỉ mặc định, tự động gán mac_dinh = 1 cho địa chỉ còn lại mới nhất
                if ($wasDefault) {
                    $promote = $pdo->prepare("
                        UPDATE dc_dia_chi_kh 
                        SET mac_dinh = 1 
                        WHERE ma_nd = :uid 
                        ORDER BY ma_dc DESC 
                        LIMIT 1
                    ");
                    $promote->execute(['uid' => $currentUserId]);
                }

                $pdo->commit();
                $successMsg = 'Đã xóa địa chỉ thành công.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errorMsg = 'Lỗi khi xóa địa chỉ: ' . $e->getMessage();
            }
        }
    }
}

// 3. Lấy thông tin tài khoản đầy đủ từ CSDL
$userStmt = $pdo->prepare("
    SELECT nd.*, vt.ten_vt, vt.mo_ta_quyen
    FROM nd_nguoi_dung nd
    INNER JOIN vt_vai_tro vt ON nd.ma_vt = vt.ma_vt
    WHERE nd.ma_nd = :uid
");
$userStmt->execute(['uid' => $currentUserId]);
$userInfo = $userStmt->fetch();

// 4. Lấy thông tin Thẻ tích điểm & Hạng thành viên
$cardStmt = $pdo->prepare("
    SELECT ttd.*, htv.ten_hang, htv.phan_tram_giam, htv.he_so_tich_diem, htv.mau_sac_the, htv.mo_ta_uu_dai
    FROM ttd_the_tich_diem ttd
    INNER JOIN htv_hang_thanh_vien htv ON ttd.ma_hang = htv.ma_hang
    WHERE ttd.ma_nd = :uid
    LIMIT 1
");
$cardStmt->execute(['uid' => $currentUserId]);
$cardInfo = $cardStmt->fetch();

// 5. Lấy Sổ địa chỉ qua helper getUserAddresses()
$addresses = getUserAddresses($pdo, $currentUserId);

$pageTitle = 'Hồ sơ cá nhân & Thẻ thành viên | AURA STUDIO';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  
  <!-- Thông báo Alert Flash Message -->
  <?php if (!empty($successMsg)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($successMsg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($errorMsg)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($errorMsg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- THẺ TÍCH ĐIỂM THÀNH VIÊN (LOYALTY CARD BANNER) -->
  <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden text-white" 
    style="background: linear-gradient(135deg, #2D2D2D 0%, #1f1f1f 100%); border-top: 4px solid <?= htmlspecialchars($cardInfo['mau_sac_the'] ?? '#CD7F32') ?> !important;">
    <div class="card-body p-4 p-md-5">
      <div class="row align-items-center g-4">
        <div class="col-lg-7">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge px-3 py-1.5 rounded-pill fw-semibold" style="background-color: <?= htmlspecialchars($cardInfo['mau_sac_the'] ?? '#CD7F32') ?>; color: #fff;">
              <?= htmlspecialchars($cardInfo['ten_hang'] ?? 'Hạng Đồng (Member)') ?>
            </span>
            <span class="text-white-50 small tracking-wider text-uppercase font-monospace">AURA LOYALTY CARD</span>
          </div>

          <div class="font-monospace fs-4 tracking-wider fw-bold text-white mb-3">
            <?= htmlspecialchars($cardInfo['ma_so_the'] ?? 'THE-AGU-CHƯA_CẤP') ?>
          </div>

          <div class="d-flex flex-wrap gap-4 text-white-50 small">
            <div>
              <div class="text-uppercase" style="font-size: 11px;">Chủ sở hữu</div>
              <div class="fw-semibold text-white fs-6"><?= htmlspecialchars($userInfo['ho_ten'] ?? '') ?></div>
            </div>
            <div>
              <div class="text-uppercase" style="font-size: 11px;">Vai trò hệ thống</div>
              <div class="fw-semibold text-white fs-6"><?= htmlspecialchars($userInfo['ten_vt'] ?? 'Thành viên') ?></div>
            </div>
            <div>
              <div class="text-uppercase" style="font-size: 11px;">Ngày cấp thẻ</div>
              <div class="fw-semibold text-white fs-6"><?= !empty($cardInfo['ngay_cap']) ? date('d/m/Y', strtotime($cardInfo['ngay_cap'])) : '-' ?></div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="row g-2 text-center">
            <div class="col-6">
              <div class="bg-white bg-opacity-10 rounded-3 p-3 border border-white border-opacity-10">
                <div class="text-white-50 small text-uppercase" style="font-size: 11px;">Tổng điểm xét hạng</div>
                <div class="fs-4 fw-bold text-white mt-1">
                  <?= number_format((int)($cardInfo['tong_diem_tich_luy'] ?? 0), 0, ',', '.') ?>
                </div>
                <div class="text-white-50" style="font-size: 11px;">điểm trọn đời</div>
              </div>
            </div>
            <div class="col-6">
              <div class="bg-white bg-opacity-10 rounded-3 p-3 border border-white border-opacity-10">
                <div class="text-warning small text-uppercase fw-semibold" style="font-size: 11px;">Điểm khả dụng</div>
                <div class="fs-4 fw-bold text-warning mt-1">
                  <?= number_format((int)($cardInfo['diem_kha_dung'] ?? 0), 0, ',', '.') ?>
                </div>
                <div class="text-white-50" style="font-size: 11px;">đổi voucher / trừ tiền</div>
              </div>
            </div>
          </div>
          <?php if (!empty($cardInfo['mo_ta_uu_dai'])): ?>
            <div class="mt-3 p-2.5 rounded-3 bg-white bg-opacity-10 text-white-50 small text-center" style="font-size: 12px;">
              <i class="bi bi-stars text-warning me-1"></i>Đặc quyền: <?= htmlspecialchars($cardInfo['mo_ta_uu_dai']) ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- CỘT TRÁI: THÔNG TIN CÁ NHÂN & ĐỔI MẬT KHẨU -->
    <div class="col-lg-5">
      <!-- 1. Thông tin cá nhân -->
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h5 class="fw-bold text-dark fs-6 mb-3 d-flex align-items-center gap-2">
          <i class="bi bi-person-lines-fill text-secondary"></i>
          Thông tin cá nhân (nd_nguoi_dung)
        </h5>
        
        <form method="POST" action="profile.php">
          <input type="hidden" name="action" value="update_profile">

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Tên đăng nhập</label>
            <input type="text" class="form-control bg-light shadow-none" value="<?= htmlspecialchars($userInfo['ten_dang_nhap']) ?>" readonly>
            <div class="form-text" style="font-size: 11px;">Tên đăng nhập không thể thay đổi.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Họ và tên <span class="text-danger">*</span></label>
            <input type="text" class="form-control shadow-none" name="ho_ten" required value="<?= htmlspecialchars($userInfo['ho_ten']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control shadow-none" name="email" required value="<?= htmlspecialchars($userInfo['email']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Số điện thoại <span class="text-danger">*</span></label>
            <input type="tel" class="form-control shadow-none" name="sdt" required value="<?= htmlspecialchars($userInfo['sdt']) ?>">
          </div>

          <button type="submit" class="btn btn-dark btn-sm w-100 py-2 rounded-3 fw-medium">
            <i class="bi bi-check-lg me-1"></i>Lưu thay đổi hồ sơ
          </button>
        </form>
      </div>

      <!-- 2. Đổi mật khẩu -->
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold text-dark fs-6 mb-3 d-flex align-items-center gap-2">
          <i class="bi bi-shield-lock text-secondary"></i>
          Đổi mật khẩu tài khoản
        </h5>

        <form method="POST" action="profile.php">
          <input type="hidden" name="action" value="change_password">

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Mật khẩu hiện tại <span class="text-danger">*</span></label>
            <input type="password" class="form-control shadow-none" name="mat_khau_cu" required placeholder="Nhập mật khẩu hiện tại">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Mật khẩu mới <span class="text-danger">*</span></label>
            <input type="password" class="form-control shadow-none" name="mat_khau_moi" required minlength="6" placeholder="Tối thiểu 6 ký tự">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
            <input type="password" class="form-control shadow-none" name="xac_nhan_mk" required minlength="6" placeholder="Nhập lại mật khẩu mới">
          </div>

          <button type="submit" class="btn btn-outline-dark btn-sm w-100 py-2 rounded-3 fw-medium">
            <i class="bi bi-key me-1"></i>Cập nhật mật khẩu mới
          </button>
        </form>
      </div>
    </div>

    <!-- CỘT PHẢI: QUẢN LÝ SỔ ĐỊA CHỈ NHẬN HÀNG (dc_dia_chi_kh) -->
    <div class="col-lg-7" id="address-section">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 pb-3 border-bottom">
          <div>
            <h5 class="fw-bold text-dark fs-6 mb-1 d-flex align-items-center gap-2">
              <i class="bi bi-geo-alt-fill text-danger"></i>
              Sổ địa chỉ nhận hàng (bảng <code>dc_dia_chi_kh</code>)
            </h5>
            <p class="text-secondary small mb-0">Địa chỉ dùng để giao nhận hàng hóa khi thanh toán đơn hàng</p>
          </div>
          <button type="button" class="btn btn-aura btn-sm rounded-pill px-3 py-1.5" onclick="openBsAddressModal('add')">
            <i class="bi bi-plus-lg me-1"></i>Thêm địa chỉ mới
          </button>
        </div>

        <?php if (empty($addresses)): ?>
          <div class="text-center py-5 border border-dashed rounded-4 bg-light">
            <i class="bi bi-geo text-secondary fs-1 d-block mb-2"></i>
            <p class="text-secondary small mb-3">Bạn chưa lưu địa chỉ nhận hàng nào trong hệ thống.</p>
            <button type="button" class="btn btn-dark btn-sm rounded-pill px-3" onclick="openBsAddressModal('add')">
              + Thêm địa chỉ đầu tiên ngay
            </button>
          </div>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($addresses as $addr): ?>
              <?php $isDef = ((int)$addr['mac_dinh'] === 1); ?>
              <div class="card rounded-3 p-3 <?= $isDef ? 'border-dark bg-light bg-opacity-25' : 'border' ?>">
                <div class="d-flex justify-content-between align-items-start gap-3">
                  <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                      <span class="fw-bold text-dark small"><?= htmlspecialchars($addr['ten_nguoi_nhan']) ?></span>
                      <?php if ($isDef): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small" style="font-size: 10px;">
                          Mặc định (mac_dinh = 1)
                        </span>
                      <?php endif; ?>
                    </div>
                    <div class="text-secondary small mb-1">
                      <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($addr['sdt_nhan']) ?>
                    </div>
                    <div class="text-muted small">
                      <i class="bi bi-pin-map me-1"></i>
                      <?= htmlspecialchars($addr['dia_chi_cu_the']) ?>, <?= htmlspecialchars($addr['phuong_xa']) ?>, <?= htmlspecialchars($addr['quan_huyen']) ?>, <?= htmlspecialchars($addr['tinh_thanh']) ?>
                    </div>
                  </div>

                  <!-- Hành động -->
                  <div class="d-flex flex-column align-items-end gap-1">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-light border btn-sm text-primary" 
                        onclick='openBsAddressModal("edit", <?= htmlspecialchars(json_encode($addr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, "UTF-8") ?>)' title="Chỉnh sửa">
                        <i class="bi bi-pencil"></i> Sửa
                      </button>
                      <form method="POST" action="profile.php" onsubmit="return confirm('Bạn có chắc chắn muốn xóa địa chỉ này?');" class="d-inline">
                        <input type="hidden" name="action" value="delete_address">
                        <input type="hidden" name="ma_dc" value="<?= (int)$addr['ma_dc'] ?>">
                        <button type="submit" class="btn btn-light border btn-sm text-danger" title="Xóa">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>

                    <?php if (!$isDef): ?>
                      <form method="POST" action="profile.php" class="mt-1">
                        <input type="hidden" name="action" value="set_default_address">
                        <input type="hidden" name="ma_dc" value="<?= (int)$addr['ma_dc'] ?>">
                        <button type="submit" class="btn btn-link p-0 text-decoration-none small text-secondary" style="font-size: 11px;">
                          Đặt làm mặc định
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<!-- BOOTSTRAP MODAL THÊM / SỬA ĐỊA CHỈ -->
<div class="modal fade" id="bsAddressModal" tabindex="-1" aria-labelledby="bsAddressModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header border-bottom py-3">
        <h5 class="modal-title fs-6 fw-bold text-dark" id="bsAddressModalLabel">Thêm địa chỉ nhận hàng mới</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="POST" action="profile.php">
        <input type="hidden" id="modalAction" name="action" value="add_address">
        <input type="hidden" id="modalMaDc" name="ma_dc" value="">

        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label small fw-semibold text-dark">Họ tên người nhận <span class="text-danger">*</span></label>
              <input type="text" class="form-control shadow-none" id="modalTenNguoiNhan" name="ten_nguoi_nhan" required placeholder="VD: Neàng Ra">
            </div>
            <div class="col-sm-6">
              <label class="form-label small fw-semibold text-dark">Số điện thoại <span class="text-danger">*</span></label>
              <input type="tel" class="form-control shadow-none" id="modalSdtNhan" name="sdt_nhan" required placeholder="VD: 0918223344">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-4">
              <label class="form-label small fw-semibold text-dark">Tỉnh / Thành <span class="text-danger">*</span></label>
              <input type="text" class="form-control shadow-none" id="modalTinhThanh" name="tinh_thanh" required placeholder="An Giang">
            </div>
            <div class="col-sm-4">
              <label class="form-label small fw-semibold text-dark">Quận / Huyện <span class="text-danger">*</span></label>
              <input type="text" class="form-control shadow-none" id="modalQuanHuyen" name="quan_huyen" required placeholder="Long Xuyên">
            </div>
            <div class="col-sm-4">
              <label class="form-label small fw-semibold text-dark">Phường / Xã <span class="text-danger">*</span></label>
              <input type="text" class="form-control shadow-none" id="modalPhuongXa" name="phuong_xa" required placeholder="Đông Xuyên">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Địa chỉ cụ thể (Số nhà, tên đường...) <span class="text-danger">*</span></label>
            <input type="text" class="form-control shadow-none" id="modalDiaChiCuThe" name="dia_chi_cu_the" required placeholder="VD: Số 18 Ung Văn Khiêm (Khu ĐH An Giang)">
          </div>

          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="modalMacDinh" name="mac_dinh" value="1">
            <label class="form-check-label small fw-medium text-dark" for="modalMacDinh">
              Đặt làm địa chỉ nhận hàng mặc định (mac_dinh = 1)
            </label>
          </div>
        </div>

        <div class="modal-footer border-top py-2 px-4">
          <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Hủy bỏ</button>
          <button type="submit" class="btn btn-dark btn-sm rounded-pill px-4" id="modalSubmitBtn">Lưu địa chỉ</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function openBsAddressModal(mode, data = null) {
    const modalEl = document.getElementById('bsAddressModal');
    const modal = new bootstrap.Modal(modalEl);
    const label = document.getElementById('bsAddressModalLabel');
    const actionInput = document.getElementById('modalAction');
    const maDcInput = document.getElementById('modalMaDc');
    const submitBtn = document.getElementById('modalSubmitBtn');

    if (mode === 'edit' && data) {
      label.textContent = 'Chỉnh sửa địa chỉ nhận hàng';
      actionInput.value = 'edit_address';
      maDcInput.value = data.ma_dc;
      document.getElementById('modalTenNguoiNhan').value = data.ten_nguoi_nhan || '';
      document.getElementById('modalSdtNhan').value = data.sdt_nhan || '';
      document.getElementById('modalTinhThanh').value = data.tinh_thanh || '';
      document.getElementById('modalQuanHuyen').value = data.quan_huyen || '';
      document.getElementById('modalPhuongXa').value = data.phuong_xa || '';
      document.getElementById('modalDiaChiCuThe').value = data.dia_chi_cu_the || '';
      document.getElementById('modalMacDinh').checked = (parseInt(data.mac_dinh, 10) === 1);
      submitBtn.textContent = 'Cập nhật địa chỉ';
    } else {
      label.textContent = 'Thêm mới địa chỉ nhận hàng';
      actionInput.value = 'add_address';
      maDcInput.value = '';
      document.getElementById('modalTenNguoiNhan').value = '';
      document.getElementById('modalSdtNhan').value = '';
      document.getElementById('modalTinhThanh').value = 'An Giang';
      document.getElementById('modalQuanHuyen').value = '';
      document.getElementById('modalPhuongXa').value = '';
      document.getElementById('modalDiaChiCuThe').value = '';
      document.getElementById('modalMacDinh').checked = false;
      submitBtn.textContent = 'Lưu địa chỉ';
    }

    modal.show();
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
