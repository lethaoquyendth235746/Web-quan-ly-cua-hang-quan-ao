# Quy tắc kiểm thử tự động (Testing Rules)

1. **Sau khi sửa Backend / Logic:**
   - Bắt buộc chạy lệnh build (`dotnet build` hoặc `npm run build`) để đảm bảo không có lỗi biên dịch.
   - Chạy lại các unit test liên quan. Nếu có lỗi, tự sửa cho đến khi pass 100% mới được báo hoàn thành.

2. **Sau khi sửa Frontend / UI:**
   - Khởi động dev server local (nếu chưa chạy).
   - Dùng Browser Subagent mở đúng trang vừa sửa trên `localhost`, kiểm tra Console Errors và Network (không có request lỗi 4xx/5xx).

3. **Nguyên tắc báo cáo:**
   - Không bao giờ bỏ qua (skip) test hoặc xóa test cũ để ép pass.
   - Luôn đính kèm kết quả lệnh test thực tế khi trả lời.