<?php
/**
 * FILE INDEX.PHP (ĐIỂM VÀO CHÍNH CỦA HỆ THỐNG CỬA HÀNG)
 * Kết nối Tầng Kết nối (config/db.php), Tầng Dịch vụ Cửa hàng (services/store/*)
 * và Tầng Giao diện Cửa hàng (store/includes/* & store/pages/*)
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/services/store/ProductStoreService.php';
require_once __DIR__ . '/services/store/LoyaltyStoreService.php';
require_once __DIR__ . '/services/store/CartCheckoutService.php';

$productService  = new ProductStoreService();
$loyaltyService  = new LoyaltyStoreService();
$checkoutService = new CartCheckoutService();

$currentUserId   = getCurrentUserId();
$loyaltyProfile  = $loyaltyService->getCustomerLoyaltyProfile($currentUserId);
$demoCustomers   = $loyaltyService->getDemoCustomers();
$cartItems       = $checkoutService->getCustomerCart($currentUserId);

$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'login':
        require __DIR__ . '/login.php';
        exit;

    case 'logout':
        require __DIR__ . '/logout.php';
        exit;

    case 'product':
        $productId = isset($_GET['id']) ? (int) $_GET['id'] : 1;
        $productDetail = $productService->getProductById($productId);
        $pageTitle = ($productDetail['ten_sp'] ?? 'Chi tiết sản phẩm') . ' | AURA STUDIO';
        require __DIR__ . '/store/includes/header.php';
        require __DIR__ . '/store/pages/product_detail.php';
        require __DIR__ . '/store/includes/footer.php';
        break;

    case 'loyalty':
        $pageTitle = 'Trung tâm Thành viên & Ưu đãi | AURA STUDIO';
        $customerTierId = (int) ($loyaltyProfile['ma_hang'] ?? 1);
        $availablePoints = (int) ($loyaltyProfile['diem_kha_dung'] ?? 0);
        $maThe = isset($loyaltyProfile['ma_the']) ? (int) $loyaltyProfile['ma_the'] : null;

        $voucherCatalog = $loyaltyService->getVoucherCatalogForCustomer($currentUserId, $customerTierId, $availablePoints);
        $pointLedger    = $loyaltyService->getPointLedgerByCard($maThe);
        $customerOrders = $loyaltyService->getCustomerOrders($currentUserId);
        $customerAddresses = $checkoutService->getCustomerAddresses($currentUserId);

        require __DIR__ . '/store/includes/header.php';
        require __DIR__ . '/store/pages/loyalty.php';
        require __DIR__ . '/store/includes/footer.php';
        break;

    case 'checkout':
        $pageTitle = 'Thanh toán Đa tầng | AURA STUDIO';
        $customerAddresses = $checkoutService->getCustomerAddresses($currentUserId);
        $availableVouchers = $checkoutService->getCustomerAvailableVouchers($currentUserId);
        $checkoutSummary   = $checkoutService->calculateCheckoutSummary($cartItems, $loyaltyProfile);

        require __DIR__ . '/store/includes/header.php';
        require __DIR__ . '/store/pages/checkout.php';
        require __DIR__ . '/store/includes/footer.php';
        break;

    case 'home':
    default:
        $pageTitle = 'Fashion Loyalty | AURA STUDIO';
        $allProducts         = $productService->getAllActiveProducts();
        $recommendedProducts = $productService->getRecommendedProducts(4);
        $subCategories       = $productService->getSubCategories();
        $filterOptions       = $productService->getAvailableFilterOptions();

        require __DIR__ . '/store/includes/header.php';
        require __DIR__ . '/store/pages/home.php';
        require __DIR__ . '/store/includes/footer.php';
        break;
}

