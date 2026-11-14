<?php
/**
 * DỊCH VỤ BÊN QUẢN TRỊ: Thống kê Tổng quan Dashboard, Biểu đồ Doanh thu & Phân bổ Hạng thành viên
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class DashboardService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy thông tin người quản trị hiện tại từ CSDL
     */
    public function getCurrentAdminProfile(int $maNd = 1): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT nd.*, vt.ten_vt, vt.mo_ta_quyen
            FROM nd_nguoi_dung nd
            INNER JOIN vt_vai_tro vt ON nd.ma_vt = vt.ma_vt
            WHERE nd.ma_nd = :ma_nd
            LIMIT 1
        ");
        $stmt->execute(['ma_nd' => $maNd]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Lấy 4 chỉ số KPI tổng quan từ CSDL thực tế
     */
    public function getKpiMetrics(): array
    {
        // Tổng doanh thu các đơn hoàn thành + đang giao
        $totalRevStmt = $this->pdo->query("
            SELECT COALESCE(SUM(thanh_tien), 0) AS tong_doanh_thu,
                   COUNT(*) AS tong_don_hang
            FROM dh_don_hang
            WHERE trang_thai_dh != 'DA_HUY'
        ");
        $revRow = $totalRevStmt->fetch();

        // Số đơn chờ xác nhận
        $pendingStmt = $this->pdo->query("
            SELECT COUNT(*) FROM dh_don_hang WHERE trang_thai_dh = 'CHO_XAC_NHAN'
        ");
        $pendingCount = (int) $pendingStmt->fetchColumn();

        // Tổng thành viên có thẻ Loyalty
        $membersStmt = $this->pdo->query("
            SELECT COUNT(*) FROM ttd_the_tich_diem
        ");
        $membersCount = (int) $membersStmt->fetchColumn();

        // Tổng điểm khả dụng đang lưu hành trên toàn hệ thống
        $pointsStmt = $this->pdo->query("
            SELECT COALESCE(SUM(diem_kha_dung), 0) AS tong_diem_kha_dung,
                   COALESCE(SUM(tong_diem_tich_luy), 0) AS tong_diem_tich_luy
            FROM ttd_the_tich_diem
        ");
        $pointsRow = $pointsStmt->fetch();

        return [
            'tong_doanh_thu'     => (float) $revRow['tong_doanh_thu'],
            'tong_don_hang'      => (int) $revRow['tong_don_hang'],
            'don_cho_duyet'      => $pendingCount,
            'tong_thanh_vien'    => $membersCount,
            'tong_diem_kha_dung' => (int) $pointsRow['tong_diem_kha_dung'],
            'tong_diem_tich_luy' => (int) $pointsRow['tong_diem_tich_luy'],
        ];
    }

    /**
     * Dữ liệu biểu đồ doanh thu và số đơn hàng theo tháng từ bảng dh_don_hang
     */
    public function getMonthlyRevenueChartData(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                DATE_FORMAT(ngay_dat, '%m/%Y') AS thang_nam,
                YEAR(ngay_dat) AS nam,
                MONTH(ngay_dat) AS thang,
                SUM(CASE WHEN trang_thai_dh != 'DA_HUY' THEN thanh_tien ELSE 0 END) AS doanh_thu,
                COUNT(*) AS so_don
            FROM dh_don_hang
            GROUP BY YEAR(ngay_dat), MONTH(ngay_dat), DATE_FORMAT(ngay_dat, '%m/%Y')
            ORDER BY nam ASC, thang ASC
        ");
        $rows = $stmt->fetchAll();

        $labels = [];
        $revenuesInMillions = [];
        $orderCounts = [];

        foreach ($rows as $r) {
            $labels[] = 'Tháng ' . $r['thang_nam'];
            $revenuesInMillions[] = round(((float) $r['doanh_thu']) / 1000000, 3);
            $orderCounts[] = (int) $r['so_don'];
        }

        return [
            'labels'   => $labels,
            'revenues' => $revenuesInMillions,
            'orders'   => $orderCounts,
        ];
    }

    /**
     * Dữ liệu biểu đồ tròn phân bổ thành viên theo 4 bậc Hạng thẻ từ CSDL
     */
    public function getTierDistributionChartData(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                htv.ma_hang,
                htv.ten_hang,
                htv.mau_sac_the,
                COUNT(ttd.ma_the) AS so_luong_khach
            FROM htv_hang_thanh_vien htv
            LEFT JOIN ttd_the_tich_diem ttd ON htv.ma_hang = ttd.ma_hang
            GROUP BY htv.ma_hang, htv.ten_hang, htv.mau_sac_the
            ORDER BY htv.diem_toi_thieu ASC
        ");
        $rows = $stmt->fetchAll();

        $labels = [];
        $counts = [];
        $colors = [];

        foreach ($rows as $r) {
            $labels[] = $r['ten_hang'];
            $counts[] = (int) $r['so_luong_khach'];
            $colors[] = $r['mau_sac_the'] ?: '#94A3B8';
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'colors' => $colors,
        ];
    }
}

