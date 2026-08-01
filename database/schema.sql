-- ============================================================
-- HỆ THỐNG ĐẶT SÂN BÓNG ĐÁ CẦN THƠ
-- Database Schema v1.0
-- Tạo ngày: 2026-07-15
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS `football_booking`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `football_booking`;

-- ============================================================
-- BẢNG 1: USERS — Người dùng hệ thống
-- ============================================================
CREATE TABLE `users` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `full_name`     VARCHAR(100) NOT NULL,
  `email`         VARCHAR(100) NOT NULL,
  `google_id`     VARCHAR(255) DEFAULT NULL,
  `facebook_id`   VARCHAR(255) DEFAULT NULL,
  `phone`         VARCHAR(15)  NOT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `role`          ENUM('customer','owner','admin') NOT NULL DEFAULT 'customer',
  `avatar`        VARCHAR(255) DEFAULT 'default-avatar.png',
  `address`       VARCHAR(255) DEFAULT NULL,
  `lat`           DECIMAL(10,8) DEFAULT NULL  COMMENT 'Vĩ độ địa chỉ nhà',
  `lng`           DECIMAL(11,8) DEFAULT NULL  COMMENT 'Kinh độ địa chỉ nhà',
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `email_verified` TINYINT(1)  NOT NULL DEFAULT 0,
  `points`         INT(11)      NOT NULL DEFAULT 0 COMMENT 'Điểm tích lũy tích được',
  `last_online`   DATETIME     DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_google_id` (`google_id`),
  UNIQUE KEY `uq_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 2: FIELDS — Sân bóng đá
-- ============================================================
CREATE TABLE `fields` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `owner_id`        INT(11)      NOT NULL,
  `name`            VARCHAR(150) NOT NULL,
  `slug`            VARCHAR(160) NOT NULL,
  `address`         VARCHAR(255) NOT NULL,
  `district`        VARCHAR(100) NOT NULL  COMMENT 'Quận/Huyện tại Cần Thơ',
  `ward`            VARCHAR(100) DEFAULT NULL COMMENT 'Phường/Xã',
  `lat`             DECIMAL(10,8) NOT NULL  COMMENT 'Vĩ độ GPS',
  `lng`             DECIMAL(11,8) NOT NULL  COMMENT 'Kinh độ GPS',
  `type`            ENUM('5v5','7v7','11v11') NOT NULL DEFAULT '5v5',
  `surface`         ENUM('co_nhan_tao','co_tu_nhien','san_xi_mang') NOT NULL DEFAULT 'co_nhan_tao',
  `price_per_hour`  DECIMAL(10,0) NOT NULL  COMMENT 'Giá thuê/giờ (VNĐ)',
  `price_peak`      DECIMAL(10,0) DEFAULT NULL COMMENT 'Giá giờ cao điểm (17h-21h)',
  `description`     TEXT          DEFAULT NULL,
  `phone`           VARCHAR(15)   DEFAULT NULL,
  `open_time`       TIME          NOT NULL DEFAULT '06:00:00',
  `close_time`      TIME          NOT NULL DEFAULT '22:00:00',
  `has_lighting`    TINYINT(1)    NOT NULL DEFAULT 1  COMMENT 'Có đèn chiếu sáng',
  `has_parking`     TINYINT(1)    NOT NULL DEFAULT 1  COMMENT 'Có bãi đỗ xe',
  `has_shower`      TINYINT(1)    NOT NULL DEFAULT 0  COMMENT 'Có phòng tắm',
  `has_canteen`     TINYINT(1)    NOT NULL DEFAULT 0  COMMENT 'Có căn tin',
  `has_rental`      TINYINT(1)    NOT NULL DEFAULT 0  COMMENT 'Cho thuê dụng cụ',
  `cover_image`     VARCHAR(255)  DEFAULT NULL,
  `status`          ENUM('active','inactive','pending') NOT NULL DEFAULT 'pending',
  `discount_percent` INT(11)     NOT NULL DEFAULT 0  COMMENT 'Mức giảm giá (%)',
  `rating`          DECIMAL(2,1)  NOT NULL DEFAULT 0.0,
  `total_reviews`   INT(11)       NOT NULL DEFAULT 0,
  `total_bookings`  INT(11)       NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_owner`    (`owner_id`),
  KEY `idx_district` (`district`),
  KEY `idx_type`     (`type`),
  KEY `idx_status`   (`status`),
  CONSTRAINT `fk_field_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 3: FIELD_IMAGES — Ảnh sân bóng
-- ============================================================
CREATE TABLE `field_images` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `field_id`   INT(11)      NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `caption`    VARCHAR(150) DEFAULT NULL,
  `sort_order` TINYINT(3)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_field` (`field_id`),
  CONSTRAINT `fk_image_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 4: BOOKINGS — Đơn đặt sân
-- ============================================================
CREATE TABLE `bookings` (
  `id`           INT(11)       NOT NULL AUTO_INCREMENT,
  `booking_code` VARCHAR(20)   NOT NULL  COMMENT 'Mã đặt sân VD: CT20260715001',
  `field_id`     INT(11)       NOT NULL,
  `user_id`      INT(11)       NOT NULL,
  `booking_date` DATE          NOT NULL,
  `start_time`   TIME          NOT NULL,
  `end_time`     TIME          NOT NULL,
  `duration`     DECIMAL(3,1)  NOT NULL DEFAULT 1 COMMENT 'Số giờ',
  `total_price`  DECIMAL(12,0) NOT NULL,
  `status`       ENUM('pending','confirmed','cancelled','completed','no_show') NOT NULL DEFAULT 'pending',
  `payment_method` ENUM('cash','momo','bank','vnpay') NOT NULL DEFAULT 'cash',
  `payment_status` ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `points_used`     INT(11)      NOT NULL DEFAULT 0  COMMENT 'Số điểm tích lũy đã dùng',
  `discount_amount` DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Số tiền được giảm từ điểm tích lũy',
  `points_earned`   INT(11)      NOT NULL DEFAULT 0  COMMENT 'Số điểm tích lũy nhận được',
  `note`         TEXT          DEFAULT NULL,
  `qr_code`      VARCHAR(255)  DEFAULT NULL  COMMENT 'Mã QR check-in',
  `cancelled_at` DATETIME      DEFAULT NULL,
  `cancel_reason` VARCHAR(255) DEFAULT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_code` (`booking_code`),
  KEY `idx_field_date` (`field_id`, `booking_date`),
  KEY `idx_user`       (`user_id`),
  KEY `idx_status`     (`status`),
  CONSTRAINT `fk_booking_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`),
  CONSTRAINT `fk_booking_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 5: PAYMENTS — Lịch sử thanh toán MoMo
-- ============================================================
CREATE TABLE `payments` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `booking_id`      INT(11)       NOT NULL,
  `user_id`         INT(11)       NOT NULL,
  `amount`          DECIMAL(12,0) NOT NULL,
  `method`          ENUM('momo','bank','cash','vnpay') NOT NULL,
  `momo_order_id`   VARCHAR(50)   DEFAULT NULL,
  `momo_request_id` VARCHAR(50)   DEFAULT NULL,
  `momo_trans_id`   VARCHAR(50)   DEFAULT NULL  COMMENT 'Transaction ID từ MoMo',
  `momo_result_code` INT(6)       DEFAULT NULL,
  `momo_message`    VARCHAR(255)  DEFAULT NULL,
  `vnpay_txn_ref`   VARCHAR(50)   DEFAULT NULL  COMMENT 'Mã giao dịch VNPay',
  `vnpay_trans_no`  VARCHAR(50)   DEFAULT NULL  COMMENT 'Mã giao dịch tại hệ thống VNPay',
  `vnpay_response_code` VARCHAR(10) DEFAULT NULL,
  `status`          ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
  `paid_at`         DATETIME      DEFAULT NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking`  (`booking_id`),
  KEY `idx_order_id` (`momo_order_id`),
  CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 6: REVIEWS — Đánh giá sân
-- ============================================================
CREATE TABLE `reviews` (
  `id`          INT(11)    NOT NULL AUTO_INCREMENT,
  `field_id`    INT(11)    NOT NULL,
  `user_id`     INT(11)    NOT NULL,
  `booking_id`  INT(11)    DEFAULT NULL,
  `rating`      TINYINT(1) NOT NULL COMMENT '1-5 sao',
  `title`       VARCHAR(150) DEFAULT NULL,
  `comment`     TEXT       DEFAULT NULL,
  `helpful`     INT(11)    NOT NULL DEFAULT 0 COMMENT 'Lượt "hữu ích"',
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Đã chơi thực tế',
  `created_at`  TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_booking` (`user_id`, `booking_id`),
  KEY `idx_field`  (`field_id`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `fk_review_field`   FOREIGN KEY (`field_id`)   REFERENCES `fields`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 7: REVIEW_IMAGES — Ảnh kèm đánh giá
-- ============================================================
CREATE TABLE `review_images` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `review_id`   INT(11)      NOT NULL,
  `image_path`  VARCHAR(255) NOT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_review` (`review_id`),
  CONSTRAINT `fk_ri_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 8: MESSAGES — Chat khách hàng ↔ chủ sân
-- ============================================================
CREATE TABLE `messages` (
  `id`          INT(11)   NOT NULL AUTO_INCREMENT,
  `field_id`    INT(11)   DEFAULT NULL COMMENT 'Chat gắn với sân cụ thể (NULL nếu chat trực tiếp với Admin)',
  `sender_id`   INT(11)   NOT NULL,
  `receiver_id` INT(11)   NOT NULL,
  `content`     TEXT      NOT NULL,
  `msg_type`    ENUM('text','image','booking_ref') NOT NULL DEFAULT 'text',
  `ref_id`      INT(11)   DEFAULT NULL COMMENT 'booking_id nếu là booking_ref',
  `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conversation` (`field_id`, `sender_id`, `receiver_id`),
  KEY `idx_receiver`     (`receiver_id`, `is_read`),
  CONSTRAINT `fk_msg_field`    FOREIGN KEY (`field_id`)    REFERENCES `fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender`   FOREIGN KEY (`sender_id`)   REFERENCES `users`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 9: NOTIFICATIONS — Thông báo trong app
-- ============================================================
CREATE TABLE `notifications` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)      NOT NULL,
  `type`        VARCHAR(50)  NOT NULL COMMENT 'booking_confirmed, new_message, event_reminder...',
  `title`       VARCHAR(150) NOT NULL,
  `body`        TEXT         NOT NULL,
  `ref_type`    VARCHAR(50)  DEFAULT NULL COMMENT 'booking, message, event...',
  `ref_id`      INT(11)      DEFAULT NULL,
  `icon`        VARCHAR(50)  DEFAULT 'bell',
  `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`, `is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 10: EVENTS — Sự kiện / Giải đấu
-- ============================================================
CREATE TABLE `events` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `field_id`        INT(11)       NOT NULL,
  `organizer_id`    INT(11)       NOT NULL,
  `title`           VARCHAR(200)  NOT NULL,
  `slug`            VARCHAR(210)  NOT NULL,
  `description`     TEXT          DEFAULT NULL,
  `event_type`      ENUM('giai_dau','friendly','training','other') NOT NULL DEFAULT 'giai_dau',
  `start_datetime`  DATETIME      NOT NULL,
  `end_datetime`    DATETIME      NOT NULL,
  `max_teams`       TINYINT(3)    DEFAULT NULL COMMENT 'Số đội tối đa',
  `current_teams`   TINYINT(3)    NOT NULL DEFAULT 0,
  `entry_fee`       DECIMAL(10,0) NOT NULL DEFAULT 0 COMMENT 'Phí tham gia (VNĐ)',
  `prize_pool`      DECIMAL(12,0) DEFAULT NULL COMMENT 'Giải thưởng',
  `cover_image`     VARCHAR(255)  DEFAULT NULL,
  `status`          ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_slug` (`slug`),
  KEY `idx_field`   (`field_id`),
  KEY `idx_start`   (`start_datetime`),
  KEY `idx_status`  (`status`),
  CONSTRAINT `fk_event_field`     FOREIGN KEY (`field_id`)     REFERENCES `fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_organizer` FOREIGN KEY (`organizer_id`) REFERENCES `users`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 11: EVENT_REGISTRATIONS — Đăng ký tham gia sự kiện
-- ============================================================
CREATE TABLE `event_registrations` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `event_id`   INT(11)      NOT NULL,
  `user_id`    INT(11)      NOT NULL,
  `team_name`  VARCHAR(100) NOT NULL,
  `contact`    VARCHAR(15)  NOT NULL,
  `status`     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `paid`       TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_user` (`event_id`, `user_id`),
  KEY `idx_event` (`event_id`),
  CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reg_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 12: FAVORITES — Sân yêu thích
-- ============================================================
CREATE TABLE `favorites` (
  `id`         INT(11)   NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)   NOT NULL,
  `field_id`   INT(11)   NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_field` (`user_id`, `field_id`),
  KEY `idx_user`  (`user_id`),
  KEY `idx_field` (`field_id`),
  CONSTRAINT `fk_fav_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- BẢNG 13: FIELD_UNAVAILABLE — Khung giờ không khả dụng
-- (chủ sân chặn giờ bảo trì, sự kiện riêng...)
-- ============================================================
CREATE TABLE `field_unavailable` (
  `id`          INT(11)   NOT NULL AUTO_INCREMENT,
  `field_id`    INT(11)   NOT NULL,
  `unavail_date` DATE     NOT NULL,
  `start_time`  TIME      NOT NULL,
  `end_time`    TIME      NOT NULL,
  `reason`      VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_field_date` (`field_id`, `unavail_date`),
  CONSTRAINT `fk_unavail_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- VIEW: Thống kê đặt sân theo sân
-- ============================================================
CREATE VIEW `v_field_stats` AS
  SELECT
    f.id,
    f.name,
    f.district,
    COUNT(b.id)                                          AS total_bookings,
    SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) AS completed_bookings,
    SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) AS total_revenue,
    AVG(r.rating)                                        AS avg_rating,
    COUNT(DISTINCT r.id)                                 AS review_count
  FROM fields f
  LEFT JOIN bookings b ON b.field_id = f.id
  LEFT JOIN reviews  r ON r.field_id = f.id
  GROUP BY f.id, f.name, f.district;
