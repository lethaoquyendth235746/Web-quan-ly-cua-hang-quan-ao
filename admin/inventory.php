<?php
/**
 * TẦNG QUẢN TRỊ: Quản lý Nhập hàng & Tồn kho Biến thể SKU (admin/inventory.php)
 * Đổ dữ liệu thực tế từ bảng bt_bien_the_sp (20 SKU) và pnk_phieu_nhap_kho (20 phiếu nhập)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/../services/admin/InventoryAdminService.php';

$inventoryService = new InventoryAdminService();
$skuList          = $inventoryService->getAllSkuInventory();
$importReceipts   = $inventoryService->getImportReceipts();

$adminPageTitle  = 'Quản lý Nhập hàng & Tồn kho | Fashion Loyalty';
$activeAdminMenu = 'inventory';

require __DIR__ . '/includes/header.php';
?>
<header>
  <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-slate-500">
    <a href="index.php" class="hover:text-slate-900">Tổng quan</a>
    <span aria-hidden="true">/</span>
    <span class="text-slate-700">Quản lý nhập hàng &amp; Tồn kho</span>
  </nav>
  <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold leading-tight text-slate-900">Quản lý Nhập hàng &amp; Tồn kho SKU</h1>
      <p class="mt-1 text-sm text-slate-500">Đối soát chính xác 20 mã biến thể SKU: <strong>Tồn kho = Tổng nhập (<code>pnk_phieu_nhap_kho</code>) - Đã bán (<code>ctdh_chi_tiet_dh</code>)</strong>.</p>
    </div>
    <button type="button" data-dev-feature="Lập phiếu nhập kho mới vào bảng pnk_phieu_nhap_kho &amp; tự động cộng tồn kho bt_bien_the_sp"
      class="tap-feedback inline-flex items-center justify-center gap-2 rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-900">
      <span aria-hidden="true" class="text-base leading-none">+</span>
      Lập phiếu nhập kho (Đang phát triển)
    </button>
  </div>
</header>

<section aria-label="Công cụ tồn kho" class="mt-8 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div class="relative">
    <label for="stockSearch" class="sr-only">Tìm theo mã SKU hoặc tên sản phẩm</label>
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6.5" /><path d="M16 16l4 4" stroke-linecap="round" /></svg>
    <input id="stockSearch" type="search" placeholder="Tìm theo mã SKU, màu sắc hoặc tên sản phẩm..."
      class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 md:w-96" />
  </div>
</section>

<!-- Bảng Tồn kho 20 Biến thể SKU từ bảng bt_bien_the_sp -->
<section aria-label="Bảng tồn kho" class="fade-up mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th scope="col" class="px-6 py-3 font-medium">Mã SKU</th>
          <th scope="col" class="px-6 py-3 font-medium">Tên sản phẩm</th>
          <th scope="col" class="px-6 py-3 font-medium">Phân loại (Size / Màu)</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Giá bán riêng</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Tổng nhập</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Đã bán</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Tồn kho thực tế</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Thao tác</th>
        </tr>
      </thead>
      <tbody id="stockRows" class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($skuList as $sku): ?>
          <?php $stock = (int)$sku['so_luong_ton']; ?>
          <tr class="sku-row align-middle"
            data-search="<?= htmlspecialchars(mb_strtolower($sku['ma_sku'] . ' ' . $sku['ten_sp'] . ' ' . $sku['mau_sac'] . ' ' . $sku['kich_co'])) ?>">
            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs font-semibold text-slate-800"><?= htmlspecialchars($sku['ma_sku']) ?></td>
            <td class="px-6 py-4 font-medium text-slate-900"><?= htmlspecialchars($sku['ten_sp']) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-slate-600">
              <span class="inline-flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-full border border-slate-300" style="background: <?= htmlspecialchars(getColorHex($sku['mau_sac'])) ?>"></span>
                <?= htmlspecialchars($sku['kich_co']) ?> / <?= htmlspecialchars($sku['mau_sac']) ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right text-slate-900"><?= formatVND($sku['gia_ban_rieng']) ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right text-slate-600"><?= (int)$sku['tong_nhap'] ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right text-blue-700"><?= (int)$sku['tong_da_ban'] ?></td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <?php if ($stock === 0): ?>
                <span class="font-semibold text-red-600">0</span>
                <span class="ml-2 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Hết hàng</span>
              <?php elseif ($stock < 15): ?>
                <span class="font-semibold text-amber-700"><?= $stock ?></span>
                <span class="ml-2 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Sắp hết</span>
              <?php else: ?>
                <span class="font-semibold text-emerald-700"><?= $stock ?></span>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <button type="button"
                data-dev-feature="Nhập thêm hàng cho mã SKU <?= htmlspecialchars($sku['ma_sku']) ?>"
                class="tap-feedback rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-slate-500 hover:text-slate-900">
                + Nhập thêm
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p id="stockSummary" class="border-t border-slate-200 px-6 py-4 text-sm text-slate-500">
    Hiển thị <?= count($skuList) ?> trên <?= count($skuList) ?> biến thể SKU trong CSDL
  </p>
</section>

<!-- Lịch sử Chứng từ Phiếu nhập kho từ bảng pnk_phieu_nhap_kho -->
<section aria-labelledby="receiptsTitle" class="fade-up mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-200 px-6 py-5">
    <h2 id="receiptsTitle" class="text-base font-semibold text-slate-900">Lịch sử Chứng từ Phiếu nhập kho (Bảng <code>pnk_phieu_nhap_kho</code> — <?= count($importReceipts) ?> phiếu)</h2>
    <p class="mt-1 text-sm text-slate-500">Toàn bộ chứng từ nhập kho đầu kỳ và người lập phiếu trong CSDL.</p>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th class="px-6 py-3 font-medium">Mã PNK</th>
          <th class="px-6 py-3 font-medium">Ngày nhập</th>
          <th class="px-6 py-3 font-medium">Mã SKU &amp; Sản phẩm</th>
          <th class="px-6 py-3 text-right font-medium">SL Nhập</th>
          <th class="px-6 py-3 text-right font-medium">Giá vốn nhập</th>
          <th class="px-6 py-3 font-medium">Nhà cung cấp</th>
          <th class="px-6 py-3 font-medium">Nhân viên nhập</th>
          <th class="px-6 py-3 font-medium">Ghi chú</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($importReceipts as $rec): ?>
          <tr>
            <td class="whitespace-nowrap px-6 py-3.5 font-mono text-xs font-semibold text-slate-700">#PNK<?= str_pad((string)$rec['ma_pnk'], 2, '0', STR_PAD_LEFT) ?></td>
            <td class="whitespace-nowrap px-6 py-3.5 text-slate-600"><?= formatDateVN($rec['ngay_nhap'], true) ?></td>
            <td class="px-6 py-3.5">
              <p class="font-mono text-xs font-semibold text-slate-900"><?= htmlspecialchars($rec['ma_sku']) ?></p>
              <p class="text-xs text-slate-500"><?= htmlspecialchars($rec['ten_sp']) ?> (<?= htmlspecialchars($rec['kich_co']) ?> - <?= htmlspecialchars($rec['mau_sac']) ?>)</p>
            </td>
            <td class="whitespace-nowrap px-6 py-3.5 text-right font-semibold text-emerald-700">+<?= (int)$rec['so_luong_nhap'] ?></td>
            <td class="whitespace-nowrap px-6 py-3.5 text-right text-slate-800"><?= formatVND($rec['gia_nhap']) ?></td>
            <td class="px-6 py-3.5 text-slate-700"><?= htmlspecialchars($rec['nha_cung_cap'] ?? '-') ?></td>
            <td class="whitespace-nowrap px-6 py-3.5 text-slate-700"><?= htmlspecialchars($rec['nguoi_nhap_ten']) ?></td>
            <td class="px-6 py-3.5 text-xs text-slate-500"><?= htmlspecialchars($rec['ghi_chu'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
  (() => {
    const searchInput = document.getElementById('stockSearch');
    const rows = Array.from(document.querySelectorAll('.sku-row'));
    const summary = document.getElementById('stockSummary');

    searchInput?.addEventListener('input', () => {
      const q = searchInput.value.trim().toLowerCase();
      let count = 0;
      rows.forEach((row) => {
        const show = !q || row.dataset.search.includes(q);
        row.classList.toggle('hidden', !show);
        if (show) count++;
      });
      if (summary) summary.textContent = `Hiển thị ${count} trên ${rows.length} biến thể SKU trong CSDL`;
    });
  })();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

