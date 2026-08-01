<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('owner');
$owner_id = $_SESSION['user_id'];

// Lấy ID Admin để làm người nhận tin nhắn
try {
    $admin_stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
    $admin = $admin_stmt->fetch();
    $admin_id = $admin ? intval($admin['id']) : 2;
    $admin_name = $admin ? $admin['full_name'] : 'Ban Quản Trị';
} catch (PDOException $e) {
    $admin_id = 2;
    $admin_name = 'Ban Quản Trị';
}

$msg = '';

// Xử lý gửi tin nhắn tới Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        try {
            $insert = $pdo->prepare("INSERT INTO messages (field_id, sender_id, receiver_id, content) VALUES (NULL, ?, ?, ?)");
            $insert->execute([$owner_id, $admin_id, $content]);
            
            // Create notification for Admin
            $sender_name = $_SESSION['user_full_name'] ?? 'Chủ sân';
            $notif_title = '💬 Tin nhắn mới từ Chủ sân ' . $sender_name;
            $notif_body = 'Chủ sân vừa gửi tin nhắn: "' . mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : '') . '"';
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon) VALUES (?, 'new_message', ?, ?, 'message', NULL, 'message-square')");
            $notif_stmt->execute([$admin_id, $notif_title, $notif_body]);
            
            header("Location: admin_chat.php");
            exit;
        } catch (PDOException $e) {
            $msg = "<span style='color:#dc2626;'>Lỗi: " . $e->getMessage() . "</span>";
        }
    }
}

try {
    // Đọc lịch sử chat hệ thống với Admin
    $msg_stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE field_id IS NULL 
        AND ((sender_id = :uid AND receiver_id = :aid) OR (sender_id = :aid2 AND receiver_id = :uid2))
        ORDER BY created_at ASC
    ");
    $msg_stmt->execute([
        'uid' => $owner_id, 
        'aid' => $admin_id,
        'aid2' => $admin_id,
        'uid2' => $owner_id
    ]);
    $messages = $msg_stmt->fetchAll();
    
    $last_msg_id = 0;
    
    // Đánh dấu đã đọc tin nhắn từ admin
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE field_id IS NULL AND sender_id = :aid AND receiver_id = :uid")
        ->execute(['aid' => $admin_id, 'uid' => $owner_id]);

} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}

$page_title = 'Liên hệ Ban Quản Trị (Admin)';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<style>
.chat-message { display: flex; flex-direction: column; max-width: 70%; margin-bottom: 10px; }
.chat-message.me { align-self: flex-end; align-items: flex-end; }
.chat-message.them { align-self: flex-start; align-items: flex-start; }
.message-bubble { padding: 10px 15px; border-radius: 12px; font-size: 14px; line-height: 1.4; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
.chat-message.me .message-bubble { background: var(--primary); color: #fff; border: none; }
.chat-message.them .message-bubble { background: #fff; color: var(--text); border: 1px solid var(--border); }
.message-time { font-size: 9px; margin-top: 4px; color: var(--text-muted); }
.chat-message.me .message-time { color: rgba(0,0,0,0.5); }
</style>

<div class="card" style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; height: 580px; overflow: hidden; border: 1px solid var(--border); background: #fff;">
    <!-- Chat Header -->
    <div style="padding: 15px 25px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 15px; background: #fff; flex-shrink: 0;">
        <div style="width: 44px; height: 44px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px;">
            AD
        </div>
        <div>
            <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--dark);"><?php echo htmlspecialchars($admin_name); ?></h3>
            <small style="color: var(--text-muted);">Kênh liên hệ chính thức hỗ trợ Chủ Sân (Quản trị hệ thống)</small>
        </div>
    </div>

    <!-- Chat Messages -->
    <div id="chat-box" 
         data-field-id="null"
         data-receiver-id="<?php echo $admin_id; ?>"
         data-is-admin-chat="1"
         data-last-id="0"
         style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background: #f8fafc;">
        <?php if (count($messages) == 0): ?>
            <div style="margin: auto; text-align: center; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; gap: 10px;">
                <i data-lucide="message-square" style="width: 40px; height: 40px; color: var(--border);"></i>
                <p style="font-size: 13px; margin: 0;">Gửi tin nhắn đầu tiên cho Ban Quản trị để yêu cầu hỗ trợ hoặc giải đáp thắc mắc.</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $m): 
                $by_me = ($m['sender_id'] === $owner_id);
                $last_msg_id = max($last_msg_id, $m['id']);
            ?>
                <div class="chat-message <?php echo $by_me ? 'me' : 'them'; ?>">
                    <div class="message-bubble">
                        <?php echo nl2br(htmlspecialchars($m['content'])); ?>
                    </div>
                    <div class="message-time" style="display: flex; gap: 8px; justify-content: <?php echo $by_me ? 'flex-end' : 'flex-start'; ?>; align-items: center;">
                        <?php if (!$by_me): ?>
                        <button class="translate-btn" data-text="<?php echo htmlspecialchars($m['content']); ?>" style="background: none; border: none; font-size: 10px; color: var(--primary, #00bfa6); cursor: pointer; padding: 0 5px; display: inline-flex; align-items: center; gap: 3px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg> Dịch
                        </button>
                        <?php endif; ?>
                        <div><?php echo date('H:i d/m', strtotime($m['created_at'])); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <script>document.getElementById('chat-box').setAttribute('data-last-id', '<?php echo $last_msg_id; ?>');</script>
        <?php endif; ?>
    </div>

    <!-- Chat Input -->
    <div style="padding: 15px 20px; background: #fff; border-top: 1px solid var(--border); flex-shrink: 0;">
        <form id="chat-form" method="POST" style="display: flex; gap: 10px; margin: 0;">
            <input type="hidden" name="action" value="send_message">
            <input type="text" id="chat-input" name="content" placeholder="Nhập tin nhắn của bạn gửi tới Admin..." required autocomplete="off" style="flex: 1; padding: 10px 15px; border: 1px solid var(--border); border-radius: 20px; outline: none; font-size: 14px;">
            <button type="submit" class="btn btn-primary" style="border-radius: 50%; width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="send" style="width: 18px; height: 18px;"></i>
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    });
</script>

<script src="../assets/js/realtime_chat.js"></script>
<?php include '../includes/dashboard_footer.php'; ?>
