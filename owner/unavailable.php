<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('owner');
$owner_id = $_SESSION['user_id'];

$msg = '';
$error = '';

// Lấy danh sách sân của owner
$fields_stmt = $pdo->prepare("SELECT id, name FROM fields WHERE owner_id = ?");
$fields_stmt->execute([$owner_id]);
$owner_fields = $fields_stmt->fetchAll();

// Xử lý thêm mới hoặc xóa khung giờ bảo trì
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $field_id     = intval($_POST['field_id']);
        $unavail_date = trim($_POST['unavail_date']);
        $start_time   = trim($_POST['start_time']);
        $end_time     = trim($_POST['end_time']);
        $reason       = trim($_POST['reason'] ?? '');

        if ($field_id <= 0 || empty($unavail_date) || empty($start_time) || empty($end_time)) {
            $error = 'Vui lòng điền đầy đủ các thông tin bắt buộc.';
        } elseif ($end_time <= $start_time) {
            $error = 'Giờ kết thúc phải lớn hơn giờ bắt đầu.';
        } else {
            try {
                // Kiểm tra sân thuộc sở hữu owner
                $check = $pdo->prepare("SELECT id FROM fields WHERE id = ? AND owner_id = ?");
                $check->execute([$field_id, $owner_id]);
                if ($check->fetch()) {
                    $ins = $pdo->prepare("
                        INSERT INTO field_unavailable (field_id, unavail_date, start_time, end_time, reason)
                        VALUES (:field_id, :unavail_date, :start_time, :end_time, :reason)
                    ");
                    $ins->execute([
                        'field_id'     => $field_id,
                        'unavail_date' => $unavail_date,
                        'start_time'   => $start_time,
                        'end_time'     => $end_time,
                        'reason'       => !empty($reason) ? $reason : 'Bảo trì sân bóng'
                    ]);
                    $msg = 'Đã chốt khung giờ không khả dụng thành công!';
                } else {
                    $error = 'Bạn không có quyền quản lý sân bóng này.';
                }
            } catch (PDOException $e) {
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['unavail_id']);
        try {
            $del = $pdo->prepare("
                DELETE fu FROM field_unavailable fu
                JOIN fields f ON fu.field_id = f.id
                WHERE fu.id = ? AND f.owner_id = ?
            ");
            $del->execute([$id, $owner_id]);
            $msg = 'Đã mở lại khung giờ hoạt động bình thường!';
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách khung giờ đang bị khóa
$list_stmt = $pdo->prepare("
    SELECT fu.*, f.name as field_name 
    FROM field_unavailable fu
    JOIN fields f ON fu.field_id = f.id
    WHERE f.owner_id = ?
    ORDER BY fu.unavail_date DESC, fu.start_time ASC
");
$list_stmt->execute([$owner_id]);
$unavail_list = $list_stmt->fetchAll();

$page_title = 'Quản Lý Khung Giờ Bảo Trì';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <?php if (!empty($msg)): ?>
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
            ✓ <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
            ⚠ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
        <!-- Form thêm khung giờ khóa -->
        <div class="card" style="padding: 24px; height: fit-content;">
            <h3 class="card-title" style="margin-bottom: 20px;">Khóa Khung Giờ Sân</h3>
            
            <form action="unavailable.php" method="POST">
                <input type="hidden" name="action" value="add">

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Chọn sân bóng *</label>
                    <select name="field_id" class="form-control" required>
                        <?php foreach ($owner_fields as $of): ?>
                            <option value="<?php echo $of['id']; ?>"><?php echo htmlspecialchars($of['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Ngày áp dụng *</label>
                    <input type="date" name="unavail_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Từ giờ *</label>
                        <input type="time" name="start_time" class="form-control" value="08:00" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Đến giờ *</label>
                        <input type="time" name="end_time" class="form-control" value="10:00" required>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Lý do (Tùy chọn)</label>
                    <input type="text" name="reason" class="form-control" placeholder="Ví dụ: Bảo trì cỏ nhân tạo, Sự kiện riêng">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i data-lucide="lock" style="width: 16px; height: 16px;"></i> Khóa khung giờ này
                </button>
            </form>
        </div>

        <!-- Danh sách khung giờ đã khóa -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Danh Sách Khung Giờ Đang Khóa / Bảo Trì</h3>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                            <th style="padding: 14px 20px;">Sân Bóng</th>
                            <th style="padding: 14px 20px;">Ngày</th>
                            <th style="padding: 14px 20px;">Thời Gian</th>
                            <th style="padding: 14px 20px;">Lý Do</th>
                            <th style="padding: 14px 20px; text-align: center;">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($unavail_list)): ?>
                            <tr>
                                <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">Chưa có khung giờ bảo trì nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($unavail_list as $ul): ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 14px 20px; font-weight: 600;"><?php echo htmlspecialchars($ul['field_name']); ?></td>
                                    <td style="padding: 14px 20px; font-weight: 600; color: var(--dark);"><?php echo date('d/m/Y', strtotime($ul['unavail_date'])); ?></td>
                                    <td style="padding: 14px 20px; color: #ef4444; font-weight: 700;">
                                        <?php echo date('H:i', strtotime($ul['start_time'])); ?> - <?php echo date('H:i', strtotime($ul['end_time'])); ?>
                                    </td>
                                    <td style="padding: 14px 20px; color: var(--text-muted);"><?php echo htmlspecialchars($ul['reason']); ?></td>
                                    <td style="padding: 14px 20px; text-align: center;">
                                        <form action="unavailable.php" method="POST" onsubmit="return confirm('Bạn có chắc muốn mở lại khung giờ này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="unavail_id" value="<?php echo $ul['id']; ?>">
                                            <button type="submit" class="btn btn-ghost btn-sm" style="color: #ef4444;" title="Mở lại giờ">
                                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i> Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
