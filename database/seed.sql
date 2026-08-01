-- ============================================================
-- SEED DATA — Dữ liệu thực tế sân bóng đá tại Cần Thơ
-- ============================================================

USE `football_booking`;

-- ============================================================
-- 1. USERS — Tài khoản mẫu
-- Mật khẩu mặc định: "Password123" (bcrypt hash)
-- ============================================================
INSERT INTO `users` (`full_name`, `email`, `phone`, `password_hash`, `role`, `address`, `lat`, `lng`) VALUES

-- Admin hệ thống
('Quản Trị Viên', 'admin@sancangio.vn', '0901000001',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',
 'Phường Tân An, Quận Ninh Kiều, Cần Thơ', 10.0452, 105.7469),

-- Chủ sân 1 — Ninh Kiều
('Nguyễn Văn Hùng', 'hung.owner@gmail.com', '0907123456',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',
 '373/10B Khu vực 4, Phường An Bình, Quận Ninh Kiều', 10.0331, 105.7789),

-- Chủ sân 2 — Cái Răng
('Trần Thị Linh', 'linh.owner@gmail.com', '0918234567',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',
 '26 Trần Chiên, Phường Lê Bình, Quận Cái Răng', 10.0089, 105.7712),

-- Chủ sân 3 — Bình Thủy
('Lê Quốc Bình', 'binh.owner@gmail.com', '0933345678',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',
 'Khu dân cư Ngân Thuận, Phường Bình Thủy', 10.0623, 105.7256),

-- Chủ sân 4 — Cái Răng (thêm)
('Phạm Thanh Tùng', 'tung.owner@gmail.com', '0944456789',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',
 'Khu đô thị Hưng Phú, Phường Hưng Phú, Quận Cái Răng', 10.0012, 105.7934),

-- Khách hàng mẫu
('Nguyễn Minh Tuấn', 'tuan.customer@gmail.com', '0955567890',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer',
 '12 Đường 3/2, Phường Xuân Khánh, Quận Ninh Kiều', 10.0398, 105.7523),

('Võ Thị Thanh', 'thanh.customer@gmail.com', '0966678901',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer',
 '45 Lê Hồng Phong, Phường An Hòa, Quận Ninh Kiều', 10.0412, 105.7601),

('Đặng Văn Khoa', 'khoa.customer@gmail.com', '0977789012',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer',
 '78 Mậu Thân, Phường An Hòa, Quận Ninh Kiều', 10.0445, 105.7489),

('Huỳnh Thế Anh', 'anh.customer@gmail.com', '0988890123',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer',
 '15 Đường số 9, Khu đô thị Hưng Phú, Quận Cái Răng', 10.0067, 105.7891),

('Lý Thị Mỹ Duyên', 'duyen.customer@gmail.com', '0999901234',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer',
 '23 Vành Đai Phi Trường, Phường An Hòa, Quận Bình Thủy', 10.0589, 105.7312);


-- ============================================================
-- 2. FIELDS — 15 Sân bóng đá thực tế tại Cần Thơ
-- ============================================================
INSERT INTO `fields` (
  `owner_id`, `name`, `slug`, `address`, `district`, `ward`,
  `lat`, `lng`, `type`, `surface`, `price_per_hour`, `price_peak`,
  `description`, `phone`, `open_time`, `close_time`,
  `has_lighting`, `has_parking`, `has_shower`, `has_canteen`, `has_rental`,
  `cover_image`, `status`, `rating`, `total_reviews`, `total_bookings`
) VALUES

-- ====== QUẬN NINH KIỀU (6 sân) ======

(2, 'Sân Bóng Hàng Bàng',
 'san-bong-hang-bang',
 '373/10B Khu vực 4, Phường An Bình, Quận Ninh Kiều, Cần Thơ',
 'Ninh Kiều', 'An Bình',
 10.02450, 105.74850,
 '5v5', 'co_nhan_tao', 200000, 280000,
 'Sân bóng mini cỏ nhân tạo thế hệ mới, hệ thống đèn LED chiếu sáng ban đêm, bãi đỗ xe rộng rãi. Phục vụ từ 6h sáng đến 22h đêm. Nằm trong khu dân cư An Bình, giao thông thuận tiện.',
 '0939001202', '06:00', '22:00',
 1, 1, 1, 1, 1,
 'assets/uploads/fields/field_cover_1.jpg', 'active', 4.5, 38, 312),

(2, 'Sân Bóng Yến Linh',
 'san-bong-yen-linh',
 'Hẻm 86 Cách Mạng Tháng 8, Phường Cái Khế, Quận Ninh Kiều, Cần Thơ',
 'Ninh Kiều', 'Cái Khế',
 10.04600, 105.77250,
 '5v5', 'co_nhan_tao', 180000, 250000,
 'Sân bóng mini ngay trung tâm thành phố Cần Thơ, gần sông Hậu. Cỏ nhân tạo mới trải 2023, có mái che một phần. Lý tưởng cho các buổi đá bóng sau giờ làm.',
 '0919123456', '06:00', '22:00',
 1, 1, 0, 1, 0,
 'assets/uploads/fields/field_cover_2.jpg', 'active', 4.2, 25, 198),

(2, 'Sân Bóng 150A Xuân Khánh',
 'san-bong-150a-xuan-khanh',
 'Khu dân cư số 5, Phường Xuân Khánh, Quận Ninh Kiều, Cần Thơ',
 'Ninh Kiều', 'Xuân Khánh',
 10.03050, 105.76800,
 '7v7', 'co_nhan_tao', 300000, 400000,
 'Sân 7 người tiêu chuẩn với cỏ nhân tạo FIFA Quality, hệ thống tưới nước tự động. Căn tin phục vụ nước giải khát. Phòng thay đồ sạch sẽ, có tủ khóa. Phù hợp tổ chức giải đấu nhỏ.',
 '0858658899', '05:30', '22:30',
 1, 1, 1, 1, 1,
 'assets/uploads/fields/field_cover_3.jpg', 'active', 4.7, 52, 445),

(2, 'Sân Bóng Trần Văn Hoài',
 'san-bong-tran-van-hoai',
 'Đường Trần Văn Hoài, Phường An Phú, Quận Ninh Kiều, Cần Thơ',
 'Ninh Kiều', 'An Phú',
 10.03220, 105.77780,
 '5v5', 'co_nhan_tao', 160000, 220000,
 'Sân bóng khu dân cư yên tĩnh, giá cả phải chăng. Phù hợp học sinh, sinh viên và người có ngân sách eo hẹp. Mở cửa sớm từ 5h30 sáng.',
 '0918789123', '05:30', '22:00',
 1, 1, 0, 0, 1,
 'assets/uploads/fields/field_cover_4.jpg', 'active', 4.0, 18, 156),

(2, 'Sân Bóng Ninh Kiều Sport',
 'san-bong-ninh-kieu-sport',
 '45 Đường Võ Văn Kiệt, Phường An Khánh, Quận Ninh Kiều, Cần Thơ',
 'Ninh Kiều', 'An Khánh',
 10.03850, 105.75350,
 '7v7', 'co_nhan_tao', 320000, 420000,
 'Cụm 3 sân liên hoàn, có thể tổ chức giải đấu lớn. Camera an ninh 24/7, bảo vệ trực ca. Hệ thống đặt sân online tích hợp. Dịch vụ cho thuê áo và giày đá bóng.',
 '0907888999', '06:00', '23:00',
 1, 1, 1, 1, 1,
 'assets/uploads/fields/field_cover_5.jpg', 'active', 4.6, 67, 589),

(2, 'Sân Bóng Cỏ Nhân Tạo 116',
 'san-bong-co-nhan-tao-116',
 '116 Đường Nguyễn Văn Cừ, Phường An Khánh, Quận Ninh Kiều, Cần Thơ',
 'Ninh Kiều', 'An Khánh',
 10.03900, 105.75500,
 '5v5', 'co_nhan_tao', 170000, 240000,
 'Sân mini gần trường đại học, thường xuyên có các đội sinh viên. Giá ưu đãi cho sinh viên có thẻ. WiFi miễn phí khu vực khán đài.',
 '0939116116', '06:00', '22:00',
 1, 0, 0, 1, 1,
 'assets/uploads/fields/field_cover_1.jpg', 'active', 4.1, 29, 234),

-- ====== QUẬN CÁI RĂNG (6 sân) ======

(3, 'Sân Bóng Mini Tây Đô',
 'san-bong-mini-tay-do',
 'Đường Trần Chiên, Phường Lê Bình, Quận Cái Răng, Cần Thơ',
 'Cái Răng', 'Lê Bình',
 10.00280, 105.78200,
 '5v5', 'co_nhan_tao', 190000, 260000,
 'Sân bóng uy tín tại Quận Cái Răng, hoạt động từ 2018. Cỏ nhân tạo được thay mới năm 2024. Có căn tin phục vụ cơm trưa và bữa tối. Khu vực khán giả có mái che.',
 '0918234567', '06:00', '22:00',
 1, 1, 1, 1, 0,
 'assets/uploads/fields/field_cover_2.jpg', 'active', 4.4, 43, 367),

(3, 'Sân Bóng Mini Hòa An',
 'san-bong-mini-hoa-an',
 '181/1 Đường Nhật Tảo, Khu vực Yên Hòa, Phường Lê Bình, Quận Cái Răng, Cần Thơ',
 'Cái Răng', 'Lê Bình',
 10.00550, 105.78650,
 '5v5', 'co_nhan_tao', 175000, 240000,
 'Sân gia đình, phục vụ tốt cho trẻ em và người lớn tuổi. Khu vực ngồi chờ thoáng mát. Gần chợ Lê Bình, dễ tìm kiếm.',
 '0918123456', '06:00', '21:30',
 1, 1, 0, 0, 1,
 'assets/uploads/fields/field_cover_3.jpg', 'active', 4.3, 21, 187),

(5, 'Sân Bóng 586 Phú Thứ',
 'san-bong-586-phu-thu',
 'Khu dân cư 586, Đường Bùi Quang Trinh, Phường Phú Thứ, Quận Cái Răng, Cần Thơ',
 'Cái Răng', 'Phú Thứ',
 10.00350, 105.81150,
 '7v7', 'co_nhan_tao', 280000, 380000,
 'Sân 7 người lớn nhất Quận Cái Răng, tiêu chuẩn tổ chức giải đấu cấp quận. Hệ thống âm thanh thông báo, màn hình điện tử hiển thị thời gian. Bãi đỗ xe ô tô và xe máy riêng.',
 '0944456789', '05:00', '23:00',
 1, 1, 1, 1, 1,
 'assets/uploads/fields/field_cover_4.jpg', 'active', 4.8, 89, 712),

(5, 'Sân Bóng Nam Long Hưng Phú',
 'san-bong-nam-long-hung-phu',
 'R23, Đường số 9, Lô 49, Khu đô thị Hưng Phú, Phường Hưng Phú, Quận Cái Răng, Cần Thơ',
 'Cái Răng', 'Hưng Phú',
 10.02150, 105.79500,
 '5v5', 'co_nhan_tao', 210000, 290000,
 'Sân trong khu đô thị Hưng Phú hiện đại, an ninh tốt. Được trang bị camera an ninh toàn khu. Phục vụ cư dân Hưng Phú và vùng lân cận. Đặt sân online giảm 10%.',
 '0944456790', '06:00', '22:00',
 1, 1, 0, 1, 0,
 'assets/uploads/fields/field_cover_5.jpg', 'active', 4.5, 34, 278),

(5, 'Sân Bóng Hoàng Uyên',
 'san-bong-hoang-uyen',
 '20/04 Khu vực 6, Trần Chiên, Phường Lê Bình, Quận Cái Răng, Cần Thơ',
 'Cái Răng', 'Lê Bình',
 10.00180, 105.78150,
 '5v5', 'co_nhan_tao', 185000, 250000,
 'Sân mini chất lượng cao, phục vụ tập luyện chuyên nghiệp và phong trào. Có huấn luyện viên tư vấn kỹ thuật theo yêu cầu. Tổ chức giải đấu nội bộ hàng tháng.',
 '0944456791', '06:00', '22:00',
 1, 1, 1, 1, 1,
 'assets/uploads/fields/field_cover_1.jpg', 'active', 4.6, 48, 398),

(5, 'Sân Bóng Mini 448 Quang Trung',
 'san-bong-mini-448-quang-trung',
 '79 Quang Trung, Phường Hưng Phú, Quận Cái Răng, Cần Thơ',
 'Cái Răng', 'Hưng Phú',
 10.02550, 105.79300,
 '5v5', 'co_nhan_tao', 165000, 230000,
 'Sân mini giá rẻ, phù hợp đội phong trào. Cỏ nhân tạo đầu sợi dài êm chân. Cho thuê bóng, áo đồng phục. Gần đường Quang Trung, dễ di chuyển.',
 '0944456792', '06:00', '22:00',
 1, 1, 0, 0, 1,
 'assets/uploads/fields/field_cover_2.jpg', 'active', 4.0, 16, 145),

-- ====== QUẬN BÌNH THỦY (3 sân) ======

(4, 'Sân Bóng Ngân Thuận',
 'san-bong-ngan-thuan',
 'B6-23 Đường số 6, Khu dân cư Ngân Thuận, Phường Bình Thủy, Quận Bình Thủy, Cần Thơ',
 'Bình Thủy', 'Bình Thủy',
 10.08250, 105.72900,
 '5v5', 'co_nhan_tao', 195000, 265000,
 'Sân bóng uy tín tại Bình Thủy, gần sân bay Cần Thơ. Không gian thoáng đãng, ít ồn ào. Phòng thay đồ đạt chuẩn, nước nóng lạnh. Bãi xe ô tô miễn phí.',
 '0933345678', '06:00', '22:00',
 1, 1, 1, 1, 1,
 'assets/uploads/fields/field_cover_3.jpg', 'active', 4.4, 31, 267),

(4, 'Sân Bóng Phi Long',
 'san-bong-phi-long',
 'Công viên Văn hóa Miền Tây, Đường Cách Mạng Tháng 8, Phường An Thới, Quận Bình Thủy, Cần Thơ',
 'Bình Thủy', 'An Hòa',
 10.06150, 105.74800,
 '7v7', 'co_nhan_tao', 310000, 410000,
 'Sân 7 người cao cấp khu vực Bình Thủy, mặt cỏ FIFA Basic. Hệ thống đèn pha công suất lớn, tầm nhìn ban đêm xuất sắc. Phù hợp giải đấu doanh nghiệp, sự kiện team building.',
 '0933345679', '06:00', '23:00',
 1, 1, 1, 1, 1,
 'assets/uploads/fields/field_cover_4.jpg', 'active', 4.7, 56, 478),

(4, 'Sân Bóng An Hòa Sport Center',
 'san-bong-an-hoa-sport-center',
 '12 Đường Lê Hồng Phong, Phường An Hòa, Quận Bình Thủy, Cần Thơ',
 'Bình Thủy', 'An Hòa',
 10.06350, 105.74350,
 '11v11', 'co_nhan_tao', 800000, 1000000,
 'Sân 11 người chuẩn thi đấu duy nhất tại Bình Thủy. Đường kẻ FIFA tiêu chuẩn, khung thành chính hãng. Phòng VIP, bình luận viên, hệ thống bảng điểm điện tử. Lý tưởng cho giải đấu cấp thành phố.',
 '0933345680', '07:00', '21:00',
 1, 1, 1, 1, 1,
 'assets/uploads/fields/field_cover_5.jpg', 'active', 4.9, 72, 234);


-- ============================================================
-- 3. FIELD_IMAGES — Ảnh cho mỗi sân
-- ============================================================
INSERT INTO `field_images` (`field_id`, `image_path`, `caption`, `sort_order`) VALUES
(1, 'assets/uploads/fields/field_sub_1.jpg', 'Mặt sân cỏ nhân tạo', 1),
(1, 'assets/uploads/fields/field_sub_2.jpg', 'Khu vực khán đài', 2),
(1, 'assets/uploads/fields/field_sub_3.jpg', 'Hệ thống đèn LED', 3),
(2, 'assets/uploads/fields/field_sub_4.jpg', 'Sân bóng view sông', 1),
(2, 'assets/uploads/fields/field_sub_5.jpg', 'Cổng vào sân', 2),
(3, 'assets/uploads/fields/field_sub_1.jpg', 'Sân 7 người tiêu chuẩn', 1),
(3, 'assets/uploads/fields/field_sub_2.jpg', 'Phòng thay đồ', 2),
(3, 'assets/uploads/fields/field_sub_3.jpg', 'Căn tin', 3),
(4, 'assets/uploads/fields/field_sub_4.jpg', 'Sân mini phong trào', 1),
(5, 'assets/uploads/fields/field_sub_5.jpg', 'Cụm sân liên hoàn', 1),
(5, 'assets/uploads/fields/field_sub_1.jpg', 'Camera an ninh', 2),
(6, 'assets/uploads/fields/field_sub_2.jpg', 'Sân gần trường ĐH', 1),
(7, 'assets/uploads/fields/field_sub_3.jpg', 'Sân Tây Đô nhìn từ trên', 1),
(7, 'assets/uploads/fields/field_sub_4.jpg', 'Ban đêm dưới đèn', 2),
(8, 'assets/uploads/fields/field_sub_5.jpg', 'Sân bóng gia đình', 1),
(9, 'assets/uploads/fields/field_sub_1.jpg', 'Sân 7 người lớn nhất Cái Răng', 1),
(9, 'assets/uploads/fields/field_sub_2.jpg', 'Màn hình điện tử', 2),
(9, 'assets/uploads/fields/field_sub_3.jpg', 'Bãi đỗ xe', 3),
(10, 'assets/uploads/fields/field_sub_4.jpg', 'Trong khu đô thị', 1),
(11, 'assets/uploads/fields/field_sub_5.jpg', 'Sân chuyên nghiệp', 1),
(12, 'assets/uploads/fields/field_sub_1.jpg', 'Sân mini phong trào', 1),
(13, 'assets/uploads/fields/field_sub_2.jpg', 'Khu dân cư yên tĩnh', 1),
(13, 'assets/uploads/fields/field_sub_3.jpg', 'Phòng tắm đạt chuẩn', 2),
(14, 'assets/uploads/fields/field_sub_4.jpg', 'Sân 7 người cao cấp', 1),
(14, 'assets/uploads/fields/field_sub_5.jpg', 'Đèn pha công suất lớn', 2),
(15, 'assets/uploads/fields/field_sub_1.jpg', 'Sân 11 người chuẩn thi đấu', 1),
(15, 'assets/uploads/fields/field_sub_2.jpg', 'Phòng VIP', 2),
(15, 'assets/uploads/fields/field_sub_3.jpg', 'Bảng điểm điện tử', 3);


-- ============================================================
-- 4. BOOKINGS — Đơn đặt sân mẫu (30 ngày gần đây)
-- ============================================================
INSERT INTO `bookings` (`booking_code`, `field_id`, `user_id`, `booking_date`, `start_time`, `end_time`, `duration`, `total_price`, `status`, `payment_method`, `payment_status`) VALUES
('CT20260710001', 1, 6, '2026-07-10', '18:00', '19:00', 1, 280000, 'completed', 'momo', 'paid'),
('CT20260710002', 9, 7, '2026-07-10', '17:00', '19:00', 2, 760000, 'completed', 'momo', 'paid'),
('CT20260711001', 3, 8, '2026-07-11', '07:00', '09:00', 2, 600000, 'completed', 'cash', 'paid'),
('CT20260712001', 5, 9, '2026-07-12', '19:00', '21:00', 2, 840000, 'completed', 'momo', 'paid'),
('CT20260713001', 14, 6, '2026-07-13', '18:00', '20:00', 2, 820000, 'completed', 'momo', 'paid'),
('CT20260714001', 1, 7, '2026-07-14', '17:00', '18:00', 1, 280000, 'completed', 'cash', 'paid'),
('CT20260714002', 9, 8, '2026-07-14', '20:00', '22:00', 2, 760000, 'completed', 'momo', 'paid'),
('CT20260715001', 3, 9, '2026-07-15', '06:00', '07:00', 1, 300000, 'confirmed', 'cash', 'unpaid'),
('CT20260715002', 5, 6, '2026-07-15', '19:00', '20:00', 1, 420000, 'confirmed', 'momo', 'paid'),
('CT20260715003', 15, 7, '2026-07-15', '08:00', '10:00', 2, 1600000, 'confirmed', 'momo', 'paid'),
-- Đặt sắp tới
('CT20260716001', 1, 6, '2026-07-16', '18:00', '19:00', 1, 280000, 'confirmed', 'momo', 'paid'),
('CT20260716002', 9, 7, '2026-07-16', '17:00', '19:00', 2, 760000, 'pending', 'cash', 'unpaid'),
('CT20260717001', 14, 8, '2026-07-17', '19:00', '21:00', 2, 820000, 'confirmed', 'momo', 'paid'),
('CT20260718001', 3, 9, '2026-07-18', '07:00', '08:00', 1, 300000, 'pending', 'momo', 'unpaid'),
('CT20260720001', 5, 6, '2026-07-20', '20:00', '22:00', 2, 840000, 'confirmed', 'momo', 'paid');


-- ============================================================
-- 5. PAYMENTS — Lịch sử thanh toán MoMo
-- ============================================================
INSERT INTO `payments` (`booking_id`, `user_id`, `amount`, `method`, `momo_order_id`, `momo_trans_id`, `momo_result_code`, `momo_message`, `status`, `paid_at`) VALUES
(1, 6, 280000, 'momo', 'MOMO20260710001', '3019029281', 0, 'Thành công', 'success', '2026-07-10 17:45:12'),
(2, 7, 760000, 'momo', 'MOMO20260710002', '3019029312', 0, 'Thành công', 'success', '2026-07-10 16:52:33'),
(4, 9, 840000, 'momo', 'MOMO20260712001', '3019029445', 0, 'Thành công', 'success', '2026-07-12 18:41:07'),
(5, 6, 820000, 'momo', 'MOMO20260713001', '3019029567', 0, 'Thành công', 'success', '2026-07-13 17:38:22'),
(9, 6, 420000, 'momo', 'MOMO20260715002', '3019029789', 0, 'Thành công', 'success', '2026-07-15 18:30:00'),
(10, 7, 1600000, 'momo', 'MOMO20260715003', '3019029812', 0, 'Thành công', 'success', '2026-07-15 07:55:41');


-- ============================================================
-- 6. REVIEWS — Đánh giá sân
-- ============================================================
INSERT INTO `reviews` (`field_id`, `user_id`, `booking_id`, `rating`, `title`, `comment`, `helpful`, `is_verified`) VALUES
(1, 6, 1, 5, 'Sân rất tốt!', 'Cỏ nhân tạo mới, bám chân tốt. Đèn sáng đẹp ban đêm. Chủ sân nhiệt tình. Sẽ quay lại!', 12, 1),
(9, 7, 2, 5, 'Sân đẹp nhất Cái Răng', 'Sân rộng rãi, hệ thống điện tử hiện đại. Bãi xe thoải mái. Giá hơi cao nhưng xứng đáng.', 18, 1),
(3, 8, 3, 4, 'Khá ổn', 'Sân sạch sẽ, có phòng thay đồ. Giá phù hợp. Hơi khó tìm lần đầu vì ngõ hẹp.', 7, 1),
(5, 9, 4, 5, 'Tuyệt vời!', 'Cụm sân chuyên nghiệp, nhân viên thân thiện. Camera an ninh đầy đủ. Đặt online tiện lợi.', 23, 1),
(14, 6, 5, 5, 'Sân đẹp, đèn siêu sáng', 'Đèn pha sáng như ban ngày, đá 10h đêm vẫn rõ ràng. Cỏ tốt, không bị trơn. Rất hài lòng!', 15, 1),
(1, 7, 6, 4, 'Sân ổn, giá tốt', 'Vị trí thuận tiện, đặt sân dễ. Sân sạch sẽ. Chỉ hơi ồn vì gần đường lớn.', 5, 1),
(9, 8, 7, 5, 'Quá đỉnh', 'Sân mới, cỏ xanh mượt. Phục vụ chuyên nghiệp. Màn hình điện tử rất cool. 10/10!', 31, 1),
-- Review không kèm booking
(2, 9, NULL, 4, 'Vị trí đẹp gần sông', 'Không khí thoáng mát, gần sông Hậu. Sân tuy nhỏ nhưng đủ tiêu chuẩn chơi.', 9, 0),
(7, 6, NULL, 4, 'Sân Tây Đô được lắm', 'Cái Răng mà có sân này là tốt rồi. Giá cả hợp lý, nhân viên dễ tính.', 6, 0),
(15, 7, NULL, 5, 'Sân 11 người chuẩn nhất Cần Thơ', 'Đúng là chuẩn FIFA, đá trên sân này khác hẳn. Phòng VIP xịn sò. Giá thuê cao nhưng trải nghiệm đáng tiền.', 42, 0);


-- ============================================================
-- 7. EVENTS — Sự kiện / Giải đấu
-- ============================================================
INSERT INTO `events` (`field_id`, `organizer_id`, `title`, `slug`, `description`, `event_type`, `start_datetime`, `end_datetime`, `max_teams`, `current_teams`, `entry_fee`, `prize_pool`, `cover_image`, `status`) VALUES
(9, 5, 'Giải Bóng Đá Mini Cái Răng Mở Rộng 2026',
 'giai-bong-da-mini-cai-rang-mo-rong-2026',
 'Giải đấu bóng đá 5 người dành cho các CLB phong trào tại Quận Cái Răng. Thể thức thi đấu vòng tròn - loại trực tiếp. Giải thưởng hấp dẫn cho 3 đội nhất nhì ba.',
 'giai_dau', '2026-07-25 07:00:00', '2026-07-25 20:00:00',
 16, 11, 500000, 15000000, 'giai-bong-mini-cai-rang.jpg', 'upcoming'),

(14, 4, 'Giải Bóng Đá 7 Người Bình Thủy Cup 2026',
 'giai-bong-da-7-nguoi-binh-thuy-cup-2026',
 'Sự kiện thường niên của Sân Phi Long, quy tụ các đội mạnh toàn Cần Thơ. Sân cỏ nhân tạo FIFA Basic. Có trọng tài chuyên nghiệp, bình luận viên.',
 'giai_dau', '2026-08-02 06:00:00', '2026-08-03 21:00:00',
 8, 6, 1000000, 30000000, 'binh-thuy-cup.jpg', 'upcoming'),

(5, 2, 'Đêm Giao Hữu Ninh Kiều - Cái Răng',
 'dem-giao-huu-ninh-kieu-cai-rang',
 'Trận giao hữu thân thiện giữa các đội bóng Quận Ninh Kiều và Cái Răng. Mục đích giao lưu, kết bạn. Không phân thắng thua, có phần ăn uống sau trận.',
 'friendly', '2026-07-22 18:00:00', '2026-07-22 22:00:00',
 4, 3, 0, NULL, 'dem-giao-huu.jpg', 'upcoming'),

(15, 4, 'Giải Vô Địch Cần Thơ Sân 11 Người 2026',
 'giai-vo-dich-can-tho-san-11-nguoi-2026',
 'Giải đấu lớn nhất năm tại An Hòa Sport Center. Sân 11 người chuẩn FIFA. Mời các CLB chuyên nghiệp và bán chuyên. Phát sóng trực tiếp trên Facebook.',
 'giai_dau', '2026-08-15 07:00:00', '2026-08-16 18:00:00',
 12, 8, 2000000, 50000000, 'vo-dich-can-tho.jpg', 'upcoming');


-- ============================================================
-- 8. NOTIFICATIONS — Thông báo mẫu
-- ============================================================
INSERT INTO `notifications` (`user_id`, `type`, `title`, `body`, `ref_type`, `ref_id`, `icon`, `is_read`) VALUES
(6, 'booking_confirmed', '✅ Đặt sân thành công!',
 'Bạn đã đặt Sân Bóng Ninh Kiều Sport vào 19:00 ngày 15/07/2026. Mã đặt: CT20260715002', 'booking', 9, 'check-circle', 0),
(7, 'booking_confirmed', '✅ Đặt sân thành công!',
 'Bạn đã đặt Sân An Hòa Sport Center vào 08:00 ngày 15/07/2026. Mã đặt: CT20260715003', 'booking', 10, 'check-circle', 0),
(6, 'event_reminder', '⚽ Giải đấu sắp diễn ra!',
 'Giải Bóng Đá Mini Cái Răng Mở Rộng 2026 sẽ diễn ra vào 25/07/2026. Đăng ký ngay!', 'event', 1, 'trophy', 0),
(7, 'event_reminder', '⚽ Giải đấu sắp diễn ra!',
 'Đêm Giao Hữu Ninh Kiều - Cái Răng diễn ra ngày 22/07/2026 lúc 18:00.', 'event', 3, 'trophy', 1),
(8, 'new_message', '💬 Tin nhắn mới từ chủ sân',
 'Chủ sân Ninh Kiều Sport vừa trả lời tin nhắn của bạn.', 'message', NULL, 'message-circle', 0);


-- ============================================================
-- 9. FAVORITES — Sân yêu thích
-- ============================================================
INSERT INTO `favorites` (`user_id`, `field_id`) VALUES
(6, 1), (6, 5), (6, 9),
(7, 3), (7, 9), (7, 14),
(8, 5), (8, 15),
(9, 9), (9, 11);


-- ============================================================
-- 10. FIELD_UNAVAILABLE — Giờ không khả dụng mẫu
-- ============================================================
INSERT INTO `field_unavailable` (`field_id`, `unavail_date`, `start_time`, `end_time`, `reason`) VALUES
(9, '2026-07-25', '06:00', '20:00', 'Giải đấu Mini Cái Răng Mở Rộng 2026'),
(14, '2026-08-02', '06:00', '23:00', 'Bình Thủy Cup 2026 - Ngày 1'),
(14, '2026-08-03', '06:00', '23:00', 'Bình Thủy Cup 2026 - Ngày 2'),
(15, '2026-08-15', '07:00', '18:00', 'Giải Vô Địch Cần Thơ 2026 - Ngày 1'),
(15, '2026-08-16', '07:00', '18:00', 'Giải Vô Địch Cần Thơ 2026 - Ngày 2'),
(3, '2026-07-18', '14:00', '17:00', 'Bảo trì cỏ nhân tạo định kỳ');


-- ============================================================
-- Cập nhật rating tổng hợp
-- ============================================================
UPDATE fields SET rating = 4.5, total_reviews = 2  WHERE id = 1;
UPDATE fields SET rating = 4.2, total_reviews = 1  WHERE id = 2;
UPDATE fields SET rating = 4.0, total_reviews = 1  WHERE id = 3;
UPDATE fields SET rating = 4.6, total_reviews = 2  WHERE id = 5;
UPDATE fields SET rating = 4.8, total_reviews = 2  WHERE id = 9;
UPDATE fields SET rating = 5.0, total_reviews = 1  WHERE id = 10 OR id = 11 OR id = 12;
UPDATE fields SET rating = 4.7, total_reviews = 1  WHERE id = 14;
UPDATE fields SET rating = 4.9, total_reviews = 1  WHERE id = 15;
