<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xử lý chuyển đổi ngôn ngữ qua GET parameter ?lang=vi hoặc ?lang=en
if (isset($_GET['lang']) && in_array($_GET['lang'], ['vi', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$current_lang = $_SESSION['lang'] ?? 'vi';

$lang_file = __DIR__ . '/../config/lang/' . $current_lang . '.php';
if (file_exists($lang_file)) {
    $translations = require $lang_file;
} else {
    $translations = require __DIR__ . '/../config/lang/vi.php';
}

/**
 * Hàm dịch chuỗi giao diện
 * @param string $key
 * @return string
 */
function __trans($key) {
    global $translations;
    return $translations[$key] ?? $key;
}
?>
