<?php
/**
 * MODAL QUẢN LÝ ĐỊA CHỈ NHẬN HÀNG (store/includes/address_modal.php)
 * Dùng chung cho cả Trang Thanh toán (checkout.php) và Hồ sơ / Thành viên (loyalty.php)
 * Giao tiếp với API api/address.php (100% Prepared Statements, Chống IDOR & XSS)
 */
?>
<!-- Address Modal Backdrop -->
<div id="addressModal" role="dialog" aria-modal="true" aria-labelledby="addressModalTitle"
  class="fixed inset-0 z-[70] hidden items-center justify-center p-4 bg-charcoal/60 backdrop-blur-sm transition-opacity duration-300">
  
  <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 sm:p-8 shadow-2xl transition-all duration-300">
    <div class="flex items-center justify-between border-b border-stone-200 pb-4">
      <div>
        <h3 id="addressModalTitle" class="text-lg font-semibold text-charcoal">Thêm địa chỉ nhận hàng</h3>
        <p class="text-xs text-muted mt-0.5">Lưu trữ vào bảng <code>dc_dia_chi_kh</code> liên kết tài khoản của bạn</p>
      </div>
      <button type="button" onclick="closeAddressModal()" class="rounded-full p-1.5 text-stone-400 hover:bg-stone-100 hover:text-charcoal transition">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Form Địa chỉ -->
    <form id="addressForm" onsubmit="handleAddressSubmit(event)" class="mt-5 space-y-4">
      <input type="hidden" id="addrMaDc" name="ma_dc" value="" />
      <input type="hidden" id="addrAction" name="action" value="create" />

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label for="addrTenNguoiNhan" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
            Họ tên người nhận <span class="text-red-500">*</span>
          </label>
          <input type="text" id="addrTenNguoiNhan" name="ten_nguoi_nhan" required
            placeholder="VD: Neàng Ra"
            class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
        </div>
        <div>
          <label for="addrSdtNhan" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
            Số điện thoại <span class="text-red-500">*</span>
          </label>
          <input type="tel" id="addrSdtNhan" name="sdt_nhan" required
            placeholder="VD: 0918223344"
            class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label for="addrTinhThanh" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
            Tỉnh / Thành phố <span class="text-red-500">*</span>
          </label>
          <input type="text" id="addrTinhThanh" name="tinh_thanh" required
            placeholder="VD: An Giang"
            class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
        </div>
        <div>
          <label for="addrQuanHuyen" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
            Quận / Huyện <span class="text-red-500">*</span>
          </label>
          <input type="text" id="addrQuanHuyen" name="quan_huyen" required
            placeholder="VD: TP. Long Xuyên"
            class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
        </div>
        <div>
          <label for="addrPhuongXa" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
            Phường / Xã <span class="text-red-500">*</span>
          </label>
          <input type="text" id="addrPhuongXa" name="phuong_xa" required
            placeholder="VD: P. Đông Xuyên"
            class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
        </div>
      </div>

      <div>
        <label for="addrDiaChiCuThe" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
          Địa chỉ cụ thể (Số nhà, tên đường...) <span class="text-red-500">*</span>
        </label>
        <input type="text" id="addrDiaChiCuThe" name="dia_chi_cu_the" required
          placeholder="VD: Số 18 Ung Văn Khiêm (Khu ĐH An Giang)"
          class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 transition" />
      </div>

      <div class="pt-1">
        <label class="flex items-center gap-2 cursor-pointer select-none">
          <input type="checkbox" id="addrMacDinh" name="mac_dinh" value="1" class="h-4 w-4 rounded border-stone-300 text-charcoal accent-accent focus:ring-accent" />
          <span class="text-xs font-medium text-charcoal">Đặt làm địa chỉ nhận hàng mặc định (mac_dinh = 1)</span>
        </label>
      </div>

      <div id="addrFormError" class="hidden text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200"></div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-stone-200">
        <button type="button" onclick="closeAddressModal()"
          class="tap-feedback rounded-xl border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-100 transition">
          Hủy bỏ
        </button>
        <button type="submit" id="addrSubmitBtn"
          class="tap-feedback rounded-xl bg-charcoal px-5 py-2 text-xs font-medium text-white hover:bg-accent transition shadow-sm">
          Lưu địa chỉ
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function openAddressModal(mode = 'create', data = null) {
    const modal = document.getElementById('addressModal');
    const title = document.getElementById('addressModalTitle');
    const actionInput = document.getElementById('addrAction');
    const maDcInput = document.getElementById('addrMaDc');
    const submitBtn = document.getElementById('addrSubmitBtn');
    const errorBox = document.getElementById('addrFormError');
    if (!modal) return;

    errorBox.classList.add('hidden');
    errorBox.textContent = '';

    if (mode === 'update' && data) {
      title.textContent = 'Chỉnh sửa địa chỉ nhận hàng';
      actionInput.value = 'update';
      maDcInput.value = data.ma_dc || '';
      document.getElementById('addrTenNguoiNhan').value = data.ten_nguoi_nhan || '';
      document.getElementById('addrSdtNhan').value = data.sdt_nhan || '';
      document.getElementById('addrTinhThanh').value = data.tinh_thanh || '';
      document.getElementById('addrQuanHuyen').value = data.quan_huyen || '';
      document.getElementById('addrPhuongXa').value = data.phuong_xa || '';
      document.getElementById('addrDiaChiCuThe').value = data.dia_chi_cu_the || '';
      document.getElementById('addrMacDinh').checked = (parseInt(data.mac_dinh, 10) === 1);
      submitBtn.textContent = 'Cập nhật địa chỉ';
    } else {
      title.textContent = 'Thêm mới địa chỉ nhận hàng';
      actionInput.value = 'create';
      maDcInput.value = '';
      document.getElementById('addressForm').reset();
      submitBtn.textContent = 'Lưu địa chỉ';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeAddressModal() {
    const modal = document.getElementById('addressModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  async function handleAddressSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const errorBox = document.getElementById('addrFormError');
    const submitBtn = document.getElementById('addrSubmitBtn');
    const originalText = submitBtn.textContent;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Đang lưu...';
    errorBox.classList.add('hidden');

    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    payload.mac_dinh = document.getElementById('addrMacDinh').checked ? 1 : 0;

    try {
      const response = await fetch('api/address.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const res = await response.json();

      if (res.success) {
        closeAddressModal();
        if (window.showStoreToast) {
          window.showStoreToast('✓ ' + res.message);
        }
        // Tải lại danh sách địa chỉ hoặc làm mới trang
        setTimeout(() => {
          window.location.reload();
        }, 600);
      } else {
        errorBox.textContent = res.message || 'Có lỗi xảy ra khi lưu địa chỉ.';
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

  async function setDefaultAddress(maDc) {
    if (!confirm('Bạn có muốn đặt địa chỉ này làm địa chỉ nhận hàng mặc định?')) return;
    try {
      const response = await fetch('api/address.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ action: 'set_default', ma_dc: maDc })
      });
      const res = await response.json();
      if (res.success) {
        if (window.showStoreToast) window.showStoreToast('✓ ' + res.message);
        setTimeout(() => window.location.reload(), 500);
      } else {
        alert(res.message || 'Lỗi khi đặt địa chỉ mặc định');
      }
    } catch (e) {
      alert('Lỗi kết nối tới máy chủ');
    }
  }

  async function deleteAddress(maDc) {
    if (!confirm('Bạn có chắc chắn muốn xóa địa chỉ này khỏi sổ địa chỉ?')) return;
    try {
      const response = await fetch('api/address.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ action: 'delete', ma_dc: maDc })
      });
      const res = await response.json();
      if (res.success) {
        if (window.showStoreToast) window.showStoreToast('✓ ' + res.message);
        setTimeout(() => window.location.reload(), 500);
      } else {
        alert(res.message || 'Lỗi khi xóa địa chỉ');
      }
    } catch (e) {
      alert('Lỗi kết nối tới máy chủ');
    }
  }
</script>
