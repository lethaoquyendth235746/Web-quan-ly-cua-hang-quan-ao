<?php
/**
 * TẦNG QUẢN TRỊ: Quản lý Chiến dịch Voucher Loyalty & Ví Voucher Khách hàng (admin/vouchers.php)
 * Đổ dữ liệu thực tế từ bảng pgg_phieu_giam_gia và vkh_vi_voucher_kh
 * Xử lý hoàn chỉnh Tạo mới, Cập nhật cấu hình & Kết thúc sớm chiến dịch Voucher
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/../services/admin/VoucherAdminService.php';

$voucherService = new VoucherAdminService();

// =============================================================================
// XỬ LÝ POST THEO MÔ HÌNH PRG (POST - REDIRECT - GET)
// Phải chạy trước khi nạp header HTML để tránh lỗi "headers already sent"
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create_voucher':
                $newId = $voucherService->createVoucher($_POST);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã tạo thành công chiến dịch Voucher '{$_POST['ma_code']}' (#PGG-{$newId})!"
                ];
                break;

            case 'update_voucher':
                $maPgg = (int)($_POST['ma_pgg'] ?? 0);
                $voucherService->updateVoucherCampaign($maPgg, $_POST);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã cập nhật cấu hình chiến dịch Voucher #{$maPgg} thành công!"
                ];
                break;

            case 'end_voucher_early':
                $maPgg = (int)($_POST['ma_pgg'] ?? 0);
                $voucherService->endVoucherEarly($maPgg);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã đóng và kết thúc sớm chiến dịch Voucher #{$maPgg}!"
                ];
                break;

            default:
                throw new Exception('Hành động yêu cầu không hợp lệ.');
        }
    } catch (Throwable $e) {
        $_SESSION['admin_flash'] = [
            'type'    => 'error',
            'message' => $e->getMessage()
        ];
    }
    header('Location: vouchers.php');
    exit;
}

$vouchers   = $voucherService->getAllVouchers();
$walletLogs = $voucherService->getCustomerVoucherWalletLogs();
$tiers      = $voucherService->getAllTiers();

$adminPageTitle  = 'Chiến dịch Voucher | Fashion Loyalty';
$activeAdminMenu = 'vouchers';

require __DIR__ . '/includes/header.php';
?>

<header>
  <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-slate-500">
    <a href="index.php" class="hover:text-slate-900">Tổng quan</a>
    <span aria-hidden="true">/</span>
    <span class="text-slate-700">Chiến dịch Voucher</span>
  </nav>
  <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold leading-tight text-slate-900">Quản lý Chiến dịch Voucher Loyalty</h1>
      <p class="mt-1 text-sm text-slate-500">
        Dữ liệu thực tế từ bảng <code>pgg_phieu_giam_gia</code> (<?= count($vouchers) ?> chiến dịch) và lịch sử đổi ví <code>vkh_vi_voucher_kh</code>.
      </p>
    </div>
    <button type="button" onclick="openCreateVoucherModal()"
      class="tap-feedback inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-900 transition-colors">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
      Tạo Voucher mới
    </button>
  </div>
</header>

<!-- Danh sách Chiến dịch Voucher từ bảng pgg_phieu_giam_gia -->
<section aria-label="Bảng chiến dịch voucher" class="fade-up mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th scope="col" class="px-6 py-3 font-medium">Mã Code</th>
          <th scope="col" class="px-6 py-3 font-medium">Tên chiến dịch</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Trị giá giảm</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Đơn tối thiểu</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Điểm cần đổi</th>
          <th scope="col" class="px-6 py-3 font-medium">Hạng yêu cầu</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">SL còn / Đã đổi</th>
          <th scope="col" class="px-6 py-3 font-medium">Thời hạn</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($vouchers as $v): ?>
          <?php
            $discountText = $v['loai_giam'] === 'PHAN_TRAM'
                ? number_format((float)$v['gia_tri_giam'], 0) . '%'
                : formatVND($v['gia_tri_giam']);

            $now = time();
            $start = strtotime($v['ngay_bd']);
            $end = strtotime($v['ngay_kt']);
            $isExpired = $end <= $now;
            $isOutOfStock = (int)$v['so_luong'] <= 0;
            $isUpcoming = $start > $now;

            if ($isExpired) {
                $badgeClass = 'bg-slate-100 text-slate-600';
                $badgeText = 'Đã kết thúc';
            } elseif ($isOutOfStock) {
                $badgeClass = 'bg-amber-50 text-amber-700';
                $badgeText = 'Hết lượt đổi';
            } elseif ($isUpcoming) {
                $badgeClass = 'bg-blue-50 text-blue-700';
                $badgeText = 'Sắp diễn ra';
            } else {
                $badgeClass = 'bg-emerald-50 text-emerald-800';
                $badgeText = 'Đang chạy';
            }
          ?>
          <tr class="align-middle hover:bg-slate-50/70 transition-colors">
            <td class="whitespace-nowrap px-6 py-4">
              <span class="font-mono font-semibold text-slate-900"><?= htmlspecialchars($v['ma_code']) ?></span>
              <span class="mt-1 block inline-block rounded-full px-2 py-0.5 text-[11px] font-medium <?= $badgeClass ?>"><?= $badgeText ?></span>
            </td>
            <td class="px-6 py-4 font-medium text-slate-800"><?= htmlspecialchars($v['ten_pgg']) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-emerald-700"><?= $discountText ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right text-slate-600"><?= formatVND($v['don_toi_thieu']) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-slate-900"><?= number_format((int)$v['diem_can_doi'], 0, ',', '.') ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4">
              <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700">
                <span class="h-2.5 w-2.5 rounded-full shadow-sm" style="background: <?= htmlspecialchars($v['mau_sac_the']) ?>"></span>
                <?= htmlspecialchars($v['ten_hang_yeu_cau']) ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <span class="font-semibold text-slate-900"><?= (int)$v['so_luong'] ?></span>
              <span class="block text-xs text-slate-500">Đã đổi: <?= (int)$v['so_luot_da_doi'] ?> (Dùng: <?= (int)$v['so_luot_da_dung'] ?>)</span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-600">
              <?= formatDateVN($v['ngay_bd']) ?> &rarr; <?= formatDateVN($v['ngay_kt']) ?>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <div class="inline-flex items-center gap-1.5">
                <button type="button"
                  class="tap-feedback rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50 hover:border-blue-400 transition-colors"
                  onclick="openEditVoucherModal(<?= htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8') ?>)">
                  Cấu hình
                </button>
                <?php if (!$isExpired && !$isOutOfStock): ?>
                  <form method="POST" action="vouchers.php" class="inline-block"
                    onsubmit="return confirm('Bạn có chắc chắn muốn kết thúc sớm chiến dịch <?= htmlspecialchars(addslashes($v['ma_code'])) ?>? Số lượng phát hành còn lại sẽ đặt về 0 và thời hạn kết thúc sẽ chuyển về thời điểm hiện tại.');">
                    <input type="hidden" name="action" value="end_voucher_early" />
                    <input type="hidden" name="ma_pgg" value="<?= (int)$v['ma_pgg'] ?>" />
                    <button type="submit"
                      class="tap-feedback rounded-lg border border-rose-200 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50 hover:border-rose-300 transition-colors">
                      Đóng sớm
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="border-t border-slate-200 px-6 py-4 text-sm text-slate-500">
    Hiển thị <?= count($vouchers) ?> chiến dịch Voucher trong CSDL
  </p>
</section>

<!-- Lịch sử Ví Voucher khách hàng đã đổi từ bảng vkh_vi_voucher_kh -->
<section aria-labelledby="walletLogTitle" class="fade-up mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-200 px-6 py-5">
    <h2 id="walletLogTitle" class="text-base font-semibold text-slate-900">Ví Voucher Khách hàng đã đổi (Bảng <code>vkh_vi_voucher_kh</code>)</h2>
    <p class="mt-1 text-sm text-slate-500">Theo dõi các mã giảm giá khách hàng đã dùng điểm khả dụng để đổi về ví cá nhân.</p>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th class="px-6 py-3 font-medium">Mã Ví</th>
          <th class="px-6 py-3 font-medium">Khách hàng</th>
          <th class="px-6 py-3 font-medium">Mã Voucher</th>
          <th class="px-6 py-3 text-right font-medium">Điểm đã đổi</th>
          <th class="px-6 py-3 font-medium">Ngày đổi</th>
          <th class="px-6 py-3 font-medium">Trạng thái sử dụng</th>
          <th class="px-6 py-3 font-medium">Ngày sử dụng</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($walletLogs as $w): ?>
          <tr class="hover:bg-slate-50/70 transition-colors">
            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-slate-600">#VKH<?= str_pad((string)$w['ma_vi_vch'], 2, '0', STR_PAD_LEFT) ?></td>
            <td class="px-6 py-4">
              <p class="font-medium text-slate-900"><?= htmlspecialchars($w['ho_ten']) ?></p>
              <p class="text-xs text-slate-500"><?= htmlspecialchars($w['sdt']) ?></p>
            </td>
            <td class="px-6 py-4">
              <span class="font-mono font-semibold text-blue-700"><?= htmlspecialchars($w['ma_code']) ?></span>
              <span class="block text-xs text-slate-500"><?= htmlspecialchars($w['ten_pgg']) ?></span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-amber-700">-<?= (int)$w['diem_can_doi'] ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4 text-slate-600"><?= formatDateVN($w['ngay_doi'], true) ?></td>
            <td class="whitespace-nowrap px-6 py-4">
              <?php if ((int)$w['trang_thai_su_dung'] === 1): ?>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Đã sử dụng</span>
              <?php else: ?>
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800">Chưa dùng (Khả dụng)</span>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-slate-600"><?= formatDateVN($w['ngay_su_dung'], true) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ========================================================================= -->
<!-- MODAL 1: TẠO MỚI CHIẾN DỊCH VOUCHER (#createVoucherModal)                  -->
<!-- ========================================================================= -->
<div id="createVoucherModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeCreateVoucherModal()"></div>
  <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative w-full max-w-xl rounded-2xl bg-white shadow-2xl transition-all">
      <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
        <div>
          <h3 class="text-lg font-semibold text-slate-900">Tạo chiến dịch Voucher mới</h3>
          <p class="text-xs text-slate-500 mt-0.5">Lưu vào bảng <code>pgg_phieu_giam_gia</code></p>
        </div>
        <button type="button" onclick="closeCreateVoucherModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        </button>
      </div>

      <form method="POST" action="vouchers.php" class="p-6 space-y-4">
        <input type="hidden" name="action" value="create_voucher" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <div class="flex items-center justify-between">
              <label for="createMaCode" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Mã Code <span class="text-rose-600">*</span></label>
              <button type="button" onclick="generateRandomCode()" class="text-[11px] text-blue-700 hover:underline">Gợi ý mã</button>
            </div>
            <input type="text" name="ma_code" id="createMaCode" required maxlength="30" placeholder="VD: GIAM50K, VIP100K..."
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 font-mono uppercase text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
            <p class="mt-1 text-[11px] text-slate-400">Từ 3-30 ký tự in hoa, số hoặc gạch dưới.</p>
          </div>

          <div>
            <label for="createSoLuong" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Số lượng phát hành <span class="text-rose-600">*</span></label>
            <input type="number" name="so_luong" id="createSoLuong" required min="1" step="1" value="100"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>
        </div>

        <div>
          <label for="createTenPgg" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Tên chiến dịch Voucher <span class="text-rose-600">*</span></label>
          <input type="text" name="ten_pgg" id="createTenPgg" required maxlength="150" placeholder="VD: Giảm 50.000đ cho đơn hàng từ 400.000đ..."
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="createLoaiGiam" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Loại giảm giá <span class="text-rose-600">*</span></label>
            <select name="loai_giam" id="createLoaiGiam" onchange="toggleDiscountType(this, 'createGiaTriUnit')"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
              <option value="TIEN">Số tiền cố định (VNĐ)</option>
              <option value="PHAN_TRAM">Phần trăm chiết khấu (%)</option>
            </select>
          </div>

          <div>
            <label for="createGiaTriGiam" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
              Trị giá giảm (<span id="createGiaTriUnit">VNĐ</span>) <span class="text-rose-600">*</span>
            </label>
            <input type="number" name="gia_tri_giam" id="createGiaTriGiam" required min="1" step="any" placeholder="VD: 50000"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="createDonToiThieu" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Đơn hàng tối thiểu (VNĐ)</label>
            <input type="number" name="don_toi_thieu" id="createDonToiThieu" min="0" step="1000" value="0"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>

          <div>
            <label for="createDiemCanDoi" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Điểm loyalty cần đổi</label>
            <input type="number" name="diem_can_doi" id="createDiemCanDoi" min="0" step="1" value="0"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
            <p class="mt-1 text-[11px] text-slate-400">0 điểm: phát tặng miễn phí.</p>
          </div>
        </div>

        <div>
          <label for="createMaHangYeuCau" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Hạng thành viên tối thiểu <span class="text-rose-600">*</span></label>
          <select name="ma_hang_yeu_cau" id="createMaHangYeuCau"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
            <?php foreach ($tiers as $t): ?>
              <option value="<?= (int)$t['ma_hang'] ?>"><?= htmlspecialchars($t['ten_hang']) ?> (Từ <?= number_format((int)$t['diem_toi_thieu'], 0, ',', '.') ?> điểm)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="createNgayBd" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Ngày giờ bắt đầu <span class="text-rose-600">*</span></label>
            <input type="datetime-local" name="ngay_bd" id="createNgayBd" required
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>

          <div>
            <label for="createNgayKt" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Ngày giờ kết thúc <span class="text-rose-600">*</span></label>
            <input type="datetime-local" name="ngay_kt" id="createNgayKt" required
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
          <button type="button" onclick="closeCreateVoucherModal()"
            class="tap-feedback rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
            Hủy
          </button>
          <button type="submit"
            class="tap-feedback inline-flex items-center gap-1.5 rounded-lg bg-blue-800 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-900 transition-colors">
            Tạo chiến dịch Voucher
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: CẬP NHẬT CẤU HÌNH VOUCHER (#editVoucherModal)                     -->
<!-- ========================================================================= -->
<div id="editVoucherModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeEditVoucherModal()"></div>
  <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative w-full max-w-xl rounded-2xl bg-white shadow-2xl transition-all">
      <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-lg font-semibold text-slate-900">Cấu hình Chiến dịch Voucher</h3>
            <span id="editVoucherCodeBadge" class="font-mono bg-blue-50 text-blue-700 font-bold px-2 py-0.5 rounded text-xs">CODE</span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">Bổ sung lượt phát hành, gia hạn thời gian hoặc điều chỉnh ưu đãi</p>
        </div>
        <button type="button" onclick="closeEditVoucherModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        </button>
      </div>

      <form method="POST" action="vouchers.php" class="p-6 space-y-4">
        <input type="hidden" name="action" value="update_voucher" />
        <input type="hidden" name="ma_pgg" id="editMaPgg" value="" />

        <div>
          <label for="editTenPgg" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Tên chiến dịch Voucher <span class="text-rose-600">*</span></label>
          <input type="text" name="ten_pgg" id="editTenPgg" required maxlength="150"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="editLoaiGiam" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Loại giảm giá <span class="text-rose-600">*</span></label>
            <select name="loai_giam" id="editLoaiGiam" onchange="toggleDiscountType(this, 'editGiaTriUnit')"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
              <option value="TIEN">Số tiền cố định (VNĐ)</option>
              <option value="PHAN_TRAM">Phần trăm chiết khấu (%)</option>
            </select>
          </div>

          <div>
            <label for="editGiaTriGiam" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
              Trị giá giảm (<span id="editGiaTriUnit">VNĐ</span>) <span class="text-rose-600">*</span>
            </label>
            <input type="number" name="gia_tri_giam" id="editGiaTriGiam" required min="1" step="any"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div>
            <label for="editSoLuong" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">SL còn lại <span class="text-rose-600">*</span></label>
            <input type="number" name="so_luong" id="editSoLuong" required min="0" step="1"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
            <p class="mt-1 text-[11px] text-slate-400">Đặt về 0 để dừng đổi.</p>
          </div>

          <div>
            <label for="editDonToiThieu" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Đơn tối thiểu</label>
            <input type="number" name="don_toi_thieu" id="editDonToiThieu" min="0" step="1000"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>

          <div>
            <label for="editDiemCanDoi" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Điểm cần đổi</label>
            <input type="number" name="diem_can_doi" id="editDiemCanDoi" min="0" step="1"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>
        </div>

        <div>
          <label for="editMaHangYeuCau" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Hạng thành viên tối thiểu <span class="text-rose-600">*</span></label>
          <select name="ma_hang_yeu_cau" id="editMaHangYeuCau"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
            <?php foreach ($tiers as $t): ?>
              <option value="<?= (int)$t['ma_hang'] ?>"><?= htmlspecialchars($t['ten_hang']) ?> (Từ <?= number_format((int)$t['diem_toi_thieu'], 0, ',', '.') ?> điểm)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="editNgayBd" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Ngày giờ bắt đầu <span class="text-rose-600">*</span></label>
            <input type="datetime-local" name="ngay_bd" id="editNgayBd" required
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>

          <div>
            <label for="editNgayKt" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Ngày giờ kết thúc (Gia hạn) <span class="text-rose-600">*</span></label>
            <input type="datetime-local" name="ngay_kt" id="editNgayKt" required
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
          <button type="button" onclick="closeEditVoucherModal()"
            class="tap-feedback rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
            Hủy
          </button>
          <button type="submit"
            class="tap-feedback inline-flex items-center gap-1.5 rounded-lg bg-blue-800 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-900 transition-colors">
            Lưu cấu hình chiến dịch
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT ĐIỀU KHIỂN MODAL & GỢI Ý MÃ                                    -->
<!-- ========================================================================= -->
<script>
  const createModal = document.getElementById('createVoucherModal');
  const editModal = document.getElementById('editVoucherModal');

  function toggleDiscountType(selectElem, unitElemId) {
    const unitElem = document.getElementById(unitElemId);
    if (!unitElem) return;
    if (selectElem.value === 'PHAN_TRAM') {
      unitElem.textContent = '%';
    } else {
      unitElem.textContent = 'VNĐ';
    }
  }

  function formatDateTimeLocal(dtStr) {
    if (!dtStr) return '';
    const d = new Date(dtStr);
    if (isNaN(d.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  // Mở modal tạo mới
  window.openCreateVoucherModal = function() {
    const now = new Date();
    const future = new Date();
    future.setDate(future.getDate() + 30);

    const pad = (n) => String(n).padStart(2, '0');
    const nowStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    const futureStr = `${future.getFullYear()}-${pad(future.getMonth() + 1)}-${pad(future.getDate())}T23:59`;

    document.getElementById('createNgayBd').value = nowStr;
    document.getElementById('createNgayKt').value = futureStr;
    document.getElementById('createMaCode').value = '';
    document.getElementById('createTenPgg').value = '';
    document.getElementById('createGiaTriGiam').value = '';
    document.getElementById('createDonToiThieu').value = '0';
    document.getElementById('createDiemCanDoi').value = '0';
    document.getElementById('createSoLuong').value = '100';

    createModal.classList.remove('hidden');
    setTimeout(() => document.getElementById('createMaCode').focus(), 100);
  };

  window.closeCreateVoucherModal = function() {
    createModal.classList.add('hidden');
  };

  // Mở modal chỉnh sửa
  window.openEditVoucherModal = function(v) {
    document.getElementById('editMaPgg').value = v.ma_pgg;
    document.getElementById('editVoucherCodeBadge').textContent = v.ma_code;
    document.getElementById('editTenPgg').value = v.ten_pgg;
    document.getElementById('editLoaiGiam').value = v.loai_giam;
    document.getElementById('editGiaTriGiam').value = Math.round(v.gia_tri_giam);
    document.getElementById('editSoLuong').value = v.so_luong;
    document.getElementById('editDonToiThieu').value = Math.round(v.don_toi_thieu);
    document.getElementById('editDiemCanDoi').value = v.diem_can_doi;
    document.getElementById('editMaHangYeuCau').value = v.ma_hang_yeu_cau;
    document.getElementById('editNgayBd').value = formatDateTimeLocal(v.ngay_bd);
    document.getElementById('editNgayKt').value = formatDateTimeLocal(v.ngay_kt);

    toggleDiscountType(document.getElementById('editLoaiGiam'), 'editGiaTriUnit');
    editModal.classList.remove('hidden');
    setTimeout(() => document.getElementById('editTenPgg').focus(), 100);
  };

  window.closeEditVoucherModal = function() {
    editModal.classList.add('hidden');
  };

  // Gợi ý mã Voucher ngẫu nhiên
  window.generateRandomCode = function() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0192837465';
    let rand = '';
    for (let i = 0; i < 5; i++) {
      rand += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('createMaCode').value = `AURA-${rand}`;
  };

  // Đóng Modal khi nhấn phím Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeCreateVoucherModal();
      closeEditVoucherModal();
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
