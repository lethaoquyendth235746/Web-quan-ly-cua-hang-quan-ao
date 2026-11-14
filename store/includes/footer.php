<?php
/**
 * TẦNG CỬA HÀNG: Footer & Script điều khiển tương tác chung (store/includes/footer.php)
 * Tuân thủ kỹ năng Animation Vocabulary (Origin-aware popover, Slide-in drawer, Toast feedback)
 */
?>
  </main>

  <footer class="border-t border-stone-200 bg-stone-100">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-8 text-sm text-muted sm:flex-row lg:px-8">
      <div>
        <p class="font-semibold tracking-[0.25em] text-charcoal">AURA STUDIO</p>
        <p class="mt-1 text-xs text-muted">Hệ thống Quản lý Cửa hàng Quần áo &amp; Khách hàng Thân thiết (CSDL: <code>csdl_cua_hang_quan_ao</code>)</p>
      </div>
      <div class="flex flex-wrap items-center gap-4">
        <a href="index.php?page=home" class="text-xs text-muted hover:text-charcoal hover:underline">Cửa hàng</a>
        <a href="index.php?page=loyalty" class="text-xs text-muted hover:text-charcoal hover:underline">Thẻ thành viên</a>
        <a href="index.php?page=checkout" class="text-xs text-muted hover:text-charcoal hover:underline">Thanh toán</a>
        <a href="admin/index.php" class="rounded-full bg-charcoal px-3.5 py-1.5 text-xs font-medium text-white transition hover:bg-accent">Phân hệ Quản trị &rarr;</a>
      </div>
    </div>
  </footer>

  <?php require __DIR__ . '/cart_drawer.php'; ?>
  <?php require __DIR__ . '/address_modal.php'; ?>
  <?php require __DIR__ . '/auth_modal.php'; ?>

  <!-- Toast thông báo trạng thái / Tính năng đang phát triển -->
  <div id="toast" role="status" aria-live="polite"
    class="pointer-events-none fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 translate-y-4 rounded-full bg-charcoal px-5 py-3 text-sm text-white opacity-0 shadow-lg transition duration-300"></div>

  <script>
    (() => {
      const $ = (id) => document.getElementById(id);
      let toastTimer = null;

      window.showStoreToast = function(message) {
        const toast = $('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('opacity-0', 'translate-y-4');
        toast.classList.add('opacity-100', 'translate-y-0');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
          toast.classList.add('opacity-0', 'translate-y-4');
          toast.classList.remove('opacity-100', 'translate-y-0');
        }, 2800);
      };

      function togglePopover(panelId, btnId, forceState = null) {
        const panel = $(panelId);
        const btn = $(btnId);
        if (!panel || !btn) return;
        const isHidden = panel.classList.contains('popover-hidden');
        const nextOpen = forceState !== null ? forceState : isHidden;
        panel.classList.toggle('popover-hidden', !nextOpen);
        panel.classList.toggle('popover-visible', nextOpen);
        btn.setAttribute('aria-expanded', String(nextOpen));
      }

      window.openCartDrawer = function() {
        document.body.classList.add('drawer-open');
        $('cartDrawer')?.setAttribute('aria-hidden', 'false');
      };

      window.closeCartDrawer = function() {
        document.body.classList.remove('drawer-open');
        $('cartDrawer')?.setAttribute('aria-hidden', 'true');
      };

      document.addEventListener('click', (event) => {
        const target = event.target;

        // Popover Hạng thành viên
        if (target.closest('#tierBtn')) {
          togglePopover('userSwitchPanel', 'userSwitchBtn', false);
          togglePopover('loyaltyPanel', 'tierBtn');
          return;
        }
        if (target.closest('[data-action="open-loyalty"]')) {
          togglePopover('loyaltyPanel', 'tierBtn', true);
          return;
        }
        if (!target.closest('#loyaltyPanel')) {
          togglePopover('loyaltyPanel', 'tierBtn', false);
        }

        // Popover Chuyển đổi khách hàng
        if (target.closest('#userSwitchBtn')) {
          togglePopover('loyaltyPanel', 'tierBtn', false);
          togglePopover('userSwitchPanel', 'userSwitchBtn');
          return;
        }
        if (!target.closest('#userSwitchPanel')) {
          togglePopover('userSwitchPanel', 'userSwitchBtn', false);
        }

        // Mở / Đóng Drawer Giỏ hàng
        if (target.closest('#cartOpenBtn')) {
          window.openCartDrawer();
          return;
        }
        if (target.closest('#cartCloseBtn') || target.closest('#cartBackdrop')) {
          window.closeCartDrawer();
          return;
        }

        // Nút tính năng đang phát triển
        const devBtn = target.closest('[data-dev-feature]');
        if (devBtn) {
          event.preventDefault();
          const featureName = devBtn.dataset.devFeature || 'Chức năng này';
          window.showStoreToast(`🚧 Tính năng "${featureName}" hiện đang phát triển!`);
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        togglePopover('loyaltyPanel', 'tierBtn', false);
        togglePopover('userSwitchPanel', 'userSwitchBtn', false);
        window.closeCartDrawer();
      });

      window.addEventListener('scroll', () => {
        $('siteHeader')?.classList.toggle('shadow-sm', window.scrollY > 8);
      }, { passive: true });
    })();
  </script>
</body>
</html>

