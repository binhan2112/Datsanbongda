<?php
require_once '../config/db.php';
require_once '../includes/auth_helper.php';

require_login('admin');

$page_title = 'Hồ sơ cá nhân';
$base_url = '../';
include '../includes/dashboard_header.php';

include '../includes/dashboard_profile.php';

include '../includes/dashboard_footer.php';
?>
