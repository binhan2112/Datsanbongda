<?php
// ═══════════════════════════════════════════════════════
// BOOKING HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════

function awardPoints($booking_id, $pdo) {
    try {
        // Lấy thông tin đơn đặt sân
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        if (!$booking) return 0;
        
        // Chỉ cộng điểm nếu chưa từng cộng cho đơn này
        if (intval($booking['points_earned']) === 0) {
            $points_earned = floor($booking['total_price'] / 10000);
            if ($points_earned > 0) {
                // Cập nhật điểm tích luỹ nhận được trong đơn hàng
                $stmt_up_bk = $pdo->prepare("UPDATE bookings SET points_earned = ? WHERE id = ?");
                $stmt_up_bk->execute([$points_earned, $booking_id]);

                // Cộng vào tài khoản của khách hàng
                $stmt_up_usr = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                $stmt_up_usr->execute([$points_earned, $booking['user_id']]);

                // Gửi thông báo cộng điểm thành công
                $stmt_notif = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon) 
                    VALUES (?, 'points_earned', '🎁 Cộng điểm tích lũy thành công!', ?, 'booking', ?, 'award')
                ");
                $body = "Bạn vừa được cộng " . $points_earned . " điểm tích lũy từ đơn đặt sân " . $booking['booking_code'] . ".";
                $stmt_notif->execute([$booking['user_id'], $body, $booking_id]);

                return $points_earned;
            }
        }
    } catch (Exception $e) {
        // Ghi log hoặc ném ngoại lệ nếu cần
        throw $e;
    }
    return 0;
}

function updateBookingStatus($booking_id, $status, $pdo, $cancel_reason = null) {
    $own_transaction = false;
    try {
        // Lấy thông tin đơn đặt sân để gửi thông báo cho khách hàng
        $stmt_get = $pdo->prepare("
            SELECT b.*, f.name as field_name 
            FROM bookings b 
            JOIN fields f ON b.field_id = f.id 
            WHERE b.id = ?
        ");
        $stmt_get->execute([$booking_id]);
        $booking = $stmt_get->fetch();
        if (!$booking) return false;

        $old_status = $booking['status'];
        if ($old_status === $status) return true;

        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $own_transaction = true;
        }

        // Cập nhật trạng thái
        if ($status === 'cancelled') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = ?, cancelled_at = NOW(), cancel_reason = ? WHERE id = ?");
            $stmt->execute([$status, $cancel_reason, $booking_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$status, $booking_id]);
        }

        // Logic cộng điểm tích lũy khi trận đấu hoàn thành
        $points_earned = 0;
        if ($status === 'completed' && $old_status !== 'completed') {
            $points_earned = awardPoints($booking_id, $pdo);
        }

        // Cấu hình nội dung thông báo dựa trên trạng thái mới
        $title = '';
        $body = '';
        $icon = 'bell';
        
        if ($status === 'confirmed') {
            $title = '🎉 Đơn đặt sân đã được xác nhận!';
            $body = "Đơn đặt sân " . $booking['field_name'] . " (Mã: " . $booking['booking_code'] . ") của bạn đã được xác nhận.";
            $icon = 'check-circle';
        } elseif ($status === 'cancelled') {
            $title = '⚠️ Lịch đặt sân của bạn đã bị hủy';
            $body = "Đơn đặt sân " . $booking['field_name'] . " (Mã: " . $booking['booking_code'] . ") của bạn đã bị hủy.";
            if ($cancel_reason) {
                $body .= " Lý do: " . $cancel_reason;
            }
            $icon = 'x-circle';
        } elseif ($status === 'completed') {
            $title = '⚽ Trận đấu hoàn thành!';
            $body = "Trận đấu tại " . $booking['field_name'] . " (Mã: " . $booking['booking_code'] . ") đã kết thúc. Cảm ơn bạn đã sử dụng dịch vụ!";
            $icon = 'smile';
        }

        if ($title !== '') {
            $stmt_notif = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon) 
                VALUES (?, ?, ?, ?, 'booking', ?, ?)
            ");
            $stmt_notif->execute([
                $booking['user_id'],
                'booking_' . $status,
                $title,
                $body,
                $booking_id,
                $icon
            ]);
        }

        if ($own_transaction) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        if ($own_transaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
?>
