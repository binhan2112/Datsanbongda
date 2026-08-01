<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'default';
$user_id = $_SESSION['user_id'];

// ─── Admin live stats endpoint ──────────────────────────────
if ($action === 'admin_live' && ($_SESSION['user_role'] ?? '') === 'admin') {
    try {
        $pending_bookings = intval($pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn());
        $pending_fields   = intval($pdo->query("SELECT COUNT(*) FROM fields WHERE status='pending'")->fetchColumn());
        $today_bookings   = intval($pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_date=CURDATE() AND status NOT IN ('cancelled','no_show')")->fetchColumn());
        $month_revenue    = floatval($pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE MONTH(booking_date)=MONTH(CURDATE()) AND YEAR(booking_date)=YEAR(CURDATE()) AND status IN ('completed','confirmed')")->fetchColumn());

        echo json_encode([
            'success'          => true,
            'pending_bookings' => $pending_bookings,
            'pending_fields'   => $pending_fields,
            'today_bookings'   => $today_bookings,
            'month_revenue'    => $month_revenue,
            'timestamp'        => date('H:i:s'),
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── Default: user notification counts ─────────────────────
try {
    $notif_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $notif_stmt->execute([$user_id]);
    $unread_notifs = intval($notif_stmt->fetchColumn());

    $msg_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $msg_stmt->execute([$user_id]);
    $unread_msgs = intval($msg_stmt->fetchColumn());

    echo json_encode([
        'success' => true,
        'unread_notifications' => $unread_notifs,
        'unread_messages'      => $unread_msgs
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
