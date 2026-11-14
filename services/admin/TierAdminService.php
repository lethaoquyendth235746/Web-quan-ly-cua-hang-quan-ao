<?php
/**
 * DỊCH VỤ BÊN QUẢN TRỊ: Cấu hình 4 Bậc Hạng Thành Viên & Danh sách Thẻ Tích Điểm
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class TierAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy cấu hình 4 hạng thành viên kèm số lượng khách hàng đang ở mỗi hạng
     */
    public function getAllTiersWithMemberCount(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                htv.*,
                COUNT(ttd.ma_the) AS so_thanh_vien
            FROM htv_hang_thanh_vien htv
            LEFT JOIN ttd_the_tich_diem ttd ON htv.ma_hang = ttd.ma_hang
            GROUP BY htv.ma_hang
            ORDER BY htv.diem_toi_thieu ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy thông tin chi tiết 1 hạng theo mã ID
     */
    public function getTierById(int $maHang): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM htv_hang_thanh_vien WHERE ma_hang = ?");
        $stmt->execute([$maHang]);
        $tier = $stmt->fetch();
        return $tier ?: null;
    }

    /**
     * Lấy danh sách thẻ tích điểm của toàn bộ khách hàng thành viên từ CSDL
     */
    public function getAllMemberCards(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                ttd.*,
                nd.ho_ten,
                nd.ten_dang_nhap,
                nd.sdt,
                nd.email,
                htv.ten_hang,
                htv.phan_tram_giam,
                htv.he_so_tich_diem,
                htv.mau_sac_the
            FROM ttd_the_tich_diem ttd
            INNER JOIN nd_nguoi_dung nd ON ttd.ma_nd = nd.ma_nd
            INNER JOIN htv_hang_thanh_vien htv ON ttd.ma_hang = htv.ma_hang
            ORDER BY ttd.tong_diem_tich_luy DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Cập nhật cấu hình hạng thành viên và tự động đồng bộ lại hạng thẻ cho toàn bộ khách hàng
     * Thực hiện trong PDO Transaction an toàn
     */
    public function updateTierConfig(int $maHang, array $data): array
    {
        // 1. Lấy danh sách toàn bộ các bậc hạng hiện tại sắp xếp theo ma_hang ASC
        $stmt = $this->pdo->query("SELECT * FROM htv_hang_thanh_vien ORDER BY ma_hang ASC");
        $allTiers = $stmt->fetchAll();

        $targetIndex = null;
        foreach ($allTiers as $idx => $t) {
            if ((int)$t['ma_hang'] === $maHang) {
                $targetIndex = $idx;
                break;
            }
        }

        if ($targetIndex === null) {
            throw new Exception("Không tìm thấy hạng thành viên #{$maHang} cần chỉnh sửa trong CSDL!");
        }

        $currentTier = $allTiers[$targetIndex];
        $prevTier = $allTiers[$targetIndex - 1] ?? null;
        $nextTier = $allTiers[$targetIndex + 1] ?? null;

        // 2. Validate dữ liệu đầu vào chặt chẽ
        $tenHang = trim($data['ten_hang'] ?? '');
        if ($tenHang === '') {
            throw new InvalidArgumentException('Tên hạng thành viên không được để trống.');
        }
        $tenHang = mb_substr($tenHang, 0, 50, 'UTF-8');

        if (!isset($data['diem_toi_thieu']) || $data['diem_toi_thieu'] === '') {
            throw new InvalidArgumentException('Vui lòng nhập ngưỡng điểm tối thiểu của hạng.');
        }
        $diemToiThieu = (int)$data['diem_toi_thieu'];
        if ($diemToiThieu < 0) {
            throw new InvalidArgumentException('Ngưỡng điểm tối thiểu không được là số âm.');
        }

        // Ràng buộc bậc khởi điểm đầu tiên
        if ($prevTier === null) {
            if ($diemToiThieu !== 0) {
                throw new Exception("Hạng khởi điểm đầu tiên bắt buộc có ngưỡng điểm tối thiểu bằng 0!");
            }
        } else {
            // Ràng buộc tăng dần giữa các bậc
            $prevPoints = (int)$prevTier['diem_toi_thieu'];
            if ($diemToiThieu <= $prevPoints) {
                throw new Exception("Ngưỡng điểm của hạng này ({$diemToiThieu}) phải lớn hơn hạng liền trước ({$prevTier['ten_hang']}: {$prevPoints} điểm)!");
            }
        }

        if ($nextTier !== null) {
            $nextPoints = (int)$nextTier['diem_toi_thieu'];
            if ($diemToiThieu >= $nextPoints) {
                throw new Exception("Ngưỡng điểm của hạng này ({$diemToiThieu}) phải nhỏ hơn hạng liền sau ({$nextTier['ten_hang']}: {$nextPoints} điểm)!");
            }
        }

        // Chiết khấu trực tiếp (0% -> 100%)
        if (!isset($data['phan_tram_giam']) || $data['phan_tram_giam'] === '') {
            throw new InvalidArgumentException('Vui lòng nhập phần trăm chiết khấu trực tiếp.');
        }
        $phanTramGiam = round((float)$data['phan_tram_giam'], 2);
        if ($phanTramGiam < 0 || $phanTramGiam > 100) {
            throw new InvalidArgumentException('Phần trăm giảm giá phải nằm trong khoảng từ 0% đến 100%.');
        }

        // Hệ số tích điểm (1.00 -> 10.00)
        if (!isset($data['he_so_tich_diem']) || $data['he_so_tich_diem'] === '') {
            throw new InvalidArgumentException('Vui lòng nhập hệ số tích điểm.');
        }
        $heSoTichDiem = round((float)$data['he_so_tich_diem'], 2);
        if ($heSoTichDiem < 1.00 || $heSoTichDiem > 10.00) {
            throw new InvalidArgumentException('Hệ số tích điểm phải nằm trong khoảng từ 1.00 đến 10.00.');
        }

        // Mô tả ưu đãi
        $moTaUuDai = isset($data['mo_ta_uu_dai']) ? trim((string)$data['mo_ta_uu_dai']) : '';
        if ($moTaUuDai !== '') {
            $moTaUuDai = mb_substr($moTaUuDai, 0, 255, 'UTF-8');
        } else {
            $moTaUuDai = null;
        }

        // Màu sắc thẻ (HEX #RRGGBB)
        $mauSacThe = isset($data['mau_sac_the']) ? trim((string)$data['mau_sac_the']) : '';
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $mauSacThe)) {
            $mauSacThe = $currentTier['mau_sac_the'];
        }

        // 3. Thực hiện cập nhật & đồng bộ trong 1 PDO Transaction
        $this->pdo->beginTransaction();
        try {
            // Bước 1: Cập nhật bảng htv_hang_thanh_vien
            $updateStmt = $this->pdo->prepare("
                UPDATE htv_hang_thanh_vien
                SET ten_hang = :ten_hang,
                    diem_toi_thieu = :diem_toi_thieu,
                    phan_tram_giam = :phan_tram_giam,
                    he_so_tich_diem = :he_so_tich_diem,
                    mo_ta_uu_dai = :mo_ta_uu_dai,
                    mau_sac_the = :mau_sac_the
                WHERE ma_hang = :ma_hang
            ");
            $updateStmt->execute([
                ':ten_hang'        => $tenHang,
                ':diem_toi_thieu'  => $diemToiThieu,
                ':phan_tram_giam'  => $phanTramGiam,
                ':he_so_tich_diem' => $heSoTichDiem,
                ':mo_ta_uu_dai'    => $moTaUuDai,
                ':mau_sac_the'     => $mauSacThe,
                ':ma_hang'         => $maHang,
            ]);

            // Bước 2: Đồng bộ lại hạng thẻ cho toàn bộ khách hàng trong ttd_the_tich_diem
            $syncSql = "
                UPDATE ttd_the_tich_diem ttd
                SET ttd.ma_hang = (
                    SELECT htv.ma_hang
                    FROM htv_hang_thanh_vien htv
                    WHERE htv.diem_toi_thieu <= ttd.tong_diem_tich_luy
                    ORDER BY htv.diem_toi_thieu DESC
                    LIMIT 1
                )
            ";
            $this->pdo->exec($syncSql);

            $this->pdo->commit();

            return [
                'ma_hang'         => $maHang,
                'ten_hang'        => $tenHang,
                'diem_toi_thieu'  => $diemToiThieu,
                'phan_tram_giam'  => $phanTramGiam,
                'he_so_tich_diem' => $heSoTichDiem,
                'mo_ta_uu_dai'    => $moTaUuDai,
                'mau_sac_the'     => $mauSacThe,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
