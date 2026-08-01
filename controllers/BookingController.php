<?php
$field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;
if ($field_id <= 0) {
    header("Location: ../index.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM fields WHERE id = :id AND status = 'active'");
    $stmt->execute(['id' => $field_id]);
    $field = $stmt->fetch();
    if (!$field) { die("Không tìm thấy thông tin sân bóng hoạt động."); }
} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['start_time'])) {
    $booking_date = trim($_POST['booking_date'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $duration = floatval($_POST['duration'] ?? 1);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $note = trim($_POST['note'] ?? '');

    $start_time_str = $booking_date . ' ' . $start_time;
    $start_timestamp = strtotime($start_time_str);
    
    $duration_minutes = $duration * 60;
    $end_timestamp = strtotime("+$duration_minutes minutes", $start_timestamp);
    $end_time = date('H:i:s', $end_timestamp);
    $start_time_db = date('H:i:s', $start_timestamp);
    $today = date('Y-m-d');

    if (empty($booking_date) || empty($start_time)) {
        $error = 'Vui lòng chọn ngày đặt và giờ bắt đầu.';
    } elseif ($booking_date < $today) {
        $error = 'Ngày đặt sân không thể nằm ở quá khứ.';
    } elseif ($start_timestamp <= time()) {
        $error = 'Giờ đặt sân đã qua, vui lòng chọn khung giờ khác.';
    } elseif (date('Y-m-d', $start_timestamp) !== date('Y-m-d', $end_timestamp) && $end_time !== '00:00:00') {
        $error = 'Hệ thống không hỗ trợ đặt sân vắt ngang qua ngày mới.';
    } elseif ($_SESSION['user_id'] == $field['owner_id'] && empty($note)) {
        $error = 'Vì bạn là Chủ sân, vui lòng điền Ô Ghi chú (Ví dụ: Đặt sân giùm bạn A, khóa sân nội bộ...)';
    } else {
        $day_of_week = date('w', strtotime($booking_date));
        $field_open_time = $field['open_time'];
        $field_close_time = $field['close_time'];
        $is_closed_today = false;

        if (!empty($field['working_hours'])) {
            $wh = json_decode($field['working_hours'], true);
            if (isset($wh[$day_of_week])) {
                if (!empty($wh[$day_of_week]['closed'])) {
                    $is_closed_today = true;
                } else {
                    if (!empty($wh[$day_of_week]['open'])) $field_open_time = $wh[$day_of_week]['open'] . ':00';
                    if (!empty($wh[$day_of_week]['close'])) $field_close_time = $wh[$day_of_week]['close'] . ':00';
                }
            }
        }

        if ($is_closed_today) {
            $error = 'Sân đóng cửa vào ngày này, vui lòng chọn ngày khác.';
        } else {
            $end_time_to_check = ($end_time === '00:00:00') ? '24:00:00' : $end_time;
            $open_sec = strtotime($field_open_time);
            $close_sec = strtotime($field_close_time);
            $start_sec = strtotime($start_time_db);
            $is_overnight = ($close_sec <= $open_sec);
            
            if (!$is_overnight) {
                $end_sec = strtotime($end_time_to_check);
                if ($start_sec < $open_sec || $end_sec > $close_sec || $end_sec <= $start_sec) {
                    $error = 'Thời gian thuê sân phải nằm trong khung giờ hoạt động (' . date('H:i', $open_sec) . ' - ' . date('H:i', $close_sec) . ').';
                }
            }
        }
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            $recurring_weeks = isset($_POST['recurring_weeks']) ? intval($_POST['recurring_weeks']) : 1;
            if ($recurring_weeks < 1) $recurring_weeks = 1;
            if ($recurring_weeks > 4) $recurring_weeks = 4;

            $dates_to_book = [];
            
            for ($w = 0; $w < $recurring_weeks; $w++) {
                $target_date = date('Y-m-d', strtotime("$booking_date +$w weeks"));
                
                $lock_stmt = $pdo->prepare("SELECT id FROM fields WHERE id = :field_id FOR UPDATE");
                $lock_stmt->execute(['field_id' => $field_id]);

                $overlap_stmt = $pdo->prepare("
                    SELECT id FROM bookings 
                    WHERE field_id = :field_id AND booking_date = :booking_date 
                      AND status NOT IN ('cancelled', 'no_show') 
                      AND (start_time < :end_time AND end_time > :start_time)
                ");
                $overlap_stmt->execute(['field_id' => $field_id, 'booking_date' => $target_date, 'start_time' => $start_time_db, 'end_time' => $end_time]);

                if ($overlap_stmt->fetch()) {
                    $error = "Khung giờ này vào ngày " . date('d/m/Y', strtotime($target_date)) . " đã có người đặt.";
                    break;
                }
                
                $unavail_stmt = $pdo->prepare("
                    SELECT id FROM field_unavailable 
                    WHERE field_id = :field_id AND unavail_date = :unavail_date 
                      AND (start_time < :end_time AND end_time > :start_time)
                ");
                $unavail_stmt->execute(['field_id' => $field_id, 'unavail_date' => $target_date, 'start_time' => $start_time_db, 'end_time' => $end_time]);
                if ($unavail_stmt->fetch()) {
                    $error = "Sân đang bảo trì vào ngày " . date('d/m/Y', strtotime($target_date)) . ".";
                    break;
                }
                
                $total_price = 0;
                $current_time = strtotime("$target_date $start_time_db");
                $half_hours = $duration * 2;
                for ($i = 0; $i < $half_hours; $i++) {
                    $hour = intval(date('H', $current_time));
                    if ($hour >= 17 && $hour < 21 && !empty($field['price_peak'])) {
                        $total_price += $field['price_peak'] / 2;
                    } else {
                        $total_price += $field['price_per_hour'] / 2;
                    }
                    $current_time = strtotime('+30 minutes', $current_time);
                }
                if ($field['discount_percent'] > 0) {
                    $total_price = $total_price * (1 - $field['discount_percent'] / 100);
                }
                
                $dates_to_book[] = ['date' => $target_date, 'price' => $total_price];
            }

            if (!empty($error)) {
                $pdo->rollBack();
            } else {
                $first_booking_id = null;
                foreach ($dates_to_book as $idx => $b_data) {
                    $booking_code = 'CT' . date('YmdHis') . rand(10, 99) . $idx;
                    $deposit_amount = 0;
                    if (!empty($field['deposit_percent']) && $field['deposit_percent'] > 0) {
                        $deposit_amount = ($b_data['price'] * $field['deposit_percent']) / 100;
                    }
                    
                    // Nếu trả bằng tiền mặt (chỉ được khi cọc = 0) thì duyệt luôn.
                    // Ngược lại, nếu thanh toán online (có cọc) thì chờ IPN xác nhận.
                    $initial_status = ($payment_method === 'cash') ? 'confirmed' : 'pending';

                    $insert_stmt = $pdo->prepare("
                        INSERT INTO bookings (booking_code, field_id, user_id, booking_date, start_time, end_time, duration, total_price, deposit_amount, status, payment_method, payment_status, note) 
                        VALUES (:booking_code, :field_id, :user_id, :booking_date, :start_time, :end_time, :duration, :total_price, :deposit_amount, :status, :payment_method, 'unpaid', :note)
                    ");
                    $insert_stmt->execute([
                        'booking_code' => $booking_code, 'field_id' => $field_id, 'user_id' => $_SESSION['user_id'],
                        'booking_date' => $b_data['date'], 'start_time' => $start_time_db, 'end_time' => $end_time,
                        'duration' => $duration, 'total_price' => $b_data['price'], 'deposit_amount' => $deposit_amount,
                        'status' => $initial_status,
                        'payment_method' => $payment_method, 'note' => !empty($note) ? $note : null
                    ]);
                    if ($idx === 0) {
                        $first_booking_id = $pdo->lastInsertId();
                    }
                }

                $pdo->commit();
                
                if ($recurring_weeks > 1) {
                    header("Location: ../pages/my_bookings.php?msg=recurring_success");
                } else {
                    header("Location: checkout.php?booking_id=" . $first_booking_id);
                }
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Đã xảy ra lỗi khi lưu thông tin: ' . $e->getMessage();
        }
    }
}

// ═══════════════════════════════════════════════════════
// QUERY SLOT STATUS FOR CINEMA-STYLE UI
// ═══════════════════════════════════════════════════════
$selected_date = $_POST['booking_date'] ?? date('Y-m-d');

$booked_slots = [];
try {
    $bs_stmt = $pdo->prepare("
        SELECT start_time, end_time, status 
        FROM bookings 
        WHERE field_id = :fid AND booking_date = :bdate 
          AND status NOT IN ('cancelled','no_show')
    ");
    $bs_stmt->execute(['fid' => $field_id, 'bdate' => $selected_date]);
    $booked_rows = $bs_stmt->fetchAll();
    
    foreach ($booked_rows as $br) {
        $start_ts = strtotime($br['start_time']);
        $end_ts = strtotime($br['end_time']);
        
        $start_h = intval(date('H', $start_ts));
        $start_m = intval(date('i', $start_ts));
        $s = $start_h + ($start_m >= 30 ? 0.5 : 0);
        
        $end_h = intval(date('H', $end_ts));
        $end_m = intval(date('i', $end_ts));
        $e = $end_h + ($end_m > 0 && $end_m <= 30 ? 0.5 : ($end_m > 30 ? 1.0 : 0));
        
        if ($e == 0) $e = 24;
        for ($h = $s; $h < $e; $h += 0.5) {
            $booked_slots[strval($h)] = $br['status'];
        }
    }
} catch (PDOException $e) { /* ignore */ }

$unavail_slots = [];
try {
    $ua_stmt = $pdo->prepare("
        SELECT start_time, end_time, reason 
        FROM field_unavailable 
        WHERE field_id = :fid AND unavail_date = :bdate
    ");
    $ua_stmt->execute(['fid' => $field_id, 'bdate' => $selected_date]);
    $ua_rows = $ua_stmt->fetchAll();
    
    foreach ($ua_rows as $ur) {
        $start_ts = strtotime($ur['start_time']);
        $end_ts = strtotime($ur['end_time']);
        
        $start_h = intval(date('H', $start_ts));
        $start_m = intval(date('i', $start_ts));
        $s = $start_h + ($start_m >= 30 ? 0.5 : 0);
        
        $end_h = intval(date('H', $end_ts));
        $end_m = intval(date('i', $end_ts));
        $e = $end_h + ($end_m > 0 && $end_m <= 30 ? 0.5 : ($end_m > 30 ? 1.0 : 0));
        
        if ($e == 0) $e = 24;
        for ($h = $s; $h < $e; $h += 0.5) {
            $unavail_slots[strval($h)] = $ur['reason'] ?: 'Bảo trì';
        }
    }
} catch (PDOException $e) { /* ignore */ }

$ui_day_of_week = date('w', strtotime($selected_date));
$ui_open_time = $field['open_time'];
$ui_close_time = $field['close_time'];
$ui_is_closed = false;

if (!empty($field['working_hours'])) {
    $wh = json_decode($field['working_hours'], true);
    if (isset($wh[$ui_day_of_week])) {
        if (!empty($wh[$ui_day_of_week]['closed'])) {
            $ui_is_closed = true;
        } else {
            if (!empty($wh[$ui_day_of_week]['open'])) $ui_open_time = $wh[$ui_day_of_week]['open'];
            if (!empty($wh[$ui_day_of_week]['close'])) $ui_close_time = $wh[$ui_day_of_week]['close'];
        }
    }
}

$open_hour = intval(date('H', strtotime($ui_open_time)));
$close_hour = intval(date('H', strtotime($ui_close_time)));
if ($close_hour == 0) $close_hour = 24;

