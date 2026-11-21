---
name: full-test
description: Chạy kiểm thử toàn diện dự án local (Build, Unit Test, Playwright E2E và soi UI bằng Browser Subagent).
---

# Quy trình Full-Test Dự án Local

Thực hiện tuần tự 4 pha sau. Nếu Pha 1 hoặc Pha 2 thất bại nghiêm trọng (không build được), dừng lại báo lỗi ngay trước khi chạy Pha 3.

## Pha 1: Build & Backend Test
1. Chạy lệnh build dự án (`dotnet build` hoặc `npm run build`).
2. Chạy toàn bộ bộ Unit Test hiện có (`dotnet test` hoặc `npm test`).
3. Ghi nhận danh sách file/hàm bị lỗi (nếu có).

## Pha 2: Chạy Regression Test (Playwright)
1. Kiểm tra xem server local đã chạy chưa; nếu chưa, khởi động server ở chế độ background.
2. Chạy lệnh `npx playwright test --reporter=list`.
3. Ghi nhận các test case fail kèm dòng lỗi cụ thể.

## Pha 3: Soi giao diện & luồng thực tế (Browser Subagent)
Chỉ dùng Browser Subagent để kiểm tra những chỗ Playwright chưa phủ tới hoặc các trang vừa được chỉnh sửa gần đây (kiểm tra qua `git status` / `git diff`):
1. Mở các trang bị ảnh hưởng trên `localhost`.
2. Kiểm tra trực quan layout (có bị vỡ khung, tràn chữ, đè nút không).
3. Kiểm tra tab Console (không có lỗi JS đỏ) và tab Network (không có API trả về 4xx/5xx).
4. Chụp ảnh màn hình nếu phát hiện lỗi giao diện.

## Pha 4: Xuất báo cáo tổng hợp
Trả về một bảng Markdown tóm tắt:
- Trạng thái từng pha (Pass / Fail).
- Danh sách lỗi cụ thể kèm đường dẫn file.
- Đề xuất cách sửa (chưa tự ý sửa code nếu người dùng chưa xác nhận).