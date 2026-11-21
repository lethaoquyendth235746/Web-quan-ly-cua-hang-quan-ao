# Skill: High-Converting Landing Page & Modern Web UI
- **Design System:** Thiết kế theo phong cách hiện đại (Bento Grid, Glassmorphism nhẹ, chữ có độ tương phản cao, khoảng trắng thoáng). Tránh tuyệt đối giao diện Bootstrap mặc định thô cứng.
- **Landing Page Anatomy:** Khi tạo trang chủ hoặc Landing Page, luôn đảm bảo cấu trúc chuẩn:
  1. Hero Section nổi bật (Headline rõ ràng, Sub-headline, 2 nút CTA chính/phụ, hình ảnh/mockup trực quan).
  2. Social Proof / Thống kê (Số liệu, cam kết dịch vụ, hoặc chương trình thành viên/khách hàng thân thiết).
  3. Bento Grid hoặc Feature Cards giới thiệu sản phẩm/tính năng.
  4. Testimonials & Call-to-Action (CTA) cuối trang.
- **Micro-interactions:** Luôn thêm hiệu ứng chuyển động mượt (`transition-all duration-300`, `hover:-translate-y-1`, `hover:shadow-lg`) cho các thẻ sản phẩm (Product Cards) và nút bấm.
- **Responsive:** Bắt buộc tương thích Mobile-first. Không để vỡ bố cục trên màn hình nhỏ.

# Skill: ASP.NET Core & Entity Framework Core Best Practices
- **Architecture:** Tách biệt rõ ràng giữa tầng giao diện (Razor Pages / MVC Views), xử lý điều hướng (PageModels / Controllers) và truy cập dữ liệu (DbContext / Services).
- **Async/Await:** Bắt buộc dùng bất đồng bộ (`async/await`, `ToListAsync()`, `FirstOrDefaultAsync()`) cho toàn bộ thao tác I/O và Database.
- **EF Core Optimization:**
  - Luôn dùng `.AsNoTracking()` cho các truy vấn chỉ đọc (hiển thị danh sách lên Landing Page, tìm kiếm, lọc dữ liệu) để tối ưu hiệu năng.
  - Tránh lỗi N+1 Query bằng cách sử dụng `.Include()` hoặc `.Select()` chiếu sang ViewModel/DTO.
- **Data Validation & Security:**
  - Sử dụng Data Annotations kết hợp kiểm tra `ModelState.IsValid` ở cả Client-side và Server-side.
  - Bảo vệ các form POST bằng Anti-Forgery Token.
- **Partial Views / View Components:** Tách các thành phần lặp lại trên Landing Page (Header, Footer, Product Card, Loyalty Banner) thành Partial Views hoặc View Components để dễ bảo trì.

# Skill: Strict Development Workflow & Guardrails
- **No Lazy Coding:** Không bao giờ viết comment kiểu `// ... phần code còn lại giữ nguyên` hoặc `// TODO: implement later` khi được yêu cầu viết code hoàn chỉnh cho một hàm/component.
- **Database Safety:** Trước khi thay đổi các class Entity trong `Models`, luôn kiểm tra sự ảnh hưởng đến `DbContext` và nhắc nhở tạo Migration mới nếu cần.
- **Preserve Existing Logic:** Chỉ sửa đúng phạm vi file/chức năng được yêu cầu. Không tự ý đổi tên biến, đổi cấu trúc thư mục hoặc xóa tính năng cũ khi chưa hỏi ý kiến.
- **Vietnamese Abbreviated Code & Natural Vietnamese UI:** Tuân thủ nghiêm ngặt quy ước đặt tên Tiếng Việt không dấu viết tắt chuẩn PascalCase/camelCase đã thiết kế cho 25 bảng CSDL và Entities (VD: SanPhamBanh, HopQuaTuChon, MaPhienKvl, DotGiaoHangDaDiem). Toàn bộ văn bản hiển thị trên giao diện (UI text) viết bằng tiếng Việt tự nhiên, sang trọng.

# Skill: Domain-Specific Context (Mooncake E-Commerce & Brand Landing Page)
- **Color Palette & Vibe:** Phối màu sang trọng, ấm cúng mang hơi hướng lễ hội Trung Thu hiện đại (Đỏ rượu vang / Crimson `#8B0000`, Vàng kim / Gold `#D4AF37`, Nền kem ấm `#FDFBF7` hoặc Xanh đêm rằm `#0F172A`).
- **Typography:** Kết hợp font chữ có chân (Serif như Playfair Display/Merriweather) cho các tiêu đề lớn (Hero Headline, Tên bộ sưu tập lễ hộp) để tạo sự cao cấp, và font không chân (Sans-serif như Inter/Plus Jakarta Sans) cho nội dung chi tiết, giá tiền.
- **Core E-Commerce & Landing Features:**
  - Khu vực trưng bày "Bộ sưu tập Lễ Hộp Thượng Hạng" (Gift Box Collections) theo dạng Bento Grid hoặc Carousel sang trọng.
  - Tính năng chọn nhân bánh / mix hộp quà tùy chỉnh hoặc bảng giá chiết khấu cho khách hàng doanh nghiệp / khách hàng thân thiết.
# Skill: Codebase Architecture Graph (Graphify)
- **Architecture First:** Trước khi trả lời câu hỏi về kiến trúc hoặc viết/sửa code liên quan đến nhiều tầng (Controller, Service, DbContext, ViewModels), LUÔN đọc file `graphify-out/GRAPH_REPORT.md` trước để nắm toàn cảnh các node kết nối trong dự án.
- **Graph Navigation:** Nếu cần tra cứu sâu hơn mối quan hệ phụ thuộc giữa các file C# và Razor Views, hãy kiểm tra cấu trúc trong `graphify-out/graph.json` thay vì đọc mò từng file thô. Lưu ý: Với cấu hình DI trong Program.cs và giao diện .cshtml, hãy đọc trực tiếp file gốc vì AST của Graphify bỏ qua top-level statements và Razor syntax.
# Skill: Baoyu High-Craft UI/UX Design System (Anti-AI-Slop Rules)
- **Design Reference:** Khi thực hiện các tác vụ thiết kế hoặc tinh chỉnh giao diện (Frontend/UI), hãy tham khảo quy chuẩn thẩm mỹ trong `.agent/baoyu-design/archive/baoyu-design` và `.agent/baoyu-design/skills/claude-design-system` (hoặc `dataviz` khi làm biểu đồ Admin).
- **ASP.NET Core Razor Adaptation:** Áp dụng trực tiếp các nguyên tắc bố cục, phân cấp thị giác (visual hierarchy), khoảng thở (whitespace), và chuyển động vi mô (micro-interactions) của `baoyu-design` vào các file `.cshtml` và `wwwroot/css/site.css` của dự án (KHÔNG tự ý tách thành dự án Node.js rời trừ khi được yêu cầu tạo Slide thuyết trình bằng `claude-slides`).
- **Strict Anti-AI-Slop Rules:**
  1. Tuyệt đối không dùng các khung viền neon/gradient công nghệ sến sẩm, không lạm dụng icon/emoji ở đầu mỗi dòng text.
  2. Ưu tiên sự tinh tế của Typography, căn chỉnh lưới (grid alignment) sắc sảo, độ tương phản màu sắc thực tế (Editorial / E-commerce chuẩn thương mại).
  3. Mọi thành phần UI phải phục vụ trực tiếp cho thao tác của người dùng thực tế, loại bỏ hoàn toàn các dòng chữ giải thích kỹ thuật thừa thãi trên mặt tiền.

  ---
# HỆ THỐNG KỸ NĂNG GIAO DIỆN & CHUYỂN ĐỘNG TỔNG HỢP (4 SKILL PACKS)
Khi thực hiện bất kỳ tác vụ Frontend, UI/UX, Razor Views (`.cshtml`), CSS (`site.css`) hoặc JavaScript (`site.js`) nào trong dự án **Nguyệt Cát**, Agent BẮT BUỘC phải áp dụng đồng thời 4 bộ kỹ năng đã được cài đặt tại `.agent/skills/` dưới đây:

## 1. [Taste-Skill v2.5] Art Direction & Bố cục Khung nhìn (`.agent/skills/taste-skill`)
- **Định danh loại trang (`page kind: product / shop`):** Nguyệt Cát là thương hiệu quà biếu Trung Thu truyền thống cao cấp.
  + **CẤM dùng hiệu ứng công nghệ/gaming:** Tuyệt đối không dùng nền đen xanh công nghệ, viền neon, hay hiệu ứng WebGL/Shader rối mắt.
  + **Tập trung vào chất liệu thực tế:** Hình ảnh sản phẩm sắc nét, Typography phân cấp rõ ràng, bảng màu phẳng ấm áp (Đỏ Sơn Mài `#7A1215`, Kem Giấy Dó `#FDF8EE`, Trắng Ngà `#FFFFFF`, Vàng Đồng Cổ `#C89B3C`, Nâu Trầm `#2C1810`).
- **Kỷ luật bố cục (`One Section Per Screen` & `Equal Content Edges`):**
  + Trên Desktop (`>= 992px`), mỗi phân khu trên Trang chủ phải nằm gọn khít trong đúng 1 khung nhìn (`calc(100vh - 68px)`), hai lề trái và phải (`padding-inline`) luôn cân xứng tuyệt đối.
  + Không bao giờ để nội dung của tầng trên tràn hoặc lòi đuôi sang tầng dưới.
- **Copy Lint (Ngôn từ thực tế):** Chỉ sử dụng ngôn ngữ thương mại và thưởng trà tinh tế. Không bao giờ dùng từ ngữ sáo rỗng kiểu AI hay phơi bày thuật ngữ lập trình ra mặt tiền.

## 2. [Impeccable] Chống "AI Slop" & Tinh chỉnh 24 Chế độ (`.agent/skills/impeccable`)
Tự động thực thi các chuẩn `critique`, `audit`, `polish`, `quieter`, `distill`, `typeset`, `layout`, và `harden` trước khi hoàn tất bất kỳ file giao diện nào:
- **Bộ lọc 62 Quy tắc Chống AI Slop (`impeccable detect`):**
  + **CẤM lạm dụng Icon/Emoji:** Không gắn icon hoặc emoji ở đầu mỗi mục menu dropdown, tiêu đề phụ hay danh sách thông số. Chỉ dùng icon ở nơi thực sự giúp nhận diện thao tác.
  + **CẤM phơi mã kỹ thuật (`distill`):** Tuyệt đối không hiển thị mã phiên `KVL-...`, mã băm `#VH1_...`, mã trạng thái `CHO_DONG_GOI`, `Mã Token: QR-...`, hay số lượt mở thiệp trên giao diện khách hàng.
  + **CẤM Thẻ lồng thẻ (Nested Cards trong Cards):** Không đóng khung viền chồng chéo nhiều lớp. Ưu tiên dùng khoảng trắng (`whitespace`), nền phân tách nhẹ (`#F7F7F7` vs `#FFFFFF`) và đường kẻ mảnh `1px dashed/solid` (như bố cục 2 cột trong Giỏ hàng và trang Thanh toán Shondo).
  + **Chống che khuất tương tác (`audit` & `harden`):** Không bao giờ để các nút nổi (Floating Action Button như nút Trợ lý Mặt Trăng) đè lên thanh nút `ĐẶT HÀNG` cố định ở đáy màn hình (`bottom` của nút nổi phải `>= 96px`). Không bao giờ đặt chữ sáng màu trên nền sáng.

## 3. [Emil Kowalski Skills] Design Engineering, Motion & Stress-Test UI (`.agent/skills/emil-*`, `animate`, `choreograph`, `break-ui`)
Tuân thủ nghiêm ngặt quy tắc từ `.agent/skills/emil-ui-cheatsheet.md` và `.agent/skills/emil-performance-cheatsheet.md`:
- **Đường cong gia tốc chuẩn (`animate` & `emil-design-eng`):**
  + Mọi thành phần xuất hiện vào màn hình (Dropdown menu xổ xuống, Modal, Offcanvas, chuyển tầng Fullpage) BẮT BUỘC dùng đường cong giảm tốc `ease-out`: `cubic-bezier(0.16, 1, 0.3, 1)` hoặc `cubic-bezier(0.22, 1, 0.36, 1)`.
  + Tuyệt đối KHÔNG dùng `ease-in` hoặc `linear` cho chuyển động tương tác UI.
  + Thời gian phản hồi micro-interaction (hover, nút bấm, dropdown): `180ms - 260ms`. Thời gian chuyển cảnh toàn màn hình (Fullpage Slide): `750ms - 850ms`.
- **Hiệu năng 60fps (`animation-performance` & `remove-animations`):**
  + Chỉ animate 2 thuộc tính chạy trên GPU Compositor: `transform` (`translate3d`, `scale`) và `opacity`. Tuyệt đối KHÔNG dùng `transition: all` trên các phần tử bố cục lớn.
  + Loại bỏ mọi hiệu ứng nhấp nháy vô nghĩa gây mỏi mắt (`remove-animations`).
- **Kiểm thử Dữ liệu Khắc nghiệt (`break-ui`):**
  + Mọi thành phần UI phải chịu được dữ liệu dài thực tế: Tên bánh dài 60–80 ký tự (như *"Thập Cẩm Bào Ngư Thượng Hạng (2 Trứng)"*), địa chỉ dài, số tiền hàng chục triệu đồng.
  + Bắt buộc dùng `min-width: 0; flex: 1;` cho khối chữ và `flex-shrink: 0; white-space: nowrap;` cho các nút thao tác (`+ Chọn`, `+ Mua lẻ`, `Xếp vào hộp`, `Áp dụng`) để nút KHÔNG BAO GIỜ bị đẩy văng ra khỏi khung thẻ!

## 4. [GSAP Skills] Hoạt ảnh Chuyên sâu & Điều phối Timeline (`.agent/skills/gsap-skills`)
Khi sử dụng GSAP hoặc các chuỗi chuyển động phức tạp (`choreograph` / Thiệp QR 3D / Chuyển cảnh):
- Luôn ưu tiên `transform: translate3d` (`x`, `y`, `xPercent`, `yPercent`, `autoAlpha`) thay vì thay đổi `top`, `left`, `width`, `height` gây reflow DOM.
- Điều phối thứ tự xuất hiện (`stagger: 0.06` đến `0.08`) có nhịp điệu: tiêu đề dẫn hướng trước, nội dung chính theo sau, nút hành động chốt cuối.
- Quản lý trạng thái khóa chuyển cảnh chặt chẽ (`dangChuyenCanh` / `isAnimating`) để khi người dùng lăn chuột hoặc click liên tục không bị chồng chéo timeline.

## 5. [Unslop Skill] Chống AI Phá Văn Phong & Định Dạng Giao Diện (`.agent/skills/unslop`)
- Tham khảo `.agent/skills/unslop/SKILL.md` và `references/taboo-phrases.md`:
  1. **Preserve Existing UI & Copy (Byte-Exact Guard):** Khi chỉnh sửa lỗi kỹ thuật hoặc bố cục, TUYỆT ĐỐI giữ nguyên cấu trúc thiết kế và nội dung đang tốt, không tự ý viết lại câu chữ hay thay đổi bảng màu nếu không được yêu cầu.
  2. **No Decorative Emoji Headers & No Chatbot Residue:** Cấm chèn emoji trang trí thừa ở đầu tiêu đề; cấm dùng văn phong sáo rỗng kiểu AI ("Tuyệt tác...", "Khám phá ngay...", câu khẩu hiệu 2 nhịp, hoặc lạm dụng dấu `—`).
  3. **Magnitude & Fact Preservation:** Giữ nguyên chính xác 100% con số, đơn vị tiền tệ (`đ`), mã đơn hàng, thông số Kcal/GI từ CSDL.