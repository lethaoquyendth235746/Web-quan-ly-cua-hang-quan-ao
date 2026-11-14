<?php
/**
 * TẦNG QUẢN TRỊ: Quản lý Nhập hàng & Tồn kho Biến thể SKU (admin/inventory.php)
 * Đổ dữ liệu thực tế từ bảng bt_bien_the_sp (20 SKU) và pnk_phieu_nhap_kho (20 phiếu nhập)
 * Xử lý hoàn chỉnh Lập phiếu nhập kho & Tự động cập nhật tồn kho bằng Transaction
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/../services/admin/InventoryAdminService.php';

$inventoryService = new InventoryAdminService();

// =============================================================================
// XỬ LÝ POST THEO MÔ HÌNH PRG (POST - REDIRECT - GET)
// Phải chạy trước khi nạp header HTML để tránh lỗi "headers already sent"
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_import_receipt') {
    try {
        $res = $inventoryService->createImportReceipt($_POST);
        $pnkCode = '#PNK' . str_pad((string)$res['ma_pnk'], 2, '0', STR_PAD_LEFT);
        $_SESSION['admin_flash'] = [
            'type'    => 'success',
            'message' => "Đã lập phiếu {$pnkCode} nhập +{$res['so_luong_nhap']} SP cho mã SKU {$res['ma_sku']} (Tồn mới: {$res['ton_moi']})!"
        ];
    } catch (Throwable $e) {
        $_SESSION['admin_flash'] = [
            'type'    => 'error',
            'message' => $e->getMessage()
        ];
    }
    header('Location: inventory.php');
    exit;
}

$skuList        = $inventoryService->getAllSkuInventory();
$importReceipts = $inventoryService->getImportReceipts();

// Thu thập danh sách nhà cung cấp gợi ý không trùng lặp
$suppliers = array_values(array_filter(array_unique(array_map(function($r) {
    return trim($r['nha_cung_cap'] ?? '');
}, $importReceipts))));

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
      <p class="mt-1 text-sm text-slate-500">
        Đối soát chính xác <?= count($skuList) ?> mã biến thể SKU: <strong>Tồn kho = Tổng nhập (<code>pnk_phieu_nhap_kho</code>) - Đã bán (<code>ctdh_chi_tiet_dh</code>)</strong>.
      </p>
    </div>
    <button type="button" id="btnOpenImportModal" onclick="openImportModal()"
      class="tap-feedback inline-flex items-center justify-center gap-2 rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-900 transition-colors">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
      Lập phiếu nhập kho
    </button>
  </div>
</header>

<!-- Thanh công cụ tìm kiếm & lọc tồn kho nhanh -->
<section aria-label="Công cụ tồn kho" class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center flex-1">
    <div class="relative flex-1 sm:max-w-md">
      <label for="stockSearch" class="sr-only">Tìm theo mã SKU hoặc tên sản phẩm</label>
      <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6.5" /><path d="M16 16l4 4" stroke-linecap="round" /></svg>
      <input id="stockSearch" type="search" placeholder="Tìm theo mã SKU, màu sắc hoặc tên sản phẩm..."
        class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
    </div>
    <div>
      <label for="stockStatusFilter" class="sr-only">Lọc nhanh theo mức tồn kho</label>
      <select id="stockStatusFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-56">
        <option value="">Tất cả mức tồn kho</option>
        <option value="low">Sắp hết hàng (&lt; 15 SP)</option>
        <option value="out">Hết hàng (0 SP)</option>
        <option value="available">Còn hàng dồi dào (&ge; 15 SP)</option>
      </select>
    </div>
  </div>
</section>

<!-- Bảng Tồn kho Biến thể SKU từ bảng bt_bien_the_sp -->
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
          <?php 
            $stock = (int)$sku['so_luong_ton']; 
            $statusGroup = $stock === 0 ? 'out' : ($stock < 15 ? 'low' : 'available');
          ?>
          <tr class="sku-row align-middle hover:bg-slate-50/70 transition-colors"
            data-search="<?= htmlspecialchars(mb_strtolower($sku['ma_sku'] . ' ' . $sku['ten_sp'] . ' ' . $sku['mau_sac'] . ' ' . $sku['kich_co'])) ?>"
            data-stock-group="<?= $statusGroup ?>">
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
                data-open-import
                data-ma-bt="<?= (int)$sku['ma_bt'] ?>"
                onclick="openImportModal(<?= (int)$sku['ma_bt'] ?>)"
                class="tap-feedback rounded-lg border border-blue-200 bg-blue-50/60 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 hover:border-blue-300 transition-colors">
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
    <h2 id="receiptsTitle" class="text-base font-semibold text-slate-900">
      Lịch sử Chứng từ Phiếu nhập kho (Bảng <code>pnk_phieu_nhap_kho</code> &mdash; <?= count($importReceipts) ?> phiếu)
    </h2>
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
          <tr class="hover:bg-slate-50/70 transition-colors">
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

<!-- ========================================================================= -->
<!-- MODAL: LẬP PHIẾU NHẬP KHO MỚI (#importReceiptModal)                         -->
<!-- ========================================================================= -->
<div id="importReceiptModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeImportModal()"></div>
  <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative w-full max-w-xl rounded-2xl bg-white shadow-2xl transition-all">
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
        <div>
          <h3 class="text-lg font-semibold text-slate-900">Lập phiếu nhập kho mới</h3>
          <p class="text-xs text-slate-500 mt-0.5">Lưu vào bảng <code>pnk_phieu_nhap_kho</code> &amp; tự động cộng tồn kho <code>bt_bien_the_sp</code></p>
        </div>
        <button type="button" onclick="closeImportModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        </button>
      </div>

      <!-- Modal Form -->
      <form id="importReceiptForm" method="POST" action="inventory.php" class="p-6 space-y-4">
        <input type="hidden" name="action" value="create_import_receipt" />

        <!-- 1. Chọn biến thể SKU -->
        <div>
          <label for="importMaBt" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
            Chọn biến thể SKU cần nhập <span class="text-rose-600">*</span>
          </label>
          <select name="ma_bt" id="importMaBt" required
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
            <option value="">-- Chọn một biến thể SKU --</option>
            <?php foreach ($skuList as $sku): ?>
              <option value="<?= (int)$sku['ma_bt'] ?>"
                data-sku="<?= htmlspecialchars($sku['ma_sku']) ?>"
                data-stock="<?= (int)$sku['so_luong_ton'] ?>"
                data-price="<?= (float)$sku['gia_ban_rieng'] ?>"
                data-name="<?= htmlspecialchars($sku['ten_sp']) ?>"
                data-classify="<?= htmlspecialchars($sku['kich_co'] . ' / ' . $sku['mau_sac']) ?>">
                [<?= htmlspecialchars($sku['ma_sku']) ?>] <?= htmlspecialchars($sku['ten_sp']) ?> &mdash; <?= htmlspecialchars($sku['kich_co']) ?> / <?= htmlspecialchars($sku['mau_sac']) ?> (Tồn hiện tại: <?= (int)$sku['so_luong_ton'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Khung xem trước thông tin biến thể & Tồn kho dự kiến -->
        <div id="skuPreviewBox" class="rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 text-xs text-slate-600 space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="text-slate-500">Mặt hàng:</span>
            <span id="previewProductName" class="font-medium text-slate-900">Chưa chọn</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-500">Tồn kho hiện tại:</span>
            <span id="previewCurrentStock" class="font-semibold text-slate-800">--</span>
          </div>
          <div class="flex items-center justify-between border-t border-slate-200/80 pt-1.5">
            <span class="font-medium text-slate-700">Tồn kho dự kiến sau nhập:</span>
            <span id="previewNewStock" class="font-bold text-emerald-700 text-sm">--</span>
          </div>
        </div>

        <!-- 2. Số lượng nhập & Giá vốn nhập -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="importSoLuong" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
              Số lượng nhập <span class="text-rose-600">*</span>
            </label>
            <input type="number" name="so_luong_nhap" id="importSoLuong" min="1" step="1" value="20" required
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
            <p class="mt-1 text-[11px] text-slate-400">Số lượng sản phẩm nhập thêm (tối thiểu 1).</p>
          </div>

          <div>
            <label for="importGiaNhap" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
              Giá vốn nhập (VNĐ / SP) <span class="text-rose-600">*</span>
            </label>
            <input type="number" name="gia_nhap" id="importGiaNhap" min="0" step="1000" placeholder="VD: 180000" required
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
            <p class="mt-1 text-[11px] text-slate-400">Giá mua vào từ nhà cung cấp cho 1 đơn vị.</p>
          </div>
        </div>

        <!-- Khung tính tổng tiền vốn phiếu nhập tự động theo thời gian thực -->
        <div class="flex items-center justify-between rounded-xl bg-blue-50/80 border border-blue-100 px-4 py-2.5">
          <span class="text-xs font-medium text-blue-900">Tổng tiền vốn phiếu nhập:</span>
          <span id="previewTotalCost" class="text-base font-bold text-blue-800">0đ</span>
        </div>

        <!-- 3. Nhà cung cấp -->
        <div>
          <label for="importNhaCungCap" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
            Nhà cung cấp
          </label>
          <input type="text" name="nha_cung_cap" id="importNhaCungCap" maxlength="150" list="supplierSuggestions"
            placeholder="VD: Xưởng May Việt Tiến, Tổng Công ty May Nhà Bè..."
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          <datalist id="supplierSuggestions">
            <?php foreach ($suppliers as $sup): ?>
              <option value="<?= htmlspecialchars($sup) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>

        <!-- 4. Ghi chú -->
        <div>
          <label for="importGhiChu" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
            Ghi chú phiếu nhập
          </label>
          <input type="text" name="ghi_chu" id="importGhiChu" maxlength="255"
            placeholder="VD: Nhập bổ sung đợt bán hàng cao điểm cuối năm..."
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
        </div>

        <!-- Footer Buttons -->
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
          <button type="button" onclick="closeImportModal()"
            class="tap-feedback rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
            Hủy
          </button>
          <button type="submit" id="btnSubmitImport"
            class="tap-feedback inline-flex items-center gap-1.5 rounded-lg bg-blue-800 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-900 transition-colors">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Xác nhận nhập kho
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT ĐIỀU KHIỂN BỘ LỌC TỒN KHO & MODAL NHẬP HÀNG                     -->
<!-- ========================================================================= -->
<script>
  // 1. Điều khiển bộ lọc tồn kho (Tìm kiếm text + Lọc nhanh mức tồn)
  (() => {
    const searchInput = document.getElementById('stockSearch');
    const statusFilter = document.getElementById('stockStatusFilter');
    const rows = Array.from(document.querySelectorAll('.sku-row'));
    const summary = document.getElementById('stockSummary');

    function filterStock() {
      const q = (searchInput?.value || '').trim().toLowerCase();
      const statusGroup = statusFilter?.value || '';
      let visibleCount = 0;

      rows.forEach((row) => {
        const matchSearch = !q || row.dataset.search.includes(q);
        const matchStatus = !statusGroup || row.dataset.stockGroup === statusGroup;
        const show = matchSearch && matchStatus;
        row.classList.toggle('hidden', !show);
        if (show) visibleCount++;
      });

      if (summary) {
        summary.textContent = `Hiển thị ${visibleCount} trên ${rows.length} biến thể SKU trong CSDL`;
      }
    }

    searchInput?.addEventListener('input', filterStock);
    statusFilter?.addEventListener('change', filterStock);
  })();

  // 2. Điều khiển Modal Lập phiếu nhập kho
  const importModal = document.getElementById('importReceiptModal');
  const importMaBt = document.getElementById('importMaBt');
  const importSoLuong = document.getElementById('importSoLuong');
  const importGiaNhap = document.getElementById('importGiaNhap');
  const previewProductName = document.getElementById('previewProductName');
  const previewCurrentStock = document.getElementById('previewCurrentStock');
  const previewNewStock = document.getElementById('previewNewStock');
  const previewTotalCost = document.getElementById('previewTotalCost');

  function updateImportPreview() {
    const selectedOption = importMaBt?.options[importMaBt.selectedIndex];
    const qty = parseInt(importSoLuong?.value || '0', 10);
    const unitPrice = parseFloat(importGiaNhap?.value || '0');

    if (selectedOption && selectedOption.value) {
      const sku = selectedOption.dataset.sku || '';
      const name = selectedOption.dataset.name || '';
      const classify = selectedOption.dataset.classify || '';
      const stock = parseInt(selectedOption.dataset.stock || '0', 10);
      const retailPrice = parseFloat(selectedOption.dataset.price || '0');

      previewProductName.textContent = `[${sku}] ${name} (${classify})`;
      previewCurrentStock.textContent = `${stock} SP`;

      const safeQty = Math.max(0, qty);
      const newStock = stock + safeQty;
      previewNewStock.textContent = `${newStock} SP (+${safeQty})`;

      // Nếu ô giá nhập đang trống, gợi ý giá vốn = 50% giá bán lẻ
      if (!importGiaNhap.value && retailPrice > 0) {
        importGiaNhap.value = Math.round(retailPrice * 0.5);
      }
    } else {
      previewProductName.textContent = 'Chưa chọn';
      previewCurrentStock.textContent = '--';
      previewNewStock.textContent = '--';
    }

    // Tính tổng tiền vốn phiếu nhập
    const currentPrice = parseFloat(importGiaNhap?.value || '0');
    const total = Math.max(0, qty) * Math.max(0, currentPrice);
    previewTotalCost.textContent = `${total.toLocaleString('vi-VN')}đ`;
  }

  window.openImportModal = function(targetMaBt = null) {
    if (targetMaBt) {
      importMaBt.value = targetMaBt;
    } else if (!importMaBt.value && importMaBt.options.length > 1) {
      importMaBt.selectedIndex = 1;
    }

    updateImportPreview();
    importModal.classList.remove('hidden');

    // Tự động focus vào ô số lượng nhập để thao tác nhanh
    setTimeout(() => {
      importSoLuong?.focus();
      importSoLuong?.select();
    }, 100);
  };

  window.closeImportModal = function() {
    importModal.classList.add('hidden');
  };

  importMaBt?.addEventListener('change', updateImportPreview);
  importSoLuong?.addEventListener('input', updateImportPreview);
  importGiaNhap?.addEventListener('input', updateImportPreview);

  // Đóng modal khi nhấn phím Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !importModal.classList.contains('hidden')) {
      closeImportModal();
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
