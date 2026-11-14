<?php
/**
 * DỊCH VỤ XÁC THỰC & TÀI KHOẢN (AuthService)
 * Quản lý Đăng nhập, Đăng xuất, Đăng ký thành viên mới & Tự động mở thẻ Hạng Đồng
 * Tương tác với các bảng: vt_vai_tro, nd_nguoi_dung, ttd_the_tich_diem
 */

require_once __DIR__ . '/../../config/db.php';

class AuthService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    /**
     * Xác thực đăng nhập bằng Tên đăng nhập HOẶC Email + Mật khẩu
     *
     * @param string $identifier Tên đăng nhập hoặc Email
     * @param string $password   Mật khẩu
     * @return array Kết quả: ['success' => bool, 'message' => string, 'user' => ?array]
     */
    public function login(string $identifier, string $password): array
    {
        $identifier = trim($identifier);
        $password = trim($password);

        if ($identifier === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Vui lòng nhập đầy đủ tên đăng nhập/email và mật khẩu.',
                'user'    => null,
            ];
        }

        // 1. Truy vấn người dùng theo ten_dang_nhap HOẶC email (Prepared Statement)
        $stmt = $this->pdo->prepare("
            SELECT 
                nd.ma_nd,
                nd.ma_vt,
                nd.ho_ten,
                nd.ten_dang_nhap,
                nd.mat_khau,
                nd.sdt,
                nd.email,
                nd.anh_dai_dien,
                nd.trang_thai,
                nd.ngay_tao,
                vt.ten_vt,
                vt.mo_ta_quyen
            FROM nd_nguoi_dung nd
            INNER JOIN vt_vai_tro vt ON nd.ma_vt = vt.ma_vt
            WHERE (nd.ten_dang_nhap = :id1 OR nd.email = :id2)
            LIMIT 1
        ");
        $stmt->execute(['id1' => $identifier, 'id2' => $identifier]);
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Tài khoản không tồn tại trong hệ thống.',
                'user'    => null,
            ];
        }

        // 2. Kiểm tra trạng thái hoạt động của tài khoản
        if ((int)$user['trang_thai'] !== 1) {
            return [
                'success' => false,
                'message' => 'Tài khoản của bạn đã bị khóa hoặc chưa được kích hoạt. Vui lòng liên hệ quản trị viên.',
                'user'    => null,
            ];
        }

        // 3. Xác thực mật khẩu:
        // Hỗ trợ cả password_verify (mật khẩu đã hash chuẩn) và plain-text (cho dữ liệu mẫu 123456 trong CSDL)
        $isPasswordValid = false;

        if (password_verify($password, $user['mat_khau'])) {
            $isPasswordValid = true;
        } elseif ($user['mat_khau'] === $password) {
            $isPasswordValid = true;
            // Tự động nâng cấp hash mật khẩu trong DB để tăng cường bảo mật
            try {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upgradeStmt = $this->pdo->prepare("UPDATE nd_nguoi_dung SET mat_khau = :hash WHERE ma_nd = :id");
                $upgradeStmt->execute(['hash' => $newHash, 'id' => $user['ma_nd']]);
            } catch (Exception $e) {
                // Không chặn luồng đăng nhập nếu update hash lỗi nhỏ
            }
        }

        if (!$isPasswordValid) {
            return [
                'success' => false,
                'message' => 'Mật khẩu không chính xác. Vui lòng kiểm tra lại.',
                'user'    => null,
            ];
        }

        // 4. Thiết lập Session người dùng an toàn
        $this->setUserSession($user);

        return [
            'success' => true,
            'message' => 'Đăng nhập thành công! Chào mừng ' . $user['ho_ten'] . '.',
            'user'    => $user,
        ];
    }

    /**
     * Đăng ký thành viên mới & Tự động mở thẻ Hạng Đồng
     * Sử dụng Database Transaction (beginTransaction -> commit / rollBack)
     *
     * @param array $data ['ho_ten', 'ten_dang_nhap', 'email', 'sdt', 'mat_khau', 'mat_khau_xac_nhan']
     * @return array Kết quả: ['success' => bool, 'message' => string, 'ma_nd' => ?int, 'ma_so_the' => ?string]
     */
    public function register(array $data): array
    {
        $hoTen           = trim($data['ho_ten'] ?? '');
        $tenDangNhap     = trim($data['ten_dang_nhap'] ?? '');
        $email           = trim($data['email'] ?? '');
        $sdt             = trim($data['sdt'] ?? '');
        $matKhau         = $data['mat_khau'] ?? '';
        $matKhauXacNhan  = $data['mat_khau_xac_nhan'] ?? '';

        // 1. Validate bắt buộc các trường
        if ($hoTen === '' || $tenDangNhap === '' || $email === '' || $sdt === '' || $matKhau === '') {
            return [
                'success' => false,
                'message' => 'Vui lòng điền đầy đủ tất cả các trường thông tin bắt buộc.',
            ];
        }

        // 2. Validate định dạng tên đăng nhập (3-30 ký tự chữ, số, dấu gạch dưới)
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $tenDangNhap)) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập từ 3 - 30 ký tự, chỉ gồm chữ cái, số và dấu gạch dưới.',
            ];
        }

        // 3. Validate định dạng email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Địa chỉ email không hợp lệ. Vui lòng kiểm tra lại.',
            ];
        }

        // 4. Validate định dạng số điện thoại (9 - 15 chữ số)
        if (!preg_match('/^[0-9]{9,15}$/', $sdt)) {
            return [
                'success' => false,
                'message' => 'Số điện thoại không hợp lệ (từ 9 đến 15 chữ số).',
            ];
        }

        // 5. Validate độ dài mật khẩu (tối thiểu 6 ký tự)
        if (strlen($matKhau) < 6) {
            return [
                'success' => false,
                'message' => 'Mật khẩu phải có độ dài tối thiểu từ 6 ký tự.',
            ];
        }

        // 6. Validate xác nhận mật khẩu khớp
        if ($matKhau !== $matKhauXacNhan) {
            return [
                'success' => false,
                'message' => 'Mật khẩu xác nhận không trùng khớp.',
            ];
        }

        // 7. Kiểm tra trùng lặp (ten_dang_nhap, email, sdt) trong bảng nd_nguoi_dung
        $checkStmt = $this->pdo->prepare("
            SELECT 
                SUM(CASE WHEN ten_dang_nhap = :tdn1 THEN 1 ELSE 0 END) AS exist_tdn,
                SUM(CASE WHEN email = :em1 THEN 1 ELSE 0 END) AS exist_email,
                SUM(CASE WHEN sdt = :sdt1 THEN 1 ELSE 0 END) AS exist_sdt
            FROM nd_nguoi_dung
            WHERE ten_dang_nhap = :tdn2 OR email = :em2 OR sdt = :sdt2
        ");
        $checkStmt->execute([
            'tdn1' => $tenDangNhap,
            'tdn2' => $tenDangNhap,
            'em1'  => $email,
            'em2'  => $email,
            'sdt1' => $sdt,
            'sdt2' => $sdt,
        ]);
        $checkRow = $checkStmt->fetch();

        if (!empty($checkRow['exist_tdn'])) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập "' . htmlspecialchars($tenDangNhap) . '" đã được sử dụng. Vui lòng chọn tên khác.',
            ];
        }
        if (!empty($checkRow['exist_email'])) {
            return [
                'success' => false,
                'message' => 'Địa chỉ email "' . htmlspecialchars($email) . '" đã được đăng ký trong hệ thống.',
            ];
        }
        if (!empty($checkRow['exist_sdt'])) {
            return [
                'success' => false,
                'message' => 'Số điện thoại "' . htmlspecialchars($sdt) . '" đã gắn với một tài khoản khác.',
            ];
        }

        // 8. BẮT ĐẦU DATABASE TRANSACTION
        $this->pdo->beginTransaction();
        try {
            // Bước 8.1: Mã hóa mật khẩu an toàn
            $hashedPassword = password_hash($matKhau, PASSWORD_DEFAULT);

            // Bước 8.2: Tạo bản ghi trong bảng nd_nguoi_dung với vai trò Khách hàng (ma_vt = 3)
            $insertUserStmt = $this->pdo->prepare("
                INSERT INTO nd_nguoi_dung (
                    ma_vt,
                    ho_ten,
                    ten_dang_nhap,
                    mat_khau,
                    sdt,
                    email,
                    anh_dai_dien,
                    trang_thai,
                    ngay_tao
                ) VALUES (
                    3,
                    :ho_ten,
                    :ten_dang_nhap,
                    :mat_khau,
                    :sdt,
                    :email,
                    'default_avatar.png',
                    1,
                    NOW()
                )
            ");
            $insertUserStmt->execute([
                'ho_ten'        => $hoTen,
                'ten_dang_nhap' => $tenDangNhap,
                'mat_khau'      => $hashedPassword,
                'sdt'           => $sdt,
                'email'         => $email,
            ]);

            $newUserId = (int)$this->pdo->lastInsertId();
            if ($newUserId <= 0) {
                throw new Exception('Không thể tạo bản ghi người dùng mới.');
            }

            // Bước 8.3: Tự động sinh mã thẻ tích điểm THE-AGU-... duy nhất
            $maSoThe = $this->generateUniqueCardNumber();

            // Bước 8.4: Tạo bản ghi trong bảng ttd_the_tich_diem (Hạng Đồng: ma_hang = 1, điểm = 0)
            $insertCardStmt = $this->pdo->prepare("
                INSERT INTO ttd_the_tich_diem (
                    ma_nd,
                    ma_hang,
                    ma_so_the,
                    tong_diem_tich_luy,
                    diem_kha_dung,
                    ngay_cap
                ) VALUES (
                    :ma_nd,
                    1,
                    :ma_so_the,
                    0,
                    0,
                    NOW()
                )
            ");
            $insertCardStmt->execute([
                'ma_nd'     => $newUserId,
                'ma_so_the' => $maSoThe,
            ]);

            // Bước 8.5: Commit Transaction
            $this->pdo->commit();

            // Lấy thông tin đầy đủ để thiết lập session đăng nhập ngay cho khách
            $fullUserStmt = $this->pdo->prepare("
                SELECT nd.*, vt.ten_vt, vt.mo_ta_quyen
                FROM nd_nguoi_dung nd
                INNER JOIN vt_vai_tro vt ON nd.ma_vt = vt.ma_vt
                WHERE nd.ma_nd = :id
            ");
            $fullUserStmt->execute(['id' => $newUserId]);
            $newUser = $fullUserStmt->fetch();
            if ($newUser) {
                $this->setUserSession($newUser);
            }

            return [
                'success'   => true,
                'message'   => 'Đăng ký thành công! Bạn đã được tự động mở Thẻ thành viên Hạng Đồng (Mã thẻ: ' . $maSoThe . ').',
                'ma_nd'     => $newUserId,
                'ma_so_the' => $maSoThe,
                'user'      => $newUser,
            ];
        } catch (Exception $e) {
            // Bước 8.6: Rollback toàn bộ nếu có bất kỳ lỗi nào xảy ra
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Lỗi khi tạo tài khoản và mở thẻ: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Tự động sinh mã số thẻ tích điểm không trùng lặp: THE-AGU-{YY}{XXX}
     */
    private function generateUniqueCardNumber(): string
    {
        $yearSuffix = date('y'); // e.g. 26

        // Tìm mã thẻ lớn nhất hiện tại
        $stmt = $this->pdo->query("SELECT ma_the FROM ttd_the_tich_diem ORDER BY ma_the DESC LIMIT 1");
        $lastTheId = (int)$stmt->fetchColumn();
        $nextSequence = $lastTheId + 1;

        $candidate = sprintf('THE-AGU-%s%03d', $yearSuffix, $nextSequence);

        // Kiểm tra xem candidate đã có ai dùng chưa
        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM ttd_the_tich_diem WHERE ma_so_the = :card");
        $checkStmt->execute(['card' => $candidate]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            $candidate = sprintf('THE-AGU-%s%05d', $yearSuffix, mt_rand(1000, 99999));
        }

        return $candidate;
    }

    /**
     * Thiết lập các biến Session chuẩn hóa
     */
    public function setUserSession(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION['explicit_logout']);

        $_SESSION['user'] = [
            'ma_nd'         => (int)$user['ma_nd'],
            'ma_vt'         => (int)$user['ma_vt'],
            'ten_vt'        => $user['ten_vt'] ?? 'Thành viên',
            'ho_ten'        => $user['ho_ten'],
            'ten_dang_nhap' => $user['ten_dang_nhap'],
            'email'         => $user['email'],
            'sdt'           => $user['sdt'],
            'anh_dai_dien'  => $user['anh_dai_dien'] ?? 'default_avatar.png',
        ];

        $_SESSION['current_user_id'] = (int)$user['ma_nd'];

        // Nếu là Admin (1) hoặc Nhân viên (2), cập nhật session admin
        if (in_array((int)$user['ma_vt'], [1, 2], true)) {
            $_SESSION['current_admin_id'] = (int)$user['ma_nd'];
        } else {
            unset($_SESSION['current_admin_id']);
        }
    }

    /**
     * Đăng xuất người dùng: xóa sạch session an toàn
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION['user']);
        unset($_SESSION['current_user_id']);
        unset($_SESSION['current_admin_id']);
        $_SESSION['explicit_logout'] = true;

        // Xóa giỏ hàng phiên hoặc các dữ liệu tạm nếu cần
        // session_destroy() có thể gọi nếu muốn hủy hoàn toàn phiên
    }

    /**
     * Lấy thông tin người dùng hiện tại đang đăng nhập
     */
    public function getCurrentUser(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['user']['ma_nd'])) {
            return $_SESSION['user'];
        }

        if (!empty($_SESSION['current_user_id']) && empty($_SESSION['explicit_logout'])) {
            $stmt = $this->pdo->prepare("
                SELECT nd.*, vt.ten_vt, vt.mo_ta_quyen
                FROM nd_nguoi_dung nd
                INNER JOIN vt_vai_tro vt ON nd.ma_vt = vt.ma_vt
                WHERE nd.ma_nd = :id AND nd.trang_thai = 1
                LIMIT 1
            ");
            $stmt->execute(['id' => (int)$_SESSION['current_user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                $this->setUserSession($user);
                return $_SESSION['user'];
            }
        }

        return null;
    }

    /**
     * Kiểm tra người dùng đã đăng nhập chưa
     */
    public function isLoggedIn(): bool
    {
        return $this->getCurrentUser() !== null;
    }

    /**
     * Kiểm tra xem người dùng hiện tại có thuộc danh sách vai trò cho phép không
     */
    public function hasRole(int ...$allowedRoles): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        return in_array((int)$user['ma_vt'], $allowedRoles, true);
    }

    /**
     * Kiểm tra xem người dùng có quyền truy cập Quản trị (Admin: 1 hoặc Nhân viên: 2)
     */
    public function hasAdminAccess(): bool
    {
        return $this->hasRole(1, 2);
    }
}
