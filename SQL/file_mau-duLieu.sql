-- =================================================================================
-- KỊCH BẢN CƠ SỞ DỮ LIỆU MYSQL CHUẨN HÓA THỰC TẾ (16 BẢNG THUẦN VIỆT)
-- Dự án: Website Quản lý Cửa hàng Quần áo (Khách hàng Thân thiết)
-- Nhóm thực hiện: Lê Minh Đức, Lê Thảo Quyên, Huỳnh Thị Thanh Thảo, Neàng Ra
-- Hướng dẫn DBeaver: Bôi đen toàn bộ hoặc nhấn Alt + X để chạy lại từ đầu
-- =================================================================================

CREATE DATABASE IF NOT EXISTS csdl_cua_hang_quan_ao
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE csdl_cua_hang_quan_ao;

-- ---------------------------------------------------------------------------------
-- BƯỚC 1: XÓA SẠCH CÁC BẢNG CŨ ĐỂ TẠO LẠI TỪ ĐẦU
-- ---------------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS lsd_lich_su_diem;
DROP TABLE IF EXISTS ctdh_chi_tiet_dh;
DROP TABLE IF EXISTS dh_don_hang;
DROP TABLE IF EXISTS gh_gio_hang;
DROP TABLE IF EXISTS pnk_phieu_nhap_kho;
DROP TABLE IF EXISTS ha_hinh_anh_sp;
DROP TABLE IF EXISTS bt_bien_the_sp;
DROP TABLE IF EXISTS sp_san_pham;
DROP TABLE IF EXISTS dm_danh_muc_sp;
DROP TABLE IF EXISTS vkh_vi_voucher_kh;
DROP TABLE IF EXISTS pgg_phieu_giam_gia;
DROP TABLE IF EXISTS ttd_the_tich_diem;
DROP TABLE IF EXISTS htv_hang_thanh_vien;
DROP TABLE IF EXISTS dc_dia_chi_kh;
DROP TABLE IF EXISTS nd_nguoi_dung;
DROP TABLE IF EXISTS vt_vai_tro;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------------
-- BƯỚC 2: KHỞI TẠO 16 BẢNG (ĐÚNG THIẾT KẾ TRONG FILE EXCEL QUẢN LÝ CÔNG VIỆC)
-- ---------------------------------------------------------------------------------

-- 1. Bảng Vai trò người dùng
CREATE TABLE vt_vai_tro (
    ma_vt INT AUTO_INCREMENT PRIMARY KEY,
    ten_vt VARCHAR(50) NOT NULL,
    mo_ta_quyen VARCHAR(255) DEFAULT NULL,
    trang_thai TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- 2. Bảng Người dùng / Tài khoản
CREATE TABLE nd_nguoi_dung (
    ma_nd INT AUTO_INCREMENT PRIMARY KEY,
    ma_vt INT NOT NULL DEFAULT 3,
    ho_ten VARCHAR(100) NOT NULL,
    ten_dang_nhap VARCHAR(50) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    sdt VARCHAR(15) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    anh_dai_dien VARCHAR(255) DEFAULT 'default_avatar.png',
    trang_thai TINYINT(1) DEFAULT 1,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_nd_vaitro FOREIGN KEY (ma_vt) REFERENCES vt_vai_tro(ma_vt)
) ENGINE=InnoDB;

-- 3. Bảng Địa chỉ khách hàng
CREATE TABLE dc_dia_chi_kh (
    ma_dc INT AUTO_INCREMENT PRIMARY KEY,
    ma_nd INT NOT NULL,
    ten_nguoi_nhan VARCHAR(100) NOT NULL,
    sdt_nhan VARCHAR(15) NOT NULL,
    tinh_thanh VARCHAR(100) NOT NULL,
    quan_huyen VARCHAR(100) NOT NULL,
    phuong_xa VARCHAR(100) NOT NULL,
    dia_chi_cu_the VARCHAR(255) NOT NULL,
    mac_dinh TINYINT(1) DEFAULT 0,
    CONSTRAINT fk_dc_nguoidung FOREIGN KEY (ma_nd) REFERENCES nd_nguoi_dung(ma_nd) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Bảng Hạng thành viên
CREATE TABLE htv_hang_thanh_vien (
    ma_hang INT AUTO_INCREMENT PRIMARY KEY,
    ten_hang VARCHAR(50) NOT NULL,
    diem_toi_thieu INT NOT NULL DEFAULT 0,
    phan_tram_giam DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    he_so_tich_diem DECIMAL(3,2) NOT NULL DEFAULT 1.00,
    mo_ta_uu_dai VARCHAR(255) DEFAULT NULL,
    mau_sac_the VARCHAR(20) DEFAULT '#CD7F32'
) ENGINE=InnoDB;

-- 5. Bảng Thẻ tích điểm khách hàng
CREATE TABLE ttd_the_tich_diem (
    ma_the INT AUTO_INCREMENT PRIMARY KEY,
    ma_nd INT NOT NULL UNIQUE,
    ma_hang INT NOT NULL DEFAULT 1,
    ma_so_the VARCHAR(30) NOT NULL UNIQUE,
    tong_diem_tich_luy INT NOT NULL DEFAULT 0,
    diem_kha_dung INT NOT NULL DEFAULT 0,
    ngay_cap DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ttd_nguoidung FOREIGN KEY (ma_nd) REFERENCES nd_nguoi_dung(ma_nd) ON DELETE CASCADE,
    CONSTRAINT fk_ttd_hangtv FOREIGN KEY (ma_hang) REFERENCES htv_hang_thanh_vien(ma_hang)
) ENGINE=InnoDB;

-- 6. Bảng Phiếu giảm giá (Voucher)
CREATE TABLE pgg_phieu_giam_gia (
    ma_pgg INT AUTO_INCREMENT PRIMARY KEY,
    ma_code VARCHAR(30) NOT NULL UNIQUE,
    ten_pgg VARCHAR(150) NOT NULL,
    loai_giam ENUM('TIEN', 'PHAN_TRAM') NOT NULL DEFAULT 'TIEN',
    gia_tri_giam DECIMAL(12,2) NOT NULL,
    don_toi_thieu DECIMAL(12,2) NOT NULL DEFAULT 0,
    diem_can_doi INT NOT NULL DEFAULT 0,
    ma_hang_yeu_cau INT NOT NULL DEFAULT 1,
    so_luong INT NOT NULL DEFAULT 100,
    ngay_bd DATETIME NOT NULL,
    ngay_kt DATETIME NOT NULL,
    CONSTRAINT fk_pgg_hangtv FOREIGN KEY (ma_hang_yeu_cau) REFERENCES htv_hang_thanh_vien(ma_hang)
) ENGINE=InnoDB;

-- 7. Bảng Ví Voucher khách hàng
CREATE TABLE vkh_vi_voucher_kh (
    ma_vi_vch INT AUTO_INCREMENT PRIMARY KEY,
    ma_nd INT NOT NULL,
    ma_pgg INT NOT NULL,
    ngay_doi DATETIME DEFAULT CURRENT_TIMESTAMP,
    trang_thai_su_dung TINYINT(1) DEFAULT 0,
    ngay_su_dung DATETIME DEFAULT NULL,
    CONSTRAINT fk_vkh_nguoidung FOREIGN KEY (ma_nd) REFERENCES nd_nguoi_dung(ma_nd) ON DELETE CASCADE,
    CONSTRAINT fk_vkh_pgg FOREIGN KEY (ma_pgg) REFERENCES pgg_phieu_giam_gia(ma_pgg) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Bảng Danh mục sản phẩm
CREATE TABLE dm_danh_muc_sp (
    ma_dm INT AUTO_INCREMENT PRIMARY KEY,
    ten_dm VARCHAR(100) NOT NULL,
    duong_dan_tinh VARCHAR(120) DEFAULT NULL,
    ma_dm_cha INT DEFAULT NULL,
    mo_ta VARCHAR(255) DEFAULT NULL,
    thu_tu_hien_thi INT DEFAULT 1,
    trang_thai TINYINT(1) DEFAULT 1,
    CONSTRAINT fk_dm_cha FOREIGN KEY (ma_dm_cha) REFERENCES dm_danh_muc_sp(ma_dm) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 9. Bảng Sản phẩm thời trang
CREATE TABLE sp_san_pham (
    ma_sp INT AUTO_INCREMENT PRIMARY KEY,
    ma_dm INT NOT NULL,
    ten_sp VARCHAR(200) NOT NULL,
    gia_niem_yet DECIMAL(12,2) NOT NULL,
    gia_khuyen_mai DECIMAL(12,2) NOT NULL,
    chat_lieu VARCHAR(100) DEFAULT NULL,
    mo_ta_chi_tiet TEXT DEFAULT NULL,
    anh_chinh VARCHAR(255) DEFAULT 'no_image.jpg',
    luot_xem INT DEFAULT 0,
    trang_thai TINYINT(1) DEFAULT 1,
    CONSTRAINT fk_sp_danhmuc FOREIGN KEY (ma_dm) REFERENCES dm_danh_muc_sp(ma_dm)
) ENGINE=InnoDB;

-- 10. Bảng Biến thể sản phẩm (Size / Màu)
CREATE TABLE bt_bien_the_sp (
    ma_bt INT AUTO_INCREMENT PRIMARY KEY,
    ma_sp INT NOT NULL,
    ma_sku VARCHAR(50) NOT NULL UNIQUE,
    kich_co VARCHAR(20) NOT NULL,
    mau_sac VARCHAR(50) NOT NULL,
    gia_ban_rieng DECIMAL(12,2) NOT NULL,
    so_luong_ton INT NOT NULL DEFAULT 0,
    trang_thai TINYINT(1) DEFAULT 1,
    CONSTRAINT fk_bt_sanpham FOREIGN KEY (ma_sp) REFERENCES sp_san_pham(ma_sp) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. Bảng Hình ảnh sản phẩm
CREATE TABLE ha_hinh_anh_sp (
    ma_ha INT AUTO_INCREMENT PRIMARY KEY,
    ma_sp INT NOT NULL,
    duong_dan_anh VARCHAR(255) NOT NULL,
    mau_sac_lien_ket VARCHAR(50) DEFAULT NULL,
    thu_tu INT DEFAULT 1,
    la_anh_chinh TINYINT(1) DEFAULT 0,
    CONSTRAINT fk_ha_sanpham FOREIGN KEY (ma_sp) REFERENCES sp_san_pham(ma_sp) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. Bảng Phiếu nhập kho hàng
CREATE TABLE pnk_phieu_nhap_kho (
    ma_pnk INT AUTO_INCREMENT PRIMARY KEY,
    ma_bt INT NOT NULL,
    ma_nd_nhap INT NOT NULL,
    so_luong_nhap INT NOT NULL,
    gia_nhap DECIMAL(12,2) NOT NULL,
    nha_cung_cap VARCHAR(150) DEFAULT NULL,
    ghi_chu VARCHAR(255) DEFAULT NULL,
    ngay_nhap DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pnk_bienthe FOREIGN KEY (ma_bt) REFERENCES bt_bien_the_sp(ma_bt),
    CONSTRAINT fk_pnk_nguoidung FOREIGN KEY (ma_nd_nhap) REFERENCES nd_nguoi_dung(ma_nd)
) ENGINE=InnoDB;

-- 13. Bảng Giỏ hàng trực tuyến
CREATE TABLE gh_gio_hang (
    ma_gh INT AUTO_INCREMENT PRIMARY KEY,
    ma_nd INT NOT NULL,
    ma_bt INT NOT NULL,
    so_luong_mua INT NOT NULL DEFAULT 1,
    ngay_them DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gh_nguoidung FOREIGN KEY (ma_nd) REFERENCES nd_nguoi_dung(ma_nd) ON DELETE CASCADE,
    CONSTRAINT fk_gh_bienthe FOREIGN KEY (ma_bt) REFERENCES bt_bien_the_sp(ma_bt) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 14. Bảng Đơn đặt hàng
CREATE TABLE dh_don_hang (
    ma_dh INT AUTO_INCREMENT PRIMARY KEY,
    ma_nd INT NOT NULL,
    ma_pgg INT DEFAULT NULL,
    tong_tien_hang DECIMAL(12,2) NOT NULL,
    tien_giam_hang_tv DECIMAL(12,2) NOT NULL DEFAULT 0,
    tien_giam_voucher DECIMAL(12,2) NOT NULL DEFAULT 0,
    diem_su_dung INT NOT NULL DEFAULT 0,
    thanh_tien DECIMAL(12,2) NOT NULL,
    diem_cong_du_kien INT NOT NULL DEFAULT 0,
    pt_thanh_toan ENUM('COD', 'CHUYEN_KHOAN') DEFAULT 'COD',
    trang_thai_dh ENUM('CHO_XAC_NHAN', 'DANG_GIAO', 'HOAN_THANH', 'DA_HUY') DEFAULT 'CHO_XAC_NHAN',
    ngay_dat DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dh_nguoidung FOREIGN KEY (ma_nd) REFERENCES nd_nguoi_dung(ma_nd),
    CONSTRAINT fk_dh_pgg FOREIGN KEY (ma_pgg) REFERENCES pgg_phieu_giam_gia(ma_pgg) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 15. Bảng Chi tiết đơn hàng & Đánh giá
CREATE TABLE ctdh_chi_tiet_dh (
    ma_ctdh INT AUTO_INCREMENT PRIMARY KEY,
    ma_dh INT NOT NULL,
    ma_bt INT NOT NULL,
    so_luong INT NOT NULL,
    don_gia_luc_mua DECIMAL(12,2) NOT NULL,
    thanh_tien_muc DECIMAL(12,2) NOT NULL,
    so_sao_danh_gia TINYINT DEFAULT NULL,
    noi_dung_danh_gia TEXT DEFAULT NULL,
    da_cong_diem_dg TINYINT(1) DEFAULT 0,
    CONSTRAINT fk_ctdh_donhang FOREIGN KEY (ma_dh) REFERENCES dh_don_hang(ma_dh) ON DELETE CASCADE,
    CONSTRAINT fk_ctdh_bienthe FOREIGN KEY (ma_bt) REFERENCES bt_bien_the_sp(ma_bt)
) ENGINE=InnoDB;

-- 16. Bảng Lịch sử biến động điểm
CREATE TABLE lsd_lich_su_diem (
    ma_ls_diem INT AUTO_INCREMENT PRIMARY KEY,
    ma_the INT NOT NULL,
    ma_dh INT DEFAULT NULL,
    loai_giao_dich ENUM('CONG_MUA_HANG', 'CONG_DANH_GIA', 'TRU_DOI_VOUCHER', 'TRU_TIEU_DIEM') NOT NULL,
    so_diem_thay_doi INT NOT NULL,
    so_du_sau_gd INT NOT NULL,
    noi_dung VARCHAR(255) NOT NULL,
    thoi_gian DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lsd_the FOREIGN KEY (ma_the) REFERENCES ttd_the_tich_diem(ma_the) ON DELETE CASCADE,
    CONSTRAINT fk_lsd_donhang FOREIGN KEY (ma_dh) REFERENCES dh_don_hang(ma_dh) ON DELETE SET NULL
) ENGINE=InnoDB;


-- =================================================================================
-- BƯỚC 3: NẠP DỮ LIỆU THỰC TẾ (KHỚP 100% SỐ HỌC GIỮA TẤT CẢ 16 BẢNG)
-- =================================================================================

-- 3.1. Vai trò hệ thống (3 vai trò)
INSERT INTO vt_vai_tro (ma_vt, ten_vt, mo_ta_quyen, trang_thai) VALUES
(1, 'Quản trị viên (Admin)', 'Toàn quyền cấu hình hệ thống, hạng thành viên, khuyến mãi và thống kê doanh thu', 1),
(2, 'Nhân viên cửa hàng', 'Quản lý sản phẩm, lập phiếu nhập kho, xác nhận và cập nhật trạng thái đơn hàng', 1),
(3, 'Khách hàng thành viên', 'Mua sắm trực tuyến, tích điểm thưởng, nâng hạng thẻ và đổi điểm lấy ưu đãi', 1);

-- 3.2. Người dùng (Đưa đúng thành viên nhóm + các khách hàng thực tế)
INSERT INTO nd_nguoi_dung (ma_nd, ma_vt, ho_ten, ten_dang_nhap, mat_khau, sdt, email, anh_dai_dien, trang_thai, ngay_tao) VALUES
(1, 1, 'Lê Minh Đức', 'admin_duc', '123456', '0901235629', 'duc_dth235629@student.agu.edu.vn', 'avatar_duc.png', 1, '2026-09-01 08:00:00'),
(2, 2, 'Lê Thảo Quyên', 'staff_quyen', '123456', '0901235746', 'quyen_dth235746@student.agu.edu.vn', 'avatar_quyen.png', 1, '2026-09-01 08:15:00'),
(3, 2, 'Huỳnh Thị Thanh Thảo', 'staff_thao', '123456', '0901235770', 'thao_dth235770@student.agu.edu.vn', 'avatar_thao.png', 1, '2026-09-01 08:30:00'),
(4, 3, 'Neàng Ra', 'neangra', '123456', '0918223344', 'neangra@student.agu.edu.vn', 'avatar_ra.png', 1, '2026-09-10 09:20:00'),
(5, 3, 'Nguyễn Hoàng Nam', 'hoangnam_lx', '123456', '0939556677', 'hoangnam.ag@gmail.com', 'avatar_nam.png', 1, '2026-09-12 14:10:00'),
(6, 3, 'Trần Ngọc Bảo Trân', 'baotran_vip', '123456', '0988778899', 'baotran.tran@gmail.com', 'avatar_tran.png', 1, '2026-09-15 19:45:00'),
(7, 3, 'Đặng Quốc Huy', 'quochuy_99', '123456', '0977112233', 'quochuy99@gmail.com', 'default_avatar.png', 1, '2026-10-05 10:00:00');

-- 3.3. Sổ địa chỉ nhận hàng của khách
INSERT INTO dc_dia_chi_kh (ma_dc, ma_nd, ten_nguoi_nhan, sdt_nhan, tinh_thanh, quan_huyen, phuong_xa, dia_chi_cu_the, mac_dinh) VALUES
(1, 4, 'Neàng Ra', '0918223344', 'An Giang', 'TP. Long Xuyên', 'Phường Đông Xuyên', 'Số 18 Ung Văn Khiêm (Khu ĐH An Giang)', 1),
(2, 4, 'Neàng Ra (Nhà riêng)', '0918223344', 'An Giang', 'Thị xã Tịnh Biên', 'Phường Nhà Bàng', 'Số 45 Đường Trà Sư', 0),
(3, 5, 'Nguyễn Hoàng Nam', '0939556677', 'An Giang', 'TP. Long Xuyên', 'Phường Mỹ Bình', 'Số 102 Nguyễn Thái Học', 1),
(4, 6, 'Trần Ngọc Bảo Trân', '0988778899', 'An Giang', 'TP. Long Xuyên', 'Phường Mỹ Xuyên', 'Số 25 Hà Hoàng Hổ', 1),
(5, 7, 'Đặng Quốc Huy', '0977112233', 'TP. Cần Thơ', 'Quận Ninh Kiều', 'Phường Xuân Khánh', 'Số 88 Đường 3 Tháng 2', 1);

-- 3.4. Cấu hình 4 Bậc Hạng Thành Viên (Quy chuẩn: 10.000đ = 1 điểm cơ bản)
-- Mốc điểm thiết kế thực tế: Đồng (0đ), Bạc (200đ ~ 2 triệu), Vàng (500đ ~ 5 triệu), Kim Cương (1500đ ~ 15 triệu)
INSERT INTO htv_hang_thanh_vien (ma_hang, ten_hang, diem_toi_thieu, phan_tram_giam, he_so_tich_diem, mo_ta_uu_dai, mau_sac_the) VALUES
(1, 'Hạng Đồng (Member)', 0, 0.00, 1.00, 'Tích lũy cơ bản: 10.000đ = 1 điểm thưởng. Đổi voucher cơ bản.', '#CD7F32'),
(2, 'Hạng Bạc (Silver)', 200, 3.00, 1.20, 'Giảm trực tiếp 3% mọi đơn hàng. Nhân 1.2 lần điểm tích lũy.', '#A8A9AD'),
(3, 'Hạng Vàng (Gold)', 500, 5.00, 1.50, 'Giảm trực tiếp 5% mọi đơn hàng. Nhân 1.5 lần điểm tích lũy. Ưu đãi kho Voucher VIP.', '#D4AF37'),
(4, 'Hạng Kim Cương (Diamond)', 1500, 10.00, 2.00, 'Giảm trực tiếp 10% mọi đơn hàng. Nhân đôi (x2.0) điểm tích lũy và ưu tiên giao hàng.', '#00ACC1');

-- 3.5. Thẻ tích điểm khách hàng (Số liệu khớp tuyệt đối với bảng lsd_lich_su_diem bên dưới)
-- + Neàng Ra (ma_nd=4): Đang có 185/200 điểm (Hạng Đồng) -> Chỉ cần Admin duyệt Đơn #4 (+24 điểm) là lên 209 điểm -> Tự động nhảy lên Hạng Bạc!
-- + Hoàng Nam (ma_nd=5): Tổng tích lũy 303 điểm (Hạng Bạc), đã dùng 100 điểm đổi Voucher nên Điểm khả dụng còn 203.
-- + Bảo Trân (ma_nd=6): Tổng tích lũy 672 điểm (Hạng Vàng), đã đổi Voucher (-150đ) và tiêu điểm (-50đ) nên Điểm khả dụng còn 472.
-- + Quốc Huy (ma_nd=7): Khách mới đăng ký, 0 điểm.
INSERT INTO ttd_the_tich_diem (ma_the, ma_nd, ma_hang, ma_so_the, tong_diem_tich_luy, diem_kha_dung, ngay_cap) VALUES
(1, 4, 1, 'THE-AGU-26001', 185, 185, '2026-09-10 09:20:00'),
(2, 5, 2, 'THE-AGU-26002', 303, 203, '2026-09-12 14:10:00'),
(3, 6, 3, 'THE-AGU-26003', 672, 472, '2026-09-15 19:45:00'),
(4, 7, 1, 'THE-AGU-26004', 0, 0, '2026-10-05 10:00:00');

-- 3.6. Kho Phiếu giảm giá / Chiến dịch Khuyến mãi (Voucher)
INSERT INTO pgg_phieu_giam_gia (ma_pgg, ma_code, ten_pgg, loai_giam, gia_tri_giam, don_toi_thieu, diem_can_doi, ma_hang_yeu_cau, so_luong, ngay_bd, ngay_kt) VALUES
(1, 'GIAM30K', 'Giảm 30.000đ cho đơn từ 250.000đ', 'TIEN', 30000, 250000, 100, 1, 100, '2026-09-01 00:00:00', '2026-12-31 23:59:59'),
(2, 'VIP50K', 'Voucher 50.000đ cho đơn từ 400.000đ (Từ Hạng Bạc)', 'TIEN', 50000, 400000, 150, 2, 80, '2026-09-01 00:00:00', '2026-12-31 23:59:59'),
(3, 'GOLD10PT', 'Giảm 10% đơn hàng từ 600.000đ (Đặc quyền Hạng Vàng)', 'PHAN_TRAM', 10.00, 600000, 250, 3, 50, '2026-09-01 00:00:00', '2026-12-31 23:59:59'),
(4, 'SIEUSALE100K', 'Siêu Voucher giảm 100.000đ cho đơn từ 800.000đ', 'TIEN', 100000, 800000, 300, 3, 30, '2026-10-01 00:00:00', '2026-12-31 23:59:59');

-- 3.7. Ví Voucher khách hàng (Khớp với lịch sử trừ điểm đổi Voucher)
INSERT INTO vkh_vi_voucher_kh (ma_vi_vch, ma_nd, ma_pgg, ngay_doi, trang_thai_su_dung, ngay_su_dung) VALUES
(1, 5, 1, '2026-10-02 10:00:00', 0, NULL),                  -- Hoàng Nam đổi mã GIAM30K (-100 điểm), chưa dùng
(2, 6, 2, '2026-10-10 15:30:00', 1, '2026-10-18 20:15:00'); -- Bảo Trân đổi mã VIP50K (-150 điểm), đã dùng ở Đơn #5

-- 3.8. Danh mục sản phẩm đa cấp (2 Danh mục gốc + 6 Danh mục con)
INSERT INTO dm_danh_muc_sp (ma_dm, ten_dm, duong_dan_tinh, ma_dm_cha, mo_ta, thu_tu_hien_thi, trang_thai) VALUES
(1, 'Thời trang Nam', 'thoi-trang-nam', NULL, 'Trang phục nam lịch lãm, năng động', 1, 1),
(2, 'Thời trang Nữ', 'thoi-trang-nu', NULL, 'Trang phục nữ thanh lịch, hiện đại', 2, 1),
(3, 'Áo Polo & Áo Thun Nam', 'ao-polo-thun-nam', 1, 'Áo thun cổ bẻ, áo phông cotton thoáng mát', 1, 1),
(4, 'Áo Sơ Mi & Áo Khoác Nam', 'ao-somi-khoac-nam', 1, 'Sơ mi công sở chống nhăn, áo khoác bomber', 2, 1),
(5, 'Quần Dài & Quần Short Nam', 'quan-nam', 1, 'Quần Kaki co giãn, quần Jeans ống đứng, quần Short', 3, 1),
(6, 'Đầm & Váy Nữ', 'dam-vay-nu', 2, 'Đầm dự tiệc, đầm dạo phố, chân váy công sở', 1, 1),
(7, 'Áo Kiểu & Sơ Mi Nữ', 'ao-kieu-somi-nu', 2, 'Áo sơ mi lụa Hàn Quốc, áo kiểu nữ tính', 2, 1);

-- 3.9. Sản phẩm thời trang (10 sản phẩm đa dạng hỗ trợ lọc & đề xuất cá nhân hóa UC15)
INSERT INTO sp_san_pham (ma_sp, ma_dm, ten_sp, gia_niem_yet, gia_khuyen_mai, chat_lieu, mo_ta_chi_tiet, anh_chinh, luot_xem, trang_thai) VALUES
(1, 3, 'Áo Polo Nam Pique Coolmax Phối Cổ', 290000, 245000, 'Cotton Pique Coolmax', 'Thương hiệu: Routine. Áo polo mắt chim thoáng khí, co giãn 4 chiều, thấm hút mồ hôi vượt trội.', 'sp01_polo_coolmax.jpg', 340, 1),
(2, 3, 'Áo Thun Unisex Cotton Compact 250gsm', 220000, 185000, '100% Cotton Compact', 'Thương hiệu: Coolmate. Áo thun cổ tròn dày dặn, không xù lông, đứng form chuẩn streetwear.', 'sp02_tshirt_compact.jpg', 285, 1),
(3, 5, 'Quần Kaki Nam Co Giãn Slimfit Công Sở', 420000, 375000, 'Kaki Thun Hàn Quốc', 'Thương hiệu: Owen. Quần kaki phom ôm vừa vặn, lịch sự khi đi học và đi làm.', 'sp03_kaki_slimfit.jpg', 190, 1),
(4, 4, 'Áo Sơ Mi Nam Oxford Tay Dài Chống Nhăn', 380000, 330000, 'Vải Oxford Cao Cấp', 'Thương hiệu: Việt Tiến. Sơ mi nam phom Regular tôn dáng, giữ phom cả ngày.', 'sp04_somi_oxford.jpg', 210, 1),
(5, 5, 'Quần Jeans Nam Denim Straight Ống Đứng', 520000, 450000, 'Denim Cotton 13oz', 'Thương hiệu: Canifa. Quần bò nam màu xanh cổ điển, bền màu, đường may chắc chắn.', 'sp05_jeans_straight.jpg', 260, 1),
(6, 5, 'Quần Short Nam Kaki Lưng Thun Năng Động', 260000, 225000, 'Kaki Cotton Mềm', 'Thương hiệu: Yame. Quần short ngang gối thoải mái dạo phố, du lịch.', 'sp06_short_kaki.jpg', 145, 1),
(7, 6, 'Đầm Nữ Cổ Vuông Tay Bồng Dáng Chữ A', 460000, 395000, 'Vải Taffeta Ánh Nhẹ', 'Thương hiệu: Gumac. Đầm xòe chữ A tôn eo, phù hợp dự tiệc và dạo phố cuối tuần.', 'sp07_dam_taffeta.jpg', 410, 1),
(8, 4, 'Áo Khoác Bomber Gió 2 Lớp Trượt Nước', 550000, 495000, 'Polyester Chống Thấm Nhẹ', 'Thương hiệu: Uniqlo Style. Áo khoác bo thun cổ tay, cản gió và chống tia UV.', 'sp08_bomber_gio.jpg', 310, 1),
(9, 6, 'Chân Váy Xếp Ly Dáng Dài Hàn Quốc', 340000, 295000, 'Tuyết Mưa Cao Cấp', 'Thương hiệu: Elise. Chân váy midi xếp ly che khuyết điểm, dễ phối cùng áo sơ mi hoặc áo thun.', 'sp09_chanvay_xeply.jpg', 275, 1),
(10, 7, 'Áo Sơ Mi Nữ Lụa Cổ Đức Thanh Lịch', 360000, 315000, 'Lụa Satin Mềm Mịn', 'Thương hiệu: Juno Fashion. Áo sơ mi nữ chất lụa mát lạnh, không nhăn, tôn vẻ sang trọng.', 'sp10_somi_lua_nu.jpg', 330, 1);

-- 3.10. Biến thể sản phẩm (20 SKU chuẩn xác: Tồn kho = Nhập kho [Bảng 12] - Đã bán [Bảng 15])
INSERT INTO bt_bien_the_sp (ma_bt, ma_sp, ma_sku, kich_co, mau_sac, gia_ban_rieng, so_luong_ton, trang_thai) VALUES
(1, 1, 'POLO-CMX-DEN-M', 'M', 'Đen', 245000, 29, 1),           -- Nhập 30, Bán 1 (Đơn #4) -> Tồn 29
(2, 1, 'POLO-CMX-DEN-L', 'L', 'Đen', 245000, 28, 1),           -- Nhập 30, Bán 2 (Đơn #1) -> Tồn 28
(3, 1, 'POLO-CMX-TRG-L', 'L', 'Trắng', 245000, 23, 1),         -- Nhập 25, Bán 2 (Đơn #2) -> Tồn 23
(4, 2, 'TSHIRT-CTN-DEN-M', 'M', 'Đen', 185000, 40, 1),         -- Nhập 40, Bán 0 -> Tồn 40
(5, 2, 'TSHIRT-CTN-TRG-M', 'M', 'Trắng', 185000, 37, 1),       -- Nhập 40, Bán 3 (Đơn #1) -> Tồn 37
(6, 3, 'KAKI-SLM-DEN-30', 'Size 30', 'Đen', 375000, 19, 1),    -- Nhập 20, Bán 1 (Đơn #6) -> Tồn 19
(7, 3, 'KAKI-SLM-BE-31', 'Size 31', 'Màu Be', 375000, 19, 1),  -- Nhập 20, Bán 1 (Đơn #1) -> Tồn 19
(8, 4, 'SOMI-OXF-TRG-M', 'M', 'Trắng', 330000, 24, 1),         -- Nhập 25, Bán 1 (Đơn #6) -> Tồn 24
(9, 4, 'SOMI-OXF-XNH-L', 'L', 'Xanh Pastel', 330000, 24, 1),   -- Nhập 25, Bán 1 (Đơn #1) -> Tồn 24
(10, 5, 'JEAN-STR-XNH-29', 'Size 29', 'Xanh Cổ Điển', 450000, 20, 1), -- Nhập 20, Bán 0 -> Tồn 20
(11, 5, 'JEAN-STR-XNH-30', 'Size 30', 'Xanh Cổ Điển', 450000, 22, 1), -- Nhập 25, Bán 3 (Đơn #2) -> Tồn 22
(12, 6, 'SHORT-KAK-BE-L', 'L', 'Màu Be', 225000, 30, 1),       -- Nhập 30, Bán 0 -> Tồn 30
(13, 7, 'DAM-TAF-DO-S', 'S', 'Đỏ Đô', 395000, 16, 1),          -- Nhập 20, Bán 4 (Đơn #3) -> Tồn 16
(14, 7, 'DAM-TAF-KEM-M', 'M', 'Trắng Kem', 395000, 16, 1),     -- Nhập 20, Bán 4 (Đơn #3) -> Tồn 16
(15, 8, 'BOMB-GIO-DEN-L', 'L', 'Đen', 495000, 13, 1),          -- Nhập 15, Bán 2 (Đơn #2) -> Tồn 13
(16, 8, 'BOMB-GIO-REU-M', 'M', 'Xanh Rêu', 495000, 14, 1),     -- Nhập 15, Bán 1 (Đơn #3) -> Tồn 14
(17, 9, 'CVAY-XLY-NAU-M', 'M', 'Nâu Tây', 295000, 21, 1),      -- Nhập 25, Bán 4 (Đơn #3) -> Tồn 21
(18, 9, 'CVAY-XLY-DEN-S', 'S', 'Đen', 295000, 24, 1),          -- Nhập 25, Bán 1 (Đơn #5) -> Tồn 24
(19, 10, 'SOMI-LUA-TRG-S', 'S', 'Trắng Ngọc', 315000, 18, 1),  -- Nhập 20, Bán 2 (Đơn #3) -> Tồn 18
(20, 10, 'SOMI-LUA-HNG-M', 'M', 'Hồng Phấn', 315000, 18, 1);   -- Nhập 20, Bán 2 (Đơn #5) -> Tồn 18

-- 3.11. Bộ sưu tập Hình ảnh sản phẩm theo màu sắc
INSERT INTO ha_hinh_anh_sp (ma_ha, ma_sp, duong_dan_anh, mau_sac_lien_ket, thu_tu, la_anh_chinh) VALUES
(1, 1, 'sp01_polo_den.jpg', 'Đen', 1, 1),
(2, 1, 'sp01_polo_trang.jpg', 'Trắng', 2, 0),
(3, 2, 'sp02_tshirt_den.jpg', 'Đen', 1, 1),
(4, 2, 'sp02_tshirt_trang.jpg', 'Trắng', 2, 0),
(5, 3, 'sp03_kaki_den.jpg', 'Đen', 1, 1),
(6, 3, 'sp03_kaki_be.jpg', 'Màu Be', 2, 0),
(7, 4, 'sp04_somi_trang.jpg', 'Trắng', 1, 1),
(8, 4, 'sp04_somi_xanh.jpg', 'Xanh Pastel', 2, 0),
(9, 5, 'sp05_jeans_xanh.jpg', 'Xanh Cổ Điển', 1, 1),
(10, 6, 'sp06_short_be.jpg', 'Màu Be', 1, 1),
(11, 7, 'sp07_dam_dodo.jpg', 'Đỏ Đô', 1, 1),
(12, 7, 'sp07_dam_kem.jpg', 'Trắng Kem', 2, 0),
(13, 8, 'sp08_bomber_den.jpg', 'Đen', 1, 1),
(14, 8, 'sp08_bomber_reu.jpg', 'Xanh Rêu', 2, 0),
(15, 9, 'sp09_chanvay_nau.jpg', 'Nâu Tây', 1, 1),
(16, 9, 'sp09_chanvay_den.jpg', 'Đen', 2, 0),
(17, 10, 'sp10_somi_lua_trang.jpg', 'Trắng Ngọc', 1, 1),
(18, 10, 'sp10_somi_lua_hong.jpg', 'Hồng Phấn', 2, 0);

-- 3.12. Phiếu nhập kho hàng (Đầy đủ chứng từ nhập kho đầu kỳ cho cả 20 biến thể SKU)
INSERT INTO pnk_phieu_nhap_kho (ma_pnk, ma_bt, ma_nd_nhap, so_luong_nhap, gia_nhap, nha_cung_cap, ghi_chu, ngay_nhap) VALUES
(1, 1, 1, 30, 140000, 'Xưởng May Việt Tiến', 'Nhập kho đầu kỳ Áo Polo Đen M', '2026-09-02 09:00:00'),
(2, 2, 1, 30, 140000, 'Xưởng May Việt Tiến', 'Nhập kho đầu kỳ Áo Polo Đen L', '2026-09-02 09:05:00'),
(3, 3, 1, 25, 140000, 'Xưởng May Việt Tiến', 'Nhập kho đầu kỳ Áo Polo Trắng L', '2026-09-02 09:10:00'),
(4, 4, 2, 40, 95000, 'Công ty Dệt May Đồng Nai', 'Nhập kho Áo Thun Compact Đen M', '2026-09-03 10:00:00'),
(5, 5, 2, 40, 95000, 'Công ty Dệt May Đồng Nai', 'Nhập kho Áo Thun Compact Trắng M', '2026-09-03 10:05:00'),
(6, 6, 2, 20, 210000, 'Tổng Công ty May Nhà Bè', 'Nhập kho Quần Kaki Đen Size 30', '2026-09-04 14:00:00'),
(7, 7, 2, 20, 210000, 'Tổng Công ty May Nhà Bè', 'Nhập kho Quần Kaki Be Size 31', '2026-09-04 14:10:00'),
(8, 8, 1, 25, 180000, 'Xưởng May Việt Tiến', 'Nhập kho Sơ mi Oxford Trắng M', '2026-09-05 08:30:00'),
(9, 9, 1, 25, 180000, 'Xưởng May Việt Tiến', 'Nhập kho Sơ mi Oxford Xanh L', '2026-09-05 08:35:00'),
(10, 10, 2, 20, 260000, 'Xưởng Denim Sài Gòn', 'Nhập kho Quần Jeans Size 29', '2026-09-06 11:00:00'),
(11, 11, 2, 25, 260000, 'Xưởng Denim Sài Gòn', 'Nhập kho Quần Jeans Size 30', '2026-09-06 11:05:00'),
(12, 12, 2, 30, 120000, 'Tổng Công ty May Nhà Bè', 'Nhập kho Quần Short Kaki Be L', '2026-09-06 15:00:00'),
(13, 13, 3, 20, 220000, 'Xưởng Thời Trang Gumac', 'Nhập kho Đầm Taffeta Đỏ Đô S', '2026-09-07 09:00:00'),
(14, 14, 3, 20, 220000, 'Xưởng Thời Trang Gumac', 'Nhập kho Đầm Taffeta Trắng Kem M', '2026-09-07 09:05:00'),
(15, 15, 1, 15, 280000, 'Xưởng Áo Khoác Hoàng Phúc', 'Nhập kho Bomber Đen L', '2026-09-08 16:00:00'),
(16, 16, 1, 15, 280000, 'Xưởng Áo Khoác Hoàng Phúc', 'Nhập kho Bomber Xanh Rêu M', '2026-09-08 16:05:00'),
(17, 17, 3, 25, 160000, 'Xưởng Thời Trang Gumac', 'Nhập kho Chân váy xếp ly Nâu M', '2026-09-09 10:00:00'),
(18, 18, 3, 25, 160000, 'Xưởng Thời Trang Gumac', 'Nhập kho Chân váy xếp ly Đen S', '2026-09-09 10:05:00'),
(19, 19, 3, 20, 175000, 'Xưởng Thời Trang Elise', 'Nhập kho Sơ mi lụa Trắng Ngọc S', '2026-09-09 14:00:00'),
(20, 20, 3, 20, 175000, 'Xưởng Thời Trang Elise', 'Nhập kho Sơ mi lụa Hồng Phấn M', '2026-09-09 14:05:00');

-- 3.13. Giỏ hàng trực tuyến (Khách đang chọn hàng chuẩn bị thanh toán)
INSERT INTO gh_gio_hang (ma_gh, ma_nd, ma_bt, so_luong_mua, ngay_them) VALUES
(1, 4, 13, 1, '2026-11-01 20:00:00'), -- Neàng Ra đang để 1 Đầm Đỏ Đô trong giỏ
(2, 5, 4, 2, '2026-11-02 09:30:00'),  -- Hoàng Nam đang để 2 Áo Thun Đen trong giỏ
(3, 6, 14, 1, '2026-11-02 15:45:00'); -- Bảo Trân đang để 1 Đầm Trắng Kem trong giỏ

-- ---------------------------------------------------------------------------------------------------------
-- 3.14. Đơn đặt hàng & 3.15. Chi tiết đơn hàng (CÔNG THỨC TÍNH TIỀN & ĐIỂM KHỚP 100%)
-- ---------------------------------------------------------------------------------------------------------

-- [ĐƠN #1] Khách Neàng Ra (ma_nd=4, Hạng Đồng 0%):
-- Mua: 2x Polo Đen L (490k) + 3x Thun Trắng M (555k) + 1x Kaki Be 31 (375k) + 1x Sơ mi Xanh L (330k) = 1.750.000đ.
-- Điểm cộng: (1.750.000 / 10.000) * 1.0 = 175 điểm.
-- [ĐƠN #2] Khách Hoàng Nam (ma_nd=5, lúc mua là Hạng Đồng 0%):
-- Mua: 3x Jeans 30 (1.350k) + 2x Bomber Đen L (990k) + 2x Polo Trắng L (490k) = 2.830.000đ.
-- Điểm cộng: (2.830.000 / 10.000) * 1.0 = 283 điểm -> Đạt >= 200 điểm nên tự động lên Hạng Bạc!
-- [ĐƠN #3] Khách Bảo Trân (ma_nd=6, lúc mua đầu tiên):
-- Mua: 4x Đầm Đỏ S (1.580k) + 4x Đầm Kem M (1.580k) + 4x Chân váy Nâu M (1.180k) + 2x Sơ mi lụa Trắng S (630k) + 1x Bomber Rêu M (495k) = 5.465.000đ.
-- Điểm cộng: (5.465.000 / 10.000) * 1.0 = 546 điểm -> Đạt >= 500 điểm nên tự động lên Hạng Vàng!
-- [ĐƠN #4 - DÙNG ĐỂ DEMO DUYỆT ĐƠN LÊN HẠNG] Khách Neàng Ra (ma_nd=4, đang có 185 điểm):
-- Mua: 1x Polo Đen M (245.000đ). Trạng thái: CHO_XAC_NHAN. Điểm dự kiến: +24 điểm. (Khi Admin duyệt -> 185 + 24 = 209 điểm -> Lên Hạng Bạc!).
-- [ĐƠN #5 - DEMO KHUYẾN MÃI KÉP HẠNG VÀNG + VOUCHER + TIÊU ĐIỂM] Khách Bảo Trân (ma_nd=6, Hạng Vàng giảm 5%, nhân 1.5x điểm):
-- Mua: 2x Sơ mi lụa Hồng M (630k) + 1x Chân váy Đen S (295k) = 925.000đ.
-- Giảm Hạng Vàng (5% của 925k) = 46.250đ | Giảm Voucher VIP50K (ma_pgg=2) = 50.000đ | Dùng 50 điểm trừ 50.000đ.
-- Thành tiền = 925.000 - 46.250 - 50.000 - 50.000 = 778.750đ.
-- Điểm cộng Hạng Vàng: FLOOR((778.750 / 10.000) * 1.5) = 116 điểm!
-- [ĐƠN #6] Khách Hoàng Nam (ma_nd=5, Hạng Bạc giảm 3%, nhân 1.2x điểm):
-- Mua: 1x Kaki Đen 30 (375k) + 1x Sơ mi Trắng M (330k) = 705.000đ. Giảm Hạng Bạc (3%) = 21.150đ -> Thành tiền = 683.850đ. Trạng thái: DANG_GIAO.

INSERT INTO dh_don_hang (ma_dh, ma_nd, ma_pgg, tong_tien_hang, tien_giam_hang_tv, tien_giam_voucher, diem_su_dung, thanh_tien, diem_cong_du_kien, pt_thanh_toan, trang_thai_dh, ngay_dat) VALUES
(1, 4, NULL, 1750000, 0, 0, 0, 1750000, 175, 'COD', 'HOAN_THANH', '2026-09-20 10:30:00'),
(2, 5, NULL, 2830000, 0, 0, 0, 2830000, 283, 'CHUYEN_KHOAN', 'HOAN_THANH', '2026-09-25 16:20:00'),
(3, 6, NULL, 5465000, 0, 0, 0, 5465000, 546, 'CHUYEN_KHOAN', 'HOAN_THANH', '2026-10-01 11:15:00'),
(4, 4, NULL, 245000, 0, 0, 0, 245000, 24, 'COD', 'CHO_XAC_NHAN', '2026-11-02 08:45:00'),
(5, 6, 2, 925000, 46250, 50000, 50, 778750, 116, 'CHUYEN_KHOAN', 'HOAN_THANH', '2026-10-18 20:15:00'),
(6, 5, NULL, 705000, 21150, 0, 0, 683850, 82, 'COD', 'DANG_GIAO', '2026-11-01 14:00:00');

-- 3.15. Chi tiết đơn hàng & Đánh giá sao sau mua hàng
INSERT INTO ctdh_chi_tiet_dh (ma_ctdh, ma_dh, ma_bt, so_luong, don_gia_luc_mua, thanh_tien_muc, so_sao_danh_gia, noi_dung_danh_gia, da_cong_diem_dg) VALUES
-- Chi tiết Đơn #1 (Tổng 1.750.000đ)
(1, 1, 2, 2, 245000, 490000, 5, 'Áo polo chất vải rất mát, đóng gói cẩn thận, giao tới ĐH An Giang siêu nhanh!', 1),
(2, 1, 5, 3, 185000, 555000, NULL, NULL, 0),
(3, 1, 7, 1, 375000, 375000, NULL, NULL, 0),
(4, 1, 9, 1, 330000, 330000, NULL, NULL, 0),
-- Chi tiết Đơn #2 (Tổng 2.830.000đ)
(5, 2, 11, 3, 450000, 1350000, 5, 'Quần jeans đứng form, màu xanh cổ điển rất đẹp.', 1),
(6, 2, 15, 2, 495000, 990000, 5, 'Áo khoác dày dặn, đi nắng đi mưa nhẹ đều ổn.', 1),
(7, 2, 3, 2, 245000, 490000, NULL, NULL, 0),
-- Chi tiết Đơn #3 (Tổng 5.465.000đ)
(8, 3, 13, 4, 395000, 1580000, 5, 'Đầm đỏ đô mặc lên tôn da, cả nhóm mình mua mặc chụp kỷ yếu ai cũng khen!', 1),
(9, 3, 14, 4, 395000, 1580000, NULL, NULL, 0),
(10, 3, 17, 4, 295000, 1180000, NULL, NULL, 0),
(11, 3, 19, 2, 315000, 630000, NULL, NULL, 0),
(12, 3, 16, 1, 495000, 495000, NULL, NULL, 0),
-- Chi tiết Đơn #4 (Tổng 245.000đ - Đang chờ xác nhận)
(13, 4, 1, 1, 245000, 245000, NULL, NULL, 0),
-- Chi tiết Đơn #5 (Tổng 925.000đ - Đã hoàn thành)
(14, 5, 20, 2, 315000, 630000, 5, 'Lụa mềm mịn, được giảm 5% Hạng Vàng lại áp thêm được Voucher 50k quá hời.', 0),
(15, 5, 18, 1, 295000, 295000, NULL, NULL, 0),
-- Chi tiết Đơn #6 (Tổng 705.000đ - Đang giao)
(16, 6, 6, 1, 375000, 375000, NULL, NULL, 0),
(17, 6, 8, 1, 330000, 330000, NULL, NULL, 0);

-- 3.16. Lịch sử biến động điểm (Sổ cái đối soát khớp từng điểm một từ 0 đến số dư hiện tại)
INSERT INTO lsd_lich_su_diem (ma_ls_diem, ma_the, ma_dh, loai_giao_dich, so_diem_thay_doi, so_du_sau_gd, noi_dung, thoi_gian) VALUES
-- Lịch sử của Thẻ #1 (Neàng Ra: 0 -> +175 -> +10 = 185 điểm)
(1, 1, 1, 'CONG_MUA_HANG', 175, 175, 'Tích điểm đơn hàng hoàn thành #1 (Giá trị: 1.750.000đ - Hệ số x1.0 Hạng Đồng)', '2026-09-22 14:00:00'),
(2, 1, 1, 'CONG_DANH_GIA', 10, 185, 'Thưởng +10 điểm đánh giá 5 sao sản phẩm Áo Polo Nam Pique Coolmax', '2026-09-23 09:15:00'),

-- Lịch sử của Thẻ #2 (Nguyễn Hoàng Nam: 0 -> +283 -> +10 -> +10 -> -100 = 203 điểm khả dụng | Tổng tích lũy = 303)
(3, 2, 2, 'CONG_MUA_HANG', 283, 283, 'Tích điểm đơn hàng hoàn thành #2 (Đạt mốc >= 200 điểm -> Thăng lên Hạng Bạc)', '2026-09-27 11:00:00'),
(4, 2, 2, 'CONG_DANH_GIA', 10, 293, 'Thưởng +10 điểm đánh giá sản phẩm Quần Jeans Nam Denim Straight', '2026-09-28 16:00:00'),
(5, 2, 2, 'CONG_DANH_GIA', 10, 303, 'Thưởng +10 điểm đánh giá sản phẩm Áo Khoác Bomber Gió 2 Lớp', '2026-09-28 16:05:00'),
(6, 2, NULL, 'TRU_DOI_VOUCHER', -100, 203, 'Đổi 100 điểm khả dụng lấy Voucher GIAM30K (Giảm 30.000đ)', '2026-10-02 10:00:00'),

-- Lịch sử của Thẻ #3 (Trần Ngọc Bảo Trân: 0 -> +546 -> +10 -> -150 -> -50 -> +116 = 472 điểm khả dụng | Tổng tích lũy = 672)
(7, 3, 3, 'CONG_MUA_HANG', 546, 546, 'Tích điểm đơn hàng hoàn thành #3 (Đạt mốc >= 500 điểm -> Thăng lên Hạng Vàng)', '2026-10-03 15:00:00'),
(8, 3, 3, 'CONG_DANH_GIA', 10, 556, 'Thưởng +10 điểm đánh giá sản phẩm Đầm Nữ Cổ Vuông Tay Bồng', '2026-10-04 10:20:00'),
(9, 3, NULL, 'TRU_DOI_VOUCHER', -150, 406, 'Đổi 150 điểm khả dụng lấy Voucher VIP50K (Giảm 50.000đ)', '2026-10-10 15:30:00'),
(10, 3, 5, 'TRU_TIEU_DIEM', -50, 356, 'Sử dụng 50 điểm trừ trực tiếp 50.000đ khi thanh toán đơn hàng #5', '2026-10-18 20:15:00'),
(11, 3, 5, 'CONG_MUA_HANG', 116, 472, 'Tích điểm đơn hàng hoàn thành #5 (Thực trả 778.750đ - Nhân hệ số x1.5 Hạng Vàng)', '2026-10-20 09:30:00');