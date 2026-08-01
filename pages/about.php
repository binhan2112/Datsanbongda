<?php
// ═══════════════════════════════════════════════════════
// TRANG GIỚI THIỆU VÀ LIÊN HỆ
// ═══════════════════════════════════════════════════════
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

$base_url = '../';
$current_page = 'about';
$page_title = 'Giới Thiệu — CanThoSport';
include '../includes/header.php';
?>

<style>
.about-hero {
    background: linear-gradient(135deg, #0c2340 0%, #1a365d 100%);
    color: white;
    padding: 60px 0;
    text-align: center;
    border-radius: 0 0 24px 24px;
    margin-bottom: 50px;
}
.about-hero h1 {
    font-size: 38px;
    font-weight: 800;
    margin-bottom: 16px;
}
.about-hero h1 span {
    color: var(--primary);
}
.about-hero p {
    font-size: 18px;
    color: #cbd5e1;
    max-width: 650px;
    margin: 0 auto;
}
.feature-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    transition: transform 0.3s, box-shadow 0.3s;
    height: 100%;
}
.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}
.icon-box {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    background: var(--primary-subtle);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}
.stats-banner {
    background: white;
    border-radius: 20px;
    padding: 40px;
    border: 1px solid var(--border-color);
    margin: 50px 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}
.contact-section {
    scroll-margin-top: 100px;
}
</style>

<div class="about-hero">
    <div class="container">
        <h1>Về Chúng Tôi — CanTho<span>Sport</span></h1>
        <p>Nền tảng kết nối và đặt sân bóng đá mini cỏ nhân tạo trực tuyến hiện đại, nhanh chóng và uy tín hàng đầu tại Thành phố Cần Thơ.</p>
    </div>
</div>

<div class="container">
    <!-- Sứ mệnh & Tầm nhìn -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 60px;">
        <div class="feature-card">
            <div class="icon-box">
                <i data-lucide="target" style="width: 30px; height: 30px;"></i>
            </div>
            <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 12px; color: var(--text-main);">Sứ Mệnh</h3>
            <p style="color: var(--text-muted); line-height: 1.7;">
                Mang đến trải nghiệm đặt sân bóng nhanh chóng chỉ trong vài thao tác. Giúp người yêu bóng đá tại Cần Thơ dễ dàng tìm sân, so sánh giá cả, đặt giờ và thanh toán thuận tiện mà không cần tốn thời gian gọi điện.
            </p>
        </div>

        <div class="feature-card">
            <div class="icon-box" style="background: rgba(0, 85, 255, 0.1); color: var(--accent);">
                <i data-lucide="eye" style="width: 30px; height: 30px;"></i>
            </div>
            <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 12px; color: var(--text-main);">Tầm Nhìn</h3>
            <p style="color: var(--text-muted); line-height: 1.7;">
                Trở thành hệ sinh thái thể thao số 1 Cần Thơ và vùng ĐBSCL. Số hóa toàn bộ quản lý vận hành cho các chủ sân bóng, hỗ trợ tổ chức giải đấu phong trào chuyên nghiệp và kết nối cộng đồng đam mê thể thao.
            </p>
        </div>

        <div class="feature-card">
            <div class="icon-box" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i data-lucide="shield-check" style="width: 30px; height: 30px;"></i>
            </div>
            <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 12px; color: var(--text-main);">Cam Kết</h3>
            <p style="color: var(--text-muted); line-height: 1.7;">
                Minh bạch 100% giá thuê sân, chính xác trạng thái khung giờ trống realtime. Đảm bảo quyền lợi khách hàng với chính sách tích điểm đổi thưởng và hỗ trợ khách hàng nhiệt tình 24/7.
            </p>
        </div>
    </div>

    <!-- Thống kê nổi bật -->
    <div class="stats-banner">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 30px; text-align: center;">
            <div>
                <div style="font-size: 40px; font-weight: 800; color: var(--primary); margin-bottom: 5px;">50+</div>
                <div style="color: var(--text-muted); font-weight: 600;">Sân bóng liên kết</div>
            </div>
            <div>
                <div style="font-size: 40px; font-weight: 800; color: var(--accent); margin-bottom: 5px;">10,000+</div>
                <div style="color: var(--text-muted); font-weight: 600;">Lượt đặt sân thành công</div>
            </div>
            <div>
                <div style="font-size: 40px; font-weight: 800; color: #10b981; margin-bottom: 5px;">98%</div>
                <div style="color: var(--text-muted); font-weight: 600;">Khách hàng hài lòng</div>
            </div>
            <div>
                <div style="font-size: 40px; font-weight: 800; color: #f59e0b; margin-bottom: 5px;">9/9</div>
                <div style="color: var(--text-muted); font-weight: 600;">Quận / Huyện Cần Thơ</div>
            </div>
        </div>
    </div>

    <!-- Thông tin liên hệ & Bản đồ -->
    <div id="contact" class="contact-section" style="margin-bottom: 80px;">
        <h2 style="font-size: 28px; font-weight: 800; text-align: center; margin-bottom: 40px; color: var(--text-main);">Liên Hệ Với Chúng Tôi</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
            <!-- Form liên hệ -->
            <div class="feature-card">
                <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Gửi phản hồi / Trợ giúp</h3>
                <form onsubmit="alert('Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.'); return false;">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px;">Họ và tên</label>
                        <input type="text" class="form-control" placeholder="Nguyễn Văn A" required>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px;">Email</label>
                        <input type="email" class="form-control" placeholder="name@domain.com" required>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px;">Nội dung</label>
                        <textarea class="form-control" rows="4" placeholder="Bạn cần hỗ trợ điều gì..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i data-lucide="send"></i> Gửi tin nhắn
                    </button>
                </form>
            </div>

            <!-- Thông tin trực tiếp -->
            <div class="feature-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 24px;">Thông Tin Trụ Sở</h3>
                    
                    <div style="display: flex; align-items: flex-start; gap: 16px; margin-bottom: 20px;">
                        <div style="background: var(--primary-subtle); color: var(--primary); padding: 10px; border-radius: 10px;">
                            <i data-lucide="map-pin" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 15px;">Địa chỉ</div>
                            <div style="color: var(--text-muted); font-size: 14px;">Đường 3/2, Phường Xuân Khánh, Quận Ninh Kiều, TP. Cần Thơ</div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 16px; margin-bottom: 20px;">
                        <div style="background: rgba(0, 85, 255, 0.1); color: var(--accent); padding: 10px; border-radius: 10px;">
                            <i data-lucide="phone" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 15px;">Hotline hỗ trợ</div>
                            <div style="color: var(--text-muted); font-size: 14px;">1900 6868 - (0292) 3888 999</div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 16px; margin-bottom: 20px;">
                        <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 10px; border-radius: 10px;">
                            <i data-lucide="mail" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 15px;">Email liên hệ</div>
                            <div style="color: var(--text-muted); font-size: 14px;">support@canthosport.vn</div>
                        </div>
                    </div>
                </div>

                <div style="border-radius: 12px; overflow: hidden; height: 180px; border: 1px solid var(--border-color);">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3928.841454308153!2d105.76842661474246!3d10.029933792830604!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31a0895a51d34ced%3A0xd14d746e9f6e913f!2zVHLGsOG7nW5nIMSQ4bqhaSBo4buNYyBD4bqnbiBUaMah!5e0!3m2!1svi!2s!4v1625000000000!5m2!1svi!2s" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
