<?php
// ═══════════════════════════════════════════════════════
// TRANG ĐIỀU KHOẢN DỊCH VỤ
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

$base_url = '../';
$current_page = 'terms';
$page_title = 'Điều Khoản Dịch Vụ — CanThoSport';
include '../includes/header.php';
?>

<div class="container" style="max-width: 900px; padding-top: 40px; padding-bottom: 60px;">
    <div style="background: white; border-radius: 20px; padding: 40px; border: 1px solid var(--border-color); box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
        <h1 style="font-size: 32px; font-weight: 800; color: var(--text-main); margin-bottom: 10px;">Điều Khoản Dịch Vụ</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 30px;">Cập nhật lần cuối: Ngày 15 tháng 07 năm 2026</p>

        <hr style="border: 0; border-top: 1px solid var(--border-color); margin-bottom: 30px;">

        <div style="line-height: 1.8; color: var(--text-secondary); font-size: 15px;">
            <h3 style="color: var(--text-main); font-size: 18px; margin-top: 25px; margin-bottom: 10px;">1. Quy Định Chung</h3>
            <p>Bằng việc truy cập và sử dụng hệ thống đặt sân CanThoSport, bạn đồng ý tuân thủ các điều khoản dịch vụ dưới đây. Nếu bạn không đồng ý với bất kỳ phần nào của các điều khoản này, vui lòng ngừng sử dụng hệ thống.</p>

            <h3 style="color: var(--text-main); font-size: 18px; margin-top: 25px; margin-bottom: 10px;">2. Tài Khoản Người Dùng</h3>
            <p>Người dùng có trách nhiệm bảo mật thông tin đăng nhập và mọi hoạt động diễn ra dưới tài khoản của mình. Vui lòng cung cấp thông tin chính xác (Họ tên, Email, Số điện thoại) khi đăng ký.</p>

            <h3 style="color: var(--text-main); font-size: 18px; margin-top: 25px; margin-bottom: 10px;">3. Quy Định Đặt Sân & Hủy Đơn</h3>
            <ul style="padding-left: 20px; margin-bottom: 15px;">
                <li>Khách hàng có thể đặt sân trước tối đa 30 ngày.</li>
                <li>Mọi đơn đặt sân ở trạng thái "Chờ xác nhận" hoặc "Đã xác nhận" có thể được hủy trên hệ thống trước thời gian đá ít nhất 2 giờ.</li>
                <li>Trường hợp khách hàng không đến sân mà không báo trước (No-show) quá 3 lần có thể bị khóa tài khoản tạm thời.</li>
            </ul>

            <h3 style="color: var(--text-main); font-size: 18px; margin-top: 25px; margin-bottom: 10px;">4. Thanh Toán & Tích Điểm</h3>
            <p>Hệ thống hỗ trợ thanh toán tiền mặt tại sân, ví MoMo và chuyển khoản ngân hàng. Điểm tích lũy chỉ có giá trị quy đổi giảm giá trên hệ thống CanThoSport và không có giá trị quy đổi thành tiền mặt.</p>

            <h3 style="color: var(--text-main); font-size: 18px; margin-top: 25px; margin-bottom: 10px;">5. Trách Nhiệm Của Chủ Sân</h3>
            <p>Chủ sân có trách nhiệm đảm bảo chất lượng mặt sân, hệ thống chiếu sáng và cơ sở vật chất đúng theo thông tin mô tả trên hệ thống.</p>

            <h3 style="color: var(--text-main); font-size: 18px; margin-top: 25px; margin-bottom: 10px;">6. Thay Đổi Điều Khoản</h3>
            <p>CanThoSport có quyền thay đổi các điều khoản này bất kỳ lúc nào. Thay đổi sẽ có hiệu lực ngay khi được đăng tải lên website.</p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
