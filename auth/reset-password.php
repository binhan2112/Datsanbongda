<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';
redirect_if_logged_in();
$email = trim($_GET['email']??$_POST['email']??'');
$otp   = trim($_GET['otp']??$_POST['otp']??'');
if (empty($email)||empty($otp)) { header("Location: forgot-password.php"); exit; }
$error=''; $invalid=false; $uid=null;
try {
    $s=$pdo->prepare("SELECT id,reset_expires FROM users WHERE email=:e AND reset_token=:o LIMIT 1"); $s->execute(['e'=>$email,'o'=>$otp]);
    $u=$s->fetch();
    if (!$u) { $invalid=true; } elseif (strtotime($u['reset_expires'])<time()) { $invalid=true; } else { $uid=$u['id']; }
} catch (PDOException $e) { $error='Loi he thong: '.$e->getMessage(); }
if ($_SERVER['REQUEST_METHOD']==='POST'&&!$invalid&&$uid) {
    $pw=$_POST['password']??''; $cpw=$_POST['confirm_password']??'';
    if (empty($pw)||empty($cpw)) { $error='Vui long nhap day du mat khau moi va xac nhan.'; }
    elseif (strlen($pw)<6) { $error='Mat khau moi phai chua it nhat 6 ky tu.'; }
    elseif ($pw!==$cpw) { $error='Mat khau xac nhan khong khop.'; }
    else { try { $pdo->prepare("UPDATE users SET password_hash=:h,reset_token=NULL,reset_expires=NULL WHERE id=:i")->execute(['h'=>password_hash($pw,PASSWORD_BCRYPT),'i'=>$uid]); header("Location: login.php?reset_success=1"); exit; } catch(PDOException $e){$error='Loi he thong: '.$e->getMessage();} }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dat Lai Mat Khau - CanThoSport</title>
<link rel="icon" type="image/png" href="../assets/images/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; font-family: 'Be Vietnam Pro', sans-serif; background: #fff; }
.auth-wrap { display: flex; height: 100vh; overflow: hidden; background: #fff; }
.auth-img { position: relative; width: 52%; flex-shrink: 0; overflow: visible; display: none; }
.auth-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: 30% center; display: block; }
.auth-img .tint { position: absolute; inset: 0; background: linear-gradient(135deg,rgba(20,5,30,0.55) 0%,rgba(10,5,15,0.2) 60%,rgba(255,255,255,0) 100%); z-index: 1; }
.auth-img .fade-right { position: absolute; inset: 0; background: linear-gradient(to right,rgba(255,255,255,0) 0%,rgba(255,255,255,0) 55%,rgba(255,255,255,0.6) 75%,rgba(255,255,255,1) 100%); z-index: 2; }
.auth-img .badge { position: absolute; bottom: 48px; left: 44px; z-index: 5; color: #fff; }
.auth-img .badge h2 { font-size: 28px; font-weight: 900; line-height: 1.2; text-shadow: 0 2px 20px rgba(0,0,0,0.55); }
.auth-img .badge p { margin-top: 10px; font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.72); line-height: 1.65; }
.auth-img .badge-bar { width: 44px; height: 3px; background: #e11d48; border-radius: 3px; margin-top: 16px; }
@media (min-width: 860px) { .auth-img { display: block; } }
.auth-form { flex: 1; height: 100vh; overflow-y: auto; background: #ffffff; display: flex; align-items: center; justify-content: flex-start; padding: 48px 56px; }
.form-box { width: 100%; max-width: 380px; }
.brand { display: flex; align-items: center; gap: 10px; margin-bottom: 40px; text-decoration: none; }
.brand-icon { width: 34px; height: 34px; background: #e11d48; border-radius: 9px; display: flex; align-items: center; justify-content: center; }
.brand-name { font-size: 14px; font-weight: 800; color: #111827; letter-spacing: 0.5px; text-transform: uppercase; }
.f-heading { font-size: 26px; font-weight: 800; color: #111827; letter-spacing: -0.5px; margin-bottom: 6px; }
.f-sub { font-size: 14px; color: #6b7280; margin-bottom: 28px; line-height: 1.5; }
.f-group { margin-bottom: 18px; }
.f-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 7px; }
.f-input { width: 100%; padding: 13px 16px; background: #f7f8fa; border: 1.5px solid #e9eaec; border-radius: 10px; font-size: 14px; font-family: 'Be Vietnam Pro', sans-serif; color: #111827; outline: none; transition: border-color .18s, box-shadow .18s, background .18s; }
.f-input[type="password"] { letter-spacing: 2px; }
.f-input[type="password"]::placeholder { letter-spacing: 0; }
.f-input:focus { background: #fff; border-color: #e11d48; box-shadow: 0 0 0 3px rgba(225,29,72,.1); }
.f-input::placeholder { color: #b0b7c3; font-size: 14px; }
.btn-main { width: 100%; padding: 14px; background: #e11d48; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; font-family: 'Be Vietnam Pro', sans-serif; cursor: pointer; transition: background .18s, box-shadow .2s, transform .15s; box-shadow: 0 4px 18px rgba(225,29,72,.28); }
.btn-main:hover { background: #c01740; transform: translateY(-1px); }
.btn-outline { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 13px; background: #fff; color: #e11d48; border: 2px solid #e11d48; border-radius: 10px; font-size: 14px; font-weight: 700; font-family: 'Be Vietnam Pro', sans-serif; cursor: pointer; text-decoration: none; transition: background .15s; }
.btn-outline:hover { background: #fff1f2; }
.f-foot { text-align: center; font-size: 13px; color: #9ca3af; margin-top: 22px; }
.f-foot a { color: #e11d48; font-weight: 700; text-decoration: none; }
.f-foot a:hover { text-decoration: underline; }
.alert { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-size: 13px; font-weight: 600; line-height: 1.5; }
.alert-err { background: #fff1f2; color: #9f1239; border: 1.5px solid #fecdd3; }
.invalid-box { background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
.invalid-title { display: flex; align-items: center; gap: 10px; font-weight: 700; color: #9f1239; margin-bottom: 8px; font-size: 14px; }
.invalid-box p { font-size: 13px; color: #374151; line-height: 1.65; }
@media (max-width: 860px) { .auth-form { padding: 40px 28px; justify-content: center; } .form-box { max-width: 100%; } }
</style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-img">
        <img src="../assets/images/son_bg.png" alt="Son Heung-min">
        <div class="tint"></div><div class="fade-right"></div>
        <div class="badge"><h2>CANTHOSPORT<br>ELITE</h2><p>San dau chat luong cao<br>Dat san toan thanh pho Can Tho</p><div class="badge-bar"></div></div>
    </div>
    <div class="auth-form">
        <div class="form-box">
            <a href="../index.php" class="brand"><div class="brand-icon"><i data-lucide="zap" style="width:16px;height:16px;color:#fff"></i></div><span class="brand-name">CanThoSport</span></a>
            <h1 class="f-heading">Khoi Phuc Mat Khau</h1>
            <?php if ($invalid): ?>
                <p class="f-sub">Ma xac nhan khong hop le hoac da het han.</p>
                <div class="invalid-box"><div class="invalid-title"><i data-lucide="x-circle" style="width:18px;height:18px"></i> Ma xac thuc khong hop le!</div><p>Yeu cau nay khong hop le hoac ma OTP da qua han 15 phut.</p></div>
                <a href="forgot-password.php" class="btn-outline"><i data-lucide="rotate-ccw" style="width:17px;height:17px"></i> Gui lai yeu cau moi</a>
            <?php else: ?>
                <p class="f-sub">Thiet lap mat khau moi cho tai khoan cua ban.</p>
                <?php if (!empty($error)): ?><div class="alert alert-err"><i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;margin-top:1px"></i><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>
                <form method="POST" action="reset-password.php">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="hidden" name="otp" value="<?php echo htmlspecialchars($otp); ?>">
                    <div class="f-group"><label class="f-label" for="password">Mat khau moi</label><input class="f-input" type="password" id="password" name="password" placeholder="Toi thieu 6 ky tu" required autofocus></div>
                    <div class="f-group"><label class="f-label" for="confirm_password">Xac nhan mat khau</label><input class="f-input" type="password" id="confirm_password" name="confirm_password" placeholder="Nhap lai mat khau" required></div>
                    <button type="submit" class="btn-main">Xac nhan thay doi</button>
                </form>
            <?php endif; ?>
            <p class="f-foot"><a href="login.php">Quay lai Dang nhap</a></p>
        </div>
    </div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>
