# AGENTS.md: Quy tắc bắt buộc cho AI và thành viên dự án SmartCoffee

Đọc hết file này trước khi làm bất cứ việc gì. Áp dụng cho mọi công cụ AI (Codex, Claude, Copilot...) và cho thành viên nhóm.

SmartCoffee là phần mềm quản trị và POS cho một quán cà phê. Hùng là PM.

## 0. Tài liệu liên quan (bắt buộc đọc đúng lúc)

| Khi nào | Đọc |
|---|---|
| Task liên quan **nghiệp vụ** | [docs/business-rules.md](docs/business-rules.md) |
| Task liên quan **API, database hoặc tích hợp frontend/backend** | [docs/api-contract.md](docs/api-contract.md) |

- Hai tài liệu trên là nguồn duy nhất cho nghiệp vụ và hợp đồng. File này **không lặp lại** nội dung của chúng.
- Tài liệu được dẫn chiếu **không tồn tại**: báo cáo, không tự suy diễn nội dung.
- Tài liệu và code hiện tại **mâu thuẫn**: không tự kết luận bên nào đúng. Ghi rõ mâu thuẫn và hỏi PM.

### Ba loại nội dung, không được trộn lẫn

| Loại | Ý nghĩa | Cách dùng |
|---|---|---|
| **Quyết định đã duyệt** | PM đã chốt, có căn cứ trong nhật ký quyết định của [business-rules.md](docs/business-rules.md) | Phải tuân theo |
| **Đề xuất** (kỹ thuật hoặc nghiệp vụ) | Do AI hoặc thành viên đưa ra, PM chưa duyệt | Chưa phải quy tắc nghiệp vụ |
| **Hiện trạng triển khai** | Những gì đang có trong SQL, code, bản nháp, mô tả task | Chỉ cho biết đang có gì, **không phải căn cứ phê duyệt** |

**Không được coi một điều là "đã chốt" chỉ vì nó có trong SQL, code, `docs/04-api.md`, mô tả task hay báo cáo.** Task phụ thuộc vào nội dung "Chờ PM duyệt" hoặc câu hỏi mở thì dừng và hỏi PM.

## 1. Thứ tự ưu tiên chỉ dẫn

1. Chỉ thị trực tiếp của PM trong phiên làm việc hiện tại.
2. File này.
3. Phần **đã chốt** trong [docs/business-rules.md](docs/business-rules.md) và phần **đã phê duyệt** trong [docs/api-contract.md](docs/api-contract.md).
4. Task Jira (mã `SC-n`).
5. Hiện trạng triển khai: `database/smartcoffee_pos.sql`, code, báo cáo, bản nháp. Chỉ để tham khảo.

- Chỉ dẫn cấp thấp không được vượt qua chỉ dẫn cấp cao hơn.
- Hai nguồn mâu thuẫn mà thứ tự trên không giải được: **dừng, nêu rõ mâu thuẫn, hỏi PM**. Không tự chọn.

## 2. Cấu trúc dự án và phạm vi trách nhiệm

```text
backend/    API PHP (public, config, routes, app/Core, Controllers, Models, Middleware)
frontend/   login, phuc-vu, pha-che, thu-ngan, quan-ly, shared
database/   Script MySQL
docs/       Tài liệu (báo cáo, business-rules.md, api-contract.md)
old-version/  Bản cũ, chỉ để tham khảo
```

| Thư mục | Phụ trách |
|---|---|
| `backend/`, `database/` | Hùng |
| `frontend/login`, `frontend/phuc-vu`, `frontend/thu-ngan` | Tuấn Anh |
| `frontend/pha-che`, `frontend/quan-ly`, `frontend/shared` | Huy |

**Bảng phân công trên đang ở trạng thái "dự kiến" cho tới khi PM xác nhận.** Không tự ý thay đổi phân công.

- Chỉ sửa trong thư mục và phạm vi của task được giao. Không sửa file ngoài phạm vi task.
- Cần sửa ngoài phạm vi: báo chủ thư mục (comment Jira), không tự sửa.

### Bảo vệ `old-version/`

- **Không sửa, không xóa, không đổi tên, không di chuyển** bất cứ thứ gì trong `old-version/`.
- Không dùng code hay tài nguyên trong đó khi chưa hỏi PM. Chỉ đọc để tham khảo.

## 3. Phân quyền thay đổi

| Thay đổi | Ai được phê duyệt |
|---|---|
| Nghiệp vụ ([business-rules.md](docs/business-rules.md)) | Chỉ PM |
| Hợp đồng API ([api-contract.md](docs/api-contract.md)) | Chỉ PM |
| Database schema (`database/`) | Chỉ PM |
| Phân công thư mục | Chỉ PM |

- Mọi thành viên và AI được **phát hiện vấn đề và đề xuất phương án**. Không được tự ý thay đổi nội dung đã duyệt, và không được tự chốt nội dung đang chờ duyệt.
- Quy trình đề xuất thay đổi API và schema: xem [docs/api-contract.md](docs/api-contract.md), mục 5.
- Chưa có xác nhận rõ ràng của PM thì coi như chưa được phép.

### Mức độ tự chủ của AI

- **Được tự xử lý:** thay đổi nhỏ, an toàn, nằm trong phạm vi task.
- **Phải dừng và xin phê duyệt:** thay đổi nghiệp vụ, API, database schema, kiến trúc hoặc thành phần bảo mật quan trọng.
- Khi xin phê duyệt: nêu vấn đề, **tối đa 2 phương án** và khuyến nghị.
- Yêu cầu nằm ngoài danh sách tính năng (xem mục "Ngoài phạm vi" trong [business-rules.md](docs/business-rules.md)): ghi lại và hỏi PM, không làm trước.

## 4. Quy tắc kỹ thuật

Nhãn nguồn: **[PM]** = PM đã quyết định; **[BC]** = có trong yêu cầu phi chức năng của báo cáo nhóm (mục 2.2), PM chưa duyệt riêng; **[ĐX]** = đề xuất kỹ thuật của AI, **chờ PM duyệt**. Quy tắc [BC] và [ĐX] là mặc định làm việc cho tới khi PM quyết định khác; chúng không phải quyết định nghiệp vụ.

**Kiến trúc**
- Backend PHP trả JSON qua API, frontend ở thư mục riêng. [PM]
- Đăng nhập bằng session PHP, không dùng JWT. [PM]
- PHP theo MVC, MySQL/MariaDB trên XAMPP. [BC]

**Backend**
- Truy vấn dùng PDO prepared statements. Cấm ghép chuỗi SQL từ dữ liệu người dùng. [BC]
- Mật khẩu băm bằng `password_hash` / `password_verify`. [BC]
- Kiểm tra quyền theo vai trò ở server cho mọi chức năng, không tin frontend. [BC]
- Mỗi endpoint kiểm tra đủ: đăng nhập, vai trò, trạng thái hợp lệ, dữ liệu đầu vào. [ĐX]
- Tiền lưu bằng số nguyên VND. [BC] Tiền do server tính, frontend không gửi tổng tiền lên. [ĐX]
- Thanh toán và đóng đơn chạy trong một transaction; giá món lưu tại thời điểm gọi. [BC] Tạo hóa đơn nằm trong cùng transaction; đổi trạng thái đơn chạy trong transaction. [ĐX]
- Chống CSRF với request thay đổi dữ liệu. [BC] Đổi `session_id` sau khi đăng nhập. [ĐX]

**Frontend**
- Dùng component trong `frontend/shared/`. Không tự vẽ lại nút, card, màu theo kiểu riêng. [ĐX, đã ghi trong SC-2 và SC-4]
- Nút cao tối thiểu 44 px. [BC]
- Mỗi màn có trạng thái đang tải, trống, lỗi. [ĐX, đã ghi trong SC-4]
- Ẩn nút theo vai trò chỉ để dễ dùng, không thay thế kiểm tra quyền ở server. [ĐX]
- Chỉ dùng endpoint đã được đặc tả trong [docs/api-contract.md](docs/api-contract.md). Không tự định nghĩa endpoint. [PM]

**Toàn dự án**
- **Dependency:** được dùng dependency đã có trong dự án. Không tự thêm, nâng cấp hoặc thay thế dependency hay framework khi chưa được phép. Đề xuất dependency mới phải nêu lý do, rủi ro và phương án không dùng nó. [PM]
- **Ngôn ngữ:** tên biến, hàm, class, API, code identifier bằng tiếng Anh. Nội dung giao diện và tài liệu nội bộ bằng tiếng Việt. [PM]
- Kiểu đặt tên: `snake_case` cho PHP và database, `camelCase` cho JavaScript. [ĐX]

## 5. Bảo mật

- Không đưa mật khẩu, khóa, token, file `.env` vào git. [ĐX]
- Mật khẩu mẫu `CHUA_MA_HOA` trong SQL chỉ dùng khi phát triển, không dùng ở môi trường thật. [ĐX]
- Không ghi mật khẩu hay token vào log. [ĐX]
- Mọi dữ liệu hiển thị phải được escape. [ĐX]
- Thay đổi thành phần bảo mật quan trọng (đăng nhập, phân quyền, session): phải xin PM phê duyệt. [PM]

## 6. Kiểm thử và Definition of Done

- Mỗi endpoint có tối thiểu 3 ca kiểm thử: đúng, sai quyền, sai trạng thái. [ĐX]
- Phải **chạy thật** trước khi báo xong. Chưa chạy thì nói rõ là chưa chạy, không ghi "xong".
- Không bịa kết quả kiểm thử. Lỗi còn lại thì báo thẳng.
- Một task chỉ "xong" khi:
  - đúng phạm vi task;
  - đã kiểm thử như trên;
  - không vi phạm phần đã chốt của [business-rules.md](docs/business-rules.md) và phần đã phê duyệt của [api-contract.md](docs/api-contract.md);
  - không tự chốt nội dung đang chờ PM duyệt;
  - đã có báo cáo theo mục 8.

## 7. Git

- Một task Jira một nhánh, đặt tên theo mã, ví dụ `SC-4-wireframe-pos`. [ĐX]
- Commit nhỏ. Dùng **Conventional Commits**: prefix tiếng Anh (`feat`, `fix`, `docs`, `refactor`, `test`, `chore`), nội dung mô tả tiếng Việt. Ví dụ: `docs: tách quy tắc nghiệp vụ khỏi AGENTS.md`. [PM]
- **Không tự commit, push, tạo pull request hoặc merge** khi chưa được PM yêu cầu hoặc cho phép rõ ràng. [PM]
- **Cấm** `git push --force`, `git reset --hard` và mọi thao tác phá hủy lịch sử hoặc làm mất thay đổi chưa lưu (rebase nhánh dùng chung, xóa nhánh của người khác, xóa hoặc đổi tên hàng loạt). [PM]
- Chỉ commit file thuộc phạm vi task. [PM]

## 8. Khi không chắc chắn và báo cáo kết quả

**Khi không chắc chắn**
1. Dừng việc đang làm.
2. Nêu điều chưa rõ hoặc mâu thuẫn, **tối đa 2 phương án** kèm khuyến nghị.
3. Chờ PM quyết. Không tự chọn rồi làm tiếp.

Việc có thể hoàn tác thì được làm tiếp phần không phụ thuộc vào câu hỏi. Việc khó hoàn tác (xóa dữ liệu, đổi cấu trúc DB, push) thì luôn hỏi trước.

**Báo cáo sau mỗi task** gồm:
- Các file đã tạo, sửa hoặc di chuyển nội dung.
- Những thay đổi chính.
- Các bước kiểm tra đã thực hiện và kết quả.
- Rủi ro và vấn đề chưa giải quyết.
- Nội dung cần PM phê duyệt.

Tách rõ: điều **đã xác nhận**, điều **suy luận**, điều **chưa kiểm tra**.
