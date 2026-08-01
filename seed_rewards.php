<?php
require_once 'config/db.php';

$rewards = [
    [
        'name' => 'Bóng đá tiêu chuẩn FIFA',
        'description' => 'Quả bóng chuẩn thi đấu, chất liệu da PU cao cấp, độ nảy tốt.',
        'points_required' => 500,
        'quantity' => 20,
        'image_url' => 'assets/images/rewards/reward_football_1785244428644.png'
    ],
    [
        'name' => 'Áo bib tập luyện',
        'description' => 'Áo bib màu xanh neon nổi bật, mỏng nhẹ thoáng mát, phù hợp chia đội.',
        'points_required' => 100,
        'quantity' => 50,
        'image_url' => 'assets/images/rewards/reward_bib_1785244440434.png'
    ],
    [
        'name' => 'Nước khoáng thể thao',
        'description' => 'Chai nước khoáng thể thao giải khát nhanh chóng, bổ sung ion và khoáng chất.',
        'points_required' => 20,
        'quantity' => 200,
        'image_url' => 'assets/images/rewards/reward_drink_1785244451906.png'
    ],
    [
        'name' => 'Giày đá bóng đinh TF',
        'description' => 'Giày chuyên dụng cho sân cỏ nhân tạo, bám sân cực tốt, màu sắc năng động.',
        'points_required' => 1500,
        'quantity' => 5,
        'image_url' => 'assets/images/rewards/reward_shoes_1785244461711.png'
    ],
    [
        'name' => 'Găng tay thủ môn',
        'description' => 'Găng tay có mút xốp dày bảo vệ ngón tay, bám bóng dính, thiết kế mạnh mẽ.',
        'points_required' => 800,
        'quantity' => 10,
        'image_url' => 'assets/images/rewards/reward_gloves_1785244473673.png'
    ],
    [
        'name' => 'Voucher giảm giá 50K',
        'description' => 'Giảm trực tiếp 50.000đ cho lần đặt sân tiếp theo tại hệ thống.',
        'points_required' => 150,
        'quantity' => 100,
        'image_url' => 'assets/images/rewards/reward_voucher_1785244485094.png'
    ],
    [
        'name' => 'Tất bóng đá chống trượt',
        'description' => 'Tất chân chuyên dụng thể thao, đệm silicon dưới lòng bàn chân chống trượt.',
        'points_required' => 80,
        'quantity' => 40,
        'image_url' => 'assets/images/rewards/reward_socks_1785244495410.png'
    ],
    [
        'name' => 'Balo thể thao đa năng',
        'description' => 'Balo rộng rãi đựng vừa giày và quần áo thể thao, chất liệu kháng nước.',
        'points_required' => 1200,
        'quantity' => 8,
        'image_url' => 'assets/images/rewards/reward_backpack_1785244506431.png'
    ],
    [
        'name' => 'Băng rôn đội trưởng (Captain)',
        'description' => 'Băng tay đội trưởng màu vàng nổi bật, co giãn tốt.',
        'points_required' => 50,
        'quantity' => 30,
        'image_url' => 'assets/images/rewards/reward_armband_1785244517092.png'
    ],
    [
        'name' => 'Bình xịt lạnh giảm đau',
        'description' => 'Bình xịt lạnh hỗ trợ xử lý chấn thương thể thao tức thì trên sân.',
        'points_required' => 300,
        'quantity' => 15,
        'image_url' => 'assets/images/rewards/reward_spray_1785244526646.png'
    ]
];

try {
    $stmt = $pdo->prepare("
        INSERT INTO rewards (owner_id, name, description, points_required, quantity, image_url, status)
        VALUES (NULL, ?, ?, ?, ?, ?, 'active')
    ");

    foreach ($rewards as $r) {
        $stmt->execute([
            $r['name'],
            $r['description'],
            $r['points_required'],
            $r['quantity'],
            $r['image_url']
        ]);
    }
    echo "Đã chèn 10 phần quà thành công.\n";
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
?>
