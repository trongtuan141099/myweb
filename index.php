<?php
session_start();
require_once "config/db.php";
require_once "core/check_permission.php";

// Lấy tham số điều hướng mainpage và subpage
$mainpage = isset($_GET['mainpage']) ? trim($_GET['mainpage']) : 'dashboard';
$subpage  = isset($_GET['subpage'])  ? trim($_GET['subpage'])  : 'overview';

// Bảo vệ an toàn chống Directory Traversal
$mainpage = preg_replace('/[^a-zA-Z0-9_-]/', '', $mainpage);
$subpage  = preg_replace('/[^a-zA-Z0-9_-]/', '', $subpage);

// 1. Xử lý riêng biệt cho Trang Đăng Nhập (Authentication)
if ($mainpage === 'authentication' && $subpage === 'login') {
    // Nếu đã có session hợp lệ, tự động điều hướng vào Dashboard
    if (isset($_SESSION['user_id']) || isset($_SESSION['user'])) {
        header("Location: index.php?mainpage=dashboard&subpage=overview");
        exit;
    }
    include "modules/authentication/login.php";
    exit;
}

// 2. Kiểm tra bắt buộc đăng nhập cho toàn bộ hệ thống
checkAuth();

$module_path = "modules/{$mainpage}/{$subpage}.php";

// 3. Nạp Khung Giao Diện Chuẩn Sản Xuất (Master Layout)
include "includes/layout_head.php";
include "includes/sidebar.php";
include "includes/header.php";

if (file_exists($module_path)) {
    include $module_path;
} else {
    echo '<div class="app-card" style="padding: 40px; text-align: center; margin: 30px auto; max-width: 500px;">';
    echo '  <span class="material-icons" style="font-size: 48px; color: var(--dx-danger); margin-bottom: 12px;">warning</span>';
    echo '  <h3 style="font-weight: 700; margin-bottom: 8px;">Không tìm thấy trang</h3>';
    echo '  <p style="color: var(--dx-text-muted); font-size: 13px; margin-bottom: 20px;">Module không tồn tại hoặc đã thay đổi: <code>' . htmlspecialchars($module_path) . '</code></p>';
    echo '  <a href="index.php?mainpage=dashboard&subpage=overview" class="app-btn app-btn-primary" style="margin: 0 auto;">Quay về Tổng quan</a>';
    echo '</div>';
}

include "includes/footer.php";
?>