# SmartCoffee: Đặc tả chức năng và tiến độ

Cập nhật: 16/11/2026. Người phê duyệt nghiệp vụ: Hùng (PM).

Tài liệu này mô tả hệ thống làm gì và đang làm tới đâu. Quy tắc nghiệp vụ gốc và căn cứ từng quyết định nằm ở [business-rules.md](business-rules.md) (mã D-xx). Chỗ nào ghi **(chờ PM)** là chưa chốt, không được coi là yêu cầu.

## 1. Tổng quan

SmartCoffee là phần mềm quản trị và POS cho **một quán cà phê**. Bán theo kiểu **thanh toán trước, pha sau**: khách chọn món, trả tiền, hệ thống mới gửi món vào quầy pha chế.

- Bán **tại bàn**: phục vụ dùng điện thoại chọn món và thu tiền.
- Bán **tại quầy**: thu ngân dùng màn POS trên máy tính.
- Công nghệ: backend PHP (MVC, PDO) trả JSON, MySQL/MariaDB trên XAMPP, frontend ở thư mục riêng, đăng nhập bằng session PHP.

## 2. Vai trò và quyền

| Việc | Phục vụ | Thu ngân | Pha chế | Manager | Admin |
|---|---|---|---|---|---|
| Chọn món, tạo đơn tại bàn | Có | | | | |
| Chọn món, tạo đơn tại quầy (POS) | | Có | | | |
| Thu tiền | Tại bàn | Tại quầy | | | |
| Nhập voucher giảm giá | Đơn tại bàn | Đơn tại quầy | | | |
| Xem hàng chờ, đổi trạng thái pha chế | | | Có **(chờ PM)** | | |
| Xác nhận "Đã mang ra" | Có **(chờ PM)** | | | | |
| Trả bàn, chuyển bàn | Có | | | | |
| Mở ca, kết ca | | Có | | | |
| Hủy món, đổi món, hủy đơn (có lý do) | | | | Có | |
| Tạo voucher | | | | Có | |
| Quản lý món, bàn, xem báo cáo | | | | Có **(chờ PM)** | |
| Quản lý tài khoản, cài đặt | | | | | Có |

- Manager **không** bán hàng, không thu tiền (D-44). Admin **không** tham gia bán hàng (D-04).
- Không có vai trò "chủ quán" riêng (D-35).

## 3. Luồng nghiệp vụ

### 3.1 Phục vụ bán tại bàn
1. Đăng nhập. Nếu chưa có ca thu ngân nào mở thì **không chọn món được** (D-38).
2. Mở sơ đồ bàn (Trống / Có khách), chạm vào bàn, vào thẳng màn chọn món (D-46).
3. Chọn món, tùy chỉnh (ngọt, đắng, đá, số lượng, ghi chú).
4. Nhập voucher nếu khách có (D-43).
5. Thanh toán: tiền mặt hoặc chuyển khoản, một phương thức cho mỗi đơn (D-39, D-40).
6. Thanh toán xong: đơn có mã do hệ thống sinh, món tự vào quầy pha chế, bàn chuyển sang **Có khách** (D-25, D-45).
7. Món pha xong thì mang ra bàn.
8. Khách gọi thêm: tạo **đơn mới**, trả tiền riêng (D-28).
9. Khách về: dọn bàn, bấm **Trả bàn**, bàn về **Trống** (D-41, D-45).

### 3.2 Thu ngân bán tại quầy
1. Đăng nhập, **mở ca**, nhập tiền mặt đầu ca (D-32, D-37).
2. Màn POS: một bên sản phẩm, một bên đơn hàng. Chọn món, nhập voucher nếu có, thanh toán (D-26).
3. Hệ thống gửi món vào quầy pha chế, in hóa đơn.
4. Cuối ca: mở màn tổng kết ca, xem hóa đơn trong ca, nhập tiền mặt cuối ca, kết ca (D-32).

### 3.3 Pha chế
1. Xem hàng chờ gồm các món đã thanh toán.
2. Bấm "Bắt đầu pha", rồi bấm "Xong" ngay trên thẻ món (D-12).

### 3.4 Manager xử lý sau thanh toán
- **Hủy món:** chọn món (kể cả đã mang ra), nhập lý do, hoàn tiền tại chỗ. Món vẫn nằm trong đơn với nhãn "Hủy" (D-29, D-30).
- **Đổi món:** trên chính đơn đó, bỏ món cũ, thêm món mới, thu thêm hoặc hoàn phần chênh lệch, nhập lý do. Món cũ mang nhãn "Đổi món" (D-36).
- **Hủy đơn:** nhập lý do, hủy cả đơn (D-35).

## 4. Đặc tả chức năng

Cột "Bản": **1** = làm trong bản 1; **Sau** = phát triển sau. Cột "Trạng thái nghiệp vụ": **Đã chốt** hoặc **Chờ PM** (một phần hoặc toàn bộ chưa chốt).

### 4.1 Tài khoản
| Mã | Chức năng | Bản | Trạng thái nghiệp vụ |
|---|---|---|---|
| CN-01 | Đăng nhập, đăng xuất bằng session; quyền theo vai trò cố định | 1 | Đã chốt (D-02, D-03) |
| CN-02 | Admin tạo, khóa tài khoản và gán vai trò | 1 | Chờ PM (P-02) |

### 4.2 Bán hàng
| Mã | Chức năng | Bản | Trạng thái nghiệp vụ |
|---|---|---|---|
| CN-03 | Sơ đồ bàn theo khu vực, trạng thái Trống / Có khách | 1 | Đã chốt (D-17, D-45) |
| CN-04 | Phục vụ chọn món và tùy chỉnh món cho bàn | 1 | Đã chốt (D-25) |
| CN-05 | Phục vụ thanh toán tại bàn bằng tiền mặt hoặc chuyển khoản | 1 | Đã chốt (D-25, D-39, D-40) |
| CN-06 | Thu ngân bán hàng trên màn POS (sản phẩm và đơn hàng) | 1 | Đã chốt (D-26) |
| CN-07 | Đơn mang đi | 1 | Chờ PM (P-04) |
| CN-08 | Hệ thống tự sinh mã đơn và gửi món đã thanh toán vào quầy | 1 | Đã chốt (D-11, D-25) |
| CN-09 | Nhập voucher giảm giá cho đơn | 1 | Đã chốt (D-43, D-48) |
| CN-10 | Gọi thêm tạo đơn mới | 1 | Đã chốt (D-28) |
| CN-11 | Trả bàn | 1 | Đã chốt (D-41, D-45, D-51): không có điều kiện |
| CN-12 | Chuyển bàn, gộp bàn | 1 | Đã chốt (D-34, D-42, D-53): gộp bàn không gộp đơn |
| CN-13 | Hóa đơn khổ 80 mm, in và in lại | 1 | Chờ PM (P-11) |
| CN-14 | Món tạm hết, món ngừng bán | 1 | Chờ PM (P-09) |

### 4.3 Pha chế
| Mã | Chức năng | Bản | Trạng thái nghiệp vụ |
|---|---|---|---|
| CN-15 | Hàng chờ pha chế, đổi trạng thái Chờ → Đang pha → Xong | 1 | Bấm Xong trên thẻ đã chốt (D-12); bộ trạng thái chờ PM (P-06, P-07) |
| CN-16 | Phục vụ xác nhận "Đã mang ra" | 1 | Chờ PM (P-01, P-06) |

### 4.4 Xử lý sau thanh toán (manager)
| Mã | Chức năng | Bản | Trạng thái nghiệp vụ |
|---|---|---|---|
| CN-17 | Hủy món có lý do, hoàn tiền; hủy được món đã mang ra | 1 | Đã chốt (D-29, D-30) |
| CN-18 | Đổi món trên đơn, thanh toán phần chênh lệch, có lý do | 1 | Đã chốt (D-36) |
| CN-19 | Hủy cả đơn có lý do | 1 | Đã chốt (D-35, D-50): hoàn 100% |
| CN-20 | Ghi nhận món đã hủy, đã đổi kèm nhãn và lý do | 1 | Đã chốt (D-29) |

### 4.5 Ca làm
| Mã | Chức năng | Bản | Trạng thái nghiệp vụ |
|---|---|---|---|
| CN-21 | Thu ngân mở ca, nhập tiền mặt đầu ca | 1 | Đã chốt (D-32, D-37, D-47): thu ngân tự mở ca, mỗi lúc một ca mở |
| CN-22 | Chặn phục vụ bán hàng khi chưa có ca mở | 1 | Đã chốt (D-38) |
| CN-23 | Ghi nhận người thu tiền của từng đơn và gắn đơn vào ca | 1 | Đã chốt (D-33) |
| CN-24 | Tổng kết ca: hóa đơn trong ca, lọc theo ngày, tiền mặt đầu và cuối ca, tiền chuyển khoản; kết ca | 1 | Đã chốt (D-32, D-49, D-52): hủy, hoàn chỉ trong ca của đơn |

### 4.6 Quản lý
| Mã | Chức năng | Bản | Trạng thái nghiệp vụ |
|---|---|---|---|
| CN-25 | Manager tạo và quản lý voucher (phần trăm, số tiền, hạn, số lần) | 1 | Đã chốt (D-43, D-48) |
| CN-26 | Manager quản lý danh mục, món, bàn | 1 | Chờ PM (P-01) |
| CN-27 | Báo cáo doanh thu | 1 | Chờ PM (P-01, P-13) |
| CN-28 | Nhân sự, xếp lịch làm, chấm công | Sau | Đã chốt là làm sau (D-37) |
| CN-29 | Thanh toán thẻ | Sau | Đã chốt là làm sau (D-39) |

### 4.7 Không làm
Chuỗi chi nhánh, VAT, sổ quỹ hoặc kế toán, đặt bàn và phòng VIP, giảm giá thành viên VIP, tích hợp GrabFood, trả nhiều phương thức trong một đơn, giảm giá nhập tay (D-08, D-40, D-43).

## 5. Tiến độ (tính đến 16/11/2026)

### 5.1 Theo hạng mục

| Hạng mục | Người làm | Trạng thái | Ghi chú |
|---|---|---|---|
| Quy tắc nghiệp vụ | Hùng | Đang chốt | Luồng mới chốt ngày 16/11. Còn 4 câu hỏi mở và 14 điểm chờ duyệt |
| Component Library (SC-2) | Huy | Đang làm, hạn 12:00 ngày 17/11 | Đã có 20 bộ nút và điều khiển. Chưa có thẻ bàn, thẻ món, thẻ hàng chờ, nhãn, hộp thoại hủy. Chưa sửa các lỗi đã nêu từ 14/11 |
| Wireframe POS v1 (SC-4) | Tuấn Anh | Đã đóng | 155 màn theo **luồng cũ**. Chỉ dùng lại được một phần |
| Thiết kế POS v2 theo luồng mới (SC-5) | Tuấn Anh | Mới giao 16/11, chưa có hạn | Làm lại luồng phục vụ, màn POS thu ngân, mở ca và kết ca, hủy và đổi món, voucher |
| Cơ sở dữ liệu | Hùng | Có script cho luồng cũ, **phải sửa** | Thiếu ca làm, voucher, trạng thái bàn, đổi món, hoàn tiền, người thu tiền. Chưa được phê duyệt làm hợp đồng dữ liệu |
| Hợp đồng API | Hùng | Chưa đặc tả | `docs/04-api.md` là bản nháp theo luồng cũ, chưa duyệt |
| Backend | Hùng | Chưa bắt đầu | Mới có khung thư mục rỗng |
| Frontend (code) | Tuấn Anh, Huy (dự kiến) | Chưa bắt đầu | Mới có khung thư mục rỗng; phân công chưa chốt |
| Tài liệu | Hùng | Đang làm | `AGENTS.md`, `business-rules.md`, `api-contract.md` và file này chưa commit. Báo cáo `.docx` còn theo luồng cũ |
| Lê Ngọc Gia Bảo | | Chưa có task | |

### 5.2 Theo chức năng

Chưa có chức năng nào được lập trình. Mức hoàn thành hiện tại chỉ ở khâu thiết kế:

| Nhóm chức năng | Nghiệp vụ | Thiết kế giao diện | Database | Backend | Frontend |
|---|---|---|---|---|---|
| Tài khoản (CN-01, 02) | Phần lớn đã chốt | Có màn đăng nhập (POS v1) | Có bảng | Chưa | Chưa |
| Bán tại bàn (CN-03 đến 05, 08 đến 12) | Đã chốt | Làm lại ở SC-5 | Phải sửa | Chưa | Chưa |
| POS thu ngân (CN-06) | Đã chốt | Chưa có, làm ở SC-5 | Phải sửa | Chưa | Chưa |
| Pha chế (CN-15, 16) | Một phần chờ PM | Có (POS v1), dùng lại được | Có bảng | Chưa | Chưa |
| Hủy, đổi món, hủy đơn (CN-17 đến 20) | Đã chốt | Làm lại ở SC-5 | Phải sửa | Chưa | Chưa |
| Ca làm (CN-21 đến 24) | Đã chốt | Chưa có, làm ở SC-5 | Chưa có | Chưa | Chưa |
| Voucher (CN-09, 25) | Đã chốt | Chưa có, đã thêm vào SC-5 ngày 16/11 | Chưa có | Chưa | Chưa |
| Quản lý món, bàn, báo cáo (CN-26, 27) | Chờ PM | Chưa có, **chưa nằm trong task nào** | Có bảng món, bàn | Chưa | Chưa |

## 6. Việc tiếp theo và rủi ro

**Thứ tự đề xuất (chưa được PM duyệt):**
1. Các câu hỏi mở Q-04 đến Q-08 đã được PM chốt ngày 17/11 (D-50 đến D-54).
2. Sửa database theo luồng mới, PM phê duyệt schema.
3. Đặc tả API, PM phê duyệt.
4. Lập trình backend theo từng nhóm chức năng, bắt đầu từ đăng nhập, ca làm và bán hàng.

**Rủi ro:**
- **Đổi luồng lõi sau khi wireframe đã xong:** thiết kế phải làm lại một vòng, backend chưa bắt đầu được cho tới khi database và API được duyệt.
- **Phạm vi tăng trong ngày 16/11:** thêm ca làm, kết ca, voucher, đổi món, hoàn tiền. Màn quản lý món, bàn, báo cáo chưa nằm trong task thiết kế nào.
- **Hoàn tiền sau khi kết ca nằm ngoài hệ thống (D-52):** các trường hợp này không có dấu vết trong phần mềm, vận hành hoặc chủ quán phải tự ghi nhận.
- **Phụ thuộc giữa các task:** SC-5 cần thẻ và nhãn của SC-2 (hạn 12:00 ngày 17/11).
- **Hai file Figma:** nhóm cần được mời vào file mới và ngừng sửa file cũ.

## 7. Câu hỏi mở

Xem [business-rules.md](business-rules.md), mục 4. Tính đến 17/11 không còn câu hỏi mở.
