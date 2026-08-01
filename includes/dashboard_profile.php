<?php
// includes/dashboard_profile.php
// File này chứa logic và giao diện cho trang Profile trong Dashboard (dành cho Admin và Owner)

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'info';

// Lấy thông tin
try {
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $user_stmt->execute(['id' => $user_id]);
    $user = $user_stmt->fetch();
} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_info') {
            $full_name = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($full_name) || empty($phone)) {
                $error = 'Họ tên và số điện thoại không được để trống.';
            } else {
                try {
                    $update = $pdo->prepare("UPDATE users SET full_name = :full_name, phone = :phone, address = :address WHERE id = :id");
                    $update->execute([
                        'full_name' => $full_name,
                        'phone' => $phone,
                        'address' => !empty($address) ? $address : null,
                        'id' => $user_id
                    ]);
                    $_SESSION['user_full_name'] = $full_name;
                    $user['full_name'] = $full_name;
                    $user['phone'] = $phone;
                    $user['address'] = $address;
                    $success = 'Cập nhật thông tin cá nhân thành công!';
                } catch (PDOException $e) {
                    $error = 'Lỗi hệ thống: ' . $e->getMessage();
                }
            }
        }

        if ($_POST['action'] === 'change_password') {
            $tab = 'password';
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_new = $_POST['confirm_new_password'] ?? '';

            if (empty($current_password) || empty($new_password) || empty($confirm_new)) {
                $error = 'Vui lòng điền đầy đủ tất cả các trường mật khẩu.';
            } elseif (!password_verify($current_password, $user['password_hash'])) {
                $error = 'Mật khẩu hiện tại không chính xác.';
            } elseif (strlen($new_password) < 6) {
                $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            } elseif ($new_password !== $confirm_new) {
                $error = 'Mật khẩu xác nhận không khớp.';
            } else {
                try {
                    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")
                        ->execute(['hash' => $new_hash, 'id' => $user_id]);
                    $success = 'Đổi mật khẩu thành công!';
                } catch (PDOException $e) {
                    $error = 'Lỗi hệ thống: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<div>
    <h2 style="margin-bottom: 20px;">Hồ sơ cá nhân</h2>

    <?php if ($success): ?>
        <div style="background: #dcfce3; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
            <i data-lucide="check-circle" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <i data-lucide="alert-circle" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; border-bottom: 1px solid var(--border); margin-bottom: 25px;">
        <a href="?tab=info" style="padding: 10px 20px; text-decoration: none; font-weight: 600; color: <?php echo $tab=='info' ? 'var(--primary)' : 'var(--text-muted)'; ?>; border-bottom: 2px solid <?php echo $tab=='info' ? 'var(--primary)' : 'transparent'; ?>;">Thông tin cơ bản</a>
        <a href="?tab=password" style="padding: 10px 20px; text-decoration: none; font-weight: 600; color: <?php echo $tab=='password' ? 'var(--primary)' : 'var(--text-muted)'; ?>; border-bottom: 2px solid <?php echo $tab=='password' ? 'var(--primary)' : 'transparent'; ?>;">Đổi mật khẩu</a>
    </div>

    <?php if ($tab == 'info'): ?>
    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_info">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Vai trò tài khoản</label>
                    <input type="text" value="<?php echo strtoupper($user['role']); ?>" disabled style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; background: #f1f5f9; color: var(--text-muted);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Email (Tài khoản đăng nhập)</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; background: #f1f5f9; color: var(--text-muted);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Họ và Tên</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Số điện thoại</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Địa chỉ (Không bắt buộc)</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-weight: 600;">Lưu thay đổi</button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Mật khẩu hiện tại</label>
                    <input type="password" name="current_password" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Mật khẩu mới</label>
                    <input type="password" name="new_password" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm_new_password" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-weight: 600;">Đổi mật khẩu</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
