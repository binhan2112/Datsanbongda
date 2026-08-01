<?php
header('Content-Type: application/json');
require_once '../../../config/db.php';
require_once '../middleware.php';

$user = authenticate(); // Returns decoded JWT payload

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$user_id = $user['user_id'];
$status = isset($_GET['status']) ? trim($_GET['status']) : ''; // pending, confirmed, completed, cancelled

try {
    $whereSql = "b.user_id = :user_id";
    $params = ['user_id' => $user_id];

    if (!empty($status)) {
        $whereSql .= " AND b.status = :status";
        $params['status'] = $status;
    }

    $sql = "SELECT b.id, b.booking_code, b.booking_date, b.start_time, b.end_time, b.total_price, 
                   b.status, b.payment_method, b.payment_status, 
                   f.name as field_name, f.address as field_address, f.cover_image
            FROM bookings b
            JOIN fields f ON b.field_id = f.id
            WHERE $whereSql
            ORDER BY b.booking_date DESC, b.start_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $bookings
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>
