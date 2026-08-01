<?php
/**
 * Cấu hình Facebook Login
 * Vui lòng điền thông tin App ID và App Secret lấy từ Facebook Developers.
 */

define('FACEBOOK_APP_ID', '5356284117930730');
define('FACEBOOK_APP_SECRET', '48ab7fd43c70fdbda1c32770c8e6a87c');
define('FACEBOOK_REDIRECT_URI', 'http://localhost/Datsanbongda/auth/facebook_callback.php');
define('FACEBOOK_API_VERSION', 'v19.0'); // Sử dụng API version mới nhất

// URL yêu cầu xác thực OAuth của Facebook
define('FACEBOOK_AUTH_URL', 'https://www.facebook.com/' . FACEBOOK_API_VERSION . '/dialog/oauth');
// URL lấy Access Token
define('FACEBOOK_TOKEN_URL', 'https://graph.facebook.com/' . FACEBOOK_API_VERSION . '/oauth/access_token');
// URL lấy thông tin người dùng
define('FACEBOOK_USERINFO_URL', 'https://graph.facebook.com/' . FACEBOOK_API_VERSION . '/me?fields=id,name,email,picture.type(large)');
?>
