<?php
// ═══════════════════════════════════════════════════════
// TRANG SÂN YÊU THÍCH
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Xử lý bỏ yêu thích (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite'])) {
    $field_id = intval($_POST['remove_favorite']);
    try {
        $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND field_id = :fid")
            ->execute(['uid' => $user_id, 'fid' => $field_id]);
        $success = 'Đã bỏ sân khỏi danh sách yêu thích.';
    } catch (PDOException $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Lấy danh sách sân yêu thích
try {
    $stmt = $pdo->prepare("
        SELECT f.*, fav.created_at as fav_date
        FROM favorites fav
        JOIN fields f ON fav.field_id = f.id
        WHERE fav.user_id = :uid
        ORDER BY fav.created_at DESC
    ");
    $stmt->execute(['uid' => $user_id]);
    $favorites = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

$type_labels = ['5v5' => 'Sân 5 người', '7v7' => 'Sân 7 người', '11v11' => 'Sân 11 người'];
$field_images = [
    'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1540747913346-19212a4b423e?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1518063319789-7217e6706b04?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1574629810360-7efbbe195018?q=80&w=700&auto=format&fit=crop',
];

$base_url = '../';
$current_page = 'favorites';
$page_title = 'Sân Yêu Thích';
include '../includes/header.php';
?>

<div class="container" style="margin-top:40px;margin-bottom:80px;">
    <div class="section-header">
        <div>
            <h1 class="section-title" style="font-size:28px;font-weight:800;">
                <i data-lucide="heart" style="width:28px;height:28px;color:#ef4444;vertical-align:middle;"></i>
                Sân Yêu Thích
            </h1>
            <p style="color:var(--text-muted);margin-top:8px;">Danh sách các sân bóng bạn đã lưu yêu thích.</p>
        </div>
        <span class="section-count">(<?php echo count($favorites); ?> sân)</span>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><i data-lucide="check-circle"></i><span><?php echo htmlspecialchars($success); ?></span></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><i data-lucide="alert-circle"></i><span><?php echo htmlspecialchars($error); ?></span></div>
    <?php endif; ?>

    <?php if (count($favorites) > 0): ?>
        <div class="fields-grid fade-in-up">
            <?php foreach ($favorites as $field): ?>
                <?php 
                $img = !empty($field['cover_image']) 
                    ? ( (strpos($field['cover_image'], 'http://') === 0 || strpos($field['cover_image'], 'https://') === 0) 
                        ? $field['cover_image'] 
                        : $base_url . $field['cover_image'] ) 
                    : $field_images[$field['id'] % count($field_images)]; 
                ?>
                <article class="field-card">
                    <div class="field-image">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($field['name']); ?>" loading="lazy">
                        <div class="field-image-overlay"></div>
                        <span class="field-badge"><?php echo htmlspecialchars($field['type']); ?></span>
                        <span class="field-rating-badge">
                            <i data-lucide="star" style="fill:var(--rating-color);width:12px;height:12px;"></i>
                            <?php echo number_format($field['rating'], 1); ?>
                        </span>
                    </div>
                    <div class="field-info">
                        <h3 class="field-name"><?php echo htmlspecialchars($field['name']); ?></h3>
                        <p class="field-address">
                            <i data-lucide="map-pin"></i>
                            <?php echo htmlspecialchars($field['address']); ?>
                        </p>
                        <div class="field-footer">
                            <div class="field-price">
                                <span class="field-price-amount"><?php echo number_format($field['price_per_hour'], 0, ',', '.'); ?>đ</span>
                                <span class="field-price-unit">/ giờ thuê</span>
                            </div>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <form action="favorites.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="remove_favorite" value="<?php echo $field['id']; ?>">
                                    <button type="submit" class="btn btn-danger-ghost" title="Bỏ yêu thích" onclick="return confirm('Bỏ yêu thích sân này?');">
                                        <i data-lucide="heart-off" style="width:14px;height:14px;"></i>
                                    </button>
                                </form>
                                <a href="detail.php?id=<?php echo $field['id']; ?>" class="btn btn-primary btn-sm">
                                    Xem chi tiết <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state fade-in-up">
            <i data-lucide="heart"></i>
            <p>Bạn chưa có sân yêu thích nào.</p>
            <a href="../index.php" class="btn btn-primary">Khám phá các sân bóng</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
