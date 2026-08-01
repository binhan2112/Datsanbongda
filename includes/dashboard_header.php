<?php
// includes/dashboard_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_role = $_SESSION['user_role'] ?? 'customer';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Bảng điều khiển'; ?></title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            /* Modern Vibrant Color Palette */
            --primary: #0ea5e9;       /* Sky Blue */
            --primary-dark: #0284c7;  /* Deep Sky */
            --primary-light: rgba(14, 165, 233, 0.1);
            --accent: #8b5cf6;        /* Violet */
            --success: #10b981;       /* Emerald */
            --warning: #f59e0b;       /* Amber */
            --danger: #ef4444;        /* Rose */
            
            /* Neutrals */
            --dark: #0f172a;          /* Slate 900 */
            --sidebar-bg: #020617;    /* Slate 950 */
            --light: #f8fafc;         /* Slate 50 */
            --border: #e2e8f0;        /* Slate 200 */
            --text: #334155;          /* Slate 700 */
            --text-muted: #64748b;    /* Slate 500 */
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
            --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.05), 0 4px 6px -4px rgb(0 0 0 / 0.05);
            --shadow-float: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            /* Transitions */
            --transition-smooth: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; scroll-behavior: smooth; }
        
        /* Modern Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        body { background-color: var(--light); color: var(--text); display: flex; min-height: 100vh; -webkit-font-smoothing: antialiased; }
        
        /* Sidebar */
        .sidebar {
            width: 270px;
            background: var(--sidebar-bg);
            background-image: radial-gradient(circle at top left, rgba(14, 165, 233, 0.15), transparent 400px);
            color: white;
            display: flex;
            flex-direction: column;
            transition: var(--transition-smooth);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            box-shadow: 4px 0 24px rgba(0,0,0,0.1);
        }
        .sidebar-brand {
            padding: 28px 24px;
            font-size: 24px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: white;
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        .sidebar-brand span { 
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .sidebar-brand i { color: var(--primary); }
        
        .sidebar-menu { flex: 1; padding: 24px 16px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
        .menu-label { padding: 16px 16px 8px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1.5px; }
        
        .menu-item {
            display: flex; align-items: center; gap: 14px; padding: 12px 16px;
            color: #94a3b8; text-decoration: none; transition: var(--transition-fast);
            font-weight: 500; border-radius: 12px; font-size: 14.5px;
            position: relative; overflow: hidden;
        }
        .menu-item i { transition: var(--transition-fast); color: #64748b; width: 18px; height: 18px; }
        .menu-item:hover {
            background-color: rgba(255,255,255,0.05);
            color: #f8fafc;
            transform: translateX(6px);
        }
        .menu-item:hover i { color: var(--primary); transform: scale(1.15); }
        
        .menu-item.active {
            background: linear-gradient(90deg, rgba(14,165,233,0.15) 0%, rgba(14,165,233,0) 100%);
            color: white;
            border-left: 3px solid var(--primary);
            border-radius: 4px 12px 12px 4px;
            font-weight: 600;
        }
        .menu-item.active i { color: var(--primary); }
        
        /* Main Content */
        .main-wrapper {
            flex: 1;
            margin-left: 270px;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: var(--light);
        }
        
        /* Top Navbar */
        .topbar {
            height: 76px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 36px;
            position: sticky;
            top: 0;
            z-index: 90;
        }
        .topbar-right { display: flex; align-items: center; gap: 24px; }
        
        .user-profile { 
            display: flex; align-items: center; gap: 12px; cursor: pointer; 
            padding: 6px 16px 6px 6px; border-radius: 50px; 
            transition: var(--transition-fast); 
            background: white; border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }
        .user-profile:hover { box-shadow: var(--shadow); transform: translateY(-1px); }
        .user-avatar { 
            width: 36px; height: 36px; border-radius: 50%; 
            background: linear-gradient(135deg, var(--primary), var(--accent)); 
            color: white; display: flex; align-items: center; justify-content: center; 
            font-weight: 700; box-shadow: 0 4px 10px rgba(14,165,233,0.2); 
        }
        
        /* Content Area */
        .content-area { padding: 36px; flex: 1; overflow-y: auto; }
        
        /* Premium Cards */
        .card, .dash-card { 
            background: white; border-radius: 20px; 
            box-shadow: var(--shadow); border: 1px solid rgba(226,232,240,0.8); 
            transition: var(--transition-smooth);
            overflow: hidden; 
        }
        .card:hover, .dash-card:hover { 
            box-shadow: var(--shadow-float);
            transform: translateY(-4px); 
            border-color: rgba(14,165,233,0.2);
        }
        .card-header, .dash-card-header { 
            padding: 24px; border-bottom: 1px solid rgba(226,232,240,0.5); 
            display: flex; justify-content: space-between; align-items: center; 
            background: rgba(248,250,252,0.5);
        }
        .card-title, .dash-card-header h3 { 
            font-size: 16px; font-weight: 700; color: var(--dark); margin: 0; 
            letter-spacing: -0.2px;
        }
        .card-body, .dash-card-body { padding: 24px; }
        
        /* Buttons */
        .btn-logout { 
            background: white; border: 1px solid #fecdd3; color: #e11d48; 
            padding: 8px 18px; border-radius: 50px; text-decoration: none; 
            font-weight: 600; font-size: 13.5px; transition: var(--transition-fast); 
            display: inline-flex; align-items: center; gap: 8px; 
            box-shadow: var(--shadow-sm); 
        }
        .btn-logout:hover { 
            background: #e11d48; color: white; border-color: #e11d48;
            transform: translateY(-2px); box-shadow: 0 4px 12px rgba(225,29,72,0.2); 
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <a href="<?php echo $base_url; ?>index.php" class="sidebar-brand">
        <img src="<?php echo $base_url; ?>assets/images/logo.png" alt="CanThoSport Logo" style="height: 32px; width: auto; border-radius: 4px; object-fit: contain; margin-right: 8px;"> CanTho<span>Sport</span>
    </a>
    
    <div class="sidebar-menu">
        <?php if ($user_role === 'admin'): ?>
            <div class="menu-label">Quản trị viên</div>
            <a href="<?php echo $base_url; ?>admin/index.php" class="menu-item <?=($active_menu??'')==='index'?'active':''?>">
                <i data-lucide="layout-dashboard"></i> Tổng quan
            </a>
            <a href="<?php echo $base_url; ?>admin/stats.php" class="menu-item <?=($active_menu??'')==='stats'?'active':''?>">
                <i data-lucide="bar-chart-2"></i> Thống kê Chi tiết
            </a>
            <a href="<?php echo $base_url; ?>admin/approve_fields.php" class="menu-item" style="position:relative;">
                <i data-lucide="check-square"></i> Duyệt sân
                <?php
                try {
                    $pf = $pdo->query("SELECT COUNT(*) FROM fields WHERE status='pending'")->fetchColumn();
                    if ($pf > 0) echo '<span style="position:absolute;right:12px;background:#ef4444;color:white;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;">'.min($pf,99).'</span>';
                } catch(Exception $e) {}
                ?>
            </a>
            <a href="<?php echo $base_url; ?>admin/fields.php" class="menu-item">
                <i data-lucide="map"></i> Quản lý Sân bóng
            </a>
            <a href="<?php echo $base_url; ?>admin/bookings.php" class="menu-item">
                <i data-lucide="calendar"></i> Quản lý Đặt sân
            </a>
            <a href="<?php echo $base_url; ?>admin/events.php" class="menu-item">
                <i data-lucide="trophy"></i> Quản lý Giải đấu
            </a>
            <a href="<?php echo $base_url; ?>admin/reviews.php" class="menu-item">
                <i data-lucide="star"></i> Quản lý Đánh giá
            </a>
            <a href="<?php echo $base_url; ?>admin/users.php" class="menu-item">
                <i data-lucide="users"></i> Người dùng
            </a>
            <a href="<?php echo $base_url; ?>admin/chat.php" class="menu-item">
                <i data-lucide="message-square"></i> Chat Hệ thống
            </a>
            <a href="<?php echo $base_url; ?>admin/rewards.php" class="menu-item <?=($active_menu??'')==='rewards'?'active':''?>">
                <i data-lucide="gift"></i> Quản lý Quà tặng
            </a>
            
        <?php elseif ($user_role === 'owner'): ?>
            <div class="menu-label">Chủ Sân</div>
            <a href="<?php echo $base_url; ?>owner/index.php" class="menu-item">
                <i data-lucide="layout-dashboard"></i> Tổng quan
            </a>
            <a href="<?php echo $base_url; ?>owner/revenue.php" class="menu-item">
                <i data-lucide="trending-up"></i> Báo cáo Doanh thu
            </a>
            <a href="<?php echo $base_url; ?>owner/calendar.php" class="menu-item">
                <i data-lucide="calendar-days"></i> Lịch Đặt sân
            </a>
            <a href="<?php echo $base_url; ?>owner/fields.php" class="menu-item">
                <i data-lucide="map-pin"></i> Sân bóng của tôi
            </a>
            <a href="<?php echo $base_url; ?>owner/bookings.php" class="menu-item">
                <i data-lucide="list"></i> Quản lý Đặt sân
            </a>
            <a href="<?php echo $base_url; ?>owner/unavailable.php" class="menu-item">
                <i data-lucide="clock-off"></i> Khung giờ bảo trì
            </a>
            <a href="<?php echo $base_url; ?>owner/reviews.php" class="menu-item">
                <i data-lucide="star"></i> Quản lý Đánh giá
            </a>
            <a href="<?php echo $base_url; ?>owner/chat.php" class="menu-item">
                <i data-lucide="message-square"></i> Hộp thư hỗ trợ
            </a>
            <a href="<?php echo $base_url; ?>owner/admin_chat.php" class="menu-item">
                <i data-lucide="help-circle"></i> Liên hệ Admin
            </a>
            <a href="<?php echo $base_url; ?>owner/rewards.php" class="menu-item <?=($active_menu??'')==='rewards'?'active':''?>">
                <i data-lucide="gift"></i> Quản lý Quà tặng
            </a>
        <?php endif; ?>
    </div>
</aside>

<!-- Main Wrapper -->
<div class="main-wrapper">
    <!-- Topbar -->
    <header class="topbar">
        <div>
            <h2 style="font-size: 20px; font-weight: 600; color: var(--dark);"><?php echo isset($page_title) ? $page_title : 'Bảng điều khiển'; ?></h2>
        </div>
        <div class="topbar-right">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_full_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div style="font-weight: 500; font-size: 14px;">
                    <?php echo htmlspecialchars($_SESSION['user_full_name'] ?? 'User'); ?>
                </div>
            </div>
            <a href="<?php echo $base_url; ?>auth/logout.php" class="btn-logout">
                <i data-lucide="log-out" style="width: 16px; height: 16px;"></i> Đăng xuất
            </a>
        </div>
    </header>

    <!-- Content Area -->
    <main class="content-area">
