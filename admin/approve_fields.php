<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('admin');
$msg = '';

// Xử lý duyệt hoặc từ chối
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $field_id = intval($_POST['field_id']);
    
    // Lấy thông tin sân bóng để gửi thông báo cho chủ sân
    $stmt_get = $pdo->prepare("SELECT name, owner_id FROM fields WHERE id = ?");
    $stmt_get->execute([$field_id]);
    $field = $stmt_get->fetch();
    
    if ($field) {
        if ($_POST['action'] === 'approve') {
            $stmt = $pdo->prepare("UPDATE fields SET status = 'active' WHERE id = ?");
            $stmt->execute([$field_id]);
            
            // Thêm thông báo
            $stmt_notif = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon) 
                VALUES (?, 'field_approved', '🎉 Sân bóng đã được duyệt!', ?, 'field', ?, 'check-circle')
            ");
            $stmt_notif->execute([
                $field['owner_id'],
                "Sân bóng \"" . $field['name'] . "\" của bạn đã được duyệt thành công và đã hiển thị công khai trên hệ thống.",
                $field_id
            ]);
            
            $msg = "Đã duyệt thành công sân bóng và gửi thông báo cho chủ sân!";
        } elseif ($_POST['action'] === 'reject') {
            $stmt = $pdo->prepare("UPDATE fields SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$field_id]);
            
            // Thêm thông báo
            $stmt_notif = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, body, ref_type, ref_id, icon) 
                VALUES (?, 'field_rejected', '⚠️ Sân bóng bị từ chối duyệt', ?, 'field', ?, 'x-circle')
            ");
            $stmt_notif->execute([
                $field['owner_id'],
                "Yêu cầu duyệt sân bóng \"" . $field['name'] . "\" của bạn đã bị từ chối hoặc bị vô hiệu hóa.",
                $field_id
            ]);
            
            $msg = "Đã từ chối (vô hiệu hóa) sân bóng và gửi thông báo cho chủ sân!";
        }
    } else {
        $msg = "<span style='color:#dc2626;'>Lỗi: Không tìm thấy sân bóng tương ứng.</span>";
    }
}

// Lấy danh sách sân chờ duyệt
$stmt = $pdo->prepare("
    SELECT f.*, u.full_name as owner_name, u.phone as owner_phone, u.email as owner_email 
    FROM fields f 
    JOIN users u ON f.owner_id = u.id 
    WHERE f.status = 'pending' 
    ORDER BY f.created_at ASC
");
$stmt->execute();
$pending_fields = $stmt->fetchAll();

$page_title = 'Duyệt Sân Bóng';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Kiểm duyệt Sân bóng</h2>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: #dcfce3; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
            <i data-lucide="check-circle" style="width: 18px; height: 18px; vertical-align: middle;"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Tên sân</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Chủ sân</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Địa chỉ</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted);">Loại & Giá</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pending_fields) > 0): ?>
                        <?php foreach ($pending_fields as $f): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo htmlspecialchars($f['name']); ?></strong><br>
                                    <small style="color: var(--text-muted);">Gửi lúc: <?php echo date('d/m/Y H:i', strtotime($f['created_at'])); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php echo htmlspecialchars($f['owner_name']); ?><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($f['owner_phone']); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php echo htmlspecialchars($f['address']); ?><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($f['district']); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    Sân <?php echo $f['type']; ?><br>
                                    <strong style="color: #ef4444;"><?php echo number_format($f['price_per_hour'], 0, ',', '.'); ?>đ/h</strong>
                                </td>
                                <td style="padding: 15px 20px; text-align: right;">
                                    <form method="POST" style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <input type="hidden" name="field_id" value="<?php echo $f['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-primary" style="padding: 6px 12px; font-size: 13px; border-radius: 4px;">
                                            <i data-lucide="check" style="width: 14px; height: 14px;"></i> Duyệt
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn-logout" style="padding: 6px 12px; font-size: 13px; border-radius: 4px;" onclick="return confirm('Từ chối sân này?');">
                                            Từ chối
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Không có sân bóng nào đang chờ duyệt.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
