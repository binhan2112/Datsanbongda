<?php
header('Content-Type: application/json');
require_once '../../../config/db.php';
require_once '../middleware.php';

$user = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $user['user_id'];
$field_id = isset($input['field_id']) ? intval($input['field_id']) : 0;
$date = isset($input['date']) ? trim($input['date']) : '';
$start_time = isset($input['start_time']) ? trim($input['start_time']) : '';
$duration = isset($input['duration']) ? floatval($input['duration']) : 1;
$payment_method = isset($input['payment_method']) ? trim($input['payment_method']) : 'cash';

if ($field_id <= 0 || empty($date) || empty($start_time) || $duration <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng cung cấp đầy đủ thông tin đặt sân']);
    exit;
}

try {
    // 1. Kiểm tra tồn tại sân
    $stmt = $pdo->prepare("SELECT price_per_hour FROM fields WHERE id = :id AND status = 'active'");
    $stmt->execute(['id' => $field_id]);
    $field = $stmt->fetch();

    if (!$field) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy sân bóng']);
        exit;
    }

    // 2. Tính toán giờ kết thúc và tổng tiền
    $start_timestamp = strtotime("$date $start_time");
    $end_timestamp = $start_timestamp + ($duration * 3600);
    $end_time = date('H:i:s', $end_timestamp);
    $total_price = $field['price_per_hour'] * $duration;

    // 3. TODO: Kiểm tra trùng giờ (giống logic availability)
    $stmtOverlap = $pdo->prepare("SELECT id FROM bookings WHERE field_id = :field_id AND booking_date = :date 
                                  AND status IN ('pending', 'confirmed') 
                                  AND ((start_time < :end_time AND end_time > :start_time))");
    $stmtOverlap->execute([
        'field_id' => $field_id,
        'date' => $date,
        'start_time' => $start_time,
        'end_time' => $end_time
    ]);

    if ($stmtOverlap->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Khung giờ này đã có người đặt, vui lòng chọn giờ khác']);
        exit;
    }

    // 4. Tạo mã đơn hàng
    $booking_code = 'CT' . date('Ymd') . rand(1000, 9999);

    // 5. Lưu vào DB
    $stmtInsert = $pdo->prepare("INSERT INTO bookings (booking_code, field_id, user_id, booking_date, start_time, end_time, duration, total_price, status, payment_method, payment_status) 
                                 VALUES (:code, :field_id, :user_id, :date, :start, :end, :duration, :price, 'pending', :method, 'unpaid')");
    $stmtInsert->execute([
        'code' => $booking_code,
        'field_id' => $field_id,
        'user_id' => $user_id,
        'date' => $date,
        'start' => $start_time,
        'end' => $end_time,
        'duration' => $duration,
        'price' => $total_price,
        'method' => $payment_method
    ]);

    $booking_id = $pdo->lastInsertId();

    // 6. Trả về kết quả
    $response = [
        'status' => 'success',
        'message' => 'Đặt sân thành công',
        'data' => [
            'booking_id' => $booking_id,
            'booking_code' => $booking_code,
            'total_price' => $total_price
        ]
    ];

    // TODO: Tích hợp lấy link thanh toán MoMo/VNPay tại đây nếu payment_method != cash
    if ($payment_method == 'momo') {
        // Pseudo code
        $response['data']['payment_url'] = "https://test-payment.momo.vn/pay?orderId=$booking_code";
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>
