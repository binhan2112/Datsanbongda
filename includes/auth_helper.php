<?php
// Bắt đầu session nếu chưa được khởi tạo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra xem người dùng đã đăng nhập chưa
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Lấy thông tin người dùng đang đăng nhập
 * @return array|null
 */
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'full_name' => $_SESSION['user_full_name'],
        'email'     => $_SESSION['user_email'],
        'role'      => $_SESSION['user_role']
    ];
}

/**
 * Yêu cầu đăng nhập mới được truy cập trang.
 * Nếu không đăng nhập, chuyển hướng về trang login.
 * Nếu truyền vào role và role không khớp, chuyển hướng về trang chủ hoặc báo lỗi.
 * 
 * @param string|null $required_role 'customer', 'owner', 'admin'
 * @return void
 */
function require_login($required_role = null) {
    if (!is_logged_in()) {
        header("Location: ../auth/login.php");
        exit;
    }
    
    if ($required_role !== null && $_SESSION['user_role'] !== $required_role) {
        // Nếu là admin thì được quyền xem tất cả
        if ($_SESSION['user_role'] === 'admin') {
            return;
        }
        
        header("Location: ../index.php?error=unauthorized");
        exit;
    }
}

/**
 * Chuyển hướng người dùng về trang chủ nếu họ đã đăng nhập
 * Thường dùng ở trang đăng nhập/đăng ký để tránh đăng nhập lại
 * 
 * @return void
 */
function redirect_if_logged_in() {
    if (is_logged_in()) {
        if ($_SESSION['user_role'] === 'admin') {
            header("Location: ../admin/index.php");
        } elseif ($_SESSION['user_role'] === 'owner') {
            header("Location: ../owner/index.php");
        } else {
            header("Location: ../index.php");
        }
        exit;
    }
}
?>
