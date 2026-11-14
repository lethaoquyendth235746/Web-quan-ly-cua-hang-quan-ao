<?php
/**
 * TẦNG QUẢN TRỊ: Cấu hình 4 Bậc Hạng Thành Viên & Danh sách Thẻ Tích Điểm (admin/tiers.php)
 * Đổ dữ liệu thực tế từ bảng htv_hang_thanh_vien và ttd_the_tich_diem
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/admin/TierAdminService.php';

$tierService = new TierAdminService();
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
  <p class="mt-2 max-w-2xl text-sm text-slate-500">Dữ liệu thực tế từ bảng <code>htv_hang_thanh_vien</code> (4 bậc hạng) và <code>ttd_the_tich_diem</code> (<?= count($memberCards) ?> thẻ thành viên).</p>
</header>

<!-- Bảng Cấu hình 4 Bậc Hạng Thành Viên -->
<section aria-label="Danh sách hạng" class="fade-up mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-200 px-6 py-5">
    <h2 class="text-base font-semibold text-slate-900">4 Bậc Hạng Thành Viên (Bảng <code>htv_hang_thanh_vien</code>)</h2>
    <p class="mt-1 text-sm text-slate-500">Quy chuẩn tích điểm cơ bản: 10.000đ = 1 điểm thưởng.</p>
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
        <?php foreach ($tiers as $t): ?>
          <tr class="align-middle">
            <td class="whitespace-nowrap px-6 py-4">
              <span class="inline-flex items-center gap-2.5 font-semibold text-slate-900">
                <span class="h-3 w-3 rounded-full" style="background: <?= htmlspecialchars($t['mau_sac_the']) ?>"></span>
                <?= htmlspecialchars($t['ten_hang']) ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-slate-900"><?= number_format((int)$t['diem_toi_thieu'], 0, ',', '.') ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-emerald-700"><?= number_format((float)$t['phan_tram_giam'], 0) ?>%</td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-blue-700">x<?= number_format((float)$t['he_so_tich_diem'], 2) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-slate-800"><?= (int)$t['so_thanh_vien'] ?> thành viên</td>
            <td class="px-6 py-4 text-xs text-slate-600"><?= htmlspecialchars($t['mo_ta_uu_dai'] ?? '') ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <button type="button"
                data-dev-feature="Chỉnh sửa thông số <?= htmlspecialchars($t['ten_hang']) ?> trong bảng htv_hang_thanh_vien"
                class="tap-feedback rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-slate-500 hover:text-slate-900">
                Chỉnh sửa (Đang phát triển)
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
    <h2 id="memberCardsTitle" class="text-base font-semibold text-slate-900">Danh sách Thẻ Tích Điểm Khách Hàng (Bảng <code>ttd_the_tich_diem</code>)</h2>
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
          <tr>
            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs font-semibold text-slate-900"><?= htmlspecialchars($mc['ma_so_the']) ?></td>
            <td class="px-6 py-4">
              <p class="font-medium text-slate-900"><?= htmlspecialchars($mc['ho_ten']) ?></p>
              <p class="text-xs text-slate-500"><?= htmlspecialchars($mc['email']) ?> &middot; <?= htmlspecialchars($mc['sdt']) ?></p>
            </td>
            <td class="whitespace-nowrap px-6 py-4">
              <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-800">
                <span class="h-2 w-2 rounded-full" style="background: <?= htmlspecialchars($mc['mau_sac_the']) ?>"></span>
                <?= htmlspecialchars($mc['ten_hang']) ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-slate-900"><?= number_format((int)$mc['tong_diem_tich_luy'], 0, ',', '.') ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-emerald-700"><?= number_format((int)$mc['diem_kha_dung'], 0, ',', '.') ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4 text-slate-600"><?= formatDateVN($mc['ngay_cap']) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <a href="../index.php?page=loyalty&switch_user=<?= (int)$mc['ma_nd'] ?>"
                class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                Mở Thẻ trên Store &rarr;
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

