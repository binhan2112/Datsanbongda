<?php
require_once __DIR__ . '/auth/jwt_helper.php';

function authenticate() {
    $headers = null;
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    } else {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
            }
        }
    }

    $authHeader = null;
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
    }

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Missing Authorization Header']);
        exit;
    }

    $arr = explode(" ", $authHeader);
    if (count($arr) != 2 || $arr[0] !== 'Bearer') {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid Authorization Format (Expected: Bearer <token>)']);
        exit;
    }

    $token = $arr[1];
    $payload = verify_jwt($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or Expired Token']);
        exit;
    }

    return $payload; // Returns decoded payload like ['user_id' => 1]
}
?>
