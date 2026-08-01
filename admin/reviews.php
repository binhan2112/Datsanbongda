<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('admin');
$msg = '';

// Xử lý Xóa đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $review_id = intval($_POST['review_id']);
    
    try {
        // Lấy field_id của review trước khi xóa để cập nhật lại thông số rating sân
        $stmt_get = $pdo->prepare("SELECT field_id FROM reviews WHERE id = ?");
        $stmt_get->execute([$review_id]);
        $review = $stmt_get->fetch();
        
        if ($review) {
            $field_id = $review['field_id'];
            
            // Xóa đánh giá
            $stmt_del = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt_del->execute([$review_id]);
            
            // Cập nhật lại trung bình rating và tổng số đánh giá của sân bóng
            $stmt_calc = $pdo->prepare("
                UPDATE fields 
                SET rating = (SELECT COALESCE(AVG(rating), 0.0) FROM reviews WHERE field_id = ?),
                    total_reviews = (SELECT COUNT(*) FROM reviews WHERE field_id = ?)
                WHERE id = ?
            ");
            $stmt_calc->execute([$field_id, $field_id, $field_id]);
            
            $msg = "Đã xóa đánh giá thành công và cập nhật lại điểm số sân bóng!";
        }
    } catch (PDOException $e) {
        $msg = "<span style='color:#dc2626;'>Lỗi hệ thống: " . $e->getMessage() . "</span>";
    }
}

// Lấy danh sách đánh giá
$sql = "
    SELECT r.*, f.name as field_name, u.full_name as reviewer_name, u.email as reviewer_email
    FROM reviews r
    JOIN fields f ON r.field_id = f.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
";
$stmt = $pdo->query($sql);
$reviews = $stmt->fetchAll();

$page_title = 'Quản lý Đánh giá';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Quản lý Đánh giá từ khách hàng</h2>
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
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); width: 20%;">Người đánh giá</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); width: 25%;">Sân bóng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); width: 15%;">Số sao</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); width: 30%;">Nội dung</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); text-align: right; width: 10%;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $r): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo htmlspecialchars($r['reviewer_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($r['reviewer_email']); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo htmlspecialchars($r['field_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);">Ngày đánh giá: <?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <span style="display: flex; align-items: center; gap: 2px; color: #fbbf24; font-weight: 600;">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i data-lucide="star" style="width: 14px; height: 14px; <?php echo ($i <= $r['rating']) ? 'fill: #fbbf24;' : 'color: #cbd5e1;'; ?>"></i>
                                        <?php endfor; ?>
                                        <span style="margin-left: 5px; color: var(--text);"><?php echo $r['rating']; ?> sao</span>
                                    </span>
                                    <?php if ($r['is_verified']): ?>
                                        <span style="font-size: 11px; background: #dcfce3; color: #166534; padding: 2px 6px; border-radius: 4px; font-weight: 600; display: inline-block; margin-top: 5px;">Đã mua/đá</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php if (!empty($r['title'])): ?>
                                        <strong style="display: block; margin-bottom: 5px; color: var(--dark);"><?php echo htmlspecialchars($r['title']); ?></strong>
                                    <?php endif; ?>
                                    <span style="color: var(--text); line-height: 1.4;"><?php echo nl2br(htmlspecialchars($r['comment'] ?? '')); ?></span>
                                </td>
                                <td style="padding: 15px 20px; text-align: right;">
                                    <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đánh giá này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                                        <button type="submit" class="btn-logout" style="padding: 6px 12px; font-size: 12px; border-radius: 4px;">
                                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Chưa có đánh giá nào trên hệ thống.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
