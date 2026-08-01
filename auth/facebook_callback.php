<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';
require_once '../config/facebook_auth.php';
redirect_if_logged_in();

if (isset($_GET['error'])) {
    header("Location: login.php?error=Nguoi dung tu choi cap quyen hoac co loi xay ra tu Facebook.");
    exit;
}

if (!isset($_GET['code'])) {
    header("Location: login.php");
    exit;
}

// Kiểm tra CSRF state
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['facebook_oauth_state'])) {
    unset($_SESSION['facebook_oauth_state']);
    header("Location: login.php?error=Loi xac thuc CSRF.");
    exit;
}

// 1. Đổi mã (code) lấy Access Token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, FACEBOOK_TOKEN_URL . '?' . http_build_query([
    'client_id' => FACEBOOK_APP_ID,
    'client_secret' => FACEBOOK_APP_SECRET,
    'redirect_uri' => FACEBOOK_REDIRECT_URI,
    'code' => $_GET['code'],
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (isset($token_data['error']) || !isset($token_data['access_token'])) {
    header("Location: login.php?error=Khong the lay Token tu Facebook.");
    exit;
}

$access_token = $token_data['access_token'];

// 2. Lấy thông tin người dùng từ Facebook
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, FACEBOOK_USERINFO_URL . '&access_token=' . $access_token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response_user = curl_exec($ch);
curl_close($ch);

$user_info = json_decode($response_user, true);

if (!isset($user_info['id'])) {
    header("Location: login.php?error=Khong the lay thong tin nguoi dung tu Facebook.");
    exit;
}

$facebook_id = $user_info['id'];
$full_name = $user_info['name'] ?? 'Facebook User';
$avatar = isset($user_info['picture']['data']['url']) ? $user_info['picture']['data']['url'] : 'default-avatar.png';
$email = $user_info['email'] ?? ($facebook_id . '@facebook.com'); // Nếu user không share email, tạo dummy email

try {
    // 3. Kiểm tra xem facebook_id hoặc email đã tồn tại trong hệ thống chưa
    $stmt = $pdo->prepare("SELECT * FROM users WHERE facebook_id = :fid OR email = :email LIMIT 1");
    $stmt->execute(['fid' => $facebook_id, 'email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_active'] == 0) {
            header("Location: login.php?error=Tai khoan cua ban dang bi khoa.");
            exit;
        }
        
        $updateQuery = "UPDATE users SET last_online = NOW()";
        $updateParams = ['id' => $user['id']];
        
        if (empty($user['facebook_id'])) {
            $updateQuery .= ", facebook_id = :facebook_id";
            $updateParams['facebook_id'] = $facebook_id;
        }
        if ($user['avatar'] === 'default-avatar.png' && $avatar !== 'default-avatar.png') {
            $updateQuery .= ", avatar = :avatar";
            $updateParams['avatar'] = $avatar;
        }
        $updateQuery .= " WHERE id = :id";
        
        $pdo->prepare($updateQuery)->execute($updateParams);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_full_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        if ($user['role'] === 'admin') { header("Location: ../admin/index.php"); }
        elseif ($user['role'] === 'owner') { header("Location: ../owner/index.php"); }
        else { header("Location: ../index.php"); }
        exit;

    } else {
        $stmt_insert = $pdo->prepare("
            INSERT INTO users (full_name, email, facebook_id, avatar, role, email_verified, is_active, phone, password_hash)
            VALUES (:fn, :em, :fid, :av, 'customer', 1, 1, :ph, NULL)
        ");
        
        $dummy_phone = 'FB' . substr(str_shuffle("0123456789"), 0, 8);
        
        $stmt_insert->execute([
            'fn' => $full_name,
            'em' => $email,
            'fid' => $facebook_id,
            'av' => $avatar,
            'ph' => $dummy_phone
        ]);

        $new_user_id = $pdo->lastInsertId();

        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['user_full_name'] = $full_name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'customer';

        header("Location: ../index.php");
        exit;
    }

} catch (PDOException $e) {
    header("Location: login.php?error=" . urlencode('Loi he thong: ' . $e->getMessage()));
    exit;
}
?>
