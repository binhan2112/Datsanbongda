<?php
header('Content-Type: application/json');
require_once '../../../config/db.php';
require_once '../../../includes/booking_helper.php'; // Reuse existing logic if available

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

if ($field_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu đầu vào không hợp lệ']);
    exit;
}

try {
    // Lấy thông tin giờ mở cửa/đóng cửa của sân
    $stmt = $pdo->prepare("SELECT open_time, close_time FROM fields WHERE id = :id AND status = 'active'");
    $stmt->execute(['id' => $field_id]);
    $field = $stmt->fetch();

    if (!$field) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy sân bóng']);
        exit;
    }

    $open_hour = (int)date('H', strtotime($field['open_time']));
    $close_hour = (int)date('H', strtotime($field['close_time']));
    if ($close_hour <= $open_hour) $close_hour += 24; // Nếu đóng cửa qua ngày hôm sau

    // Lấy danh sách booking đã đặt
    $stmtBookings = $pdo->prepare("SELECT start_time, end_time FROM bookings 
                                   WHERE field_id = :id AND booking_date = :date AND status IN ('pending', 'confirmed')");
    $stmtBookings->execute(['id' => $field_id, 'date' => $date]);
    $bookings = $stmtBookings->fetchAll();

    // Lấy danh sách giờ không khả dụng (bảo trì)
    $stmtUnavail = $pdo->prepare("SELECT start_time, end_time FROM field_unavailable 
                                  WHERE field_id = :id AND unavail_date = :date");
    $stmtUnavail->execute(['id' => $field_id, 'date' => $date]);
    $unavailables = $stmtUnavail->fetchAll();

    // Tạo các slot 1 tiếng
    $slots = [];
    for ($h = $open_hour; $h < $close_hour; $h++) {
        $real_h = $h % 24;
        $start = sprintf("%02d:00:00", $real_h);
        $end = sprintf("%02d:00:00", ($real_h + 1) % 24);
        
        $is_available = true;
        
        // Kiểm tra booking
        foreach ($bookings as $b) {
            if ($start >= $b['start_time'] && $start < $b['end_time']) {
                $is_available = false;
                break;
            }
        }

        // Kiểm tra block bảo trì
        if ($is_available) {
            foreach ($unavailables as $u) {
                if ($start >= $u['start_time'] && $start < $u['end_time']) {
                    $is_available = false;
                    break;
                }
            }
        }

        // Nếu là ngày hôm nay, bỏ qua các giờ trong quá khứ
        if ($date == date('Y-m-d')) {
            $current_hour = (int)date('H');
            if ($real_h <= $current_hour) {
                $is_available = false;
            }
        }

        $slots[] = [
            'start_time' => $start,
            'end_time' => $end,
            'is_available' => $is_available
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'date' => $date,
            'slots' => $slots
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>
