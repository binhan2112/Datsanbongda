<?php
/**
 * Cau hinh Google OAuth 2.0 cho chuc nang Dang nhap bang Google.
 * Vui long dien thong tin Client ID va Client Secret lay tu Google Cloud Console.
 */

define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/Datsanbongda/auth/google_callback.php');

// URL yeu cau xac thuc OAuth
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
// URL lay Token
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
// URL lay thong tin User
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');
?>
