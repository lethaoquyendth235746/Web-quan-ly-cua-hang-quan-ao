<?php
/**
 * TẦNG CỬA HÀNG: Drawer Giỏ hàng (store/includes/cart_drawer.php)
 * Đổ dữ liệu trực tiếp từ bảng gh_gio_hang trong CSDL csdl_cua_hang_quan_ao
 */
$cartTotalQty = 0;
$cartSubtotalAmount = 0;
foreach ($cartItems as $ci) {
    $cartTotalQty += (int) $ci['so_luong_mua'];
    $cartSubtotalAmount += (float) $ci['thanh_tien'];
}
$tierMultiplier = (float) ($loyaltyProfile['he_so_tich_diem'] ?? 1.0);
$expectedCartPoints = (int) floor(($cartSubtotalAmount / POINTS_PER_VND) * $tierMultiplier);
?>
  <div id="cartDrawer" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="cartTitle" aria-hidden="true">
    <div id="cartBackdrop" class="drawer-backdrop absolute inset-0 bg-charcoal/30 opacity-0 pointer-events-none transition-opacity duration-300"></div>
    <aside class="drawer-panel absolute right-0 top-0 flex h-full w-full max-w-md translate-x-full flex-col bg-cream shadow-xl transition-transform duration-300 ease-out">
      <div class="flex items-center justify-between border-b border-stone-200 px-6 py-5">
        <div>
          <h2 id="cartTitle" class="text-lg font-semibold">
            Giỏ hàng của <?= htmlspecialchars($loyaltyProfile['ho_ten'] ?? 'bạn') ?>
            <span id="cartCountText" class="tabular-nums text-sm font-normal text-muted">(<?= $cartTotalQty ?>)</span>
          </h2>
          <p class="mt-0.5 text-xs text-muted">Dữ liệu thực tế từ bảng <code class="text-charcoal">gh_gio_hang</code></p>
        </div>
        <button type="button" id="cartCloseBtn" aria-label="Đóng giỏ hàng" class="tap-feedback rounded-full p-2 text-charcoal hover:bg-stone-100">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
          </svg>
        </button>
      </div>

      <div id="cartItems" class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
        <?php if (empty($cartItems)): ?>
          <div class="py-16 text-center">
            <p class="text-base font-medium text-charcoal">Giỏ hàng trong CSDL đang trống</p>
            <p class="mt-2 text-sm text-muted">Khách hàng <strong><?= htmlspecialchars($loyaltyProfile['ho_ten'] ?? '') ?></strong> hiện chưa có sản phẩm nào trong bảng <code>gh_gio_hang</code>.</p>
          </div>
        <?php else: ?>
          <?php foreach ($cartItems as $item): ?>
            <div class="flex gap-4 rounded-xl bg-white p-3.5 shadow-sm">
              <a href="index.php?page=product&id=<?= (int)$item['ma_sp'] ?>" class="h-28 w-20 shrink-0 overflow-hidden rounded-lg bg-stone-100">
                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['ten_sp']) ?>" class="h-full w-full object-cover" />
              </a>
              <div class="flex flex-1 flex-col justify-between">
                <div>
                  <a href="index.php?page=product&id=<?= (int)$item['ma_sp'] ?>" class="text-sm font-medium leading-snug hover:text-accent">
                    <?= htmlspecialchars($item['ten_sp']) ?>
                  </a>
                  <p class="mt-1 text-xs text-muted">
                    SKU: <span class="font-mono text-charcoal"><?= htmlspecialchars($item['ma_sku']) ?></span>
                  </p>
                  <p class="text-xs text-muted">
                    Màu: <?= htmlspecialchars($item['mau_sac']) ?> &middot; Size: <?= htmlspecialchars($item['kich_co']) ?>
                  </p>
                  <p class="mt-1 tabular-nums text-sm font-semibold text-charcoal"><?= formatVND($item['gia_ban_rieng']) ?></p>
                </div>
                <div class="mt-2 flex items-center justify-between">
                  <div class="inline-flex items-center rounded-full border border-stone-200 bg-cream">
                    <button type="button" data-dev-feature="Cập nhật giảm số lượng trong bảng gh_gio_hang" aria-label="Giảm số lượng" class="tap-feedback h-7 w-7 rounded-full text-sm hover:bg-stone-200">-</button>
                    <span class="tabular-nums w-8 text-center text-xs font-semibold"><?= (int)$item['so_luong_mua'] ?></span>
                    <button type="button" data-dev-feature="Cập nhật tăng số lượng trong bảng gh_gio_hang" aria-label="Tăng số lượng" class="tap-feedback h-7 w-7 rounded-full text-sm hover:bg-stone-200">+</button>
                  </div>
                  <button type="button" data-dev-feature="Xóa sản phẩm khỏi bảng gh_gio_hang" class="text-xs text-muted underline-offset-4 transition hover:text-red-700 hover:underline">
                    Xóa (Đang phát triển)
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="space-y-4 border-t border-stone-200 bg-white px-6 py-5">
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted">Tạm tính (<?= $cartTotalQty ?> sản phẩm)</span>
          <span id="cartSubtotal" class="tabular-nums text-base font-semibold text-charcoal"><?= formatVND($cartSubtotalAmount) ?></span>
        </div>
        <?php if ($expectedCartPoints > 0): ?>
          <p id="cartPoints" class="text-xs text-olive">
            Đơn hàng này dự kiến tích lũy <strong class="tabular-nums">+<?= $expectedCartPoints ?> điểm</strong> (<?= htmlspecialchars($loyaltyProfile['ten_hang'] ?? 'Hạng Đồng') ?> x<?= number_format($tierMultiplier, 1) ?>)
          </p>
        <?php endif; ?>
        <div class="grid grid-cols-2 gap-3">
          <a href="index.php?page=checkout"
            class="tap-feedback flex items-center justify-center rounded-full bg-charcoal px-4 py-3.5 text-center text-sm font-medium text-white hover:bg-accent">
            Xem trang Thanh toán
          </a>
          <button type="button" data-dev-feature="Đồng bộ Giỏ hàng Ajax (Thêm/Sửa/Xóa trực tiếp)"
            class="tap-feedback rounded-full border border-stone-300 px-4 py-3.5 text-xs font-medium text-muted hover:border-charcoal hover:text-charcoal">
            Ajax Cart (Đang phát triển)
          </button>
        </div>
      </div>
    </aside>
  </div>

