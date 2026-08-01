<?php
header('Content-Type: application/json');
require_once '../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID sân bóng không hợp lệ']);
    exit;
}

try {
    // 1. Fetch Field Info
    $stmt = $pdo->prepare("SELECT f.*, u.full_name as owner_name, u.phone as owner_phone, u.avatar as owner_avatar 
                           FROM fields f 
                           JOIN users u ON f.owner_id = u.id 
                           WHERE f.id = :id AND f.status = 'active'");
    $stmt->execute(['id' => $id]);
    $field = $stmt->fetch();

    if (!$field) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy sân bóng']);
        exit;
    }

    // 2. Fetch Images
    $stmtImg = $pdo->prepare("SELECT image_path, caption FROM field_images WHERE field_id = :id ORDER BY sort_order ASC");
    $stmtImg->execute(['id' => $id]);
    $images = $stmtImg->fetchAll();

    // 3. Fetch Top Reviews
    $stmtRev = $pdo->prepare("SELECT r.rating, r.comment, r.created_at, u.full_name, u.avatar 
                              FROM reviews r 
                              JOIN users u ON r.user_id = u.id 
                              WHERE r.field_id = :id 
                              ORDER BY r.created_at DESC LIMIT 5");
    $stmtRev->execute(['id' => $id]);
    $reviews = $stmtRev->fetchAll();

    $field['images'] = $images;
    $field['recent_reviews'] = $reviews;

    echo json_encode([
        'status' => 'success',
        'data' => $field
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>
