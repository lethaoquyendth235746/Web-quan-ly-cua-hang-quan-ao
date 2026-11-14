<?php
/**
 * TẦNG QUẢN TRỊ: Header & Sidebar dùng chung (admin/includes/header.php)
 * Kết nối dữ liệu quản trị viên từ CSDL (bảng nd_nguoi_dung & vt_vai_tro)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/admin/DashboardService.php';

$dashServiceForHeader = new DashboardService();
$adminUser = $dashServiceForHeader->getCurrentAdminProfile(getCurrentAdminId());
$activeAdminMenu = $activeAdminMenu ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($adminPageTitle ?? 'Quản trị | Fashion Loyalty') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <?php if (!empty($useChartJs)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <?php endif; ?>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['"Be Vietnam Pro"', 'system-ui', 'sans-serif'] },
          colors: { canvas: '#F8FAFC' },
        },
      },
    };
  </script>
  <style>
    .tabular-nums { font-variant-numeric: tabular-nums; }

    /* Entrances & Stagger (Animation Vocabulary) */
    .fade-up {
      animation: fadeUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
      animation-delay: calc(var(--stagger, 0) * 50ms);
      will-change: transform, opacity;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Press / Tap feedback */
    .tap-feedback {
      transition: transform 0.15s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }
    .tap-feedback:active:not(:disabled) {
      transform: scale(0.97);
    }

    .nav-item { transition: background-color 0.2s ease, color 0.2s ease; }

    @media (prefers-reduced-motion: reduce) {
      *, ::before, ::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>
<body class="bg-canvas font-sans text-slate-800 antialiased">
  <div class="flex min-h-screen flex-col lg:flex-row">
    <aside class="border-b border-slate-200 bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-64 lg:shrink-0 lg:flex-col lg:border-b-0 lg:border-r">
      <div class="px-6 pb-4 pt-6 lg:pb-6">
        <a href="../index.php" class="text-sm font-semibold tracking-[0.25em] text-slate-900 hover:text-blue-700">AURA STUDIO</a>
        <p class="mt-1 text-xs text-slate-500">Quản trị Fashion Loyalty (CSDL)</p>
      </div>

      <nav aria-label="Menu quản trị" class="flex gap-1 overflow-x-auto px-3 pb-3 lg:flex-col lg:overflow-visible lg:pb-0">
        <a href="index.php" <?= $activeAdminMenu === 'dashboard' ? 'aria-current="page"' : '' ?>
          class="nav-item flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activeAdminMenu === 'dashboard' ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?>">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1.5" /><rect x="14" y="3" width="7" height="5" rx="1.5" /><rect x="14" y="12" width="7" height="9" rx="1.5" /><rect x="3" y="16" width="7" height="5" rx="1.5" /></svg>
          Tổng quan
        </a>
        <a href="products.php" <?= $activeAdminMenu === 'products' ? 'aria-current="page"' : '' ?>
          class="nav-item flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activeAdminMenu === 'products' ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?>">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7l8-4 8 4-8 4-8-4Z" stroke-linejoin="round" /><path d="M4 7v10l8 4 8-4V7" stroke-linejoin="round" /></svg>
          Danh mục &amp; Sản phẩm
        </a>
        <a href="inventory.php" <?= $activeAdminMenu === 'inventory' ? 'aria-current="page"' : '' ?>
          class="nav-item flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activeAdminMenu === 'inventory' ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?>">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 8h18M5 8v11h14V8M9 12h6" stroke-linejoin="round" /></svg>
          Quản lý nhập hàng
        </a>
        <a href="orders.php" <?= $activeAdminMenu === 'orders' ? 'aria-current="page"' : '' ?>
          class="nav-item flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activeAdminMenu === 'orders' ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?>">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 4h12v16l-6-3-6 3V4Z" stroke-linejoin="round" /></svg>
          Quản lý Đơn hàng
        </a>
        <a href="tiers.php" <?= $activeAdminMenu === 'tiers' ? 'aria-current="page"' : '' ?>
          class="nav-item flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activeAdminMenu === 'tiers' ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?>">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.6 6.8 19.1l1-5.8-4.3-4.1 5.9-.9L12 3Z" stroke-linejoin="round" /></svg>
          Cấu hình Hạng thành viên
        </a>
        <a href="vouchers.php" <?= $activeAdminMenu === 'vouchers' ? 'aria-current="page"' : '' ?>
          class="nav-item flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm <?= $activeAdminMenu === 'vouchers' ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?>">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9a2 2 0 0 0 0 4v2h18v-2a2 2 0 0 0 0-4V7H3v2Z" stroke-linejoin="round" /></svg>
          Chiến dịch Voucher
        </a>
      </nav>

      <div class="border-t border-slate-200 px-6 py-4 lg:mt-auto space-y-2">
        <div>
          <p class="text-xs font-semibold text-slate-900"><?= htmlspecialchars($adminUser['ho_ten'] ?? 'Lê Minh Đức') ?></p>
          <p class="truncate text-[11px] text-slate-500"><?= htmlspecialchars($adminUser['ten_vt'] ?? 'Quản trị viên') ?></p>
          <p class="truncate text-[11px] text-slate-400"><?= htmlspecialchars($adminUser['email'] ?? '') ?></p>
        </div>
        <div class="flex items-center justify-between pt-1">
          <a href="../index.php" class="text-xs font-medium text-blue-700 hover:underline">&larr; Về Cửa hàng</a>
          <button type="button" data-dev-feature="Đăng xuất / Phân quyền Phiên đăng nhập Quản trị"
            class="text-xs font-medium text-slate-600 hover:text-slate-900 hover:underline">
            Đăng xuất
          </button>
        </div>
      </div>
    </aside>

    <main class="min-w-0 flex-1 px-6 py-8 lg:px-10 lg:py-10">

