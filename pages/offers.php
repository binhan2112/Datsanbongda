<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

$rewards = [];
try {
    $stmt = $pdo->query("SELECT * FROM rewards WHERE status = 'active' ORDER BY points_required ASC");
    $db_rewards = $stmt->fetchAll();
    foreach ($db_rewards as $r) {
        $rewards[$r['id']] = [
            'name' => $r['name'],
            'points' => $r['points_required'],
            'icon' => 'gift',
            'desc' => $r['description'],
            'image_url' => $r['image_url'],
            'quantity' => $r['quantity']
        ];
    }
} catch (PDOException $e) {
    // Fallback if needed, but array remains empty
}

// Fetch current user points
$user_points = 0;
if (is_logged_in()) {
    try {
        $pts_stmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
        $pts_stmt->execute([$_SESSION['user_id']]);
        $user_points = intval($pts_stmt->fetchColumn());
    } catch (PDOException $e) {
        $user_points = 0;
    }
}

$success_msg = '';
$error_msg = '';

// Handle points redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'redeem') {
    if (!is_logged_in()) {
        $error_msg = 'Vui lòng đăng nhập để đổi quà!';
    } else {
        $reward_id = intval($_POST['reward_id'] ?? 0);
        if (isset($rewards[$reward_id])) {
            $reward = $rewards[$reward_id];
            if ($user_points < $reward['points']) {
                $error_msg = 'Số điểm tích lũy của bạn không đủ để đổi phần quà này!';
            } elseif ($reward['quantity'] <= 0) {
                $error_msg = 'Phần quà này đã hết hàng!';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Deduct user points
                    $up_stmt = $pdo->prepare("UPDATE users SET points = points - ? WHERE id = ?");
                    $up_stmt->execute([$reward['points'], $_SESSION['user_id']]);
                    
                    // Insert into reward_exchanges
                    $exchange_stmt = $pdo->prepare("INSERT INTO reward_exchanges (user_id, reward_id, points_used, status) VALUES (?, ?, ?, 'pending')");
                    $exchange_stmt->execute([$_SESSION['user_id'], $reward_id, $reward['points']]);

                    // Update quantity
                    $up_qty_stmt = $pdo->prepare("UPDATE rewards SET quantity = quantity - 1 WHERE id = ?");
                    $up_qty_stmt->execute([$reward_id]);
                    
                    // Generate random voucher code (optional for DB, but keeping for notification)
                    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $voucher_code = 'QD-' . substr(str_shuffle($chars), 0, 6);
                    
                    // Create notification
                    $notif_title = '🎁 Yêu cầu đổi quà thành công: ' . $reward['name'];
                    $notif_body = 'Bạn đã dùng ' . $reward['points'] . ' điểm yêu cầu đổi "' . $reward['name'] . '". Trạng thái: Đang chờ duyệt. (Mã tham chiếu: ' . $voucher_code . ')';
                    $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, ref_type, icon) VALUES (?, 'reward_redeem', ?, ?, 'reward', 'gift')");
                    $notif_stmt->execute([$_SESSION['user_id'], $notif_title, $notif_body]);
                    
                    $pdo->commit();
                    
                    // Update points in current memory
                    $user_points -= $reward['points'];
                    $rewards[$reward_id]['quantity'] -= 1; // update locally
                    
                    $success_msg = 'Yêu cầu đổi quà thành công! Đang chờ admin duyệt. Mã tham chiếu: <b style="font-size:18px; color:var(--primary);">' . $voucher_code . '</b>.';
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error_msg = 'Lỗi hệ thống khi đổi quà: ' . $e->getMessage();
                }
            }
        } else {
            $error_msg = 'Phần quà quy đổi không hợp lệ!';
        }
    }
}

try {
    // Query active fields with discounts
    $stmt = $pdo->query("SELECT * FROM fields WHERE status = 'active' AND discount_percent > 0 ORDER BY discount_percent DESC, rating DESC");
    $fields = $stmt->fetchAll();
    $db_connected = true;
} catch (PDOException $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
    $fields = [];
}

$field_images = [
    'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1540747913346-19212a4b423e?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1518063319789-7217e6706b04?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?q=80&w=700&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1574629810360-7efbbe195018?q=80&w=700&auto=format&fit=crop',
];

$amenity_map = [
    'has_lighting' => ['lightbulb', 'Đèn LED'],
    'has_shower'   => ['bath',      'Phòng tắm'],
    'has_canteen'  => ['coffee',    'Căn tin'],
    'has_parking'  => ['shield',    'Bãi xe'],
    'has_rental'   => ['shirt',     'Cho thuê'],
];

$base_url = '../';
$current_page = 'offers';
$page_title = 'Ưu Đãi & Đổi Thưởng';
include '../includes/header.php';
?>

<!-- ══ PROMO HERO ═══════════════════════════════════════════════ -->
<section style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); padding: 50px 0; text-align: center; color: white; border-radius: 0 0 24px 24px; box-shadow: var(--shadow-md); margin-bottom: 40px;">
    <div class="container">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 12px; display: inline-flex; align-items: center; gap: 8px;">
            <i data-lucide="percent" style="stroke-width: 2.5;"></i> Chương Trình Ưu Đãi Giảm Giá Sân & Đổi Thưởng
        </h1>
        <p style="font-size: 16px; opacity: 0.9; max-width: 600px; margin: 0 auto; line-height: 1.6;">
            Đặt sân bóng nhanh chóng với giá ưu đãi cực sốc. Tích lũy điểm thưởng và quy đổi thành các phần quà cực hấp dẫn trực tiếp tại đây!
        </p>
    </div>
</section>

<main class="container" style="min-height: 500px; margin-bottom: 80px;">
    
    <!-- Alerts -->
    <?php if (!empty($success_msg)): ?>
        <div style="background: #dcfce3; color: #166534; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 30px; font-weight: 500; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
            <span><?php echo $success_msg; ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; border: 1px solid #fecaca; margin-bottom: 30px; font-weight: 500; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
            <span><?php echo $error_msg; ?></span>
        </div>
    <?php endif; ?>

    <!-- ══ SECTION 1: DISCOUNTED FIELDS ══════════════════════════════ -->
    <div class="section-header" style="margin-bottom: 30px;">
        <h2 class="section-title">
            Danh sách sân đang giảm giá
            <span class="section-count" style="margin-left:10px;">(<?php echo count($fields); ?> sân)</span>
        </h2>
    </div>

    <?php if (isset($db_error)): ?>
        <div class="alert alert-danger">
            <i data-lucide="alert-circle"></i>
            <span>Không thể kết nối cơ sở dữ liệu: <?php echo htmlspecialchars($db_error); ?></span>
        </div>
    <?php endif; ?>

    <?php if (count($fields) > 0): ?>
        <div class="fields-grid fade-in-up" style="margin-bottom: 60px;">
            <?php foreach ($fields as $field): ?>
                <?php 
                $img = !empty($field['cover_image']) ? $base_url . $field['cover_image'] : $field_images[$field['id'] % count($field_images)]; 
                ?>
                <article class="field-card clickable-card" onclick="window.location.href='detail.php?id=<?php echo $field['id']; ?>'">

                    <!-- Image -->
                    <div class="field-image">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($field['name']); ?>" loading="lazy">
                        <div class="field-image-overlay"></div>
                        <span class="field-badge"><?php echo htmlspecialchars($field['type']); ?></span>
                        
                        <span class="field-badge" style="background-color: var(--error-color); color: white; left: 14px; right: auto;">Giảm <?php echo $field['discount_percent']; ?>%</span>

                        <span class="field-rating-badge">
                            <i data-lucide="star" style="fill:var(--rating-color);width:12px;height:12px;"></i>
                            <?php echo number_format($field['rating'], 1); ?>
                        </span>
                    </div>

                    <!-- Info -->
                    <div class="field-info">
                        <h3 class="field-name"><?php echo htmlspecialchars($field['name']); ?></h3>

                        <p class="field-address">
                            <i data-lucide="map-pin"></i>
                            <?php echo htmlspecialchars($field['address']); ?>
                        </p>

                        <!-- Amenities -->
                        <div class="field-amenities">
                            <?php foreach ($amenity_map as $key => [$icon, $label]): ?>
                                <?php if ($field[$key]): ?>
                                    <span class="amenity-pill">
                                        <i data-lucide="<?php echo $icon; ?>"></i>
                                        <?php echo $label; ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Footer -->
                        <div class="field-footer">
                            <div class="field-price">
                                <?php 
                                $discounted_price = $field['price_per_hour'] * (1 - $field['discount_percent'] / 100);
                                ?>
                                <span class="field-price-amount">
                                    <?php echo number_format($discounted_price, 0, ',', '.'); ?>đ
                                </span>
                                <span style="text-decoration: line-through; color: var(--text-muted); font-size: 13px; margin-left: 6px;">
                                    <?php echo number_format($field['price_per_hour'], 0, ',', '.'); ?>đ
                                </span>
                                <span class="field-price-unit">/ giờ thuê</span>
                            </div>

                            <a href="detail.php?id=<?php echo $field['id']; ?>" class="btn btn-primary btn-sm">
                                Xem sân <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state fade-in-up" style="text-align: center; padding: 40px 20px; background-color: rgba(255,255,255,0.01); border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 60px;">
            <i data-lucide="percent" style="width: 40px; height: 40px; color: var(--text-muted); margin-bottom: 12px;"></i>
            <p style="font-size: 15px; color: var(--text-muted);">Hiện tại chưa có chương trình giảm giá sân bóng nào diễn ra.</p>
        </div>
    <?php endif; ?>

    <!-- ══ SECTION 2: REWARDS / POINT REDEMPTION ════════════════════ -->
    <div style="background-color: rgba(255, 255, 255, 0.01); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px;">
            <div>
                <h2 style="font-size: 24px; font-weight: 700; color: var(--text-main); margin: 0; display: inline-flex; align-items: center; gap: 8px;">
                    <i data-lucide="gift" style="color: var(--primary);"></i> Đổi Điểm Thưởng Nhận Quà
                </h2>
                <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Quy đổi điểm tích lũy của bạn lấy các phần quà dịch vụ hoặc voucher giảm giá</p>
            </div>
            
            <?php if (is_logged_in()): ?>
                <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.15); border-radius: 30px; padding: 8px 20px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="award" style="color: var(--primary); width: 20px; height: 20px;"></i>
                    <span style="font-size: 14px; color: var(--text-muted); font-weight: 500;">Điểm của bạn: <b style="font-size: 18px; color: var(--primary); font-weight: 800;"><?php echo number_format($user_points, 0, ',', '.'); ?></b> điểm</span>
                </div>
            <?php else: ?>
                <a href="../auth/login.php" class="btn btn-outline btn-sm" style="text-decoration: none;">
                    <i data-lucide="log-in"></i> Đăng nhập để xem điểm
                </a>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($rewards as $id => $reward): ?>
                <?php 
                $can_redeem = is_logged_in() && ($user_points >= $reward['points']);
                ?>
                <div style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.3s; position: relative;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div style="width: 60px; height: 60px; border-radius: 10px; background: rgba(16, 185, 129, 0.08); display: flex; align-items: center; justify-content: center; color: var(--primary); overflow: hidden;">
                                <?php if (!empty($reward['image_url'])): ?>
                                    <img src="<?php echo $base_url . $reward['image_url']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i data-lucide="<?php echo $reward['icon']; ?>" style="width: 24px; height: 24px;"></i>
                                <?php endif; ?>
                            </div>
                            <span style="background: rgba(255,255,255,0.05); color: var(--text-muted); border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; border: 1px solid var(--border-color);">
                                <b style="color: var(--primary); font-size: 14px;"><?php echo $reward['points']; ?></b> điểm
                            </span>
                        </div>
                        <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 8px; line-height: 1.4;"><?php echo htmlspecialchars($reward['name']); ?></h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px; line-height: 1.5;"><?php echo htmlspecialchars($reward['desc']); ?></p>
                        <p style="font-size: 12px; color: <?php echo $reward['quantity'] > 0 ? 'var(--text-muted)' : '#dc2626'; ?>; margin-bottom: 20px; font-weight: 500;">Còn lại: <?php echo $reward['quantity'] > 0 ? $reward['quantity'] : 'Đã hết hàng'; ?></p>
                    </div>

                    <form method="POST" action="offers.php" style="margin: 0; width: 100%;">
                        <input type="hidden" name="action" value="redeem">
                        <input type="hidden" name="reward_id" value="<?php echo $id; ?>">
                        
                        <?php if (!is_logged_in()): ?>
                            <a href="../auth/login.php" class="btn btn-outline" style="width: 100%; text-decoration: none; font-size: 13px; padding: 8px; text-align: center; display: block;">
                                Đăng nhập để đổi
                            </a>
                        <?php else: ?>
                            <button type="submit" class="btn <?php echo ($can_redeem && $reward['quantity'] > 0) ? 'btn-primary' : 'btn-outline'; ?>" style="width: 100%; font-size: 13px; padding: 8px;" <?php echo (!$can_redeem || $reward['quantity'] <= 0) ? 'disabled' : ''; ?> onclick="return confirm('Bạn có chắc chắn muốn dùng <?php echo $reward['points']; ?> điểm để yêu cầu đổi phần quà này?');">
                                <?php echo ($can_redeem && $reward['quantity'] > 0) ? 'Đổi Quà Ngay' : (($reward['quantity'] <= 0) ? 'Hết Hàng' : 'Chưa Đủ Điểm'); ?>
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
