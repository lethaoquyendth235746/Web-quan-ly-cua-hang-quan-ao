<?php
/**
 * TẦNG QUẢN TRỊ: Quản lý Danh mục & Sản phẩm thời trang (admin/products.php)
 * Đổ dữ liệu thực tế từ bảng dm_danh_muc_sp, sp_san_pham, bt_bien_the_sp, ha_hinh_anh_sp
 * Xử lý hoàn chỉnh CRUD Danh mục, Sản phẩm, Upload ảnh & Quản lý Biến thể SKU
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/admin/ProductAdminService.php';

$productAdminService = new ProductAdminService();

// =============================================================================
// XỬ LÝ POST REQUEST THEO MÔ HÌNH PRG (POST - REDIRECT - GET)
// Phải chạy trước khi xuất header HTML để tránh lỗi "headers already sent"
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectUrl = 'products.php';

    try {
        switch ($action) {
            // --- NHÓM SẢN PHẨM ---
            case 'create_product':
                $newMaSp = $productAdminService->createProduct($_POST, $_FILES['anh_chinh'] ?? null);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã tạo mới sản phẩm #SP-" . sprintf('%03d', $newMaSp) . " '{$_POST['ten_sp']}' thành công!"
                ];
                break;

            case 'update_product':
                $maSp = (int)($_POST['ma_sp'] ?? 0);
                $productAdminService->updateProduct($maSp, $_POST, $_FILES['anh_chinh'] ?? null);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã cập nhật sản phẩm #SP-" . sprintf('%03d', $maSp) . " thành công!"
                ];
                break;

            case 'toggle_product_status':
                $maSp = (int)($_POST['ma_sp'] ?? 0);
                $newStatus = $productAdminService->toggleProductStatus($maSp);
                $statusText = $newStatus === 1 ? 'Đang bán (Hiển thị)' : 'Tạm ẩn (Ngưng bán)';
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã chuyển sản phẩm #SP-" . sprintf('%03d', $maSp) . " sang trạng thái: {$statusText}."
                ];
                break;

            case 'delete_product':
                $maSp = (int)($_POST['ma_sp'] ?? 0);
                $productAdminService->deleteProduct($maSp);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã xóa sản phẩm #SP-" . sprintf('%03d', $maSp) . " cùng các biến thể và hình ảnh thành công!"
                ];
                break;

            // --- NHÓM DANH MỤC ---
            case 'create_category':
                $newMaDm = $productAdminService->createCategory($_POST);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã tạo danh mục mới '{$_POST['ten_dm']}' (#DM-{$newMaDm}) thành công!"
                ];
                $redirectUrl = 'products.php#catTableTitle';
                break;

            case 'update_category':
                $maDm = (int)($_POST['ma_dm'] ?? 0);
                $productAdminService->updateCategory($maDm, $_POST);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã cập nhật danh mục #DM-{$maDm} thành công!"
                ];
                $redirectUrl = 'products.php#catTableTitle';
                break;

            case 'delete_category':
                $maDm = (int)($_POST['ma_dm'] ?? 0);
                $productAdminService->deleteCategory($maDm);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã xóa danh mục #DM-{$maDm} thành công!"
                ];
                $redirectUrl = 'products.php#catTableTitle';
                break;

            // --- NHÓM BIẾN THỂ SKU ---
            case 'create_variant':
                $maSp = (int)($_POST['ma_sp'] ?? 0);
                $newMaBt = $productAdminService->createVariant($maSp, $_POST);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã thêm biến thể SKU '{$_POST['ma_sku']}' cho sản phẩm #SP-" . sprintf('%03d', $maSp) . "!"
                ];
                $redirectUrl = 'products.php?open_variants=' . $maSp;
                break;

            case 'update_variant':
                $maBt = (int)($_POST['ma_bt'] ?? 0);
                $maSp = (int)($_POST['ma_sp'] ?? 0);
                $productAdminService->updateVariant($maBt, $_POST);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã cập nhật biến thể SKU '{$_POST['ma_sku']}' thành công!"
                ];
                $redirectUrl = 'products.php?open_variants=' . $maSp;
                break;

            case 'delete_variant':
                $maBt = (int)($_POST['ma_bt'] ?? 0);
                $maSp = (int)($_POST['ma_sp'] ?? 0);
                $productAdminService->deleteVariant($maBt);
                $_SESSION['admin_flash'] = [
                    'type'    => 'success',
                    'message' => "Đã xóa biến thể #{$maBt} thành công!"
                ];
                $redirectUrl = 'products.php?open_variants=' . $maSp;
                break;

            default:
                throw new Exception('Thao tác yêu cầu không hợp lệ.');
        }
    } catch (Exception $e) {
        $_SESSION['admin_flash'] = [
            'type'    => 'error',
            'message' => $e->getMessage()
        ];
        if (in_array($action, ['create_variant', 'update_variant', 'delete_variant'], true)) {
            $maSp = (int)($_POST['ma_sp'] ?? 0);
            if ($maSp > 0) {
                $redirectUrl = 'products.php?open_variants=' . $maSp;
            }
        }
    }

    header('Location: ' . $redirectUrl);
    exit;
}

// Lấy dữ liệu hiển thị giao diện
$categories      = $productAdminService->getAllCategories();
$products        = $productAdminService->getAllProducts();
$groupedVariants = $productAdminService->getAllVariantsGroupedByProduct();

$adminPageTitle  = 'Danh mục & Sản phẩm | Fashion Loyalty';
$activeAdminMenu = 'products';

// Kiểm tra mở sẵn modal biến thể nếu vừa thao tác biến thể
$openVariantsId = isset($_GET['open_variants']) ? (int)$_GET['open_variants'] : 0;

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
      <p class="mt-1 text-sm text-slate-500">
        Dữ liệu thực tế từ bảng <code>sp_san_pham</code> (<?= count($products) ?> sản phẩm) và <code>dm_danh_muc_sp</code> (<?= count($categories) ?> danh mục).
      </p>
    </div>
    <button type="button" id="btnOpenAddProduct"
      class="tap-feedback inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-800">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
      Thêm sản phẩm mới
    </button>
  </div>
</header>

<!-- Công cụ lọc & tìm kiếm sản phẩm -->
<section aria-label="Công cụ lọc" class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
  <div class="relative flex-1">
    <label for="productSearch" class="sr-only">Tìm sản phẩm</label>
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6.5" /><path d="M16 16l4 4" stroke-linecap="round" /></svg>
    <input id="productSearch" type="search" placeholder="Tìm theo tên sản phẩm, chất liệu hoặc mã (SP-001)..."
      class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
  </div>
  <div>
    <label for="categoryFilter" class="sr-only">Lọc theo danh mục</label>
    <select id="categoryFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-64">
      <option value="">Tất cả danh mục trong CSDL</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int)$cat['ma_dm'] ?>">
          <?= $cat['ma_dm_cha'] ? '&mdash; ' : '' ?><?= htmlspecialchars($cat['ten_dm']) ?> (<?= (int)$cat['so_san_pham'] ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="statusFilter" class="sr-only">Lọc theo trạng thái</label>
    <select id="statusFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-44">
      <option value="">Tất cả trạng thái</option>
      <option value="1">Đang bán</option>
      <option value="0">Tạm ẩn</option>
    </select>
  </div>
</section>

<!-- Danh sách sản phẩm thực tế từ bảng sp_san_pham -->
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
        <?php if (empty($products)): ?>
          <tr>
            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
              Chưa có sản phẩm nào trong CSDL. Hãy bấm nút "Thêm sản phẩm mới" để bắt đầu!
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($products as $p): ?>
          <tr class="product-row align-middle hover:bg-slate-50/70 transition-colors"
            data-name="<?= htmlspecialchars(mb_strtolower($p['ten_sp'] . ' ' . $p['ma_sp_code'] . ' ' . ($p['chat_lieu'] ?? ''))) ?>"
            data-cat="<?= (int)$p['ma_dm'] ?>"
            data-status="<?= (int)$p['trang_thai'] ?>">
            <td class="px-6 py-4">
              <img src="<?= htmlspecialchars($p['anh_chinh_url']) ?>" alt="<?= htmlspecialchars($p['ten_sp']) ?>"
                class="h-12 w-12 rounded-lg object-cover border border-slate-200 bg-slate-100"
                onerror="this.src='https://images.pexels.com/photos/9968526/pexels-photo-9968526.jpeg?auto=compress&cs=tinysrgb&h=650&w=940';" />
            </td>
            <td class="px-6 py-4">
              <p class="font-medium text-slate-900"><?= htmlspecialchars($p['ten_sp']) ?></p>
              <p class="mt-0.5 text-xs text-slate-500">
                Mã: <span class="font-mono font-semibold text-slate-700"><?= htmlspecialchars($p['ma_sp_code']) ?></span> &middot; Chất liệu: <?= htmlspecialchars($p['chat_lieu'] ?? '-') ?>
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
              <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                <?= (int)$p['so_bien_the'] ?> SKU
              </span>
              <span class="block text-xs mt-0.5 font-medium <?= (int)$p['tong_ton_kho'] > 0 ? 'text-emerald-700' : 'text-rose-600' ?>">
                Tồn: <?= (int)$p['tong_ton_kho'] ?>
              </span>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right text-slate-600"><?= number_format((int)$p['luot_xem'], 0, ',', '.') ?></td>
            <td class="whitespace-nowrap px-6 py-4">
              <form method="POST" action="products.php" class="inline-block">
                <input type="hidden" name="action" value="toggle_product_status" />
                <input type="hidden" name="ma_sp" value="<?= (int)$p['ma_sp'] ?>" />
                <button type="submit" title="Nhấn để đổi trạng thái ẩn/hiện"
                  class="tap-feedback inline-flex items-center gap-2 rounded-full text-xs font-medium cursor-pointer <?= (int)$p['trang_thai'] === 1 ? 'text-emerald-800' : 'text-slate-500' ?>">
                  <span class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors <?= (int)$p['trang_thai'] === 1 ? 'bg-emerald-600' : 'bg-slate-300' ?>">
                    <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform <?= (int)$p['trang_thai'] === 1 ? 'translate-x-4' : 'translate-x-0.5' ?>"></span>
                  </span>
                  <span><?= (int)$p['trang_thai'] === 1 ? 'Đang bán' : 'Tạm ẩn' ?></span>
                </button>
              </form>
            </td>
            <td class="whitespace-nowrap px-6 py-4 text-right">
              <div class="inline-flex items-center gap-1.5">
                <a href="../index.php?page=product&id=<?= (int)$p['ma_sp'] ?>" target="_blank"
                  title="Xem trên trang cửa hàng"
                  class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:border-slate-400 hover:text-slate-900 transition-colors">
                  Web
                </a>
                <button type="button"
                  onclick="openVariantModal(<?= (int)$p['ma_sp'] ?>)"
                  class="tap-feedback rounded-lg border border-indigo-200 bg-indigo-50/50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 transition-colors">
                  Biến thể (<?= (int)$p['so_bien_the'] ?>)
                </button>
                <button type="button"
                  onclick="openEditProductModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)"
                  class="tap-feedback rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 transition-colors">
                  Sửa
                </button>
                <form method="POST" action="products.php" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn sản phẩm #SP-<?= sprintf('%03d', $p['ma_sp']) ?> \'<?= htmlspecialchars(addslashes($p['ten_sp'])) ?>\' khỏi hệ thống?');">
                  <input type="hidden" name="action" value="delete_product" />
                  <input type="hidden" name="ma_sp" value="<?= (int)$p['ma_sp'] ?>" />
                  <button type="submit"
                    class="tap-feedback rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 transition-colors">
                    Xóa
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="border-t border-slate-200 px-6 py-4 flex items-center justify-between text-sm text-slate-500">
    <p id="resultSummary">Hiển thị <?= count($products) ?> sản phẩm trong CSDL</p>
  </div>
</section>

<!-- Bảng Cây Danh mục sản phẩm từ dm_danh_muc_sp -->
<section aria-labelledby="catTableTitle" class="fade-up mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5">
    <div>
      <h2 id="catTableTitle" class="text-base font-semibold text-slate-900">Cây Danh mục sản phẩm đa cấp (Bảng <code>dm_danh_muc_sp</code>)</h2>
      <p class="mt-1 text-sm text-slate-500">Quản lý trọn vẹn danh mục cha - con và thứ tự hiển thị ngoài website.</p>
    </div>
    <button type="button" id="btnOpenAddCategory"
      class="tap-feedback inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-medium text-slate-700 hover:border-slate-500 hover:bg-slate-50 transition-colors shadow-sm">
      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
      + Thêm danh mục mới
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
          <th class="px-6 py-3 text-center font-medium">Thứ tự</th>
          <th class="px-6 py-3 font-medium">Trạng thái</th>
          <th class="px-6 py-3 font-medium">Mô tả</th>
          <th class="px-6 py-3 text-right font-medium">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 tabular-nums">
        <?php foreach ($categories as $cat): ?>
          <tr class="hover:bg-slate-50/70 transition-colors">
            <td class="whitespace-nowrap px-6 py-3.5 font-mono text-xs font-semibold text-slate-600">#DM-<?= (int)$cat['ma_dm'] ?></td>
            <td class="whitespace-nowrap px-6 py-3.5 font-medium text-slate-900">
              <?= $cat['ma_dm_cha'] ? '<span class="text-slate-300 mr-1">&mdash;&mdash;</span> ' : '<span class="inline-block w-2 h-2 rounded-full bg-blue-600 mr-2"></span>' ?>
              <?= htmlspecialchars($cat['ten_dm']) ?>
            </td>
            <td class="whitespace-nowrap px-6 py-3.5 font-mono text-xs text-slate-500"><?= htmlspecialchars($cat['duong_dan_tinh'] ?? '') ?></td>
            <td class="whitespace-nowrap px-6 py-3.5 text-slate-600">
              <?= $cat['ten_dm_cha'] ? htmlspecialchars($cat['ten_dm_cha']) : '<span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">Danh mục gốc</span>' ?>
            </td>
            <td class="whitespace-nowrap px-6 py-3.5 text-right font-medium text-slate-900"><?= (int)$cat['so_san_pham'] ?> SP</td>
            <td class="whitespace-nowrap px-6 py-3.5 text-center text-slate-600"><?= (int)$cat['thu_tu_hien_thi'] ?></td>
            <td class="whitespace-nowrap px-6 py-3.5">
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= (int)$cat['trang_thai'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                <?= (int)$cat['trang_thai'] === 1 ? 'Hoạt động' : 'Tạm ẩn' ?>
              </span>
            </td>
            <td class="px-6 py-3.5 text-xs text-slate-500 max-w-xs truncate"><?= htmlspecialchars($cat['mo_ta'] ?? '-') ?></td>
            <td class="whitespace-nowrap px-6 py-3.5 text-right">
              <div class="inline-flex items-center gap-1.5">
                <button type="button"
                  onclick="openEditCategoryModal(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8') ?>)"
                  class="tap-feedback rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50 transition-colors">
                  Sửa
                </button>
                <form method="POST" action="products.php" class="inline-block" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục \'<?= htmlspecialchars(addslashes($cat['ten_dm'])) ?>\'?');">
                  <input type="hidden" name="action" value="delete_category" />
                  <input type="hidden" name="ma_dm" value="<?= (int)$cat['ma_dm'] ?>" />
                  <button type="submit"
                    class="tap-feedback rounded-lg border border-red-200 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-50 transition-colors">
                    Xóa
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ========================================================================= -->
<!-- MODAL 1: THÊM / CHỈNH SỬA SẢN PHẨM (#productModal)                         -->
<!-- ========================================================================= -->
<div id="productModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeProductModal()"></div>
  <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl transition-all">
      <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
        <div>
          <h3 id="productModalTitle" class="text-lg font-semibold text-slate-900">Thêm sản phẩm mới</h3>
          <p class="text-xs text-slate-500 mt-0.5">Lưu trực tiếp vào bảng <code>sp_san_pham</code> &amp; thư mục <code>assets/images/</code></p>
        </div>
        <button type="button" onclick="closeProductModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        </button>
      </div>

      <form id="productForm" method="POST" action="products.php" enctype="multipart/form-data" class="p-6 space-y-4">
        <input type="hidden" name="action" id="prodAction" value="create_product" />
        <input type="hidden" name="ma_sp" id="prodMaSp" value="" />

        <div>
          <label for="prodTenSp" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Tên sản phẩm thời trang <span class="text-rose-600">*</span></label>
          <input type="text" name="ten_sp" id="prodTenSp" required placeholder="VD: Áo Khoác Gió Bomber Unisex..."
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="prodMaDm" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Danh mục <span class="text-rose-600">*</span></label>
            <select name="ma_dm" id="prodMaDm" required
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
              <option value="">-- Chọn danh mục --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['ma_dm'] ?>">
                  <?= $cat['ma_dm_cha'] ? '&mdash; ' : '' ?><?= htmlspecialchars($cat['ten_dm']) ?> <?= $cat['ten_dm_cha'] ? '(' . htmlspecialchars($cat['ten_dm_cha']) . ')' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="prodChatLieu" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Chất liệu vải</label>
            <input type="text" name="chat_lieu" id="prodChatLieu" placeholder="VD: Cotton 100%, Kaki Spandex, Silk..."
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="prodGiaNiemYet" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Giá niêm yết (Gốc) VNĐ <span class="text-rose-600">*</span></label>
            <input type="number" name="gia_niem_yet" id="prodGiaNiemYet" required min="0" step="1000" placeholder="VD: 450000"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>
          <div>
            <label for="prodGiaKhuyenMai" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Giá khuyến mãi (Bán) VNĐ <span class="text-rose-600">*</span></label>
            <input type="number" name="gia_khuyen_mai" id="prodGiaKhuyenMai" required min="0" step="1000" placeholder="VD: 399000"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
            <p class="mt-1 text-xs text-slate-400">Giá khuyến mãi phải nhỏ hơn hoặc bằng giá niêm yết.</p>
          </div>
        </div>

        <!-- Khung Upload ảnh & Xem trước thực tế -->
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Ảnh sản phẩm chính (Upload thực tế)</label>
          <div class="mt-1 flex items-start gap-4">
            <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center">
              <img id="imagePreview" src="https://images.pexels.com/photos/9968526/pexels-photo-9968526.jpeg?auto=compress&cs=tinysrgb&h=650&w=940"
                alt="Xem trước ảnh" class="h-full w-full object-cover" />
            </div>
            <div class="flex-1">
              <input type="file" name="anh_chinh" id="prodAnhChinh" accept=".jpg,.jpeg,.png,.webp"
                class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-xs file:font-medium file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
              <p class="mt-1 text-xs text-slate-500">Chấp nhận JPG, JPEG, PNG, WEBP (tối đa 5MB). File sẽ được lưu vật lý vào <code>assets/images/</code>.</p>
              <p id="currentImageHint" class="mt-1 text-xs text-blue-600 hidden">Đang giữ ảnh hiện tại nếu không chọn file mới.</p>
            </div>
          </div>
        </div>

        <div>
          <label for="prodMoTa" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Mô tả chi tiết sản phẩm</label>
          <textarea name="mo_ta_chi_tiet" id="prodMoTa" rows="3" placeholder="Mô tả form dáng, tính năng vải, hướng dẫn bảo quản..."
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100"></textarea>
        </div>

        <div>
          <label for="prodTrangThai" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Trạng thái bán</label>
          <select name="trang_thai" id="prodTrangThai"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:w-48">
            <option value="1">Đang bán (Hiển thị)</option>
            <option value="0">Tạm ẩn (Ngưng bán)</option>
          </select>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
          <button type="button" onclick="closeProductModal()"
            class="tap-feedback rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Hủy
          </button>
          <button type="submit" id="btnSubmitProduct"
            class="tap-feedback rounded-lg bg-blue-700 px-5 py-2 text-sm font-medium text-white hover:bg-blue-800 shadow-sm">
            Lưu sản phẩm
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: QUẢN LÝ BIẾN THỂ SIZE & MÀU SKU (#variantModal)                    -->
<!-- ========================================================================= -->
<div id="variantModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeVariantModal()"></div>
  <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative w-full max-w-4xl rounded-2xl bg-white shadow-2xl transition-all">
      <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
        <div>
          <div class="flex items-center gap-2">
            <h3 id="variantModalTitle" class="text-lg font-semibold text-slate-900">Quản lý Biến thể SKU</h3>
            <span id="variantProductCode" class="font-mono rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">SP-001</span>
          </div>
          <p id="variantProductSubtitle" class="text-xs text-slate-500 mt-0.5">Sản phẩm: Áo Khoác Gió &middot; Bảng <code>bt_bien_the_sp</code></p>
        </div>
        <button type="button" onclick="closeVariantModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        </button>
      </div>

      <div class="p-6 space-y-6">
        <!-- Bảng danh sách biến thể hiện có của sản phẩm -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Danh sách biến thể SKU hiện có</h4>
            <span id="variantCountBadge" class="text-xs font-medium text-slate-500">0 biến thể</span>
          </div>
          <div class="overflow-x-auto rounded-lg border border-slate-200 max-h-56 overflow-y-auto">
            <table class="min-w-full text-left text-xs">
              <thead class="sticky top-0 bg-slate-50 font-medium text-slate-500 uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th class="px-4 py-2.5">Mã SKU</th>
                  <th class="px-4 py-2.5">Kích cỡ</th>
                  <th class="px-4 py-2.5">Màu sắc</th>
                  <th class="px-4 py-2.5 text-right">Giá bán riêng</th>
                  <th class="px-4 py-2.5 text-right">Tồn kho</th>
                  <th class="px-4 py-2.5 text-right">Đã bán / Nhập</th>
                  <th class="px-4 py-2.5">Trạng thái</th>
                  <th class="px-4 py-2.5 text-right">Thao tác</th>
                </tr>
              </thead>
              <tbody id="variantTableBody" class="divide-y divide-slate-200 tabular-nums">
                <!-- Render bằng JavaScript -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- Form Thêm mới / Cập nhật biến thể -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
          <div class="flex items-center justify-between border-b border-slate-200/80 pb-2 mb-3">
            <h4 id="variantFormHeading" class="text-xs font-bold uppercase tracking-wider text-blue-900">+ Thêm biến thể SKU mới</h4>
            <button type="button" id="btnCancelEditVariant" onclick="resetVariantForm()" class="hidden text-xs font-medium text-rose-600 hover:underline">
              Hủy chỉnh sửa (Về thêm mới)
            </button>
          </div>

          <form id="variantForm" method="POST" action="products.php" class="space-y-3">
            <input type="hidden" name="action" id="variantAction" value="create_variant" />
            <input type="hidden" name="ma_sp" id="variantMaSp" value="" />
            <input type="hidden" name="ma_bt" id="variantMaBt" value="" />

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
              <div>
                <div class="flex items-center justify-between">
                  <label for="varSku" class="block text-xs font-semibold text-slate-700">Mã SKU <span class="text-rose-600">*</span></label>
                  <button type="button" onclick="autoGenerateSku()" class="text-[11px] text-blue-700 hover:underline">Gợi ý mã</button>
                </div>
                <input type="text" name="ma_sku" id="varSku" required placeholder="VD: SP01-DEN-L"
                  class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-mono uppercase text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-100" />
              </div>

              <div>
                <label for="varKichCo" class="block text-xs font-semibold text-slate-700">Kích cỡ (Size) <span class="text-rose-600">*</span></label>
                <input type="text" name="kich_co" id="varKichCo" list="sizeSuggestions" required placeholder="VD: S, M, L, XL..."
                  class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-100" />
                <datalist id="sizeSuggestions">
                  <option value="XS"></option>
                  <option value="S"></option>
                  <option value="M"></option>
                  <option value="L"></option>
                  <option value="XL"></option>
                  <option value="2XL"></option>
                  <option value="3XL"></option>
                  <option value="FreeSize"></option>
                </datalist>
              </div>

              <div>
                <label for="varMauSac" class="block text-xs font-semibold text-slate-700">Màu sắc <span class="text-rose-600">*</span></label>
                <input type="text" name="mau_sac" id="varMauSac" list="colorSuggestions" required placeholder="VD: Đen, Trắng, Xanh Rêu..."
                  class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-100" />
                <datalist id="colorSuggestions">
                  <option value="Đen"></option>
                  <option value="Trắng"></option>
                  <option value="Trắng Kem"></option>
                  <option value="Màu Be"></option>
                  <option value="Xanh Pastel"></option>
                  <option value="Xanh Cổ Điển"></option>
                  <option value="Xanh Rêu"></option>
                  <option value="Đỏ Đô"></option>
                  <option value="Nâu Tây"></option>
                  <option value="Hồng Phấn"></option>
                </datalist>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
              <div>
                <label for="varGiaBanRieng" class="block text-xs font-semibold text-slate-700">Giá bán riêng (VNĐ) <span class="text-rose-600">*</span></label>
                <input type="number" name="gia_ban_rieng" id="varGiaBanRieng" required min="1000" step="1000"
                  class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-100" />
              </div>

              <div>
                <label for="varSoLuongTon" class="block text-xs font-semibold text-slate-700">Tồn kho ban đầu</label>
                <input type="number" name="so_luong_ton" id="varSoLuongTon" min="0" step="1" value="0"
                  class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-100" />
                <p id="varTonHint" class="text-[10px] text-slate-400 mt-0.5 hidden">Đã có phiếu nhập kho, tồn kho được quản lý qua phiếu nhập.</p>
              </div>

              <div>
                <label for="varTrangThai" class="block text-xs font-semibold text-slate-700">Trạng thái bán</label>
                <select name="trang_thai" id="varTrangThai"
                  class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-100">
                  <option value="1">Đang bán</option>
                  <option value="0">Tạm ẩn</option>
                </select>
              </div>
            </div>

            <div class="flex justify-end pt-2">
              <button type="submit" id="btnSaveVariant"
                class="tap-feedback rounded-lg bg-blue-700 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800 shadow-sm">
                Lưu biến thể SKU
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 3: THÊM / CHỈNH SỬA DANH MỤC (#categoryModal)                        -->
<!-- ========================================================================= -->
<div id="categoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeCategoryModal()"></div>
  <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl transition-all">
      <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
        <div>
          <h3 id="categoryModalTitle" class="text-lg font-semibold text-slate-900">Thêm danh mục mới</h3>
          <p class="text-xs text-slate-500 mt-0.5">Bảng <code>dm_danh_muc_sp</code></p>
        </div>
        <button type="button" onclick="closeCategoryModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        </button>
      </div>

      <form id="categoryForm" method="POST" action="products.php" class="p-6 space-y-4">
        <input type="hidden" name="action" id="catAction" value="create_category" />
        <input type="hidden" name="ma_dm" id="catMaDm" value="" />

        <div>
          <label for="catTenDm" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Tên danh mục <span class="text-rose-600">*</span></label>
          <input type="text" name="ten_dm" id="catTenDm" required placeholder="VD: Áo Khoác Nam, Váy Dạ Hội..."
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
        </div>

        <div>
          <label for="catSlug" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Đường dẫn tĩnh (Slug URL)</label>
          <input type="text" name="duong_dan_tinh" id="catSlug" placeholder="Để trống hệ thống sẽ tự động tạo từ tên danh mục"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          <p class="mt-1 text-xs text-slate-400">VD: <code>ao-khoac-nam</code></p>
        </div>

        <div>
          <label for="catMaDmCha" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Danh mục cha</label>
          <select name="ma_dm_cha" id="catMaDmCha"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
            <option value="">-- Danh mục gốc (Cấp 1 / Root) --</option>
            <?php foreach ($categories as $c): ?>
              <?php if ($c['ma_dm_cha'] === null): ?>
                <option value="<?= (int)$c['ma_dm'] ?>"><?= htmlspecialchars($c['ten_dm']) ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="catThuTu" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Thứ tự hiển thị</label>
            <input type="number" name="thu_tu_hien_thi" id="catThuTu" value="1" min="1"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100" />
          </div>
          <div>
            <label for="catTrangThai" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Trạng thái</label>
            <select name="trang_thai" id="catTrangThai"
              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
              <option value="1">Hoạt động</option>
              <option value="0">Tạm ẩn</option>
            </select>
          </div>
        </div>

        <div>
          <label for="catMoTa" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Mô tả ngắn</label>
          <textarea name="mo_ta" id="catMoTa" rows="2" placeholder="Mô tả danh mục hiển thị cho SEO..."
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100"></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
          <button type="button" onclick="closeCategoryModal()"
            class="tap-feedback rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Hủy
          </button>
          <button type="submit"
            class="tap-feedback rounded-lg bg-blue-700 px-5 py-2 text-sm font-medium text-white hover:bg-blue-800 shadow-sm">
            Lưu danh mục
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT ĐIỀU KHIỂN GIAO DIỆN & MODAL                                   -->
<!-- ========================================================================= -->
<script>
  // Dữ liệu PHP được truyền an toàn vào JS
  const allProducts = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
  const allGroupedVariants = <?= json_encode($groupedVariants, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
  const colorHexMap = {
    'Đen': '#2D2D2D',
    'Trắng': '#FFFFFF',
    'Trắng Kem': '#F5F0E6',
    'Trắng Ngọc': '#F8FAFC',
    'Màu Be': '#D8C3A5',
    'Be': '#D8C3A5',
    'Xanh Pastel': '#A7C7E7',
    'Xanh Cổ Điển': '#3B5998',
    'Xanh Rêu': '#5F6F52',
    'Đỏ Đô': '#7B1E26',
    'Nâu Tây': '#8C6D53',
    'Hồng Phấn': '#F4C2C2'
  };

  let currentVariantProduct = null;

  // Lọc sản phẩm tìm kiếm & danh mục & trạng thái
  (() => {
    const searchInput = document.getElementById('productSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const rows = Array.from(document.querySelectorAll('.product-row'));
    const summary = document.getElementById('resultSummary');

    function filterRows() {
      const q = (searchInput?.value || '').trim().toLowerCase();
      const cat = categoryFilter?.value || '';
      const st = statusFilter?.value || '';
      let visibleCount = 0;

      rows.forEach((row) => {
        const matchQ = !q || row.dataset.name.includes(q);
        const matchCat = !cat || row.dataset.cat === cat;
        const matchStatus = st === '' || row.dataset.status === st;
        const show = matchQ && matchCat && matchStatus;
        row.classList.toggle('hidden', !show);
        if (show) visibleCount++;
      });

      if (summary) {
        summary.textContent = `Hiển thị ${visibleCount} trên ${rows.length} sản phẩm trong CSDL`;
      }
    }

    searchInput?.addEventListener('input', filterRows);
    categoryFilter?.addEventListener('change', filterRows);
    statusFilter?.addEventListener('change', filterRows);
  })();

  // ---------------------------------------------------------------------------
  // XỬ LÝ MODAL SẢN PHẨM
  // ---------------------------------------------------------------------------
  const productModal = document.getElementById('productModal');
  const btnOpenAddProduct = document.getElementById('btnOpenAddProduct');
  const prodAnhChinh = document.getElementById('prodAnhChinh');
  const imagePreview = document.getElementById('imagePreview');
  const currentImageHint = document.getElementById('currentImageHint');

  btnOpenAddProduct?.addEventListener('click', () => {
    document.getElementById('productModalTitle').textContent = 'Thêm sản phẩm mới';
    document.getElementById('prodAction').value = 'create_product';
    document.getElementById('prodMaSp').value = '';
    document.getElementById('prodTenSp').value = '';
    document.getElementById('prodMaDm').value = '';
    document.getElementById('prodChatLieu').value = '';
    document.getElementById('prodGiaNiemYet').value = '';
    document.getElementById('prodGiaKhuyenMai').value = '';
    document.getElementById('prodMoTa').value = '';
    document.getElementById('prodTrangThai').value = '1';
    document.getElementById('prodAnhChinh').value = '';
    imagePreview.src = 'https://images.pexels.com/photos/9968526/pexels-photo-9968526.jpeg?auto=compress&cs=tinysrgb&h=650&w=940';
    currentImageHint.classList.add('hidden');
    productModal.classList.remove('hidden');
  });

  window.openEditProductModal = function(p) {
    document.getElementById('productModalTitle').textContent = `Chỉnh sửa sản phẩm #${p.ma_sp_code}`;
    document.getElementById('prodAction').value = 'update_product';
    document.getElementById('prodMaSp').value = p.ma_sp;
    document.getElementById('prodTenSp').value = p.ten_sp || '';
    document.getElementById('prodMaDm').value = p.ma_dm || '';
    document.getElementById('prodChatLieu').value = p.chat_lieu || '';
    document.getElementById('prodGiaNiemYet').value = Math.round(p.gia_niem_yet) || '';
    document.getElementById('prodGiaKhuyenMai').value = Math.round(p.gia_khuyen_mai) || '';
    document.getElementById('prodMoTa').value = p.mo_ta_chi_tiet || '';
    document.getElementById('prodTrangThai').value = p.trang_thai;
    document.getElementById('prodAnhChinh').value = '';
    imagePreview.src = p.anh_chinh_url || 'https://images.pexels.com/photos/9968526/pexels-photo-9968526.jpeg?auto=compress&cs=tinysrgb&h=650&w=940';
    currentImageHint.classList.remove('hidden');
    productModal.classList.remove('hidden');
  };

  window.closeProductModal = function() {
    productModal.classList.add('hidden');
  };

  // Xem trước ảnh tức thì khi chọn file từ máy tính
  prodAnhChinh?.addEventListener('change', function(e) {
    const file = e.target.files?.[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(evt) {
        imagePreview.src = evt.target.result;
      };
      reader.readAsDataURL(file);
    }
  });

  // ---------------------------------------------------------------------------
  // XỬ LÝ MODAL BIẾN THỂ SIZE & MÀU SKU
  // ---------------------------------------------------------------------------
  const variantModal = document.getElementById('variantModal');

  window.openVariantModal = function(maSp) {
    const p = allProducts.find(item => Number(item.ma_sp) === Number(maSp));
    if (!p) return;

    currentVariantProduct = p;
    document.getElementById('variantModalTitle').textContent = `Quản lý Biến thể SKU - ${p.ten_sp}`;
    document.getElementById('variantProductCode').textContent = p.ma_sp_code;
    document.getElementById('variantProductSubtitle').textContent = `Danh mục: ${p.ten_dm} · Giá bán mặc định: ${Number(p.gia_khuyen_mai).toLocaleString('vi-VN')}đ`;

    renderVariantTable(maSp);
    resetVariantForm();

    variantModal.classList.remove('hidden');
  };

  window.closeVariantModal = function() {
    variantModal.classList.add('hidden');
  };

  function renderVariantTable(maSp) {
    const tbody = document.getElementById('variantTableBody');
    const badge = document.getElementById('variantCountBadge');
    const variants = allGroupedVariants[maSp] || [];

    badge.textContent = `${variants.length} biến thể SKU`;

    if (variants.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" class="px-4 py-8 text-center text-slate-400">
            Sản phẩm này chưa có biến thể SKU nào. Hãy nhập thông tin bên dưới để thêm biến thể đầu tiên!
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = variants.map((v) => {
      const hex = colorHexMap[v.mau_sac] || '#94A3B8';
      const isBorder = ['#FFFFFF', '#F8FAFC', '#F5F0E6'].includes(hex) ? 'border border-slate-300' : '';
      const daBan = Number(v.so_luong_da_ban || 0);
      const phieuNhap = Number(v.so_phieu_nhap || 0);
      const hasHistory = daBan > 0 || phieuNhap > 0;

      const deleteBtn = hasHistory
        ? `<button type="button" disabled title="Không thể xóa do đã phát sinh ${daBan} lượt bán hoặc ${phieuNhap} phiếu nhập kho" class="rounded px-2 py-1 text-[11px] font-medium text-slate-400 bg-slate-100 cursor-not-allowed">Xóa</button>`
        : `<form method="POST" action="products.php" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa biến thể SKU ${v.ma_sku}?');">
             <input type="hidden" name="action" value="delete_variant" />
             <input type="hidden" name="ma_sp" value="${v.ma_sp}" />
             <input type="hidden" name="ma_bt" value="${v.ma_bt}" />
             <button type="submit" class="tap-feedback rounded px-2 py-1 text-[11px] font-medium text-rose-700 border border-rose-200 hover:bg-rose-50 transition-colors">Xóa</button>
           </form>`;

      return `
        <tr class="hover:bg-slate-50/70 transition-colors">
          <td class="whitespace-nowrap px-4 py-2.5 font-mono font-bold text-slate-800">${v.ma_sku}</td>
          <td class="whitespace-nowrap px-4 py-2.5">
            <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-800">${v.kich_co}</span>
          </td>
          <td class="whitespace-nowrap px-4 py-2.5">
            <div class="inline-flex items-center gap-1.5">
              <span class="inline-block h-3.5 w-3.5 rounded-full shadow-sm ${isBorder}" style="background-color: ${hex}"></span>
              <span class="text-slate-800">${v.mau_sac}</span>
            </div>
          </td>
          <td class="whitespace-nowrap px-4 py-2.5 text-right font-medium text-slate-900">${Number(v.gia_ban_rieng).toLocaleString('vi-VN')}đ</td>
          <td class="whitespace-nowrap px-4 py-2.5 text-right font-semibold ${Number(v.so_luong_ton) > 0 ? 'text-emerald-700' : 'text-rose-600'}">${v.so_luong_ton}</td>
          <td class="whitespace-nowrap px-4 py-2.5 text-right text-slate-500">${daBan} bán / ${phieuNhap} PNK</td>
          <td class="whitespace-nowrap px-4 py-2.5">
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ${Number(v.trang_thai) === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
              ${Number(v.trang_thai) === 1 ? 'Đang bán' : 'Tạm ẩn'}
            </span>
          </td>
          <td class="whitespace-nowrap px-4 py-2.5 text-right">
            <div class="inline-flex items-center gap-1">
              <button type="button" onclick="editVariantInModal(${v.ma_bt})" class="tap-feedback rounded px-2 py-1 text-[11px] font-medium text-blue-700 border border-slate-200 hover:bg-blue-50 transition-colors">Sửa</button>
              ${deleteBtn}
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  window.resetVariantForm = function() {
    if (!currentVariantProduct) return;
    document.getElementById('variantFormHeading').textContent = '+ Thêm biến thể SKU mới';
    document.getElementById('variantAction').value = 'create_variant';
    document.getElementById('variantMaSp').value = currentVariantProduct.ma_sp;
    document.getElementById('variantMaBt').value = '';
    document.getElementById('varSku').value = '';
    document.getElementById('varKichCo').value = 'M';
    document.getElementById('varMauSac').value = 'Đen';
    document.getElementById('varGiaBanRieng').value = Math.round(currentVariantProduct.gia_khuyen_mai);
    document.getElementById('varSoLuongTon').value = '50';
    document.getElementById('varSoLuongTon').disabled = false;
    document.getElementById('varTonHint').classList.add('hidden');
    document.getElementById('varTrangThai').value = '1';
    document.getElementById('btnCancelEditVariant').classList.add('hidden');
    document.getElementById('btnSaveVariant').textContent = 'Lưu biến thể SKU mới';
  };

  window.editVariantInModal = function(maBt) {
    if (!currentVariantProduct) return;
    const variants = allGroupedVariants[currentVariantProduct.ma_sp] || [];
    const v = variants.find(item => Number(item.ma_bt) === Number(maBt));
    if (!v) return;

    document.getElementById('variantFormHeading').textContent = `Chỉnh sửa biến thể SKU #${v.ma_sku}`;
    document.getElementById('variantAction').value = 'update_variant';
    document.getElementById('variantMaSp').value = v.ma_sp;
    document.getElementById('variantMaBt').value = v.ma_bt;
    document.getElementById('varSku').value = v.ma_sku;
    document.getElementById('varKichCo').value = v.kich_co;
    document.getElementById('varMauSac').value = v.mau_sac;
    document.getElementById('varGiaBanRieng').value = Math.round(v.gia_ban_rieng);
    document.getElementById('varTrangThai').value = v.trang_thai;

    const hasPnk = Number(v.so_phieu_nhap || 0) > 0;
    const tonInput = document.getElementById('varSoLuongTon');
    const tonHint = document.getElementById('varTonHint');
    tonInput.value = v.so_luong_ton;
    if (hasPnk) {
      tonInput.disabled = true;
      tonHint.classList.remove('hidden');
    } else {
      tonInput.disabled = false;
      tonHint.classList.add('hidden');
    }

    document.getElementById('btnCancelEditVariant').classList.remove('hidden');
    document.getElementById('btnSaveVariant').textContent = 'Cập nhật biến thể';
    document.getElementById('varSku').focus();
  };

  // Tự động tạo mã SKU dựa trên mã SP + Màu + Size
  window.autoGenerateSku = function() {
    if (!currentVariantProduct) return;
    const code = (currentVariantProduct.ma_sp_code || 'SP-001').replace('-', '');
    const size = (document.getElementById('varKichCo').value || 'M').trim().toUpperCase();
    const color = (document.getElementById('varMauSac').value || 'DEN').trim();
    const cleanColor = removeVietnameseTones(color).toUpperCase().replace(/[^A-Z0-9]/g, '');
    document.getElementById('varSku').value = `${code}-${cleanColor || 'COLOR'}-${size}`;
  };

  function removeVietnameseTones(str) {
    return str.normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/đ/g, 'd').replace(/Đ/g, 'D');
  }

  // ---------------------------------------------------------------------------
  // XỬ LÝ MODAL DANH MỤC
  // ---------------------------------------------------------------------------
  const categoryModal = document.getElementById('categoryModal');
  const btnOpenAddCategory = document.getElementById('btnOpenAddCategory');

  btnOpenAddCategory?.addEventListener('click', () => {
    document.getElementById('categoryModalTitle').textContent = 'Thêm danh mục mới';
    document.getElementById('catAction').value = 'create_category';
    document.getElementById('catMaDm').value = '';
    document.getElementById('catTenDm').value = '';
    document.getElementById('catSlug').value = '';
    document.getElementById('catMaDmCha').value = '';
    document.getElementById('catThuTu').value = '1';
    document.getElementById('catTrangThai').value = '1';
    document.getElementById('catMoTa').value = '';
    categoryModal.classList.remove('hidden');
  });

  window.openEditCategoryModal = function(c) {
    document.getElementById('categoryModalTitle').textContent = `Chỉnh sửa danh mục #DM-${c.ma_dm}`;
    document.getElementById('catAction').value = 'update_category';
    document.getElementById('catMaDm').value = c.ma_dm;
    document.getElementById('catTenDm').value = c.ten_dm || '';
    document.getElementById('catSlug').value = c.duong_dan_tinh || '';
    document.getElementById('catMaDmCha').value = c.ma_dm_cha || '';
    document.getElementById('catThuTu').value = c.thu_tu_hien_thi || 1;
    document.getElementById('catTrangThai').value = c.trang_thai;
    document.getElementById('catMoTa').value = c.mo_ta || '';
    categoryModal.classList.remove('hidden');
  };

  window.closeCategoryModal = function() {
    categoryModal.classList.add('hidden');
  };

  // Đóng modal khi nhấn phím Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeProductModal();
      closeVariantModal();
      closeCategoryModal();
    }
  });

  // Tự động mở modal biến thể nếu URL có param open_variants
  <?php if ($openVariantsId > 0): ?>
    document.addEventListener('DOMContentLoaded', () => {
      openVariantModal(<?= $openVariantsId ?>);
    });
  <?php endif; ?>
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
