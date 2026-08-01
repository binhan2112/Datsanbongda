<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('admin');
$msg = '';

// Xử lý Khóa/Mở khóa tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_user_id = intval($_POST['user_id']);
    
    // Ngăn chặn admin tự khóa chính mình
    if ($target_user_id === $_SESSION['user_id']) {
        $msg = "<span style='color:#dc2626;'>Lỗi: Bạn không thể tự khóa tài khoản của chính mình!</span>";
    } else {
        if ($_POST['action'] === 'lock') {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->execute([$target_user_id]);
            $msg = "Đã KHÓA tài khoản người dùng thành công!";
        } elseif ($_POST['action'] === 'unlock') {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->execute([$target_user_id]);
            $msg = "Đã MỞ KHÓA tài khoản người dùng thành công!";
        }
    }
}

// Bộ lọc
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';

// Lấy danh sách người dùng
$query = "SELECT * FROM users";
$params = [];
if ($filter_role !== '') {
    $query .= " WHERE role = ?";
    $params[] = $filter_role;
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'Quản lý Người dùng';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Quản lý Người dùng</h2>
        
        <form method="GET" style="display: flex; gap: 10px;">
            <select name="role" onchange="this.form.submit()" style="padding: 8px 15px; border-radius: 6px; border: 1px solid var(--border); outline: none;">
                <option value="">-- Tất cả vai trò --</option>
                <option value="customer" <?php if($filter_role == 'customer') echo 'selected'; ?>>Khách hàng (Customer)</option>
                <option value="owner" <?php if($filter_role == 'owner') echo 'selected'; ?>>Chủ sân (Owner)</option>
                <option value="admin" <?php if($filter_role == 'admin') echo 'selected'; ?>>Quản trị viên (Admin)</option>
            </select>
        </form>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--border); font-weight: 500;">
            <i data-lucide="info" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Khách hàng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Liên hệ</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Vai trò</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Trạng thái</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $u): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo htmlspecialchars($u['full_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);">Tham gia: <?php echo date('d/m/Y', strtotime($u['created_at'])); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php echo htmlspecialchars($u['email']); ?><br>
                                    <span style="color: var(--primary); font-weight: 500;"><?php echo htmlspecialchars($u['phone']); ?></span>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($u['role'] == 'admin'): ?>
                                        <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Admin</span>
                                    <?php elseif ($u['role'] == 'owner'): ?>
                                        <span style="background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Chủ sân</span>
                                    <?php else: ?>
                                        <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Khách hàng</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($u['is_active'] == 1): ?>
                                        <span style="color: #166534; font-weight: 500;"><i data-lucide="user-check" style="width:16px;height:16px;vertical-align:text-bottom;"></i> Hoạt động</span>
                                    <?php else: ?>
                                        <span style="color: #dc2626; font-weight: 500;"><i data-lucide="user-x" style="width:16px;height:16px;vertical-align:text-bottom;"></i> Bị Khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: right;">
                                    <?php if ($u['id'] !== $_SESSION['user_id']): // Ẩn nút khóa với chính mình ?>
                                        <form method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <?php if ($u['is_active'] == 1): ?>
                                                <button type="submit" name="action" value="lock" class="btn-logout" style="padding: 6px 12px; font-size: 12px; border-radius: 4px;" onclick="return confirm('Bạn có chắc chắn muốn khóa tài khoản này? Người này sẽ không thể đăng nhập được nữa.');">
                                                    Khóa tài khoản
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="unlock" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 4px; color: #166534; border-color: #166534;">
                                                    Mở khóa
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size: 12px;">Bạn</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Không tìm thấy người dùng nào.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
