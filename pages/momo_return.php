<?php
require_once '../config/db.php';
require_once '../config/payment.php';
require_once '../includes/auth_helper.php';
require_once '../includes/booking_helper.php';
$page_title = 'Kết Quả Thanh Toán MoMo';
$base_url = '../';
include '../includes/header.php';

$resultCode = $_GET['resultCode'] ?? '-1';
$orderId = $_GET['orderId'] ?? '';
$message = $_GET['message'] ?? '';

// Cập nhật CSDL trực tiếp trên Return URL (Vì MoMo không thể gọi IPN tới localhost)
if ($resultCode == '0' && !empty($orderId)) {
    try {
        $booking_id = explode('_', $orderId)[1];
        
        // Kiểm tra xem đã update chưa
        $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $status = $stmt->fetchColumn();
        
        if ($status === 'pending') {
            // Update payments
            $pdo->prepare("UPDATE payments SET status = 'success', paid_at = NOW() WHERE momo_order_id = ?")
                ->execute([$orderId]);
            
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
        <?php if ($resultCode == '0'): ?>
            <i data-lucide="check-circle" style="color: #10b981; width: 64px; height: 64px; margin-bottom: 20px;"></i>
            <h2 style="color: #10b981; margin-bottom: 12px; font-weight: 800;">Thanh Toán MoMo Thành Công!</h2>
            <p style="color: var(--text-muted); margin-bottom: 8px;">Cảm ơn bạn đã sử dụng dịch vụ thanh toán MoMo.</p>
            <p style="color: var(--text-muted); margin-bottom: 30px;">Mã đơn: <b style="color: var(--text-main);"><?php echo htmlspecialchars($orderId); ?></b></p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <a href="../pages/my_bookings.php" class="btn btn-primary">Xem Đơn Đặt Sân</a>
                <a href="../index.php" class="btn btn-outline">Về Trang Chủ</a>
            </div>
        <?php else: ?>
            <i data-lucide="x-circle" style="color: #ef4444; width: 64px; height: 64px; margin-bottom: 20px;"></i>
            <h2 style="color: #ef4444; margin-bottom: 12px; font-weight: 800;">Thanh Toán Thất Bại</h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;">Giao dịch qua MoMo không thành công (<?php echo htmlspecialchars($message); ?>). Vui lòng kiểm tra lại số dư hoặc thử thanh toán lại.</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <a href="../index.php" class="btn btn-primary">Về Trang Chủ</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
