<?php

/**
 * TẦNG CỬA HÀNG: Drawer Giỏ hàng (store/includes/cart_drawer.php)
 * Tích hợp Fetch API tương tác hai chiều thời gian thực (Add, Update, Remove, Selective Checkout)
 */

$isLoggedIn = isUserLoggedIn();
$cartTotalQty = 0;
$cartSubtotalAmount = 0;
foreach ($cartItems as $ci) {
    $cartTotalQty += (int)$ci['so_luong_mua'];
    $cartSubtotalAmount += (float)$ci['thanh_tien'];
}
$tierMultiplier = (float)($loyaltyProfile['he_so_tich_diem'] ?? 1.0);
$expectedCartPoints = (int)floor(($cartSubtotalAmount / POINTS_PER_VND) * $tierMultiplier);
?>
  <div id="cartDrawer" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="cartTitle" aria-hidden="true">
    <div id="cartBackdrop" class="drawer-backdrop absolute inset-0 bg-charcoal/30 opacity-0 pointer-events-none transition-opacity duration-300"></div>
    <aside class="drawer-panel absolute right-0 top-0 flex h-full w-full max-w-md translate-x-full flex-col bg-cream shadow-xl transition-transform duration-300 ease-out">
      <div class="flex items-center justify-between border-b border-stone-200 px-6 py-5">
        <div>
          <h2 id="cartTitle" class="text-lg font-semibold">
            Giỏ hàng của <?= htmlspecialchars($isLoggedIn ? ($loyaltyProfile['ho_ten'] ?? 'bạn') : 'bạn') ?>
            <span id="cartCountText" class="tabular-nums text-sm font-normal text-muted">(<?= $isLoggedIn ? $cartTotalQty : 0 ?>)</span>
          </h2>
          <p class="mt-0.5 text-xs text-muted"><?= $isLoggedIn ? 'Đồng bộ trực tuyến với giỏ hàng của bạn' : 'Đăng nhập để mua sắm và tích điểm' ?></p>
        </div>
        <button type="button" id="cartCloseBtn" aria-label="Đóng giỏ hàng" class="tap-feedback rounded-full p-2 text-charcoal hover:bg-stone-100">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
          </svg>
        </button>
      </div>

      <!-- 1 Thanh điều khiển duy nhất ở đầu Drawer giỏ hàng -->
      <div id="drawerToolbar" class="<?= (!$isLoggedIn || empty($cartItems)) ? 'hidden' : 'flex' ?> items-center justify-between px-6 py-2.5 border-b border-stone-200 bg-stone-50/70">
        <label class="flex items-center gap-2 cursor-pointer select-none text-xs font-semibold text-charcoal">
          <input type="checkbox" id="drawerSelectAll" class="h-4 w-4 rounded border-stone-300 accent-accent cursor-pointer" checked />
          <span>Chọn mua tất cả (<span id="drawerSelectedCount" class="tabular-nums font-bold text-accent">0 đã chọn</span>)</span>
        </label>
        <button type="button" id="drawerClearCartBtn" class="tap-feedback inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium text-red-600 hover:text-red-700 hover:bg-red-50 transition border border-transparent hover:border-red-200">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Xóa tất cả
        </button>
      </div>

      <div id="cartItems" class="flex-1 space-y-4 overflow-y-auto px-6 py-4">
        <?php if (!$isLoggedIn): ?>
          <!-- Khách vãng lai: Hiển thị thông báo mời đăng nhập -->
          <div class="py-12 text-center" id="cartGuestState">
            <svg class="mx-auto h-12 w-12 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
              <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12z" stroke-linejoin="round" />
              <path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6" stroke-linecap="round" />
            </svg>
            <p class="mt-4 text-base font-medium text-charcoal">Chưa đăng nhập</p>
            <p class="mt-1 text-xs text-muted">Vui lòng đăng nhập hoặc đăng ký để xem và quản lý giỏ hàng của bạn.</p>
            <div class="mt-5 flex flex-col gap-2.5 px-4">
              <a href="login.php" class="tap-feedback rounded-full bg-charcoal px-5 py-2.5 text-sm font-medium text-white hover:bg-accent transition text-center">
                Đăng nhập
              </a>
              <a href="register.php" class="tap-feedback rounded-full border border-stone-300 px-5 py-2.5 text-sm font-medium text-charcoal hover:border-charcoal hover:bg-stone-50 transition text-center">
                Đăng ký ngay
              </a>
            </div>
          </div>
        <?php elseif (empty($cartItems)): ?>
          <div class="py-16 text-center" id="cartEmptyState">
            <svg class="mx-auto h-12 w-12 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
              <path d="M6 7h12l-1 13H7L6 7Z" stroke-linejoin="round" /><path d="M9 7V5a3 3 0 0 1 6 0v2" stroke-linecap="round" />
            </svg>
            <p class="mt-4 text-base font-medium text-charcoal">Giỏ hàng của bạn đang trống</p>
            <p class="mt-1 text-xs text-muted">Khách hàng <strong><?= htmlspecialchars($loyaltyProfile['ho_ten'] ?? '') ?></strong> chưa có sản phẩm nào trong giỏ hàng.</p>
          </div>
        <?php else: ?>
          <?php foreach ($cartItems as $item): ?>
            <div class="flex gap-3 rounded-xl bg-white p-3.5 shadow-sm transition-all" data-cart-row="<?= (int)$item['ma_bt'] ?>" data-ma-gh="<?= (int)$item['ma_gh'] ?>">
              <!-- Checkbox chọn sản phẩm -->
              <div class="flex items-center pt-1">
                <input type="checkbox"
                  class="drawer-item-check h-4 w-4 rounded border-stone-300 accent-accent cursor-pointer"
                  data-ma-gh="<?= (int)$item['ma_gh'] ?>"
                  data-ma-bt="<?= (int)$item['ma_bt'] ?>"
                  data-thanh-tien="<?= (float)$item['thanh_tien'] ?>"
                  data-so-luong="<?= (int)$item['so_luong_mua'] ?>"
                  checked />
              </div>
              <a href="index.php?page=product&id=<?= (int)$item['ma_sp'] ?>" class="h-28 w-20 shrink-0 overflow-hidden rounded-lg bg-stone-100">
                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['ten_sp']) ?>" class="h-full w-full object-cover" />
              </a>
              <div class="flex flex-1 flex-col justify-between">
                <div>
                  <a href="index.php?page=product&id=<?= (int)$item['ma_sp'] ?>" class="text-sm font-medium leading-snug hover:text-accent">
                    <?= htmlspecialchars($item['ten_sp']) ?>
                  </a>
                  <p class="text-xs text-muted">
                    Màu: <?= htmlspecialchars($item['mau_sac']) ?> &middot; Size: <?= htmlspecialchars($item['kich_co']) ?>
                  </p>
                  <p class="mt-1 tabular-nums text-sm font-semibold text-charcoal"><?= formatVND($item['gia_ban_rieng']) ?></p>
                </div>
                <div class="mt-2 flex items-center justify-between">
                  <div class="inline-flex items-center rounded-full border border-stone-200 bg-cream">
                    <button type="button"
                      data-cart-action="minus"
                      data-ma-bt="<?= (int)$item['ma_bt'] ?>"
                      data-qty="<?= (int)$item['so_luong_mua'] ?>"
                      aria-label="Giảm số lượng"
                      class="tap-feedback h-7 w-7 rounded-full text-sm hover:bg-stone-200">-</button>
                    <span class="tabular-nums w-8 text-center text-xs font-semibold"><?= (int)$item['so_luong_mua'] ?></span>
                    <button type="button"
                      data-cart-action="plus"
                      data-ma-bt="<?= (int)$item['ma_bt'] ?>"
                      data-qty="<?= (int)$item['so_luong_mua'] ?>"
                      aria-label="Tăng số lượng"
                      class="tap-feedback h-7 w-7 rounded-full text-sm hover:bg-stone-200">+</button>
                  </div>
                  <button type="button"
                    data-cart-action="remove"
                    data-ma-bt="<?= (int)$item['ma_bt'] ?>"
                    class="text-xs text-muted underline-offset-4 transition hover:text-red-700 hover:underline">
                    Xóa
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="space-y-4 border-t border-stone-200 bg-white px-6 py-5">
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted">Tạm tính đã chọn (<span id="cartFooterCount"><?= $cartTotalQty ?></span>)</span>
          <span id="cartSubtotal" class="tabular-nums text-base font-semibold text-charcoal"><?= formatVND($cartSubtotalAmount) ?></span>
        </div>
        <p id="cartPoints" class="text-xs text-olive <?= $expectedCartPoints > 0 ? '' : 'hidden' ?>">
          Dự kiến tích lũy <strong class="tabular-nums font-semibold">+<?= $expectedCartPoints ?> điểm</strong> (<?= htmlspecialchars($loyaltyProfile['ten_hang'] ?? 'Hạng Đồng') ?> x<?= number_format($tierMultiplier, 1) ?>)
        </p>
        <div class="grid grid-cols-2 gap-3">
          <a href="index.php?page=checkout" id="drawerCheckoutLink"
            class="tap-feedback flex items-center justify-center rounded-full bg-charcoal px-4 py-3.5 text-center text-sm font-medium text-white hover:bg-accent">
            Xem trang Thanh toán
          </a>
          <button type="button" id="cartRefreshBtn"
            class="tap-feedback flex items-center justify-center gap-1.5 rounded-full border border-stone-300 px-4 py-3.5 text-xs font-medium text-charcoal hover:border-charcoal hover:bg-stone-50">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5" /></svg>
            Làm mới
          </button>
        </div>
      </div>
    </aside>
  </div>

  <script>
    (() => {
      function escapeHtml(str) {
        if (!str) return '';
        return String(str)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function formatVnd(val) {
        return Math.round(Number(val) || 0).toLocaleString('vi-VN') + 'đ';
      }

      // ---- Selective Checkout Logic ----
      // Lưu state tích chọn: Set<ma_gh>
      window._drawerSelectedMaGh = new Set();

      function getDrawerCheckboxes() {
        return Array.from(document.querySelectorAll('.drawer-item-check'));
      }

      function updateDrawerSelection() {
        const checks = getDrawerCheckboxes();
        const selectAll = document.getElementById('drawerSelectAll');
        const countEl = document.getElementById('drawerSelectedCount');
        const subtotalEl = document.getElementById('cartSubtotal');
        const footerCountEl = document.getElementById('cartFooterCount');
        const ptsEl = document.getElementById('cartPoints');
        const ckLink = document.getElementById('drawerCheckoutLink');

        window._drawerSelectedMaGh.clear();
        let selectedCount = 0;
        let selectedSubtotal = 0;
        let selectedQty = 0;

        checks.forEach((cb) => {
          if (cb.checked) {
            const maGh = Number(cb.dataset.maGh);
            if (maGh) window._drawerSelectedMaGh.add(maGh);
            selectedCount++;
            selectedSubtotal += Number(cb.dataset.thanhTien || 0);
            selectedQty += Number(cb.dataset.soLuong || 0);
          }
        });

        const selectedIds = Array.from(window._drawerSelectedMaGh);

        // Lưu vào sessionStorage để đồng bộ sang trang checkout
        try {
          sessionStorage.setItem('selected_cart_ids', JSON.stringify(selectedIds));
        } catch (e) {}

        // Cập nhật link nút Xem trang Thanh toán kèm query items
        if (ckLink) {
          if (selectedIds.length > 0) {
            ckLink.href = 'index.php?page=checkout&items=' + selectedIds.join(',');
            ckLink.classList.remove('opacity-50');
          } else {
            ckLink.href = 'index.php?page=checkout';
            ckLink.classList.add('opacity-50');
          }
        }

        if (countEl) countEl.textContent = `${selectedCount} đã chọn`;
        if (subtotalEl) subtotalEl.textContent = formatVnd(selectedSubtotal);
        if (footerCountEl) footerCountEl.textContent = String(selectedQty);

        // Cập nhật điểm dự kiến (tính từ subtotal đã chọn)
        const tierMul = <?= json_encode($tierMultiplier) ?>;
        const expPts = Math.floor((selectedSubtotal / <?= POINTS_PER_VND ?>) * tierMul);
        if (ptsEl) {
          if (expPts > 0) {
            ptsEl.innerHTML = `Dự kiến tích lũy <strong class="tabular-nums font-semibold">+${expPts} điểm</strong>`;
            ptsEl.classList.remove('hidden');
          } else {
            ptsEl.classList.add('hidden');
          }
        }

        // Select-all trạng thái
        if (selectAll) {
          selectAll.checked = checks.length > 0 && selectedCount === checks.length;
          selectAll.indeterminate = selectedCount > 0 && selectedCount < checks.length;
        }
      }

      function bindDrawerCheckboxEvents() {
        document.getElementById('drawerSelectAll')?.addEventListener('change', (e) => {
          getDrawerCheckboxes().forEach((cb) => { cb.checked = e.target.checked; });
          updateDrawerSelection();
        });

        document.addEventListener('change', (e) => {
          if (e.target.classList.contains('drawer-item-check')) {
            updateDrawerSelection();
          }
        });

        // Xử lý nút Xóa tất cả sản phẩm trong giỏ hàng có xác nhận
        document.getElementById('drawerClearCartBtn')?.addEventListener('click', async () => {
          if (!confirm('Bạn có chắc chắn muốn xóa toàn bộ sản phẩm trong giỏ hàng không?')) {
            return;
          }
          try {
            const res = await window.cartAPI.clear();
            if (res.success) {
              window.showStoreToast?.('Đã xóa toàn bộ sản phẩm trong giỏ hàng.');
            } else {
              window.showStoreToast?.(res.message || 'Không thể xóa giỏ hàng.');
            }
          } catch (err) {
            console.error(err);
            window.showStoreToast?.('Lỗi kết nối khi xóa giỏ hàng.');
          }
        });

        // Chặn chuyển trang checkout nếu không có sản phẩm nào được chọn
        document.getElementById('drawerCheckoutLink')?.addEventListener('click', (e) => {
          if (window._drawerSelectedMaGh.size === 0) {
            e.preventDefault();
            window.showStoreToast?.('Vui lòng tích chọn ít nhất 1 sản phẩm để thanh toán!');
          }
        });
      }

      // Khởi tạo lần đầu
      bindDrawerCheckboxEvents();
      // Chọn tất cả mặc định rồi tính
      setTimeout(() => {
        getDrawerCheckboxes().forEach((cb) => { cb.checked = true; });
        updateDrawerSelection();
      }, 0);

      // ---- renderCartDrawer ----
      window.renderCartDrawer = function(summary) {
        if (!summary) return;
        const totalQty = Number(summary.total_qty || 0);
        const items = summary.items || [];

        // 1. Cập nhật Badge trên Header
        const badge = document.getElementById('cartBadge');
        if (badge) {
          badge.textContent = String(totalQty);
          if (totalQty > 0) {
            badge.classList.remove('hidden');
            badge.classList.add('flex');
          } else {
            badge.classList.remove('flex');
            badge.classList.add('hidden');
          }
        }

        // 2. Cập nhật text số lượng ở tiêu đề drawer
        const countText = document.getElementById('cartCountText');
        if (countText) countText.textContent = `(${totalQty})`;

        // 3. Quản lý hiển thị thanh công cụ duy nhất
        const toolbar = document.getElementById('drawerToolbar');
        if (toolbar) {
          if (items.length > 0) {
            toolbar.classList.remove('hidden');
            toolbar.classList.add('flex');
          } else {
            toolbar.classList.remove('flex');
            toolbar.classList.add('hidden');
          }
        }

        // Dọn sạch nếu có drawer-select-bar trùng lặp
        document.querySelectorAll('.drawer-select-bar').forEach(el => el.remove());

        // 4. Render danh sách mặt hàng
        const container = document.getElementById('cartItems');
        if (!container) return;

        if (items.length === 0) {
          container.innerHTML = `
            <div class="py-16 text-center" id="cartEmptyState">
              <svg class="mx-auto h-12 w-12 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                <path d="M6 7h12l-1 13H7L6 7Z" stroke-linejoin="round" /><path d="M9 7V5a3 3 0 0 1 6 0v2" stroke-linecap="round" />
              </svg>
              <p class="mt-4 text-base font-medium text-charcoal">Giỏ hàng của bạn đang trống</p>
              <p class="mt-1 text-xs text-muted">Hãy chọn thêm sản phẩm vào giỏ nhé!</p>
            </div>
          `;
          // Reset totals
          const subtotalEl = document.getElementById('cartSubtotal');
          if (subtotalEl) subtotalEl.textContent = '0đ';
          const footerCountEl = document.getElementById('cartFooterCount');
          if (footerCountEl) footerCountEl.textContent = '0';
          const countEl = document.getElementById('drawerSelectedCount');
          if (countEl) countEl.textContent = '0 đã chọn';
          window._drawerSelectedMaGh.clear();
          try { sessionStorage.removeItem('selected_cart_ids'); } catch (e) {}
          return;
        }

        // Nhớ trạng thái đã chọn cũ
        const prevSelected = new Set(window._drawerSelectedMaGh);

        let html = '';
        items.forEach((item) => {
          const wasChecked = prevSelected.size === 0 || prevSelected.has(Number(item.ma_gh));
          html += `
            <div class="flex gap-3 rounded-xl bg-white p-3.5 shadow-sm transition-all" data-cart-row="${item.ma_bt}" data-ma-gh="${item.ma_gh}">
              <div class="flex items-center pt-1">
                <input type="checkbox"
                  class="drawer-item-check h-4 w-4 rounded border-stone-300 accent-accent cursor-pointer"
                  data-ma-gh="${item.ma_gh}"
                  data-ma-bt="${item.ma_bt}"
                  data-thanh-tien="${item.thanh_tien}"
                  data-so-luong="${item.so_luong_mua}"
                  ${wasChecked ? 'checked' : ''} />
              </div>
              <a href="index.php?page=product&id=${item.ma_sp}" class="h-28 w-20 shrink-0 overflow-hidden rounded-lg bg-stone-100">
                <img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.ten_sp)}" class="h-full w-full object-cover" />
              </a>
              <div class="flex flex-1 flex-col justify-between">
                <div>
                  <a href="index.php?page=product&id=${item.ma_sp}" class="text-sm font-medium leading-snug hover:text-accent">
                    ${escapeHtml(item.ten_sp)}
                  </a>
                  <p class="text-xs text-muted">
                    SKU: <span class="font-mono text-charcoal">${escapeHtml(item.ma_sku)}</span>
                  </p>
                  <p class="text-xs text-muted">
                    Màu: ${escapeHtml(item.mau_sac)} &middot; Size: ${escapeHtml(item.kich_co)}
                  </p>
                  <p class="mt-1 tabular-nums text-sm font-semibold text-charcoal">${item.formatted_price || formatVnd(item.gia_ban_rieng)}</p>
                </div>
                <div class="mt-2 flex items-center justify-between">
                  <div class="inline-flex items-center rounded-full border border-stone-200 bg-cream">
                    <button type="button"
                      data-cart-action="minus"
                      data-ma-bt="${item.ma_bt}"
                      data-qty="${item.so_luong_mua}"
                      aria-label="Giảm số lượng"
                      class="tap-feedback h-7 w-7 rounded-full text-sm hover:bg-stone-200">-</button>
                    <span class="tabular-nums w-8 text-center text-xs font-semibold">${item.so_luong_mua}</span>
                    <button type="button"
                      data-cart-action="plus"
                      data-ma-bt="${item.ma_bt}"
                      data-qty="${item.so_luong_mua}"
                      aria-label="Tăng số lượng"
                      class="tap-feedback h-7 w-7 rounded-full text-sm hover:bg-stone-200">+</button>
                  </div>
                  <button type="button"
                    data-cart-action="remove"
                    data-ma-bt="${item.ma_bt}"
                    class="text-xs text-muted underline-offset-4 transition hover:text-red-700 hover:underline">
                    Xóa
                  </button>
                </div>
              </div>
            </div>
          `;
        });
        container.innerHTML = html;

        updateDrawerSelection();
      };

      // Đối tượng Cart API Client (Fetch API Async/Await)
      window.cartAPI = {
        async get() {
          const res = await fetch('api/cart.php?action=get');
          const json = await res.json();
          if (json.success && json.data) {
            window.renderCartDrawer(json.data);
          }
          return json;
        },

        async add(maBt, soLuong = 1) {
          const res = await fetch('api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', ma_bt: Number(maBt), so_luong_mua: Number(soLuong) })
          });
          const json = await res.json();
          if (json.success && json.data) {
            window.renderCartDrawer(json.data);
          }
          return json;
        },

        async update(maBt, soLuong) {
          const res = await fetch('api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', ma_bt: Number(maBt), so_luong_mua: Number(soLuong) })
          });
          const json = await res.json();
          if (json.success && json.data) {
            window.renderCartDrawer(json.data);
          }
          return json;
        },

        async remove(maBt) {
          const res = await fetch('api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove', ma_bt: Number(maBt) })
          });
          const json = await res.json();
          if (json.success && json.data) {
            window.renderCartDrawer(json.data);
          }
          return json;
        },

        async clear() {
          const res = await fetch('api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clear' })
          });
          const json = await res.json();
          if (json.success && json.data) {
            window.renderCartDrawer(json.data);
          }
          return json;
        }
      };

      // Bắt sự kiện click các nút tăng, giảm, xóa trong Cart Drawer
      document.addEventListener('click', async (e) => {
        const actionBtn = e.target.closest('[data-cart-action]');
        if (!actionBtn) return;

        const action = actionBtn.dataset.cartAction;
        const maBt = Number(actionBtn.dataset.maBt || 0);
        const currentQty = Number(actionBtn.dataset.qty || 1);

        if (!maBt) return;
        actionBtn.disabled = true;

        try {
          if (action === 'plus') {
            const res = await window.cartAPI.update(maBt, currentQty + 1);
            if (!res.success) {
              if (res.require_login && window.openAuthModal) {
                window.openAuthModal('login');
              } else {
                window.showStoreToast?.(res.message || 'Không thể tăng số lượng');
              }
            }
          } else if (action === 'minus') {
            const res = await window.cartAPI.update(maBt, currentQty - 1);
            if (!res.success) {
              if (res.require_login && window.openAuthModal) {
                window.openAuthModal('login');
              } else {
                window.showStoreToast?.(res.message || 'Không thể giảm số lượng');
              }
            }
          } else if (action === 'remove') {
            const res = await window.cartAPI.remove(maBt);
            if (!res.success && res.require_login && window.openAuthModal) {
              window.openAuthModal('login');
            } else {
              window.showStoreToast?.(res.message || 'Đã xóa sản phẩm khỏi giỏ');
            }
          }
        } catch (err) {
          console.error('Cart interaction error:', err);
          window.showStoreToast?.('Lỗi kết nối khi cập nhật giỏ hàng');
        } finally {
          actionBtn.disabled = false;
        }
      });

      // Nút Làm mới giỏ hàng
      document.getElementById('cartRefreshBtn')?.addEventListener('click', async () => {
        try {
          await window.cartAPI.get();
          window.showStoreToast?.('Đã đồng bộ giỏ hàng mới nhất');
        } catch (err) {
          window.showStoreToast?.('Không thể làm mới giỏ hàng');
        }
      });
    })();
  </script>
