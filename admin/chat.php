                                                                                                                                                                                                                                                                <?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('admin');
$admin_id = $_SESSION['user_id'];

$active_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$msg = '';

// 1. Xử lý gửi tin nhắn hệ thống từ Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $content = trim($_POST['content']);
    if (!empty($content) && $active_user_id > 0) {
        try {
            $stmt_send = $pdo->prepare("
                INSERT INTO messages (field_id, sender_id, receiver_id, content) 
                VALUES (NULL, ?, ?, ?)
            ");
            $stmt_send->execute([$admin_id, $active_user_id, $content]);
            
            // Create notification for receiver
            $sender_name = 'Ban Quản Trị';
            $notif_title = '💬 Tin nhắn mới từ ' . $sender_name;
            $notif_body = 'Hệ thống gửi bạn tin nhắn: "' . mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : '') . '"';
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon) VALUES (?, 'new_message', ?, ?, 'message', NULL, 'message-square')");
            $notif_stmt->execute([$active_user_id, $notif_title, $notif_body]);
            
            header("Location: chat.php?user_id=" . $active_user_id);
            exit;
        } catch (PDOException $e) {
            $msg = "<span style='color:#dc2626;'>Lỗi: " . $e->getMessage() . "</span>";
        }
    }
}

// 2. Lấy danh sách hội thoại hệ thống (field_id IS NULL)
try {
    $stmt_convs = $pdo->prepare("
        SELECT 
            CASE WHEN m.sender_id = :admin_id1 THEN m.receiver_id ELSE m.sender_id END as user_id,
            u.full_name as user_name,
            u.role as user_role,
            MAX(m.created_at) as last_msg_time,
            SUM(CASE WHEN m.receiver_id = :admin_id2 AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
        FROM messages m
        JOIN users u ON u.id = CASE WHEN m.sender_id = :admin_id3 THEN m.receiver_id ELSE m.sender_id END
        WHERE m.field_id IS NULL
        GROUP BY user_id
        ORDER BY last_msg_time DESC
    ");
    $stmt_convs->execute([
        'admin_id1' => $admin_id,
        'admin_id2' => $admin_id,
        'admin_id3' => $admin_id
    ]);
    $conversations = $stmt_convs->fetchAll();
} catch (PDOException $e) {
    die("Lỗi lấy danh sách hội thoại: " . $e->getMessage());
}

// 3. Lấy thông tin tất cả người dùng để làm ô tìm kiếm tạo cuộc trò chuyện mới
$all_users = [];
try {
    $stmt_users = $pdo->prepare("SELECT id, full_name, email, role FROM users WHERE id != ? ORDER BY full_name ASC");
    $stmt_users->execute([$admin_id]);
    $all_users = $stmt_users->fetchAll();
} catch (PDOException $e) {
    // Bỏ qua lỗi
}

// 4. Nếu đang mở cuộc trò chuyện với user cụ thể
$chat_messages = [];
$active_user_info = null;

if ($active_user_id > 0) {
    try {
        $stmt_user = $pdo->prepare("SELECT id, full_name, role, email FROM users WHERE id = ?");
        $stmt_user->execute([$active_user_id]);
        $active_user_info = $stmt_user->fetch();

        if ($active_user_info) {
            $last_msg_id = 0;
            // Đánh dấu đã đọc
            $stmt_read = $pdo->prepare("
                UPDATE messages 
                SET is_read = 1 
                WHERE field_id IS NULL AND sender_id = ? AND receiver_id = ?
            ");
            $stmt_read->execute([$active_user_id, $admin_id]);

            // Lấy lịch sử chat
            $stmt_chat = $pdo->prepare("
                SELECT * FROM messages 
                WHERE field_id IS NULL 
                AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                ORDER BY created_at ASC
            ");
            $stmt_chat->execute([$admin_id, $active_user_id, $active_user_id, $admin_id]);
            $chat_messages = $stmt_chat->fetchAll();
        }
    } catch (PDOException $e) {
        die("Lỗi lấy lịch sử chat: " . $e->getMessage());
    }
}

// Thêm cuộc trò chuyện ảo vào danh sách hiển thị nếu chưa có tin nhắn nào nhưng admin chọn nhắn tin mới
$conversation_user_ids = array_column($conversations, 'user_id');
if ($active_user_info && !in_array($active_user_id, $conversation_user_ids)) {
    array_unshift($conversations, [
        'user_id' => $active_user_info['id'],
        'user_name' => $active_user_info['full_name'],
        'user_role' => $active_user_info['role'],
        'last_msg_time' => date('Y-m-d H:i:s'),
        'unread_count' => 0
    ]);
}

$page_title = 'Hộp thư Hệ thống (Admin)';
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

<div class="card" style="display: grid; grid-template-columns: 320px 1fr; height: 600px; overflow: hidden; border: 1px solid var(--border);">
    <!-- Khung trái: Danh sách cuộc trò chuyện -->
    <div style="border-right: 1px solid var(--border); display: flex; flex-direction: column; background: #fff; min-height: 0; height: 100%;">
        <div style="padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; flex-direction: column; gap: 10px;">
            <div style="font-weight: 700; color: var(--dark); font-size: 16px;">Chat Hỗ trợ Hệ thống</div>
            
            <!-- Tìm kiếm người dùng mới để chat -->
            <form method="GET" style="display: flex; gap: 5px;">
                <select name="user_id" onchange="this.form.submit()" style="flex: 1; padding: 8px; border-radius: 6px; border: 1px solid var(--border); font-size: 13px; outline: none; background: #f8fafc;">
                    <option value="">-- Tạo cuộc trò chuyện mới --</option>
                    <?php foreach ($all_users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php if($active_user_id === $u['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo $u['role'] === 'owner' ? 'Chủ Sân' : 'Khách'; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column;">
            <?php if (count($conversations) > 0): ?>
                <?php foreach ($conversations as $c): 
                    $is_active = ($c['user_id'] === $active_user_id);
                ?>
                    <a href="chat.php?user_id=<?php echo $c['user_id']; ?>" 
                       style="padding: 12px 20px; text-decoration: none; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; background: <?php echo $is_active ? 'rgba(0,191,166,0.08)' : 'transparent'; ?>; transition: 0.2s;">
                        
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $c['user_role'] === 'owner' ? '#3b82f6' : 'var(--primary)'; ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                            <?php echo strtoupper(substr($c['user_name'], 0, 1)); ?>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                                <strong style="font-size: 14px; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;"><?php echo htmlspecialchars($c['user_name']); ?></strong>
                                <?php if ($c['unread_count'] > 0): ?>
                                    <span style="background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: 700;"><?php echo $c['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <span style="font-size: 11px; background: <?php echo $c['user_role'] === 'owner' ? '#dbeafe; color: #2563eb;' : '#f1f5f9; color: #475569;'; ?>; padding: 2px 6px; border-radius: 10px; font-weight: 500;">
                                <?php echo $c['user_role'] === 'owner' ? 'Chủ sân' : 'Khách hàng'; ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 13px;">
                    Chưa có cuộc trò chuyện hệ thống nào.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Khung phải: Phòng chat chi tiết -->
    <div style="display: flex; flex-direction: column; background: #f8fafc; min-height: 0; height: 100%;">
        <?php if ($active_user_info): ?>
            <!-- Thanh tiêu đề chat -->
            <div style="padding: 15px 25px; border-bottom: 1px solid var(--border); background: #fff; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--dark);"><?php echo htmlspecialchars($active_user_info['full_name']); ?></h3>
                    <small style="color: var(--text-muted);">Email: <?php echo htmlspecialchars($active_user_info['email']); ?> | Vai trò: <strong><?php echo $active_user_info['role'] === 'owner' ? 'Chủ Sân' : 'Khách hàng'; ?></strong></small>
                </div>
            </div>

            <!-- Khung chứa tin nhắn -->
            <div id="chat-box" 
                 data-field-id="null"
                 data-receiver-id="<?php echo $active_user_id; ?>"
                 data-is-admin-chat="1"
                 data-last-id="0"
                 style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($chat_messages as $m): 
                    $by_me = ($m['sender_id'] === $admin_id);
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
            </div>

            <!-- Khung nhập và gửi tin nhắn -->
            <div style="padding: 15px 20px; background: #fff; border-top: 1px solid var(--border);">
                <form id="chat-form" method="POST" style="display: flex; gap: 10px;">
                    <input type="hidden" name="action" value="send_message">
                    <input type="text" id="chat-input" name="content" placeholder="Nhập tin nhắn hệ thống gửi tới người dùng..." required autocomplete="off" style="flex: 1; padding: 10px 15px; border: 1px solid var(--border); border-radius: 20px; outline: none; font-size: 14px;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 50%; width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="send" style="width: 18px; height: 18px;"></i>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Placeholder chưa chọn cuộc trò chuyện -->
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); gap: 12px;">
                <i data-lucide="message-square" style="width: 50px; height: 50px; stroke-width: 1.5; color: var(--border);"></i>
                <p style="font-size: 14px; margin: 0;">Vui lòng chọn một người dùng từ danh sách hoặc dropdown tìm kiếm bên trái để bắt đầu chat.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Tự động cuộn chat xuống dưới cùng khi mở phòng chat
    document.addEventListener('DOMContentLoaded', function() {
        var chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    });
</script>

<script src="../assets/js/realtime_chat.js"></script>
<?php include '../includes/dashboard_footer.php'; ?>
