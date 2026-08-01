<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$GEMINI_API_KEY = "YOUR_GEMINI_API_KEY_HERE"; // API Key của bạn

$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim($input['message']) : '';
$userLat = isset($input['lat']) ? floatval($input['lat']) : null;
$userLng = isset($input['lng']) ? floatval($input['lng']) : null;

if (empty($message)) {
    echo json_encode(['reply' => 'Chan bố mày đi']);
    exit;
}

if (empty($GEMINI_API_KEY) || $GEMINI_API_KEY === "YOUR_GEMINI_API_KEY_HERE") {
    echo json_encode(['reply' => '⚠️ **Chưa cấu hình API Key!**<br>Để AI hoạt động thông minh, vui lòng mở file <b>api/ai_chat.php</b> và dán Google Gemini API Key của bạn vào biến <code>$GEMINI_API_KEY</code>.<br><a href="https://aistudio.google.com/app/apikey" target="_blank" style="color:var(--primary);text-decoration:underline;">Bấm vào đây để lấy Key miễn phí</a>']);
    exit;
}

// 1. Fetch all fields to provide context to Gemini
// Use ST_Distance_Sphere directly in SQL if user coordinates are provided for better performance
if ($userLat && $userLng) {
    $sql = "SELECT id, name, district, type, price_per_hour, rating, total_bookings, lat, lng, 
            has_parking, has_shower, has_canteen, has_rental,
            (ST_Distance_Sphere(point(lng, lat), point(:userLng, :userLat)) / 1000) AS distance_km
            FROM fields WHERE status = 'active' ORDER BY distance_km ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['userLng' => $userLng, 'userLat' => $userLat]);
} else {
    $sql = "SELECT id, name, district, type, price_per_hour, rating, total_bookings, lat, lng, 
            has_parking, has_shower, has_canteen, has_rental,
            NULL AS distance_km
            FROM fields WHERE status = 'active'";
    $stmt = $pdo->query($sql);
}
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

$context_data = [];
foreach ($fields as $f) {
    $distStr = ($f['distance_km'] !== null) ? round($f['distance_km'], 1) . " km" : "Không rõ (Do khách chưa cấp quyền vị trí)";
    
    $amenities = [];
    if($f['has_parking']) $amenities[] = "Đậu xe";
    if($f['has_shower']) $amenities[] = "Phòng tắm";
    if($f['has_canteen']) $amenities[] = "Căn tin";
    if($f['has_rental']) $amenities[] = "Thuê đồ";
    
    $context_data[] = [
        'id' => $f['id'],
        'Tên' => $f['name'],
        'Khu vực' => $f['district'],
        'Loại' => $f['type'],
        'Giá' => number_format($f['price_per_hour'], 0, ',', '.') . ' VNĐ/h',
        'Đánh giá' => $f['rating'] . ' sao (' . $f['total_bookings'] . ' lượt đặt)',
        'Tiện ích' => implode(', ', $amenities),
        'Khoảng cách tới người dùng' => $distStr
    ];
}

// Build Prompt
$system_instruction = "Bạn là trợ lý ảo thân thiện của nền tảng Đặt Sân Bóng Cần Thơ. Bạn giúp khách hàng tìm kiếm, tư vấn sân bóng và giải đáp các thắc mắc về bóng đá. Dưới đây là danh sách toàn bộ sân bóng trong hệ thống (Khoảng cách đã được tính tự động dựa trên vị trí khách hàng):\n\n" . json_encode($context_data, JSON_UNESCAPED_UNICODE) . "\n\nQUY TẮC BẮT BUỘC:\n1. Khi khách hàng cần tìm sân, đặt sân hoặc hỏi thông tin tiện ích, ƯU TIÊN tư vấn các sân có trong danh sách trên.\n2. Bạn có thể tự do trả lời các câu hỏi về kiến thức bóng đá chung (World Cup, Euro, Ngoại hạng Anh, luật chơi, cầu thủ, tin tức...) bằng kiến thức chung của bạn một cách ngắn gọn, sinh động và chính xác. Nếu không có thông tin thời gian thực, hãy trả lời khéo léo và gợi ý theo dõi kênh chính thống.\n3. LUÔN tạo link HTML cho tên sân bóng khi nhắc đến tên sân có trong hệ thống như sau: <a href='/Datsanbongda/pages/detail.php?id=[ID]' style='color:var(--primary);font-weight:bold;text-decoration:none;'>[TÊN SÂN]</a>.\n4. Dùng mã HTML hợp lệ (<br> để xuống dòng, <b> để in đậm). TUYỆT ĐỐI KHÔNG DÙNG MARKDOWN (không dùng dấu **).\n5. Trả lời ngắn gọn, không giải thích dài dòng.\n6. Nếu khách hỏi sân gần nhất, hãy liệt kê và sắp xếp các sân từ gần nhất đến xa nhất dựa vào 'Khoảng cách'.";

// Khởi tạo lịch sử chat nếu chưa có
if (!isset($_SESSION['ai_chat_history'])) {
    $_SESSION['ai_chat_history'] = [];
}

// Thêm tin nhắn của user vào lịch sử
$_SESSION['ai_chat_history'][] = [
    "role" => "user",
    "parts" => [
        ["text" => $message]
    ]
];

// Giới hạn lịch sử lưu tối đa 10 tin nhắn gần nhất (5 lượt trao đổi) để tránh quá tải token
if (count($_SESSION['ai_chat_history']) > 10) {
    $_SESSION['ai_chat_history'] = array_slice($_SESSION['ai_chat_history'], -10);
}

$data = [
    "system_instruction" => [
        "parts" => [
            ["text" => $system_instruction]
        ]
    ],
    "contents" => $_SESSION['ai_chat_history'],
    "generationConfig" => [
        "temperature" => 0.2,
        "maxOutputTokens" => 2000
    ]
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $GEMINI_API_KEY;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bỏ qua xác thực SSL trên localhost
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode == 200) {
    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $reply = $result['candidates'][0]['content']['parts'][0]['text'];
        
        // Lưu phản hồi của AI vào lịch sử
        $_SESSION['ai_chat_history'][] = [
            "role" => "model",
            "parts" => [
                ["text" => $reply]
            ]
        ];

        // Xóa dấu markdown markdown ```html nếu có
        $formatted_reply = str_replace('```html', '', $reply);
        $formatted_reply = str_replace('```', '', $formatted_reply);
        // Chuyển đổi **text** thành <b>text</b> đề phòng AI vẫn dùng markdown
        $formatted_reply = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $formatted_reply);
        // Đảm bảo xuống dòng được render thành <br>
        $formatted_reply = nl2br(trim($formatted_reply));
        echo json_encode(['reply' => $formatted_reply]);
    } else {
        echo json_encode(['reply' => 'Lỗi xử lý phản hồi từ AI.']);
    }
} else {
    $error = json_decode($response, true);
    $errMsg = isset($error['error']['message']) ? $error['error']['message'] : 'Lỗi kết nối API';
    
    // Nếu là lỗi quá tải (429, 503) thì dùng FALLBACK để không bể demo lúc báo cáo
    if ($httpcode == 503 || $httpcode == 429) {
        $fallbackMsg = "Dạ, hiện tại em (Trợ lý AI) đang tiếp nhận rất nhiều yêu cầu. Tuy nhiên, anh/chị có thể bấm vào mục <b><a href='/Datsanbongda/pages/events.php' style='color:var(--primary);'>Sự Kiện</a></b> hoặc tìm sân trực tiếp tại trang chủ để tiến hành đặt sân nhanh nhất nhé!";
        echo json_encode(['reply' => $fallbackMsg]);
    } else {
        // Nếu là lỗi cấu hình/lỗi khác (ví dụ: 400 API Key sai), thì hiển thị lỗi thật để dễ sửa
        echo json_encode(['reply' => "⚠️ **Lỗi kết nối Google API (HTTP $httpcode):** " . $errMsg]);
    }
}
