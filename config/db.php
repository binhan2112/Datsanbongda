<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình thông số cơ sở dữ liệu
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'football_booking');
define('DB_PORT', 3306);

try {
    // Thiết lập chuỗi kết nối DSN (Data Source Name)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4";
    
    // Các tùy chọn cấu hình PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Bắt lỗi dạng Exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Trả về dạng mảng kết hợp
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Sử dụng Prepared Statement thực tế
    ];

    // Khởi tạo đối tượng kết nối PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Xử lý khi kết nối thất bại
    die("Kết nối cơ sở dữ liệu thất bại: " . $e->getMessage());
}
?>
