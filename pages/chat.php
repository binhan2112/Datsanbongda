<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login();
$user_id = $_SESSION['user_id'];

$url_field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;
$chat_type = isset($_GET['chat_type']) ? $_GET['chat_type'] : '';

// Nếu chủ sân xem tin nhắn của chính sân mình, hướng về owner dashboard chat
if ($_SESSION['user_role'] === 'owner' && $url_field_id > 0) {
    try {
        $stmt_chk = $pdo->prepare("SELECT id FROM fields WHERE id = ? AND owner_id = ?");
        $stmt_chk->execute([$url_field_id, $user_id]);
        if ($stmt_chk->fetch()) {
            header("Location: ../owner/chat.php?field_id=" . $url_field_id);
            exit;
        }
    } catch (PDOException $e) {
        // Bỏ qua
    }
}

// 1. Lấy thông tin Admin để làm hội thoại hệ thống
try {
    $admin_stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
    $admin = $admin_stmt->fetch();
    $admin_id = $admin ? intval($admin['id']) : 2;
    $admin_name = $admin ? $admin['full_name'] : 'Ban Quản Trị';
} catch (PDOException $e) {
    $admin_id = 2;
    $admin_name = 'Ban Quản Trị';
}

$conversations = [];

try {
    // Đọc tin nhắn cuối cùng với admin để lấy thời gian
    $last_admin_stmt = $pdo->prepare("
        SELECT MAX(created_at) as last_time,
               SUM(CASE WHEN receiver_id = :uid AND is_read = 0 THEN 1 ELSE 0 END) as unread
        FROM messages 
        WHERE field_id IS NULL AND (sender_id = :uid2 OR receiver_id = :uid3)
    ");
    $last_admin_stmt->execute(['uid' => $user_id, 'uid2' => $user_id, 'uid3' => $user_id]);
    $last_admin = $last_admin_stmt->fetch();
    
    // Thêm Admin vào danh sách hội thoại
    $conversations[] = [
        'type' => 'admin',
        'field_id' => null,
        'title' => $admin_name,
        'subtitle' => 'Hỗ trợ hệ thống',
        'receiver_id' => $admin_id,
        'last_msg_time' => $last_admin['last_time'] ?? '1970-01-01 00:00:00',
        'unread_count' => intval($last_admin['unread'] ?? 0)
    ];

    // 2. Lấy danh sách hội thoại về các sân bóng
    $fields_stmt = $pdo->prepare("
        SELECT 
            m.field_id,
            f.name as title,
            u.full_name as subtitle,
            u.id as receiver_id,
            MAX(m.created_at) as last_msg_time,
            SUM(CASE WHEN m.receiver_id = :uid AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
        FROM messages m
        JOIN fields f ON m.field_id = f.id
        JOIN users u ON u.id = f.owner_id
        WHERE (m.sender_id = :uid2 OR m.receiver_id = :uid3)
        GROUP BY m.field_id
    ");
    $fields_stmt->execute(['uid' => $user_id, 'uid2' => $user_id, 'uid3' => $user_id]);
    $field_convs = $fields_stmt->fetchAll();

    foreach ($field_convs as $fc) {
        $conversations[] = [
            'type' => 'field',
            'field_id' => intval($fc['field_id']),
            'title' => $fc['title'],
            'subtitle' => 'Chủ sân: ' . $fc['subtitle'],
            'receiver_id' => intval($fc['receiver_id']),
            'last_msg_time' => $fc['last_msg_time'],
            'unread_count' => intval($fc['unread_count'])
        ];
    }
} catch (PDOException $e) {
    die("Lỗi kết nối hội thoại: " . $e->getMessage());
}

// 3. Nếu được gọi chat từ trang sân chi tiết nhưng chưa có tin nhắn nào
if ($url_field_id > 0) {
    $found = false;
    foreach ($conversations as $c) {
        if ($c['type'] === 'field' && $c['field_id'] === $url_field_id) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        try {
            $field_q = $pdo->prepare("
                SELECT f.id, f.name, u.id as owner_id, u.full_name as owner_name 
                FROM fields f 
                JOIN users u ON f.owner_id = u.id 
                WHERE f.id = ? AND f.status = 'active'
            ");
            $field_q->execute([$url_field_id]);
            $new_field = $field_q->fetch();
            if ($new_field) {
                array_unshift($conversations, [
                    'type' => 'field',
                    'field_id' => $new_field['id'],
                    'title' => $new_field['name'],
                    'subtitle' => 'Chủ sân: ' . $new_field['owner_name'],
                    'receiver_id' => $new_field['owner_id'],
                    'last_msg_time' => date('Y-m-d H:i:s'),
                    'unread_count' => 0
                ]);
            }
        } catch (PDOException $e) {
            // Bỏ qua
        }
    }
}

// Sắp xếp các hội thoại theo tin nhắn mới nhất
usort($conversations, function($a, $b) {
    return strcmp($b['last_msg_time'], $a['last_msg_time']);
});

// Xác định hội thoại hoạt động
$active_conv = null;
if ($url_field_id > 0) {
    foreach ($conversations as $c) {
        if ($c['type'] === 'field' && $c['field_id'] === $url_field_id) {
            $active_conv = $c;
            break;
        }
    }
} elseif ($chat_type === 'admin') {
    foreach ($conversations as $c) {
        if ($c['type'] === 'admin') {
            $active_conv = $c;
            break;
        }
    }
}

if (!$active_conv && !empty($conversations)) {
    $active_conv = $conversations[0];
}

// 4. Xử lý gửi tin nhắn mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && $active_conv) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        try {
            $fid = $active_conv['field_id']; // Có thể là NULL
            $rid = $active_conv['receiver_id'];
            
            $insert = $pdo->prepare("INSERT INTO messages (field_id, sender_id, receiver_id, content) VALUES (?, ?, ?, ?)");
            $insert->execute([$fid, $user_id, $rid, $content]);
            
            // Create notification for receiver
            $sender_name = $_SESSION['user_full_name'] ?? 'Khách hàng';
            $notif_title = '💬 Tin nhắn mới từ ' . $sender_name;
            $notif_body = 'Bạn có tin nhắn mới: "' . mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : '') . '"';
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon) VALUES (?, 'new_message', ?, ?, 'message', ?, 'message-square')");
            $notif_stmt->execute([$rid, $notif_title, $notif_body, $fid]);
            
            if ($active_conv['type'] === 'admin') {
                header("Location: chat.php?chat_type=admin");
            } else {
                header("Location: chat.php?field_id=" . $active_conv['field_id']);
            }
            exit;
        } catch (PDOException $e) {
            // Lỗi gửi
        }
    }
}

// Lấy lịch sử tin nhắn của hội thoại hoạt động
$messages = [];
$last_msg_id = 0;
if ($active_conv) {
    try {
        if ($active_conv['type'] === 'admin') {
            // Đánh dấu đã đọc
            $pdo->prepare("UPDATE messages SET is_read = 1 WHERE field_id IS NULL AND sender_id = ? AND receiver_id = ?")
                ->execute([$active_conv['receiver_id'], $user_id]);

            // Lấy lịch sử
            $msg_stmt = $pdo->prepare("
                SELECT * FROM messages 
                WHERE field_id IS NULL 
                AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                ORDER BY created_at ASC
            ");
            $msg_stmt->execute([$user_id, $active_conv['receiver_id'], $active_conv['receiver_id'], $user_id]);
            $messages = $msg_stmt->fetchAll();
        } else {
            // Đánh dấu đã đọc
            $pdo->prepare("UPDATE messages SET is_read = 1 WHERE field_id = ? AND sender_id = ? AND receiver_id = ?")
                ->execute([$active_conv['field_id'], $active_conv['receiver_id'], $user_id]);

            // Lấy lịch sử
            $msg_stmt = $pdo->prepare("
                SELECT * FROM messages 
                WHERE field_id = ? 
                AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                ORDER BY created_at ASC
            ");
            $msg_stmt->execute([$active_conv['field_id'], $user_id, $active_conv['receiver_id'], $active_conv['receiver_id'], $user_id]);
            $messages = $msg_stmt->fetchAll();
        }
    } catch (PDOException $e) {
        // Lỗi lấy tin nhắn
    }
}

$base_url = '../';
$current_page = 'admin_chat';
$page_title = 'Hộp thư Liên hệ';
include '../includes/header.php';
?>

<div class="container" style="margin-top: 30px; margin-bottom: 60px;">
    <div style="display: grid; grid-template-columns: 300px 1fr; border: 1px solid var(--border-color); border-radius: 16px; height: 600px; overflow: hidden; background: var(--bg-card); box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
        
        <!-- Khung bên trái: Danh sách cuộc trò chuyện -->
        <div style="border-right: 1px solid var(--border-color); display: flex; flex-direction: column; background: rgba(255, 255, 255, 0.01); min-height: 0; height: 100%;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border-color); font-weight: 700; color: var(--text-main); font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="message-square" style="color:var(--primary);"></i> Hộp thư của bạn
            </div>
            
            <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column;">
                <?php if (count($conversations) > 0): ?>
                    <?php foreach ($conversations as $c): 
                        $is_active = false;
                        if ($active_conv) {
                            if ($c['type'] === 'admin' && $active_conv['type'] === 'admin') {
                                $is_active = true;
                            } elseif ($c['type'] === 'field' && $active_conv['type'] === 'field' && $c['field_id'] === $active_conv['field_id']) {
                                $is_active = true;
                            }
                        }
                        
                        $link = ($c['type'] === 'admin') ? 'chat.php?chat_type=admin' : 'chat.php?field_id=' . $c['field_id'];
                    ?>
                        <a href="<?php echo $link; ?>" 
                           style="padding: 15px 20px; text-decoration: none; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px; background: <?php echo $is_active ? 'rgba(0,191,166,0.08)' : 'transparent'; ?>; transition: 0.2s;">
                            
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $c['type'] === 'admin' ? '#ef4444' : 'var(--primary)'; ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                                <?php echo $c['type'] === 'admin' ? 'AD' : strtoupper(substr($c['title'], 0, 1)); ?>
                            </div>
                            
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                                    <strong style="font-size: 14px; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;"><?php echo htmlspecialchars($c['title']); ?></strong>
                                    <?php if ($c['unread_count'] > 0): ?>
                                        <span style="background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: 700;"><?php echo $c['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <small style="color: var(--text-muted); display: block; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($c['subtitle']); ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 13px;">
                        Hộp thư trống.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Khung bên phải: Phòng chat chi tiết -->
        <div style="display: flex; flex-direction: column; background: rgba(255, 255, 255, 0.005); min-height: 0; height: 100%;">
            <?php if ($active_conv): ?>
                <!-- Header chat -->
                <div style="padding: 15px 25px; border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01); display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: <?php echo $active_conv['type'] === 'admin' ? '#ef4444' : 'var(--primary)'; ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <?php echo $active_conv['type'] === 'admin' ? 'AD' : strtoupper(substr($active_conv['title'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($active_conv['title']); ?></h3>
                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($active_conv['subtitle']); ?></small>
                    </div>
                </div>

                <!-- Lịch sử tin nhắn -->
                <div id="chat-box" 
                     data-field-id="<?php echo $active_conv['type'] === 'admin' ? 'null' : $active_conv['field_id']; ?>"
                     data-receiver-id="<?php echo $active_conv['receiver_id']; ?>"
                     data-is-admin-chat="<?php echo $active_conv['type'] === 'admin' ? '1' : '0'; ?>"
                     data-last-id="0"
                     style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px;">
                    <?php if (count($messages) == 0): ?>
                        <div style="margin: auto; text-align: center; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; gap: 10px;">
                            <i data-lucide="message-circle" style="width: 40px; height: 40px; color: var(--border-color);"></i>
                            <p style="font-size: 13px; margin: 0;">Bắt đầu gửi câu hỏi hoặc lời chào để nhận được hỗ trợ nhanh nhất.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $m): 
                            $by_me = ($m['sender_id'] === $user_id);
                            $last_msg_id = max($last_msg_id, $m['id']);
                        ?>
                            <div class="chat-message <?php echo $by_me ? 'me' : 'them'; ?>">
                                <div class="message-bubble" style="<?php echo $by_me ? '' : 'background-color: rgba(255,255,255,0.05); color: var(--text-main); border: 1px solid var(--border-color);'; ?>">
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

                <!-- Input nhắn tin -->
                <div style="padding: 15px 20px; background: rgba(255,255,255,0.01); border-top: 1px solid var(--border-color); flex-shrink: 0;">
                    <form id="chat-form" method="POST" style="display: flex; gap: 10px; margin: 0;">
                        <input type="text" id="chat-input" name="content" placeholder="Nhập nội dung tin nhắn gửi đi..." required autocomplete="off" style="flex: 1; padding: 10px 15px; border: 1px solid var(--border-color); border-radius: 20px; outline: none; font-size: 14px; background: var(--bg-card); color: var(--text-main);">
                        <button type="submit" class="btn btn-primary" style="border-radius: 50%; width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="send" style="width: 18px; height: 18px; margin-left: 2px;"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); gap: 12px;">
                    <i data-lucide="message-square" style="width: 50px; height: 50px; stroke-width: 1.5; color: var(--border-color);"></i>
                    <p style="font-size: 14px; margin: 0;">Vui lòng chọn một cuộc trò chuyện từ danh sách bên trái.</p>
                </div>
            <?php endif; ?>
        </div>

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
<?php include '../includes/footer.php'; ?>
