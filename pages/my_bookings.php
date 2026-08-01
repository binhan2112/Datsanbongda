<?php
// ═══════════════════════════════════════════════════════
// TRANG LỊCH SỬ ĐẶT SÂN
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login();

$user_id = $_SESSION['user_id'];
$message = '';
$success = '';

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = 'Đặt sân thành công! Sân bóng đã sẵn sàng phục vụ bạn.';
}
if (isset($_GET['cancelled']) && $_GET['cancelled'] == 1) {
    $message = 'Đã hủy đơn đặt sân thành công.';
}
if (isset($_GET['reviewed']) && $_GET['reviewed'] == 1) {
    $success = 'Cảm ơn bạn đã đánh giá! Đánh giá của bạn giúp cộng đồng chọn sân tốt hơn.';
}
if (isset($_GET['error']) && $_GET['error'] === 'already_reviewed') {
    $error_msg = 'Bạn đã viết đánh giá cho lần đặt sân này rồi.';
}

// Xử lý Hủy đơn đặt sân (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $cancel_id = intval($_POST['cancel_booking_id']);
    $cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : 'Người dùng chủ động hủy';
    $cancel_reason_full = "Khách hàng hủy: " . $cancel_reason;
    try {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $cancel_id, 'user_id' => $user_id]);
        $booking = $stmt->fetch();

        if ($booking && in_array($booking['status'], ['pending', 'confirmed'])) {
            $datetime_str = $booking['booking_date'] . ' ' . $booking['start_time'];
            $start_time_ts = strtotime($datetime_str);
            $now_ts = time();
            $diff_mins = floor(($start_time_ts - $now_ts) / 60);
            
            if ($diff_mins < 30 && $diff_mins > 0 && $booking['deposit_amount'] > 0) {
                $cancel_reason_full .= ' (Vi phạm: Hủy sân dưới 30 phút. Phạt: Mất cọc ' . number_format($booking['deposit_amount'], 0, ',', '.') . 'đ)';
            }
            
            $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = :reason WHERE id = :id")
                ->execute(['reason' => $cancel_reason_full, 'id' => $cancel_id]);
            header("Location: my_bookings.php?cancelled=1");
            exit;
        } else {
            $error_msg = $booking ? 'Không thể hủy đơn đặt sân đã hoàn thành hoặc đã bị hủy trước đó.' : 'Đơn đặt sân không tồn tại hoặc bạn không có quyền hủy.';
        }
    } catch (PDOException $e) {
        $error_msg = 'Đã xảy ra lỗi: ' . $e->getMessage();
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, f.name as field_name, f.district as field_district, f.phone as field_phone
        FROM bookings b
        JOIN fields f ON b.field_id = f.id
        WHERE b.user_id = :user_id
        ORDER BY b.booking_date DESC, b.start_time DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $bookings = $stmt->fetchAll();

    // Lấy danh sách booking_id đã review
    $reviewed_bookings = [];
    $review_stmt = $pdo->prepare("SELECT booking_id FROM reviews WHERE user_id = :uid AND booking_id IS NOT NULL");
    $review_stmt->execute(['uid' => $user_id]);
    $reviewed_bookings = $review_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

$base_url = '../';
$current_page = 'bookings';
$page_title = 'Lịch Sử Đặt Sân';
include '../includes/header.php';
?>

    <div class="container bookings-container">
        <h1 style="font-size: 28px; font-weight: 800; margin-bottom: 8px;">Đơn đặt sân của bạn</h1>
        <p style="color: var(--text-muted); margin-bottom: 30px;">Xem lịch đặt, quét QR check-in khi ra sân hoặc thực hiện hủy lịch.</p>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i data-lucide="check-circle"></i><span><?php echo htmlspecialchars($success); ?></span></div>
        <?php endif; ?>
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><i data-lucide="info"></i><span><?php echo htmlspecialchars($message); ?></span></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><i data-lucide="alert-circle"></i><span><?php echo htmlspecialchars($error_msg); ?></span></div>
        <?php endif; ?>

        <?php if (count($bookings) > 0): ?>
            <div>
                <?php foreach ($bookings as $b): ?>
                    <div class="booking-card">
                        <div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <h3 style="font-size: 18px; font-weight: 700;"><?php echo htmlspecialchars($b['field_name']); ?></h3>
                                <span style="font-size: 13px; color: var(--text-muted);">Mã: <b><?php echo htmlspecialchars($b['booking_code']); ?></b></span>
                            </div>
                            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                <i data-lucide="map-pin" style="width: 14px; height: 14px;"></i>
                                Quận <?php echo htmlspecialchars($b['field_district']); ?>
                            </p>
                            <div style="font-size: 15px; display: flex; flex-direction: column; gap: 6px;">
                                <span><i data-lucide="calendar" style="width: 16px; height: 16px; vertical-align: middle; color: var(--primary);"></i> Ngày đá: <b><?php echo date('d/m/Y', strtotime($b['booking_date'])); ?></b></span>
                                <span><i data-lucide="clock" style="width: 16px; height: 16px; vertical-align: middle; color: var(--primary);"></i> Khung giờ: <b><?php echo date('H:i', strtotime($b['start_time'])); ?> - <?php echo date('H:i', strtotime($b['end_time'])); ?></b> (<?php echo $b['duration']; ?> giờ)</span>
                                <span><i data-lucide="banknote" style="width: 16px; height: 16px; vertical-align: middle; color: var(--primary);"></i> Tổng tiền: <b style="color: var(--primary);"><?php echo number_format($b['total_price'], 0, ',', '.'); ?> đ</b></span>
                                <span><i data-lucide="credit-card" style="width: 16px; height: 16px; vertical-align: middle; color: var(--primary);"></i> Thanh toán: <b><?php echo $b['payment_status'] === 'paid' ? '<span style="color: var(--primary);">Đã thanh toán</span>' : '<span style="color: var(--rating-color);">Chưa thanh toán</span>'; ?></b></span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                            <?php if ($b['status'] === 'pending'): ?>
                                <span class="status-badge status-pending"><i data-lucide="clock"></i> Chờ duyệt</span>
                            <?php elseif ($b['status'] === 'confirmed'): ?>
                                <span class="status-badge status-confirmed"><i data-lucide="check-circle2"></i> Đã xác nhận</span>
                            <?php elseif ($b['status'] === 'completed'): ?>
                                <span class="status-badge status-completed"><i data-lucide="smile"></i> Đã hoàn thành</span>
                            <?php elseif ($b['status'] === 'cancelled'): ?>
                                <span class="status-badge status-cancelled"><i data-lucide="x-circle"></i> Đã hủy</span>
                            <?php elseif ($b['status'] === 'no_show'): ?>
                                <span class="status-badge status-noshow"><i data-lucide="alert-triangle"></i> Vắng mặt</span>
                            <?php endif; ?>

                            <?php 
                            $today_str = date('Y-m-d');
                            if (in_array($b['status'], ['pending', 'confirmed']) && $b['booking_date'] >= $today_str):
                                $datetime_str = $b['booking_date'] . 'T' . $b['start_time'];
                            ?>
                                <form action="my_bookings.php" method="POST" onsubmit="event.preventDefault(); openCancelModal(this, '<?php echo $datetime_str; ?>');">
                                    <input type="hidden" name="cancel_booking_id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="btn btn-danger-ghost">
                                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Hủy đặt sân
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($b['status'] === 'confirmed'): ?>
                                <button class="btn btn-ghost btn-sm" onclick="showQRCode('<?php echo $b['booking_code']; ?>')">
                                    <i data-lucide="qr-code" style="width: 14px; height: 14px;"></i> Mã QR Check-in
                                </button>
                            <?php endif; ?>

                            <?php if (in_array($b['status'], ['confirmed', 'completed'])): ?>
                                <a href="invoice.php?booking_id=<?php echo $b['id']; ?>" target="_blank" class="btn btn-secondary btn-sm">
                                    <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Hóa đơn
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state fade-in-up" style="background-color: var(--bg-card); border: 1px solid var(--border); border-radius: 16px;">
                <i data-lucide="calendar-x" style="width: 48px; height: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                <p>Bạn chưa đặt sân bóng nào.</p>
                <a href="../index.php" class="btn btn-primary">Xem các sân bóng ngay</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal hiển thị QR Check-in -->
    <div id="qrModal" class="modal-overlay" style="display: none;">
        <div class="auth-card" style="max-width: 380px; text-align: center; position: relative;">
            <button onclick="closeModal()" style="position: absolute; top: 16px; right: 16px; background: transparent; border: none; color: var(--text-muted); cursor: pointer;"><i data-lucide="x"></i></button>
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px; color: var(--primary);">Mã QR Check-in</h3>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">Đưa mã này cho nhân viên lễ tân của sân để check-in nhận sân nhanh chóng.</p>
            <div style="padding: 16px; background: white; border-radius: 8px; display: inline-block; margin-bottom: 16px;">
                <img id="modalQrImg" src="" style="width: 200px; height: 200px;" alt="Check-in QR Code">
            </div>
            <p id="modalQrCodeText" style="font-weight: 700; color: var(--text-main); font-size: 15px;"></p>
        </div>
    </div>

    <!-- Modal Hủy Sân -->
    <div id="cancelModal" class="modal-overlay" style="display: none;">
        <div class="auth-card" style="max-width: 420px; text-align: left; position: relative;">
            <button onclick="closeCancelModal()" style="position: absolute; top: 16px; right: 16px; background: transparent; border: none; color: var(--text-muted); cursor: pointer;"><i data-lucide="x"></i></button>
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px; color: #ef4444;">Xác Nhận Hủy Đặt Sân</h3>
            
            <div id="cancelWarningBox" style="display: none; padding: 12px; background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 6px; margin-bottom: 16px;">
                <p style="font-size: 13px; color: #b91c1c; font-weight: 600; margin-bottom: 4px;"><i data-lucide="alert-triangle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i> CẢNH BÁO MẤT CỌC</p>
                <p style="font-size: 13px; color: #991b1b; line-height: 1.4;">Bạn đang hủy sân quá sát giờ (dưới 30 phút). Theo quy định, bạn sẽ <b>MẤT TOÀN BỘ TIỀN CỌC</b>.</p>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-main);">Lý do hủy đơn này <span style="color: red;">*</span></label>
                <textarea id="cancelReasonInput" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 14px; resize: none;" placeholder="Vui lòng cho chúng tôi biết lý do..."></textarea>
                <p id="cancelErrorText" style="color: #ef4444; font-size: 12px; margin-top: 6px; display: none;">Vui lòng nhập lý do hủy.</p>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeCancelModal()">Quay lại</button>
                <button type="button" class="btn btn-danger" onclick="submitCancelForm()">Chấp nhận Hủy</button>
            </div>
        </div>
    </div>

    <script>
        let currentCancelForm = null;

        function showQRCode(code) {
            document.getElementById('modalQrImg').src = `https://quickchart.io/qr?text=${code}&size=200`;
            document.getElementById('modalQrCodeText').innerText = code;
            document.getElementById('qrModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('qrModal').style.display = 'none';
        }

        function openCancelModal(formElement, datetimeStr) {
            currentCancelForm = formElement;
            document.getElementById('cancelReasonInput').value = '';
            document.getElementById('cancelErrorText').style.display = 'none';

            let startDatetime = new Date(datetimeStr);
            let now = new Date();
            let diffMs = startDatetime - now;
            let diffMins = Math.floor(diffMs / 60000);
            
            // Show warning if < 30 mins
            if (diffMins < 30 && diffMins > 0) {
                document.getElementById('cancelWarningBox').style.display = 'block';
            } else {
                document.getElementById('cancelWarningBox').style.display = 'none';
            }

            document.getElementById('cancelModal').style.display = 'flex';
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
            currentCancelForm = null;
        }

        function submitCancelForm() {
            let reason = document.getElementById('cancelReasonInput').value.trim();
            if (reason === '') {
                document.getElementById('cancelErrorText').style.display = 'block';
                return;
            }
            
            if (currentCancelForm) {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'cancel_reason';
                input.value = reason;
                currentCancelForm.appendChild(input);
                currentCancelForm.submit();
            }
        }

        document.getElementById('qrModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>

<?php include '../includes/footer.php'; ?>
