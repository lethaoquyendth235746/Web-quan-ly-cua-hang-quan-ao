<?php
/**
 * TẦNG QUẢN TRỊ: Trang Tổng quan Dashboard (admin/index.php)
 * Đổ dữ liệu thực tế từ DashboardService, OrderAdminService, TierAdminService
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/../services/admin/DashboardService.php';
require_once __DIR__ . '/../services/admin/OrderAdminService.php';
require_once __DIR__ . '/../services/admin/TierAdminService.php';

$dashboardService = new DashboardService();
$orderService     = new OrderAdminService();
$tierService      = new TierAdminService();

$kpis             = $dashboardService->getKpiMetrics();
$revenueChartData = $dashboardService->getMonthlyRevenueChartData();
$tierChartData    = $dashboardService->getTierDistributionChartData();
$recentOrders     = $orderService->getAllOrders();
$tiers            = $tierService->getAllTiersWithMemberCount();

$adminPageTitle  = 'Tổng quan Quản trị | Fashion Loyalty';
$activeAdminMenu = 'dashboard';
$useChartJs      = true;

require __DIR__ . '/includes/header.php';

$statusBadges = [
    'CHO_XAC_NHAN' => ['label' => 'Chờ xác nhận', 'class' => 'bg-amber-50 text-amber-800'],
    'DANG_GIAO'    => ['label' => 'Đang giao',    'class' => 'bg-blue-50 text-blue-800'],
    'HOAN_THANH'   => ['label' => 'Hoàn thành',   'class' => 'bg-emerald-50 text-emerald-800'],
    'DA_HUY'       => ['label' => 'Đã hủy',       'class' => 'bg-slate-100 text-slate-600'],
];
?>
<header class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-2xl font-semibold leading-tight text-slate-900">Tổng quan Hệ thống (Dữ liệu CSDL thực tế)</h1>
    <p class="mt-1 text-sm text-slate-500">Số liệu trực tiếp từ CSDL <code>csdl_cua_hang_quan_ao</code> (16 bảng)</p>
  </div>
  <div class="flex items-center gap-2">
    <a href="orders.php" class="tap-feedback rounded-lg bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-slate-700">
      Xử lý đơn chờ duyệt (<?= $kpis['don_cho_duyet'] ?>)
    </a>
  </div>
</header>

<!-- 4 Chỉ số KPI từ CSDL -->
<section aria-label="Chỉ số chính" class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <div class="fade-up rounded-xl border border-slate-200 bg-white p-6" style="--stagger: 0">
    <p class="text-sm text-slate-500">Tổng doanh thu thực tế</p>
    <p class="tabular-nums mt-3 text-2xl font-semibold text-slate-900"><?= formatVND($kpis['tong_doanh_thu']) ?></p>
    <p class="mt-2 text-xs text-slate-500">Trên tổng số <?= $kpis['tong_don_hang'] ?> đơn hàng hợp lệ</p>
  </div>
  <div class="fade-up rounded-xl border border-slate-200 bg-white p-6" style="--stagger: 1">
    <p class="text-sm text-slate-500">Đơn hàng chờ xác nhận</p>
    <p class="tabular-nums mt-3 text-2xl font-semibold text-amber-700"><?= $kpis['don_cho_duyet'] ?> đơn</p>
    <p class="mt-2 text-xs text-slate-500">Cần duyệt để cộng điểm &amp; xét nâng hạng</p>
  </div>
  <div class="fade-up rounded-xl border border-slate-200 bg-white p-6" style="--stagger: 2">
    <p class="text-sm text-slate-500">Tổng thành viên Loyalty</p>
    <p class="tabular-nums mt-3 text-2xl font-semibold text-slate-900"><?= $kpis['tong_thanh_vien'] ?> khách</p>
    <p class="mt-2 text-xs text-slate-500">Đã mở thẻ trong bảng <code>ttd_the_tich_diem</code></p>
  </div>
  <div class="fade-up rounded-xl border border-slate-200 bg-white p-6" style="--stagger: 3">
    <p class="text-sm text-slate-500">Tổng điểm khả dụng đang lưu hành</p>
    <p class="tabular-nums mt-3 text-2xl font-semibold text-emerald-700"><?= number_format($kpis['tong_diem_kha_dung'], 0, ',', '.') ?> điểm</p>
    <p class="mt-2 text-xs text-slate-500">Tổng điểm tích lũy: <?= number_format($kpis['tong_diem_tich_luy'], 0, ',', '.') ?> điểm</p>
  </div>
</section>

<!-- Biểu đồ thống kê thực tế từ CSDL -->
<section aria-label="Biểu đồ" class="fade-up mt-6 grid gap-4 lg:grid-cols-3" style="--stagger: 4">
  <div class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
    <h2 class="text-base font-semibold text-slate-900">Doanh thu &amp; Số đơn theo tháng (Từ bảng <code>dh_don_hang</code>)</h2>
    <div class="relative mt-6 h-72">
      <canvas id="revenueChart" role="img" aria-label="Biểu đồ cột doanh thu và đường số đơn hàng theo tháng"></canvas>
    </div>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="text-base font-semibold text-slate-900">Phân bổ khách theo hạng (<code>ttd_the_tich_diem</code>)</h2>
    <div class="relative mt-6 h-72">
      <canvas id="tierChart" role="img" aria-label="Biểu đồ tròn phân bổ khách hàng theo hạng thành viên"></canvas>
    </div>
  </div>
</section>

<!-- Danh sách Đơn hàng & Cộng điểm thưởng từ CSDL -->
<section aria-labelledby="ordersTitle" class="fade-up mt-6 rounded-xl border border-slate-200 bg-white" style="--stagger: 5">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5">
    <div>
      <h2 id="ordersTitle" class="text-base font-semibold text-slate-900">Đơn đặt hàng &amp; Đối soát điểm thưởng (Bảng <code>dh_don_hang</code>)</h2>
      <p class="mt-1 text-sm text-slate-500">Duyệt đơn sang hoàn thành để kích hoạt cộng điểm và xét nâng hạng tự động.</p>
    </div>
    <a href="orders.php" class="text-xs font-medium text-blue-700 hover:underline">Xem toàn bộ <?= count($recentOrders) ?> đơn &rarr;</a>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th scope="col" class="px-6 py-3 font-medium">Mã đơn</th>
          <th scope="col" class="px-6 py-3 font-medium">Khách hàng &amp; Thẻ</th>
          <th scope="col" class="px-6 py-3 font-medium">Ngày đặt</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Thực trả</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Điểm dự kiến</th>
          <th scope="col" class="px-6 py-3 font-medium">Trạng thái</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($recentOrders as $od): ?>
          <?php $badge = $statusBadges[$od['trang_thai_dh']] ?? ['label' => $od['trang_thai_dh'], 'class' => 'bg-slate-100 text-slate-700']; ?>
          <tr class="align-middle">
            <td class="whitespace-nowrap px-6 py-4 font-mono font-semibold text-slate-900">#<?= htmlspecialchars($od['ma_dh_code']) ?></td>
            <td class="px-6 py-4">
              <p class="font-medium text-slate-900"><?= htmlspecialchars($od['ten_khach_hang']) ?></p>
              <p class="mt-0.5 text-xs text-slate-500">
                <?= htmlspecialchars($od['ten_hang'] ?? 'Chưa có hạng') ?> &middot; Tích lũy: <strong><?= (int)$od['tong_diem_tich_luy'] ?> điểm</strong>
                <?php if ($od['will_upgrade_on_approve'] && $od['trang_thai_dh'] !== 'HOAN_THANH'): ?>
                  <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">Duyệt sẽ lên <?= htmlspecialchars($od['next_tier']['ten_hang']) ?>!</span>
                <?php endif; ?>
              </p>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-slate-600"><?= formatDateVN($od['ngay_dat'], true) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-slate-900"><?= formatVND($od['thanh_tien']) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-emerald-700">+<?= (int)$od['diem_cong_du_kien'] ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4">
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $badge['class'] ?>">
                <?= htmlspecialchars($badge['label']) ?>
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <?php if ($od['trang_thai_dh'] !== 'HOAN_THANH' && $od['trang_thai_dh'] !== 'DA_HUY'): ?>
                <button type="button"
                  data-dev-feature="Duyệt hoàn thành đơn #<?= htmlspecialchars($od['ma_dh_code']) ?> (+<?= (int)$od['diem_cong_du_kien'] ?> điểm &amp; xét nâng hạng tự động)"
                  class="tap-feedback rounded-lg bg-slate-900 px-4 py-2 text-left text-xs font-medium leading-snug text-white hover:bg-slate-700">
                  Duyệt hoàn thành
                  <span class="block font-normal text-slate-300">+ cộng điểm &amp; xét hạng (Đang phát triển)</span>
                </button>
              <?php else: ?>
                <span class="text-xs text-slate-500">Đã đối soát sổ cái</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- Cấu hình 4 Hạng thành viên từ CSDL -->
<section aria-labelledby="tierTitle" class="fade-up mt-6 rounded-xl border border-slate-200 bg-white" style="--stagger: 6">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5">
    <div>
      <h2 id="tierTitle" class="text-base font-semibold text-slate-900">Cấu hình 4 Bậc Hạng Thành Viên (Bảng <code>htv_hang_thanh_vien</code>)</h2>
      <p class="mt-1 text-sm text-slate-500">Mốc điểm tối thiểu, phần trăm giảm trực tiếp và hệ số nhân điểm tích lũy.</p>
    </div>
    <a href="tiers.php" class="text-xs font-medium text-blue-700 hover:underline">Quản lý chi tiết &rarr;</a>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th scope="col" class="px-6 py-3 font-medium">Bậc Hạng</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Mốc điểm tối thiểu</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Chiết khấu đơn</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Hệ số nhân điểm</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Số thành viên</th>
          <th scope="col" class="px-6 py-3 font-medium">Mô tả ưu đãi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($tiers as $t): ?>
          <tr>
            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-900">
              <span class="inline-flex items-center gap-2">
                <span class="h-3 w-3 rounded-full" style="background: <?= htmlspecialchars($t['mau_sac_the']) ?>"></span>
                <?= htmlspecialchars($t['ten_hang']) ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right"><?= number_format((int)$t['diem_toi_thieu'], 0, ',', '.') ?> điểm</td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-emerald-700"><?= number_format((float)$t['phan_tram_giam'], 0) ?>%</td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-blue-700">x<?= number_format((float)$t['he_so_tich_diem'], 2) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold"><?= (int)$t['so_thanh_vien'] ?> khách</td>
            <td class="px-6 py-4 text-xs text-slate-500"><?= htmlspecialchars($t['mo_ta_uu_dai'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
  (() => {
    const revLabels = <?= json_encode($revenueChartData['labels']) ?>;
    const revData   = <?= json_encode($revenueChartData['revenues']) ?>;
    const ordData   = <?= json_encode($revenueChartData['orders']) ?>;

    const tierLabels = <?= json_encode($tierChartData['labels']) ?>;
    const tierCounts = <?= json_encode($tierChartData['counts']) ?>;
    const tierColors = <?= json_encode($tierChartData['colors']) ?>;

    new Chart(document.getElementById('revenueChart'), {
      data: {
        labels: revLabels,
        datasets: [
          {
            type: 'bar',
            label: 'Doanh thu (triệu đ)',
            data: revData,
            backgroundColor: '#0F766E',
            borderRadius: 6,
            maxBarThickness: 42,
            yAxisID: 'y',
          },
          {
            type: 'line',
            label: 'Số đơn hàng',
            data: ordData,
            borderColor: '#475569',
            backgroundColor: '#475569',
            pointRadius: 4,
            tension: 0.3,
            yAxisID: 'y1',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, color: '#475569' } } },
        scales: {
          x: { grid: { display: false }, ticks: { color: '#64748B' } },
          y: { beginAtZero: true, grid: { color: '#E2E8F0' }, ticks: { color: '#64748B' }, title: { display: true, text: 'Triệu đồng', color: '#64748B' } },
          y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { color: '#64748B', precision: 0 }, title: { display: true, text: 'Đơn', color: '#64748B' } },
        },
      },
    });

    new Chart(document.getElementById('tierChart'), {
      type: 'doughnut',
      data: {
        labels: tierLabels,
        datasets: [{ data: tierCounts, backgroundColor: tierColors, borderColor: '#FFFFFF', borderWidth: 2 }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '64%',
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, color: '#475569' } } },
      },
    });
  })();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

