<?php
/**
 * MODAL ĐĂNG NHẬP & ĐĂNG KÝ THÀNH VIÊN (store/includes/auth_modal.php)
 * Tích hợp AJAX trực tiếp tại Header/Footer, cho phép khách hàng đăng nhập/đăng ký
 * mà không bị gián đoạn trải nghiệm mua sắm
 */
?>
<!-- Auth Modal Backdrop -->
<div id="authModal" role="dialog" aria-modal="true" aria-labelledby="authModalTitle"
  class="fixed inset-0 z-[70] hidden items-center justify-center p-4 bg-charcoal/60 backdrop-blur-sm transition-opacity duration-300">
  
  <div class="relative w-full max-w-md rounded-2xl bg-white p-6 sm:p-8 shadow-2xl transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between border-b border-stone-200 pb-3">
      <div class="flex gap-4">
        <button type="button" id="modalTabLoginBtn" onclick="switchModalAuthTab('login')"
          class="pb-2 text-sm font-semibold border-b-2 border-charcoal text-charcoal transition">
          Đăng nhập
        </button>
        <button type="button" id="modalTabRegisterBtn" onclick="switchModalAuthTab('register')"
          class="pb-2 text-sm font-semibold border-b-2 border-transparent text-muted hover:text-charcoal transition">
          Đăng ký thành viên
        </button>
      </div>
      <button type="button" onclick="closeAuthModal()" class="rounded-full p-1.5 text-stone-400 hover:bg-stone-100 hover:text-charcoal transition">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Alert thông báo lỗi Modal -->
    <div id="authModalError" class="hidden mt-4 text-xs text-red-600 bg-red-50 p-3 rounded-xl border border-red-200"></div>

    <!-- TAB 1: MODAL ĐĂNG NHẬP -->
    <div id="modalLoginSection" class="mt-4">
      <form id="modalLoginForm" onsubmit="handleModalLogin(event)" class="space-y-4">
        <div>
          <label for="modalLoginId" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
            Tên đăng nhập / Email <span class="text-red-500">*</span>
          </label>
          <input type="text" id="modalLoginId" name="identifier" required
            placeholder="VD: admin_duc, neangra hoặc email..."
            class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label for="modalLoginPass" class="block text-xs font-semibold uppercase tracking-wider text-charcoal">
              Mật khẩu <span class="text-red-500">*</span>
            </label>
            <span class="text-[11px] text-muted">Pass mẫu: <code>123456</code></span>
          </div>
          <input type="password" id="modalLoginPass" name="password" required
            placeholder="Nhập mật khẩu..."
            class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
        </div>

        <button type="submit" id="modalLoginSubmitBtn"
          class="tap-feedback w-full rounded-xl bg-charcoal py-2.5 text-xs font-semibold text-white hover:bg-accent transition shadow-sm">
          Đăng nhập ngay
        </button>
      </form>

      <!-- Quick test pills in modal -->
      <div class="mt-4 pt-3 border-t border-stone-100">
        <p class="text-[11px] font-semibold text-muted uppercase tracking-wider mb-2">Đăng nhập nhanh để test:</p>
        <div class="grid grid-cols-2 gap-2 text-xs">
          <button type="button" onclick="modalQuickFill('admin_duc', '123456')" class="p-2 rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-900 font-medium text-left truncate">
            👑 Admin Đức
          </button>
          <button type="button" onclick="modalQuickFill('staff_quyen', '123456')" class="p-2 rounded-lg border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-900 font-medium text-left truncate">
            💼 NV Quyên
          </button>
          <button type="button" onclick="modalQuickFill('neangra', '123456')" class="p-2 rounded-lg border border-amber-200 bg-amber-50 hover:bg-amber-100 text-amber-900 font-medium text-left truncate">
            🥉 Neàng Ra (Đồng)
          </button>
          <button type="button" onclick="modalQuickFill('hoangnam_lx', '123456')" class="p-2 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-800 font-medium text-left truncate">
            🥈 Hoàng Nam (Bạc)
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 2: MODAL ĐĂNG KÝ -->
    <div id="modalRegisterSection" class="mt-4 hidden">
      <div class="mb-3 rounded-lg bg-amber-50 p-2.5 border border-amber-200 text-xs text-amber-800">
        ✨ Tự động kích hoạt <strong>Thẻ Hạng Đồng</strong> (Mã thẻ: <code>THE-AGU-...</code>)
      </div>

      <form id="modalRegisterForm" onsubmit="handleModalRegister(event)" class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-charcoal mb-0.5">Họ và tên <span class="text-red-500">*</span></label>
          <input type="text" name="ho_ten" required placeholder="Họ và tên khách hàng"
            class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-xs text-charcoal focus:border-accent focus:outline-none" />
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-charcoal mb-0.5">Tên đăng nhập <span class="text-red-500">*</span></label>
            <input type="text" name="ten_dang_nhap" required placeholder="van_an99"
              class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-xs text-charcoal focus:border-accent focus:outline-none" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-charcoal mb-0.5">Số điện thoại <span class="text-red-500">*</span></label>
            <input type="tel" name="sdt" required placeholder="0912345678"
              class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-xs text-charcoal focus:border-accent focus:outline-none" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-charcoal mb-0.5">Email <span class="text-red-500">*</span></label>
          <input type="email" name="email" required placeholder="email@gmail.com"
            class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-xs text-charcoal focus:border-accent focus:outline-none" />
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-semibold text-charcoal mb-0.5">Mật khẩu <span class="text-red-500">*</span></label>
            <input type="password" name="mat_khau" required minlength="6" placeholder="Tối thiểu 6 ký tự"
              class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-xs text-charcoal focus:border-accent focus:outline-none" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-charcoal mb-0.5">Xác nhận MK <span class="text-red-500">*</span></label>
            <input type="password" name="mat_khau_xac_nhan" required minlength="6" placeholder="Nhập lại"
              class="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-xs text-charcoal focus:border-accent focus:outline-none" />
          </div>
        </div>

        <div class="pt-2">
          <button type="submit" id="modalRegSubmitBtn"
            class="tap-feedback w-full rounded-xl bg-olive py-2.5 text-xs font-semibold text-white hover:bg-olive/90 transition shadow-sm">
            Đăng ký &amp; Tự động mở thẻ
          </button>
        </div>
      </form>
    </div>

  </div>
</div>

<script>
  function openAuthModal(tab = 'login') {
    const modal = document.getElementById('authModal');
    if (!modal) return;
    switchModalAuthTab(tab);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  function switchModalAuthTab(tab) {
    const loginSection = document.getElementById('modalLoginSection');
    const registerSection = document.getElementById('modalRegisterSection');
    const tabLoginBtn = document.getElementById('modalTabLoginBtn');
    const tabRegisterBtn = document.getElementById('modalTabRegisterBtn');
    const errorBox = document.getElementById('authModalError');

    if (errorBox) errorBox.classList.add('hidden');

    if (tab === 'register') {
      loginSection.classList.add('hidden');
      registerSection.classList.remove('hidden');
      tabRegisterBtn.classList.add('border-charcoal', 'text-charcoal');
      tabRegisterBtn.classList.remove('border-transparent', 'text-muted');
      tabLoginBtn.classList.remove('border-charcoal', 'text-charcoal');
      tabLoginBtn.classList.add('border-transparent', 'text-muted');
    } else {
      registerSection.classList.add('hidden');
      loginSection.classList.remove('hidden');
      tabLoginBtn.classList.add('border-charcoal', 'text-charcoal');
      tabLoginBtn.classList.remove('border-transparent', 'text-muted');
      tabRegisterBtn.classList.remove('border-charcoal', 'text-charcoal');
      tabRegisterBtn.classList.add('border-transparent', 'text-muted');
    }
  }

  function modalQuickFill(username, password) {
    document.getElementById('modalLoginId').value = username;
    document.getElementById('modalLoginPass').value = password;
    document.getElementById('modalLoginForm').dispatchEvent(new Event('submit', { cancelable: true }));
  }

  async function handleModalLogin(e) {
    e.preventDefault();
    const errorBox = document.getElementById('authModalError');
    const submitBtn = document.getElementById('modalLoginSubmitBtn');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Đang xác thực...';
    errorBox.classList.add('hidden');

    const identifier = document.getElementById('modalLoginId').value;
    const password = document.getElementById('modalLoginPass').value;

    try {
      const response = await fetch('api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ action: 'login', identifier, password })
      });
      const data = await response.json();

      if (data.success) {
        if (window.showStoreToast) window.showStoreToast('✓ ' + data.message);
        setTimeout(() => {
          window.location.href = data.redirect || 'index.php';
        }, 500);
      } else {
        errorBox.textContent = data.message || 'Đăng nhập không thành công.';
        errorBox.classList.remove('hidden');
      }
    } catch (err) {
      errorBox.textContent = 'Lỗi kết nối tới máy chủ. Vui lòng thử lại.';
      errorBox.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  }

  async function handleModalRegister(e) {
    e.preventDefault();
    const form = e.target;
    const errorBox = document.getElementById('authModalError');
    const submitBtn = document.getElementById('modalRegSubmitBtn');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Đang xử lý mở thẻ...';
    errorBox.classList.add('hidden');

    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    payload.action = 'register';

    try {
      const response = await fetch('api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await response.json();

      if (data.success) {
        if (window.showStoreToast) window.showStoreToast('✓ ' + data.message);
        setTimeout(() => {
          window.location.href = 'index.php?page=loyalty&registered=1';
        }, 600);
      } else {
        errorBox.textContent = data.message || 'Đăng ký không thành công.';
        errorBox.classList.remove('hidden');
      }
    } catch (err) {
      errorBox.textContent = 'Lỗi kết nối tới máy chủ. Vui lòng thử lại.';
      errorBox.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  }
</script>
