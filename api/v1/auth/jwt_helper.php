<?php
define('JWT_SECRET', 'your_super_secret_key_for_mobile_api_2026');

function create_jwt($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    $payload['iat'] = time();
    // Token hết hạn sau 30 ngày
    $payload['exp'] = time() + (86400 * 30); 
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function verify_jwt($token) {
    $tokenParts = explode('.', $token);
    if (count($tokenParts) != 3) {
        return false;
    }
    $header = $tokenParts[0];
    $payload = $tokenParts[1];
    $signature_provided = $tokenParts[2];

    // Check signature
    $signature = hash_hmac('sha256', $header . "." . $payload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if ($base64UrlSignature !== $signature_provided) {
        return false;
    }
    
    // Check expiration
    $payload_decoded = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    if (isset($payload_decoded['exp']) && $payload_decoded['exp'] < time()) {
        return false;
    }
    
    return $payload_decoded;
}
?>
