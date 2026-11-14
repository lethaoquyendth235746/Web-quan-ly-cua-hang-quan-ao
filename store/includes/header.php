<?php
/**
 * TẦNG CỬA HÀNG: Header dùng chung (store/includes/header.php)
 * Hiển thị thanh điều hướng, tìm kiếm, Popover Thẻ thành viên (Origin-aware animation)
 * và bộ chuyển đổi nhanh khách hàng thực tế từ CSDL để kiểm thử các bậc Hạng thẻ.
 */
$currentPage = $_GET['page'] ?? 'home';
$currentGender = $_GET['gender'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

$tierName = $loyaltyProfile['ten_hang'] ?? 'Khách vãng lai';
$availablePoints = (int) ($loyaltyProfile['diem_kha_dung'] ?? 0);
$totalPoints = (int) ($loyaltyProfile['tong_diem_tich_luy'] ?? 0);
$nextTier = $loyaltyProfile['next_tier'] ?? null;
$pointsNeeded = (int) ($loyaltyProfile['points_needed'] ?? 0);
$progressPercent = (float) ($loyaltyProfile['progress_percent'] ?? 0);
$allTiers = $loyaltyProfile['all_tiers'] ?? [];
$tierColor = $loyaltyProfile['mau_sac_the'] ?? '#5F6F52';
$cartTotalCount = array_sum(array_column($cartItems, 'so_luong_mua'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Fashion Loyalty | AURA STUDIO') ?></title>
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
            stone: { 100: '#F3F2EE', 200: '#E7E5E0' },
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
    html { scroll-behavior: smooth; }
    .tabular-nums { font-variant-numeric: tabular-nums; }

    /* Entrances & Sequencing: Fade in + Translate + Stagger (Emil Kowalski's Animation Vocabulary) */
    .fade-up {
      animation: fadeUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
      animation-delay: calc(var(--stagger, 0) * 60ms);
      will-change: transform, opacity;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Origin-aware animation: Popover grows from trigger button (top-right) with Ease-out */
    .popover-origin {
      transform-origin: top right;
      transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1), transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
      will-change: transform, opacity;
    }
    .popover-hidden {
      opacity: 0;
      transform: scale(0.95) translateY(-4px);
      pointer-events: none;
    }
    .popover-visible {
      opacity: 1;
      transform: scale(1) translateY(0);
      pointer-events: auto;
    }

    /* Press / Tap feedback: subtle physical scale-down when clicked */
    .tap-feedback {
      transition: transform 0.15s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }
    .tap-feedback:active:not(:disabled) {
      transform: scale(0.97);
    }

    /* Accordion / Collapse */
    details[open] .accordion-icon { transform: rotate(45deg); }
    .accordion-icon { transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1); }

    /* Slide in Drawer with Ease-out */
    .drawer-open .drawer-panel { transform: translateX(0); }
    .drawer-open .drawer-backdrop { opacity: 1; pointer-events: auto; }
    #cartDrawer { pointer-events: none; }
    .drawer-open #cartDrawer { pointer-events: auto; }

    /* Respect Reduced motion */
    @media (prefers-reduced-motion: reduce) {
      *, ::before, ::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
      }
    }
  </style>
</head>
<body class="bg-cream font-sans text-charcoal antialiased">

  <header id="siteHeader" class="sticky top-0 z-40 border-b border-stone-200 bg-cream/95 backdrop-blur transition-shadow duration-300">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-6 lg:px-8">
      <div class="flex items-center gap-8">
        <a href="index.php" class="text-sm font-semibold tracking-[0.25em] text-charcoal">AURA STUDIO</a>
        <nav class="hidden items-center gap-6 md:flex" aria-label="Danh mục chính">
          <a href="index.php?page=home&gender=all"
            class="nav-link text-sm transition hover:text-charcoal <?= ($currentPage === 'home' && $currentGender === 'all') ? 'font-medium text-charcoal' : 'text-muted' ?>">
            Trang chủ
          </a>
          <a href="index.php?page=home&gender=nam#cua-hang"
            class="nav-link text-sm transition hover:text-charcoal <?= ($currentPage === 'home' && $currentGender === 'nam') ? 'font-medium text-charcoal' : 'text-muted' ?>">
            Thời trang Nam
          </a>
          <a href="index.php?page=home&gender=nu#cua-hang"
            class="nav-link text-sm transition hover:text-charcoal <?= ($currentPage === 'home' && $currentGender === 'nu') ? 'font-medium text-charcoal' : 'text-muted' ?>">
            Thời trang Nữ
          </a>
          <a href="index.php?page=loyalty"
            class="nav-link text-sm transition hover:text-charcoal <?= $currentPage === 'loyalty' ? 'font-medium text-charcoal' : 'text-muted' ?>">
            Ưu đãi Thành viên
          </a>
        </nav>
      </div>

      <div class="mx-auto hidden max-w-md flex-1 lg:block">
        <form action="index.php" method="GET" class="relative block">
          <input type="hidden" name="page" value="home" />
          <?php if ($currentGender !== 'all'): ?>
            <input type="hidden" name="gender" value="<?= htmlspecialchars($currentGender) ?>" />
          <?php endif; ?>
          <span class="sr-only">Tìm kiếm sản phẩm</span>
          <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" stroke-linecap="round" />
          </svg>
          <input id="searchInput" name="q" type="search" value="<?= htmlspecialchars($searchQuery) ?>"
            placeholder="Tìm kiếm áo polo, đầm taffeta, sơ mi lụa..."
            class="w-full rounded-full border border-stone-200 bg-white py-2 pl-11 pr-4 text-sm text-charcoal placeholder:text-muted transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" />
        </form>
      </div>

      <div class="ml-auto flex items-center gap-3 lg:ml-0">
        <!-- Popover Hạng Thành Viên (Origin-aware animation) -->
        <div class="relative">
          <button type="button" id="tierBtn" aria-expanded="false" aria-controls="loyaltyPanel"
            class="tap-feedback flex items-center gap-2 rounded-full border border-stone-200 bg-white px-3.5 py-1.5 text-xs font-medium text-charcoal shadow-sm hover:border-olive/50 hover:shadow">
            <span class="h-2.5 w-2.5 rounded-full" style="background-color: <?= htmlspecialchars($tierColor) ?>"></span>
            <span class="hidden sm:inline"><?= htmlspecialchars($tierName) ?></span>
            <span class="text-muted">&bull;</span>
            <span class="tabular-nums font-semibold"><?= number_format($availablePoints, 0, ',', '.') ?> điểm</span>
          </button>

          <div id="loyaltyPanel" class="popover-origin popover-hidden absolute right-0 top-full z-50 mt-3 w-80 rounded-xl border border-stone-200 bg-white p-5 shadow-lg">
            <div class="flex items-center justify-between">
              <p class="text-xs uppercase tracking-wider text-muted">Thẻ thành viên CSDL</p>
              <span class="font-mono text-xs font-medium text-accent"><?= htmlspecialchars($loyaltyProfile['ma_so_the'] ?? 'CHƯA CẤP') ?></span>
            </div>
            <p class="mt-1 text-lg font-semibold text-charcoal"><?= htmlspecialchars($tierName) ?></p>
            <p class="mt-0.5 text-xs text-muted">
              Tổng tích lũy xét hạng: <strong class="tabular-nums text-charcoal"><?= number_format($totalPoints, 0, ',', '.') ?> điểm</strong>
            </p>
            <?php if ($nextTier): ?>
              <p class="mt-2 text-sm text-muted">
                Còn <span class="tabular-nums font-semibold text-charcoal"><?= number_format($pointsNeeded, 0, ',', '.') ?> điểm</span> để đạt <span class="font-medium text-charcoal"><?= htmlspecialchars($nextTier['ten_hang']) ?></span>
              </p>
              <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-stone-100">
                <div class="h-full rounded-full bg-olive transition-all duration-700" style="width: <?= $progressPercent ?>%"></div>
              </div>
            <?php else: ?>
              <p class="mt-2 text-sm text-olive font-medium">Bạn đang ở hạng cao nhất của hệ thống!</p>
            <?php endif; ?>

            <ul class="mt-4 space-y-2 border-t border-stone-100 pt-3 text-xs text-muted">
              <?php foreach ($allTiers as $t): ?>
                <li class="flex justify-between">
                  <span><?= htmlspecialchars($t['ten_hang']) ?> (<span class="tabular-nums"><?= number_format((int)$t['diem_toi_thieu'], 0, ',', '.') ?></span>đ)</span>
                  <span class="tabular-nums font-medium text-charcoal">Giảm <?= number_format((float)$t['phan_tram_giam'], 0) ?>% &middot; x<?= number_format((float)$t['he_so_tich_diem'], 1) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
            <a href="index.php?page=loyalty" class="tap-feedback mt-4 block w-full rounded-full bg-charcoal py-2 text-center text-xs font-medium text-white hover:bg-accent">
              Mở Trung tâm Thành viên
            </a>
          </div>
        </div>

        <!-- Nút mở Giỏ hàng (Slide in Drawer) -->
        <button type="button" id="cartOpenBtn" aria-label="Mở giỏ hàng" aria-controls="cartDrawer"
          class="tap-feedback relative rounded-full p-2 text-charcoal hover:bg-stone-100">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path d="M6 7h12l-1 13H7L6 7Z" stroke-linejoin="round" /><path d="M9 7V5a3 3 0 0 1 6 0v2" stroke-linecap="round" />
          </svg>
          <span id="cartBadge" class="tabular-nums absolute -right-0.5 -top-0.5 <?= $cartTotalCount > 0 ? 'flex' : 'hidden' ?> h-5 min-w-5 items-center justify-center rounded-full bg-accent px-1 text-[11px] font-semibold text-white">
            <?= $cartTotalCount ?>
          </span>
        </button>

        <!-- Bộ tài khoản người dùng & Chuyển đổi kiểm thử CSDL -->
        <?php $isLoggedIn = isUserLoggedIn(); ?>
        <?php if (!$isLoggedIn): ?>
          <button type="button" onclick="openAuthModal('login')"
            class="tap-feedback hidden sm:inline-flex items-center gap-1.5 rounded-full bg-charcoal px-3.5 py-1.5 text-xs font-medium text-white hover:bg-accent transition shadow-sm">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
            Đăng nhập
          </button>
        <?php endif; ?>

        <div class="relative">
          <button type="button" id="userSwitchBtn" aria-expanded="false" aria-controls="userSwitchPanel"
            class="tap-feedback flex items-center gap-2 rounded-full border border-stone-200 bg-white p-1 pr-3 text-left transition hover:border-stone-300">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-olive text-xs font-semibold text-white">
              <?= htmlspecialchars(mb_substr($loyaltyProfile['ho_ten'] ?? 'K', 0, 1)) ?>
            </span>
            <span class="hidden text-xs font-medium text-charcoal xl:inline"><?= htmlspecialchars($loyaltyProfile['ho_ten'] ?? ($isLoggedIn ? 'Thành viên' : 'Khách vãng lai')) ?></span>
            <svg class="h-3.5 w-3.5 text-muted" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
          </button>

          <div id="userSwitchPanel" class="popover-origin popover-hidden absolute right-0 top-full z-50 mt-3 w-80 rounded-xl border border-stone-200 bg-white p-4 shadow-lg">
            <div class="border-b border-stone-100 pb-3">
              <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">
                  <?= $isLoggedIn ? 'Tài khoản đang đăng nhập' : 'Phiên khách mẫu (CSDL)' ?>
                </p>
                <?php if ($isLoggedIn): ?>
                  <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Đã đăng nhập</span>
                <?php else: ?>
                  <span class="rounded-full bg-stone-100 px-2 py-0.5 text-[10px] font-medium text-stone-600">Khách demo</span>
                <?php endif; ?>
              </div>
              <p class="mt-1 text-sm font-semibold text-charcoal"><?= htmlspecialchars($loyaltyProfile['ho_ten'] ?? 'Chưa đăng nhập') ?></p>
              <p class="text-xs text-muted"><?= htmlspecialchars($loyaltyProfile['email'] ?? '') ?> &middot; <?= htmlspecialchars($loyaltyProfile['sdt'] ?? '') ?></p>
            </div>

            <div class="mt-2.5 flex items-center justify-between text-xs">
              <a href="index.php?page=loyalty#so-dia-chi" class="font-medium text-accent hover:underline">
                Sổ địa chỉ nhận hàng &rarr;
              </a>
              <?php if (hasAdminAccess()): ?>
                <a href="admin/index.php" class="font-semibold text-purple-700 hover:underline">
                  Vào Quản trị (Admin) &rarr;
                </a>
              <?php else: ?>
                <a href="admin/index.php" class="text-stone-500 hover:underline">
                  Thử vào Quản trị &rarr;
                </a>
              <?php endif; ?>
            </div>

            <p class="mt-3 border-t border-stone-100 pt-3 text-[11px] font-medium uppercase tracking-wider text-muted">Chuyển nhanh khách hàng trong DB để test:</p>
            <div class="mt-2 space-y-1.5 max-h-48 overflow-y-auto">
              <?php foreach ($demoCustomers as $cus): ?>
                <?php $isCurrent = ((int)$cus['ma_nd'] === (int)($loyaltyProfile['ma_nd'] ?? 0)); ?>
                <a href="index.php?page=<?= urlencode($currentPage) ?><?= isset($_GET['id']) ? '&id=' . (int)$_GET['id'] : '' ?>&switch_user=<?= (int)$cus['ma_nd'] ?>"
                  class="flex items-center justify-between rounded-lg px-3 py-2 text-xs transition <?= $isCurrent ? 'bg-stone-100 font-semibold text-charcoal' : 'text-muted hover:bg-stone-50 hover:text-charcoal' ?>">
                  <div>
                    <p class="text-charcoal font-medium"><?= htmlspecialchars($cus['ho_ten']) ?></p>
                    <p class="text-[11px] text-muted"><?= htmlspecialchars($cus['ten_hang'] ?? 'Chưa mở thẻ') ?></p>
                  </div>
                  <span class="tabular-nums rounded-full bg-gold px-2 py-0.5 text-[11px] font-medium text-charcoal">
                    <?= number_format((int)($cus['diem_kha_dung'] ?? 0), 0, ',', '.') ?> đ
                  </span>
                </a>
              <?php endforeach; ?>
            </div>

            <div class="mt-3 border-t border-stone-100 pt-3 flex items-center justify-between">
              <button type="button" onclick="openAuthModal('login')"
                class="text-xs font-semibold text-charcoal hover:text-accent hover:underline">
                <?= $isLoggedIn ? 'Đổi tài khoản' : 'Đăng nhập / Đăng ký' ?>
              </button>
              <?php if ($isLoggedIn): ?>
                <a href="logout.php" class="text-xs font-medium text-red-600 hover:underline">Đăng xuất</a>
              <?php else: ?>
                <a href="login.php" class="text-xs font-medium text-accent hover:underline">Mở trang Login &rarr;</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="border-t border-stone-200 px-6 pb-3 pt-3 lg:hidden">
      <form action="index.php" method="GET" class="relative block">
        <input type="hidden" name="page" value="home" />
        <span class="sr-only">Tìm kiếm sản phẩm</span>
        <input id="searchInputMobile" name="q" type="search" value="<?= htmlspecialchars($searchQuery) ?>"
          placeholder="Tìm kiếm áo polo, đầm taffeta..."
          class="w-full rounded-full border border-stone-200 bg-white py-2 pl-4 pr-4 text-sm text-charcoal placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" />
      </form>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-6 pb-20 pt-8 lg:px-8 lg:pt-12">
    <?php if (in_array($currentPage, ['loyalty', 'checkout'], true)): ?>
      <nav id="accountTabs" aria-label="Khu vực tài khoản" class="mb-10">
        <div class="inline-flex rounded-full border border-stone-200 bg-white p-1 shadow-sm">
          <a href="index.php?page=loyalty"
            class="tap-feedback rounded-full px-5 py-2.5 text-sm font-medium transition <?= $currentPage === 'loyalty' ? 'bg-charcoal text-white' : 'text-muted hover:text-charcoal' ?>">
            Trung tâm Thành viên
          </a>
          <a href="index.php?page=checkout"
            class="tap-feedback rounded-full px-5 py-2.5 text-sm font-medium transition <?= $currentPage === 'checkout' ? 'bg-charcoal text-white' : 'text-muted hover:text-charcoal' ?>">
            Thanh toán Giỏ hàng (<?= $cartTotalCount ?>)
          </a>
        </div>
      </nav>
    <?php endif; ?>

