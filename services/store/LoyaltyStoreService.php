<?php
/**
 * DỊCH VỤ BÊN CỬA HÀNG: Khách hàng Thân thiết (Loyalty), Thẻ tích điểm, Hạng thành viên, Kho Voucher & Lịch sử điểm
 * Truy vấn 100% từ CSDL csdl_cua_hang_quan_ao
 */

require_once __DIR__ . '/../../config/db.php';

class LoyaltyStoreService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Lấy toàn bộ 4 bậc Hạng thành viên từ CSDL
     */
    public function getAllTiers(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM htv_hang_thanh_vien ORDER BY diem_toi_thieu ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy thông tin hồ sơ + Thẻ tích điểm + Tiến độ lên hạng tiếp theo của khách hàng hiện tại
     */
    public function getCustomerLoyaltyProfile(int $maNd): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                nd.ma_nd,
                nd.ho_ten,
                nd.ten_dang_nhap,
                nd.sdt,
                nd.email,
                nd.anh_dai_dien,
                ttd.ma_the,
                ttd.ma_so_the,
                ttd.tong_diem_tich_luy,
                ttd.diem_kha_dung,
                ttd.ngay_cap,
                htv.ma_hang,
                htv.ten_hang,
                htv.diem_toi_thieu,
                htv.phan_tram_giam,
                htv.he_so_tich_diem,
                htv.mo_ta_uu_dai,
                htv.mau_sac_the
            FROM nd_nguoi_dung nd
            LEFT JOIN ttd_the_tich_diem ttd ON nd.ma_nd = ttd.ma_nd
            LEFT JOIN htv_hang_thanh_vien htv ON ttd.ma_hang = htv.ma_hang
            WHERE nd.ma_nd = :ma_nd
            LIMIT 1
        ");
        $stmt->execute(['ma_nd' => $maNd]);
        $profile = $stmt->fetch();

        if (!$profile) {
            return null;
        }

        // Tìm hạng tiếp theo dựa trên tổng điểm tích lũy
        $tiers = $this->getAllTiers();
        $nextTier = null;
        foreach ($tiers as $t) {
            if ((int) $t['diem_toi_thieu'] > (int) $profile['tong_diem_tich_luy']) {
                $nextTier = $t;
                break;
            }
        }

        if ($nextTier) {
            $targetPoints = (int) $nextTier['diem_toi_thieu'];
            $currentPoints = (int) $profile['tong_diem_tich_luy'];
            $pointsNeeded = max(0, $targetPoints - $currentPoints);
            $progressPercent = $targetPoints > 0 ? min(100, round(($currentPoints / $targetPoints) * 100, 1)) : 100;
        } else {
            $targetPoints = (int) $profile['tong_diem_tich_luy'];
            $pointsNeeded = 0;
            $progressPercent = 100;
        }

        $profile['next_tier'] = $nextTier;
        $profile['points_needed'] = $pointsNeeded;
        $profile['progress_percent'] = $progressPercent;
        $profile['all_tiers'] = $tiers;

        return $profile;
    }

    /**
     * Lấy danh sách chiến dịch Phiếu giảm giá (Kho đổi điểm Voucher) kèm trạng thái đổi của khách
     * Ẩn hoàn toàn các voucher đã hết hạn hoặc kết thúc sớm (ngay_kt < NOW())
     */
    public function getVoucherCatalogForCustomer(int $maNd, int $customerTierId, int $availablePoints): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                pgg.*,
                htv.ten_hang AS ten_hang_yeu_cau,
                htv.diem_toi_thieu AS moc_diem_hang
            FROM pgg_phieu_giam_gia pgg
            INNER JOIN htv_hang_thanh_vien htv ON pgg.ma_hang_yeu_cau = htv.ma_hang
            WHERE pgg.ngay_kt > NOW()
            ORDER BY pgg.diem_can_doi ASC
        ");
        $vouchers = $stmt->fetchAll();

        // Kiểm tra những voucher khách đã có trong ví
        $walletStmt = $this->pdo->prepare("
            SELECT ma_pgg, trang_thai_su_dung, ngay_doi, ngay_su_dung
            FROM vkh_vi_voucher_kh
            WHERE ma_nd = :ma_nd
        ");
        $walletStmt->execute(['ma_nd' => $maNd]);
        $ownedMap = [];
        foreach ($walletStmt->fetchAll() as $w) {
            $ownedMap[$w['ma_pgg']] = $w;
        }

        // Lấy ngưỡng điểm tối thiểu của hạng khách để so sánh đúng spec
        $tiers = $this->getAllTiers();
        $customerMinPoints = 0;
        foreach ($tiers as $t) {
            if ((int)$t['ma_hang'] === $customerTierId) {
                $customerMinPoints = (int)$t['diem_toi_thieu'];
                break;
            }
        }

        foreach ($vouchers as &$v) {
            $vid = $v['ma_pgg'];
            $v['owned_info'] = $ownedMap[$vid] ?? null;
            $v['is_tier_eligible'] = $customerMinPoints >= (int) $v['moc_diem_hang'];
            $v['is_points_eligible'] = $availablePoints >= (int) $v['diem_can_doi'];
            $v['is_in_stock'] = (int) $v['so_luong'] > 0;
            $v['can_redeem'] = $v['is_tier_eligible'] && $v['is_points_eligible'] && $v['is_in_stock'] && empty($v['owned_info']);
        }
        unset($v);

        return $vouchers;
    }

    /**
     * Lấy lịch sử biến động điểm từ bảng lsd_lich_su_diem của thẻ khách hàng
     */
    public function getPointLedgerByCard(?int $maThe): array
    {
        if (!$maThe) {
            return [];
        }
        $stmt = $this->pdo->prepare("
            SELECT 
                lsd.*,
                dh.tong_tien_hang,
                dh.thanh_tien,
                dh.trang_thai_dh
            FROM lsd_lich_su_diem lsd
            LEFT JOIN dh_don_hang dh ON lsd.ma_dh = dh.ma_dh
            WHERE lsd.ma_the = :ma_the
            ORDER BY lsd.thoi_gian DESC, lsd.ma_ls_diem DESC
        ");
        $stmt->execute(['ma_the' => $maThe]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách đơn hàng của khách hàng kèm danh sách sản phẩm thuộc từng đơn (ctdh JOIN bt JOIN sp)
     */
    public function getCustomerOrders(int $maNd): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                dh.*,
                pgg.ma_code AS voucher_code,
                pgg.ten_pgg AS voucher_name,
                nd.ho_ten AS ten_khach_hang,
                nd.sdt AS sdt_khach_hang,
                nd.email AS email_khach_hang,
                COALESCE(dc.ten_nguoi_nhan, nd.ho_ten) AS ten_nguoi_nhan,
                COALESCE(dc.sdt_nhan, nd.sdt) AS sdt_nhan,
                dc.dia_chi_cu_the,
                dc.phuong_xa,
                dc.quan_huyen,
                dc.tinh_thanh,
                (SELECT COUNT(*) FROM ctdh_chi_tiet_dh ct WHERE ct.ma_dh = dh.ma_dh) AS so_mat_hang
            FROM dh_don_hang dh
            INNER JOIN nd_nguoi_dung nd ON dh.ma_nd = nd.ma_nd
            LEFT JOIN pgg_phieu_giam_gia pgg ON dh.ma_pgg = pgg.ma_pgg
            LEFT JOIN dc_dia_chi_kh dc ON dc.ma_dc = (
                SELECT ma_dc FROM dc_dia_chi_kh 
                WHERE ma_nd = dh.ma_nd 
                ORDER BY mac_dinh DESC, ma_dc ASC 
                LIMIT 1
            )
            WHERE dh.ma_nd = :ma_nd
            ORDER BY dh.ngay_dat DESC
        ");
        $stmt->execute(['ma_nd' => $maNd]);
        $orders = $stmt->fetchAll();

        if (empty($orders)) {
            return [];
        }

        $orderIds = array_column($orders, 'ma_dh');
        $inPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));

        $itemsStmt = $this->pdo->prepare("
            SELECT 
                ct.ma_ctdh,
                ct.ma_dh,
                ct.ma_bt,
                ct.so_luong,
                ct.don_gia_luc_mua,
                ct.thanh_tien_muc,
                ct.so_sao_danh_gia,
                ct.noi_dung_danh_gia,
                ct.da_cong_diem_dg,
                bt.ma_sku,
                bt.kich_co,
                bt.mau_sac,
                sp.ma_sp,
                sp.ten_sp,
                sp.anh_chinh
            FROM ctdh_chi_tiet_dh ct
            INNER JOIN bt_bien_the_sp bt ON ct.ma_bt = bt.ma_bt
            INNER JOIN sp_san_pham sp ON bt.ma_sp = sp.ma_sp
            WHERE ct.ma_dh IN ($inPlaceholders)
            ORDER BY ct.ma_ctdh ASC
        ");
        $itemsStmt->execute($orderIds);
        $items = $itemsStmt->fetchAll();

        $itemsByOrder = [];
        foreach ($items as $item) {
            $item['anh_chinh_url'] = resolveProductImage($item['anh_chinh']);
            $itemsByOrder[$item['ma_dh']][] = $item;
        }

        foreach ($orders as &$order) {
            $order['ma_dh_code'] = 'DH' . str_pad((string)$order['ma_dh'], 2, '0', STR_PAD_LEFT);
            $order['items'] = $itemsByOrder[$order['ma_dh']] ?? [];

            $addrParts = array_filter([
                $order['dia_chi_cu_the'] ?? '',
                $order['phuong_xa'] ?? '',
                $order['quan_huyen'] ?? '',
                $order['tinh_thanh'] ?? ''
            ]);
            $order['dia_chi_giao_hang'] = !empty($addrParts) 
                ? implode(', ', $addrParts) 
                : 'Địa chỉ theo thông tin đăng ký tài khoản';
            $order['phi_van_chuyen'] = 0; // Hệ thống áp dụng miễn phí giao hàng
            $order['tien_giam_voucher_diem'] = (float)$order['tien_giam_voucher'] + ((int)$order['diem_su_dung'] * 1000);
        }
        unset($order);

        return $orders;
    }

    /**
     * Alias getUserOrders theo đặc tả Mục 1.4
     */
    public function getUserOrders(int $maNd): array
    {
        return $this->getCustomerOrders($maNd);
    }

    /**
     * Khách hàng tự hủy đơn hàng đang ở trạng thái CHO_XAC_NHAN (Yêu cầu 2)
     * Thực thi trong 1 PDO Transaction (SELECT ... FOR UPDATE):
     * 1. Khóa dòng đơn hàng dh_don_hang theo ma_dh và ma_nd
     * 2. Chỉ cho phép hủy khi trang_thai_dh === 'CHO_XAC_NHAN'
     * 3. Cập nhật dh_don_hang.trang_thai_dh = 'DA_HUY'
     * 4. Hoàn trả tồn kho bt_bien_the_sp.so_luong_ton từ ctdh_chi_tiet_dh
     * 5. Hoàn trả voucher ví vkh_vi_voucher_kh (nếu có dùng)
     * 6. Hoàn trả điểm khả dụng ttd_the_tich_diem và ghi nhận sổ cái lsd_lich_su_diem (nếu có dùng điểm)
     *
     * @param int $maDh Mã đơn hàng cần hủy
     * @param int $maNd Mã khách hàng thực hiện hủy
     * @return array Kết quả xử lý
     * @throws Exception
     */
    public function cancelCustomerOrder(int $maDh, int $maNd): array
    {
        if ($maDh <= 0 || $maNd <= 0) {
            throw new Exception("Thông tin đơn hàng hoặc người dùng không hợp lệ.");
        }

        $this->pdo->beginTransaction();

        try {
            // 1. Khóa dòng đơn hàng của chính khách hàng này (FOR UPDATE)
            $stmt = $this->pdo->prepare("
                SELECT * FROM dh_don_hang 
                WHERE ma_dh = :ma_dh AND ma_nd = :ma_nd 
                FOR UPDATE
            ");
            $stmt->execute([
                'ma_dh' => $maDh,
                'ma_nd' => $maNd,
            ]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("Đơn hàng không tồn tại hoặc bạn không có quyền hủy đơn hàng này.");
            }

            $currentStatus = $order['trang_thai_dh'];
            $dhCode = 'DH' . str_pad((string)$maDh, 2, '0', STR_PAD_LEFT);

            // 2. Chỉ cho phép khách hàng tự hủy khi trang_thai_dh === 'CHO_XAC_NHAN'
            if ($currentStatus !== 'CHO_XAC_NHAN') {
                throw new Exception("Đơn hàng đã được xử lý hoặc đang giao, không thể tự hủy.");
            }

            // 3. Cập nhật dh_don_hang.trang_thai_dh = 'DA_HUY'
            $this->pdo->prepare("UPDATE dh_don_hang SET trang_thai_dh = 'DA_HUY' WHERE ma_dh = :ma_dh")
                      ->execute(['ma_dh' => $maDh]);

            // 4. Hoàn trả tồn kho: cộng trả lại so_luong vào bt_bien_the_sp.so_luong_ton
            $stmtItems = $this->pdo->prepare("
                SELECT ma_bt, so_luong FROM ctdh_chi_tiet_dh WHERE ma_dh = :ma_dh
            ");
            $stmtItems->execute(['ma_dh' => $maDh]);
            $items = $stmtItems->fetchAll();

            $stmtReturnStock = $this->pdo->prepare("
                UPDATE bt_bien_the_sp 
                SET so_luong_ton = so_luong_ton + :qty 
                WHERE ma_bt = :ma_bt
            ");
            foreach ($items as $item) {
                $stmtReturnStock->execute([
                    'qty'   => (int)$item['so_luong'],
                    'ma_bt' => (int)$item['ma_bt'],
                ]);
            }

            // 5. Hoàn trả Voucher (nếu có sử dụng voucher)
            if (!empty($order['ma_pgg'])) {
                $this->pdo->prepare("
                    UPDATE vkh_vi_voucher_kh
                    SET trang_thai_su_dung = 0, ngay_su_dung = NULL
                    WHERE ma_nd = :ma_nd AND ma_pgg = :ma_pgg AND trang_thai_su_dung = 1
                    ORDER BY ngay_su_dung DESC
                    LIMIT 1
                ")->execute([
                    'ma_nd'  => $maNd,
                    'ma_pgg' => (int)$order['ma_pgg'],
                ]);
            }

            // 6. Hoàn trả điểm tiêu dùng (nếu đơn có dùng điểm)
            $diemSuDung = (int)($order['diem_su_dung'] ?? 0);
            if ($diemSuDung > 0) {
                $stmtCard = $this->pdo->prepare("
                    SELECT ma_the, diem_kha_dung FROM ttd_the_tich_diem WHERE ma_nd = :ma_nd FOR UPDATE
                ");
                $stmtCard->execute(['ma_nd' => $maNd]);
                $card = $stmtCard->fetch();

                if ($card) {
                    $newBalance = (int)$card['diem_kha_dung'] + $diemSuDung;
                    $this->pdo->prepare("
                        UPDATE ttd_the_tich_diem 
                        SET diem_kha_dung = diem_kha_dung + :pts 
                        WHERE ma_the = :ma_the
                    ")->execute([
                        'pts'    => $diemSuDung,
                        'ma_the' => $card['ma_the'],
                    ]);

                    $this->pdo->prepare("
                        INSERT INTO lsd_lich_su_diem (
                            ma_the, ma_dh, loai_giao_dich, so_diem_thay_doi,
                            so_du_sau_gd, noi_dung, thoi_gian
                        ) VALUES (
                            :ma_the, :ma_dh, 'CONG_MUA_HANG', :pts,
                            :balance, :note, NOW()
                        )
                    ")->execute([
                        'ma_the'  => $card['ma_the'],
                        'ma_dh'   => $maDh,
                        'pts'     => $diemSuDung,
                        'balance' => $newBalance,
                        'note'    => "Hoàn lại {$diemSuDung} điểm khi hủy đơn #{$dhCode}",
                    ]);
                }
            }

            $this->pdo->commit();

            return [
                'success'       => true,
                'message'       => "Đã hủy đơn hàng #{$dhCode} thành công.",
                'ma_dh'         => $maDh,
                'trang_thai_dh' => 'DA_HUY',
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}

