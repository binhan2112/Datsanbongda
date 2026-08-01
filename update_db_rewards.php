<?php
require_once 'config/db.php';

try {
    $sql = "
    -- Bảng rewards
    CREATE TABLE IF NOT EXISTS `rewards` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `owner_id` INT(11) DEFAULT NULL COMMENT 'NULL nếu là phần quà của Admin (toàn hệ thống)',
      `name` VARCHAR(255) NOT NULL,
      `description` TEXT DEFAULT NULL,
      `points_required` INT(11) NOT NULL DEFAULT 0,
      `quantity` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng còn lại',
      `image_url` VARCHAR(255) DEFAULT NULL,
      `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_owner` (`owner_id`),
      CONSTRAINT `fk_reward_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Bảng reward_exchanges
    CREATE TABLE IF NOT EXISTS `reward_exchanges` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `reward_id` INT(11) NOT NULL,
      `points_used` INT(11) NOT NULL,
      `status` ENUM('pending', 'approved', 'rejected', 'delivered') NOT NULL DEFAULT 'pending',
      `exchange_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_user` (`user_id`),
      KEY `idx_reward` (`reward_id`),
      CONSTRAINT `fk_exchange_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_exchange_reward` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "Bảng rewards và reward_exchanges đã được tạo thành công.\n";
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
?>
