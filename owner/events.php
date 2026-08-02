<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('owner');
$msg = '';

// Hàm tạo slug đơn giản
function createSlug($str) {
    $str = mb_strtolower($str);
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9\-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

// Xử lý khi gửi form thêm giải đấu mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_event') {
        $field_id = intval($_POST['field_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $event_type = $_POST['event_type'];
        $start_datetime = $_POST['start_datetime'];
        $end_datetime = $_POST['end_datetime'];
        $max_teams = empty($_POST['max_teams']) ? null : intval($_POST['max_teams']);
        $entry_fee = floatval($_POST['entry_fee'] ?? 0);
        $prize_pool = empty($_POST['prize_pool']) ? null : floatval($_POST['prize_pool']);
        
        if (empty($title) || empty($start_datetime) || empty($end_datetime) || $field_id <= 0) {
            $msg = "<span style='color:#dc2626;'>Lỗi: Vui lòng nhập đầy đủ các trường bắt buộc (Tên giải, sân đấu, thời gian).</span>";
        } else {
            $slug = createSlug($title) . '-' . time();
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO events 
                    (field_id, organizer_id, title, slug, description, event_type, start_datetime, end_datetime, max_teams, entry_fee, prize_pool, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming')
                ");
                $stmt->execute([
                    $field_id,
                    $_SESSION['user_id'], // Người tổ chức là admin
                    $title,
                    $slug,
                    $description,
                    $event_type,
                    $start_datetime,
                    $end_datetime,
                    $max_teams,
                    $entry_fee,
                    $prize_pool
                ]);
                $msg = "Thêm giải đấu / sự kiện thành công!";
            } catch (PDOException $e) {
                $msg = "<span style='color:#dc2626;'>Lỗi cơ sở dữ liệu: " . $e->getMessage() . "</span>";
            }
        }
    } elseif ($_POST['action'] === 'update_status') {
        $event_id = intval($_POST['event_id']);
        $status = $_POST['status'];
        if (in_array($status, ['upcoming', 'ongoing', 'completed', 'cancelled'])) {
            $stmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
            $stmt->execute([$status, $event_id]);
            $msg = "Đã cập nhật trạng thái sự kiện thành công!";
        }
    } elseif ($_POST['action'] === 'delete') {
        $event_id = intval($_POST['event_id']);
        try {
            // Xóa sự kiện (xóa liên đới đăng ký tham gia do ON DELETE CASCADE)
            // Chủ sân chỉ được xóa khi pending hoặc rejected
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ? AND approval_status != 'approved'");
            $stmt->execute([$event_id, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $msg = "Đã xóa sự kiện thành công!";
            } else {
                $msg = "<span style='color:#dc2626;'>Lỗi: Không thể xóa sự kiện này (có thể đã được duyệt).</span>";
            }
        } catch (PDOException $e) {
            $msg = "<span style='color:#dc2626;'>Lỗi: Không thể xóa sự kiện này.</span>";
        }
    }
}

// Lấy danh sách sân hoạt động của owner để làm dropdown
$fields_stmt = $pdo->prepare("SELECT id, name FROM fields WHERE status = 'active' AND owner_id = ? ORDER BY name");
$fields_stmt->execute([$_SESSION['user_id']]);
$active_fields = $fields_stmt->fetchAll();

// Lấy danh sách các giải đấu của owner
$sql = "
    SELECT e.*, f.name as field_name, u.full_name as organizer_name 
    FROM events e
    JOIN fields f ON e.field_id = f.id
    JOIN users u ON e.organizer_id = u.id
    WHERE e.organizer_id = ? OR f.owner_id = ?
    ORDER BY e.start_datetime DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$events = $stmt->fetchAll();

$page_title = 'Quản lý Giải đấu & Sự kiện';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Quản lý Giải đấu & Sự kiện</h2>
        <button onclick="document.getElementById('add-event-modal').style.display='flex';" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
            <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Tạo giải đấu mới
        </button>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--border); font-weight: 500;">
            <i data-lucide="info" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- Modal Tạo Giải Đấu -->
    <div id="add-event-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="card" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; overflow-y: auto;">
            <div class="card-header">
                <h3 class="card-title">Tạo Giải đấu / Sự kiện mới</h3>
                <button onclick="document.getElementById('add-event-modal').style.display='none';" style="background: none; border: none; cursor: pointer;"><i data-lucide="x"></i></button>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_event">
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Tên giải đấu / Sự kiện <span style="color:red;">*</span></label>
                        <input type="text" name="title" required placeholder="VD: Giải Bóng Đá Cúp Tứ Hùng Cần Thơ" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Sân bóng diễn ra <span style="color:red;">*</span></label>
                        <select name="field_id" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                            <option value="">-- Chọn sân bóng --</option>
                            <?php foreach ($active_fields as $f): ?>
                                <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Loại sự kiện <span style="color:red;">*</span></label>
                            <select name="event_type" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                                <option value="giai_dau">Giải đấu (Tournament)</option>
                                <option value="friendly">Giao hữu (Friendly)</option>
                                <option value="training">Tập huấn (Training)</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Số đội tối đa</label>
                            <input type="number" name="max_teams" placeholder="VD: 8, 16..." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Thời gian bắt đầu <span style="color:red;">*</span></label>
                            <input type="datetime-local" name="start_datetime" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Thời gian kết thúc <span style="color:red;">*</span></label>
                            <input type="datetime-local" name="end_datetime" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Lệ phí tham gia (VNĐ)</label>
                            <input type="number" name="entry_fee" value="0" placeholder="Lệ phí / đội..." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Tổng giải thưởng (VNĐ)</label>
                            <input type="number" name="prize_pool" placeholder="Giá trị giải thưởng..." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Mô tả chi tiết giải đấu</label>
                        <textarea name="description" rows="4" placeholder="Mô tả thể thức thi đấu, cơ cấu giải thưởng, liên hệ đăng ký..." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; resize: vertical;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Tạo sự kiện & công bố</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách sự kiện -->
    <div class="card">
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Sự kiện & Giải đấu</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Địa điểm</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Thời gian</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Đội đăng ký</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Lệ phí & Giải thưởng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Trạng thái (Tiến độ)</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Phê duyệt</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($events) > 0): ?>
                        <?php foreach ($events as $e): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo htmlspecialchars($e['title']); ?></strong><br>
                                    <span style="font-size: 11px; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 600; text-transform: uppercase;">
                                        <?php echo ($e['event_type'] === 'giai_dau') ? 'Giải đấu' : (($e['event_type'] === 'friendly') ? 'Giao hữu' : 'Khác'); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php echo htmlspecialchars($e['field_name']); ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <small style="color: var(--text-muted);">Bắt đầu:</small> <?php echo date('d/m/Y H:i', strtotime($e['start_datetime'])); ?><br>
                                    <small style="color: var(--text-muted);">Kết thúc:</small> <?php echo date('d/m/Y H:i', strtotime($e['end_datetime'])); ?>
                                </td>
                                <td style="padding: 15px 20px; font-weight: 600;">
                                    <?php echo $e['current_teams']; ?> / <?php echo $e['max_teams'] ?? '∞'; ?> đội
                                </td>
                                <td style="padding: 15px 20px;">
                                    Lệ phí: <strong style="color: #ef4444;"><?php echo number_format($e['entry_fee'], 0, ',', '.'); ?>đ</strong><br>
                                    Giải: <strong style="color: #10b981;"><?php echo $e['prize_pool'] ? number_format($e['prize_pool'], 0, ',', '.') . 'đ' : 'Không có'; ?></strong>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($e['status'] === 'upcoming'): ?>
                                        <span style="background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Sắp diễn ra</span>
                                    <?php elseif ($e['status'] === 'ongoing'): ?>
                                        <span style="background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đang diễn ra</span>
                                    <?php elseif ($e['status'] === 'completed'): ?>
                                        <span style="background: #dcfce3; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đã kết thúc</span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đã hủy</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($e['approval_status'] === 'approved'): ?>
                                        <span style="background: #dcfce3; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Đã duyệt</span>
                                    <?php elseif ($e['approval_status'] === 'rejected'): ?>
                                        <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Bị từ chối</span>
                                    <?php else: ?>
                                        <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Chờ duyệt</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: right;">
                                    <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                        <!-- Thay đổi trạng thái -->
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="event_id" value="<?php echo $e['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px; border: 1px solid var(--border); font-size: 12px;">
                                                <option value="upcoming" <?php if($e['status'] === 'upcoming') echo 'selected'; ?>>Sắp diễn ra</option>
                                                <option value="ongoing" <?php if($e['status'] === 'ongoing') echo 'selected'; ?>>Đang diễn ra</option>
                                                <option value="completed" <?php if($e['status'] === 'completed') echo 'selected'; ?>>Kết thúc</option>
                                                <option value="cancelled" <?php if($e['status'] === 'cancelled') echo 'selected'; ?>>Hủy bỏ</option>
                                            </select>
                                        </form>

                                        <!-- Xóa -->
                                        <?php if ($e['approval_status'] !== 'approved'): ?>
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA sự kiện này không?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="event_id" value="<?php echo $e['id']; ?>">
                                            <button type="submit" class="btn-logout" style="padding: 6px 10px; font-size: 12px; border-radius: 4px;">
                                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Xóa
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Không tìm thấy giải đấu/sự kiện nào.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
