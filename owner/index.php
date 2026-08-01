<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

// Bảo vệ trang: Chỉ cho phép owner
require_login('owner');

$owner_id = $_SESSION['user_id'];

// Lấy thống kê
try {
    // Tổng số sân
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM fields WHERE owner_id = ?");
    $stmt1->execute([$owner_id]);
    $total_fields = $stmt1->fetchColumn();

    // Tổng số lượt đặt (các đơn không bị hủy)
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN fields f ON b.field_id = f.id WHERE f.owner_id = ? AND b.status != 'cancelled'");
    $stmt2->execute([$owner_id]);
    $total_bookings = $stmt2->fetchColumn();

    // Doanh thu ước tính (chỉ tính đơn hoàn thành hoặc đã thanh toán)
    $stmt3 = $pdo->prepare("SELECT SUM(total_price) FROM bookings b JOIN fields f ON b.field_id = f.id WHERE f.owner_id = ? AND b.status IN ('confirmed', 'completed')");
    $stmt3->execute([$owner_id]);
    $total_revenue = $stmt3->fetchColumn();
    if (!$total_revenue) $total_revenue = 0;

} catch (PDOException $e) {
    die("Lỗi cơ sở dữ liệu: " . $e->getMessage());
}

$page_title = 'Bảng điều khiển - Chủ sân';
$base_url = '../';
include '../includes/dashboard_header.php';
?>

<div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="background: rgba(0, 191, 166, 0.1); color: var(--primary); padding: 15px; border-radius: 50%;">
                    <i data-lucide="map" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <h3 style="font-size: 24px; margin-bottom: 4px;"><?php echo $total_fields; ?></h3>
                    <p style="color: var(--text-muted); font-size: 14px;">Sân bóng của bạn</p>
                </div>
            </div>
        </div>

        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 15px; border-radius: 50%;">
                    <i data-lucide="calendar-check" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <h3 style="font-size: 24px; margin-bottom: 4px;"><?php echo $total_bookings; ?></h3>
                    <p style="color: var(--text-muted); font-size: 14px;">Lượt đặt thành công</p>
                </div>
            </div>
        </div>

        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 15px; border-radius: 50%;">
                    <i data-lucide="wallet" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <h3 style="font-size: 24px; margin-bottom: 4px;"><?php echo number_format($total_revenue, 0, ',', '.'); ?>đ</h3>
                    <p style="color: var(--text-muted); font-size: 14px;">Doanh thu ước tính</p>
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border); overflow: hidden;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border);">
                <h3 style="font-size: 18px; margin: 0;">Truy cập nhanh</h3>
            </div>
            <div style="padding: 20px;">
                <a href="fields.php" style="display: flex; align-items: center; gap: 10px; color: var(--text); text-decoration: none; padding: 12px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 12px; transition: 0.2s;">
                    <i data-lucide="map-pin" style="color: var(--primary);"></i> Quản lý Sân bóng
                </a>
                <a href="bookings.php" style="display: flex; align-items: center; gap: 10px; color: var(--text); text-decoration: none; padding: 12px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 12px; transition: 0.2s;">
                    <i data-lucide="list" style="color: var(--primary);"></i> Quản lý Đơn đặt sân
                </a>
                <a href="profile.php" style="display: flex; align-items: center; gap: 10px; color: var(--text); text-decoration: none; padding: 12px; border: 1px solid var(--border); border-radius: 8px; transition: 0.2s;">
                    <i data-lucide="user" style="color: var(--primary);"></i> Hồ sơ cá nhân
                </a>
            </div>
        </div>
        
        <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border); overflow: hidden;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border);">
                <h3 style="font-size: 18px; margin: 0;">Thông báo mới</h3>
            </div>
            <div style="padding: 20px;">
                <p style="color: var(--text-muted);">Bạn chưa có thông báo mới nào.</p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/dashboard_footer.php'; ?>
