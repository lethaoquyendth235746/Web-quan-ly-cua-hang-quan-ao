<?php

/**
 * TẦNG QUẢN TRỊ: Footer & Script điều khiển tương tác chung (admin/includes/footer.php)
 */
?>
    </main>
  </div>

  <!-- Toast thông báo Quản trị -->
  <div id="adminToast" role="status" aria-live="polite"
    class="pointer-events-none fixed bottom-6 right-6 z-[60] translate-y-4 rounded-lg bg-slate-900 px-4 py-3 text-sm text-white opacity-0 shadow-lg transition duration-300"></div>

  <script>
    (() => {
      const toast = document.getElementById('adminToast');
      let toastTimer = null;

      window.showAdminToast = function(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('translate-y-4', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
          toast.classList.add('translate-y-4', 'opacity-0');
          toast.classList.remove('translate-y-0', 'opacity-100');
        }, 2800);
      };

      document.addEventListener('click', (event) => {
        const devBtn = event.target.closest('[data-dev-feature]');
        if (devBtn) {
          event.preventDefault();
          const feature = devBtn.dataset.devFeature || 'Chức năng này';
          window.showAdminToast(`🚧 Tính năng "${feature}" hiện đang phát triển!`);
        }
      });
    })();
  </script>

  <?php if (isset($_SESSION['admin_flash'])): ?>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.showAdminToast === 'function') {
          window.showAdminToast(<?= json_encode($_SESSION['admin_flash']['message'], JSON_UNESCAPED_UNICODE) ?>);
        }
      });
    </script>
    <?php unset($_SESSION['admin_flash']); ?>
  <?php endif; ?>
</body>
</html>

