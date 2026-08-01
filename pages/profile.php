<?php
// ═══════════════════════════════════════════════════════
// TRANG HỒ SƠ CÁ NHÂN
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login();

if ($_SESSION['user_role'] === 'admin') {
    header("Location: ../admin/profile.php");
    exit;
} elseif ($_SESSION['user_role'] === 'owner') {
    header("Location: ../owner/profile.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'info'; // info | password

// Lấy thông tin user hiện tại
try {
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $user_stmt->execute(['id' => $user_id]);
    $user = $user_stmt->fetch();

    // Thống kê
    $stats_bookings = $pdo->prepare("SELECT SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as total, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN payment_status='paid' THEN total_price ELSE 0 END) as total_spent FROM bookings WHERE user_id = :uid");
    $stats_bookings->execute(['uid' => $user_id]);
    $stats = $stats_bookings->fetch();

    $stats_reviews = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = :uid");
    $stats_reviews->execute(['uid' => $user_id]);
    $total_reviews = (int)$stats_reviews->fetchColumn();

    $stats_favorites = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = :uid");
    $stats_favorites->execute(['uid' => $user_id]);
    $total_favorites = (int)$stats_favorites->fetchColumn();

    // Lịch sử biến động điểm tích lũy
    $points_history_stmt = $pdo->prepare("
        SELECT b.id, b.booking_code, b.booking_date, b.points_used, b.discount_amount, b.points_earned, b.total_price, f.name as field_name
        FROM bookings b
        JOIN fields f ON b.field_id = f.id
        WHERE b.user_id = :uid AND (b.points_used > 0 OR b.points_earned > 0)
        ORDER BY b.booking_date DESC, b.created_at DESC
    ");
    $points_history_stmt->execute(['uid' => $user_id]);
    $points_history = $points_history_stmt->fetchAll();

    // Lịch sử đổi quà
    $reward_history_stmt = $pdo->prepare("
        SELECT re.*, r.name as reward_name, r.image_url 
        FROM reward_exchanges re 
        JOIN rewards r ON re.reward_id = r.id 
        WHERE re.user_id = :uid 
        ORDER BY re.exchange_date DESC
    ");
    $reward_history_stmt->execute(['uid' => $user_id]);
    $reward_history = $reward_history_stmt->fetchAll();

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

// Xử lý cập nhật thông tin (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // === CẬP NHẬT THÔNG TIN ===
        if ($_POST['action'] === 'update_info') {
            $full_name = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($full_name) || empty($phone)) {
                $error = 'Họ tên và số điện thoại không được để trống.';
            } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
                $error = 'Số điện thoại phải chứa 10 hoặc 11 chữ số.';
            } else {
                try {
                    // Kiểm tra phone trùng
                    $check = $pdo->prepare("SELECT id FROM users WHERE phone = :phone AND id != :id");
                    $check->execute(['phone' => $phone, 'id' => $user_id]);
                    if ($check->fetch()) {
                        $error = 'Số điện thoại này đã được sử dụng bởi tài khoản khác.';
                    } else {
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

                        // Xử lý upload avatar
                        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                            $fileTmpPath = $_FILES['avatar']['tmp_name'];
                            $fileName = $_FILES['avatar']['name'];
                            $fileSize = $_FILES['avatar']['size'];
                            $fileNameCmps = explode(".", $fileName);
                            $fileExtension = strtolower(end($fileNameCmps));
                            
                            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
                            if (in_array($fileExtension, $allowedfileExtensions)) {
                                if ($fileSize <= 2 * 1024 * 1024) {
                                    $newFileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
                                    $uploadFileDir = '../assets/uploads/avatars/';
                                    if (!is_dir($uploadFileDir)) {
                                        mkdir($uploadFileDir, 0777, true);
                                    }
                                    $dest_path = $uploadFileDir . $newFileName;
                                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                                        if (!empty($user['avatar']) && $user['avatar'] !== 'default-avatar.png') {
                                            $old_file = $uploadFileDir . $user['avatar'];
                                            if (file_exists($old_file)) {
                                                unlink($old_file);
                                            }
                                        }
                                        $update_av = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
                                        $update_av->execute(['avatar' => $newFileName, 'id' => $user_id]);
                                        $user['avatar'] = $newFileName;
                                        $success = 'Cập nhật thông tin và ảnh đại diện thành công!';
                                    } else {
                                        $error = 'Có lỗi xảy ra khi lưu file ảnh.';
                                        $success = '';
                                    }
                                } else {
                                    $error = 'Dung lượng file ảnh đại diện vượt quá 2MB.';
                                    $success = '';
                                }
                            } else {
                                $error = 'Định dạng file không được hỗ trợ (Chỉ nhận JPG, JPEG, PNG, GIF, WEBP).';
                                $success = '';
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $error = 'Lỗi hệ thống: ' . $e->getMessage();
                }
            }
        }

        // === ĐỔI MẬT KHẨU ===
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

$base_url = '../';
$current_page = 'profile';
$page_title = 'Hồ Sơ Cá Nhân';
include '../includes/header.php';
?>

<div class="container profile-container">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar-lg" style="position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--border-color);">
            <?php 
            if (!empty($user['avatar']) && $user['avatar'] !== 'default-avatar.png') {
                if (filter_var($user['avatar'], FILTER_VALIDATE_URL)) {
                    echo '<img src="'.htmlspecialchars($user['avatar']).'" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">';
                } elseif (file_exists('../assets/uploads/avatars/' . $user['avatar'])) {
                    echo '<img src="../assets/uploads/avatars/'.htmlspecialchars($user['avatar']).'" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">';
                } else {
                    echo mb_strtoupper(mb_substr($user['full_name'], 0, 1));
                }
            } else {
                echo mb_strtoupper(mb_substr($user['full_name'], 0, 1));
            }
            ?>
        </div>
        <div class="profile-header-info">
            <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
            <p class="profile-role-text">
                <span class="role-chip"><?php echo $user['role'] === 'owner' ? 'Chủ Sân' : ($user['role'] === 'admin' ? 'Admin' : 'Người chơi'); ?></span>
                &nbsp; Thành viên từ <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
            </p>
            <p style="margin-top: 8px; font-weight: 600; color: var(--primary); display: inline-flex; align-items: center; gap: 4px; font-size: 14px;">
                <i data-lucide="award" style="width:16px;height:16px;stroke-width:2.5;"></i>
                Điểm tích lũy: <b style="font-size:16px;"><?php echo number_format($user['points'], 0, ',', '.'); ?></b> điểm
            </p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="profile-stats">
        <div class="profile-stat-card">
            <div class="profile-stat-icon"><i data-lucide="calendar-check"></i></div>
            <div class="profile-stat-number"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="profile-stat-label">Tổng đơn đặt</div>
        </div>
        <div class="profile-stat-card">
            <div class="profile-stat-icon" style="background:rgba(59,130,246,0.1);"><i data-lucide="check-circle" style="color:#3b82f6;"></i></div>
            <div class="profile-stat-number"><?php echo $stats['completed'] ?? 0; ?></div>
            <div class="profile-stat-label">Đã hoàn thành</div>
        </div>
        <div class="profile-stat-card">
            <div class="profile-stat-icon" style="background:rgba(245,158,11,0.1);"><i data-lucide="wallet" style="color:#f59e0b;"></i></div>
            <div class="profile-stat-number"><?php echo number_format($stats['total_spent'] ?? 0, 0, ',', '.'); ?>đ</div>
            <div class="profile-stat-label">Tổng chi tiêu</div>
        </div>
        <div class="profile-stat-card">
            <div class="profile-stat-icon" style="background:rgba(239,68,68,0.1);"><i data-lucide="heart" style="color:#ef4444;"></i></div>
            <div class="profile-stat-number"><?php echo $total_favorites; ?></div>
            <div class="profile-stat-label">Sân yêu thích</div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><i data-lucide="check-circle"></i><span><?php echo htmlspecialchars($success); ?></span></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><i data-lucide="alert-circle"></i><span><?php echo htmlspecialchars($error); ?></span></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="profile-tabs">
        <a href="profile.php?tab=info" class="profile-tab <?php echo $tab === 'info' ? 'active' : ''; ?>">
            <i data-lucide="user"></i> Thông tin cá nhân
        </a>
        <a href="profile.php?tab=password" class="profile-tab <?php echo $tab === 'password' ? 'active' : ''; ?>">
            <i data-lucide="lock"></i> Đổi mật khẩu
        </a>
        <a href="profile.php?tab=rewards" class="profile-tab <?php echo $tab === 'rewards' ? 'active' : ''; ?>">
            <i data-lucide="award"></i> Điểm đổi thưởng
        </a>
    </div>

    <!-- Tab Content -->
    <?php if ($tab === 'info'): ?>
    <div class="profile-card">
        <h2 style="font-size:20px;font-weight:700;margin-bottom:24px;">Thông Tin Cá Nhân</h2>
        <form action="profile.php?tab=info" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_info">

            <div class="form-group" style="margin-bottom:20px;">
                <label for="avatar">Ảnh đại diện mới</label>
                <input type="file" name="avatar" id="avatar" class="form-control" accept="image/*" style="padding: 8px 12px; border: 1px dashed var(--border-color); background: rgba(255,255,255,0.01);">
                <small style="font-size:12px;color:var(--text-muted);">Định dạng hỗ trợ: JPG, PNG, WEBP (Tối đa 2MB).</small>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label for="email">Địa chỉ Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity:0.6;cursor:not-allowed;">
                <small style="font-size:12px;color:var(--text-muted);">Email không thể thay đổi.</small>
            </div>

            <div class="form-row">
                <div class="form-group" style="margin-bottom:20px;">
                    <label for="full_name">Họ và tên *</label>
                    <input type="text" name="full_name" id="full_name" class="form-control" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label for="phone">Số điện thoại *</label>
                    <input type="text" name="phone" id="phone" class="form-control" required value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:24px;">
                <label for="address">Địa chỉ</label>
                <input type="text" name="address" id="address" class="form-control" placeholder="Số nhà, Đường, Phường, Quận, Cần Thơ" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="padding:12px 32px;">
                <i data-lucide="save"></i> Lưu thay đổi
            </button>
        </form>
    </div>
    <?php elseif ($tab === 'password'): ?>
    <div class="profile-card">
        <h2 style="font-size:20px;font-weight:700;margin-bottom:24px;">Đổi Mật Khẩu</h2>
        <form action="profile.php?tab=password" method="POST">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group" style="margin-bottom:20px;">
                <label for="current_password">Mật khẩu hiện tại *</label>
                <input type="password" name="current_password" id="current_password" class="form-control" required placeholder="Nhập mật khẩu hiện tại">
            </div>

            <div class="form-row">
                <div class="form-group" style="margin-bottom:20px;">
                    <label for="new_password">Mật khẩu mới *</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required placeholder="Tối thiểu 6 ký tự">
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label for="confirm_new_password">Xác nhận mật khẩu mới *</label>
                    <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" required placeholder="Nhập lại mật khẩu mới">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding:12px 32px;">
                <i data-lucide="key"></i> Đổi mật khẩu
            </button>
        </form>
    </div>
    <?php elseif ($tab === 'rewards'): ?>
    <div class="profile-card">
        <h2 style="font-size:20px;font-weight:700;margin-bottom:24px;display:flex;align-items:center;gap:8px; color: var(--text-main);">
            <i data-lucide="award" style="color:var(--primary);"></i> Lịch sử & Đổi thưởng
        </h2>

        <!-- Point summary card -->
        <div style="background: linear-gradient(135deg, rgba(16,185,129,0.06) 0%, rgba(4,120,87,0.06) 100%); border: 1px solid rgba(16,185,129,0.15); border-radius: 12px; padding: 20px; display: flex; gap: 20px; align-items: center; margin-bottom: 24px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; box-shadow: var(--shadow-sm);">
                <i data-lucide="trophy" style="width: 30px; height: 30px;"></i>
            </div>
            <div>
                <span style="font-size: 14px; color: var(--text-muted); font-weight: 500;">Điểm tích lũy hiện tại</span>
                <div style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-top: 4px; display: flex; align-items: baseline; gap: 6px;">
                    <?php echo number_format($user['points'], 0, ',', '.'); ?>
                    <span style="font-size: 14px; font-weight: 500; color: var(--text-muted);">điểm</span>
                </div>
            </div>
        </div>

        <!-- Rules card -->
        <div style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 30px;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: var(--text-main);">Chính sách đổi thưởng:</h3>
            <ul style="margin: 0; padding-left: 20px; color: var(--text-muted); font-size: 14px; line-height: 1.8;">
                <li><b>Tích lũy:</b> Mỗi khi trận đấu hoàn thành, bạn sẽ được cộng điểm tương ứng với số tiền thực tế đã thanh toán: <b>10.000đ = 1 điểm tích lũy</b>.</li>
                <li><b>Quy đổi:</b> Khi thanh toán đơn đặt sân mới, bạn có thể chọn dùng điểm tích lũy để được giảm giá: <b>1 điểm = 100đ giảm giá</b>.</li>
                <li>Mức quy đổi tối đa cho mỗi hóa đơn không vượt quá giá trị của đơn đặt sân đó.</li>
            </ul>
        </div>

        <!-- Ledger Table -->
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--text-main);">Lịch sử biến động điểm</h3>
        <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-weight: 600; color: var(--text-muted);">Mã đơn / Ngày</th>
                        <th style="padding: 12px 16px; font-weight: 600; color: var(--text-muted);">Sân bóng</th>
                        <th style="padding: 12px 16px; font-weight: 600; color: var(--text-muted);">Hoạt động</th>
                        <th style="padding: 12px 16px; font-weight: 600; color: var(--text-muted); text-align: right; width: 100px;">Điểm số</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($points_history) > 0): ?>
                        <?php foreach ($points_history as $row): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px;">
                                    <strong style="color: var(--text-main);"><?php echo $row['booking_code']; ?></strong><br>
                                    <small style="color: var(--text-muted);"><?php echo date('d/m/Y', strtotime($row['booking_date'])); ?></small>
                                </td>
                                <td style="padding: 12px 16px; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($row['field_name']); ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <?php if ($row['points_used'] > 0): ?>
                                        <span style="color: #ef4444; font-weight: 500;">Quy đổi thanh toán</span>
                                    <?php elseif ($row['points_earned'] > 0): ?>
                                        <span style="color: var(--primary); font-weight: 500;">Tích lũy hoàn thành</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: right; font-weight: 700;">
                                    <?php if ($row['points_used'] > 0): ?>
                                        <span style="color: #ef4444;">-<?php echo $row['points_used']; ?></span>
                                    <?php elseif ($row['points_earned'] > 0): ?>
                                        <span style="color: var(--primary);">+<?php echo $row['points_earned']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Bạn chưa có lịch sử biến động điểm tích lũy nào. Hãy đặt sân bóng ngay hôm nay!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Reward Exchange Ledger Table -->
        <h3 style="font-size: 16px; font-weight: 700; margin-top: 30px; margin-bottom: 16px; color: var(--text-main);">Lịch sử đổi quà (Từ trang Ưu Đãi)</h3>
        <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-weight: 600; color: var(--text-muted);">Ngày đổi</th>
                        <th style="padding: 12px 16px; font-weight: 600; color: var(--text-muted);">Phần quà</th>
                        <th style="padding: 12px 16px; font-weight: 600; color: var(--text-muted);">Điểm tiêu dùng</th>
                        <th style="padding: 12px 16px; font-weight: 600; color: var(--text-muted);">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reward_history) > 0): ?>
                        <?php foreach ($reward_history as $rh): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px;">
                                    <?php echo date('d/m/Y H:i', strtotime($rh['exchange_date'] ?? $rh['updated_at'])); ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <?php if ($rh['image_url']): ?>
                                            <img src="<?php echo $base_url . $rh['image_url']; ?>" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">
                                        <?php endif; ?>
                                        <strong style="color: var(--text-main);"><?php echo htmlspecialchars($rh['reward_name']); ?></strong>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px; font-weight: 700; color: #ef4444;">
                                    -<?php echo number_format($rh['points_used'], 0, ',', '.'); ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <?php if ($rh['status'] === 'pending'): ?>
                                        <span style="background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Chờ duyệt</span>
                                    <?php elseif ($rh['status'] === 'approved'): ?>
                                        <span style="background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đã duyệt (Đến sân nhận)</span>
                                    <?php elseif ($rh['status'] === 'delivered'): ?>
                                        <span style="background: #dcfce3; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đã nhận quà</span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Từ chối</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Bạn chưa đổi phần quà nào.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
