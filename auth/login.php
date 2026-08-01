<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';
redirect_if_logged_in();
$error = '';
$success = '';
if (isset($_GET['registered']) && $_GET['registered'] == 1) { $success = 'Dang ky thanh cong! Hay dung tai khoan do dang nhap.'; }
elseif (isset($_GET['reset_success']) && $_GET['reset_success'] == 1) { $success = 'Khoi phuc mat khau thanh cong! Vui long dang nhap voi mat khau moi.'; }
elseif (isset($_GET['verified']) && $_GET['verified'] == 1) { $success = 'Xac thuc email thanh cong! Hay dang nhap ngay.'; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) { $error = 'Vui long dien day du Email va Mat khau.'; }
    else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['is_active'] == 0) {
                    if ($user['role'] === 'owner') {
                        $error = 'Tài khoản Chủ sân của bạn đang chờ Admin phê duyệt. Vui lòng liên hệ hỗ trợ.';
                    } else {
                        $error = 'Tài khoản của bạn đang bị khóa.';
                    }
                }
                else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_full_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['show_onboarding'] = true; // Show onboarding modal on first visit
                    $pdo->prepare("UPDATE users SET last_online = NOW() WHERE id = :id")->execute(['id' => $user['id']]);
                    if ($user['role'] === 'admin') { header("Location: ../admin/index.php"); }
                    elseif ($user['role'] === 'owner') { header("Location: ../owner/index.php"); }
                    else { header("Location: ../index.php"); }
                    exit;
                }
            } else { $error = 'Email hoac Mat khau khong chinh xac.'; }
        } catch (PDOException $e) { $error = 'Loi he thong: ' . $e->getMessage(); }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dang Nhap - CanThoSport</title>
<link rel="icon" type="image/png" href="../assets/images/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; font-family: 'Be Vietnam Pro', sans-serif; background: #fff; }

/* ── OUTER WRAPPER ── */
.auth-wrap {
    display: flex;
    height: 100vh;
    overflow: hidden;
    background: #fff;
}

/* ── LEFT: IMAGE PANEL ── */
.auth-img {
    position: relative;
    /* Extra wide so the fade bleeds into the white right side */
    width: 52%;
    flex-shrink: 0;
    overflow: visible; /* let the gradient bleed out */
    display: none;
}
.auth-img img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: 30% center;
    display: block;
}

/* 
 * KEY: Two overlays stacked
 * 1. Dark tint so image doesn't wash out
 * 2. Fade to WHITE on the right edge — this is what blends into the form
 */
.auth-img .tint {
    position: absolute;
    inset: 0;
    /* darken top & left, keep center visible */
    background: linear-gradient(
        135deg,
        rgba(20, 5, 30, 0.55) 0%,
        rgba(10, 5, 15, 0.2) 60%,
        rgba(255, 255, 255, 0) 100%
    );
    z-index: 1;
}
.auth-img .fade-right {
    position: absolute;
    inset: 0;
    /* The magic: fade image to white on right edge */
    background: linear-gradient(
        to right,
        rgba(255,255,255,0) 0%,
        rgba(255,255,255,0) 55%,
        rgba(255,255,255,0.6) 75%,
        rgba(255,255,255,1) 100%
    );
    z-index: 2;
}

/* Badge bottom-left on image */
.auth-img .badge {
    position: absolute;
    bottom: 48px;
    left: 44px;
    z-index: 5;
    color: #fff;
}
.auth-img .badge h2 {
    font-size: 28px;
    font-weight: 900;
    line-height: 1.2;
    letter-spacing: -0.3px;
    text-shadow: 0 2px 20px rgba(0,0,0,0.55);
}
.auth-img .badge p {
    margin-top: 10px;
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,0.72);
    line-height: 1.65;
}
.auth-img .badge-bar {
    width: 44px; height: 3px;
    background: #e11d48;
    border-radius: 3px;
    margin-top: 16px;
}

@media (min-width: 860px) { .auth-img { display: block; } }

/* ── RIGHT: FORM PANEL ── */
.auth-form {
    flex: 1;
    height: 100vh;
    overflow-y: auto;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: flex-start; /* align to left so it's near the image blend */
    padding: 48px 56px;
}

.form-box {
    width: 100%;
    max-width: 380px;
}

/* ── BRAND ── */
.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 40px;
    text-decoration: none;
}
.brand-icon {
    width: 34px; height: 34px;
    background: #e11d48;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
}
.brand-name {
    font-size: 14px; font-weight: 800;
    color: #111827; letter-spacing: 0.5px; text-transform: uppercase;
}

/* ── HEADING ── */
.f-heading { font-size: 28px; font-weight: 800; color: #111827; letter-spacing: -0.5px; margin-bottom: 6px; }
.f-sub { font-size: 14px; color: #6b7280; margin-bottom: 32px; line-height: 1.5; }

/* ── FIELDS ── */
.f-group { margin-bottom: 20px; }
.f-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 7px; }
.f-label-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px; }
.f-label-row .f-label { margin-bottom: 0; }
.f-forgot { font-size: 13px; font-weight: 600; color: #e11d48; text-decoration: none; }
.f-forgot:hover { text-decoration: underline; }

.f-input {
    width: 100%;
    padding: 13px 16px;
    background: #f7f8fa;
    border: 1.5px solid #e9eaec;
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Be Vietnam Pro', sans-serif;
    color: #111827;
    outline: none;
    transition: border-color .18s, box-shadow .18s, background .18s;
}
.f-input[type="password"] { letter-spacing: 2px; }
.f-input[type="password"]::placeholder { letter-spacing: 0; }
.f-input:focus { background: #fff; border-color: #e11d48; box-shadow: 0 0 0 3px rgba(225,29,72,.1); }
.f-input::placeholder { color: #b0b7c3; font-size: 14px; }

.f-check { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
.f-check input[type=checkbox] { width: 16px; height: 16px; accent-color: #e11d48; cursor: pointer; flex-shrink: 0; }
.f-check label { font-size: 13px; color: #6b7280; cursor: pointer; }

/* ── BUTTON ── */
.btn-main {
    width: 100%; padding: 14px;
    background: #e11d48; color: #fff;
    border: none; border-radius: 10px;
    font-size: 15px; font-weight: 700;
    font-family: 'Be Vietnam Pro', sans-serif;
    cursor: pointer;
    transition: background .18s, box-shadow .2s, transform .15s;
    box-shadow: 0 4px 18px rgba(225,29,72,.28);
}
.btn-main:hover { background: #c01740; box-shadow: 0 6px 24px rgba(225,29,72,.4); transform: translateY(-1px); }

/* ── DIVIDER ── */
.divider { display: flex; align-items: center; gap: 12px; margin: 24px 0; font-size: 12px; font-weight: 600; color: #c4c9d4; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #eaecf0; }

/* ── SOCIAL ── */
.social-row { display: flex; gap: 10px; margin-bottom: 28px; }
.btn-social { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 11px; background: #fff; border: 1.5px solid #e9eaec; border-radius: 10px; font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; font-family: 'Be Vietnam Pro', sans-serif; transition: background .15s; }
.btn-social:hover { background: #f7f8fa; }
.btn-social img { width: 18px; height: 18px; }

/* ── FOOT ── */
.f-foot { text-align: center; font-size: 13px; color: #9ca3af; }
.f-foot a { color: #e11d48; font-weight: 700; text-decoration: none; }
.f-foot a:hover { text-decoration: underline; }

/* ── ALERTS ── */
.alert { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; font-weight: 600; line-height: 1.5; }
.alert-ok  { background: #f0fdf4; color: #166534; border: 1.5px solid #bbf7d0; }
.alert-err { background: #fff1f2; color: #9f1239; border: 1.5px solid #fecdd3; }

@media (max-width: 860px) {
    .auth-form { padding: 40px 28px; justify-content: center; }
    .form-box { max-width: 100%; }
}
</style>
</head>
<body>
<div class="auth-wrap">

    <!-- LEFT: Image fades into white on right edge -->
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

    <!-- RIGHT: Pure white form, seamlessly continues from the faded image -->
    <div class="auth-form">
        <div class="form-box">

            <a href="../index.php" class="brand">
                <div class="brand-icon">
                    <i data-lucide="zap" style="width:16px;height:16px;color:#fff"></i>
                </div>
                <span class="brand-name">CanThoSport</span>
            </a>

            <h1 class="f-heading">Chao mung tro lai</h1>
            <p class="f-sub">Dang nhap de tiep tuc dat san va trai nghiem dich vu.</p>

            <?php if (!empty($success)): ?>
            <div class="alert alert-ok">
                <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;margin-top:1px"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-err">
                <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;margin-top:1px"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="f-group">
                    <label class="f-label" for="email">Email</label>
                    <input class="f-input" type="email" id="email" name="email"
                        placeholder="example@canthosport.vn" required
                        value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                <div class="f-group">
                    <div class="f-label-row">
                        <label class="f-label" for="password">Mat khau</label>
                        <a href="forgot-password.php" class="f-forgot">Quen mat khau?</a>
                    </div>
                    <input class="f-input" type="password" id="password" name="password"
                        placeholder="Nhap mat khau" required>
                </div>
                <div class="f-check">
                    <input type="checkbox" id="remember">
                    <label for="remember">Ghi nho dang nhap</label>
                </div>
                <button type="submit" class="btn-main">Dang nhap</button>
            </form>

            <div class="divider">Hoac tiep tuc voi</div>
            <div class="social-row">
                <button class="btn-social" type="button" onclick="window.location.href='google_login.php'">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="G"> Google
                </button>
            </div>
            <p class="f-foot">Ban chua co tai khoan? <a href="register.php">Dang ky mien phi</a></p>
        </div>
    </div>

</div>
<script>lucide.createIcons();</script>
</body>
</html>
