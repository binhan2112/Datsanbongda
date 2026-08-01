<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('owner');
$owner_id = $_SESSION['user_id'];
$msg = '';

// Xử lý gửi/sửa phản hồi của chủ sân bóng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $review_id = intval($_POST['review_id']);
    
    // Kiểm tra review này có thuộc sân của owner này không
    $stmt_check = $pdo->prepare("
        SELECT r.id FROM reviews r 
        JOIN fields f ON r.field_id = f.id 
        WHERE r.id = ? AND f.owner_id = ?
    ");
    $stmt_check->execute([$review_id, $owner_id]);
    
    if ($stmt_check->fetch()) {
        if ($_POST['action'] === 'reply') {
            $reply = trim($_POST['owner_reply']);
            if (empty($reply)) {
                $msg = "<span style='color:#dc2626;'>Lỗi: Nội dung phản hồi không được để trống.</span>";
            } else {
                $stmt_reply = $pdo->prepare("UPDATE reviews SET owner_reply = ? WHERE id = ?");
                $stmt_reply->execute([$reply, $review_id]);
                $msg = "Đã gửi phản hồi đánh giá thành công!";
            }
        } elseif ($_POST['action'] === 'delete_reply') {
            $stmt_del = $pdo->prepare("UPDATE reviews SET owner_reply = NULL WHERE id = ?");
            $stmt_del->execute([$review_id]);
            $msg = "Đã xóa phản hồi thành công!";
        }
    } else {
        $msg = "<span style='color:#dc2626;'>Lỗi: Đánh giá không thuộc phạm vi quản lý của bạn.</span>";
    }
}

// Lấy danh sách đánh giá của các sân thuộc chủ sân này
$sql = "
    SELECT r.*, f.name as field_name, u.full_name as reviewer_name, u.email as reviewer_email
    FROM reviews r
    JOIN fields f ON r.field_id = f.id
    JOIN users u ON r.user_id = u.id
    WHERE f.owner_id = ?
    ORDER BY r.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$owner_id]);
$reviews = $stmt->fetchAll();

$page_title = 'Quản lý Đánh giá sân bóng';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Đánh giá & Phản hồi khách hàng</h2>
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
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); width: 20%;">Khách hàng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); width: 20%;">Sân bóng</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); width: 25%;">Đánh giá</th>
                        <th style="padding: 15px 20px; font-weight: 600; color: var(--text-muted); width: 35%;">Phản hồi của bạn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $r): ?>
                            <tr style="border-bottom: 1px solid var(--border); vertical-align: top;">
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo htmlspecialchars($r['reviewer_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($r['reviewer_email']); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <strong><?php echo htmlspecialchars($r['field_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></small>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <div style="display: flex; align-items: center; gap: 2px; color: #fbbf24; margin-bottom: 5px;">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i data-lucide="star" style="width: 14px; height: 14px; <?php echo ($i <= $r['rating']) ? 'fill: #fbbf24;' : 'color: #cbd5e1;'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if (!empty($r['title'])): ?>
                                        <strong style="display:block; margin-bottom:2px; font-size:13px; color:var(--dark);"><?php echo htmlspecialchars($r['title']); ?></strong>
                                    <?php endif; ?>
                                    <p style="color: var(--text-muted); margin: 0; margin-bottom: 8px; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($r['comment'] ?? '')); ?></p>
                                    <?php 
                                    if (!empty($r['images'])) {
                                        $images = json_decode($r['images'], true);
                                        if (is_array($images) && count($images) > 0) {
                                            echo '<div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px;">';
                                            foreach ($images as $img) {
                                                echo '<img src="../assets/uploads/reviews/' . htmlspecialchars($img) . '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer;" onclick="window.open(this.src, \'_blank\')">';
                                            }
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <?php if (!empty($r['owner_reply'])): ?>
                                        <div style="background: rgba(0, 191, 166, 0.05); padding: 12px; border-radius: 6px; border-left: 3px solid var(--primary); margin-bottom: 8px;">
                                            <p style="margin: 0 0 5px 0; color: var(--text); font-size: 13px;"><?php echo nl2br(htmlspecialchars($r['owner_reply'])); ?></p>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Bạn có chắc muốn xóa phản hồi này?');">
                                                <input type="hidden" name="action" value="delete_reply">
                                                <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" style="background:none; border:none; color:#ef4444; font-size:11px; cursor:pointer; font-weight:600; padding:0;">
                                                    <i data-lucide="trash-2" style="width:11px; height:11px; vertical-align:text-bottom;"></i> Xóa phản hồi
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <!-- Form phản hồi -->
                                        <form method="POST" style="display: flex; gap: 8px; flex-direction: column;">
                                            <input type="hidden" name="action" value="reply">
                                            <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                                            <textarea name="owner_reply" placeholder="Nhập lời cảm ơn hoặc phản hồi của bạn..." rows="2" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; font-family:inherit; font-size:13px; resize:vertical;"></textarea>
                                            <button type="submit" class="btn btn-primary" style="align-self: flex-end; padding: 6px 12px; font-size: 12px; border-radius: 4px;">Phản hồi</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Chưa có khách hàng nào đánh giá sân của bạn.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
