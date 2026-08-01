<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';
require_once '../config/facebook_auth.php';
redirect_if_logged_in();

// Tạo một chuỗi ngẫu nhiên chống CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['facebook_oauth_state'] = $state;

// Xây dựng URL chuyển hướng sang trang đăng nhập của Facebook
$params = [
    'client_id' => FACEBOOK_APP_ID,
    'redirect_uri' => FACEBOOK_REDIRECT_URI,
    'state' => $state,
    'scope' => 'email,public_profile', // Yêu cầu quyền lấy email và thông tin cơ bản
];

$login_url = FACEBOOK_AUTH_URL . '?' . http_build_query($params);

// Chuyển hướng người dùng sang Facebook
header('Location: ' . $login_url);
exit;
?>
