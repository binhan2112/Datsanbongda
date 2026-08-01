<?php
// ═══════════════════════════════════════════════════════
// TRANG CHÍNH SÁCH BẢO MẬT
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

$base_url = '../';
$current_page = 'privacy';
$page_title = 'Chính Sách Bảo Mật — CanThoSport';
include '../includes/header.php';
?>

<div class="container" style="max-width: 900px; padding-top: 40px; padding-bottom: 60px;">
    <div style="background: white; border-radius: 20px; padding: 40px; border: 1px solid var(--border-color); box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
        <h1 style="font-size: 32px; font-weight: 800; color: var(--text-main); margin-bottom: 10px;">Chính Sách Bảo Mật</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 30px;">Cập nhật lần cuối: Ngày 15 tháng 07 năm 2026</p>

        <hr style="border: 0; border-top: 1px solid var(--border-color); margin-bottom: 30px;">

        <div style="line-height: 1.8; color: var(--text-secondary); font-size: 15px;">
            <h3 style="color: var(--text-main); font-size: 18px; margin-top: 25px; margin-bottom: 10px;">1. Thu Thập Thông Tin</h3>
            <p>Chúng tôi thu thập các thông tin cá nhân bạn cung cấp khi đăng ký tài khoản bao gồm: Họ và tên, Địa chỉ Email, Số điện thoại, và Ảnh đại diện (nếu có).</p>

            <h3 style="color: var(--text-main); font-size: 18px; margin-top: 25px; margin-bottom: 10px;">2. Mục Đích Sử Dụng Thông Tin</h3>
            <ul style="padding-left: 20px; margin-bottom: 15px;">
                <li>Xác nhận và xử lý các đơn đặt sân bóng.</li>
                <li>Gửi thông báo xác nhận, mã OTP khôi phục mật khẩu hoặc thông báo sự kiện.</li>
                <li>Liên lạc hỗ trợ khi xảy ra sự cố đặt sân.</li>
                <li>Nâng cao chất lượng dịch vụ dựa trên phản hồi của người dùng.</li>
            </ul>

            <h3 style="color: var(--text-main); font-size: 18px; margin-top: 25px; margin-bottom: 10px;">3. Bảo Vệ Dữ Liệu</h3>
            <p>CanThoSport sử dụng thuật toán mã hóa chuẩn BCRYPT cho mật khẩu và giao thức HTTPS mã hóa dữ liệu truyền tải. Chúng tôi cam kết không bán, chia sẻ hay trao đổi thông tin cá nhân của bạn cho bên thứ ba vì mục đích thương mại.</p>

            <h3 style="color: var(--text-main); font-size: 18px; margin-top: 25px; margin-bottom: 10px;">4. Quyền Của Người Dùng</h3>
            <p>Bạn có quyền truy cập, chỉnh sửa hoặc yêu cầu xóa thông tin cá nhân bất kỳ lúc nào thông qua trang Hồ sơ cá nhân hoặc liên hệ bộ phận hỗ trợ.</p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
