<?php
// ═══════════════════════════════════════════════════════
// TRANG XÁC NHẬN THANH TOÁN
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../config/payment.php';
require_once '../includes/auth_helper.php';
require_once '../includes/booking_helper.php';

require_login();

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
if ($booking_id <= 0) {
    header("Location: ../index.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, f.name as field_name, f.address as field_address 
        FROM bookings b
        JOIN fields f ON b.field_id = f.id
        WHERE b.id = :id AND b.user_id = :user_id
    ");
    $stmt->execute(['id' => $booking_id, 'user_id' => $_SESSION['user_id']]);
    $booking = $stmt->fetch();
    if (!$booking) { die("Không tìm thấy đơn đặt sân phù hợp hoặc bạn không có quyền xem đơn này."); }
    $user_stmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user_points = intval($user_stmt->fetchColumn());
} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $payment_method = $booking['payment_method'];
        $use_points = isset($_POST['use_points']) ? true : false;
        $points_to_use = 0;
        $discount_amount = 0;
        
        $base_payment = ($booking['deposit_amount'] > 0) ? $booking['deposit_amount'] : $booking['total_price'];
        
        if ($use_points) {
            $max_points_for_booking = floor($base_payment / 100);
            $points_to_use = min($user_points, $max_points_for_booking);
            $discount_amount = $points_to_use * 100;
        }
        $final_price = $base_payment - $discount_amount;
        
        $pdo->beginTransaction();
        
        if ($points_to_use > 0) {
            // Trừ điểm của user
            $pdo->prepare("UPDATE users SET points = points - :points WHERE id = :user_id")
                ->execute(['points' => $points_to_use, 'user_id' => $_SESSION['user_id']]);
            
            // Cập nhật lại bookings với số điểm dùng và tiền giảm giá
            $pdo->prepare("UPDATE bookings SET points_used = :points_used, discount_amount = :discount_amount, total_price = :total_price WHERE id = :id")
                ->execute([
                    'points_used' => $points_to_use,
                    'discount_amount' => $discount_amount,
                    'total_price' => $final_price,
                    'id' => $booking_id
                ]);
        }
        
        if ($payment_method === 'cash' || $payment_method === 'bank') {
            $pdo->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'unpaid' WHERE id = :id")->execute(['id' => $booking_id]);
            
            // Lịch sử thanh toán cho bank
            if ($payment_method === 'bank') {
                $pay_stmt = $pdo->prepare("INSERT INTO payments (booking_id, user_id, amount, method, status) VALUES (?, ?, ?, ?, 'pending')");
                $pay_stmt->execute([$booking_id, $_SESSION['user_id'], $final_price, 'bank']);
            }
            
            $pdo->commit();
            // Tạo thông báo
            $notif_body = 'Bạn đã đặt ' . $booking['field_name'] . ' vào ' . date('H:i', strtotime($booking['start_time'])) . ' ngày ' . date('d/m/Y', strtotime($booking['booking_date'])) . '. Mã đặt: ' . $booking['booking_code'];
            $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon, is_read) VALUES (:user_id, 'booking_confirmed', '✅ Đặt sân thành công!', :body, 'booking', :ref_id, 'check-circle', 0)")
                ->execute(['user_id' => $_SESSION['user_id'], 'body' => $notif_body, 'ref_id' => $booking_id]);

            header("Location: my_bookings.php?success=1");
            exit;

        } elseif ($payment_method === 'vnpay') {
            $pdo->commit();
            
            $vnp_TxnRef = $booking_id . '_' . time(); // Mã đơn hàng 
            $vnp_OrderInfo = "Thanh toan don dat san " . $booking['booking_code'];
            $vnp_OrderType = 'billpayment';
            $vnp_Amount = $final_price * 100;
            $vnp_Locale = 'vn';
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => VNPAY_TMN_CODE,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => VNPAY_RETURN_URL,
                "vnp_TxnRef" => $vnp_TxnRef
            );

            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = VNPAY_URL . "?" . $query;
            if (defined('VNPAY_HASH_SECRET')) {
                $vnpSecureHash =   hash_hmac('sha512', $hashdata, VNPAY_HASH_SECRET);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            
            // Insert pending payment record
            $pay_stmt = $pdo->prepare("INSERT INTO payments (booking_id, user_id, amount, method, vnpay_txn_ref, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $pay_stmt->execute([$booking_id, $_SESSION['user_id'], $final_price, 'vnpay', $vnp_TxnRef]);

            header('Location: ' . $vnp_Url);
            die();
            
        } elseif ($payment_method === 'momo') {
            $pdo->commit();
            // Đơn giản hóa quá trình chuyển hướng MoMo (do MoMo phức tạp hơn với signature)
            // Trong thực tế, bạn sẽ gửi request tới MOMO_ENDPOINT để nhận payUrl
            // Ở đây, chuyển trực tiếp sang mock hoặc trang xử lý momo
            // Tạo record pending cho momo
            $momo_order_id = 'MOMO_' . $booking_id . '_' . time();
            $pay_stmt = $pdo->prepare("INSERT INTO payments (booking_id, user_id, amount, method, momo_order_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $pay_stmt->execute([$booking_id, $_SESSION['user_id'], $final_price, 'momo', $momo_order_id]);

            // For sandbox mock, we will redirect directly to momo return with success signature or simulated error
            // As implementing MoMo API request requires cURL and valid keys.
            // But we will prepare the generic redirect
            // Simulate the payUrl redirect
            $payUrl = MOMO_RETURN_URL . "?orderId=" . $momo_order_id . "&resultCode=0&message=Simulated+Success&amount=" . $final_price;
            header('Location: ' . $payUrl);
            die();
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $error = 'Lỗi xử lý giao dịch: ' . $e->getMessage();
    }
}

$base_url = '../';
$current_page = 'bookings';
$page_title = 'Xác Nhận Đơn Đặt Sân';
include '../includes/header.php';
?>

    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 650px;">
            <h1 class="auth-title">Hóa Đơn Đặt Lịch</h1>
            <p class="auth-subtitle" style="margin-bottom: 24px;">Vui lòng kiểm tra lại thông tin đặt sân và hoàn tất thanh toán.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i data-lucide="alert-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <div style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; margin-bottom: 24px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px; color: var(--primary); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="receipt"></i> Chi Tiết Đơn Đặt Lịch
                </h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; font-size: 15px;">
                    <div><span style="color: var(--text-muted);">Mã đơn đặt:</span><p style="font-weight: 700; color: var(--text-main); margin-top: 4px;"><?php echo htmlspecialchars($booking['booking_code']); ?></p></div>
                    <div><span style="color: var(--text-muted);">Sân bóng:</span><p style="font-weight: 700; color: var(--text-main); margin-top: 4px;"><?php echo htmlspecialchars($booking['field_name']); ?></p></div>
                    <div><span style="color: var(--text-muted);">Ngày đá:</span><p style="font-weight: 600; color: var(--text-main); margin-top: 4px;"><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></p></div>
                    <div><span style="color: var(--text-muted);">Khung giờ:</span><p style="font-weight: 600; color: var(--text-main); margin-top: 4px;"><?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?> (<?php echo $booking['duration']; ?> tiếng)</p></div>
                    <div><span style="color: var(--text-muted);">Phương thức:</span><p style="font-weight: 600; color: var(--text-main); margin-top: 4px;"><?php if ($booking['payment_method'] === 'cash') echo 'Tiền mặt tại sân'; elseif ($booking['payment_method'] === 'momo') echo 'Ví điện tử MoMo'; elseif ($booking['payment_method'] === 'vnpay') echo 'Cổng VNPAY'; elseif ($booking['payment_method'] === 'bank') echo 'Chuyển khoản Ngân hàng'; ?></p></div>
                    <?php if ($booking['deposit_amount'] > 0): ?>
                    <div><span style="color: var(--text-muted);">Tổng tiền:</span><p style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-top: 4px;"><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> đ</p></div>
                    <div><span style="color: var(--text-muted);">Tiền cọc cần thanh toán:</span><p style="font-size: 18px; font-weight: 800; color: var(--primary); margin-top: 4px;"><span id="final-price"><?php echo number_format($booking['deposit_amount'], 0, ',', '.'); ?></span> đ</p></div>
                    <?php else: ?>
                    <div><span style="color: var(--text-muted);">Tổng thanh toán:</span><p style="font-size: 18px; font-weight: 800; color: var(--primary); margin-top: 4px;"><span id="final-price"><?php echo number_format($booking['total_price'], 0, ',', '.'); ?></span> đ</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="background-color: rgba(255, 255, 255, 0.01); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 30px;">
                <?php if ($booking['payment_method'] === 'cash'): ?>
                    <div style="display: flex; gap: 12px;">
                        <i data-lucide="hand-metal" style="color: var(--primary); width: 24px; height: 24px; flex-shrink: 0;"></i>
                        <div>
                            <h4 style="font-weight: 600; margin-bottom: 6px;">Thanh toán bằng Tiền mặt</h4>
                            <p style="font-size: 14px; color: var(--text-muted); line-height: 1.5;">Bạn sẽ thanh toán trực tiếp <b><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> đ</b> tại quầy lễ tân của sân bóng khi đến nhận sân.</p>
                        </div>
                    </div>
                <?php elseif ($booking['payment_method'] === 'momo'): ?>
                    <div style="display: flex; gap: 16px; flex-direction: column; align-items: center; text-align: center;">
                        <div style="width: 50px; height: 50px; background-color: #ae2070; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; font-size: 18px;">MoMo</div>
                        <div>
                            <h4 style="font-weight: 600; margin-bottom: 6px;">Thanh Toán Qua Ví MoMo</h4>
                            <p style="font-size: 14px; color: var(--text-muted); max-width: 500px; margin-bottom: 16px;">Nhấn xác nhận để được chuyển hướng đến cổng thanh toán MoMo an toàn.</p>
                        </div>
                    </div>
                <?php elseif ($booking['payment_method'] === 'vnpay'): ?>
                    <div style="display: flex; gap: 16px; flex-direction: column; align-items: center; text-align: center;">
                        <div style="width: 80px; height: 50px; background-color: #005A9C; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; font-size: 18px;">VNPAY</div>
                        <div>
                            <h4 style="font-weight: 600; margin-bottom: 6px;">Thanh Toán Qua Cổng VNPAY</h4>
                            <p style="font-size: 14px; color: var(--text-muted); max-width: 500px; margin-bottom: 16px;">Hỗ trợ thẻ ATM nội địa, Visa/Mastercard và quét mã QR VNPay. Bạn sẽ được chuyển hướng an toàn.</p>
                        </div>
                    </div>
                <?php elseif ($booking['payment_method'] === 'bank'): ?>
                    <div style="display: flex; gap: 16px; flex-direction: column; align-items: center;">
                        <i data-lucide="landmark" style="color: var(--primary); width: 36px; height: 36px;"></i>
                        <div style="text-align: center;">
                            <h4 style="font-weight: 600; margin-bottom: 6px;">Chuyển khoản Ngân hàng (Giả lập)</h4>
                        </div>
                        <div style="width: 100%; font-size: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px;">
                            <p style="margin-bottom: 8px; display: flex; justify-content: space-between;"><span>Ngân hàng:</span> <b>MB Bank</b></p>
                            <p style="margin-bottom: 8px; display: flex; justify-content: space-between;"><span>Số tài khoản:</span> <b>0907123456</b></p>
                            <p style="margin-bottom: 8px; display: flex; justify-content: space-between;"><span>Chủ tài khoản:</span> <b>CAN THO SPORT CENTER</b></p>
                            <p style="display: flex; justify-content: space-between;"><span>Nội dung:</span> <b style="color: var(--primary);"><?php echo htmlspecialchars($booking['booking_code']); ?></b></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <form action="checkout.php?booking_id=<?php echo $booking['id']; ?>" method="POST">
                <?php if ($user_points > 0): ?>
                    <div style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i data-lucide="award" style="color: var(--primary); width: 22px; height: 22px;"></i>
                            <div>
                                <h4 style="font-size: 14px; font-weight: 600; margin: 0; color: var(--text-main);">Sử dụng điểm tích lũy</h4>
                                <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0 0;">Bạn đang có <b><?php echo $user_points; ?></b> điểm (quy đổi tối đa <b><?php echo number_format(min($user_points * 100, $booking['total_price']), 0, ',', '.'); ?>đ</b>)</p>
                            </div>
                        </div>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; font-size: 14px; color: var(--text-main);">
                            <input type="checkbox" name="use_points" id="use_points" value="1" onchange="togglePointsUsage(this)"> Dùng điểm
                        </label>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 50px;">
                    <i data-lucide="check-circle2"></i>&nbsp;&nbsp;Xác nhận hoàn tất
                </button>
            </form>
        </div>
    </div>

    <script>
        const basePrice = <?php echo $booking['total_price']; ?>;
        const userPoints = <?php echo $user_points; ?>;
        const pointValue = 100; // 1 điểm = 100đ

        function togglePointsUsage(checkbox) {
            let finalPrice = basePrice;
            if (checkbox.checked) {
                const maxDiscount = Math.min(userPoints * pointValue, basePrice);
                finalPrice = basePrice - maxDiscount;
            }
            document.getElementById('final-price').innerText = new Intl.NumberFormat('vi-VN').format(finalPrice).replace(/\s/g, '');
        }
    </script>

<?php include '../includes/footer.php'; ?>
