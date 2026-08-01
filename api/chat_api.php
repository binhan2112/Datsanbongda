<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'fetch') {
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    $field_id = isset($_GET['field_id']) && $_GET['field_id'] !== 'null' ? intval($_GET['field_id']) : null;
    $receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;
    $is_admin = isset($_GET['is_admin']) && $_GET['is_admin'] === '1';

    if ($receiver_id === 0) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin người nhận']);
        exit;
    }

    try {
        if ($is_admin) {
            // Đánh dấu đã đọc
            $stmt_read = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE field_id IS NULL AND sender_id = ? AND receiver_id = ? AND id > ?");
            $stmt_read->execute([$receiver_id, $user_id, $last_id]);

            $stmt = $pdo->prepare("
                SELECT * FROM messages 
                WHERE field_id IS NULL 
                AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                AND id > ?
                ORDER BY created_at ASC
            ");
            $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id, $last_id]);
        } else {
            // Đánh dấu đã đọc
            $stmt_read = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE field_id = ? AND sender_id = ? AND receiver_id = ? AND id > ?");
            $stmt_read->execute([$field_id, $receiver_id, $user_id, $last_id]);

            $stmt = $pdo->prepare("
                SELECT * FROM messages 
                WHERE field_id = ? 
                AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                AND id > ?
                ORDER BY created_at ASC
            ");
            $stmt->execute([$field_id, $user_id, $receiver_id, $receiver_id, $user_id, $last_id]);
        }

        $messages = $stmt->fetchAll();
        $formatted_messages = [];
        foreach ($messages as $m) {
            $formatted_messages[] = [
                'id' => $m['id'],
                'content' => nl2br(htmlspecialchars($m['content'])),
                'time' => date('H:i d/m', strtotime($m['created_at'])),
                'is_me' => ($m['sender_id'] === $user_id)
            ];
        }

        echo json_encode(['success' => true, 'messages' => $formatted_messages]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif ($action === 'send') {
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $field_id = isset($_POST['field_id']) && $_POST['field_id'] !== 'null' ? intval($_POST['field_id']) : null;
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] === '1';

    if (empty($content) || $receiver_id === 0) {
        echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    try {
        if ($is_admin) {
            $insert = $pdo->prepare("INSERT INTO messages (field_id, sender_id, receiver_id, content) VALUES (NULL, ?, ?, ?)");
            $insert->execute([$user_id, $receiver_id, $content]);
            $msg_id = $pdo->lastInsertId();

            // Create notification
            $sender_name = $_SESSION['user_full_name'] ?? 'Ban Quản Trị / Khách hàng';
            if ($_SESSION['user_role'] === 'admin') {
                $sender_name = 'Ban Quản Trị';
            }
            $notif_title = '💬 Tin nhắn mới từ ' . $sender_name;
            $notif_body = 'Bạn có tin nhắn mới: "' . mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : '') . '"';
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon) VALUES (?, 'new_message', ?, ?, 'message', NULL, 'message-square')");
            $notif_stmt->execute([$receiver_id, $notif_title, $notif_body]);
            
        } else {
            $insert = $pdo->prepare("INSERT INTO messages (field_id, sender_id, receiver_id, content) VALUES (?, ?, ?, ?)");
            $insert->execute([$field_id, $user_id, $receiver_id, $content]);
            $msg_id = $pdo->lastInsertId();

            // Create notification
            $sender_name = $_SESSION['user_full_name'] ?? 'Người dùng';
            $notif_title = '💬 Tin nhắn mới từ ' . $sender_name;
            $notif_body = 'Bạn có tin nhắn mới: "' . mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : '') . '"';
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon) VALUES (?, 'new_message', ?, ?, 'message', ?, 'message-square')");
            $notif_stmt->execute([$receiver_id, $notif_title, $notif_body, $field_id]);
        }

        echo json_encode([
            'success' => true,
            'message' => [
                'id' => $msg_id,
                'content' => nl2br(htmlspecialchars($content)),
                'time' => date('H:i d/m'),
                'is_me' => true
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
