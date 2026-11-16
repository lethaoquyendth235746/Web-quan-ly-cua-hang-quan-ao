# 📋 BÁO CÁO RÀ SOÁT & KIỂM TOÁN MÃ NGUỒN GIAI ĐOẠN 1

| Hạng mục | Nội dung |
| :--- | :--- |
| **Dự án** | Hệ thống Quản lý Cửa hàng Quần áo & Tích điểm Khách hàng Thân thiết (AURA ARCHIVE) |
| **Phạm vi kiểm tra** | **Mục 1** (Xác thực & Sổ địa chỉ) · **Mục 2** (Giỏ hàng & Checkout Đa tầng) · **Mục 4** (CRUD Admin: Sản phẩm, Kho SKU, Hạng thẻ, Chiến dịch Voucher) |

## 📑 Mục lục

- [Phần I. Các điểm đã triển khai rất tốt](#phần-i-ghi-nhận-các-điểm-đã-triển-khai-rất-tốt)
- [Phần II. Chi tiết 6 điểm thiếu sót & lỗ hổng logic cần khắc phục](#phần-ii-chi-tiết-6-điểm-thiếu-sót--lỗ-hổng-logic-cần-khắc-phục)

---

## PHẦN I. GHI NHẬN CÁC ĐIỂM ĐÃ TRIỂN KHAI RẤT TỐT

Trước khi đi vào các điểm cần khắc phục, cần ghi nhận nhóm đã xây dựng nền tảng kiến trúc rất chắc chắn ở các phần sau:

### 1. 🔒 An toàn Giao dịch & Chống Race Condition (`SELECT ... FOR UPDATE`)

- Trong `api/checkout.php`: Đã khóa dòng biến thể (`bt_bien_the_sp`), thẻ tích điểm (`ttd_the_tich_diem`) và ví voucher (`vkh_vi_voucher_kh`) bên trong `PDO Transaction` trước khi trừ kho và tạo đơn hàng.
- Trong `services/admin/InventoryAdminService.php`: Đã khóa dòng SKU trước khi cộng tồn kho và tính lại giá vốn bình quân gia quyền.

### 2. 🛡️ Bảo mật Chống IDOR & SQL Injection

- Toàn bộ truy vấn trong `services/` và `api/` đều sử dụng `PDO Prepared Statements`.
- Trong `api/address.php` và `services/store/CustomerProfileService.php`: Mọi thao tác sửa/xóa/đặt mặc định địa chỉ đều kèm điều kiện `WHERE ma_dc = :ma_dc AND ma_nd = :ma_nd`.

### 3. 🧮 Công thức Thanh toán Đa tầng (Multi-tier Pricing) Chính xác

Đã tính chuẩn theo thứ tự sau:

```text
Tạm tính
  → Chiết khấu trực tiếp theo Hạng thẻ
  → Trừ Voucher (kiểm tra đơn tối thiểu, hạng yêu cầu, trần giảm tối đa)
  → Cộng phí vận chuyển 30.000đ
  → Tính điểm cộng dự kiến = ⌊ Tổng thanh toán / 10.000 ⌋
```

### 4. ✨ Trải nghiệm Quản trị (PRG Pattern & Ràng buộc toàn vẹn)

- Các trang Admin (`products.php`, `inventory.php`, `tiers.php`, `vouchers.php`) đều áp dụng mô hình **Post-Redirect-Get (PRG)** kèm Flash Toast, chống gửi trùng form khi F5.
- Đã kiểm tra khóa ngoại trước khi xóa sản phẩm/danh mục (nếu đã phát sinh đơn hàng hoặc phiếu nhập thì tự động chuyển sang ngừng kinh doanh `trang_thai = 0`).

---

## PHẦN II. CHI TIẾT 6 ĐIỂM THIẾU SÓT & LỖ HỔNG LOGIC CẦN KHẮC PHỤC

Dưới đây là 6 vấn đề phát hiện được khi rà soát chéo giữa mã nguồn thực tế và đặc tả trong `plan-sche`.

### 📊 Bảng tổng hợp nhanh

| # | Mục | Mức độ | Vấn đề | File chính |
| :-: | :-: | :-: | :--- | :--- |
| 1 | 2.1 | 🔴 Cao | Nút "+ Thêm vào giỏ" ở Trang chủ vẫn bị khóa nhãn "Đang phát triển" | `store/pages/home.php` |
| 2 | 2.2 | 🔴 Cao | Bộ chọn Voucher ở trang Thanh toán chưa lọc Voucher hết hạn / bị đóng sớm | `services/store/CartCheckoutService.php` |
| 3 | 1.1 & 2.2 | 🟠 Trung bình | Bất nhất logic Session giữa "Demo Guest (ID = 4)" và "Người dùng đã đăng nhập" | `config/db.php`, `api/*.php` |
| 4 | 2.2 & Admin Đơn hàng | 🟠 Trung bình | Modal Chi tiết Đơn hàng ở Admin chưa hiển thị SĐT và Địa chỉ giao hàng | `services/admin/OrderAdminService.php`, `admin/orders.php` |
| 5 | 1.1 & 4 | 🟠 Trung bình | Chưa tách biệt phân quyền giữa Quản trị viên (`ma_vt = 1`) và Nhân viên (`ma_vt = 2`) | `admin/auth_middleware.php`, `admin/tiers.php`, `admin/vouchers.php` |
| 6 | 4.1 | 🟢 Nhẹ | Chưa tự động tăng `luot_xem` & chưa chọn được `mau_sac_lien_ket` khi tải ảnh phụ | `services/store/ProductStoreService.php`, `admin/products.php` |

---

### 1. 🔴 [Mức độ: Cao — Mục 2.1] Nút "+ Thêm vào giỏ" tại Trang chủ (`store/pages/home.php`) vẫn đang bị khóa bằng nhãn "Đang phát triển"

#### 📍 Vị trí phát hiện

- File `store/pages/home.php` — Dòng 45 (khu vực *"Gợi ý riêng cho bạn"*) và Dòng 177 (khu vực *"Lưới sản phẩm chính"*).

#### 🔎 Hiện trạng & Nguyên nhân

- Tại trang Chi tiết sản phẩm (`store/pages/product_detail.php`), nhóm đã nối thành công nút Thêm vào giỏ với `window.cartAPI.add(selectedVariant.ma_bt, qty)`.
- Tuy nhiên, tại `store/pages/home.php`, cả 2 nút **"+ Thêm vào giỏ"** vẫn đang giữ thuộc tính tĩnh `data-dev-feature="Thêm nhanh sản phẩm vào giỏ hàng"`. Do đó, khi người dùng bấm vào nút này ở trang chủ, file `store/assets/store.js` sẽ chặn lại và hiện thông báo *"🚧 Tính năng hiện đang phát triển!"* thay vì thêm sản phẩm vào bảng `gh_gio_hang`.

#### 🛠️ Cách khắc phục cụ thể

1. Trong `store/pages/home.php`, lấy biến thể đầu tiên còn tồn kho (`so_luong_ton > 0`) từ mảng `$p['variants']` để gắn `data-ma-bt="<?= (int)$defaultVariant['ma_bt'] ?>"` lên nút **"+ Thêm vào giỏ"**.
2. Gỡ bỏ thuộc tính `data-dev-feature` trên nút này.
3. Bổ sung sự kiện click gọi `window.cartAPI.add(maBt, 1)` để thêm ngay 1 sản phẩm vào giỏ và tự động mở ngăn kéo giỏ hàng (`Cart Drawer`). Nếu sản phẩm đã hết sạch tồn kho ở mọi biến thể, vô hiệu hóa nút và đổi nhãn thành `"Hết hàng"`.

---

### 2. 🔴 [Mức độ: Cao — Mục 2.2] Bộ chọn Voucher ở trang Thanh toán (`CartCheckoutService.php`) chưa lọc các Voucher đã hết hạn hoặc bị Admin đóng sớm

#### 📍 Vị trí phát hiện

- File `services/store/CartCheckoutService.php` — Phương thức `getCustomerAvailableVouchers(int $maNd)` (Dòng 53 - 68).

#### 🔎 Hiện trạng & Nguyên nhân

Câu truy vấn lấy danh sách Voucher trong ví của khách hàng để hiển thị lên thẻ `<select id="voucherSelect">` ở trang `store/pages/checkout.php` hiện tại đang viết:

```sql
WHERE vkh.ma_nd = :ma_nd
  AND vkh.trang_thai_su_dung = 0
  AND pgg.trang_thai = 1
```

Truy vấn này **thiếu điều kiện kiểm tra thời gian hiệu lực** (`NOW() BETWEEN pgg.ngay_bd AND pgg.ngay_kt`).

> [!WARNING]
> **Hệ quả:** Khi Admin vào `admin/vouchers.php` và bấm nút **"Kết thúc sớm"** một chiến dịch Voucher (hệ thống cập nhật `ngay_kt = NOW()`), hoặc khi một Voucher trong ví khách hàng đã qua ngày `ngay_kt`, Voucher đó vẫn hiển thị bình thường trong dropdown ở trang Thanh toán. Khách hàng chọn được trên giao diện (JS vẫn trừ tiền ảo trên UI), nhưng đến khi bấm nút **"Đặt hàng ngay"** thì `api/checkout.php` mới báo lỗi *"Voucher đã hết hạn sử dụng"*, gây trải nghiệm khó chịu.

#### 🛠️ Cách khắc phục cụ thể

Cập nhật câu SQL trong `CartCheckoutService::getCustomerAvailableVouchers(int $maNd)` thành:

```sql
SELECT vkh.ma_vi_voucher, vkh.ma_code_voucher, vkh.ngay_doi,
       pgg.ten_chuong_trinh, pgg.loai_giam, pgg.gia_tri_giam,
       pgg.giam_toi_da, pgg.don_hang_toi_thieu, pgg.ngay_bd, pgg.ngay_kt
FROM vkh_vi_voucher_kh vkh
JOIN pgg_phieu_giam_gia pgg ON vkh.ma_pgg = pgg.ma_pgg
WHERE vkh.ma_nd = :ma_nd
  AND vkh.trang_thai_su_dung = 0
  AND pgg.trang_thai = 1
  AND NOW() BETWEEN pgg.ngay_bd AND pgg.ngay_kt
ORDER BY pgg.gia_tri_giam DESC
```

---

### 3. 🟠 [Mức độ: Trung bình — Mục 1.1 & 2.2] Bất nhất logic Session giữa chế độ "Demo Guest (ID = 4)" và "Người dùng đã đăng nhập"

#### 📍 Vị trí phát hiện

- File `config/db.php` (hàm `getCurrentUserId()` và `isUserLoggedIn()`, dòng 114 - 134).
- File `api/address.php` (dòng 18) so với `api/cart.php` (dòng 18) và `api/checkout.php` (dòng 22).

#### 🔎 Hiện trạng & Nguyên nhân

- Trong `config/db.php`, hàm `getCurrentUserId()` đang gán cứng: nếu chưa có `$_SESSION['current_user_id']` và chưa bấm Logout (`empty($_SESSION['explicit_logout'])`), hệ thống tự động gán `$_SESSION['current_user_id'] = 4` (tài khoản mẫu Neàng Ra).
- Ngay phía dưới, hàm `isUserLoggedIn()` kiểm tra:

  ```php
  return isset($_SESSION['current_user_id']) && (int)$_SESSION['current_user_id'] > 0;
  ```

> [!IMPORTANT]
> **Hệ quả 1:** Ngay lần đầu tiên mở trình duyệt, biến `$currentUserId = getCurrentUserId()` chạy trước làm `$_SESSION['current_user_id']` được gán bằng `4`, kéo theo `isUserLoggedIn()` luôn trả về `true` dù người dùng chưa hề đăng nhập!

**Hệ quả 2:** Khi người dùng bấm nút **Đăng xuất**, `$_SESSION['explicit_logout'] = true` và `current_user_id = 0`. Lúc này nếu họ bấm "Thêm vào giỏ" ở trang chi tiết sản phẩm, API trả về lỗi `401` nhưng giao diện chỉ hiện Toast báo lỗi chữ đỏ thay vì tự động bật Modal Đăng nhập (`window.openAuthModal('login')`).

#### 🛠️ Cách khắc phục cụ thể

1. Trong `config/db.php`: Đảm bảo khi tự động nạp tài khoản mẫu lần đầu (`ID = 4`), hệ thống đánh dấu rõ hoặc đồng bộ để không gây xung đột giữa `api/address.php`, `api/cart.php` và `api/checkout.php`.
2. Trong `store/pages/product_detail.php` và `store/pages/home.php`: Khi `window.cartAPI.add()` trả về `require_login: true` (khi khách đã chủ động bấm Đăng xuất), tự động gọi để mời khách đăng nhập lại mượt mà:

   ```js
   if (res.require_login && window.openAuthModal) window.openAuthModal('login');
   ```

---

### 4. 🟠 [Mức độ: Trung bình — Mục 2.2 & Admin Đơn hàng] Modal Chi tiết Đơn hàng ở Admin (`admin/orders.php`) chưa hiển thị SĐT và Địa chỉ giao hàng của khách

#### 📍 Vị trí phát hiện

- File `services/admin/OrderAdminService.php` (phương thức `getAllOrdersWithDetails()`) và `admin/orders.php` (Modal `#orderDetailModal`).

#### 🔎 Hiện trạng & Nguyên nhân

- Ở trang `store/pages/checkout.php`, khách hàng có chọn địa chỉ giao hàng (`ma_dc`) từ bảng `dc_dia_chi_kh` và `api/checkout.php` có kiểm tra địa chỉ đó hợp lệ.
- Tuy nhiên, trong `OrderAdminService.php`, câu truy vấn lấy danh sách đơn hàng mới chỉ lấy `nd.ho_ten`, `nd.email` mà chưa lấy `nd.sdt` và địa chỉ giao hàng mặc định từ `dc_dia_chi_kh`. Khi Admin hoặc Nhân viên mở Modal **"Chi tiết đơn hàng"** để chuẩn bị đóng gói giao hàng, họ không thấy số điện thoại người nhận hay địa chỉ nhận hàng ở đâu.

#### 🛠️ Cách khắc phục cụ thể

- Không cần thay đổi cấu trúc 16 bảng trong file SQL gốc (để giữ nguyên tính tương thích CSDL của cả nhóm).
- Chỉ cần bổ sung `LEFT JOIN dc_dia_chi_kh dc ON nd.ma_nd = dc.ma_nd AND dc.mac_dinh = 1` vào `OrderAdminService::getAllOrdersWithDetails()` để lấy `ten_nguoi_nhan`, `sdt_nguoi_nhan`, `dia_chi_cu_the` và hiển thị rõ ràng trong Modal Chi tiết đơn hàng tại `admin/orders.php`.

---

### 5. 🟠 [Mức độ: Trung bình — Mục 1.1 & Mục 4] Chưa tách biệt phân quyền thao tác giữa Quản trị viên (`ma_vt = 1`) và Nhân viên (`ma_vt = 2`)

#### 📍 Vị trí phát hiện

- File `admin/auth_middleware.php`, `admin/tiers.php`, `admin/vouchers.php`.

#### 🔎 Hiện trạng & Nguyên nhân

Theo thiết kế trong bảng `vt_vai_tro` (`SQL/file_mau-duLieu.sql`):

| Vai trò | `ma_vt` | Quyền hạn theo thiết kế |
| :--- | :-: | :--- |
| **Quản trị viên** | 1 | Toàn quyền cấu hình hệ thống, **hạng thành viên**, **khuyến mãi** và thống kê doanh thu. |
| **Nhân viên** | 2 | Quản lý sản phẩm, lập phiếu nhập kho, xác nhận và cập nhật trạng thái đơn hàng. |

Hiện tại, `admin/auth_middleware.php` cho phép cả `ma_vt = 1` và `ma_vt = 2` đi qua như nhau. Nhân viên (`staff_quyen`, `staff_thao`) khi đăng nhập vào Admin vẫn có thể tự ý sửa ngưỡng điểm lên hạng Kim Cương (`admin/tiers.php`) hoặc tạo/đóng chiến dịch phát hành Voucher (`admin/vouchers.php`).

#### 🛠️ Cách khắc phục cụ thể

- Thêm hàm `isSuperAdminRole()` (`$adminCurrentUser['ma_vt'] === 1`) trong `admin/auth_middleware.php`.
- Tại `admin/tiers.php` và `admin/vouchers.php`: Nếu là Nhân viên (`ma_vt = 2`), chỉ cho phép xem danh sách ở chế độ **Read-only (Chỉ xem)**; nếu Nhân viên gửi request `POST` để sửa hạng thẻ hoặc tạo/đóng chiến dịch Voucher, hệ thống chặn lại và báo Flash Toast:

  > *"Chỉ Quản trị viên cấp cao (Admin) mới có quyền thay đổi cấu hình Hạng thành viên và Chiến dịch Khuyến mãi!"*

---

### 6. 🟢 [Mức độ: Nhẹ — Mục 4.1] Chưa tự động tăng `luot_xem` khi khách xem chi tiết sản phẩm & Chưa chọn được `mau_sac_lien_ket` khi tải ảnh phụ

#### 📍 Vị trí phát hiện

- File `services/store/ProductStoreService.php` (phương thức `getProductById()`).
- File `admin/products.php` & `services/admin/ProductAdminService.php`.

#### 🔎 Hiện trạng & Nguyên nhân

1. Bảng `sp_san_pham` có cột `luot_xem` dùng để sắp xếp các sản phẩm "Hot / Gợi ý riêng cho bạn" (`ORDER BY sp.luot_xem DESC`), nhưng khi khách hàng truy cập `index.php?page=product&id=X`, hệ thống chưa thực hiện câu lệnh cộng thêm 1 lượt xem.
2. Bảng `ha_hinh_anh_sp` có cột `mau_sac_lien_ket` để liên kết từng bức ảnh với màu sắc của biến thể, nhưng trong Modal Thêm/Sửa sản phẩm ở `admin/products.php`, Admin mới chỉ tải lên được 1 ảnh chính (`anh_chinh`), chưa có khu vực thêm ảnh phụ gắn kèm tên màu sắc.

#### 🛠️ Cách khắc phục cụ thể

1. Thêm hàm `incrementProductView(int $maSp)` gọi ngay khi nạp trang chi tiết sản phẩm:

   ```sql
   UPDATE sp_san_pham SET luot_xem = luot_xem + 1 WHERE ma_sp = :id
   ```

2. Trong `admin/products.php` và `ProductAdminService.php`, bổ sung tính năng thêm/xóa nhanh ảnh bộ sưu tập (`ha_hinh_anh_sp`) kèm trường chọn `mau_sac_lien_ket` và `la_anh_chinh`.
