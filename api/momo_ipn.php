<?php
require_once '../config/db.php';
require_once '../config/payment.php';
require_once '../includes/booking_helper.php';

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    // Nếu giả lập gọi GET để test
    $data = $_GET;
}

$orderId = $data['orderId'] ?? '';
$resultCode = $data['resultCode'] ?? '';
$transId = $data['transId'] ?? '';

// Trong thực tế, bạn cần xác minh signature từ MOMO
// signature = hash_hmac('sha256', string to hash, MOMO_SECRET_KEY)
$isMock = (MOMO_SECRET_KEY === 'YOUR_SECRET_KEY_HERE');
$isValid = $isMock ? true : false; // Đơn giản hóa, mặc định chấp nhận nếu mock

if ($isValid && $orderId != '') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE momo_order_id = :orderId LIMIT 1");
        $stmt->execute(['orderId' => $orderId]);
        $payment = $stmt->fetch();

        if ($payment) {
            $bookingId = $payment['booking_id'];
            if ($payment['status'] == 'pending') {
                if ($resultCode == '0') {
                    // Cập nhật payments
                    $pdo->prepare("UPDATE payments SET status = 'success', momo_result_code = :code, momo_trans_id = :transId, paid_at = NOW() WHERE id = :id")
                        ->execute(['code' => $resultCode, 'transId' => $transId, 'id' => $payment['id']]);
                    // Cập nhật bookings
                    $pdo->prepare("UPDATE bookings SET payment_status = 'paid', status = 'confirmed' WHERE id = :id")
                        ->execute(['id' => $bookingId]);
                    // Tích luỹ điểm
                    awardPoints($bookingId, $pdo);
                } else {
                    $pdo->prepare("UPDATE payments SET status = 'failed', momo_result_code = :code WHERE id = :id")
                        ->execute(['code' => $resultCode, 'id' => $payment['id']]);
                }
                echo json_encode(['status' => 'success', 'message' => 'Processed successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Order already processed']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
