<?php
require "config/db.php";

$mainpage = isset($_GET['mainpage']) ? $_GET['mainpage'] : 'authentication';
$subpage = isset($_GET['subpage']) ? $_GET['subpage'] : 'login';

//  Chỉ nhúng Sidebar nếu KHÔNG PHẢI là trang login (hoặc không nằm trong luồng authentication)
if ($subpage !== 'login' && $mainpage !== 'authentication') {
    include "includes/sidebar.php";
    include "includes/header.php";
}

//  Nhúng Subpage
$module_path = "modules/{$mainpage}/{$subpage}.php";

if (file_exists($module_path)) {
    include $module_path;
} else {
    echo '<div class="alert alert-danger">Module không tìm thấy: ' . htmlspecialchars($module_path) . '</div>';
}

//  Nhúng Footer
include "includes/footer.php";
?>