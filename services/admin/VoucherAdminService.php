<?php
/**
 * DỊCH VỤ BÊN QUẢN TRỊ: Quản lý Chiến dịch Phiếu giảm giá (Voucher Loyalty) & Ví Voucher
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class VoucherAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy danh sách tất cả chiến dịch Voucher từ bảng pgg_phieu_giam_gia kèm số lượt đã đổi
     */
    public function getAllVouchers(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                pgg.*,
                htv.ten_hang AS ten_hang_yeu_cau,
                htv.mau_sac_the,
                (SELECT COUNT(*) FROM vkh_vi_voucher_kh vkh WHERE vkh.ma_pgg = pgg.ma_pgg) AS so_luot_da_doi,
                (SELECT COUNT(*) FROM vkh_vi_voucher_kh vkh WHERE vkh.ma_pgg = pgg.ma_pgg AND vkh.trang_thai_su_dung = 1) AS so_luot_da_dung
            FROM pgg_phieu_giam_gia pgg
            INNER JOIN htv_hang_thanh_vien htv ON pgg.ma_hang_yeu_cau = htv.ma_hang
            ORDER BY pgg.ma_pgg ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy chi tiết một Voucher theo mã ID
     */
    public function getVoucherById(int $maPgg): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                pgg.*,
                htv.ten_hang AS ten_hang_yeu_cau,
                htv.mau_sac_the,
                (SELECT COUNT(*) FROM vkh_vi_voucher_kh vkh WHERE vkh.ma_pgg = pgg.ma_pgg) AS so_luot_da_doi,
                (SELECT COUNT(*) FROM vkh_vi_voucher_kh vkh WHERE vkh.ma_pgg = pgg.ma_pgg AND vkh.trang_thai_su_dung = 1) AS so_luot_da_dung
            FROM pgg_phieu_giam_gia pgg
            INNER JOIN htv_hang_thanh_vien htv ON pgg.ma_hang_yeu_cau = htv.ma_hang
            WHERE pgg.ma_pgg = ?
        ");
        $stmt->execute([$maPgg]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Lấy lịch sử đổi voucher của khách hàng từ bảng vkh_vi_voucher_kh
     */
    public function getCustomerVoucherWalletLogs(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                vkh.*,
                nd.ho_ten,
                nd.sdt,
                pgg.ma_code,
                pgg.ten_pgg,
                pgg.diem_can_doi
            FROM vkh_vi_voucher_kh vkh
            INNER JOIN nd_nguoi_dung nd ON vkh.ma_nd = nd.ma_nd
            INNER JOIN pgg_phieu_giam_gia pgg ON vkh.ma_pgg = pgg.ma_pgg
            ORDER BY vkh.ngay_doi DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách toàn bộ các bậc Hạng thành viên để đổ vào dropdown
     */
    public function getAllTiers(): array
    {
        $stmt = $this->pdo->query("
            SELECT ma_hang, ten_hang, diem_toi_thieu, mau_sac_the 
            FROM htv_hang_thanh_vien 
            ORDER BY diem_toi_thieu ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Tạo mới một chiến dịch Voucher vào bảng pgg_phieu_giam_gia
     */
    public function createVoucher(array $data): int
    {
        // 1. Chuẩn hóa & Validate mã code
        $maCode = strtoupper(trim($data['ma_code'] ?? ''));
        if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $maCode)) {
            throw new InvalidArgumentException("Mã Voucher '{$maCode}' không hợp lệ. Chỉ chấp nhận từ 3 đến 30 ký tự in hoa, chữ số, gạch nối (-) hoặc gạch dưới (_).");
        }

        // Kiểm tra tính duy nhất (UNIQUE) của mã code
        $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM pgg_phieu_giam_gia WHERE ma_code = ?");
        $stmtCheck->execute([$maCode]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            throw new Exception("Mã Voucher '{$maCode}' đã tồn tại trong hệ thống!");
        }

        // 2. Validate tên chiến dịch
        $tenPgg = trim($data['ten_pgg'] ?? '');
        if ($tenPgg === '') {
            throw new InvalidArgumentException('Tên chiến dịch Voucher không được để trống.');
        }
        $tenPgg = mb_substr($tenPgg, 0, 150, 'UTF-8');

        // 3. Validate loại giảm giá & giá trị giảm
        $loaiGiam = trim($data['loai_giam'] ?? 'TIEN');
        if (!in_array($loaiGiam, ['TIEN', 'PHAN_TRAM'], true)) {
            throw new InvalidArgumentException('Loại giảm giá không hợp lệ. Chỉ chấp nhận TIEN hoặc PHAN_TRAM.');
        }

        if (!isset($data['gia_tri_giam']) || $data['gia_tri_giam'] === '') {
            throw new InvalidArgumentException('Vui lòng nhập giá trị giảm giá của Voucher.');
        }
        $giaTriGiam = (float)$data['gia_tri_giam'];
        if ($giaTriGiam <= 0) {
            throw new InvalidArgumentException('Giá trị giảm giá phải lớn hơn 0.');
        }
        if ($loaiGiam === 'PHAN_TRAM' && ($giaTriGiam < 1 || $giaTriGiam > 100)) {
            throw new InvalidArgumentException('Phần trăm giảm giá phải nằm trong khoảng từ 1% đến 100%.');
        }
        if ($loaiGiam === 'TIEN' && $giaTriGiam < 1000) {
            throw new InvalidArgumentException('Số tiền giảm giá cố định tối thiểu là 1.000đ.');
        }

        // 4. Validate đơn tối thiểu & điểm cần đổi
        $donToiThieu = max(0, (float)($data['don_toi_thieu'] ?? 0));
        $diemCanDoi = max(0, (int)($data['diem_can_doi'] ?? 0));

        // 5. Validate hạng yêu cầu
        $maHangYeuCau = (int)($data['ma_hang_yeu_cau'] ?? 1);
        $stmtTier = $this->pdo->prepare("SELECT ma_hang FROM htv_hang_thanh_vien WHERE ma_hang = ?");
        $stmtTier->execute([$maHangYeuCau]);
        if (!$stmtTier->fetch()) {
            throw new InvalidArgumentException('Hạng thành viên yêu cầu không hợp lệ.');
        }

        // 6. Validate số lượng phát hành
        $soLuong = (int)($data['so_luong'] ?? 100);
        if ($soLuong < 1) {
            throw new InvalidArgumentException('Số lượng phát hành ban đầu phải tối thiểu 1 lượt.');
        }

        // 7. Validate thời gian chiến dịch
        $timeBd = strtotime($data['ngay_bd'] ?? '');
        $timeKt = strtotime($data['ngay_kt'] ?? '');
        if (!$timeBd || !$timeKt) {
            throw new InvalidArgumentException('Thời gian bắt đầu và kết thúc không đúng định dạng ngày giờ.');
        }
        if ($timeKt <= $timeBd) {
            throw new Exception("Thời gian kết thúc phải sau thời gian bắt đầu!");
        }

        $ngayBd = date('Y-m-d H:i:s', $timeBd);
        $ngayKt = date('Y-m-d H:i:s', $timeKt);

        // 8. Thực hiện INSERT vào bảng pgg_phieu_giam_gia
        $stmt = $this->pdo->prepare("
            INSERT INTO pgg_phieu_giam_gia (
                ma_code, ten_pgg, loai_giam, gia_tri_giam, don_toi_thieu,
                diem_can_doi, ma_hang_yeu_cau, so_luong, ngay_bd, ngay_kt
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $maCode, $tenPgg, $loaiGiam, $giaTriGiam, $donToiThieu,
            $diemCanDoi, $maHangYeuCau, $soLuong, $ngayBd, $ngayKt
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Cập nhật thông số chiến dịch Voucher (gia hạn thời gian, bổ sung số lượng phát hành,...)
     */
    public function updateVoucherCampaign(int $maPgg, array $data): bool
    {
        $existing = $this->getVoucherById($maPgg);
        if (!$existing) {
            throw new Exception("Không tìm thấy chiến dịch Voucher #{$maPgg}!");
        }

        // 1. Validate tên chiến dịch
        $tenPgg = trim($data['ten_pgg'] ?? '');
        if ($tenPgg === '') {
            throw new InvalidArgumentException('Tên chiến dịch Voucher không được để trống.');
        }
        $tenPgg = mb_substr($tenPgg, 0, 150, 'UTF-8');

        // 2. Validate loại giảm giá & giá trị giảm
        $loaiGiam = trim($data['loai_giam'] ?? $existing['loai_giam']);
        if (!in_array($loaiGiam, ['TIEN', 'PHAN_TRAM'], true)) {
            throw new InvalidArgumentException('Loại giảm giá không hợp lệ. Chỉ chấp nhận TIEN hoặc PHAN_TRAM.');
        }

        $giaTriGiam = isset($data['gia_tri_giam']) ? (float)$data['gia_tri_giam'] : (float)$existing['gia_tri_giam'];
        if ($giaTriGiam <= 0) {
            throw new InvalidArgumentException('Giá trị giảm giá phải lớn hơn 0.');
        }
        if ($loaiGiam === 'PHAN_TRAM' && ($giaTriGiam < 1 || $giaTriGiam > 100)) {
            throw new InvalidArgumentException('Phần trăm giảm giá phải nằm trong khoảng từ 1% đến 100%.');
        }
        if ($loaiGiam === 'TIEN' && $giaTriGiam < 1000) {
            throw new InvalidArgumentException('Số tiền giảm giá cố định tối thiểu là 1.000đ.');
        }

        // 3. Validate đơn tối thiểu & điểm cần đổi
        $donToiThieu = max(0, (float)($data['don_toi_thieu'] ?? $existing['don_toi_thieu']));
        $diemCanDoi = max(0, (int)($data['diem_can_doi'] ?? $existing['diem_can_doi']));

        // 4. Validate hạng yêu cầu
        $maHangYeuCau = (int)($data['ma_hang_yeu_cau'] ?? $existing['ma_hang_yeu_cau']);
        $stmtTier = $this->pdo->prepare("SELECT ma_hang FROM htv_hang_thanh_vien WHERE ma_hang = ?");
        $stmtTier->execute([$maHangYeuCau]);
        if (!$stmtTier->fetch()) {
            throw new InvalidArgumentException('Hạng thành viên yêu cầu không hợp lệ.');
        }

        // 5. Validate số lượng phát hành còn lại (cho phép đặt về 0 nếu muốn đóng chiến dịch)
        $soLuong = max(0, (int)($data['so_luong'] ?? $existing['so_luong']));

        // 6. Validate thời gian chiến dịch
        $timeBd = strtotime($data['ngay_bd'] ?? $existing['ngay_bd']);
        $timeKt = strtotime($data['ngay_kt'] ?? $existing['ngay_kt']);
        if (!$timeBd || !$timeKt) {
            throw new InvalidArgumentException('Thời gian bắt đầu và kết thúc không đúng định dạng ngày giờ.');
        }
        if ($timeKt <= $timeBd) {
            throw new Exception("Thời gian kết thúc phải sau thời gian bắt đầu!");
        }

        $ngayBd = date('Y-m-d H:i:s', $timeBd);
        $ngayKt = date('Y-m-d H:i:s', $timeKt);

        // 7. Thực hiện UPDATE vào bảng pgg_phieu_giam_gia
        $stmt = $this->pdo->prepare("
            UPDATE pgg_phieu_giam_gia
            SET ten_pgg = :ten_pgg,
                loai_giam = :loai_giam,
                gia_tri_giam = :gia_tri_giam,
                don_toi_thieu = :don_toi_thieu,
                diem_can_doi = :diem_can_doi,
                ma_hang_yeu_cau = :ma_hang_yeu_cau,
                so_luong = :so_luong,
                ngay_bd = :ngay_bd,
                ngay_kt = :ngay_kt
            WHERE ma_pgg = :ma_pgg
        ");

        return $stmt->execute([
            ':ten_pgg'         => $tenPgg,
            ':loai_giam'       => $loaiGiam,
            ':gia_tri_giam'    => $giaTriGiam,
            ':don_toi_thieu'   => $donToiThieu,
            ':diem_can_doi'    => $diemCanDoi,
            ':ma_hang_yeu_cau' => $maHangYeuCau,
            ':so_luong'        => $soLuong,
            ':ngay_bd'         => $ngayBd,
            ':ngay_kt'         => $ngayKt,
            ':ma_pgg'          => $maPgg,
        ]);
    }

    /**
     * Kết thúc sớm chiến dịch Voucher ngay lập tức: đóng số lượng còn lại về 0 và thời gian kết thúc về NOW()
     */
    public function endVoucherEarly(int $maPgg): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE pgg_phieu_giam_gia
            SET ngay_kt = NOW(),
                so_luong = 0
            WHERE ma_pgg = :ma_pgg
        ");
        return $stmt->execute([':ma_pgg' => $maPgg]);
    }
}
