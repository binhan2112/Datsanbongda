<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';
require_once '../includes/booking_helper.php';

require_login('owner');
$owner_id = $_SESSION['user_id'];

// Xử lý cập nhật trạng thái đơn đặt sân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status'];
    $cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : '';
    if ($new_status === 'cancelled' && $cancel_reason !== '') {
        $cancel_reason = "Chủ sân hủy: " . $cancel_reason;
    }
    
    // Kiểm tra đơn này có thuộc sân của owner này không
    $stmt = $pdo->prepare("SELECT b.id FROM bookings b JOIN fields f ON b.field_id = f.id WHERE b.id = ? AND f.owner_id = ?");
    $stmt->execute([$booking_id, $owner_id]);
    if ($stmt->fetch()) {
        try {
            if (updateBookingStatus($booking_id, $new_status, $pdo, $cancel_reason)) {
                $msg = "Cập nhật trạng thái thành công!";
            } else {
                $msg = "<span style='color:#dc2626;'>Lỗi: Cập nhật trạng thái thất bại.</span>";
            }
        } catch (Exception $e) {
            $msg = "<span style='color:#dc2626;'>Lỗi hệ thống: " . $e->getMessage() . "</span>";
        }
    }
}

// Lấy danh sách đơn đặt sân
$stmt = $pdo->prepare("
    SELECT b.*, f.name as field_name, u.full_name as customer_name, u.phone as customer_phone 
    FROM bookings b 
    JOIN fields f ON b.field_id = f.id 
    JOIN users u ON b.user_id = u.id 
    WHERE f.owner_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$owner_id]);
$bookings = $stmt->fetchAll();

$page_title = 'Quản lý Đơn đặt sân';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Danh sách Đơn đặt sân</h2>
    </div>

    <?php if (isset($msg)): ?>
        <div style="background: #dcfce3; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i data-lucide="check-circle" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Mã Đơn</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Khách hàng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Sân</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Thời gian đá</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Tổng tiền</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Trạng thái</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px 20px;"><strong><?php echo $b['booking_code']; ?></strong></td>
                                <td style="padding: 15px 20px;">
                                    <?php echo htmlspecialchars($b['customer_name']); ?><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($b['customer_phone']); ?></small>
                                </td>
                                <td style="padding: 15px 20px;"><?php echo htmlspecialchars($b['field_name']); ?></td>
                                <td style="padding: 15px 20px;">
                                    <?php echo date('d/m/Y', strtotime($b['booking_date'])); ?><br>
                                    <strong style="color: var(--primary);"><?php echo date('H:i', strtotime($b['start_time'])) . ' - ' . date('H:i', strtotime($b['end_time'])); ?></strong>
                                </td>
                                <td style="padding: 15px 20px; font-weight: 600; color: #ef4444;">
                                    <?php echo number_format($b['total_price'], 0, ',', '.'); ?>đ
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php
                                        $status_colors = [
                                            'pending' => ['#fef3c7', '#d97706', 'Chờ duyệt'],
                                            'confirmed' => ['#dbeafe', '#2563eb', 'Đã xác nhận'],
                                            'completed' => ['#dcfce3', '#166534', 'Hoàn thành'],
                                            'cancelled' => ['#fee2e2', '#dc2626', 'Đã hủy']
                                        ];
                                        $sc = $status_colors[$b['status']] ?? ['#f1f5f9', '#64748b', 'Không rõ'];
                                    ?>
                                    <span style="background: <?php echo $sc[0]; ?>; color: <?php echo $sc[1]; ?>; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                        <?php echo $sc[2]; ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <form method="POST" style="display: flex; gap: 5px;" onsubmit="return checkCancelStatus(this);">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <select name="status" style="padding: 6px; border-radius: 4px; border: 1px solid var(--border); outline: none;">
                                            <option value="pending" <?php if($b['status'] == 'pending') echo 'selected'; ?>>Chờ duyệt</option>
                                            <option value="confirmed" <?php if($b['status'] == 'confirmed') echo 'selected'; ?>>Xác nhận</option>
                                            <option value="completed" <?php if($b['status'] == 'completed') echo 'selected'; ?>>Hoàn thành</option>
                                            <option value="cancelled" <?php if($b['status'] == 'cancelled') echo 'selected'; ?>>Hủy</option>
                                        </select>
                                        <button type="submit" style="background: var(--primary); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 500;">Lưu</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Chưa có đơn đặt sân nào.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function checkCancelStatus(formElement) {
    let select = formElement.querySelector('select[name="status"]');
    if (select.value === 'cancelled') {
        let reason = prompt('Vui lòng nhập lý do hủy đơn đặt sân này:');
        if (reason === null) return false;
        if (reason.trim() === '') {
            alert('Lý do hủy không được để trống.');
            return false;
        }
        let input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'cancel_reason';
        input.value = reason;
        formElement.appendChild(input);
    }
    return true;
}
</script>

<?php include '../includes/dashboard_footer.php'; ?>
