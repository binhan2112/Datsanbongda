<?php
// Gọi các file cấu hình và helper
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

// Nếu đã đăng nhập thì chuyển hướng về trang chủ
redirect_if_logged_in();

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$demo_otp = trim($_GET['demo_otp'] ?? '');

if (empty($email)) {
    header("Location: forgot-password.php");
    exit;
}

$error = '';
$success = '';

// Xử lý khi gửi form xác thực OTP (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp'] ?? '');

    if (empty($otp_input)) {
        $error = 'Vui lòng điền mã OTP.';
    } elseif (strlen($otp_input) !== 6 || !is_numeric($otp_input)) {
        $error = 'Mã OTP phải gồm đúng 6 chữ số.';
    } else {
        try {
            // Tìm tài khoản theo email và mã OTP khớp
            $stmt = $pdo->prepare("
                SELECT id, reset_expires 
                FROM users 
                WHERE email = :email AND reset_token = :otp LIMIT 1
            ");
            $stmt->execute([
                'email' => $email,
                'otp' => $otp_input
            ]);
            $user = $stmt->fetch();

            if ($user) {
                // Kiểm tra mã OTP xem còn hiệu lực hay không
                $expiry_time = strtotime($user['reset_expires']);
                if ($expiry_time < time()) {
                    $error = 'Mã OTP này đã hết hạn. Vui lòng yêu cầu mã mới.';
                } else {
                    // Xác thực thành công! Chuyển hướng sang đặt lại mật khẩu mới kèm email và otp
                    header("Location: reset-password.php?email=" . urlencode($email) . "&otp=" . urlencode($otp_input));
                    exit;
                }
            } else {
                $error = 'Mã OTP không chính xác. Vui lòng kiểm tra lại.';
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
    <title>Xác Thực OTP — CanThoSport</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.3">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* CSS cho các ô nhập mã OTP đẹp mắt */
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

    <!-- Header / Navbar tối giản cho trang auth -->
    <header>
        <div class="container navbar">
            <a href="../index.php" class="logo">
                <i data-lucide="trophy"></i> CanTho<span>Sport</span>
            </a>
            <a href="forgot-password.php" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i>&nbsp;Thay đổi Email nhận mã</a>
        </div>
    </header>

    <!-- Biểu mẫu Xác thực OTP -->
    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 500px;">
            <h1 class="auth-title" style="margin-bottom: 8px;">Xác Thực OTP</h1>
            <p class="auth-subtitle">Mã xác thực đã được gửi đến email: <strong style="color: var(--text);"><?php echo htmlspecialchars($email); ?></strong></p>

            <!-- Hiển thị demo OTP cho môi trường localhost phát triển -->
            <?php if (!empty($demo_otp)): ?>
                <div class="alert alert-success" style="margin-bottom: 20px; display: flex; flex-direction: column; align-items: flex-start; gap: 8px; padding: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="info" style="color: #10b981; flex-shrink: 0; width: 18px; height: 18px;"></i>
                        <span style="font-weight: 600;">Thông tin mô phỏng (Demo Mode):</span>
                    </div>
                    <p style="margin: 0; font-size: 13px; color: var(--text-secondary); line-height: 1.5;">
                        Máy chủ cục bộ chưa được cấu hình gửi email SMTP. Bạn có thể sử dụng mã OTP thử nghiệm dưới đây để tiếp tục test:
                    </p>
                    <div style="font-size: 20px; font-weight: 700; background: rgba(16, 185, 129, 0.1); padding: 6px 16px; border-radius: 4px; color: #10b981; border: 1px dashed #10b981; margin-top: 4px; letter-spacing: 2px;">
                        <?php echo htmlspecialchars($demo_otp); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Hiển thị thông báo lỗi -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <i data-lucide="alert-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="verify-otp.php" method="POST">
                <!-- Truyền ngầm Email nhận OTP -->
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                <!-- Mã OTP -->
                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="otp" style="display: block; text-align: center; margin-bottom: 12px; font-weight: 600;">Nhập mã OTP gồm 6 chữ số</label>
                    <input type="text" name="otp" id="otp" class="form-control otp-input-field" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autocomplete="off" autofocus>
                </div>

                <!-- Nút Xác thực -->
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px;">
                    <i data-lucide="shield-check"></i>&nbsp;&nbsp;Xác nhận mã OTP
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <p style="font-size: 13px; color: var(--text-muted);">
                    Không nhận được mã? <a href="forgot-password.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Gửi lại yêu cầu</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2026 CanThoSport. All rights reserved. Phát triển bởi Antigravity.</p>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
