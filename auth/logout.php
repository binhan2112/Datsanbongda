<?php
// Gọi tệp helper xác thực để khởi động session nếu chưa có
require_once '../includes/auth_helper.php';

// Xóa toàn bộ các biến trong Session
$_SESSION = array();

// Nếu sử dụng cookie session, xóa cả cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy Session
session_destroy();

// Chuyển hướng người dùng về trang chủ
header("Location: ../index.php");
exit;
?>
