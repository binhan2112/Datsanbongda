<?php
// =========================================================
// CẤU HÌNH CỔNG THANH TOÁN VNPay & MoMo
// (Môi trường Sandbox/Thử nghiệm)
// =========================================================

// Lấy base URL tự động (tuỳ thuộc vào cài đặt localhost hoặc domain thật)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
// Tính toán thư mục gốc dựa trên đường dẫn hiện tại để linh hoạt
// Do file này nằm trong config/, nên URL gốc = dirname(dirname($_SERVER['SCRIPT_NAME']))
$script_name = $_SERVER['SCRIPT_NAME'];
$base_dir = str_replace('\\', '/', dirname(dirname($script_name)));
if ($base_dir === '/') $base_dir = '';
$base_url = $protocol . $domainName . $base_dir;

// Nếu chạy từ CLI (test script), đặt cứng localhost
if (php_sapi_name() === 'cli') {
    $base_url = 'http://localhost/Datsanbongda';
}

// 1. CẤU HÌNH VNPAY
define('VNPAY_TMN_CODE', 'IWZMNC34'); // Thay bằng TmnCode thật
define('VNPAY_HASH_SECRET', 'WABIAIFTZUSKRFJUACVKYVCYUVFYDCNZ'); // Thay bằng HashSecret thật
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'); // Sandbox URL
define('VNPAY_RETURN_URL', $base_url . '/pages/vnpay_return.php');
define('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction');

// 2. CẤU HÌNH MOMO
define('MOMO_PARTNER_CODE', 'MOMO');
define('MOMO_ACCESS_KEY', 'YOUR_ACCESS_KEY_HERE');
define('MOMO_SECRET_KEY', 'YOUR_SECRET_KEY_HERE');
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
define('MOMO_RETURN_URL', $base_url . '/pages/momo_return.php');
define('MOMO_NOTIFY_URL', $base_url . '/api/momo_ipn.php');
?>
