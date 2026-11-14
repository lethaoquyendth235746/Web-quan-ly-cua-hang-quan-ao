<?php
/**
 * DỊCH VỤ BÊN QUẢN TRỊ: Quản lý Danh mục & Sản phẩm thời trang
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class ProductAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Chuyển đổi chuỗi tiếng Việt có dấu thành slug không dấu nối bằng dấu gạch ngang (-)
     */
    public static function slugifyVietnamese(string $str): string
    {
        $vietnameseMap = [
            'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a',
            'ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
            'â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
            'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
            'ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
            'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
            'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o',
            'ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
            'ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
            'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
            'ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
            'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
            'đ'=>'d',
            'À'=>'a','Á'=>'a','Ả'=>'a','Ã'=>'a','Ạ'=>'a',
            'Ă'=>'a','Ằ'=>'a','Ắ'=>'a','Ẳ'=>'a','Ẵ'=>'a','Ặ'=>'a',
            'Â'=>'a','Ầ'=>'a','Ấ'=>'a','Ẩ'=>'a','Ẫ'=>'a','Ậ'=>'a',
            'È'=>'e','É'=>'e','Ẻ'=>'e','Ẽ'=>'e','Ẹ'=>'e',
            'Ê'=>'e','Ề'=>'e','Ế'=>'e','Ể'=>'e','Ễ'=>'e','Ệ'=>'e',
            'Ì'=>'i','Í'=>'i','Ỉ'=>'i','Ĩ'=>'i','Ị'=>'i',
            'Ò'=>'o','Ó'=>'o','Ỏ'=>'o','Õ'=>'o','Ọ'=>'o',
            'Ô'=>'o','Ồ'=>'o','Ố'=>'o','Ổ'=>'o','Ỗ'=>'o','Ộ'=>'o',
            'Ơ'=>'o','Ờ'=>'o','Ớ'=>'o','Ở'=>'o','Ỡ'=>'o','Ợ'=>'o',
            'Ù'=>'u','Ú'=>'u','Ủ'=>'u','Ũ'=>'u','Ụ'=>'u',
            'Ư'=>'u','Ừ'=>'u','Ứ'=>'u','Ử'=>'u','Ữ'=>'u','Ự'=>'u',
            'Ỳ'=>'y','Ý'=>'y','Ỷ'=>'y','Ỹ'=>'y','Ỵ'=>'y',
            'Đ'=>'d',
        ];
        $str = strtr(trim($str), $vietnameseMap);
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/[^a-z0-9]+/i', '-', $str);
        $str = trim((string)$str, '-');
        return $str !== '' ? $str : 'danh-muc-' . time();
    }

    // =========================================================================
    // NHÓM PHƯƠNG THỨC DANH MỤC (dm_danh_muc_sp)
    // =========================================================================

    /**
     * Lấy danh sách toàn bộ danh mục sản phẩm (cả cha và con)
     */
    public function getAllCategories(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                dm.*,
                cha.ten_dm AS ten_dm_cha,
                (SELECT COUNT(*) FROM sp_san_pham sp WHERE sp.ma_dm = dm.ma_dm) AS so_san_pham,
                (SELECT COUNT(*) FROM dm_danh_muc_sp con WHERE con.ma_dm_cha = dm.ma_dm) AS so_dm_con
            FROM dm_danh_muc_sp dm
            LEFT JOIN dm_danh_muc_sp cha ON dm.ma_dm_cha = cha.ma_dm
            ORDER BY COALESCE(dm.ma_dm_cha, dm.ma_dm), dm.ma_dm_cha IS NOT NULL, dm.thu_tu_hien_thi ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh mục theo mã ID
     */
    public function getCategoryById(int $maDm): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dm_danh_muc_sp WHERE ma_dm = ?");
        $stmt->execute([$maDm]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Thêm danh mục mới vào dm_danh_muc_sp
     */
    public function createCategory(array $data): int
    {
        $tenDm = trim($data['ten_dm'] ?? '');
        if ($tenDm === '') {
            throw new InvalidArgumentException('Tên danh mục không được để trống.');
        }

        $slug = trim($data['duong_dan_tinh'] ?? '');
        if ($slug === '') {
            $slug = self::slugifyVietnamese($tenDm);
        } else {
            $slug = self::slugifyVietnamese($slug);
        }

        $maDmCha = !empty($data['ma_dm_cha']) ? (int)$data['ma_dm_cha'] : null;
        if ($maDmCha !== null) {
            $checkCha = $this->pdo->prepare("SELECT ma_dm FROM dm_danh_muc_sp WHERE ma_dm = ?");
            $checkCha->execute([$maDmCha]);
            if (!$checkCha->fetch()) {
                throw new InvalidArgumentException('Danh mục cha được chọn không tồn tại.');
            }
        }

        $moTa = isset($data['mo_ta']) ? trim($data['mo_ta']) : null;
        $thuTu = isset($data['thu_tu_hien_thi']) ? (int)$data['thu_tu_hien_thi'] : 1;
        $trangThai = isset($data['trang_thai']) ? (int)$data['trang_thai'] : 1;

        $stmt = $this->pdo->prepare("
            INSERT INTO dm_danh_muc_sp (ten_dm, duong_dan_tinh, ma_dm_cha, mo_ta, thu_tu_hien_thi, trang_thai)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tenDm, $slug, $maDmCha, $moTa, $thuTu, $trangThai]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Cập nhật danh mục trong dm_danh_muc_sp
     */
    public function updateCategory(int $maDm, array $data): bool
    {
        $current = $this->getCategoryById($maDm);
        if (!$current) {
            throw new InvalidArgumentException("Danh mục #{$maDm} không tồn tại.");
        }

        $tenDm = trim($data['ten_dm'] ?? '');
        if ($tenDm === '') {
            throw new InvalidArgumentException('Tên danh mục không được để trống.');
        }

        $slug = trim($data['duong_dan_tinh'] ?? '');
        if ($slug === '') {
            $slug = self::slugifyVietnamese($tenDm);
        } else {
            $slug = self::slugifyVietnamese($slug);
        }

        $maDmCha = !empty($data['ma_dm_cha']) ? (int)$data['ma_dm_cha'] : null;
        if ($maDmCha !== null) {
            if ($maDmCha === $maDm) {
                throw new InvalidArgumentException('Không thể chọn chính danh mục này làm danh mục cha.');
            }
            $checkCha = $this->pdo->prepare("SELECT ma_dm FROM dm_danh_muc_sp WHERE ma_dm = ?");
            $checkCha->execute([$maDmCha]);
            if (!$checkCha->fetch()) {
                throw new InvalidArgumentException('Danh mục cha được chọn không tồn tại.');
            }
        }

        $moTa = isset($data['mo_ta']) ? trim($data['mo_ta']) : null;
        $thuTu = isset($data['thu_tu_hien_thi']) ? (int)$data['thu_tu_hien_thi'] : 1;
        $trangThai = isset($data['trang_thai']) ? (int)$data['trang_thai'] : 1;

        $stmt = $this->pdo->prepare("
            UPDATE dm_danh_muc_sp
            SET ten_dm = ?, duong_dan_tinh = ?, ma_dm_cha = ?, mo_ta = ?, thu_tu_hien_thi = ?, trang_thai = ?
            WHERE ma_dm = ?
        ");
        return $stmt->execute([$tenDm, $slug, $maDmCha, $moTa, $thuTu, $trangThai, $maDm]);
    }

    /**
     * Xóa danh mục khi không vướng danh mục con và sản phẩm
     */
    public function deleteCategory(int $maDm): void
    {
        // 1. Kiểm tra danh mục con
        $stmtChild = $this->pdo->prepare("SELECT COUNT(*) FROM dm_danh_muc_sp WHERE ma_dm_cha = ?");
        $stmtChild->execute([$maDm]);
        if ((int)$stmtChild->fetchColumn() > 0) {
            throw new Exception("Không thể xóa danh mục #{$maDm} vì đang có danh mục con trực thuộc! Vui lòng xóa hoặc di chuyển các danh mục con trước.");
        }

        // 2. Kiểm tra sản phẩm đang thuộc danh mục
        $stmtProd = $this->pdo->prepare("SELECT COUNT(*) FROM sp_san_pham WHERE ma_dm = ?");
        $stmtProd->execute([$maDm]);
        $prodCount = (int)$stmtProd->fetchColumn();
        if ($prodCount > 0) {
            throw new Exception("Không thể xóa danh mục #{$maDm} vì đang chứa {$prodCount} sản phẩm liên kết! Vui lòng chuyển các sản phẩm sang danh mục khác trước khi xóa.");
        }

        // 3. Tiến hành xóa
        $stmtDel = $this->pdo->prepare("DELETE FROM dm_danh_muc_sp WHERE ma_dm = ?");
        $stmtDel->execute([$maDm]);
    }

    // =========================================================================
    // NHÓM PHƯƠNG THỨC SẢN PHẨM & UPLOAD ẢNH (sp_san_pham, ha_hinh_anh_sp)
    // =========================================================================

    /**
     * Xử lý upload file ảnh vật lý vào assets/images/
     * Trả về tên file an toàn (sp_timestamp_random.ext) hoặc null nếu không chọn file
     */
    public function handleImageUpload(?array $file): ?string
    {
        if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Lỗi khi tải file ảnh lên (Mã lỗi: ' . $file['error'] . ').');
        }

        // Kiểm tra dung lượng tối đa 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Dung lượng file ảnh vượt quá giới hạn cho phép (Tối đa 5MB).');
        }

        // Kiểm tra phần mở rộng
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts, true)) {
            throw new Exception('Định dạng ảnh không hợp lệ. Chỉ chấp nhận các định dạng: ' . implode(', ', $allowedExts));
        }

        // Kiểm tra tính hợp lệ của file ảnh thực tế
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('File tải lên không phải là ảnh hợp lệ hoặc file đã bị hỏng.');
        }

        // Tự động tạo thư mục assets/images nếu chưa tồn tại
        $targetDir = dirname(__DIR__, 2) . '/assets/images';
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new Exception('Không thể tạo thư mục lưu trữ ảnh trên máy chủ.');
            }
        }

        // Sinh tên file an toàn
        $fileName = 'sp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $targetDir . '/' . $fileName;

        if (is_uploaded_file($file['tmp_name'])) {
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception('Không thể lưu file ảnh vào thư mục máy chủ. Vui lòng kiểm tra quyền ghi thư mục assets/images.');
            }
        } else {
            // Hỗ trợ môi trường CLI test / nội bộ
            if (!copy($file['tmp_name'], $destination)) {
                throw new Exception('Không thể lưu file ảnh vào thư mục máy chủ.');
            }
            @unlink($file['tmp_name']);
        }

        return $fileName;
    }

    /**
     * Lấy danh sách sản phẩm kèm danh mục, số lượng biến thể SKU và tổng tồn kho từ CSDL
     */
    public function getAllProducts(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                sp.*,
                dm.ten_dm,
                dm.duong_dan_tinh AS dm_slug,
                cha.ten_dm AS ten_dm_cha,
                (SELECT COUNT(*) FROM bt_bien_the_sp bt WHERE bt.ma_sp = sp.ma_sp) AS so_bien_the,
                (SELECT COALESCE(SUM(bt.so_luong_ton), 0) FROM bt_bien_the_sp bt WHERE bt.ma_sp = sp.ma_sp) AS tong_ton_kho
            FROM sp_san_pham sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            LEFT JOIN dm_danh_muc_sp cha ON dm.ma_dm_cha = cha.ma_dm
            ORDER BY sp.ma_sp ASC
        ");
        $products = $stmt->fetchAll();

        foreach ($products as &$p) {
            $img = resolveProductImage($p['anh_chinh']);
            // Nếu là đường dẫn nội bộ assets/images/..., khi hiển thị ở trang admin/products.php cần ../ để trỏ về thư mục gốc
            if (strpos($img, 'assets/images/') === 0) {
                $img = '../' . $img;
            }
            $p['anh_chinh_url'] = $img;
            $p['ma_sp_code'] = sprintf('SP-%03d', $p['ma_sp']);
        }
        unset($p);

        return $products;
    }

    /**
     * Lấy thông tin chi tiết một sản phẩm theo ID
     */
    public function getProductById(int $maSp): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT sp.*, dm.ten_dm, cha.ten_dm AS ten_dm_cha
            FROM sp_san_pham sp
            INNER JOIN dm_danh_muc_sp dm ON sp.ma_dm = dm.ma_dm
            LEFT JOIN dm_danh_muc_sp cha ON dm.ma_dm_cha = cha.ma_dm
            WHERE sp.ma_sp = ?
        ");
        $stmt->execute([$maSp]);
        $p = $stmt->fetch();
        if (!$p) {
            return null;
        }

        $img = resolveProductImage($p['anh_chinh']);
        if (strpos($img, 'assets/images/') === 0) {
            $img = '../' . $img;
        }
        $p['anh_chinh_url'] = $img;
        $p['ma_sp_code'] = sprintf('SP-%03d', $p['ma_sp']);
        return $p;
    }

    /**
     * Tạo mới sản phẩm kèm upload ảnh vật lý trong Transaction
     */
    public function createProduct(array $data, ?array $imageFile): int
    {
        $tenSp = trim($data['ten_sp'] ?? '');
        if ($tenSp === '') {
            throw new InvalidArgumentException('Tên sản phẩm không được để trống.');
        }

        $maDm = (int)($data['ma_dm'] ?? 0);
        if ($maDm <= 0) {
            throw new InvalidArgumentException('Vui lòng chọn danh mục hợp lệ cho sản phẩm.');
        }

        // Kiểm tra danh mục có tồn tại không
        $checkDm = $this->pdo->prepare("SELECT ma_dm FROM dm_danh_muc_sp WHERE ma_dm = ?");
        $checkDm->execute([$maDm]);
        if (!$checkDm->fetch()) {
            throw new InvalidArgumentException('Danh mục được chọn không tồn tại.');
        }

        $giaNiemYet = (float)($data['gia_niem_yet'] ?? 0);
        $giaKhuyenMai = (float)($data['gia_khuyen_mai'] ?? 0);

        if ($giaNiemYet < 0 || $giaKhuyenMai < 0) {
            throw new InvalidArgumentException('Giá sản phẩm không được là số âm.');
        }
        if ($giaKhuyenMai > $giaNiemYet) {
            throw new InvalidArgumentException('Giá khuyến mãi (' . number_format($giaKhuyenMai, 0, ',', '.') . 'đ) không được lớn hơn giá niêm yết (' . number_format($giaNiemYet, 0, ',', '.') . 'đ).');
        }

        $chatLieu = trim($data['chat_lieu'] ?? '');
        $moTaChiTiet = trim($data['mo_ta_chi_tiet'] ?? '');
        $trangThai = isset($data['trang_thai']) ? (int)$data['trang_thai'] : 1;

        // Xử lý upload ảnh trước
        $uploadedFileName = $this->handleImageUpload($imageFile);
        $anhChinh = $uploadedFileName ?: 'no_image.jpg';

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO sp_san_pham (ma_dm, ten_sp, gia_niem_yet, gia_khuyen_mai, chat_lieu, mo_ta_chi_tiet, anh_chinh, luot_xem, trang_thai)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
            ");
            $stmt->execute([
                $maDm,
                $tenSp,
                $giaNiemYet,
                $giaKhuyenMai,
                $chatLieu ?: null,
                $moTaChiTiet ?: null,
                $anhChinh,
                $trangThai
            ]);
            $maSp = (int)$this->pdo->lastInsertId();

            // Nếu có upload ảnh thật, lưu thêm vào bảng ha_hinh_anh_sp làm ảnh chính
            if ($uploadedFileName !== null) {
                $imgStmt = $this->pdo->prepare("
                    INSERT INTO ha_hinh_anh_sp (ma_sp, duong_dan_anh, mau_sac_lien_ket, thu_tu, la_anh_chinh)
                    VALUES (?, ?, NULL, 1, 1)
                ");
                $imgStmt->execute([$maSp, $uploadedFileName]);
            }

            $this->pdo->commit();
            return $maSp;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Dọn dẹp file ảnh vừa upload nếu ghi DB thất bại
            if ($uploadedFileName) {
                $localPath = dirname(__DIR__, 2) . '/assets/images/' . $uploadedFileName;
                if (file_exists($localPath)) {
                    @unlink($localPath);
                }
            }
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin sản phẩm và upload ảnh mới (nếu có)
     */
    public function updateProduct(int $maSp, array $data, ?array $imageFile): bool
    {
        $current = $this->getProductById($maSp);
        if (!$current) {
            throw new InvalidArgumentException("Sản phẩm #{$maSp} không tồn tại.");
        }

        $tenSp = trim($data['ten_sp'] ?? '');
        if ($tenSp === '') {
            throw new InvalidArgumentException('Tên sản phẩm không được để trống.');
        }

        $maDm = (int)($data['ma_dm'] ?? 0);
        if ($maDm <= 0) {
            throw new InvalidArgumentException('Vui lòng chọn danh mục hợp lệ.');
        }

        $checkDm = $this->pdo->prepare("SELECT ma_dm FROM dm_danh_muc_sp WHERE ma_dm = ?");
        $checkDm->execute([$maDm]);
        if (!$checkDm->fetch()) {
            throw new InvalidArgumentException('Danh mục được chọn không tồn tại.');
        }

        $giaNiemYet = (float)($data['gia_niem_yet'] ?? 0);
        $giaKhuyenMai = (float)($data['gia_khuyen_mai'] ?? 0);

        if ($giaNiemYet < 0 || $giaKhuyenMai < 0) {
            throw new InvalidArgumentException('Giá sản phẩm không được là số âm.');
        }
        if ($giaKhuyenMai > $giaNiemYet) {
            throw new InvalidArgumentException('Giá khuyến mãi không được lớn hơn giá niêm yết.');
        }

        $chatLieu = trim($data['chat_lieu'] ?? '');
        $moTaChiTiet = trim($data['mo_ta_chi_tiet'] ?? '');
        $trangThai = isset($data['trang_thai']) ? (int)$data['trang_thai'] : 1;

        $newImage = $this->handleImageUpload($imageFile);

        $this->pdo->beginTransaction();
        try {
            if ($newImage !== null) {
                $stmt = $this->pdo->prepare("
                    UPDATE sp_san_pham
                    SET ma_dm = ?, ten_sp = ?, gia_niem_yet = ?, gia_khuyen_mai = ?, chat_lieu = ?, mo_ta_chi_tiet = ?, anh_chinh = ?, trang_thai = ?
                    WHERE ma_sp = ?
                ");
                $stmt->execute([
                    $maDm,
                    $tenSp,
                    $giaNiemYet,
                    $giaKhuyenMai,
                    $chatLieu ?: null,
                    $moTaChiTiet ?: null,
                    $newImage,
                    $trangThai,
                    $maSp
                ]);

                // Đặt các ảnh cũ của sản phẩm này về la_anh_chinh = 0
                $stmtReset = $this->pdo->prepare("UPDATE ha_hinh_anh_sp SET la_anh_chinh = 0 WHERE ma_sp = ?");
                $stmtReset->execute([$maSp]);

                // Lưu ảnh mới vào ha_hinh_anh_sp với la_anh_chinh = 1
                $stmtImg = $this->pdo->prepare("
                    INSERT INTO ha_hinh_anh_sp (ma_sp, duong_dan_anh, mau_sac_lien_ket, thu_tu, la_anh_chinh)
                    VALUES (?, ?, NULL, 1, 1)
                ");
                $stmtImg->execute([$maSp, $newImage]);
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE sp_san_pham
                    SET ma_dm = ?, ten_sp = ?, gia_niem_yet = ?, gia_khuyen_mai = ?, chat_lieu = ?, mo_ta_chi_tiet = ?, trang_thai = ?
                    WHERE ma_sp = ?
                ");
                $stmt->execute([
                    $maDm,
                    $tenSp,
                    $giaNiemYet,
                    $giaKhuyenMai,
                    $chatLieu ?: null,
                    $moTaChiTiet ?: null,
                    $trangThai,
                    $maSp
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($newImage) {
                $localPath = dirname(__DIR__, 2) . '/assets/images/' . $newImage;
                if (file_exists($localPath)) {
                    @unlink($localPath);
                }
            }
            throw $e;
        }
    }

    /**
     * Bật / tắt trạng thái hiển thị của sản phẩm (Đang bán <-> Tạm ẩn)
     */
    public function toggleProductStatus(int $maSp): int
    {
        $stmt = $this->pdo->prepare("UPDATE sp_san_pham SET trang_thai = 1 - trang_thai WHERE ma_sp = ?");
        $stmt->execute([$maSp]);

        $check = $this->pdo->prepare("SELECT trang_thai FROM sp_san_pham WHERE ma_sp = ?");
        $check->execute([$maSp]);
        return (int)$check->fetchColumn();
    }

    /**
     * Xóa sản phẩm khỏi CSDL nếu chưa phát sinh đơn hàng hoặc phiếu nhập kho
     */
    public function deleteProduct(int $maSp): void
    {
        // 1. Kiểm tra đơn hàng liên quan đến bất kỳ biến thể nào của sản phẩm
        $stmtOrder = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM ctdh_chi_tiet_dh ct
            INNER JOIN bt_bien_the_sp bt ON ct.ma_bt = bt.ma_bt
            WHERE bt.ma_sp = ?
        ");
        $stmtOrder->execute([$maSp]);
        $orderCount = (int)$stmtOrder->fetchColumn();

        // 2. Kiểm tra phiếu nhập kho
        $stmtPnk = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM pnk_phieu_nhap_kho pnk
            INNER JOIN bt_bien_the_sp bt ON pnk.ma_bt = bt.ma_bt
            WHERE bt.ma_sp = ?
        ");
        $stmtPnk->execute([$maSp]);
        $pnkCount = (int)$stmtPnk->fetchColumn();

        if ($orderCount > 0 || $pnkCount > 0) {
            throw new Exception("Sản phẩm đã phát sinh giao dịch đơn hàng ({$orderCount} mục) hoặc phiếu nhập kho ({$pnkCount} phiếu), không thể xóa cứng. Vui lòng chuyển sang trạng thái Tạm ẩn!");
        }

        // 3. Lấy danh sách tên file ảnh để dọn dẹp vật lý nếu có
        $stmtImgs = $this->pdo->prepare("
            SELECT duong_dan_anh FROM ha_hinh_anh_sp WHERE ma_sp = ?
            UNION
            SELECT anh_chinh FROM sp_san_pham WHERE ma_sp = ?
        ");
        $stmtImgs->execute([$maSp, $maSp]);
        $images = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);

        // 4. Xóa sản phẩm (Ràng buộc ON DELETE CASCADE tự dọn dẹp bt_bien_the_sp, ha_hinh_anh_sp, gh_gio_hang)
        $stmtDel = $this->pdo->prepare("DELETE FROM sp_san_pham WHERE ma_sp = ?");
        $stmtDel->execute([$maSp]);

        // 5. Xóa file ảnh vật lý nếu tồn tại trong thư mục assets/images
        $assetsDir = dirname(__DIR__, 2) . '/assets/images/';
        foreach ($images as $imgFile) {
            if ($imgFile && !preg_match('/^https?:\/\//i', $imgFile) && $imgFile !== 'no_image.jpg') {
                $filePath = $assetsDir . $imgFile;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }
    }

    // =========================================================================
    // NHÓM PHƯƠNG THỨC BIẾN THỂ SIZE & MÀU (bt_bien_the_sp)
    // =========================================================================

    /**
     * Lấy toàn bộ biến thể của 1 sản phẩm kèm số lượng đã bán & số phiếu nhập
     */
    public function getVariantsByProduct(int $maSp): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                bt.*,
                (SELECT COALESCE(SUM(ct.so_luong), 0) FROM ctdh_chi_tiet_dh ct WHERE ct.ma_bt = bt.ma_bt) AS so_luong_da_ban,
                (SELECT COUNT(*) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt) AS so_phieu_nhap
            FROM bt_bien_the_sp bt
            WHERE bt.ma_sp = ?
            ORDER BY bt.ma_bt ASC
        ");
        $stmt->execute([$maSp]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy toàn bộ biến thể được nhóm theo mã sản phẩm [ma_sp => [danh sách biến thể]]
     * Giúp trang admin nạp sẵn dữ liệu cho Modal mà không cần gọi AJAX
     */
    public function getAllVariantsGroupedByProduct(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                bt.*,
                (SELECT COALESCE(SUM(ct.so_luong), 0) FROM ctdh_chi_tiet_dh ct WHERE ct.ma_bt = bt.ma_bt) AS so_luong_da_ban,
                (SELECT COUNT(*) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt) AS so_phieu_nhap
            FROM bt_bien_the_sp bt
            ORDER BY bt.ma_sp ASC, bt.ma_bt ASC
        ");
        $rows = $stmt->fetchAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['ma_sp']][] = $row;
        }
        return $grouped;
    }

    /**
     * Lấy thông tin 1 biến thể theo ID
     */
    public function getVariantById(int $maBt): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                bt.*,
                (SELECT COALESCE(SUM(ct.so_luong), 0) FROM ctdh_chi_tiet_dh ct WHERE ct.ma_bt = bt.ma_bt) AS so_luong_da_ban,
                (SELECT COUNT(*) FROM pnk_phieu_nhap_kho pnk WHERE pnk.ma_bt = bt.ma_bt) AS so_phieu_nhap
            FROM bt_bien_the_sp bt
            WHERE bt.ma_bt = ?
        ");
        $stmt->execute([$maBt]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Thêm biến thể mới cho sản phẩm
     */
    public function createVariant(int $maSp, array $data): int
    {
        $checkProd = $this->pdo->prepare("SELECT ma_sp FROM sp_san_pham WHERE ma_sp = ?");
        $checkProd->execute([$maSp]);
        if (!$checkProd->fetch()) {
            throw new InvalidArgumentException("Sản phẩm #{$maSp} không tồn tại.");
        }

        $sku = strtoupper(trim($data['ma_sku'] ?? ''));
        if ($sku === '') {
            throw new InvalidArgumentException('Mã SKU biến thể không được để trống.');
        }

        // Kiểm tra tính duy nhất của mã SKU
        $checkSku = $this->pdo->prepare("SELECT COUNT(*) FROM bt_bien_the_sp WHERE ma_sku = ?");
        $checkSku->execute([$sku]);
        if ((int)$checkSku->fetchColumn() > 0) {
            throw new Exception("Mã SKU '{$sku}' đã tồn tại trong hệ thống. Vui lòng đặt mã SKU khác!");
        }

        $kichCo = trim($data['kich_co'] ?? '');
        if ($kichCo === '') {
            throw new InvalidArgumentException('Kích cỡ biến thể không được để trống.');
        }

        $mauSac = trim($data['mau_sac'] ?? '');
        if ($mauSac === '') {
            throw new InvalidArgumentException('Màu sắc biến thể không được để trống.');
        }

        $giaBanRieng = (float)($data['gia_ban_rieng'] ?? 0);
        if ($giaBanRieng <= 0) {
            throw new InvalidArgumentException('Giá bán riêng của biến thể phải lớn hơn 0.');
        }

        $soLuongTon = isset($data['so_luong_ton']) ? (int)$data['so_luong_ton'] : 0;
        if ($soLuongTon < 0) {
            throw new InvalidArgumentException('Số lượng tồn kho ban đầu không được âm.');
        }

        $trangThai = isset($data['trang_thai']) ? (int)$data['trang_thai'] : 1;

        $stmt = $this->pdo->prepare("
            INSERT INTO bt_bien_the_sp (ma_sp, ma_sku, kich_co, mau_sac, gia_ban_rieng, so_luong_ton, trang_thai)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$maSp, $sku, $kichCo, $mauSac, $giaBanRieng, $soLuongTon, $trangThai]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Cập nhật biến thể
     */
    public function updateVariant(int $maBt, array $data): bool
    {
        $current = $this->getVariantById($maBt);
        if (!$current) {
            throw new InvalidArgumentException("Biến thể #{$maBt} không tồn tại.");
        }

        $sku = strtoupper(trim($data['ma_sku'] ?? ''));
        if ($sku === '') {
            throw new InvalidArgumentException('Mã SKU không được để trống.');
        }

        // Kiểm tra SKU không trùng với biến thể khác
        $checkSku = $this->pdo->prepare("SELECT COUNT(*) FROM bt_bien_the_sp WHERE ma_sku = ? AND ma_bt != ?");
        $checkSku->execute([$sku, $maBt]);
        if ((int)$checkSku->fetchColumn() > 0) {
            throw new Exception("Mã SKU '{$sku}' đã được sử dụng bởi biến thể khác!");
        }

        $kichCo = trim($data['kich_co'] ?? '');
        if ($kichCo === '') {
            throw new InvalidArgumentException('Kích cỡ biến thể không được để trống.');
        }

        $mauSac = trim($data['mau_sac'] ?? '');
        if ($mauSac === '') {
            throw new InvalidArgumentException('Màu sắc biến thể không được để trống.');
        }

        $giaBanRieng = (float)($data['gia_ban_rieng'] ?? 0);
        if ($giaBanRieng <= 0) {
            throw new InvalidArgumentException('Giá bán riêng phải lớn hơn 0.');
        }

        $trangThai = isset($data['trang_thai']) ? (int)$data['trang_thai'] : 1;

        // Nếu biến thể đã có phiếu nhập kho, không cho phép chỉnh sửa trực tiếp số lượng tồn để bảo đảm đối soát
        $hasPnk = ((int)($current['so_phieu_nhap'] ?? 0)) > 0;
        if ($hasPnk) {
            $stmt = $this->pdo->prepare("
                UPDATE bt_bien_the_sp
                SET ma_sku = ?, kich_co = ?, mau_sac = ?, gia_ban_rieng = ?, trang_thai = ?
                WHERE ma_bt = ?
            ");
            return $stmt->execute([$sku, $kichCo, $mauSac, $giaBanRieng, $trangThai, $maBt]);
        } else {
            $soLuongTon = isset($data['so_luong_ton']) ? max(0, (int)$data['so_luong_ton']) : (int)$current['so_luong_ton'];
            $stmt = $this->pdo->prepare("
                UPDATE bt_bien_the_sp
                SET ma_sku = ?, kich_co = ?, mau_sac = ?, gia_ban_rieng = ?, so_luong_ton = ?, trang_thai = ?
                WHERE ma_bt = ?
            ");
            return $stmt->execute([$sku, $kichCo, $mauSac, $giaBanRieng, $soLuongTon, $trangThai, $maBt]);
        }
    }

    /**
     * Xóa biến thể khỏi CSDL nếu chưa phát sinh đơn hàng hoặc phiếu nhập kho
     */
    public function deleteVariant(int $maBt): void
    {
        // 1. Kiểm tra đơn hàng
        $stmtOrder = $this->pdo->prepare("SELECT COUNT(*) FROM ctdh_chi_tiet_dh WHERE ma_bt = ?");
        $stmtOrder->execute([$maBt]);
        if ((int)$stmtOrder->fetchColumn() > 0) {
            throw new Exception("Biến thể SKU này đã có trong lịch sử đơn hàng của khách, không thể xóa cứng. Vui lòng chuyển trạng thái sang Tạm ẩn (Ngưng bán)!");
        }

        // 2. Kiểm tra phiếu nhập kho
        $stmtPnk = $this->pdo->prepare("SELECT COUNT(*) FROM pnk_phieu_nhap_kho WHERE ma_bt = ?");
        $stmtPnk->execute([$maBt]);
        if ((int)$stmtPnk->fetchColumn() > 0) {
            throw new Exception("Biến thể SKU này đã có lịch sử chứng từ nhập kho, không thể xóa cứng. Vui lòng chuyển trạng thái sang Tạm ẩn (Ngưng bán)!");
        }

        // 3. Xóa biến thể (gh_gio_hang sẽ tự động ON DELETE CASCADE)
        $stmtDel = $this->pdo->prepare("DELETE FROM bt_bien_the_sp WHERE ma_bt = ?");
        $stmtDel->execute([$maBt]);
    }
}
