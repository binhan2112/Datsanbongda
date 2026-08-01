<?php
// Gọi các file cấu hình và helper
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

redirect_if_logged_in();

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$demo_otp = trim($_GET['demo_otp'] ?? '');

if (empty($email)) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Xử lý xác thực OTP khi người dùng bấm Submit (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp'] ?? '');

    if (empty($otp_input)) {
        $error = 'Vui lòng nhập mã OTP xác thực.';
    } elseif (strlen($otp_input) !== 6 || !is_numeric($otp_input)) {
        $error = 'Mã OTP phải bao gồm 6 chữ số.';
    } else {
        try {
            // Tìm người dùng với email và OTP khớp
            $stmt = $pdo->prepare("
                SELECT id, reset_expires, email_verified 
                FROM users 
                WHERE email = :email AND reset_token = :otp LIMIT 1
            ");
            $stmt->execute([
                'email' => $email,
                'otp'   => $otp_input
            ]);
            $user = $stmt->fetch();

            if ($user) {
                $expiry_time = strtotime($user['reset_expires']);
                if ($expiry_time < time()) {
                    $error = 'Mã OTP xác thực đã hết hạn. Vui lòng đăng ký lại hoặc yêu cầu hỗ trợ.';
                } else {
                    // Cập nhật trạng thái email_verified = 1 và xóa OTP
                    $update_stmt = $pdo->prepare("
                        UPDATE users 
                        SET email_verified = 1, 
                            reset_token = NULL, 
                            reset_expires = NULL 
                        WHERE id = :id
                    ");
                    $update_stmt->execute(['id' => $user['id']]);

                    header("Location: login.php?verified=1");
                    exit;
                }
            } else {
                $error = 'Mã OTP xác thực không chính xác. Vui lòng kiểm tra lại.';
            }
        } catch (PDOException $e) {
            $error = 'Đã xảy ra lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Thực Email — CanThoSport</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.3">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .otp-input-field {
            letter-spacing: 12px;
            font-size: 24px;
            text-align: center;
            font-weight: 700;
            padding-left: 20px;
        }
    </style>
</head>
<body>

    <header>
        <div class="container navbar">
            <a href="../index.php" class="logo">
                <i data-lucide="trophy"></i> CanTho<span>Sport</span>
            </a>
            <a href="login.php" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i>&nbsp;Về trang Đăng nhập</a>
        </div>
    </header>

    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 500px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary-subtle); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <i data-lucide="mail-check" style="width: 32px; height: 32px;"></i>
                </div>
                <h1 class="auth-title" style="margin-bottom: 8px;">Xác Thực Tài Khoản</h1>
                <p class="auth-subtitle">Mã OTP kích hoạt tài khoản đã được gửi tới email:<br><strong style="color: var(--text-main);"><?php echo htmlspecialchars($email); ?></strong></p>
            </div>

            <!-- Demo Mode alert nếu SMTP chưa cấu hình -->
            <?php if (!empty($demo_otp)): ?>
                <div class="alert alert-success" style="margin-bottom: 20px; display: flex; flex-direction: column; align-items: flex-start; gap: 8px; padding: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="info" style="color: #10b981; flex-shrink: 0; width: 18px; height: 18px;"></i>
                        <span style="font-weight: 600;">Mã OTP kích hoạt (Demo Mode):</span>
                    </div>
                    <p style="margin: 0; font-size: 13px; color: var(--text-secondary); line-height: 1.5;">
                        Server local chưa bật SMTP. Bạn dùng mã OTP thử nghiệm dưới đây để kích hoạt:
                    </p>
                    <div style="font-size: 20px; font-weight: 700; background: rgba(16, 185, 129, 0.1); padding: 6px 16px; border-radius: 4px; color: #10b981; border: 1px dashed #10b981; margin-top: 4px; letter-spacing: 2px;">
                        <?php echo htmlspecialchars($demo_otp); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <i data-lucide="alert-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="verify-email.php" method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="otp" style="display: block; text-align: center; margin-bottom: 12px; font-weight: 600;">Nhập mã OTP 6 chữ số</label>
                    <input type="text" name="otp" id="otp" class="form-control otp-input-field" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autocomplete="off" autofocus>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px;">
                    <i data-lucide="check-circle-2"></i>&nbsp;&nbsp;Kích Hoạt Tài Khoản
                </button>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2026 CanThoSport. All rights reserved.</p>
        </div>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
