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
$full_name = isset($input['full_name']) ? trim($input['full_name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin (Họ tên, Email, SĐT, Mật khẩu)']);
    exit;
}

try {
    // Kiểm tra trùng email hoặc sđt
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR phone = :phone LIMIT 1");
    $stmt->execute(['email' => $email, 'phone' => $phone]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Email hoặc số điện thoại đã tồn tại trong hệ thống']);
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (:full_name, :email, :phone, :password_hash, 'customer')");
    $stmt->execute([
        'full_name' => $full_name,
        'email' => $email,
        'phone' => $phone,
        'password_hash' => $password_hash
    ]);

    $new_user_id = $pdo->lastInsertId();

    // Lấy thông tin user vừa tạo
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, avatar, points FROM users WHERE id = :id");
    $stmt->execute(['id' => $new_user_id]);
    $user = $stmt->fetch();

    // Tạo JWT Token tự động đăng nhập sau khi đăng ký
    $payload = [
        'user_id' => $user['id'],
        'role' => $user['role'],
        'email' => $user['email']
    ];
    $token = create_jwt($payload);

    echo json_encode([
        'status' => 'success',
        'message' => 'Đăng ký tài khoản thành công',
        'data' => [
            'token' => $token,
            'user' => $user
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>
