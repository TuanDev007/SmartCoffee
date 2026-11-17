# SmartCoffee: Quy tắc nghiệp vụ

**Chỉ Hùng (PM) được chốt và thay đổi nghiệp vụ.** Tài liệu này tách ba loại nội dung, không được trộn lẫn:

| Loại | Nằm ở | Được dùng thế nào |
|---|---|---|
| **Đã chốt**: có quyết định của PM, có căn cứ | Mục 1, 2, 6 | Là quy tắc. Phải tuân theo |
| **Chờ PM duyệt**: chỉ có trong SQL, mô tả task do AI soạn, báo cáo nhóm hoặc đề xuất | Mục 3 | **Chưa phải quy tắc.** Là hiện trạng hoặc đề xuất |
| **Câu hỏi mở**: chưa có phương án nào được chọn | Mục 4 | Không tự chọn |

Task phụ thuộc vào nội dung ở mục 3 hoặc mục 4 thì **dừng và hỏi PM**, không tự chốt theo SQL, code hay tài liệu nháp.

**SQL, code, `docs/04-api.md`, mô tả task Jira và báo cáo nhóm không phải nguồn phê duyệt nghiệp vụ** (quyết định D-23). Căn cứ của từng quyết định ghi ở nhật ký quyết định, mục 7.

**Ngày 16/11/2026 PM đổi luồng bán hàng sang "thanh toán trước, pha sau"** (D-25 đến D-46). Nhiều quyết định trước đó bị thay thế, xem cột Trạng thái ở mục 7. SQL và wireframe POS v1 **chưa theo luồng mới** (mục 5).

Bản đặc tả chức năng đầy đủ và tiến độ: [dac-ta-chuc-nang-va-tien-do.md](dac-ta-chuc-nang-va-tien-do.md).

## 1. Phạm vi hệ thống (đã chốt)

- Phần mềm quản trị và POS cho **một quán** cà phê (D-01).
- Bán tại bàn (phục vụ dùng điện thoại) và tại quầy (thu ngân dùng màn POS) (D-25, D-26).
- Kiến trúc: backend trả JSON qua API, frontend ở thư mục riêng (D-09). Đây là quyết định kỹ thuật, ghi ở đây để biết phạm vi.

## 2. Quy tắc đã chốt

### 2.1 Đăng nhập và vai trò
- Đăng nhập bằng **session PHP**, không dùng JWT (D-02).
- Quyền theo vai trò **cố định trong code**, không cấu hình động (D-03).
- Các tài khoản vận hành: phục vụ, pha chế, thu ngân, manager (D-14). **Không có vai trò "chủ quán" riêng**; manager là cấp duyệt cao nhất trong vận hành (D-35).
- **Admin chỉ quản lý hệ thống**, không tham gia bán hàng (D-04).
- **Manager không có chức năng của phục vụ và thu ngân**: không bán hàng, không thu tiền (D-44).

### 2.2 Luồng bán hàng: thanh toán trước, pha sau
- **Phục vụ (tại bàn):** chọn bàn → chọn món → tùy chỉnh món (ngọt, đắng, đá) → **thanh toán** → hệ thống tự gửi các món đã thanh toán vào quầy pha chế. **Không có bước "Gửi món"**, không có đơn nháp gửi quầy (D-25).
- **Thu ngân (tại quầy):** màn chính là POS bán hàng, một bên là sản phẩm, một bên là thông tin đơn hàng. Thu ngân chọn món, thanh toán, sau đó hệ thống gửi món vào quầy pha chế (D-26).
- **Không có màn "Mở đơn" trung gian**: chạm bàn là vào chọn món (D-46).
- **Mỗi lần gọi là một đơn mới, trả tiền riêng.** Gọi thêm là tạo đơn mới (D-28).
- **Mã đơn do hệ thống tự sinh.** Khách mang đi nhận món theo mã đơn. Không có màn "mã nhận món" (D-11).
- **Thu ngân bán được đơn mang đi;** đơn mang đi không gắn bàn (D-55). Phục vụ không bán mang đi (D-54).
- **Thu ngân bán mang đi, phục vụ bán tại bàn và trả bàn** (D-61).
- **Tùy chỉnh món:** độ ngọt và độ đắng có 3 mức Ít / Bình thường / Nhiều; đá có 4 mức Không đá / Ít / Bình thường / Nhiều (D-56).
- **Món có hai trạng thái ngoài "đang bán":** Tạm hết (vẫn hiện, không gọi được) và Ngừng bán (ẩn khỏi thực đơn) (D-58).
- Quầy pha chế bấm **Xong** ngay trên thẻ món, không qua màn chi tiết (D-12).

### 2.3 Thanh toán và giảm giá
- Phương thức thanh toán: **tiền mặt** và **chuyển khoản**. **Chưa phát triển thanh toán thẻ** (D-39).
- **Mỗi đơn chỉ một phương thức.** Không trả nhiều phương thức trong một đơn (D-40).
- **Khoản hoàn hoặc thu thêm ghi theo cách thực tế đưa hoặc nhận tiền** (tiền mặt hoặc chuyển khoản), không bắt buộc giống phương thức của đơn (D-62).
- **Phục vụ được thu tiền tại bàn**, thu ngân thu tại quầy (D-25, D-26, D-33).
- **Giảm giá chỉ qua voucher do manager tạo trong hệ thống.** Thu ngân (đơn tại quầy) và phục vụ (đơn tại bàn) nhập voucher cho đơn. Không có giảm giá nhập tay (D-43).
- **Voucher do manager tự cấu hình** theo cách tính của quán: giảm theo phần trăm hoặc số tiền, có hạn dùng, số lần dùng (D-48).
- **Mỗi đơn tối đa một voucher.** Voucher phần trăm chưa có trần số tiền giảm; chưa có điều kiện giá trị đơn tối thiểu (D-57).

### 2.4 Bàn
- **Không hỏi và không quản lý số lượng khách** (D-10).
- Sơ đồ bàn chỉ có **Trống / Có khách**, không có "Chờ thanh toán" (D-17).
- Bàn chuyển sang **Có khách khi đơn được thanh toán xong**, và về **Trống khi nhân viên phục vụ bấm "Trả bàn"** (D-31, D-41, D-45).
- **Chuyển bàn:** đơn hàng được chuyển sang bàn mới, bàn cũ thành Trống. **Bàn đích không bắt buộc đang trống**: được chuyển vào bàn đang có khách (gộp bàn) (D-34, D-42).

### 2.5 Hủy món, đổi món, hủy đơn sau khi đã thanh toán
- **Chỉ manager** thực hiện hủy món, đổi món và hủy đơn. **Bắt buộc nhập lý do** (D-06, D-29, D-35).
- **Hủy món:** manager hủy món đã thanh toán, kể cả món **đã mang ra**, kèm **hoàn tiền tại chỗ** (D-29, D-30).
- **Đổi món:** sửa trực tiếp trên đơn đó: bỏ món cũ, thêm món mới, **thanh toán phần chênh lệch** (thu thêm hoặc hoàn lại), đơn được cập nhật trên hệ thống (D-29, D-36).
- **Hủy cả đơn** sau khi đã thanh toán: vẫn có, chỉ manager, bắt buộc lý do. Không cần cấp duyệt thứ hai (D-35).
- Hệ thống **vẫn ghi nhận món đã hủy hoặc đã đổi**, kèm nhãn **"Hủy"** hoặc **"Đổi món"** và **lý do** (D-29).

### 2.6 Ca làm và kết ca của thu ngân
- **Mỗi ca chỉ có một thu ngân.** **Thu ngân tự mở ca; mỗi lúc chỉ có một ca mở** (D-37, D-47).
- **Chưa có ca mở thì phục vụ không chọn món, không bán được** (D-38).
- **Tiền phục vụ thu tại bàn thuộc ca đang làm.** Hệ thống lưu rõ đơn nào do phục vụ thu (kèm mã nhân viên phục vụ) và đơn nào do thu ngân thu (D-33).
- **Tiền hoàn của đơn thuộc ca nào thì tính vào ca đó** (D-49).
- **Hủy cả đơn thì hoàn 100% tiền của đơn** (D-50).
- **Hủy hoặc đổi một món trong đơn có voucher:** hoàn (hoặc tính chênh lệch) theo **giá món sau giảm** (D-60).
- **Gộp bàn không gộp đơn:** các đơn vẫn là đơn riêng, chỉ chuyển về bàn đích (D-53).
- **Phục vụ không bán đơn mang đi** (D-54).
- **Trả bàn không có điều kiện:** phục vụ bấm khi dọn bàn (D-51).
- **Hủy đơn, hoàn tiền chỉ thực hiện trong ca của đơn đó**, không để sang ca sau. Trường hợp phát sinh sau khi ca đã kết do team vận hành hoặc chủ quán xử lý riêng, ngoài hệ thống (D-52).
- Thu ngân có màn **tổng kết ca** để kết ca (D-32). Nội dung:
  - danh sách hóa đơn trong ca;
  - bộ lọc theo ngày, chỉ hiện các ca mà chính tài khoản này làm thu ngân;
  - tiền mặt đầu ca và tiền mặt cuối ca;
  - tiền chuyển khoản, được hệ thống ghi nhận vào ca làm.

### 2.7 Phạm vi bản 1
- **Bản 1 không làm dashboard hay báo cáo cho manager.** Chỉ có tổng kết ca của thu ngân (D-59).

### 2.8 Giao diện (ảnh hưởng nghiệp vụ)
- Thẻ bàn không hiện số khách, không có màn hỏi số khách (D-10).

## 3. Nội dung CHỜ PM DUYỆT (hiện trạng hoặc đề xuất, chưa phải quy tắc)

Các điểm dưới đây đang có trong SQL, mô tả task hoặc báo cáo nhóm, chưa có quyết định riêng của PM. Ký hiệu nguồn: `SQL` = `database/smartcoffee_pos.sql` (bản cũ, đã xóa khỏi `main` ngày 17/11, còn trong lịch sử git); `SC-4` = mô tả task Jira SC-4; `BC` = báo cáo `docs/Bao-cao-SmartCoffee-Nhom-2.docx`. SQL, SC-4 và báo cáo được viết cho luồng cũ.

- P-01. Quyền chi tiết còn lại: pha chế xem hàng chờ và đổi trạng thái món; phục vụ xác nhận "Đã mang ra"; manager quản lý món, bàn và xem báo cáo (SQL, SC-4, BC).
- P-02. Không có đăng ký; admin tạo tài khoản cho nhân viên (SC-4, BC FR04).
- P-03. Màn đăng nhập không có nút chọn vai trò, vai trò lấy theo tài khoản (SC-4).
- P-05. Bộ trạng thái của đơn. SQL hiện có "đang mở, đã thanh toán, đã hủy", thiết kế cho luồng cũ.
- P-06. Bộ trạng thái pha chế của món: Chờ → Đang pha → Xong → Đã mang ra (SQL, SC-4).
- P-07. Hàng chờ quầy chỉ hiện món Chờ và Đang pha, vào trước xếp trước (SC-4, BC FR09).
- P-08. Mỗi lần gọi một món là một dòng riêng; giá món lưu tại thời điểm gọi (SQL, BC).
- P-10. Tiền mặt: nhập tiền khách đưa, hiện tiền thối (SC-4, BC FR10).
- P-11. Mỗi đơn đã thanh toán có một hóa đơn; hóa đơn khổ 80 mm, có in và in lại, đếm số lần in (SQL, SC-4, BC FR11).
- P-12. Tiền VND lưu bằng số nguyên (SQL, BC).
- P-13. Danh sách chức năng FR01 đến FR13 trong báo cáo, mục 2.1. Chưa được PM duyệt lại theo luồng mới.
- P-14. Khách không tự gọi món (SC-4, BC).

## 4. Câu hỏi mở (chưa có phương án, không tự chọn)

Q-01, Q-02, Q-03 đã được PM chốt ngày 16/11 (D-47, D-48, D-49). Q-04 đến Q-08 đã được PM chốt ngày 17/11 (D-50 đến D-54).

Q-09, Q-10 đã được PM chốt ngày 17/11 (D-60, D-61).

Q-11 đã được PM chốt ngày 17/11 (D-62). Hiện không còn câu hỏi mở.

## 5. Tài liệu và sản phẩm cần đồng bộ theo luồng mới (không tự sửa nghiệp vụ)

- **`database/smartcoffee_pos.sql`:** đã thay bằng `database/smartcoffee_v2.sql` ngày 17/11 và xóa khỏi `main`. Ghi chú cũ: viết cho luồng cũ. Chưa có ca làm, voucher, trạng thái bàn lưu riêng, nhãn đổi món, khoản hoàn hoặc thu thêm, người thu tiền; còn trạng thái nháp, phương thức thẻ, cột giảm giá nhập tay, ràng buộc mỗi bàn một đơn đang mở. Thay đổi schema phải được PM phê duyệt.
- **Wireframe POS v1 và mô tả SC-4:** theo luồng cũ. Task thay thế: SC-5.
- **`docs/04-api.md`:** bản nháp chưa phê duyệt, theo luồng cũ. Xem [api-contract.md](api-contract.md).
- **Báo cáo `.docx` và `.pdf`:** FR06, FR07, FR09, FR10 theo luồng cũ.
- **`README.md`:** còn lặp mục nghiệp vụ theo luồng cũ và bảng phân công.

## 6. Ngoài phạm vi bản 1 (đã chốt)

- **Không làm:** chuỗi chi nhánh, VAT, sổ quỹ hoặc kế toán, đặt bàn VIP và phòng VIP, giảm giá thành viên VIP, tích hợp GrabFood (D-08).
- **Phát triển sau:** quản lý nhân sự, xếp lịch làm, chấm công (D-37); thanh toán thẻ (D-39).

Ca làm của thu ngân, màn kết ca và voucher do manager tạo **nằm trong phạm vi bản 1** (D-32, D-43).

Gặp yêu cầu nằm ngoài danh sách tính năng: ghi lại và hỏi PM, không làm trước.

## 7. Nhật ký quyết định

Chỉ ghi **Đã chốt** khi có lời của PM hoặc PM duyệt một nội dung cụ thể. "Tóm tắt phiên" nghĩa là quyết định có từ phần đầu phiên làm việc, hiện chỉ còn bản tóm tắt, không còn nguyên văn; PM nên xác nhận lại nếu thấy ghi sai.

| Mã | Ngày | Loại | Quyết định | Trạng thái | Căn cứ |
|---|---|---|---|---|---|
| D-01 | trước 14/11 | Nghiệp vụ | Hệ thống cho một quán, POS và quản trị | Đã chốt | Tóm tắt phiên; PM nhắc lại "phạm vi 1 quán" |
| D-02 | trước 14/11 | Kỹ thuật | Đăng nhập bằng session PHP, không JWT | Đã chốt | Tóm tắt phiên |
| D-03 | trước 14/11 | Nghiệp vụ | Quyền theo vai trò cố định trong code | Đã chốt | Tóm tắt phiên |
| D-04 | trước 14/11 | Nghiệp vụ | Admin chỉ quản lý hệ thống | Đã chốt | Tóm tắt phiên |
| D-05 | trước 14/11 | Nghiệp vụ | Món là nháp cho tới khi bấm Gửi món | **Đã thay bằng D-25** | Tóm tắt phiên |
| D-06 | trước 14/11 | Nghiệp vụ | Chỉ manager hủy món, bắt buộc có lý do | Đã chốt (áp dụng theo D-29, D-30, D-35) | Tóm tắt phiên |
| D-07 | trước 14/11 | Nghiệp vụ | Đơn đã thanh toán không hủy | **Đã thay bằng D-29, D-35** | Tóm tắt phiên |
| D-08 | 14/11 | Phạm vi | Danh sách nội dung ngoài phạm vi (mục 6) | Đã chốt, **điều chỉnh bởi D-32, D-37, D-43** | PM yêu cầu ghi vào SC-2 |
| D-09 | 14/11 | Kỹ thuật | Backend trả JSON, frontend riêng (phương án B) | Đã chốt | PM: "B đi" |
| D-10 | 14/11 | Nghiệp vụ | Không hỏi, không quản lý số khách | Đã chốt | PM nhắn nhóm |
| D-11 | 14/11 | Nghiệp vụ | Mã đơn hệ thống tự sinh, khách nhận theo mã đơn | Đã chốt | PM nhắn nhóm |
| D-12 | 14/11 | Giao diện | Pha chế bấm Xong ngay trên thẻ | Đã chốt | PM nhắn nhóm |
| D-13 | 14/11 | Giao diện | Không có thông tin ca làm việc trên giao diện | **Đã thay bằng D-32** | PM duyệt comment SC-4 số 10016 |
| D-14 | 14/11 | Giao diện | 4 tài khoản mẫu: phục vụ, pha chế, thu ngân, manager | Đã chốt | PM duyệt comment SC-4 số 10016 |
| D-15 | 14/11 | Nghiệp vụ | Bàn đã có khách không tạo đơn trùng | **Đã thay bằng D-28** | PM, 14/11 |
| D-16 | 14/11 | Nghiệp vụ | Gọi thêm vào đơn đang mở; manager có Hủy món và Hủy đơn | **Đã thay bằng D-28, D-29, D-35** | PM, 14/11 |
| D-17 | 14/11 | Nghiệp vụ | Sơ đồ bàn không có "Chờ thanh toán" | Đã chốt | PM, 14/11 |
| D-18 | 14/11 | Nghiệp vụ | Manager không hủy được món đã mang ra | **Đã thay bằng D-30** | PM, 14/11 |
| D-19 | 14/11 | Nghiệp vụ | Hủy cả đơn theo luồng cũ | **Đã thay bằng D-35** | PM, 14/11 |
| D-20 | 15/11 | Nghiệp vụ | Không suy ra hủy đơn = hủy món đã mang ra | Hết hiệu lực do D-30, D-35 | PM review 15/11 |
| D-21 | 14/11, 15/11 | Quy trình | AI không tự thiết kế API; `docs/04-api.md` giữ nguyên là bản nháp chưa phê duyệt | Đã chốt | PM |
| D-22 | 14/11 | Quy trình | Chỉ PM phê duyệt thay đổi nghiệp vụ, API, database schema | Đã chốt | Task tái cấu trúc tài liệu của PM |
| D-23 | 15/11 | Quy trình | SQL không phải nguồn phê duyệt nghiệp vụ | Đã chốt | PM review 15/11 |
| D-24 | 14/11 | Kỹ thuật | Bản cũ chuyển vào `old-version/`, dựng cấu trúc thư mục mới | Đã chốt | PM: "Ok" |
| D-25 | 16/11 | Nghiệp vụ | Phục vụ: chọn món → tùy chỉnh → thanh toán → hệ thống gửi món đã thanh toán vào quầy | Đã chốt | PM, 16/11 14:56 |
| D-26 | 16/11 | Nghiệp vụ | Thu ngân: màn chính là POS, chọn món và thanh toán, hệ thống gửi vào quầy | Đã chốt | PM, 16/11 14:56 |
| D-28 | 16/11 | Nghiệp vụ | Mỗi lần gọi là một đơn mới, trả tiền riêng | Đã chốt | PM, 16/11 15:03 |
| D-29 | 16/11 | Nghiệp vụ | Sau thanh toán: manager hủy hoặc đổi món, bù hoặc hoàn tiền tại chỗ; hệ thống vẫn ghi món kèm nhãn và lý do | Đã chốt | PM, 16/11 15:03 |
| D-30 | 16/11 | Nghiệp vụ | Manager được hủy món đã mang ra, kèm hoàn tiền, bắt buộc lý do | Đã chốt | PM, 16/11 15:10 |
| D-31 | 16/11 | Nghiệp vụ | Bàn Có khách cho tới khi nhân viên dọn và bấm Trả bàn | Đã chốt (chi tiết ở D-45) | PM, 16/11 15:03 |
| D-32 | 16/11 | Nghiệp vụ | Kết ca thu ngân: hóa đơn trong ca, lọc theo ngày và theo ca của tài khoản, tiền mặt đầu và cuối ca, chuyển khoản ghi vào ca | Đã chốt | PM, 16/11 14:56 và 15:03 |
| D-33 | 16/11 | Nghiệp vụ | Tiền phục vụ thu thuộc ca đang làm; lưu ai thu từng đơn | Đã chốt | PM, 16/11 15:10 |
| D-34 | 16/11 | Nghiệp vụ | Chuyển bàn: đơn sang bàn mới, bàn cũ thành trống | Đã chốt (bổ sung bởi D-42) | PM, 16/11 15:10 |
| D-35 | 16/11 | Nghiệp vụ | Hủy cả đơn sau thanh toán: chỉ manager, bắt buộc lý do; không có vai trò chủ quán riêng, không duyệt hai cấp | Đã chốt | PM, 16/11 15:37 và 15:42 ("bỏ role chủ quán cho manager huỷ đơn") |
| D-36 | 16/11 | Nghiệp vụ | Đổi món: sửa trên đơn đó, bỏ món cũ, thêm món mới, thanh toán phần chênh lệch | Đã chốt | PM, 16/11 15:37 |
| D-37 | 16/11 | Nghiệp vụ | Mỗi ca một thu ngân, chỉ thu ngân của ca mở ca; nhân sự, lịch làm, chấm công phát triển sau | Đã chốt | PM, 16/11 15:37 và 15:42 |
| D-38 | 16/11 | Nghiệp vụ | Chưa có ca mở thì phục vụ không bấm món được | Đã chốt | PM, 16/11 15:42 |
| D-39 | 16/11 | Phạm vi | Chưa phát triển thanh toán thẻ | Đã chốt | PM, 16/11 15:37 |
| D-40 | 16/11 | Nghiệp vụ | Không trả nhiều phương thức trong một đơn | Đã chốt | PM, 16/11 15:37 |
| D-41 | 16/11 | Nghiệp vụ | Phục vụ được bấm Trả bàn | Đã chốt | PM, 16/11 15:37 |
| D-42 | 16/11 | Nghiệp vụ | Chuyển bàn không bắt buộc bàn đích trống, có thể gộp bàn | Đã chốt | PM, 16/11 15:37 |
| D-43 | 16/11 | Nghiệp vụ | Giảm giá phải có voucher do manager tạo từ hệ thống; thu ngân và phục vụ nhập theo đơn tại quầy hay tại bàn | Đã chốt | PM, 16/11 15:37 và 15:42 |
| D-44 | 16/11 | Nghiệp vụ | Manager không có chức năng của phục vụ và thu ngân | Đã chốt | PM, 16/11 15:37 |
| D-45 | 16/11 | Nghiệp vụ | Bàn thành Có khách khi đơn thanh toán xong; Trống khi phục vụ bấm Trả bàn | Đã chốt | PM, 16/11 15:42 |
| D-46 | 16/11 | Giao diện | Bỏ màn "Mở đơn" trung gian, chạm bàn vào thẳng chọn món | Đã chốt | PM, 16/11 15:49: "Bỏ màn mở đơn" |
| D-47 | 16/11 | Nghiệp vụ | Thu ngân tự mở ca; mỗi lúc chỉ một ca mở | Đã chốt | PM, 16/11 15:49 |
| D-48 | 16/11 | Nghiệp vụ | Voucher do manager tạo và tự cấu hình: phần trăm, số tiền, hạn, số lần | Đã chốt | PM, 16/11 15:49 |
| D-49 | 16/11 | Nghiệp vụ | Tiền hoàn của đơn thuộc ca nào thì tính vào ca đó | Đã chốt | PM, 16/11 15:49 |
| P-01 đến P-14 | | | Các điểm ở mục 3 | Chờ PM duyệt | Chỉ có trong SQL, SC-4, báo cáo |
| D-50 | 17/11 | Nghiệp vụ | Hủy cả đơn thì hoàn 100% tiền của đơn | Đã chốt | PM, 17/11 12:31 |
| D-51 | 17/11 | Nghiệp vụ | Trả bàn không có điều kiện, phục vụ bấm khi dọn bàn | Đã chốt | PM, 17/11 12:31 |
| D-52 | 17/11 | Nghiệp vụ | Hủy đơn, hoàn tiền chỉ thực hiện trong ca của đơn; phát sinh sau khi kết ca do vận hành hoặc chủ quán xử lý ngoài hệ thống | Đã chốt | PM, 17/11 12:31 |
| D-53 | 17/11 | Nghiệp vụ | Gộp bàn không gộp đơn: các đơn giữ riêng, chỉ chuyển về bàn đích (PM trả lời "gộp đơn" lúc 12:31, đổi lại lúc 12:35) | Đã chốt | PM, 17/11 12:35 |
| D-54 | 17/11 | Nghiệp vụ | Phục vụ không bán đơn mang đi | Đã chốt | PM, 17/11 12:35 |
| D-55 | 17/11 | Nghiệp vụ | Thu ngân bán đơn mang đi; đơn mang đi không gắn bàn (thay P-04) | Đã chốt | PM, 17/11 20:53 |
| D-56 | 17/11 | Nghiệp vụ | Ngọt, đắng: Ít / Bình thường / Nhiều; đá: Không đá / Ít / Bình thường / Nhiều | Đã chốt | PM, 17/11 20:53 |
| D-57 | 17/11 | Nghiệp vụ | Mỗi đơn tối đa một voucher; chưa có trần giảm, chưa có điều kiện đơn tối thiểu | Đã chốt | PM, 17/11 20:53 |
| D-58 | 17/11 | Nghiệp vụ | Món có trạng thái Tạm hết và Ngừng bán (thay P-09) | Đã chốt | PM, 17/11 20:53 |
| D-59 | 17/11 | Phạm vi | Bản 1 không làm dashboard, báo cáo cho manager | Đã chốt | PM, 17/11 20:53 |
| D-60 | 17/11 | Nghiệp vụ | Hủy, đổi một món trong đơn có voucher: hoàn hoặc tính chênh lệch theo giá món sau giảm | Đã chốt | PM, 17/11 20:58 |
| D-61 | 17/11 | Nghiệp vụ | Thu ngân bán mang đi; phục vụ bán tại bàn và trả bàn | Đã chốt | PM, 17/11 20:58 |
| D-62 | 17/11 | Nghiệp vụ | Khoản hoàn, thu thêm ghi theo cách thực tế đưa hoặc nhận tiền | Đã chốt | PM, 17/11 20:59 |

Ghi chú: không có mã D-27.
