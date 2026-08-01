<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';
require_once '../includes/booking_helper.php';

require_login('admin');
$msg = '';

// Xử lý cập nhật trạng thái đơn đặt sân hoặc thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = intval($_POST['booking_id']);
    
    if ($_POST['action'] === 'update_status') {
        $status = $_POST['status'];
        $cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : '';
        if ($status === 'cancelled' && $cancel_reason !== '') {
            $cancel_reason = "Admin hủy: " . $cancel_reason;
        }

        if (in_array($status, ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])) {
            try {
                if (updateBookingStatus($booking_id, $status, $pdo, $cancel_reason)) {
                    $msg = "Đã cập nhật trạng thái đơn đặt sân thành công và gửi thông báo tới khách hàng!";
                } else {
                    $msg = "<span style='color:#dc2626;'>Lỗi: Không tìm thấy thông tin đơn đặt sân tương ứng.</span>";
                }
            } catch (Exception $e) {
                $msg = "<span style='color:#dc2626;'>Lỗi hệ thống: " . $e->getMessage() . "</span>";
            }
        }
    } elseif ($_POST['action'] === 'update_payment') {
        $payment_status = $_POST['payment_status'];
        if (in_array($payment_status, ['unpaid', 'paid', 'refunded'])) {
            $stmt = $pdo->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
            $stmt->execute([$payment_status, $booking_id]);
            $msg = "Đã cập nhật trạng thái thanh toán thành công!";
            
            if ($payment_status === 'paid') {
                awardPoints($booking_id, $pdo);
            }
        }
    }
}

// Lấy bộ lọc và tìm kiếm
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_payment = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Tạo câu truy vấn
$sql = "
    SELECT b.*, f.name as field_name, u.full_name as customer_name, u.phone as customer_phone 
    FROM bookings b
    JOIN fields f ON b.field_id = f.id
    JOIN users u ON b.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($filter_status !== '') {
    $sql .= " AND b.status = ?";
    $params[] = $filter_status;
}
if ($filter_payment !== '') {
    $sql .= " AND b.payment_status = ?";
    $params[] = $filter_payment;
}
if ($search !== '') {
    $sql .= " AND (b.booking_code LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$page_title = 'Quản lý Đơn đặt sân';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h2 style="margin: 0;">Quản lý Đơn đặt sân (Hệ thống)</h2>
        
        <!-- Tìm kiếm và Lọc -->
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="text" name="search" placeholder="Mã đơn, Tên khách, SĐT..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 15px; border-radius: 6px; border: 1px solid var(--border); outline: none; min-width: 220px;">
            
            <select name="status" onchange="this.form.submit()" style="padding: 8px 15px; border-radius: 6px; border: 1px solid var(--border); outline: none;">
                <option value="">-- Trạng thái đơn --</option>
                <option value="pending" <?php if($filter_status === 'pending') echo 'selected'; ?>>Chờ duyệt</option>
                <option value="confirmed" <?php if($filter_status === 'confirmed') echo 'selected'; ?>>Đã xác nhận</option>
                <option value="completed" <?php if($filter_status === 'completed') echo 'selected'; ?>>Đã hoàn thành</option>
                <option value="cancelled" <?php if($filter_status === 'cancelled') echo 'selected'; ?>>Đã hủy</option>
                <option value="no_show" <?php if($filter_status === 'no_show') echo 'selected'; ?>>Vắng mặt (No Show)</option>
            </select>

            <select name="payment_status" onchange="this.form.submit()" style="padding: 8px 15px; border-radius: 6px; border: 1px solid var(--border); outline: none;">
                <option value="">-- Trạng thái thanh toán --</option>
                <option value="unpaid" <?php if($filter_payment === 'unpaid') echo 'selected'; ?>>Chưa thanh toán</option>
                <option value="paid" <?php if($filter_payment === 'paid') echo 'selected'; ?>>Đã thanh toán</option>
                <option value="refunded" <?php if($filter_payment === 'refunded') echo 'selected'; ?>>Đã hoàn tiền</option>
            </select>
            
            <button type="submit" class="btn btn-primary" style="padding: 8px 15px; border-radius: 6px; display: flex; align-items: center; gap: 5px;">
                <i data-lucide="search" style="width: 16px; height: 16px;"></i> Lọc
            </button>
            
            <?php if($search !== '' || $filter_status !== '' || $filter_payment !== ''): ?>
                <a href="bookings.php" class="btn-logout" style="padding: 8px 15px; border-radius: 6px; border: 1px solid var(--border); text-decoration: none; color: var(--text-muted); display: inline-flex; align-items: center;">Xóa lọc</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--border); font-weight: 500;">
            <i data-lucide="info" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Mã đặt sân</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Khách hàng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Sân bóng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Lịch đá</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Thành tiền & TT</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Cập nhật Trạng thái</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Cập nhật Thanh toán</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px 20px;">
                                    <strong style="color: var(--primary);"><?php echo htmlspecialchars($b['booking_code']); ?></strong><br>
                                    <small style="color: var(--text-muted);">Đặt: <?php echo date('d/m/Y H:i', strtotime($b['created_at'])); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo htmlspecialchars($b['customer_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($b['customer_phone']); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php echo htmlspecialchars($b['field_name']); ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo date('d/m/Y', strtotime($b['booking_date'])); ?></strong><br>
                                    <span style="font-size: 13px; color: var(--primary); font-weight: 600;"><?php echo date('H:i', strtotime($b['start_time'])) . ' - ' . date('H:i', strtotime($b['end_time'])); ?></span>
                                    <small style="color: var(--text-muted);">(<?php echo $b['duration']; ?>h)</small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <strong style="color: #ef4444;"><?php echo number_format($b['total_price'], 0, ',', '.'); ?>đ</strong><br>
                                    <small style="text-transform: uppercase; color: var(--text-muted);"><?php echo $b['payment_method']; ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <form method="POST" style="display: flex; gap: 5px; align-items: center;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <select name="status" onchange="if(checkCancelStatus(this)) this.form.submit(); else this.value='<?php echo $b['status']; ?>';" style="padding: 6px; border-radius: 4px; border: 1px solid var(--border); font-size: 12px; outline: none; background: #fff; font-weight: 500;">
                                            <option value="pending" <?php if($b['status'] === 'pending') echo 'selected'; ?>>Chờ duyệt</option>
                                            <option value="confirmed" <?php if($b['status'] === 'confirmed') echo 'selected'; ?>>Đã xác nhận</option>
                                            <option value="completed" <?php if($b['status'] === 'completed') echo 'selected'; ?>>Hoàn thành</option>
                                            <option value="cancelled" <?php if($b['status'] === 'cancelled') echo 'selected'; ?>>Hủy bỏ</option>
                                            <option value="no_show" <?php if($b['status'] === 'no_show') echo 'selected'; ?>>Không đến</option>
                                        </select>
                                    </form>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <form method="POST" style="display: flex; gap: 5px; align-items: center;">
                                        <input type="hidden" name="action" value="update_payment">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <select name="payment_status" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px; border: 1px solid var(--border); font-size: 12px; outline: none; background: #fff; font-weight: 500;">
                                            <option value="unpaid" <?php if($b['payment_status'] === 'unpaid') echo 'selected'; ?>>Chưa thanh toán</option>
                                            <option value="paid" <?php if($b['payment_status'] === 'paid') echo 'selected'; ?>>Đã thanh toán</option>
                                            <option value="refunded" <?php if($b['payment_status'] === 'refunded') echo 'selected'; ?>>Đã hoàn tiền</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Không tìm thấy đơn đặt sân nào.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function checkCancelStatus(selectElement) {
    if (selectElement.value === 'cancelled') {
        let reason = prompt('Vui lòng nhập lý do hủy đơn đặt sân này:');
        if (reason === null || reason.trim() === '') {
            if (reason !== null) alert('Lý do hủy không được để trống.');
            return false;
        }
        let input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'cancel_reason';
        input.value = reason;
        selectElement.form.appendChild(input);
    }
    return true;
}
</script>

<?php include '../includes/dashboard_footer.php'; ?>
