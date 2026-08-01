<?php
header('Content-Type: application/json');
require_once '../../../config/db.php';
require_once '../middleware.php';

$user = authenticate();
$user_id = $user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Lấy danh sách yêu thích
    try {
        $sql = "SELECT f.id, f.name, f.district, f.type, f.price_per_hour, f.rating, f.cover_image 
                FROM favorites fav
                JOIN fields f ON fav.field_id = f.id
                WHERE fav.user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ']);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Thêm/Xóa yêu thích
    $input = json_decode(file_get_contents('php://input'), true);
    $field_id = isset($input['field_id']) ? intval($input['field_id']) : 0;
    $action = isset($input['action']) ? trim($input['action']) : 'add'; // add or remove

    if ($field_id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID sân không hợp lệ']);
        exit;
    }

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, field_id) VALUES (:user_id, :field_id)");
            $stmt->execute(['user_id' => $user_id, 'field_id' => $field_id]);
            $msg = 'Đã thêm vào danh sách yêu thích';
        } else {
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = :user_id AND field_id = :field_id");
            $stmt->execute(['user_id' => $user_id, 'field_id' => $field_id]);
            $msg = 'Đã xóa khỏi danh sách yêu thích';
        }

        echo json_encode(['status' => 'success', 'message' => $msg]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
}
?>
