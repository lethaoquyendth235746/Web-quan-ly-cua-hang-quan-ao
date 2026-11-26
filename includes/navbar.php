<?php

/**
 * THANH ĐIỀU HƯỚNG NAVBAR CLIENT (includes/navbar.php)
 * Hiển thị linh hoạt theo trạng thái đăng nhập và vai trò người dùng (Bootstrap 5)
 */

$user = $_SESSION['user'] ?? null;
$isLoggedIn = !empty($user);
$userRole = (int)($user['ma_vt'] ?? 0);
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-2.5">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand fw-bold fs-6 brand-letter-spacing text-dark" href="index.php">
      AURA STUDIO
    </a>

    <!-- Mobile toggle -->
    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#clientNavbar" aria-controls="clientNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Nav items -->
    <div class="collapse navbar-collapse" id="clientNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-lg-2">
        <li class="nav-item">
          <a class="nav-link <?= ($currentScript === 'index.php' && empty($_GET['page'])) ? 'active' : '' ?>" href="index.php">
            Trang chủ
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=home&gender=nam#cua-hang">
            Thời trang Nam
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=home&gender=nu#cua-hang">
            Thời trang Nữ
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= (($_GET['page'] ?? '') === 'loyalty') ? 'active' : '' ?>" href="index.php?page=loyalty">
            <i class="bi bi-award text-warning me-1"></i>Ưu đãi Thành viên
          </a>
        </li>
      </ul>

      <!-- Right items: Giỏ hàng & Trạng thái Tài khoản -->
      <div class="d-flex align-items-center gap-3">
        <!-- Nút Giỏ hàng -->
        <a href="index.php?page=checkout" class="btn btn-light rounded-pill position-relative px-3 py-1.5 text-secondary border">
          <i class="bi bi-bag fs-6"></i>
          <span class="d-none d-sm-inline ms-1 small fw-medium">Giỏ hàng</span>
        </a>

        <?php if ($isLoggedIn): ?>
          <!-- Dropdown khi đã đăng nhập -->
          <div class="dropdown">
            <button class="btn btn-outline-secondary rounded-pill d-flex align-items-center gap-2 px-3 py-1 dropdown-toggle shadow-none" type="button" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center small fw-semibold" style="width: 24px; height: 24px;">
                <?= htmlspecialchars(mb_substr($user['ho_ten'] ?? 'U', 0, 1)) ?>
              </span>
              <span class="small fw-semibold text-dark text-truncate" style="max-width: 140px;">
                <?= htmlspecialchars($user['ho_ten'] ?? 'Tài khoản') ?>
              </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2 py-2" aria-labelledby="userMenuBtn" style="min-width: 230px; border-radius: 12px;">
              <li class="px-3 py-2 border-bottom mb-1">
                <div class="fw-semibold text-dark small"><?= htmlspecialchars($user['ho_ten'] ?? '') ?></div>
                <div class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                <div class="mt-1">
                  <?php if ($userRole === ROLE_ADMIN): ?>
                    <span class="badge bg-purple-subtle text-purple text-dark border small" style="background:#f3e8ff;">👑 Quản trị viên</span>
                  <?php elseif ($userRole === ROLE_STAFF): ?>
                    <span class="badge bg-info-subtle text-info text-dark border small" style="background:#e0f2fe;">💼 Nhân viên</span>
                  <?php else: ?>
                    <span class="badge bg-success-subtle text-success text-dark border small" style="background:#dcfce7;">🥉 Khách hàng TV</span>
                  <?php endif; ?>
                </div>
              </li>
              <li>
                <a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="profile.php">
                  <i class="bi bi-person-gear text-secondary"></i>
                  Hồ sơ cá nhân &amp; Thẻ
                </a>
              </li>
              <li>
                <a class="dropdown-item small py-2 d-flex align-items-center gap-2" href="profile.php#address-section">
                  <i class="bi bi-geo-alt text-secondary"></i>
                  Sổ địa chỉ nhận hàng
                </a>
              </li>
              <?php if (in_array($userRole, [ROLE_ADMIN, ROLE_STAFF], true)): ?>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                  <a class="dropdown-item small py-2 fw-semibold text-primary d-flex align-items-center gap-2" href="admin/index.php">
                    <i class="bi bi-speedometer2"></i>
                    Vào trang Quản trị (Admin) &rarr;
                  </a>
                </li>
              <?php endif; ?>
              <li><hr class="dropdown-divider my-1"></li>
              <li>
                <a class="dropdown-item small py-2 text-danger d-flex align-items-center gap-2" href="logout.php">
                  <i class="bi bi-box-arrow-right"></i>
                  Đăng xuất
                </a>
              </li>
            </ul>
          </div>
        <?php else: ?>
          <!-- Nút Đăng nhập & Đăng ký khi chưa đăng nhập -->
          <div class="d-flex align-items-center gap-2">
            <a href="login.php" class="btn btn-outline-dark rounded-pill px-3 py-1.5 small fw-medium">
              Đăng nhập
            </a>
            <a href="register.php" class="btn btn-aura rounded-pill px-3 py-1.5 small fw-medium">
              Đăng ký
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
