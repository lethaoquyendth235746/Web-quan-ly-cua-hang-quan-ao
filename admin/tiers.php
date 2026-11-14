<?php
/**
 * TẦNG QUẢN TRỊ: Cấu hình 4 Bậc Hạng Thành Viên & Danh sách Thẻ Tích Điểm (admin/tiers.php)
 * Đổ dữ liệu thực tế từ bảng htv_hang_thanh_vien và ttd_the_tich_diem
 * Xử lý hoàn chỉnh Chỉnh sửa cấu hình hạng & Tự động đồng bộ thẻ tích điểm
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/admin/TierAdminService.php';

$tierService = new TierAdminService();

// =============================================================================
// XỬ LÝ POST THEO MÔ HÌNH PRG (POST - REDIRECT - GET)
// Phải chạy trước khi nạp header HTML để tránh lỗi "headers already sent"
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_tier') {
    try {
        $maHang = (int)($_POST['ma_hang'] ?? 0);
        $res = $tierService->updateTierConfig($maHang, $_POST);
        $_SESSION['admin_flash'] = [
            'type'    => 'success',
            'message' => "Đã cập nhật cấu hình {$res['ten_hang']} và tự động đồng bộ thẻ tích điểm thành viên thành công!"
        ];
    } catch (Throwable $e) {
        $_SESSION['admin_flash'] = [
            'type'    => 'error',
            'message' => $e->getMessage()
        ];
    }
    header('Location: tiers.php');
    exit;
}

$tiers       = $tierService->getAllTiersWithMemberCount();
$memberCards = $tierService->getAllMemberCards();

$adminPageTitle  = 'Cấu hình Hạng thành viên | Fashion Loyalty';
$activeAdminMenu = 'tiers';

require __DIR__ . '/includes/header.php';
?>

<header>
  <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-slate-500">
    <a href="index.php" class="hover:text-slate-900">Tổng quan</a>
    <span aria-hidden="true">/</span>
    <span class="text-slate-700">Cấu hình Hạng thành viên</span>
  </nav>
  <h1 class="mt-2 text-2xl font-semibold leading-tight text-slate-900">Cấu hình Hạng thành viên &amp; Quản lý Thẻ tích điểm</h1>
  <p class="mt-2 max-w-2xl text-sm text-slate-500">
    Dữ liệu thực tế từ bảng <code>htv_hang_thanh_vien</code> (4 bậc hạng) và <code>ttd_the_tich_diem</code> (<?= count($memberCards) ?> thẻ thành viên).
  </p>
</header>

<!-- Bảng Cấu hình 4 Bậc Hạng Thành Viên -->
<section aria-label="Danh sách hạng" class="fade-up mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-200 px-6 py-5 flex items-center justify-between">
    <div>
      <h2 class="text-base font-semibold text-slate-900">4 Bậc Hạng Thành Viên (Bảng <code>htv_hang_thanh_vien</code>)</h2>
      <p class="mt-1 text-sm text-slate-500">Quy chuẩn tích điểm cơ bản: 10.000đ = 1 điểm thưởng. Thay đổi ngưỡng điểm sẽ tự động đồng bộ lại thẻ của toàn bộ khách hàng.</p>
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th scope="col" class="px-6 py-3 font-medium">Mã &amp; Tên Hạng</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Ngưỡng điểm tối thiểu</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Chiết khấu trực tiếp</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Hệ số nhân điểm</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Số khách đang đạt</th>
          <th scope="col" class="px-6 py-3 font-medium">Mô tả quyền lợi</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($tiers as $idx => $t): ?>
          <?php 
            $prevTier = $tiers[$idx - 1] ?? null;
            $nextTier = $tiers[$idx + 1] ?? null;
            $minAllowed = $prevTier ? (int)$prevTier['diem_toi_thieu'] + 1 : 0;
            $maxAllowed = $nextTier ? (int)$nextTier['diem_toi_thieu'] - 1 : null;
          ?>
          <tr class="align-middle hover:bg-slate-50/70 transition-colors">
            <td class="whitespace-nowrap px-6 py-4">
              <span class="inline-flex items-center gap-2.5 font-semibold text-slate-900">
                <span class="h-3.5 w-3.5 rounded-full shadow-sm border border-slate-200" style="background: <?= htmlspecialchars($t['mau_sac_the']) ?>"></span>
                <?= htmlspecialchars($t['ten_hang']) ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-slate-900"><?= number_format((int)$t['diem_toi_thieu'], 0, ',', '.') ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-emerald-700"><?= number_format((float)$t['phan_tram_giam'], 1) ?>%</td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-blue-700">x<?= number_format((float)$t['he_so_tich_diem'], 2) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-slate-800"><?= (int)$t['so_thanh_vien'] ?> thành viên</td>
            <td class="px-6 py-4 text-xs text-slate-600 max-w-xs"><?= htmlspecialchars($t['mo_ta_uu_dai'] ?? '') ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <button type="button"
                class="btn-edit-tier tap-feedback rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:border-blue-400 hover:bg-blue-50 transition-colors"
                data-ma-hang="<?= (int)$t['ma_hang'] ?>"
                data-ten-hang="<?= htmlspecialchars($t['ten_hang']) ?>"
                data-diem-toi-thieu="<?= (int)$t['diem_toi_thieu'] ?>"
                data-phan-tram-giam="<?= (float)$t['phan_tram_giam'] ?>"
                data-he-so-tich-diem="<?= (float)$t['he_so_tich_diem'] ?>"
                data-mo-ta-uu-dai="<?= htmlspecialchars($t['mo_ta_uu_dai'] ?? '') ?>"
                data-mau-sac-the="<?= htmlspecialchars($t['mau_sac_the']) ?>"
                data-min-allowed="<?= $minAllowed ?>"
                data-max-allowed="<?= $maxAllowed !== null ? $maxAllowed : '' ?>"
                data-prev-name="<?= htmlspecialchars($prevTier['ten_hang'] ?? '') ?>"
                data-prev-points="<?= (int)($prevTier['diem_toi_thieu'] ?? 0) ?>"
                data-next-name="<?= htmlspecialchars($nextTier['ten_hang'] ?? '') ?>"
                data-next-points="<?= (int)($nextTier['diem_toi_thieu'] ?? 0) ?>"
                onclick="openEditTierModal(this)">
                Chỉnh sửa
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- Danh sách Thẻ tích điểm của khách hàng từ bảng ttd_the_tich_diem -->
<section aria-labelledby="memberCardsTitle" class="fade-up mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-200 px-6 py-5">
    <h2 id="memberCardsTitle" class="text-base font-semibold text-slate-900">
      Danh sách Thẻ Tích Điểm Khách Hàng (Bảng <code>ttd_the_tich_diem</code> &mdash; <?= count($memberCards) ?> thẻ)
    </h2>
    <p class="mt-1 text-sm text-slate-500">Theo dõi tách biệt giữa Tổng điểm tích lũy (xét hạng) và Điểm khả dụng (tiêu dùng/đổi voucher).</p>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th class="px-6 py-3 font-medium">Mã số thẻ</th>
          <th class="px-6 py-3 font-medium">Khách hàng</th>
          <th class="px-6 py-3 font-medium">Hạng hiện tại</th>
          <th class="px-6 py-3 text-right font-medium">Tổng điểm xét hạng</th>
          <th class="px-6 py-3 text-right font-medium">Điểm khả dụng</th>
          <th class="px-6 py-3 font-medium">Ngày mở thẻ</th>
          <th class="px-6 py-3 text-right font-medium">Xem dưới góc độ khách</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($memberCards as $mc): ?>
          <tr class="hover:bg-slate-50/70 transition-colors">
            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs font-semibold text-slate-900"><?= htmlspecialchars($mc['ma_so_the']) ?></td>
            <td class="px-6 py-4">
              <p class="font-medium text-slate-900"><?= htmlspecialchars($mc['ho_ten']) ?></p>
              <p class="text-xs text-slate-500"><?= htmlspecialchars($mc['email']) ?> &middot; <?= htmlspecialchars($mc['sdt']) ?></p>
            </td>
            <td class="whitespace-nowrap px-6 py-4">
              <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-800">
                <span class="h-2 w-2 rounded-full shadow-sm" style="background: <?= htmlspecialchars($mc['mau_sac_the']) ?>"></span>
                <?= htmlspecialchars($mc['ten_hang']) ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-slate-900"><?= number_format((int)$mc['tong_diem_tich_luy'], 0, ',', '.') ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-emerald-700"><?= number_format((int)$mc['diem_kha_dung'], 0, ',', '.') ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4 text-slate-600"><?= formatDateVN($mc['ngay_cap']) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <a href="../index.php?page=loyalty&switch_user=<?= (int)$mc['ma_nd'] ?>"
                class="tap-feedback inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 transition-colors">
                Mở Thẻ trên Store &rarr;
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ========================================================================= -->
<!-- MODAL: CHỈNH SỬA CẤU HÌNH HẠNG THÀNH VIÊN (#tierEditModal)                 -->
<!-- ========================================================================= -->
<div id="tierEditModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeEditTierModal()"></div>
  <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl transition-all">
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-lg font-semibold text-slate-900">Chỉnh sửa Cấu hình Hạng</h3>
            <span id="modalTierBadge" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold text-slate-800 bg-slate-100">
              <span id="modalBadgeDot" class="h-2.5 w-2.5 rounded-full"></span>
              <span id="modalBadgeText">Hạng</span>
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">Bảng <code>htv_hang_thanh_vien</code> &middot; Tự động đồng bộ thẻ thành viên</p>
        </div>
        <button type="button" onclick="closeEditTierModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        </button>
      </div>

      <!-- Modal Form -->
      <form id="tierEditForm" method="POST" action="tiers.php" class="p-6 space-y-4">
        <input type="hidden" name="action" value="update_tier" />
        <input type="hidden" name="ma_hang" id="editMaHang" value="" />

        <!-- 1. Tên hạng -->
        <div>
          <label for="editTenHang" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
            Tên Hạng thành viên <span class="text-rose-600">*</span>
          </label>
          <input type="text" name="ten_hang" id="editTenHang" required maxlength="50"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
        </div>

        <!-- 2. Ngưỡng điểm tối thiểu -->
        <div>
          <label for="editDiemToiThieu" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
            Ngưỡng điểm tối thiểu (Xét thăng hạng) <span class="text-rose-600">*</span>
          </label>
          <input type="number" name="diem_toi_thieu" id="editDiemToiThieu" min="0" step="1" required
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          <p id="pointConstraintHint" class="mt-1.5 text-xs text-slate-500"></p>
        </div>

        <!-- 3. Chiết khấu trực tiếp & Hệ số nhân điểm -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="editPhanTramGiam" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
              Chiết khấu trực tiếp (%) <span class="text-rose-600">*</span>
            </label>
            <input type="number" name="phan_tram_giam" id="editPhanTramGiam" min="0" max="100" step="0.5" required
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
            <p class="mt-1 text-[11px] text-slate-400">Giảm trừ trên hóa đơn (0% - 100%).</p>
          </div>

          <div>
            <label for="editHeSoTichDiem" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
              Hệ số nhân điểm thưởng <span class="text-rose-600">*</span>
            </label>
            <input type="number" name="he_so_tich_diem" id="editHeSoTichDiem" min="1" max="10" step="0.05" required
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
            <p class="mt-1 text-[11px] text-slate-400">Hệ số nhân (1.00 - 10.00 lần).</p>
          </div>
        </div>

        <!-- 4. Màu sắc thẻ (Color picker + Input HEX) -->
        <div>
          <label for="editMauSacThe" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
            Màu sắc thẻ nhận diện <span class="text-rose-600">*</span>
          </label>
          <div class="mt-1 flex items-center gap-3">
            <input type="color" id="editColorPicker"
              class="h-9 w-12 cursor-pointer rounded-lg border border-slate-300 p-0.5 bg-white" />
            <input type="text" name="mau_sac_the" id="editMauSacThe" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" required
              class="w-32 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono uppercase text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
            <span class="text-xs text-slate-400">Định dạng mã màu HEX (VD: #CD7F32, #D4AF37)</span>
          </div>
        </div>

        <!-- 5. Mô tả quyền lợi -->
        <div>
          <label for="editMoTaUuDai" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
            Mô tả quyền lợi &amp; đặc quyền hạng
          </label>
          <textarea name="mo_ta_uu_dai" id="editMoTaUuDai" rows="3" maxlength="255"
            placeholder="Mô tả ưu đãi chiết khấu, voucher và quyền lợi chăm sóc khách hàng..."
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100"></textarea>
        </div>

        <!-- Footer Buttons -->
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
          <button type="button" onclick="closeEditTierModal()"
            class="tap-feedback rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
            Hủy
          </button>
          <button type="submit"
            class="tap-feedback inline-flex items-center gap-1.5 rounded-lg bg-blue-700 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-800 transition-colors">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Lưu thay đổi &amp; Đồng bộ thẻ
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT ĐIỀU KHIỂN MODAL & SYNC MÀU SẮC                                -->
<!-- ========================================================================= -->
<script>
  const tierModal = document.getElementById('tierEditModal');
  const editMaHang = document.getElementById('editMaHang');
  const editTenHang = document.getElementById('editTenHang');
  const editDiemToiThieu = document.getElementById('editDiemToiThieu');
  const editPhanTramGiam = document.getElementById('editPhanTramGiam');
  const editHeSoTichDiem = document.getElementById('editHeSoTichDiem');
  const editMoTaUuDai = document.getElementById('editMoTaUuDai');
  const editMauSacThe = document.getElementById('editMauSacThe');
  const editColorPicker = document.getElementById('editColorPicker');
  const modalBadgeText = document.getElementById('modalBadgeText');
  const modalBadgeDot = document.getElementById('modalBadgeDot');
  const pointHint = document.getElementById('pointConstraintHint');

  window.openEditTierModal = function(btn) {
    const maHang = parseInt(btn.dataset.maHang, 10);
    const tenHang = btn.dataset.tenHang || '';
    const diemToiThieu = parseInt(btn.dataset.diemToiThieu, 10);
    const phanTramGiam = parseFloat(btn.dataset.phanTramGiam || '0');
    const heSoTichDiem = parseFloat(btn.dataset.heSoTichDiem || '1');
    const moTaUuDai = btn.dataset.moTaUuDai || '';
    const mauSacThe = btn.dataset.mauSacThe || '#000000';

    const minAllowed = btn.dataset.minAllowed !== '' ? parseInt(btn.dataset.minAllowed, 10) : null;
    const maxAllowed = btn.dataset.maxAllowed !== '' ? parseInt(btn.dataset.maxAllowed, 10) : null;
    const prevName = btn.dataset.prevName || '';
    const prevPoints = btn.dataset.prevPoints !== '' ? parseInt(btn.dataset.prevPoints, 10) : 0;
    const nextName = btn.dataset.nextName || '';
    const nextPoints = btn.dataset.nextPoints !== '' ? parseInt(btn.dataset.nextPoints, 10) : 0;

    editMaHang.value = maHang;
    editTenHang.value = tenHang;
    editDiemToiThieu.value = diemToiThieu;
    editPhanTramGiam.value = phanTramGiam;
    editHeSoTichDiem.value = heSoTichDiem;
    editMoTaUuDai.value = moTaUuDai;
    editMauSacThe.value = mauSacThe;
    editColorPicker.value = mauSacThe;

    modalBadgeText.textContent = tenHang;
    modalBadgeDot.style.backgroundColor = mauSacThe;

    // Ràng buộc riêng cho từng bậc hạng
    if (maHang === 1) {
      editDiemToiThieu.readOnly = true;
      editDiemToiThieu.min = 0;
      editDiemToiThieu.max = 0;
      pointHint.innerHTML = '<span class="text-amber-700 font-medium">Hạng khởi điểm đầu tiên luôn có ngưỡng cố định bằng 0 điểm.</span>';
    } else {
      editDiemToiThieu.readOnly = false;
      let hintParts = [];

      if (minAllowed !== null) {
        editDiemToiThieu.min = minAllowed;
        hintParts.push(`Lớn hơn ${prevPoints.toLocaleString('vi-VN')} điểm (${prevName})`);
      }
      if (maxAllowed !== null) {
        editDiemToiThieu.max = maxAllowed;
        hintParts.push(`nhỏ hơn ${nextPoints.toLocaleString('vi-VN')} điểm (${nextName})`);
      } else {
        editDiemToiThieu.removeAttribute('max');
      }

      pointHint.innerHTML = `Quy chuẩn: <span class="font-medium text-slate-700">Phải ${hintParts.join(' và ')}</span>.`;
    }

    tierModal.classList.remove('hidden');

    setTimeout(() => {
      if (maHang !== 1) {
        editDiemToiThieu.focus();
        editDiemToiThieu.select();
      } else {
        editPhanTramGiam.focus();
        editPhanTramGiam.select();
      }
    }, 100);
  };

  window.closeEditTierModal = function() {
    tierModal.classList.add('hidden');
  };

  // Đồng bộ giữa Color Picker và Text input HEX
  editColorPicker?.addEventListener('input', (e) => {
    editMauSacThe.value = e.target.value.toUpperCase();
    modalBadgeDot.style.backgroundColor = e.target.value;
  });

  editMauSacThe?.addEventListener('input', (e) => {
    const val = e.target.value.trim();
    if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
      editColorPicker.value = val;
      modalBadgeDot.style.backgroundColor = val;
    }
  });

  // Đóng Modal khi nhấn phím Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !tierModal.classList.contains('hidden')) {
      closeEditTierModal();
    }
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

<!-- Hiển thị Toast thông báo PRG từ Session -->
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
