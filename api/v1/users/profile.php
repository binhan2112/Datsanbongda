<?php
header('Content-Type: application/json');
require_once '../../../config/db.php';
require_once '../middleware.php';

$user = authenticate();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, avatar, address, points, created_at FROM users WHERE id = :id");
        $stmt->execute(['id' => $user['user_id']]);
        $profile = $stmt->fetch();

        echo json_encode(['status' => 'success', 'data' => $profile]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cập nhật thông tin profile
    $input = json_decode(file_get_contents('php://input'), true);
    $full_name = isset($input['full_name']) ? trim($input['full_name']) : null;
    $address = isset($input['address']) ? trim($input['address']) : null;

    if (empty($full_name)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Họ tên không được để trống']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = :full_name, address = :address WHERE id = :id");
        $stmt->execute([
            'full_name' => $full_name,
            'address' => $address,
            'id' => $user['user_id']
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Cập nhật thành công']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
}
?>
