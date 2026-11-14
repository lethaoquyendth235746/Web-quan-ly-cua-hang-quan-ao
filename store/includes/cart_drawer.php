<?php
/**
 * TẦNG CỬA HÀNG: Drawer Giỏ hàng (store/includes/cart_drawer.php)
 * Đổ dữ liệu thực tế từ bảng gh_gio_hang trong CSDL csdl_cua_hang_quan_ao
 * Tích hợp Fetch API tương tác hai chiều thời gian thực (Add, Update, Remove)
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
          <p class="mt-0.5 text-xs text-muted">Đồng bộ trực tuyến với bảng <code class="text-charcoal font-mono">gh_gio_hang</code></p>
        </div>
        <button type="button" id="cartCloseBtn" aria-label="Đóng giỏ hàng" class="tap-feedback rounded-full p-2 text-charcoal hover:bg-stone-100">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
          </svg>
        </button>
      </div>

      <div id="cartItems" class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
        <?php if (empty($cartItems)): ?>
          <div class="py-16 text-center" id="cartEmptyState">
            <svg class="mx-auto h-12 w-12 text-stone-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
              <path d="M6 7h12l-1 13H7L6 7Z" stroke-linejoin="round" /><path d="M9 7V5a3 3 0 0 1 6 0v2" stroke-linecap="round" />
            </svg>
            <p class="mt-4 text-base font-medium text-charcoal">Giỏ hàng của bạn đang trống</p>
            <p class="mt-1 text-xs text-muted">Khách hàng <strong><?= htmlspecialchars($loyaltyProfile['ho_ten'] ?? '') ?></strong> chưa có sản phẩm nào trong bảng <code>gh_gio_hang</code>.</p>
          </div>
        <?php else: ?>
          <?php foreach ($cartItems as $item): ?>
            <div class="flex gap-4 rounded-xl bg-white p-3.5 shadow-sm transition-all" data-cart-row="<?= (int)$item['ma_bt'] ?>">
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
          <span class="text-muted">Tạm tính (<span id="cartFooterCount"><?= $cartTotalQty ?></span> sản phẩm)</span>
          <span id="cartSubtotal" class="tabular-nums text-base font-semibold text-charcoal"><?= formatVND($cartSubtotalAmount) ?></span>
        </div>
        <p id="cartPoints" class="text-xs text-olive <?= $expectedCartPoints > 0 ? '' : 'hidden' ?>">
          Đơn hàng này dự kiến tích lũy <strong class="tabular-nums font-semibold">+<?= $expectedCartPoints ?> điểm</strong> (<?= htmlspecialchars($loyaltyProfile['ten_hang'] ?? 'Hạng Đồng') ?> x<?= number_format($tierMultiplier, 1) ?>)
        </p>
        <div class="grid grid-cols-2 gap-3">
          <a href="index.php?page=checkout"
            class="tap-feedback flex items-center justify-center rounded-full bg-charcoal px-4 py-3.5 text-center text-sm font-medium text-white hover:bg-accent">
            Xem trang Thanh toán
          </a>
          <button type="button" id="cartRefreshBtn"
            class="tap-feedback flex items-center justify-center gap-1.5 rounded-full border border-stone-300 px-4 py-3.5 text-xs font-medium text-charcoal hover:border-charcoal hover:bg-stone-50">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5" /></svg>
            Làm mới giỏ hàng
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

      window.renderCartDrawer = function(summary) {
        if (!summary) return;
        const totalQty = Number(summary.total_qty || 0);
        const subtotal = Number(summary.subtotal || 0);
        const expectedPts = Number(summary.expected_points || 0);
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

        // 2. Cập nhật text số lượng ở tiêu đề và footer drawer
        const countText = document.getElementById('cartCountText');
        if (countText) countText.textContent = `(${totalQty})`;

        const footerCount = document.getElementById('cartFooterCount');
        if (footerCount) footerCount.textContent = String(totalQty);

        // 3. Cập nhật tạm tính & điểm dự kiến
        const subtotalEl = document.getElementById('cartSubtotal');
        if (subtotalEl) subtotalEl.textContent = summary.formatted_subtotal || formatVnd(subtotal);

        const ptsEl = document.getElementById('cartPoints');
        if (ptsEl) {
          if (expectedPts > 0) {
            ptsEl.innerHTML = `Đơn hàng này dự kiến tích lũy <strong class="tabular-nums font-semibold">+${expectedPts} điểm</strong> (${escapeHtml(summary.tier_name || 'Hạng Đồng')} x${Number(summary.tier_multiplier || 1).toFixed(1)})`;
            ptsEl.classList.remove('hidden');
          } else {
            ptsEl.classList.add('hidden');
          }
        }

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
          return;
        }

        let html = '';
        items.forEach((item) => {
          html += `
            <div class="flex gap-4 rounded-xl bg-white p-3.5 shadow-sm transition-all" data-cart-row="${item.ma_bt}">
              <a href="index.php?page=product&id=${item.ma_sp}" class="h-28 w-20 shrink-0 overflow-hidden rounded-lg bg-stone-100">
                <img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.ten_sp)}" class="h-full w-full object-cover" />
              </a>
              <div class="flex flex-1 flex-col justify-between">
                <div>
                  <a href="index.php?page=product&id=${item.ma_sp}" class="text-sm font-medium leading-snug hover:text-accent">
                    ${escapeHtml(item.ten_sp)}
                  </a>
                  <p class="mt-1 text-xs text-muted">
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
              window.showStoreToast?.(res.message || 'Không thể tăng số lượng');
            }
          } else if (action === 'minus') {
            const res = await window.cartAPI.update(maBt, currentQty - 1);
            if (!res.success) {
              window.showStoreToast?.(res.message || 'Không thể giảm số lượng');
            }
          } else if (action === 'remove') {
            const res = await window.cartAPI.remove(maBt);
            window.showStoreToast?.(res.message || 'Đã xóa sản phẩm khỏi giỏ');
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
          window.showStoreToast?.('Đã đồng bộ giỏ hàng mới nhất từ CSDL');
        } catch (err) {
          window.showStoreToast?.('Không thể làm mới giỏ hàng');
        }
      });
    })();
  </script>
