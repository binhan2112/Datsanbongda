<?php
// ═══════════════════════════════════════════════════════
// TRANG THÔNG BÁO
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login();

$user_id = $_SESSION['user_id'];

// Xử lý đánh dấu đã đọc
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['mark_read'])) {
            $notif_id = intval($_POST['mark_read']);
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid")
                ->execute(['id' => $notif_id, 'uid' => $user_id]);
        }
        if (isset($_POST['mark_all_read'])) {
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0")
                ->execute(['uid' => $user_id]);
        }
        if (isset($_POST['delete_notif'])) {
            $notif_id = intval($_POST['delete_notif']);
            $pdo->prepare("DELETE FROM notifications WHERE id = :id AND user_id = :uid")
                ->execute(['id' => $notif_id, 'uid' => $user_id]);
        }
        header("Location: notifications.php");
        exit;
    } catch (PDOException $e) {
        // Ignore
    }
}

// Lấy danh sách thông báo
try {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications
        WHERE user_id = :uid
        ORDER BY is_read ASC, created_at DESC
        LIMIT 50
    ");
    $stmt->execute(['uid' => $user_id]);
    $notifications = $stmt->fetchAll();

    $unread_count = 0;
    foreach ($notifications as $n) {
        if (!$n['is_read']) $unread_count++;
    }
} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

// Ánh xạ icon và màu theo type
$notif_icons = [
    'booking_confirmed' => ['check-circle', '#10b981'],
    'booking_cancelled' => ['x-circle', '#ef4444'],
    'event_reminder'    => ['trophy', '#f59e0b'],
    'new_message'       => ['message-circle', '#3b82f6'],
    'review_reply'      => ['message-square', '#8b5cf6'],
    'system'            => ['info', '#64748b'],
];

$base_url = '../';
$current_page = 'notifications';
$page_title = 'Thông Báo';
include '../includes/header.php';
?>

<div class="container" style="max-width:800px;margin:40px auto 80px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;flex-wrap:wrap;gap:16px;">
        <div>
            <h1 style="font-size:28px;font-weight:800;">
                <i data-lucide="bell" style="width:28px;height:28px;color:var(--primary);vertical-align:middle;"></i>
                Thông Báo
            </h1>
            <p style="color:var(--text-muted);margin-top:4px;">
                <?php echo $unread_count > 0 ? "Bạn có <b style='color:var(--primary);'>$unread_count</b> thông báo chưa đọc." : "Tất cả thông báo đã đọc."; ?>
            </p>
        </div>
        <?php if ($unread_count > 0): ?>
            <form action="notifications.php" method="POST">
                <button type="submit" name="mark_all_read" value="1" class="btn btn-ghost btn-sm">
                    <i data-lucide="check-check"></i> Đánh dấu tất cả đã đọc
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (count($notifications) > 0): ?>
        <div class="notif-list fade-in-up">
            <?php foreach ($notifications as $n): ?>
                <?php
                    $type_key = $n['type'] ?? 'system';
                    $icon_info = $notif_icons[$type_key] ?? $notif_icons['system'];
                    $icon_name = $icon_info[0];
                    $icon_color = $icon_info[1];
                    
                    // Generate link
                    $link = '#';
                    $role = $_SESSION['user_role'] ?? 'customer';
                    if ($n['ref_type'] === 'message') {
                        if ($role === 'admin') $link = '../admin/chat.php';
                        else if ($role === 'owner') $link = '../owner/chat.php' . ($n['ref_id'] ? '?field_id='.$n['ref_id'] : '');
                        else $link = 'chat.php' . ($n['ref_id'] ? '?field_id='.$n['ref_id'] : '');
                    } else if ($n['ref_type'] === 'booking') {
                        if ($role === 'owner') $link = '../owner/bookings.php';
                        else $link = 'profile.php#bookings';
                    } else if ($n['ref_type'] === 'event') {
                        $link = 'events.php';
                    } else if ($n['ref_type'] === 'reward') {
                        if ($role === 'owner' || $role === 'admin') $link = '../' . $role . '/rewards.php';
                        else $link = 'offers.php';
                    }
                ?>
                <div class="notif-item <?php echo $n['is_read'] ? '' : 'notif-unread'; ?>">
                    <div class="notif-icon" style="background:<?php echo $icon_color; ?>15;color:<?php echo $icon_color; ?>;">
                        <i data-lucide="<?php echo $icon_name; ?>"></i>
                    </div>
                    <a href="<?php echo htmlspecialchars($link); ?>" class="notif-content" style="text-decoration:none; color:inherit; display:block; flex:1;">
                        <h4 class="notif-title"><?php echo htmlspecialchars($n['title']); ?></h4>
                        <p class="notif-body"><?php echo htmlspecialchars($n['body']); ?></p>
                        <span class="notif-time">
                            <i data-lucide="clock" style="width:12px;height:12px;"></i>
                            <?php
                                $diff = time() - strtotime($n['created_at']);
                                if ($diff < 60) echo 'Vừa xong';
                                elseif ($diff < 3600) echo floor($diff / 60) . ' phút trước';
                                elseif ($diff < 86400) echo floor($diff / 3600) . ' giờ trước';
                                elseif ($diff < 604800) echo floor($diff / 86400) . ' ngày trước';
                                else echo date('d/m/Y H:i', strtotime($n['created_at']));
                            ?>
                        </span>
                    </a>
                    <div class="notif-actions">
                        <?php if (!$n['is_read']): ?>
                            <form action="notifications.php" method="POST" style="margin:0;">
                                <button type="submit" name="mark_read" value="<?php echo $n['id']; ?>" class="btn btn-ghost btn-sm" title="Đánh dấu đã đọc" style="padding:6px 8px;">
                                    <i data-lucide="check" style="width:14px;height:14px;"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <form action="notifications.php" method="POST" style="margin:0;">
                            <button type="submit" name="delete_notif" value="<?php echo $n['id']; ?>" class="btn btn-ghost btn-sm" title="Xóa" style="padding:6px 8px;color:var(--text-muted);" onclick="return confirm('Xóa thông báo này?');">
                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state fade-in-up">
            <i data-lucide="bell-off"></i>
            <p>Bạn chưa có thông báo nào.</p>
            <a href="../index.php" class="btn btn-primary">Về trang chủ</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
