<?php
/**
 * TẦNG QUẢN TRỊ: Quản lý Danh mục & Sản phẩm thời trang (admin/products.php)
 * Đổ dữ liệu thực tế từ bảng dm_danh_muc_sp, sp_san_pham, bt_bien_the_sp
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/admin/ProductAdminService.php';

$productAdminService = new ProductAdminService();
$categories = $productAdminService->getAllCategories();
$products   = $productAdminService->getAllProducts();

$adminPageTitle  = 'Danh mục & Sản phẩm | Fashion Loyalty';
$activeAdminMenu = 'products';

require __DIR__ . '/includes/header.php';
?>
<header>
  <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-slate-500">
    <a href="index.php" class="hover:text-slate-900">Tổng quan</a>
    <span aria-hidden="true">/</span>
    <span class="text-slate-700">Danh mục &amp; Sản phẩm</span>
  </nav>
  <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold leading-tight text-slate-900">Quản lý Danh mục &amp; Sản phẩm thời trang</h1>
      <p class="mt-1 text-sm text-slate-500">Dữ liệu thực tế từ bảng <code>sp_san_pham</code> (<?= count($products) ?> sản phẩm) và <code>dm_danh_muc_sp</code> (<?= count($categories) ?> danh mục).</p>
    </div>
    <button type="button" data-dev-feature="Thêm sản phẩm mới &amp; Upload ảnh vào bảng sp_san_pham"
      class="tap-feedback inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
      <span aria-hidden="true" class="text-base leading-none">+</span>
      Thêm sản phẩm mới (Đang phát triển)
    </button>
  </div>
</header>

<!-- Công cụ lọc sản phẩm -->
<section aria-label="Công cụ lọc" class="mt-8 flex flex-col gap-3 sm:flex-row">
  <div class="relative">
    <label for="productSearch" class="sr-only">Tìm sản phẩm</label>
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6.5" /><path d="M16 16l4 4" stroke-linecap="round" /></svg>
    <input id="productSearch" type="search" placeholder="Tìm theo tên sản phẩm, chất liệu hoặc mã..."
      class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-80" />
  </div>
  <div>
    <label for="categoryFilter" class="sr-only">Lọc theo danh mục</label>
    <select id="categoryFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-64">
      <option value="">Tất cả danh mục trong CSDL</option>
      <?php foreach ($categories as $cat): ?>
        <?php if ($cat['ma_dm_cha'] !== null): ?>
          <option value="<?= (int)$cat['ma_dm'] ?>"><?= htmlspecialchars($cat['ten_dm']) ?> (<?= (int)$cat['so_san_pham'] ?>)</option>
        <?php endif; ?>
      <?php endforeach; ?>
    </select>
  </div>
</section>

<!-- Danh sách 10 sản phẩm thực tế từ bảng sp_san_pham -->
<section aria-label="Danh sách sản phẩm" class="fade-up mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th scope="col" class="px-6 py-3 font-medium">Hình ảnh</th>
          <th scope="col" class="px-6 py-3 font-medium">Tên sản phẩm &amp; Chất liệu</th>
          <th scope="col" class="px-6 py-3 font-medium">Danh mục</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Giá bán / Niêm yết</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Số SKU / Tồn</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Lượt xem</th>
          <th scope="col" class="px-6 py-3 font-medium">Trạng thái</th>
          <th scope="col" class="px-6 py-3 text-right font-medium">Thao tác</th>
        </tr>
      </thead>
      <tbody id="productRows" class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($products as $p): ?>
          <tr class="product-row align-middle"
            data-name="<?= htmlspecialchars(mb_strtolower($p['ten_sp'] . ' ' . $p['ma_sp_code'] . ' ' . ($p['chat_lieu'] ?? ''))) ?>"
            data-cat="<?= (int)$p['ma_dm'] ?>">
            <td class="px-6 py-4">
              <img src="<?= htmlspecialchars($p['anh_chinh_url']) ?>" alt="<?= htmlspecialchars($p['ten_sp']) ?>" class="h-12 w-12 rounded-lg object-cover border border-slate-200" />
            </td>
            <td class="px-6 py-4">
              <p class="font-medium text-slate-900"><?= htmlspecialchars($p['ten_sp']) ?></p>
              <p class="mt-0.5 text-xs text-slate-500">
                Mã: <span class="font-mono text-slate-700"><?= htmlspecialchars($p['ma_sp_code']) ?></span> &middot; Chất liệu: <?= htmlspecialchars($p['chat_lieu'] ?? '-') ?>
              </p>
            </td>
            <td class="whitespace-nowrap px-6 py-4">
              <p class="text-slate-800 font-medium"><?= htmlspecialchars($p['ten_dm']) ?></p>
              <p class="text-xs text-slate-500"><?= htmlspecialchars($p['ten_dm_cha'] ?? 'Danh mục gốc') ?></p>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <p class="font-semibold text-slate-900"><?= formatVND($p['gia_khuyen_mai']) ?></p>
              <?php if ((float)$p['gia_niem_yet'] > (float)$p['gia_khuyen_mai']): ?>
                <p class="text-xs text-slate-400 line-through"><?= formatVND($p['gia_niem_yet']) ?></p>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <span class="font-medium text-slate-900"><?= (int)$p['so_bien_the'] ?> SKU</span>
              <span class="block text-xs text-emerald-700">Tồn: <?= (int)$p['tong_ton_kho'] ?></span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right text-slate-600"><?= number_format((int)$p['luot_xem'], 0, ',', '.') ?></td>
            <td class="whitespace-nowrap px-6 py-4">
              <button type="button"
                data-dev-feature="Đổi trạng thái ẩn/hiện sản phẩm #<?= (int)$p['ma_sp'] ?>"
                class="inline-flex items-center gap-2 rounded-full text-xs font-medium <?= (int)$p['trang_thai'] === 1 ? 'text-emerald-800' : 'text-slate-500' ?>">
                <span class="relative inline-flex h-5 w-9 items-center rounded-full <?= (int)$p['trang_thai'] === 1 ? 'bg-emerald-600' : 'bg-slate-300' ?>">
                  <span class="inline-block h-4 w-4 rounded-full bg-white shadow <?= (int)$p['trang_thai'] === 1 ? 'translate-x-4' : 'translate-x-0.5' ?>"></span>
                </span>
                <?= (int)$p['trang_thai'] === 1 ? 'Đang bán' : 'Tạm ẩn' ?>
              </button>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <div class="inline-flex items-center gap-1.5">
                <a href="../index.php?page=product&id=<?= (int)$p['ma_sp'] ?>" target="_blank"
                  class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:border-slate-400 hover:text-slate-900">
                  Xem trên Web
                </a>
                <button type="button" data-dev-feature="Chỉnh sửa sản phẩm #<?= (int)$p['ma_sp'] ?> trong bảng sp_san_pham"
                  class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50">
                  Sửa
                </button>
                <button type="button" data-dev-feature="Xóa sản phẩm #<?= (int)$p['ma_sp'] ?> khỏi CSDL"
                  class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                  Xóa
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="border-t border-slate-200 px-6 py-4">
    <p id="resultSummary" class="text-sm text-slate-500">Hiển thị <?= count($products) ?> sản phẩm trong CSDL</p>
  </div>
</section>

<!-- Bảng Cây Danh mục sản phẩm từ dm_danh_muc_sp -->
<section aria-labelledby="catTableTitle" class="fade-up mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5">
    <div>
      <h2 id="catTableTitle" class="text-base font-semibold text-slate-900">Cây Danh mục sản phẩm đa cấp (Bảng <code>dm_danh_muc_sp</code>)</h2>
      <p class="mt-1 text-sm text-slate-500">2 danh mục gốc và 5 danh mục con đang lưu trữ trong CSDL.</p>
    </div>
    <button type="button" data-dev-feature="Thêm/Sửa danh mục sản phẩm trong bảng dm_danh_muc_sp"
      class="tap-feedback rounded-lg border border-slate-300 px-3.5 py-2 text-xs font-medium text-slate-700 hover:border-slate-500">
      + Thêm danh mục (Đang phát triển)
    </button>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th class="px-6 py-3 font-medium">Mã DM</th>
          <th class="px-6 py-3 font-medium">Tên danh mục</th>
          <th class="px-6 py-3 font-medium">Đường dẫn tĩnh (Slug)</th>
          <th class="px-6 py-3 font-medium">Danh mục cha</th>
          <th class="px-6 py-3 text-right font-medium">Số sản phẩm</th>
          <th class="px-6 py-3 font-medium">Mô tả</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($categories as $cat): ?>
          <tr>
            <td class="whitespace-nowrap px-6 py-3.5 font-mono text-xs text-slate-600">#<?= (int)$cat['ma_dm'] ?></td>
            <td class="whitespace-nowrap px-6 py-3.5 font-medium text-slate-900">
              <?= $cat['ma_dm_cha'] ? '&mdash; ' : '' ?><?= htmlspecialchars($cat['ten_dm']) ?>
            </td>
            <td class="whitespace-nowrap px-6 py-3.5 font-mono text-xs text-slate-500"><?= htmlspecialchars($cat['duong_dan_tinh'] ?? '') ?></td>
            <td class="whitespace-nowrap px-6 py-3.5 text-slate-600"><?= htmlspecialchars($cat['ten_dm_cha'] ?? 'Gốc (Root)') ?></td>
            <td class="whitespace-nowrap px-6 py-3.5 text-right font-medium text-slate-900"><?= (int)$cat['so_san_pham'] ?> SP</td>
            <td class="px-6 py-3.5 text-xs text-slate-500"><?= htmlspecialchars($cat['mo_ta'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
  (() => {
    const searchInput = document.getElementById('productSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const rows = Array.from(document.querySelectorAll('.product-row'));
    const summary = document.getElementById('resultSummary');

    function filterRows() {
      const q = (searchInput?.value || '').trim().toLowerCase();
      const cat = categoryFilter?.value || '';
      let visibleCount = 0;

      rows.forEach((row) => {
        const matchQ = !q || row.dataset.name.includes(q);
        const matchCat = !cat || row.dataset.cat === cat;
        const show = matchQ && matchCat;
        row.classList.toggle('hidden', !show);
        if (show) visibleCount++;
      });

      if (summary) {
        summary.textContent = `Hiển thị ${visibleCount} trên ${rows.length} sản phẩm trong CSDL`;
      }
    }

    searchInput?.addEventListener('input', filterRows);
    categoryFilter?.addEventListener('change', filterRows);
  })();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

