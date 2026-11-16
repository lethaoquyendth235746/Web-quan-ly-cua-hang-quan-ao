# Skill: Strict Development Workflow & Guardrails
- **No Lazy Coding:** Không bao giờ viết comment kiểu `// ... phần code còn lại giữ nguyên` hoặc `// TODO: implement later` khi được yêu cầu viết code hoàn chỉnh cho một hàm/component.
- **Database Safety:** Trước khi thay đổi cấu trúc bảng trong thư mục `SQL/` hoặc các class/model dữ liệu, luôn kiểm tra sự ảnh hưởng đến các truy vấn liên quan (đặc biệt là luồng tích điểm và phân hạng thành viên).
- **Preserve Existing Logic:** Chỉ sửa đúng phạm vi file/chức năng được yêu cầu trong `Demo_UI/` hoặc `SQL/`. Không tự ý đổi tên biến, đổi cấu trúc thư mục hoặc xóa tính năng cũ khi chưa hỏi ý kiến.
- **Clean Code & Natural Vietnamese UI:** Tuân thủ nghiêm ngặt quy ước đặt tên nhất quán với cơ sở dữ liệu MySQL của dự án. Toàn bộ văn bản hiển thị trên giao diện (UI text) viết bằng tiếng Việt tự nhiên, hiện đại, chuẩn thương mại điện tử thời trang.

# Skill: Domain-Specific Context (Fashion E-Commerce & Loyalty System)
- **Color Palette & Vibe:** Phối màu hiện đại, thanh lịch chuẩn E-Commerce thời trang (Đen tuyền / Charcoal `#111827`, Trắng tinh khiết `#FFFFFF`, Xám nền nhạt `#F8FAFC`, kết hợp điểm nhấn Vàng Kim / Champagne `#D4AF37` hoặc Đồng `#B45309` dành riêng cho huy hiệu Hạng Thành Viên VIP / Loyalty Tier).
- **Typography:** Kết hợp font chữ thanh lịch cho các tiêu đề bộ sưu tập (Hero Headline, Lookbook) và font không chân hiện đại, dễ đọc (Sans-serif như Inter/Plus Jakarta Sans) cho thông tin sản phẩm, phân loại Size/Màu, bảng giá và số điểm tích lũy.
- **Core E-Commerce & Loyalty Features:**
  - Khu vực trưng bày Bộ sưu tập thời trang (Lookbook / Seasonal Collection), bộ lọc thuộc tính thông minh (Size, Màu sắc, Chất liệu, Khoảng giá).
  - Hệ thống Khách hàng thân thiết (Loyalty System): Hiển thị trực quan thanh tiến trình nâng hạng (Member -> Silver -> Gold -> Diamond), số điểm tích lũy dự kiến khi mua hàng, kho Voucher đổi điểm và ưu đãi chiết khấu theo hạng.
  - Phân hệ Quản trị (Admin UI): Quản lý tồn kho theo SKU biến thể (Size/Màu), quản lý chương trình khuyến mãi, voucher và lịch sử giao dịch điểm.

# Skill: Codebase Architecture Graph (Graphify)
- **Architecture First:** Trước khi trả lời câu hỏi về kiến trúc hoặc viết/sửa code liên quan đến nhiều trang/tầng, LUÔN đọc file `graphify-out/GRAPH_REPORT.md` (nếu có) để nắm toàn cảnh các node kết nối trong dự án.
- **Graph Navigation:** Kiểm tra cấu trúc trong `graphify-out/graph.json` để tra cứu mối quan hệ phụ thuộc giữa các module thay vì đọc mò từng file thô.

# Skill: Baoyu High-Craft UI/UX Design System (Anti-AI-Slop Rules)
- **Design Reference:** Khi thực hiện các tác vụ thiết kế hoặc tinh chỉnh giao diện (Frontend/UI trong `Demo_UI/`), hãy tham khảo quy chuẩn thẩm mỹ trong `.agent/baoyu-design/archive/baoyu-design` và `.agent/baoyu-design/skills/claude-design-system` (hoặc `dataviz` khi làm biểu đồ thống kê doanh thu/tồn kho Admin).
- **Frontend Adaptation:** Áp dụng trực tiếp các nguyên tắc bố cục, phân cấp thị giác (visual hierarchy), khoảng thở (whitespace), và chuyển động vi mô (micro-interactions) của `baoyu-design` vào các file `.html`, `.css`, `.js` của dự án.
- **Strict Anti-AI-Slop Rules:**
  1. Tuyệt đối không dùng các khung viền neon/gradient công nghệ sến sẩm, không lạm dụng icon/emoji ở đầu mỗi dòng text.
  2. Ưu tiên sự tinh tế của Typography, căn chỉnh lưới (grid alignment) sắc sảo, độ tương phản màu sắc thực tế (Editorial / Fashion E-commerce chuẩn thương mại).
  3. Mọi thành phần UI phải phục vụ trực tiếp cho thao tác mua sắm và tra cứu điểm thưởng của người dùng thực tế, loại bỏ hoàn toàn các dòng chữ giải thích kỹ thuật thừa thãi trên mặt tiền.

---
# HỆ THỐNG KỸ NĂNG GIAO DIỆN & CHUYỂN ĐỘNG TỔNG HỢP (4 SKILL PACKS)
Khi thực hiện bất kỳ tác vụ Frontend, UI/UX, HTML/CSS/JS nào trong dự án **Fashion Loyalty Web**, Agent BẮT BUỘC phải áp dụng đồng thời 4 bộ kỹ năng đã được cài đặt tại `.agent/skills/` dưới đây:

## 1. [Taste-Skill v2.5] Art Direction & Bố cục Khung nhìn (`.agent/skills/taste-skill`)
- **Định danh loại trang (`page kind: product / fashion shop`):** Hệ thống bán lẻ thời trang hiện đại tích hợp chăm sóc khách hàng thân thiết.
  + **CẤM dùng hiệu ứng công nghệ/gaming:** Tuyệt đối không dùng nền đen xanh công nghệ, viền neon, hay hiệu ứng WebGL/Shader rối mắt.
  + **Tập trung vào hình ảnh thời trang & trải nghiệm mua sắm:** Hình ảnh sản phẩm (Lookbook/Thumbnail) nổi bật, Typography phân cấp rõ ràng, bảng màu sạch sẽ tôn vinh trang phục.
- **Kỷ luật bố cục (`Equal Content Edges` & `Clean Grid`):**
  + Hai lề trái và phải (`padding-inline`) luôn cân xứng tuyệt đối, lưới sản phẩm (Product Grid) thẳng hàng về chiều cao thẻ và vị trí nút bấm.
- **Copy Lint (Ngôn từ thực tế):** Chỉ sử dụng ngôn ngữ thương mại thời trang và chăm sóc khách hàng tinh tế. Không bao giờ dùng từ ngữ sáo rỗng kiểu AI hay phơi bày thuật ngữ lập trình ra mặt tiền.

## 2. [Impeccable] Chống "AI Slop" & Tinh chỉnh 24 Chế độ (`.agent/skills/impeccable`)
Tự động thực thi các chuẩn `critique`, `audit`, `polish`, `quieter`, `distill`, `typeset`, `layout`, và `harden` trước khi hoàn tất bất kỳ file giao diện nào:
- **Bộ lọc 62 Quy tắc Chống AI Slop (`impeccable detect`):**
  + **CẤM lạm dụng Icon/Emoji:** Không gắn icon hoặc emoji ở đầu mỗi mục menu dropdown, tiêu đề phụ hay danh sách thông số. Chỉ dùng icon ở nơi thực sự giúp nhận diện thao tác (giỏ hàng, tìm kiếm, bộ lọc).
  + **CẤM phơi mã kỹ thuật (`distill`):** Tuyệt đối không hiển thị ID tự tăng trong CSDL, mã băm nội bộ hay mã trạng thái thô (`PENDING_PAYMENT`, `TIER_GOLD_01`) trực tiếp ra giao diện khách hàng.
  + **CẤM Thẻ lồng thẻ (Nested Cards trong Cards):** Không đóng khung viền chồng chéo nhiều lớp. Ưu tiên dùng khoảng trắng (`whitespace`), nền phân tách nhẹ (`#F8FAFC` vs `#FFFFFF`) và đường kẻ mảnh `1px solid/dashed`.
  + **Chống che khuất tương tác (`audit` & `harden`):** Không bao giờ để các nút nổi (Floating Action Button) đè lên thanh nút `THANH TOÁN` hoặc `THÊM VÀO GIỎ` cố định ở đáy màn hình (`bottom` của nút nổi phải `>= 96px`). Không bao giờ đặt chữ sáng màu trên nền sáng.

## 3. [Emil Kowalski Skills] Design Engineering, Motion & Stress-Test UI (`.agent/skills/emil-*`, `animate`, `choreograph`, `break-ui`)
Tuân thủ nghiêm ngặt quy tắc từ `.agent/skills/emil-ui-cheatsheet.md` và `.agent/

