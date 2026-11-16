<?php
/**
 * TẦNG QUẢN TRỊ: Quản lý Đơn hàng & Xét duyệt Cộng điểm (admin/orders.php)
 * Đổ dữ liệu thực tế từ bảng dh_don_hang, ctdh_chi_tiet_dh, ttd_the_tich_diem, htv_hang_thanh_vien
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/../services/admin/OrderAdminService.php';

$orderService = new OrderAdminService();
$orders       = $orderService->getAllOrders();

$adminPageTitle  = 'Quản lý Đơn hàng | Fashion Loyalty';
$activeAdminMenu = 'orders';

require __DIR__ . '/includes/header.php';

$statusBadges = [
    'CHO_XAC_NHAN' => ['label' => 'Chờ xác nhận', 'class' => 'bg-amber-50 text-amber-800'],
    'DANG_GIAO'    => ['label' => 'Đang giao',    'class' => 'bg-blue-50 text-blue-800'],
    'HOAN_THANH'   => ['label' => 'Hoàn thành',   'class' => 'bg-emerald-50 text-emerald-800'],
    'DA_HUY'       => ['label' => 'Đã hủy',       'class' => 'bg-slate-100 text-slate-600'],
];
?>
<header>
  <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-slate-500">
    <a href="index.php" class="hover:text-slate-900">Tổng quan</a>
    <span aria-hidden="true">/</span>
    <span class="text-slate-700">Quản lý Đơn hàng</span>
  </nav>
  <h1 class="mt-2 text-2xl font-semibold leading-tight text-slate-900">Quản lý Đơn hàng &amp; Kích hoạt Loyalty Engine</h1>
  <p class="mt-1 text-sm text-slate-500">Quản lý và cập nhật trạng thái các đơn đặt hàng của khách.</p>
</header>

<section aria-label="Công cụ lọc" class="mt-8 flex flex-col gap-3 sm:flex-row">
  <div class="relative">
    <label for="orderSearch" class="sr-only">Tìm theo mã đơn hoặc tên khách</label>
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6.5" /><path d="M16 16l4 4" stroke-linecap="round" /></svg>
    <input id="orderSearch" type="search" placeholder="Tìm theo mã đơn, tên khách hoặc SĐT..."
      class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-80" />
  </div>
  <div>
    <label for="statusFilter" class="sr-only">Lọc theo trạng thái</label>
    <select id="statusFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-56">
      <option value="">Tất cả trạng thái</option>
      <option value="CHO_XAC_NHAN">Chờ xác nhận</option>
      <option value="DANG_GIAO">Đang giao</option>
      <option value="HOAN_THANH">Hoàn thành</option>
      <option value="DA_HUY">Đã hủy</option>
    </select>
  </div>
</section>

<section aria-label="Danh sách đơn hàng" class="fade-up mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th scope="col" class="px-6 py-3 font-medium">Mã đơn</th>
          <th scope="col" class="px-6 py-3 font-medium">Khách hàng &amp; Hạng hiện tại</th>
          <th scope="col" class="px-6 py-3 font-medium">Ngày đặt</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Tiền hàng / Giảm giá</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Thực trả</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Điểm dự kiến</th>
          <th scope="col" class="px-6 py-3 font-medium">Trạng thái</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Thao tác</th>
        </tr>
      </thead>
      <tbody id="orderRows" class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($orders as $od): ?>
          <?php
            $badge = $statusBadges[$od['trang_thai_dh']] ?? ['label' => $od['trang_thai_dh'], 'class' => 'bg-slate-100 text-slate-700'];
            $totalDiscount = (float)$od['tien_giam_hang_tv'] + (float)$od['tien_giam_voucher'] + ((int)$od['diem_su_dung'] * 1000);
          ?>
          <tr class="order-row align-middle"
            data-search="<?= htmlspecialchars(mb_strtolower($od['ma_dh_code'] . ' ' . $od['ten_khach_hang'] . ' ' . $od['sdt_khach_hang'])) ?>"
            data-status="<?= htmlspecialchars($od['trang_thai_dh']) ?>">
            <td class="whitespace-nowrap px-6 py-4 font-mono font-semibold text-slate-900">#<?= htmlspecialchars($od['ma_dh_code']) ?></td>
            <td class="px-6 py-4">
              <p class="font-medium text-slate-900"><?= htmlspecialchars($od['ten_khach_hang']) ?></p>
              <p class="text-xs text-slate-500">
                <?= htmlspecialchars($od['ten_hang'] ?? 'Chưa mở thẻ') ?> (<?= (int)$od['tong_diem_tich_luy'] ?> điểm)
              </p>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-slate-600"><?= formatDateVN($od['ngay_dat'], true) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <p class="text-slate-700"><?= formatVND($od['tong_tien_hang']) ?></p>
              <?php if ($totalDiscount > 0): ?>
                <p class="text-xs text-amber-700">Giảm: -<?= formatVND($totalDiscount) ?></p>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-slate-900"><?= formatVND($od['thanh_tien']) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-emerald-700">+<?= (int)$od['diem_cong_du_kien'] ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4">
              <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium <?= $badge['class'] ?>">
                <?= htmlspecialchars($badge['label']) ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <div class="inline-flex items-center gap-2">
                <button type="button" data-open-order="<?= (int)$od['ma_dh'] ?>"
                  class="tap-feedback rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-slate-500 hover:text-slate-900">
                  Xem chi tiết (<?= count($od['items']) ?>)
                </button>
                <?php if ($od['trang_thai_dh'] !== 'HOAN_THANH' && $od['trang_thai_dh'] !== 'DA_HUY'): ?>
                  <button type="button"
                    data-approve-order="<?= (int)$od['ma_dh'] ?>"
                    data-order-code="<?= htmlspecialchars($od['ma_dh_code']) ?>"
                    class="tap-feedback inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Duyệt đơn
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p id="orderSummary" class="border-t border-slate-200 px-6 py-4 text-sm text-slate-500">
    Hiển thị <?= count($orders) ?> trên <?= count($orders) ?> đơn hàng
  </p>
</section>

<!-- Modal Xem Chi tiết Đơn hàng & Tiến trình lên hạng từ CSDL -->
<div id="orderModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4 py-8" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle">
  <div class="max-h-full w-full max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
      <div>
        <p id="orderModalCode" class="font-mono text-xs font-semibold uppercase tracking-wider text-blue-700"></p>
        <h2 id="orderModalTitle" class="mt-1 text-lg font-semibold leading-tight text-slate-900">Chi tiết đơn đặt hàng</h2>
      </div>
      <button type="button" id="closeOrderModalBtn" aria-label="Đóng" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round" /></svg>
      </button>
    </div>

    <div class="space-y-6 px-6 py-6">
      <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm sm:grid-cols-4">
        <div>
          <dt class="text-slate-500">Khách hàng</dt>
          <dd id="orderCustomer" class="mt-1 font-medium text-slate-900"></dd>
        </div>
        <div>
          <dt class="text-slate-500">Ngày đặt</dt>
          <dd id="orderDate" class="mt-1 font-medium text-slate-900"></dd>
        </div>
        <div>
          <dt class="text-slate-500">Thanh toán</dt>
          <dd id="orderPayment" class="mt-1 font-medium text-slate-900"></dd>
        </div>
        <div>
          <dt class="text-slate-500">Thực trả</dt>
          <dd id="orderTotal" class="tabular-nums mt-1 font-semibold text-slate-900"></dd>
        </div>
      </dl>

      <!-- Thông tin giao hàng -->
      <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-blue-700 mb-2">📦 Thông tin giao hàng</h3>
        <dl class="space-y-1 text-slate-700">
          <div class="flex gap-2"><dt class="w-36 shrink-0 text-slate-500">Người nhận:</dt><dd id="orderRecipientName" class="font-medium"></dd></div>
          <div class="flex gap-2"><dt class="w-36 shrink-0 text-slate-500">Số điện thoại:</dt><dd id="orderRecipientPhone" class="font-medium"></dd></div>
          <div class="flex gap-2"><dt class="w-36 shrink-0 text-slate-500">Địa chỉ giao:</dt><dd id="orderRecipientAddress" class="leading-relaxed"></dd></div>
        </dl>
      </div>

      <div>
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Danh sách mặt hàng đã đặt</h3>
        <div class="mt-2 overflow-x-auto rounded-lg border border-slate-200">
          <table class="min-w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500">
              <tr>
                <th class="px-4 py-2.5">Mã SKU</th>
                <th class="px-4 py-2.5">Sản phẩm &amp; Phân loại</th>
                <th class="px-4 py-2.5 text-right">SL</th>
                <th class="px-4 py-2.5 text-right">Đơn giá</th>
                <th class="px-4 py-2.5 text-right">Thành tiền</th>
              </tr>
            </thead>
            <tbody id="orderItemsBody" class="divide-y divide-slate-200 tabular-nums"></tbody>
          </table>
        </div>
      </div>

      <div class="rounded-lg bg-slate-50 p-4">
        <p class="text-sm font-medium text-slate-800">Mô phỏng Xét nâng hạng Thẻ thành viên</p>
        <p id="orderPointsSummary" class="mt-1 text-sm text-slate-600"></p>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
          <div id="orderPointsBar" class="h-full rounded-full bg-blue-700 transition-all duration-500" style="width: 0%"></div>
        </div>
      </div>

      <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
        <button type="button" id="closeOrderModalFooterBtn" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:border-slate-500">Đóng</button>
        <button type="button" id="modalApproveBtn"
          class="tap-feedback inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          Duyệt hoàn thành
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  (() => {
    const ordersData = <?= json_encode($orders) ?>;
    const searchInput = document.getElementById('orderSearch');
    const statusFilter = document.getElementById('statusFilter');
    const rows = Array.from(document.querySelectorAll('.order-row'));
    const summary = document.getElementById('orderSummary');
    const modal = document.getElementById('orderModal');

    function filterOrders() {
      const q = (searchInput?.value || '').trim().toLowerCase();
      const st = statusFilter?.value || '';
      let count = 0;
      rows.forEach((row) => {
        const matchQ = !q || row.dataset.search.includes(q);
        const matchSt = !st || row.dataset.status === st;
        const show = matchQ && matchSt;
        row.classList.toggle('hidden', !show);
        if (show) count++;
      });
      if (summary) summary.textContent = `Hiển thị ${count} trên ${rows.length} đơn hàng`;
    }

    searchInput?.addEventListener('input', filterOrders);
    statusFilter?.addEventListener('change', filterOrders);

    function openOrderModal(orderId) {
      const order = ordersData.find((o) => Number(o.ma_dh) === Number(orderId));
      if (!order) return;

      document.getElementById('orderModalCode').textContent = `Đơn #${order.ma_dh_code} (${order.trang_thai_dh})`;
      document.getElementById('orderCustomer').textContent = `${order.ten_khach_hang} (${order.ten_hang || 'Khách'})`;
      document.getElementById('orderDate').textContent = order.ngay_dat;
      document.getElementById('orderPayment').textContent = order.pt_thanh_toan;
      document.getElementById('orderTotal').textContent = Number(order.thanh_tien).toLocaleString('vi-VN') + 'đ';

      // Thông tin giao hàng
      document.getElementById('orderRecipientName').textContent = order.ten_nguoi_nhan || order.ten_khach_hang || '—';
      document.getElementById('orderRecipientPhone').textContent = order.sdt_nguoi_nhan || order.sdt_khach_hang || '—';
      const diaChiParts = [order.dia_chi_cu_the, order.phuong_xa, order.quan_huyen, order.tinh_thanh].filter(Boolean);
      document.getElementById('orderRecipientAddress').textContent = diaChiParts.length > 0 ? diaChiParts.join(', ') : 'Khách chưa cập nhật địa chỉ mặc định';

      const tbody = document.getElementById('orderItemsBody');
      tbody.innerHTML = (order.items || []).map((item) => `
        <tr>
          <td class="px-4 py-2.5 font-mono font-medium text-slate-800">${item.ma_sku}</td>
          <td class="px-4 py-2.5 text-slate-800">${item.ten_sp} (${item.mau_sac}, ${item.kich_co})</td>
          <td class="px-4 py-2.5 text-right">${item.so_luong}</td>
          <td class="px-4 py-2.5 text-right">${Number(item.don_gia_luc_mua).toLocaleString('vi-VN')}đ</td>
          <td class="px-4 py-2.5 text-right font-semibold">${Number(item.thanh_tien_muc).toLocaleString('vi-VN')}đ</td>
        </tr>
      `).join('');

      const curPts = Number(order.tong_diem_tich_luy || 0);
      const addPts = Number(order.diem_cong_du_kien || 0);
      const nextTier = order.next_tier;
      if (nextTier) {
        document.getElementById('orderPointsSummary').textContent =
          `Điểm hiện tại: ${curPts}/${nextTier.diem_toi_thieu} điểm. Đơn này cộng +${addPts} điểm -> Sau khi duyệt đạt ${curPts + addPts} điểm ` +
          (order.will_upgrade_on_approve ? `(Đủ điều kiện tự động thăng lên ${nextTier.ten_hang}!)` : `(Còn thiếu ${order.points_to_next_tier} điểm để lên ${nextTier.ten_hang}).`);
        document.getElementById('orderPointsBar').style.width = `${order.progress_percent}%`;
      } else {
        document.getElementById('orderPointsSummary').textContent = `Khách hàng đang có ${curPts} điểm (Đã ở hạng cao nhất).`;
        document.getElementById('orderPointsBar').style.width = '100%';
      }

      const approveBtn = document.getElementById('modalApproveBtn');
      approveBtn.classList.toggle('hidden', order.trang_thai_dh === 'HOAN_THANH' || order.trang_thai_dh === 'DA_HUY');
      approveBtn.dataset.approveOrder = order.ma_dh;
      approveBtn.dataset.orderCode = order.ma_dh_code;

      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function closeOrderModal() {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    async function handleApproveOrder(maDh, orderCode, triggerBtn) {
      if (!confirm(`Xác nhận duyệt HOÀN THÀNH đơn hàng #${orderCode} và kích hoạt cộng điểm tích lũy cho khách hàng?`)) {
        return;
      }

      triggerBtn.disabled = true;
      const originalHtml = triggerBtn.innerHTML;
      triggerBtn.innerHTML = `
        <svg class="h-4 w-4 animate-spin text-white inline-block mr-1" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        Đang xử lý...
      `;

      try {
        const res = await fetch('../api/admin_order.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'approve', ma_dh: Number(maDh), trang_thai_dh: 'HOAN_THANH' })
        });
        const data = await res.json();

        if (data.success) {
          window.showAdminToast(data.message);
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          alert(data.message || 'Có lỗi xảy ra khi duyệt đơn.');
          triggerBtn.disabled = false;
          triggerBtn.innerHTML = originalHtml;
        }
      } catch (err) {
        console.error(err);
        alert('Lỗi kết nối khi gửi yêu cầu duyệt đơn.');
        triggerBtn.disabled = false;
        triggerBtn.innerHTML = originalHtml;
      }
    }

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-open-order]');
      if (btn) openOrderModal(btn.dataset.openOrder);
      if (e.target === modal || e.target.closest('#closeOrderModalBtn') || e.target.closest('#closeOrderModalFooterBtn')) {
        closeOrderModal();
      }

      const approveBtn = e.target.closest('[data-approve-order]');
      if (approveBtn) {
        handleApproveOrder(approveBtn.dataset.approveOrder, approveBtn.dataset.orderCode || approveBtn.dataset.approveOrder, approveBtn);
      }
    });
  })();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

