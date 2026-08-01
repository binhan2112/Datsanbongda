<?php
require_once '../includes/auth_helper.php';
require_once '../config/google_auth.php';
redirect_if_logged_in();

// Tao chuoi state de chong CSRF
$_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));

$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online',
    'state' => $_SESSION['google_oauth_state'],
    'prompt' => 'select_account'
];

$login_url = GOOGLE_AUTH_URL . '?' . http_build_query($params);
header('Location: ' . filter_var($login_url, FILTER_SANITIZE_URL));
exit;
?>
