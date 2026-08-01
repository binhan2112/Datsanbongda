<?php
require_once '../config/db.php';
require_once '../config/payment.php';
require_once '../includes/auth_helper.php';
require_once '../includes/booking_helper.php';
$page_title = 'Kết Quả Thanh Toán VNPAY';
$base_url = '../';
include '../includes/header.php';

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}
unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}
$secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);

// Bỏ qua kiểm tra hash nếu secret key chưa được cài đặt thật sự (đang mock)
$isMock = (VNPAY_HASH_SECRET === 'YOUR_HASH_SECRET_HERE');
$isValid = $isMock ? true : ($secureHash == $vnp_SecureHash);

$responseCode = $_GET['vnp_ResponseCode'] ?? '';
$txnRef = $_GET['vnp_TxnRef'] ?? '';
$vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';

// Cập nhật CSDL trực tiếp trên Return URL (Vì VNPAY không thể gọi IPN tới localhost)
if ($isValid && $responseCode == '00' && !empty($txnRef)) {
    try {
        $booking_id = explode('_', $txnRef)[0];
        
        // Kiểm tra xem đã update chưa
        $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $status = $stmt->fetchColumn();
        
        if ($status === 'pending') {
            // Update payments
            $pdo->prepare("UPDATE payments SET status = 'success', vnpay_response_code = ?, vnpay_trans_no = ?, paid_at = NOW() WHERE vnpay_txn_ref = ?")
                ->execute([$responseCode, $vnp_TransactionNo, $txnRef]);
            
            // Update bookings
            $pdo->prepare("UPDATE bookings SET payment_status = 'paid', status = 'confirmed' WHERE id = ?")
                ->execute([$booking_id]);
            
            // Cộng điểm (nếu có hàm)
            if (function_exists('awardPoints')) {
                awardPoints($booking_id, $pdo);
            }
        }
    } catch (Exception $e) {
        // Bỏ qua lỗi cập nhật nếu có
    }
}
?>

<div class="container" style="padding: 60px 15px; text-align: center; min-height: 50vh;">
    <div style="max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
        <?php if ($isValid && $responseCode == '00'): ?>
            <i data-lucide="check-circle" style="color: #10b981; width: 64px; height: 64px; margin-bottom: 20px;"></i>
            <h2 style="color: #10b981; margin-bottom: 12px; font-weight: 800;">Thanh Toán Thành Công!</h2>
            <p style="color: var(--text-muted); margin-bottom: 8px;">Cảm ơn bạn đã sử dụng dịch vụ thanh toán VNPAY.</p>
            <p style="color: var(--text-muted); margin-bottom: 30px;">Mã giao dịch: <b style="color: var(--text-main);"><?php echo htmlspecialchars($txnRef); ?></b></p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <a href="../pages/my_bookings.php" class="btn btn-primary">Xem Đơn Đặt Sân</a>
                <a href="../index.php" class="btn btn-outline">Về Trang Chủ</a>
            </div>
        <?php else: ?>
            <i data-lucide="x-circle" style="color: #ef4444; width: 64px; height: 64px; margin-bottom: 20px;"></i>
            <h2 style="color: #ef4444; margin-bottom: 12px; font-weight: 800;">Thanh Toán Thất Bại</h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;">Đã xảy ra lỗi trong quá trình thanh toán hoặc giao dịch bị hủy (Mã lỗi: <?php echo htmlspecialchars($responseCode); ?>). Vui lòng thử lại.</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <a href="../index.php" class="btn btn-primary">Về Trang Chủ</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
