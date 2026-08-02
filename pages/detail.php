<?php
// ═══════════════════════════════════════════════════════
// TRANG CHI TIẾT SÂN BÓNG
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

$field_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($field_id <= 0) {
    header("Location: ../index.php");
    exit;
}

// Xử lý toggle yêu thích
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_favorite'])) {
    if (!is_logged_in()) {
        header("Location: ../auth/login.php");
        exit;
    }
    try {
        $check_fav = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :uid AND field_id = :fid");
        $check_fav->execute(['uid' => $_SESSION['user_id'], 'fid' => $field_id]);
        if ($check_fav->fetch()) {
            $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND field_id = :fid")
                ->execute(['uid' => $_SESSION['user_id'], 'fid' => $field_id]);
        } else {
            $pdo->prepare("INSERT INTO favorites (user_id, field_id) VALUES (:uid, :fid)")
                ->execute(['uid' => $_SESSION['user_id'], 'fid' => $field_id]);
        }
        header("Location: detail.php?id=" . $field_id);
        exit;
    } catch (PDOException $e) {
        // Ignore duplicate
    }
}

try {
    // 1. Lấy thông tin chi tiết sân bóng và thông tin chủ sân
    $stmt = $pdo->prepare("
        SELECT f.*, u.full_name as owner_name, u.phone as owner_phone 
        FROM fields f 
        JOIN users u ON f.owner_id = u.id 
        WHERE f.id = :id AND f.status = 'active'
    ");
    $stmt->execute(['id' => $field_id]);
    $field = $stmt->fetch();

    if (!$field) {
        die("Không tìm thấy sân bóng nào hoạt động với ID đã cung cấp.");
    }

    // 2. Lấy danh sách hình ảnh phụ của sân bóng
    $images_stmt = $pdo->prepare("SELECT * FROM field_images WHERE field_id = :field_id ORDER BY sort_order ASC");
    $images_stmt->execute(['field_id' => $field_id]);
    $images = $images_stmt->fetchAll();

    // 3. Lấy danh sách đánh giá của sân bóng kèm tên người đánh giá
    $reviews_stmt = $pdo->prepare("
        SELECT r.*, u.full_name, u.avatar 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.field_id = :field_id 
        ORDER BY r.created_at DESC
    ");
    $reviews_stmt->execute(['field_id' => $field_id]);
    $reviews = $reviews_stmt->fetchAll();

    // 4. Kiểm tra đã yêu thích chưa
    $is_favorited = false;
    if (is_logged_in()) {
        $fav_check = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :uid AND field_id = :fid");
        $fav_check->execute(['uid' => $_SESSION['user_id'], 'fid' => $field_id]);
        $is_favorited = (bool) $fav_check->fetch();
    }

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

$base_url = '../';
$current_page = 'fields';
$page_title = $field['name'];
include '../includes/header.php';
?>
<style>
    .detail-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-top: 40px;
        margin-bottom: 60px;
    }
    @media (max-width: 992px) {
        .detail-layout { grid-template-columns: 1fr; }
    }
    .gallery-container {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        margin-bottom: 24px;
    }
    .main-image {
        width: 100%;
        height: 400px;
        object-fit: cover;
        background-color: #232c3f;
    }
    .thumbnail-list {
        display: flex;
        gap: 12px;
        margin-bottom: 30px;
        overflow-x: auto;
        padding-bottom: 8px;
    }
    .thumbnail-item {
        width: 100px;
        height: 70px;
        border-radius: 8px;
        object-fit: cover;
        cursor: pointer;
        border: 2px solid transparent;
        transition: var(--transition-smooth);
        background-color: #232c3f;
    }
    .thumbnail-item:hover, .thumbnail-item.active {
        border-color: var(--primary);
        transform: scale(1.05);
    }
    .info-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
    }
    .facility-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border-color);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        color: var(--text-main);
    }
    .facility-badge.active {
        border-color: var(--primary);
        color: var(--primary);
        background-color: rgba(16, 185, 129, 0.05);
    }
    .review-item { border-bottom: 1px solid var(--border-color); padding: 20px 0; }
    .review-item:last-child { border-bottom: none; }
    .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .review-user { display: flex; align-items: center; gap: 10px; }
    .review-user img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color); }
    .verified-badge { display: inline-flex; align-items: center; gap: 4px; color: var(--primary); font-size: 12px; font-weight: 500; }
</style>

    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:24px;flex-wrap:wrap;gap:12px;">
            <p style="font-size: 14px; color: var(--text-muted);">
                <a href="../index.php" style="color: var(--text-muted); text-decoration: none;"><?php echo __trans('home'); ?></a> &gt; 
                Quận <?php echo htmlspecialchars($field['district']); ?> &gt; 
                <span style="color: var(--text-main);"><?php echo htmlspecialchars($field['name']); ?></span>
            </p>

            <!-- Nút Yêu thích -->
            <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'customer'): ?>
            <form action="detail.php?id=<?php echo $field_id; ?>" method="POST" style="margin:0;">
                <input type="hidden" name="toggle_favorite" value="1">
                <button type="submit" class="btn-favorite <?php echo $is_favorited ? 'favorited' : ''; ?>">
                    <i data-lucide="heart" style="<?php echo $is_favorited ? 'fill:#ef4444;' : ''; ?>"></i>
                    <?php echo $is_favorited ? __trans('favorited') : __trans('favorite'); ?>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <div class="detail-layout">
            <!-- Cột trái: Ảnh và thông tin -->
            <div>
                <div class="gallery-container">
                    <?php 
                    $main_img = 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?q=80&w=1200&auto=format&fit=crop';
                    if (!empty($field['cover_image'])) {
                        $main_img = (strpos($field['cover_image'], 'http://') === 0 || strpos($field['cover_image'], 'https://') === 0) 
                            ? $field['cover_image'] 
                            : $base_url . $field['cover_image'];
                    }
                    ?>
                    <img id="main-gallery-img" src="<?php echo htmlspecialchars($main_img); ?>" class="main-image" alt="<?php echo htmlspecialchars($field['name']); ?>">
                </div>
 
                <?php if (count($images) > 0 || !empty($field['cover_image'])): ?>
                    <div class="thumbnail-list">
                        <?php if (!empty($field['cover_image'])): ?>
                            <?php 
                            $thumb_cover = (strpos($field['cover_image'], 'http://') === 0 || strpos($field['cover_image'], 'https://') === 0) 
                                ? $field['cover_image'] 
                                : $base_url . $field['cover_image'];
                            ?>
                            <img src="<?php echo htmlspecialchars($thumb_cover); ?>" class="thumbnail-item active" onclick="changeImage(this.src, this)">
                        <?php endif; ?>
                        <?php foreach ($images as $img): ?>
                            <?php 
                            $thumb_sub = (strpos($img['image_path'], 'http://') === 0 || strpos($img['image_path'], 'https://') === 0) 
                                ? $img['image_path'] 
                                : $base_url . $img['image_path'];
                            ?>
                            <img src="<?php echo htmlspecialchars($thumb_sub); ?>" class="thumbnail-item" onclick="changeImage(this.src, this)" title="<?php echo htmlspecialchars($img['caption'] ?? ''); ?>">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="info-card">
                    <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 16px;"><?php echo __trans('field_intro'); ?></h2>
                    <p style="color: var(--text-muted); white-space: pre-line;"><?php echo htmlspecialchars($field['description']); ?></p>
                </div>

                <div class="info-card">
                    <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 20px;"><?php echo __trans('available_amenities'); ?></h2>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <div class="facility-badge <?php echo $field['has_lighting'] ? 'active' : ''; ?>">
                            <i data-lucide="lightbulb"></i> <?php echo __trans('led_light'); ?>
                        </div>
                        <div class="facility-badge <?php echo $field['has_parking'] ? 'active' : ''; ?>">
                            <i data-lucide="shield"></i> <?php echo __trans('parking'); ?>
                        </div>
                        <div class="facility-badge <?php echo $field['has_shower'] ? 'active' : ''; ?>">
                            <i data-lucide="bath"></i> <?php echo __trans('shower'); ?>
                        </div>
                        <div class="facility-badge <?php echo $field['has_canteen'] ? 'active' : ''; ?>">
                            <i data-lucide="coffee"></i> <?php echo __trans('canteen'); ?>
                        </div>
                        <div class="facility-badge <?php echo $field['has_rental'] ? 'active' : ''; ?>">
                            <i data-lucide="shirt"></i> <?php echo __trans('rental'); ?>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="font-size: 24px; font-weight: 700;"><?php echo __trans('reviews_title'); ?> (<?php echo count($reviews); ?>)</h2>
                        <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'customer'): ?>
                        <a href="review.php?field_id=<?php echo $field['id']; ?>" class="btn btn-primary btn-sm" style="border-radius: 40px; padding: 8px 16px;">
                            <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> <?php echo __trans('write_review'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php if (count($reviews) > 0): ?>
                        <div>
                            <?php foreach ($reviews as $rev): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="review-user">
                                            <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=<?php echo urlencode($rev['full_name']); ?>" alt="avatar">
                                            <div>
                                                <h4 style="font-weight: 600; font-size: 15px;"><?php echo htmlspecialchars($rev['full_name']); ?></h4>
                                                <span style="font-size: 12px; color: var(--text-muted);"><?php echo date('d/m/Y H:i', strtotime($rev['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                            <div style="color: var(--rating-color); display: flex; gap: 2px;">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i data-lucide="star" style="width: 14px; height: 14px; fill: <?php echo $i <= $rev['rating'] ? 'var(--rating-color)' : 'none'; ?>; color: var(--rating-color);"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <?php if ($rev['is_verified']): ?>
                                                <span class="verified-badge"><i data-lucide="check-circle" style="width: 12px; height: 12px;"></i> <?php echo __trans('verified_player'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <h5 style="font-size: 15px; font-weight: 600; margin-bottom: 6px;"><?php echo htmlspecialchars($rev['title'] ?? ''); ?></h5>
                                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 12px;"><?php echo htmlspecialchars($rev['comment'] ?? ''); ?></p>
                                    
                                    <?php 
                                    if (!empty($rev['images'])) {
                                        $images = json_decode($rev['images'], true);
                                        if (is_array($images) && count($images) > 0) {
                                            echo '<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">';
                                            foreach ($images as $img) {
                                                echo '<img src="../assets/uploads/reviews/' . htmlspecialchars($img) . '" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; cursor: pointer;" onclick="window.open(this.src, \'_blank\')">';
                                            }
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                    <?php if (!empty($rev['owner_reply'])): ?>
                                        <div style="margin-top: 12px; margin-left: 20px; padding: 10px 15px; background: rgba(0, 191, 166, 0.05); border-left: 3px solid var(--primary); border-radius: 6px;">
                                            <strong style="font-size: 13px; color: var(--primary); display: block; margin-bottom: 4px;"><?php echo __trans('owner_reply'); ?></strong>
                                            <p style="font-size: 13px; color: var(--text-main); margin: 0;"><?php echo nl2br(htmlspecialchars($rev['owner_reply'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 14px;"><?php echo __trans('no_reviews'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cột phải: Thẻ đặt lịch -->
            <div style="position: sticky; top: 100px; align-self: start;">
                <div class="info-card" style="border-color: var(--primary); box-shadow: 0 8px 30px rgba(255, 0, 60, 0.08);">
                    <h1 style="font-size: 26px; font-weight: 800; margin-bottom: 12px;"><?php echo htmlspecialchars($field['name']); ?></h1>
                    <p class="field-address" style="margin-bottom: 8px;">
                        <i data-lucide="map-pin" style="width: 16px; height: 16px;"></i>
                        <?php echo htmlspecialchars($field['address']); ?>
                    </p>
                    <p id="distance-display" style="margin-bottom: 16px; font-size: 14px; color: var(--primary); font-weight: 600; display: none;">
                        <i data-lucide="navigation" style="width: 16px; height: 16px;"></i>
                        <?php echo __trans('distance_lbl'); ?> <span id="distance-km">...</span> km
                    </p>
                    <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 20px; font-size: 14px;">
                        <span style="display: flex; align-items: center; gap: 4px; color: var(--rating-color); font-weight: 600;">
                            <i data-lucide="star" style="fill: var(--rating-color); width: 16px; height: 16px;"></i>
                            <?php echo number_format($field['rating'], 1); ?> (<?php echo $field['total_reviews']; ?> <?php echo __trans('total_reviews'); ?>)
                        </span>
                        <span style="color: var(--text-muted);">
                            <i data-lucide="check" style="width: 16px; height: 16px; vertical-align: middle; color: var(--primary);"></i>
                            <?php echo $field['total_bookings']; ?> <?php echo __trans('total_bookings'); ?>
                        </span>
                    </div>
                    <div style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                        <?php if ($field['discount_percent'] > 0): ?>
                            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 6px; padding: 10px; margin-bottom: 14px; text-align: center; color: var(--error-color); font-weight: 700; font-size: 14px;">
                                <i data-lucide="tag" style="width: 14px; height: 14px; vertical-align: middle;"></i> <?php echo __trans('discount_msg_1'); ?> <?php echo $field['discount_percent']; ?>%!
                            </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px; align-items: center;">
                            <span style="color: var(--text-muted);"><?php echo __trans('normal_hour'); ?></span>
                            <div>
                                <?php if ($field['discount_percent'] > 0): 
                                    $disc_hour = $field['price_per_hour'] * (1 - $field['discount_percent'] / 100);
                                ?>
                                    <span style="font-weight: 700; color: var(--text-main);"><?php echo number_format($disc_hour, 0, ',', '.'); ?> đ/h</span>
                                    <span style="text-decoration: line-through; color: var(--text-muted); font-size: 12px; margin-left: 4px;"><?php echo number_format($field['price_per_hour'], 0, ',', '.'); ?>đ</span>
                                <?php else: ?>
                                    <span style="font-weight: 700; color: var(--text-main);"><?php echo number_format($field['price_per_hour'], 0, ',', '.'); ?> đ/h</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($field['price_peak'])): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 15px; align-items: center;">
                                <span style="color: var(--text-muted);"><?php echo __trans('peak_hour'); ?></span>
                                <div>
                                    <?php if ($field['discount_percent'] > 0): 
                                        $disc_peak = $field['price_peak'] * (1 - $field['discount_percent'] / 100);
                                    ?>
                                        <span style="font-weight: 700; color: var(--primary);"><?php echo number_format($disc_peak, 0, ',', '.'); ?> đ/h</span>
                                        <span style="text-decoration: line-through; color: var(--text-muted); font-size: 12px; margin-left: 4px;"><?php echo number_format($field['price_peak'], 0, ',', '.'); ?>đ</span>
                                    <?php else: ?>
                                        <span style="font-weight: 700; color: var(--primary);"><?php echo number_format($field['price_peak'], 0, ',', '.'); ?> đ/h</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 12px; border-top: 1px solid var(--border-color); padding-top: 10px; display: flex; align-items: center; gap: 4px;">
                            <i data-lucide="clock" style="width: 12px; height: 12px;"></i>
                            <?php echo __trans('operating_hours'); ?> <?php echo date('H:i', strtotime($field['open_time'])); ?> - <?php echo date('H:i', strtotime($field['close_time'])); ?>
                        </div>
                    </div>
                    <!-- Nút đặt sân -->
                    <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'customer'): ?>
                    <a href="booking.php?field_id=<?php echo $field['id']; ?>" class="btn btn-primary" style="width: 100%; height: 50px; font-size: 16px;">
                        <i data-lucide="calendar-plus"></i>&nbsp;&nbsp;<?php echo __trans('book_now'); ?>
                    </a>
                    <?php endif; ?>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); font-size: 14px; color: var(--text-muted);">
                        <p style="margin-bottom: 6px;"><i data-lucide="user" style="width: 14px; height: 14px; vertical-align: middle;"></i> <?php echo __trans('owner'); ?> <b><?php echo htmlspecialchars($field['owner_name']); ?></b></p>
                        <p style="margin-bottom: 12px;"><i data-lucide="phone" style="width: 14px; height: 14px; vertical-align: middle;"></i> <?php echo __trans('phone'); ?> <?php echo htmlspecialchars($field['owner_phone']); ?></p>
                        
                        <!-- Nút Chat -->
                        <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'customer'): ?>
                            <a href="chat.php?field_id=<?php echo $field['id']; ?>" class="btn btn-ghost" style="width: 100%; border-color: var(--primary); color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                                <i data-lucide="message-square"></i> <?php echo __trans('chat_owner'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Google Maps Embed -->
                <div class="info-card" style="margin-top: 24px; padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid var(--border-color);">
                    <iframe 
                        width="100%" 
                        height="250" 
                        frameborder="0" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade"
                        src="https://maps.google.com/maps?q=<?php echo $field['lat']; ?>,<?php echo $field['lng']; ?>&hl=vi&z=15&output=embed">
                    </iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        function changeImage(src, element) {
            document.getElementById('main-gallery-img').src = src;
            document.querySelectorAll('.thumbnail-item').forEach(t => t.classList.remove('active'));
            element.classList.add('active');
        }

        // Tính khoảng cách
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of the earth in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c; // Distance in km
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    const fieldLat = <?php echo $field['lat']; ?>;
                    const fieldLng = <?php echo $field['lng']; ?>;
                    
                    const dist = calculateDistance(userLat, userLng, fieldLat, fieldLng);
                    
                    document.getElementById('distance-km').innerText = dist.toFixed(1);
                    document.getElementById('distance-display').style.display = 'block';
                    lucide.createIcons();
                }, function(error) {
                    console.log("Không thể lấy định vị: ", error);
                });
            }
        });
    </script>

<?php include '../includes/footer.php'; ?>
