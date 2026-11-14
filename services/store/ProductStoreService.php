<?php
/**
 * DỊCH VỤ BÊN CỬA HÀNG: Sản phẩm, Danh mục, Biến thể (Size/Màu), Hình ảnh & Đánh giá
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class ProductStoreService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy danh sách danh mục gốc và danh mục con đang hoạt động
     */
    public function getCategories(): array
    {
        $stmt = $this->pdo->query("
            SELECT dm.*, cha.ten_dm AS ten_dm_cha, cha.duong_dan_tinh AS slug_cha
            FROM dm_danh_muc_sp dm
            LEFT JOIN dm_danh_muc_sp cha ON dm.ma_dm_cha = cha.ma_dm
            WHERE dm.trang_thai = 1
            ORDER BY COALESCE(dm.ma_dm_cha, dm.ma_dm), dm.ma_dm_cha IS NOT NULL, dm.thu_tu_hien_thi ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách danh mục con để hiển thị ở bộ lọc
     */
    public function getSubCategories(): array
    {
        $stmt = $this->pdo->query("
            SELECT dm.*, cha.ten_dm AS ten_dm_cha, cha.duong_dan_tinh AS slug_cha
            FROM dm_danh_muc_sp dm
            INNER JOIN dm_danh_muc_sp cha ON dm.ma_dm_cha = cha.ma_dm
            WHERE dm.trang_thai = 1
            ORDER BY dm.ma_dm_cha ASC, dm.thu_tu_hien_thi ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy toàn bộ sản phẩm đang mở bán kèm thông tin danh mục, biến thể (Size/Màu/Tồn kho) và bộ sưu tập ảnh
     */
    public function getAllActiveProducts(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                sp.ma_sp,
                sp.ma_dm,
                sp.ten_sp,
                sp.gia_niem_yet,
                sp.gia_khuyen_mai,
                sp.chat_lieu,
                sp.mo_ta_chi_tiet,
                sp.anh_chinh,
                sp.luot_xem,
                sp.trang_thai,
                dm.ten_dm,
                dm.duong_dan_tinh AS dm_slug,
                dm.ma_dm_cha,
                cha.ten_dm AS ten_dm_cha,
                cha.duong_dan_tinh AS dm_cha_slug
            FROM sp_san_pham sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            LEFT JOIN dm_danh_muc_sp cha ON dm.ma_dm_cha = cha.ma_dm
            WHERE sp.trang_thai = 1
            ORDER BY sp.ma_sp ASC
        ");
        $products = $stmt->fetchAll();
        if (empty($products)) {
            return [];
        }

        // Lấy toàn bộ biến thể của các sản phẩm
        $variantsStmt = $this->pdo->query("
            SELECT ma_bt, ma_sp, ma_sku, kich_co, mau_sac, gia_ban_rieng, so_luong_ton, trang_thai
            FROM bt_bien_the_sp
            WHERE trang_thai = 1
            ORDER BY ma_bt ASC
        ");
        $variantsByProduct = [];
        foreach ($variantsStmt->fetchAll() as $v) {
            $variantsByProduct[$v['ma_sp']][] = $v;
        }

        // Lấy toàn bộ hình ảnh sản phẩm
        $imagesStmt = $this->pdo->query("
            SELECT ma_ha, ma_sp, duong_dan_anh, mau_sac_lien_ket, thu_tu, la_anh_chinh
            FROM ha_hinh_anh_sp
            ORDER BY la_anh_chinh DESC, thu_tu ASC
        ");
        $imagesByProduct = [];
        foreach ($imagesStmt->fetchAll() as $img) {
            $img['resolved_url'] = resolveProductImage($img['duong_dan_anh']);
            $imagesByProduct[$img['ma_sp']][] = $img;
        }

        // Ghép dữ liệu chi tiết cho từng sản phẩm
        foreach ($products as &$p) {
            $pid = $p['ma_sp'];
            $p['anh_chinh_url'] = resolveProductImage($p['anh_chinh']);
            $p['gender_group'] = ($p['ma_dm_cha'] == 1 || $p['ma_dm'] == 1) ? 'nam' : 'nu';
            $p['variants'] = $variantsByProduct[$pid] ?? [];
            $p['images'] = $imagesByProduct[$pid] ?? [];

            if (empty($p['images'])) {
                $p['images'][] = [
                    'duong_dan_anh'    => $p['anh_chinh'],
                    'resolved_url'     => $p['anh_chinh_url'],
                    'mau_sac_lien_ket' => null,
                ];
            }

            $colors = [];
            $sizes = [];
            $totalStock = 0;
            foreach ($p['variants'] as $v) {
                if (!in_array($v['mau_sac'], $colors, true)) {
                    $colors[] = $v['mau_sac'];
                }
                if (!in_array($v['kich_co'], $sizes, true)) {
                    $sizes[] = $v['kich_co'];
                }
                $totalStock += (int) $v['so_luong_ton'];
            }
            $p['colors'] = $colors;
            $p['sizes'] = $sizes;
            $p['tong_ton_kho'] = $totalStock;
            $p['diem_thuong_du_kien'] = (int) floor((float) $p['gia_khuyen_mai'] / POINTS_PER_VND);
        }
        unset($p);

        return $products;
    }

    /**
     * Lấy danh sách sản phẩm gợi ý (4 sản phẩm có lượt xem cao nhất từ CSDL)
     */
    public function getRecommendedProducts(int $limit = 4): array
    {
        $all = $this->getAllActiveProducts();
        usort($all, fn($a, $b) => $b['luot_xem'] <=> $a['luot_xem']);
        return array_slice($all, 0, $limit);
    }

    /**
     * Lấy chi tiết 1 sản phẩm theo ma_sp kèm biến thể, ảnh và đánh giá thực tế từ khách hàng đã mua
     */
    public function getProductById(int $maSp): ?array
    {
        $all = $this->getAllActiveProducts();
        foreach ($all as $p) {
            if ((int) $p['ma_sp'] === $maSp) {
                $p['reviews'] = $this->getProductReviews($maSp);
                return $p;
            }
        }
        return null;
    }

    /**
     * Lấy danh sách đánh giá thực tế của sản phẩm từ bảng ctdh_chi_tiet_dh
     */
    public function getProductReviews(int $maSp): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                ct.ma_ctdh,
                ct.so_sao_danh_gia,
                ct.noi_dung_danh_gia,
                ct.da_cong_diem_dg,
                bt.kich_co,
                bt.mau_sac,
                dh.ngay_dat,
                nd.ho_ten,
                htv.ten_hang
            FROM ctdh_chi_tiet_dh ct
            INNER JOIN bt_bien_the_sp bt ON ct.ma_bt = bt.ma_bt
            INNER JOIN dh_don_hang dh ON ct.ma_dh = dh.ma_dh
            INNER JOIN nd_nguoi_dung nd ON dh.ma_nd = nd.ma_nd
            LEFT JOIN ttd_the_tich_diem ttd ON nd.ma_nd = ttd.ma_nd
            LEFT JOIN htv_hang_thanh_vien htv ON ttd.ma_hang = htv.ma_hang
            WHERE bt.ma_sp = :ma_sp
              AND ct.so_sao_danh_gia IS NOT NULL
            ORDER BY dh.ngay_dat DESC
        ");
        $stmt->execute(['ma_sp' => $maSp]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách tất cả Kích cỡ và Màu sắc hiện có trong CSDL để tạo bộ lọc động
     */
    public function getAvailableFilterOptions(): array
    {
        $sizesStmt = $this->pdo->query("
            SELECT DISTINCT kich_co FROM bt_bien_the_sp WHERE trang_thai = 1 ORDER BY ma_bt ASC
        ");
        $colorsStmt = $this->pdo->query("
            SELECT DISTINCT mau_sac FROM bt_bien_the_sp WHERE trang_thai = 1 ORDER BY ma_bt ASC
        ");
        return [
            'sizes'  => $sizesStmt->fetchAll(PDO::FETCH_COLUMN),
            'colors' => $colorsStmt->fetchAll(PDO::FETCH_COLUMN),
        ];
    }
}

