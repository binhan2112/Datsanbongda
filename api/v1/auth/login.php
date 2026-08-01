<?php
header('Content-Type: application/json');
require_once '../../../config/db.php';
require_once 'jwt_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$login_id = isset($input['login_id']) ? trim($input['login_id']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($login_id) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập Email/Số điện thoại và Mật khẩu']);
    exit;
}

try {
    // Tìm user bằng email hoặc số điện thoại
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = :login_id OR phone = :login_id) LIMIT 1");
    $stmt->execute(['login_id' => $login_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['is_active'] == 0) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Tài khoản của bạn đã bị khóa']);
            exit;
        }

        // Tạo JWT Token
        $payload = [
            'user_id' => $user['id'],
            'role' => $user['role'],
            'email' => $user['email']
        ];
        $token = create_jwt($payload);

        // Xóa password_hash trước khi trả về
        unset($user['password_hash']);

        echo json_encode([
            'status' => 'success',
            'message' => 'Đăng nhập thành công',
            'data' => [
                'token' => $token,
                'user' => $user
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Email/Số điện thoại hoặc Mật khẩu không chính xác']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>
