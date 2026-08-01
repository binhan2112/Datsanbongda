<?php
// ═══════════════════════════════════════════════════════
// HEADER CHUNG — Dùng cho tất cả các trang
// ═══════════════════════════════════════════════════════
// Biến cần set trước khi include:
//   $page_title   (string)  — Tiêu đề trang
//   $current_page (string)  — 'home', 'fields', 'events', 'bookings', 'profile', etc.
//   $base_url     (string)  — Đường dẫn gốc (ví dụ: '../' hoặc '')
// ═══════════════════════════════════════════════════════

if (!isset($base_url)) $base_url = '';
if (!isset($current_page)) $current_page = '';
if (!isset($page_title)) $page_title = 'CanThoSport';

require_once __DIR__ . '/lang_helper.php';

// Đếm thông báo và tin nhắn chưa đọc, lấy avatar người dùng
$unread_notifications = 0;
$unread_messages = 0;
$user_avatar = 'default-avatar.png';
if (is_logged_in()) {
    try {
        $notif_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
        $notif_stmt->execute(['uid' => $_SESSION['user_id']]);
        $unread_notifications = (int) $notif_stmt->fetchColumn();
        
        $msg_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = :uid AND is_read = 0");
        $msg_count_stmt->execute(['uid' => $_SESSION['user_id']]);
        $unread_messages = (int) $msg_count_stmt->fetchColumn();

        $user_data_stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = :uid");
        $user_data_stmt->execute(['uid' => $_SESSION['user_id']]);
        $user_avatar = $user_data_stmt->fetchColumn() ?: 'default-avatar.png';
    } catch (PDOException $e) {
        $unread_notifications = 0;
        $unread_messages = 0;
        $user_avatar = 'default-avatar.png';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title><?php echo htmlspecialchars($page_title); ?> — CanThoSport</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/images/logo.png">
    <meta name="description" content="Hệ thống đặt sân bóng đá mini 5v5, 7v7, 11v11 trực tuyến hàng đầu tại Cần Thơ.">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css?v=1.3">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<header>
    <div class="container navbar">
        <a href="<?php echo $base_url; ?>index.php" class="logo" id="main-logo" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
            <img src="<?php echo $base_url; ?>assets/images/logo.png" alt="CanThoSport Logo" style="height: 36px; width: auto; border-radius: 4px; object-fit: contain;">
            CanTho<span>Sport</span>
        </a>

        <ul class="nav-links">
            <li><a href="<?php echo $base_url; ?>index.php" class="<?php echo $current_page === 'home' ? 'active' : ''; ?>"><?php echo __trans('home'); ?></a></li>
            <li><a href="<?php echo $base_url; ?>index.php#fields-list" class="<?php echo $current_page === 'fields' ? 'active' : ''; ?>"><?php echo __trans('fields_list'); ?></a></li>
            <li><a href="<?php echo $base_url; ?>pages/offers.php" class="<?php echo $current_page === 'offers' ? 'active' : ''; ?>"><?php echo __trans('offers'); ?></a></li>
            <li><a href="<?php echo $base_url; ?>pages/events.php" class="<?php echo $current_page === 'events' ? 'active' : ''; ?>"><?php echo __trans('events'); ?></a></li>
            <li><a href="<?php echo $base_url; ?>pages/my_bookings.php" class="<?php echo $current_page === 'bookings' ? 'active' : ''; ?>"><?php echo __trans('my_bookings'); ?></a></li>
            <?php if (is_logged_in()): ?>
                <li>
                    <a href="<?php echo $base_url; ?>pages/chat.php" class="<?php echo $current_page === 'chat' ? 'active' : ''; ?>" style="position: relative;">
                        Liên hệ
                        <?php if ($unread_messages > 0): ?>
                            <span style="position: absolute; top: -5px; right: -12px; background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 9px; font-weight: 700; line-height: 1;">
                                <?php echo $unread_messages; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="nav-auth">
            <!-- Language Switcher -->
            <div style="display: flex; gap: 4px; border: 1px solid var(--border-color); border-radius: 20px; padding: 2px 6px; background: rgba(0,0,0,0.02); margin-right: 6px;">
                <a href="?lang=vi" style="font-size: 11px; font-weight: 700; text-decoration: none; padding: 2px 6px; border-radius: 12px; <?php echo $current_lang === 'vi' ? 'background: var(--primary); color: white;' : 'color: var(--text-muted);'; ?>">VI</a>
                <a href="?lang=en" style="font-size: 11px; font-weight: 700; text-decoration: none; padding: 2px 6px; border-radius: 12px; <?php echo $current_lang === 'en' ? 'background: var(--primary); color: white;' : 'color: var(--text-muted);'; ?>">EN</a>
            </div>

            <?php if (is_logged_in()): ?>
                <!-- Nút chuông thông báo -->
                <a href="<?php echo $base_url; ?>pages/notifications.php" class="nav-notif-btn" title="Thông báo">
                    <i data-lucide="bell"></i>
                    <?php if ($unread_notifications > 0): ?>
                        <span class="notif-badge"><?php echo $unread_notifications > 9 ? '9+' : $unread_notifications; ?></span>
                    <?php endif; ?>
                </a>

                <!-- User dropdown -->
                <div class="nav-user-dropdown" id="userDropdown">
                    <button class="nav-user-pill" onclick="toggleUserMenu()" type="button" id="userDropdownBtn">
                        <div class="avatar" style="position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <?php 
                            if (!empty($user_avatar) && $user_avatar !== 'default-avatar.png') {
                                if (filter_var($user_avatar, FILTER_VALIDATE_URL)) {
                                    echo '<img src="'.htmlspecialchars($user_avatar).'" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">';
                                } elseif (file_exists($base_url . 'assets/uploads/avatars/' . $user_avatar)) {
                                    echo '<img src="'.$base_url.'assets/uploads/avatars/'.htmlspecialchars($user_avatar).'" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">';
                                } else {
                                    echo mb_strtoupper(mb_substr($_SESSION['user_full_name'], 0, 1));
                                }
                            } else {
                                echo mb_strtoupper(mb_substr($_SESSION['user_full_name'], 0, 1));
                            }
                            ?>
                        </div>
                        <span><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></span>
                        <span class="role-chip"><?php echo $_SESSION['user_role'] === 'owner' ? 'Chủ Sân' : ($_SESSION['user_role'] === 'admin' ? 'Admin' : 'Khách'); ?></span>
                        <i data-lucide="chevron-down" style="width:14px;height:14px;color:var(--text-muted);"></i>
                    </button>
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <?php if (isset($_SESSION['user_role'])): ?>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="<?php echo $base_url; ?>admin/index.php" class="dropdown-item" style="color: var(--primary); font-weight: 600;">
                                    <i data-lucide="layout-dashboard"></i> Bảng điều khiển Admin
                                </a>
                            <?php elseif ($_SESSION['user_role'] === 'owner'): ?>
                                <a href="<?php echo $base_url; ?>owner/index.php" class="dropdown-item" style="color: var(--primary); font-weight: 600;">
                                    <i data-lucide="layout-dashboard"></i> Quản lý Sân bóng
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php
                        $profile_link = $base_url . 'pages/profile.php';
                        if ($_SESSION['user_role'] === 'admin') {
                            $profile_link = $base_url . 'admin/profile.php';
                        } elseif ($_SESSION['user_role'] === 'owner') {
                            $profile_link = $base_url . 'owner/profile.php';
                        }
                        ?>
                        <a href="<?php echo $profile_link; ?>" class="dropdown-item">
                            <i data-lucide="user"></i> Hồ sơ cá nhân
                        </a>
                        <a href="<?php echo $base_url; ?>pages/my_bookings.php" class="dropdown-item">
                            <i data-lucide="calendar"></i> Lịch đặt sân
                        </a>
                        <a href="<?php echo $base_url; ?>pages/favorites.php" class="dropdown-item">
                            <i data-lucide="heart"></i> Sân yêu thích
                        </a>
                        <a href="<?php echo $base_url; ?>pages/notifications.php" class="dropdown-item">
                            <i data-lucide="bell"></i> Thông báo
                            <?php if ($unread_notifications > 0): ?>
                                <span class="dropdown-badge"><?php echo $unread_notifications; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo $base_url; ?>pages/chat.php?chat_type=admin" class="dropdown-item">
                            <i data-lucide="help-circle"></i> Hỗ trợ hệ thống
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $base_url; ?>auth/logout.php" class="dropdown-item dropdown-item-danger">
                            <i data-lucide="log-out"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo $base_url; ?>auth/register.php" class="btn btn-ghost btn-sm">Đăng Ký</a>
                <a href="<?php echo $base_url; ?>auth/login.php" class="btn btn-primary btn-sm" id="btn-login">
                    <i data-lucide="user"></i> Đăng Nhập
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile menu toggle -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" type="button" aria-label="Menu">
            <i data-lucide="menu"></i>
        </button>
    </div>
</header>

<?php if (is_logged_in()): ?>
<script>
    // Realtime notification polling every 15 seconds
    setInterval(function() {
        fetch('<?php echo $base_url; ?>api/notifications.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const notifBadge = document.querySelector('.notif-badge');
                    if (notifBadge) {
                        if (data.unread_notifications > 0) {
                            notifBadge.textContent = data.unread_notifications > 9 ? '9+' : data.unread_notifications;
                            notifBadge.style.display = 'inline-block';
                        } else {
                            notifBadge.style.display = 'none';
                        }
                    }
                }
            })
            .catch(err => console.error(err));
    }, 15000);
</script>
<?php endif; ?>
