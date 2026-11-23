<?php
/**
 * DỊCH VỤ SỔ ĐỊA CHỈ NHẬN HÀNG (AddressService)
 * Quản lý Sổ địa chỉ nhận hàng của khách hàng (bảng dc_dia_chi_kh)
 * Chống lỗ hổng IDOR bằng cách ràng buộc 100% truy vấn với ma_nd của người dùng hiện tại
 */

require_once __DIR__ . '/../../config/db.php';

class AddressService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy toàn bộ danh sách địa chỉ của khách hàng (Ưu tiên địa chỉ mặc định lên đầu)
     *
     * @param int $maNd Mã người dùng
     * @return array Danh sách bản ghi từ bảng dc_dia_chi_kh
     */
    public function getAddressesByUserId(int $maNd): array
    {
        if ($maNd <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT 
                ma_dc,
                ma_nd,
                ten_nguoi_nhan,
                sdt_nhan,
                tinh_thanh,
                quan_huyen,
                phuong_xa,
                dia_chi_cu_the,
                mac_dinh
            FROM dc_dia_chi_kh
            WHERE ma_nd = :ma_nd
            ORDER BY mac_dinh DESC, ma_dc DESC
        ");
        $stmt->execute(['ma_nd' => $maNd]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy thông tin 1 địa chỉ theo mã, đảm bảo thuộc quyền sở hữu của ma_nd (Chống IDOR)
     *
     * @param int $maDc Mã địa chỉ
     * @param int $maNd Mã người dùng sở hữu
     * @return array|null Bản ghi địa chỉ hoặc null nếu không tìm thấy/không thuộc quyền
     */
    public function getAddressById(int $maDc, int $maNd): ?array
    {
        if ($maDc <= 0 || $maNd <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM dc_dia_chi_kh
            WHERE ma_dc = :ma_dc AND ma_nd = :ma_nd
            LIMIT 1
        ");
        $stmt->execute([
            'ma_dc' => $maDc,
            'ma_nd' => $maNd,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Thêm mới địa chỉ nhận hàng vào bảng dc_dia_chi_kh
     *
     * @param int   $maNd Mã người dùng
     * @param array $data ['ten_nguoi_nhan', 'sdt_nhan', 'tinh_thanh', 'quan_huyen', 'phuong_xa', 'dia_chi_cu_the', 'mac_dinh']
     * @return array ['success' => bool, 'message' => string, 'ma_dc' => ?int]
     */
    public function createAddress(int $maNd, array $data): array
    {
        if ($maNd <= 0) {
            return ['success' => false, 'message' => 'Yêu cầu đăng nhập để thực hiện thao tác.'];
        }

        $tenNguoiNhan = trim($data['ten_nguoi_nhan'] ?? '');
        $sdtNhan      = trim($data['sdt_nhan'] ?? '');
        $tinhThanh    = trim($data['tinh_thanh'] ?? '');
        $quanHuyen    = trim($data['quan_huyen'] ?? '');
        $phuongXa     = trim($data['phuong_xa'] ?? '');
        $diaChiCuThe  = trim($data['dia_chi_cu_the'] ?? '');
        $macDinhReq   = !empty($data['mac_dinh']) ? 1 : 0;

        if ($tenNguoiNhan === '' || $sdtNhan === '' || $tinhThanh === '' || $quanHuyen === '' || $phuongXa === '' || $diaChiCuThe === '') {
            return ['success' => false, 'message' => 'Vui lòng điền đầy đủ các thông tin địa chỉ nhận hàng.'];
        }

        if (!preg_match('/^[0-9]{9,15}$/', $sdtNhan)) {
            return ['success' => false, 'message' => 'Số điện thoại nhận hàng không hợp lệ (9-15 số).'];
        }

        $this->pdo->beginTransaction();
        try {
            // Kiểm tra số lượng địa chỉ hiện tại của người dùng
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM dc_dia_chi_kh WHERE ma_nd = :ma_nd");
            $countStmt->execute(['ma_nd' => $maNd]);
            $existingCount = (int)$countStmt->fetchColumn();

            // Nếu đây là địa chỉ đầu tiên hoặc khách chọn "Đặt làm mặc định"
            $macDinh = ($existingCount === 0 || $macDinhReq === 1) ? 1 : 0;

            if ($macDinh === 1 && $existingCount > 0) {
                // Đưa tất cả địa chỉ cũ của khách về 0
                $resetStmt = $this->pdo->prepare("UPDATE dc_dia_chi_kh SET mac_dinh = 0 WHERE ma_nd = :ma_nd");
                $resetStmt->execute(['ma_nd' => $maNd]);
            }

            $insertStmt = $this->pdo->prepare("
                INSERT INTO dc_dia_chi_kh (
                    ma_nd,
                    ten_nguoi_nhan,
                    sdt_nhan,
                    tinh_thanh,
                    quan_huyen,
                    phuong_xa,
                    dia_chi_cu_the,
                    mac_dinh
                ) VALUES (
                    :ma_nd,
                    :ten_nguoi_nhan,
                    :sdt_nhan,
                    :tinh_thanh,
                    :quan_huyen,
                    :phuong_xa,
                    :dia_chi_cu_the,
                    :mac_dinh
                )
            ");
            $insertStmt->execute([
                'ma_nd'          => $maNd,
                'ten_nguoi_nhan' => $tenNguoiNhan,
                'sdt_nhan'       => $sdtNhan,
                'tinh_thanh'     => $tinhThanh,
                'quan_huyen'     => $quanHuyen,
                'phuong_xa'      => $phuongXa,
                'dia_chi_cu_the' => $diaChiCuThe,
                'mac_dinh'       => $macDinh,
            ]);

            $newId = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Đã thêm địa chỉ nhận hàng thành công.',
                'ma_dc'   => $newId,
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Lỗi khi lưu địa chỉ: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Chỉnh sửa thông tin địa chỉ nhận hàng (Chống IDOR: ràng buộc ma_nd)
     */
    public function updateAddress(int $maDc, int $maNd, array $data): array
    {
        if ($maDc <= 0 || $maNd <= 0) {
            return ['success' => false, 'message' => 'Dữ liệu yêu cầu không hợp lệ.'];
        }

        // 1. Xác thực quyền sở hữu địa chỉ
        $existing = $this->getAddressById($maDc, $maNd);
        if (!$existing) {
            return ['success' => false, 'message' => 'Không tìm thấy địa chỉ hoặc bạn không có quyền sửa địa chỉ này.'];
        }

        $tenNguoiNhan = trim($data['ten_nguoi_nhan'] ?? '');
        $sdtNhan      = trim($data['sdt_nhan'] ?? '');
        $tinhThanh    = trim($data['tinh_thanh'] ?? '');
        $quanHuyen    = trim($data['quan_huyen'] ?? '');
        $phuongXa     = trim($data['phuong_xa'] ?? '');
        $diaChiCuThe  = trim($data['dia_chi_cu_the'] ?? '');
        $macDinhReq   = !empty($data['mac_dinh']) ? 1 : 0;

        if ($tenNguoiNhan === '' || $sdtNhan === '' || $tinhThanh === '' || $quanHuyen === '' || $phuongXa === '' || $diaChiCuThe === '') {
            return ['success' => false, 'message' => 'Vui lòng điền đầy đủ các thông tin địa chỉ.'];
        }

        if (!preg_match('/^[0-9]{9,15}$/', $sdtNhan)) {
            return ['success' => false, 'message' => 'Số điện thoại nhận hàng không hợp lệ (9-15 số).'];
        }

        $this->pdo->beginTransaction();
        try {
            // Xử lý mặc định
            if ($macDinhReq === 1) {
                // Đưa tất cả địa chỉ của khách về 0 trước
                $resetStmt = $this->pdo->prepare("UPDATE dc_dia_chi_kh SET mac_dinh = 0 WHERE ma_nd = :ma_nd");
                $resetStmt->execute(['ma_nd' => $maNd]);
                $macDinh = 1;
            } else {
                // Nếu đây là địa chỉ duy nhất, bắt buộc giữ là mặc định
                $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM dc_dia_chi_kh WHERE ma_nd = :ma_nd");
                $countStmt->execute(['ma_nd' => $maNd]);
                if ((int)$countStmt->fetchColumn() <= 1) {
                    $macDinh = 1;
                } else {
                    $macDinh = 0;
                }
            }

            $updateStmt = $this->pdo->prepare("
                UPDATE dc_dia_chi_kh
                SET 
                    ten_nguoi_nhan = :ten,
                    sdt_nhan       = :sdt,
                    tinh_thanh     = :tinh,
                    quan_huyen     = :quan,
                    phuong_xa      = :phuong,
                    dia_chi_cu_the = :dc,
                    mac_dinh       = :mac_dinh
                WHERE ma_dc = :ma_dc AND ma_nd = :ma_nd
            ");
            $updateStmt->execute([
                'ten'      => $tenNguoiNhan,
                'sdt'      => $sdtNhan,
                'tinh'     => $tinhThanh,
                'quan'     => $quanHuyen,
                'phuong'   => $phuongXa,
                'dc'       => $diaChiCuThe,
                'mac_dinh' => $macDinh,
                'ma_dc'    => $maDc,
                'ma_nd'    => $maNd,
            ]);

            // Nếu người dùng bỏ tích mặc định nhưng chưa có địa chỉ nào khác là mặc định -> gán 1 địa chỉ bất kỳ thành mặc định
            $checkDefStmt = $this->pdo->prepare("SELECT COUNT(*) FROM dc_dia_chi_kh WHERE ma_nd = :ma_nd AND mac_dinh = 1");
            $checkDefStmt->execute(['ma_nd' => $maNd]);
            if ((int)$checkDefStmt->fetchColumn() === 0) {
                $fixStmt = $this->pdo->prepare("
                    UPDATE dc_dia_chi_kh 
                    SET mac_dinh = 1 
                    WHERE ma_nd = :ma_nd 
                    ORDER BY ma_dc DESC 
                    LIMIT 1
                ");
                $fixStmt->execute(['ma_nd' => $maNd]);
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Cập nhật địa chỉ nhận hàng thành công.',
                'ma_dc'   => $maDc,
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Lỗi khi cập nhật địa chỉ: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Xóa địa chỉ nhận hàng (Chống IDOR: ràng buộc ma_nd)
     * Nếu xóa trúng địa chỉ đang mặc định, tự động chuyển 1 địa chỉ còn lại thành mặc định
     */
    public function deleteAddress(int $maDc, int $maNd): array
    {
        if ($maDc <= 0 || $maNd <= 0) {
            return ['success' => false, 'message' => 'Dữ liệu yêu cầu không hợp lệ.'];
        }

        $existing = $this->getAddressById($maDc, $maNd);
        if (!$existing) {
            return ['success' => false, 'message' => 'Không tìm thấy địa chỉ hoặc bạn không có quyền xóa địa chỉ này.'];
        }

        $wasDefault = ((int)$existing['mac_dinh'] === 1);

        $this->pdo->beginTransaction();
        try {
            $delStmt = $this->pdo->prepare("DELETE FROM dc_dia_chi_kh WHERE ma_dc = :ma_dc AND ma_nd = :ma_nd");
            $delStmt->execute([
                'ma_dc' => $maDc,
                'ma_nd' => $maNd,
            ]);

            // Nếu địa chỉ vừa xóa là mặc định, chuyển 1 địa chỉ khác thành mặc định
            if ($wasDefault) {
                $promoteStmt = $this->pdo->prepare("
                    UPDATE dc_dia_chi_kh 
                    SET mac_dinh = 1 
                    WHERE ma_nd = :ma_nd 
                    ORDER BY ma_dc DESC 
                    LIMIT 1
                ");
                $promoteStmt->execute(['ma_nd' => $maNd]);
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Đã xóa địa chỉ nhận hàng thành công.',
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Lỗi khi xóa địa chỉ: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Đặt một địa chỉ làm mặc định (Sử dụng Database Transaction)
     * Chống IDOR: ràng buộc ma_nd
     */
    public function setDefaultAddress(int $maDc, int $maNd): array
    {
        if ($maDc <= 0 || $maNd <= 0) {
            return ['success' => false, 'message' => 'Dữ liệu yêu cầu không hợp lệ.'];
        }

        $existing = $this->getAddressById($maDc, $maNd);
        if (!$existing) {
            return ['success' => false, 'message' => 'Không tìm thấy địa chỉ hoặc bạn không có quyền thao tác trên địa chỉ này.'];
        }

        $this->pdo->beginTransaction();
        try {
            // Bước 1: Đưa tất cả địa chỉ của khách về 0
            $resetStmt = $this->pdo->prepare("UPDATE dc_dia_chi_kh SET mac_dinh = 0 WHERE ma_nd = :ma_nd");
            $resetStmt->execute(['ma_nd' => $maNd]);

            // Bước 2: Đặt địa chỉ được chọn thành 1
            $setStmt = $this->pdo->prepare("UPDATE dc_dia_chi_kh SET mac_dinh = 1 WHERE ma_dc = :ma_dc AND ma_nd = :ma_nd");
            $setStmt->execute([
                'ma_dc' => $maDc,
                'ma_nd' => $maNd,
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Đã đặt địa chỉ thành mặc định thành công.',
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Lỗi khi đặt địa chỉ mặc định: ' . $e->getMessage(),
            ];
        }
    }
}
