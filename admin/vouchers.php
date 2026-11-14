<?php
/**
 * TẦNG QUẢN TRỊ: Quản lý Chiến dịch Voucher Loyalty & Ví Voucher Khách hàng (admin/vouchers.php)
 * Đổ dữ liệu thực tế từ bảng pgg_phieu_giam_gia và vkh_vi_voucher_kh
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/../services/admin/VoucherAdminService.php';

$voucherService = new VoucherAdminService();
$vouchers       = $voucherService->getAllVouchers();
$walletLogs     = $voucherService->getCustomerVoucherWalletLogs();

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
      <p class="mt-1 text-sm text-slate-500">Dữ liệu thực tế từ bảng <code>pgg_phieu_giam_gia</code> (<?= count($vouchers) ?> chiến dịch) và lịch sử đổi ví <code>vkh_vi_voucher_kh</code>.</p>
    </div>
    <button type="button" data-dev-feature="Tạo chiến dịch Voucher mới vào bảng pgg_phieu_giam_gia"
      class="tap-feedback inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-900">
      <span aria-hidden="true" class="text-base leading-none">+</span>
      Tạo Voucher mới (Đang phát triển)
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
          ?>
          <tr class="align-middle">
            <td class="whitespace-nowrap px-6 py-4">
              <span class="font-mono font-semibold text-slate-900"><?= htmlspecialchars($v['ma_code']) ?></span>
              <span class="mt-1 block inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-800">Đang chạy</span>
            </td>
            <td class="px-6 py-4 font-medium text-slate-800"><?= htmlspecialchars($v['ten_pgg']) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-emerald-700"><?= $discountText ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right text-slate-600"><?= formatVND($v['don_toi_thieu']) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-slate-900"><?= number_format((int)$v['diem_can_doi'], 0, ',', '.') ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4">
              <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700">
                <span class="h-2.5 w-2.5 rounded-full" style="background: <?= htmlspecialchars($v['mau_sac_the']) ?>"></span>
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
              <button type="button"
                data-dev-feature="Chỉnh sửa / Khóa chiến dịch Voucher <?= htmlspecialchars($v['ma_code']) ?>"
                class="tap-feedback rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-slate-500">
                Cấu hình (Đang phát triển)
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="border-t border-slate-200 px-6 py-4 text-sm text-slate-500">
    Hiển thị <?= count($vouchers) ?> chiến dịch Voucher đang hoạt động trong CSDL
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
          <tr>
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

<?php require __DIR__ . '/includes/footer.php'; ?>

