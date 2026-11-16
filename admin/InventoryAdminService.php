<?php
/**
 * DỊCH VỤ BÊN QUẢN TRỊ: Quản lý Tồn kho Biến thể SKU & Lịch sử Phiếu nhập kho
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class InventoryAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
        /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}


    /**
     * Lấy danh sách tất cả biến thể SKU cùng số lượng nhập, đã bán và tồn kho thực tế từ CSDL
     */
    public function getAllSkuInventory(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong) 
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            ORDER BY bt.ma_bt ASC
        ");
        return $stmt->fetchAll();
        /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}


    /**
     * Lấy danh sách chứng từ Phiếu nhập kho từ bảng pnk_phieu_nhap_kho
     */
    public function getImportReceipts(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                pnk.*,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                sp.ten_sp,
                nd.ho_ten AS nguoi_nhap_ten
            FROM pnk_phieu_nhap_kho pnk
            INNER JOIN bt_bien_the_sp bt ON pnk.ma_bt = bt.ma_bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN nd_nguoi_dung nd ON pnk.ma_nd_nhap = nd.ma_nd
            ORDER BY pnk.ngay_nhap DESC, pnk.ma_pnk DESC
        ");
        return $stmt->fetchAll();
        /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}


    /**
     * Lập phiếu nhập kho mới và tự động cập nhật số lượng tồn của biến thể SKU
     * Thực hiện trong PDO Transaction an toàn với FOR UPDATE
     */
    public function createImportReceipt(array $data): array
    {
        // 1. Validate dữ liệu đầu vào
        $maBt = (int)($data['ma_bt'] ?? 0);
        if ($maBt <= 0) {
            throw new InvalidArgumentException('Vui lòng chọn biến thể SKU hợp lệ cần nhập kho.');
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}


        $soLuongNhap = (int)($data['so_luong_nhap'] ?? 0);
        if ($soLuongNhap < 1) {
            throw new InvalidArgumentException('Số lượng nhập kho phải là số nguyên dương (tối thiểu 1 sản phẩm).');
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}


        if (!isset($data['gia_nhap']) || $data['gia_nhap'] === '') {
            throw new InvalidArgumentException('Vui lòng nhập giá vốn nhập kho của sản phẩm.');
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}

        $giaNhap = (float)$data['gia_nhap'];
        if ($giaNhap < 0) {
            throw new InvalidArgumentException('Giá vốn nhập kho không được là số âm.');
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}


        $nhaCungCap = isset($data['nha_cung_cap']) ? trim((string)$data['nha_cung_cap']) : '';
        if ($nhaCungCap !== '') {
            $nhaCungCap = mb_substr($nhaCungCap, 0, 150, 'UTF-8');
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}
 else {
            $nhaCungCap = null;
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}


        $ghiChu = isset($data['ghi_chu']) ? trim((string)$data['ghi_chu']) : '';
        if ($ghiChu !== '') {
            $ghiChu = mb_substr($ghiChu, 0, 255, 'UTF-8');
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}
 else {
            $ghiChu = null;
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}


        $maNdNhap = !empty($data['ma_nd_nhap']) ? (int)$data['ma_nd_nhap'] : getCurrentAdminId();
        if ($maNdNhap <= 0) {
            $maNdNhap = 1;
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}


        // 2. Thực hiện trong 1 PDO Transaction
        $this->pdo->beginTransaction();
        try {
            // Bước 1: Khóa và kiểm tra biến thể SKU tồn tại (FOR UPDATE)
            $lockStmt = $this->pdo->prepare("
                SELECT bt.ma_bt, bt.ma_sku, bt.kich_co, bt.mau_sac, bt.so_luong_ton, sp.ten_sp
                FROM bt_bien_the_sp bt
                INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
                WHERE bt.ma_bt = :ma_bt
                FOR UPDATE
            ");
            $lockStmt->execute([':ma_bt' => $maBt]);
            $variant = $lockStmt->fetch();

            if (!$variant) {
                throw new Exception('Không tìm thấy biến thể SKU được chọn trong CSDL!');
                /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}


            // Bước 2: Chèn chứng từ vào bảng pnk_phieu_nhap_kho
            $insertStmt = $this->pdo->prepare("
                INSERT INTO pnk_phieu_nhap_kho (ma_bt, ma_nd_nhap, so_luong_nhap, gia_nhap, nha_cung_cap, ghi_chu, ngay_nhap)
                VALUES (:ma_bt, :ma_nd_nhap, :so_luong_nhap, :gia_nhap, :nha_cung_cap, :ghi_chu, NOW())
            ");
            $insertStmt->execute([
                ':ma_bt'         => $maBt,
                ':ma_nd_nhap'    => $maNdNhap,
                ':so_luong_nhap' => $soLuongNhap,
                ':gia_nhap'      => $giaNhap,
                ':nha_cung_cap'  => $nhaCungCap,
                ':ghi_chu'       => $ghiChu,
            ]);
            $maPnk = (int)$this->pdo->lastInsertId();

            // Bước 3: Tự động cộng dồn tồn kho vào bảng bt_bien_the_sp
            $updateStmt = $this->pdo->prepare("
                UPDATE bt_bien_the_sp
                SET so_luong_ton = so_luong_ton + :so_luong_nhap
                WHERE ma_bt = :ma_bt
            ");
            $updateStmt->execute([
                ':so_luong_nhap' => $soLuongNhap,
                ':ma_bt'         => $maBt,
            ]);

            $this->pdo->commit();

            $tonCu = (int)$variant['so_luong_ton'];
            $tonMoi = $tonCu + $soLuongNhap;

            return [
                'ma_pnk'        => $maPnk,
                'ma_bt'         => $maBt,
                'ma_sku'        => $variant['ma_sku'],
                'ten_sp'        => $variant['ten_sp'],
                'kich_co'       => $variant['kich_co'],
                'mau_sac'       => $variant['mau_sac'],
                'so_luong_nhap' => $soLuongNhap,
                'gia_nhap'      => $giaNhap,
                'nha_cung_cap'  => $nhaCungCap,
                'ton_cu'        => $tonCu,
                'ton_moi'       => $tonMoi,
            ];
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}
 catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
                /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}

            throw $e;
            /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}

        /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}

    /**
     * Lấy danh sách SKU theo bộ lọc tồn kho và các tiêu chí tìm kiếm khác.
     *
     * @param string $stockFilter 'all' | 'low' (<=5) | 'out' (0)
     * @param string|null $search Từ khóa tìm kiếm trong tên sản phẩm hoặc SKU.
     * @param int|null $categoryId ID danh mục (nếu có) để lọc.
     * @return array Danh sách SKU phù hợp.
     */
    public function getSkuInventoryFiltered(string $stockFilter = 'all', ?string $search = null, ?int $categoryId = null): array
    {
        $where = [];
        $params = [];
        // Bộ lọc trạng thái tồn kho
        switch ($stockFilter) {
            case 'low':
                $where[] = 'bt.so_luong_ton <= :lowThreshold';
                $params[':lowThreshold'] = 5;
                break;
            case 'out':
                $where[] = 'bt.so_luong_ton = :outZero';
                $params[':outZero'] = 0;
                break;
            case 'all':
            default:
                // không thêm điều kiện
                break;
        }
        // Tìm kiếm theo tên sản phẩm hoặc mã SKU
        if ($search !== null && $search !== '') {
            $where[] = '(sp.ten_sp LIKE :search OR bt.ma_sku LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        // Lọc theo danh mục nếu truyền vào
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'dm.ma_dm = :categoryId';
            $params[':categoryId'] = $categoryId;
        }
        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT 
                bt.ma_bt,
                bt.ma_sp,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                bt.gia_ban_rieng,
                bt.so_luong_ton,
                bt.trang_thai,
                sp.ten_sp,
                dm.ten_dm,
                COALESCE((SELECT SUM(pnk.so_luong_nhap) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt), 0) AS tong_nhap,
                COALESCE((
                    SELECT SUM(ct.so_luong)
                    FROM ctdh_chi_tiet_dh ct 
                    INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
                    WHERE ct.ma_bt = bt.ma_bt AND dh.trang_thai_dh != 'DA_HUY'
                ), 0) AS tong_da_ban
            FROM bt_bien_the_sp bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            $whereSql
            ORDER BY bt.so_luong_ton ASC, bt.ma_bt ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm số lượng SKU cho từng trạng thái: tất cả, sắp hết (<=5) và hết (0).
     * Trả về mảng ['all'=>int, 'low'=>int, 'out'=>int].
     */
    public function getSkuStatusCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN so_luong_ton <= 5 THEN 1 ELSE 0 END) AS low,
                SUM(CASE WHEN so_luong_ton = 0 THEN 1 ELSE 0 END) AS out
            FROM bt_bien_the_sp
        ";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();
        return [
            'all' => (int)($row['total'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
        ];
    }
}

