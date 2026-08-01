<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';
require_once '../config/google_auth.php';
redirect_if_logged_in();

if (isset($_GET['error'])) {
    // Nguoi dung tu choi hoac co loi
    header("Location: login.php?error=Nguoi dung tu choi cap quyen hoac co loi xay ra.");
    exit;
}

if (!isset($_GET['code'])) {
    header("Location: login.php");
    exit;
}

// Kiem tra CSRF state
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['google_oauth_state'])) {
    unset($_SESSION['google_oauth_state']);
    header("Location: login.php?error=Loi xac thuc CSRF.");
    exit;
}

// 1. Doi ma (code) lay Access Token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'code' => $_GET['code'],
    'grant_type' => 'authorization_code'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Moi truong dev (localhost) thuong dung SSL tu chung nhan
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (isset($token_data['error']) || !isset($token_data['access_token'])) {
    header("Location: login.php?error=Khong the lay Token tu Google.");
    exit;
}

$access_token = $token_data['access_token'];

// 2. Lay thong tin nguoi dung tu Google
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_USERINFO_URL);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response_user = curl_exec($ch);
curl_close($ch);

$user_info = json_decode($response_user, true);

if (!isset($user_info['email'])) {
    header("Location: login.php?error=Khong the lay thong tin Email tu Google.");
    exit;
}

// Thong tin tra ve tu Google
$google_id = $user_info['id'];
$email = $user_info['email'];
$full_name = $user_info['name'] ?? 'Google User';
$avatar = $user_info['picture'] ?? 'default-avatar.png';

try {
    // 3. Kiem tra xem email nay da ton tai trong he thong chua
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Tai khoan da ton tai (co the dang ky thu cong tu truoc hoac da dang nhap Google)
        if ($user['is_active'] == 0) {
            header("Location: login.php?error=Tai khoan cua ban dang bi khoa.");
            exit;
        }
        
        // Cap nhat google_id va avatar neu chua co
        $updateQuery = "UPDATE users SET last_online = NOW()";
        $updateParams = ['id' => $user['id']];
        
        if (empty($user['google_id'])) {
            $updateQuery .= ", google_id = :google_id";
            $updateParams['google_id'] = $google_id;
        }
        if ($user['avatar'] === 'default-avatar.png' && $avatar !== 'default-avatar.png') {
            $updateQuery .= ", avatar = :avatar";
            $updateParams['avatar'] = $avatar;
        }
        $updateQuery .= " WHERE id = :id";
        
        $pdo->prepare($updateQuery)->execute($updateParams);

        // Luu session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_full_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['show_onboarding'] = true;
        
        if ($user['role'] === 'admin') { header("Location: ../admin/index.php"); }
        elseif ($user['role'] === 'owner') { header("Location: ../owner/index.php"); }
        else { header("Location: ../index.php"); }
        exit;

    } else {
        // Tai khoan chua ton tai -> Tu dong dang ky
        $stmt_insert = $pdo->prepare("
            INSERT INTO users (full_name, email, google_id, avatar, role, email_verified, is_active, phone, password_hash)
            VALUES (:fn, :em, :gid, :av, 'customer', 1, 1, :ph, NULL)
        ");
        
        // So dien thoai khong the trong (NOT NULL trong schema hien tai)
        // Tao 1 so dt gia tu google_id hoac yeu cau bo sung sau (Tam thoi de trong bang cach tao chuoi ngau nhien co prefix GG)
        $dummy_phone = 'GG' . substr(str_shuffle("0123456789"), 0, 8);
        
        $stmt_insert->execute([
            'fn' => $full_name,
            'em' => $email,
            'gid' => $google_id,
            'av' => $avatar,
            'ph' => $dummy_phone
        ]);

        $new_user_id = $pdo->lastInsertId();

        // Luu session
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['user_full_name'] = $full_name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'customer';
        $_SESSION['show_onboarding'] = true;

        header("Location: ../index.php");
        exit;
    }

} catch (PDOException $e) {
    header("Location: login.php?error=" . urlencode('Loi he thong: ' . $e->getMessage()));
    exit;
}
?>
