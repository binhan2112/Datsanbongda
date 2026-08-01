<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('owner');
$owner_id = $_SESSION['user_id'];

// Lấy tuần hiện tại hoặc từ tham số GET
$week_offset = isset($_GET['week']) ? intval($_GET['week']) : 0;
$selected_field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;

// Tính ngày bắt đầu (Thứ 2) và ngày kết thúc (Chủ Nhật) của tuần
$monday_timestamp = strtotime("monday this week +" . $week_offset . " weeks");
$sunday_timestamp = strtotime("sunday this week +" . $week_offset . " weeks");

$start_date_str = date('Y-m-d', $monday_timestamp);
$end_date_str   = date('Y-m-d', $sunday_timestamp);

// Tạo danh sách 7 ngày trong tuần
$week_days = [];
for ($i = 0; $i < 7; $i++) {
    $ts = strtotime("+$i days", $monday_timestamp);
    $week_days[] = [
        'date'      => date('Y-m-d', $ts),
        'day_name'  => ($i === 6) ? 'Chủ Nhật' : 'Thứ ' . ($i + 2),
        'formatted' => date('d/m', $ts)
    ];
}

try {
    // Lấy danh sách sân bóng của owner
    $fields_stmt = $pdo->prepare("SELECT id, name FROM fields WHERE owner_id = ?");
    $fields_stmt->execute([$owner_id]);
    $owner_fields = $fields_stmt->fetchAll();

    if ($selected_field_id <= 0 && !empty($owner_fields)) {
        $selected_field_id = $owner_fields[0]['id'];
    }

    // Lấy các booking trong tuần này cho sân chọn
    $bookings = [];
    if ($selected_field_id > 0) {
        $b_stmt = $pdo->prepare("
            SELECT b.*, u.full_name as customer_name, u.phone as customer_phone
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.field_id = :field_id 
              AND b.booking_date BETWEEN :start_date AND :end_date
              AND b.status != 'cancelled'
        ");
        $b_stmt->execute([
            'field_id'   => $selected_field_id,
            'start_date' => $start_date_str,
            'end_date'   => $end_date_str
        ]);
        $raw_bookings = $b_stmt->fetchAll();

        // Đưa bookings vào mảng tra cứu theo [booking_date][hour]
        foreach ($raw_bookings as $b) {
            $date = $b['booking_date'];
            $start_h = intval(substr($b['start_time'], 0, 2));
            $end_h   = intval(substr($b['end_time'], 0, 2));
            if ($end_h === 0) $end_h = 24;

            for ($h = $start_h; $h < $end_h; $h++) {
                $bookings[$date][$h] = $b;
            }
        }
    }

} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}

$page_title = 'Lịch Đặt Sân Trực Quan';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    background: white;
}
.calendar-header-cell {
    background: #f8fafc;
    padding: 12px;
    text-align: center;
    font-weight: 700;
    border-bottom: 2px solid var(--border);
    border-right: 1px solid var(--border);
}
.calendar-time-cell {
    background: #f8fafc;
    padding: 10px;
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border);
    border-right: 1px solid var(--border);
}
.calendar-slot-cell {
    padding: 6px;
    height: 54px;
    border-bottom: 1px solid var(--border);
    border-right: 1px solid var(--border);
    position: relative;
}
.booking-block {
    background: rgba(0, 191, 166, 0.15);
    border-left: 4px solid var(--primary);
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 11px;
    height: 100%;
    overflow: hidden;
}
.booking-block.confirmed {
    background: rgba(59, 130, 246, 0.15);
    border-left-color: #3b82f6;
}
.booking-block.completed {
    background: rgba(16, 185, 129, 0.15);
    border-left-color: #10b981;
}
</style>

<div>
    <!-- Navigation chọn sân & chọn tuần -->
    <div class="card" style="margin-bottom: 24px; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <form method="GET" action="calendar.php" style="display: flex; align-items: center; gap: 12px;">
                <input type="hidden" name="week" value="<?php echo $week_offset; ?>">
                <label style="font-weight: 600; font-size: 14px;">Chọn sân bóng:</label>
                <select name="field_id" class="form-control" style="width: 240px;" onchange="this.form.submit()">
                    <?php foreach ($owner_fields as $of): ?>
                        <option value="<?php echo $of['id']; ?>" <?php echo $of['id'] === $selected_field_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($of['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="calendar.php?field_id=<?php echo $selected_field_id; ?>&week=<?php echo $week_offset - 1; ?>" class="btn btn-secondary btn-sm">
                    <i data-lucide="chevron-left"></i> Tuần trước
                </a>
                <span style="font-weight: 700; font-size: 15px; color: var(--dark);">
                    <?php echo date('d/m/Y', $monday_timestamp); ?> — <?php echo date('d/m/Y', $sunday_timestamp); ?>
                </span>
                <a href="calendar.php?field_id=<?php echo $selected_field_id; ?>&week=<?php echo $week_offset + 1; ?>" class="btn btn-secondary btn-sm">
                    Tuần sau <i data-lucide="chevron-right"></i>
                </a>
                <a href="calendar.php?field_id=<?php echo $selected_field_id; ?>&week=0" class="btn btn-ghost btn-sm">Hôm nay</a>
            </div>
        </div>
    </div>

    <!-- Lịch theo định dạng Lưới Tuần -->
    <div class="calendar-grid">
        <!-- Header hàng trên -->
        <div class="calendar-header-cell">Giờ</div>
        <?php foreach ($week_days as $wd): ?>
            <div class="calendar-header-cell <?php echo $wd['date'] === date('Y-m-d') ? 'style="background: rgba(0, 191, 166, 0.1); color: var(--primary);"' : ''; ?>">
                <div><?php echo $wd['day_name']; ?></div>
                <div style="font-size: 12px; font-weight: 500; color: var(--text-muted);"><?php echo $wd['formatted']; ?></div>
            </div>
        <?php endforeach; ?>

        <!-- Lưới thời gian từ 06:00 đến 22:00 -->
        <?php for ($hour = 6; $hour <= 22; $hour++): ?>
            <?php $time_label = sprintf('%02d:00', $hour); ?>
            <div class="calendar-time-cell"><?php echo $time_label; ?></div>

            <?php foreach ($week_days as $wd): ?>
                <?php 
                    $d = $wd['date'];
                    $b = $bookings[$d][$hour] ?? null;
                ?>
                <div class="calendar-slot-cell">
                    <?php if ($b): ?>
                        <div class="booking-block <?php echo $b['status']; ?>" title="<?php echo htmlspecialchars($b['customer_name']); ?> - <?php echo $b['customer_phone']; ?>">
                            <div style="font-weight: 700; color: var(--dark);"><?php echo htmlspecialchars($b['customer_name']); ?></div>
                            <div style="color: var(--text-muted);"><?php echo date('H:i', strtotime($b['start_time'])); ?> - <?php echo date('H:i', strtotime($b['end_time'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
