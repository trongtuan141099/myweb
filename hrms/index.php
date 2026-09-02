<?php
require "config/db.php";
include "includes/header.php";
include "includes/sidebar.php";

$mainpage = isset($_GET['mainpage']) ? $_GET['mainpage'] : 'dashboard';
$subpage = isset($_GET['subpage']) ? $_GET['subpage'] : 'overview';

$module_path = "modules/{$mainpage}/{$subpage}.php";

if (file_exists($module_path)) {
    include $module_path;
} else {
    echo '<div class="alert alert-danger">Module không tìm thấy: ' . htmlspecialchars($module_path) . '</div>';
}

include "includes/footer.php";
?>