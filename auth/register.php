<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';
redirect_if_logged_in();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    if (empty($full_name)||empty($email)||empty($phone)||empty($password)||empty($confirm_password)) { $error = 'Vui long dien day du cac truong bat buoc.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Dinh dang Email khong hop le.'; }
    elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) { $error = 'So dien thoai phai chua 10 hoac 11 chu so.'; }
    elseif (strlen($password) < 6) { $error = 'Mat khau phai co it nhat 6 ky tu.'; }
    elseif ($password !== $confirm_password) { $error = 'Mat khau xac nhan khong trung khop.'; }
    elseif (!in_array($role, ['customer','owner'])) { $error = 'Vai tro tai khoan khong hop le.'; }
    else {
        try {
            $c1 = $pdo->prepare("SELECT id FROM users WHERE email=:e"); $c1->execute(['e'=>$email]);
            if ($c1->fetch()) { $error = 'Email nay da duoc su dung.'; }
            else { $c2=$pdo->prepare("SELECT id FROM users WHERE phone=:p"); $c2->execute(['p'=>$phone]); if($c2->fetch()){$error='So dien thoai nay da duoc su dung.';} }
            if (empty($error)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $is_active = ($role==='owner') ? 0 : 1;
                require_once '../includes/mail_helper.php';
                $otp = strval(rand(100000,999999));
                $pdo->prepare("INSERT INTO users (full_name,email,phone,password_hash,role,address,is_active,email_verified,reset_token,reset_expires) VALUES (:fn,:em,:ph,:pw,:ro,:ad,:ia,0,:ot,DATE_ADD(NOW(),INTERVAL 15 MINUTE))")->execute(['fn'=>$full_name,'em'=>$email,'ph'=>$phone,'pw'=>$hash,'ro'=>$role,'ad'=>!empty($address)?$address:null,'ia'=>$is_active,'ot'=>$otp]);
                $sent = send_otp_email($email,$otp);
                header("Location: verify-email.php?email=".urlencode($email).($sent?'':"&demo_otp=".urlencode($otp))); exit;
            }
        } catch (PDOException $e) { $error = 'Loi he thong: '.$e->getMessage(); }
    }
}

// Shared CSS string used in all auth pages
$CSS = <<<'ENDCSS'
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; font-family: 'Be Vietnam Pro', sans-serif; background: #fff; }
.auth-wrap { display: flex; height: 100vh; overflow: hidden; background: #fff; }
.auth-img { position: relative; width: 52%; flex-shrink: 0; overflow: visible; display: none; }
.auth-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: 30% center; display: block; }
.auth-img .tint { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(20,5,30,0.55) 0%, rgba(10,5,15,0.2) 60%, rgba(255,255,255,0) 100%); z-index: 1; }
.auth-img .fade-right { position: absolute; inset: 0; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0) 55%, rgba(255,255,255,0.6) 75%, rgba(255,255,255,1) 100%); z-index: 2; }
.auth-img .badge { position: absolute; bottom: 48px; left: 44px; z-index: 5; color: #fff; }
.auth-img .badge h2 { font-size: 28px; font-weight: 900; line-height: 1.2; letter-spacing: -0.3px; text-shadow: 0 2px 20px rgba(0,0,0,0.55); }
.auth-img .badge p { margin-top: 10px; font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.72); line-height: 1.65; }
.auth-img .badge-bar { width: 44px; height: 3px; background: #e11d48; border-radius: 3px; margin-top: 16px; }
@media (min-width: 860px) { .auth-img { display: block; } }
.auth-form { flex: 1; height: 100vh; overflow-y: auto; background: #ffffff; display: flex; align-items: center; justify-content: flex-start; padding: 48px 56px; }
.form-box { width: 100%; max-width: 420px; }
.brand { display: flex; align-items: center; gap: 10px; margin-bottom: 36px; text-decoration: none; }
.brand-icon { width: 34px; height: 34px; background: #e11d48; border-radius: 9px; display: flex; align-items: center; justify-content: center; }
.brand-name { font-size: 14px; font-weight: 800; color: #111827; letter-spacing: 0.5px; text-transform: uppercase; }
.f-heading { font-size: 26px; font-weight: 800; color: #111827; letter-spacing: -0.5px; margin-bottom: 6px; }
.f-sub { font-size: 14px; color: #6b7280; margin-bottom: 28px; line-height: 1.5; }
.f-row { display: flex; gap: 12px; }
.f-row .f-group { flex: 1; }
.f-group { margin-bottom: 17px; }
.f-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 7px; }
.f-label-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px; }
.f-label-row .f-label { margin-bottom: 0; }
.f-forgot { font-size: 13px; font-weight: 600; color: #e11d48; text-decoration: none; }
.f-forgot:hover { text-decoration: underline; }
.f-input { width: 100%; padding: 12px 15px; background: #f7f8fa; border: 1.5px solid #e9eaec; border-radius: 10px; font-size: 14px; font-family: 'Be Vietnam Pro', sans-serif; color: #111827; outline: none; transition: border-color .18s, box-shadow .18s, background .18s; }
.f-input[type="password"] { letter-spacing: 2px; }
.f-input[type="password"]::placeholder { letter-spacing: 0; }
.f-input:focus { background: #fff; border-color: #e11d48; box-shadow: 0 0 0 3px rgba(225,29,72,.1); }
.f-input::placeholder { color: #b0b7c3; font-size: 14px; }
.f-input-sel { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 13px center; }
.f-check { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
.f-check input[type=checkbox] { width: 16px; height: 16px; accent-color: #e11d48; cursor: pointer; flex-shrink: 0; }
.f-check label { font-size: 13px; color: #6b7280; cursor: pointer; }
.btn-main { width: 100%; padding: 14px; background: #e11d48; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; font-family: 'Be Vietnam Pro', sans-serif; cursor: pointer; transition: background .18s, box-shadow .2s, transform .15s; box-shadow: 0 4px 18px rgba(225,29,72,.28); }
.btn-main:hover { background: #c01740; box-shadow: 0 6px 24px rgba(225,29,72,.4); transform: translateY(-1px); }
.btn-outline { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 13px; background: #fff; color: #e11d48; border: 2px solid #e11d48; border-radius: 10px; font-size: 14px; font-weight: 700; font-family: 'Be Vietnam Pro', sans-serif; cursor: pointer; text-decoration: none; transition: background .15s; }
.btn-outline:hover { background: #fff1f2; }
.divider { display: flex; align-items: center; gap: 12px; margin: 22px 0; font-size: 12px; font-weight: 600; color: #c4c9d4; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #eaecf0; }
.social-row { display: flex; gap: 10px; margin-bottom: 24px; }
.btn-social { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 11px; background: #fff; border: 1.5px solid #e9eaec; border-radius: 10px; font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; font-family: 'Be Vietnam Pro', sans-serif; transition: background .15s; }
.btn-social:hover { background: #f7f8fa; }
.btn-social img { width: 18px; height: 18px; }
.f-foot { text-align: center; font-size: 13px; color: #9ca3af; }
.f-foot a { color: #e11d48; font-weight: 700; text-decoration: none; }
.f-foot a:hover { text-decoration: underline; }
.alert { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-size: 13px; font-weight: 600; line-height: 1.5; }
.alert-ok  { background: #f0fdf4; color: #166534; border: 1.5px solid #bbf7d0; }
.alert-err { background: #fff1f2; color: #9f1239; border: 1.5px solid #fecdd3; }
.invalid-box { background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
.invalid-title { display: flex; align-items: center; gap: 10px; font-weight: 700; color: #9f1239; margin-bottom: 8px; font-size: 14px; }
.invalid-box p { font-size: 13px; color: #374151; line-height: 1.65; }
@media (max-width: 860px) { .auth-form { padding: 40px 28px; justify-content: center; } .form-box { max-width: 100%; } }
ENDCSS;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dang Ky - CanThoSport</title>
<link rel="icon" type="image/png" href="../assets/images/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style><?php echo $CSS; ?></style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-img">
        <img src="../assets/images/son_bg.png" alt="Son Heung-min">
        <div class="tint"></div>
        <div class="fade-right"></div>
        <div class="badge">
            <h2>CANTHOSPORT<br>ELITE</h2>
            <p>San dau chat luong cao<br>Dat san toan thanh pho Can Tho</p>
            <div class="badge-bar"></div>
        </div>
    </div>
    <div class="auth-form">
        <div class="form-box">
            <a href="../index.php" class="brand">
                <div class="brand-icon"><i data-lucide="zap" style="width:16px;height:16px;color:#fff"></i></div>
                <span class="brand-name">CanThoSport</span>
            </a>
            <h1 class="f-heading">Tao tai khoan moi</h1>
            <p class="f-sub">Gia nhap doi ngu — dat san nhanh — trai nghiem dinh cao.</p>
            <?php if (!empty($error)): ?>
            <div class="alert alert-err">
                <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;margin-top:1px"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>
            <form method="POST" action="register.php">
                <div class="f-row">
                    <div class="f-group">
                        <label class="f-label" for="full_name">Ho va Ten</label>
                        <input class="f-input" type="text" id="full_name" name="full_name" placeholder="Ten ban" required value="<?php echo htmlspecialchars($_POST['full_name']??''); ?>">
                    </div>
                    <div class="f-group">
                        <label class="f-label" for="role">Vai tro</label>
                        <select class="f-input f-input-sel" id="role" name="role" required>
                            <option value="customer" <?php if(isset($_POST['role'])&&$_POST['role']==='customer')echo'selected';?>>Nguoi choi</option>
                            <option value="owner" <?php if(isset($_POST['role'])&&$_POST['role']==='owner')echo'selected';?>>Chu san</option>
                        </select>
                    </div>
                </div>
                <div class="f-row">
                    <div class="f-group">
                        <label class="f-label" for="email">Email</label>
                        <input class="f-input" type="email" id="email" name="email" placeholder="example@son7.com" required value="<?php echo htmlspecialchars($_POST['email']??''); ?>">
                    </div>
                    <div class="f-group">
                        <label class="f-label" for="phone">So Dien Thoai</label>
                        <input class="f-input" type="text" id="phone" name="phone" placeholder="09..." required value="<?php echo htmlspecialchars($_POST['phone']??''); ?>">
                    </div>
                </div>
                <div class="f-group">
                    <label class="f-label" for="address">Dia Chi (Tuy chon)</label>
                    <input class="f-input" type="text" id="address" name="address" placeholder="Noi o cua ban" value="<?php echo htmlspecialchars($_POST['address']??''); ?>">
                </div>
                <div class="f-row">
                    <div class="f-group">
                        <label class="f-label" for="password">Mat Khau</label>
                        <input class="f-input" type="password" id="password" name="password" placeholder="Toi thieu 6 ky tu" required>
                    </div>
                    <div class="f-group">
                        <label class="f-label" for="confirm_password">Xac Nhan</label>
                        <input class="f-input" type="password" id="confirm_password" name="confirm_password" placeholder="Nhap lai" required>
                    </div>
                </div>
                <div class="f-check">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">Toi dong y voi Dieu khoan & Chinh sach su dung</label>
                </div>
                <button type="submit" class="btn-main">Dang ky ngay</button>
            </form>
            <div class="divider">Hoac tiep tuc voi</div>
            <div class="social-row">
                <button class="btn-social" type="button" onclick="window.location.href='google_login.php'"><img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="G"> Google</button>
            </div>
            <p class="f-foot">Ban da co tai khoan? <a href="login.php">Dang nhap</a></p>
        </div>
    </div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>
