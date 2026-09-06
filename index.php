<?php
session_start();
require "config/db.php";
require "core/check_permission.php"; // Nhúng Core phân quyền

$mainpage = isset($_GET['mainpage']) ? $_GET['mainpage'] : 'authentication';
$subpage = isset($_GET['subpage']) ? $_GET['subpage'] : 'login';

// Kiểm tra Đăng nhập tự động cho tất cả Module
if ($subpage !== 'login' && $mainpage !== 'authentication') {
    checkAuth();
    include "includes/sidebar.php";
    include "includes/header.php";
}

$module_path = "modules/{$mainpage}/{$subpage}.php";

if (file_exists($module_path)) {
    include $module_path;
} else {
    echo '<div class="alert alert-danger">Module không tìm thấy: ' . htmlspecialchars($module_path) . '</div>';
}

include "includes/footer.php";
?>