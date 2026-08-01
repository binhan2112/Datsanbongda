<?php
// Tự động load file config/mail.php nếu tồn tại
$config_path = dirname(__DIR__) . '/config/mail.php';
if (file_exists($config_path)) {
    require_once $config_path;
}

/**
 * Gửi email chứa mã OTP khôi phục mật khẩu qua giao thức SMTP (Sockets).
 * 
 * @param string $to_email Địa chỉ nhận mail
 * @param string $otp_code Mã OTP 6 chữ số
 * @return bool Trả về true nếu gửi thành công, false nếu thất bại hoặc chưa cấu hình
 */
function send_otp_email($to_email, $otp_code) {
    // 1. Kiểm tra cấu hình có hợp lệ không
    if (!defined('SMTP_USER') || !defined('SMTP_PASS') || 
        SMTP_USER === 'your_email@gmail.com' || 
        SMTP_PASS === 'your_gmail_app_password' || 
        empty(SMTP_USER) || empty(SMTP_PASS)) {
        // Chưa cấu hình hoặc dùng tài khoản mặc định -> Trả về false để bật chế độ Demo
        return false;
    }

    $subject = "Mã OTP khôi phục mật khẩu - CanThoSport";
    
    // Giao diện HTML email
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Mã OTP khôi phục mật khẩu</title>
        <style>
            body { font-family: "Segoe UI", Arial, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #e1e8ed; }
            .header { background-color: #0c2340; padding: 25px; text-align: center; color: #ffffff; }
            .header h2 { margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 0.5px; }
            .header h2 span { color: #f43f5e; }
            .content { padding: 40px 30px; text-align: center; line-height: 1.6; }
            .content p { font-size: 15px; margin-bottom: 25px; color: #555; }
            .otp-box { background-color: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 6px; padding: 15px 30px; display: inline-block; font-size: 32px; font-weight: 700; color: #0c2340; letter-spacing: 4px; margin-bottom: 30px; }
            .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #f1f5f9; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>CanTho<span style="color: #f43f5e;">Sport</span></h2>
            </div>
            <div class="content">
                <h3 style="color: #0c2340; margin-top: 0; font-size: 20px;">Yêu cầu khôi phục mật khẩu</h3>
                <p>Chúng tôi nhận được yêu cầu cấp lại mật khẩu cho tài khoản của bạn. Vui lòng sử dụng mã OTP dưới đây để xác nhận thay đổi mật khẩu:</p>
                <div class="otp-box">' . htmlspecialchars($otp_code) . '</div>
                <p style="color: #ef4444; font-weight: 600; font-size: 14px; margin-top: 10px;">Lưu ý: Mã OTP này có hiệu lực trong vòng 15 phút và chỉ sử dụng được 1 lần duy nhất.</p>
                <p style="font-size: 13px; color: #64748b; margin-top: 25px;">Nếu bạn không yêu cầu thay đổi mật khẩu này, bạn có thể bỏ qua email này một cách an toàn.</p>
            </div>
            <div class="footer">
                &copy; 2026 CanThoSport. Hệ thống đặt sân bóng trực tuyến hàng đầu Cần Thơ.
            </div>
        </div>
    </body>
    </html>
    ';

    // 2. Gửi thư qua Sockets SMTP
    try {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $username = SMTP_USER;
        $password = SMTP_PASS;
        $from = SMTP_FROM;

        // Mở kết nối TCP
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) {
            return false;
        }

        // Hàm helper đọc dữ liệu phản hồi từ SMTP Server
        $read_response = function($sock, $expected_code) {
            $response = "";
            while (substr($response, 3, 1) != ' ') {
                $line = fgets($sock, 512);
                if ($line === false) return false;
                $response .= $line;
            }
            return substr($response, 0, 3) === $expected_code;
        };

        if (!$read_response($socket, "220")) { fclose($socket); return false; }

        // Gửi lệnh EHLO bắt đầu làm việc
        fwrite($socket, "EHLO localhost\r\n");
        if (!$read_response($socket, "250")) { fclose($socket); return false; }

        // Đề xuất mã hóa TLS qua STARTTLS
        fwrite($socket, "STARTTLS\r\n");
        if (!$read_response($socket, "220")) { fclose($socket); return false; }

        // Bật mã hóa TLS trên luồng socket
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }

        // Chào EHLO lại sau khi bật mã hóa
        fwrite($socket, "EHLO localhost\r\n");
        if (!$read_response($socket, "250")) { fclose($socket); return false; }

        // Xác thực Đăng nhập AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        if (!$read_response($socket, "334")) { fclose($socket); return false; }

        fwrite($socket, base64_encode($username) . "\r\n");
        if (!$read_response($socket, "334")) { fclose($socket); return false; }

        fwrite($socket, base64_encode($password) . "\r\n");
        if (!$read_response($socket, "235")) { fclose($socket); return false; }

        // Thiết lập người gửi (MAIL FROM)
        fwrite($socket, "MAIL FROM: <$from>\r\n");
        if (!$read_response($socket, "250")) { fclose($socket); return false; }

        // Thiết lập người nhận (RCPT TO)
        fwrite($socket, "RCPT TO: <$to_email>\r\n");
        if (!$read_response($socket, "250")) { fclose($socket); return false; }

        // Khai báo chuẩn bị gửi DATA
        fwrite($socket, "DATA\r\n");
        if (!$read_response($socket, "354")) { fclose($socket); return false; }

        // Nội dung Headers và Body của Email
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <$from>\r\n";
        $headers .= "To: <$to_email>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date("r") . "\r\n";

        fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        if (!$read_response($socket, "250")) { fclose($socket); return false; }

        // Ngắt kết nối
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
