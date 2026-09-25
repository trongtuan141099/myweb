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

// 2. Kiểm tra bắt buộc đăng nhập và đồng bộ vai trò/quyền mới nhất
checkAuth();

// 3. Định nghĩa ma trận quyền Route toàn hệ thống
$route_permissions = [
    'dashboard' => [
        'overview' => 'dashboard.view'
    ],
    'production' => [
        'production_planing' => 'production.plan',
        'production_data'    => 'production.data'
    ],
    'hrm' => [
        'list'                => 'hrm.view',
        'add_employee'        => 'hrm.manage',
        'inventory_org_chart' => 'hrm.view',
        'leave_management'    => 'hrm.view'
    ],
    'document' => [
        'viewer' => 'document.view'
    ],
    'iot' => [
        'device_status'  => 'device.view',
        'device_history' => 'device.history'
    ],
    'materials' => [
        'viscoscity' => 'materials.view'
    ],
    'five_s' => [
        'overview'     => 'five_s.view',
        'list'         => 'five_s.view',
        'proposals'    => 'five_s.proposals',
        'mobile_audit' => 'five_s.audit',
        'settings'     => 'five_s.settings'
    ],
    'utilities' => [
        'color_mixer'         => 'mixer.view',
        'color_mixer_summary' => 'mixer.view'
    ],
    'system' => [
        'roles' => 'role.manage'
    ],
    'sample' => [
        'format' => 'dashboard.view'
    ],
    'overtime' => [
        'dashboard'      => 'overtime.view',
        'import'         => 'overtime.import',
        'reconciliation' => 'overtime.reconcile',
        'explanations'   => 'overtime.explain',
        'yearly_control' => 'overtime.yearly',
        'records'        => 'overtime.view',
        'export'         => 'overtime.export'
    ]
];

$module_path = "modules/{$mainpage}/{$subpage}.php";

// 4. Nạp Khung Giao Diện Chuẩn Sản Xuất (Master Layout)
include "includes/layout_head.php";
include "includes/sidebar.php";
include "includes/header.php";

// 5. Kiểm tra phân quyền truy cập Web Route
$required_perm = $route_permissions[$mainpage][$subpage] ?? null;

if ($required_perm && !hasPermission($required_perm)) {
    // Chặn hiển thị và thông báo 403 Forbidden
    $userRole = $_SESSION['user']['role'] ?? 'viewer';
    echo '
    <div class="app-page-wrapper">
        <div class="app-card" style="padding: 48px 24px; text-align: center; margin: 40px auto; max-width: 560px; border-top: 4px solid var(--dx-danger, #ef4444);">
            <div style="width: 64px; height: 64px; background: rgba(239, 68, 68, 0.12); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <span class="material-icons" style="font-size: 36px; color: var(--dx-danger, #ef4444);">gpp_bad</span>
            </div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--dx-text-main); margin-bottom: 8px;">403 - KHÔNG CÓ QUYỀN TRUY CẬP</h2>
            <p style="color: var(--dx-text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 24px;">
                Tài khoản của bạn đang có vai trò <strong>[' . htmlspecialchars(strtoupper($userRole)) . ']</strong>.<br>
                Chức năng này yêu cầu quyền: <code style="color: var(--dx-danger); font-weight: 600;">' . htmlspecialchars($required_perm) . '</code>.<br>
                Vui lòng liên hệ Quản trị viên để được cấp thêm quyền sử dụng.
            </p>
            <div>
                <a href="index.php?mainpage=dashboard&subpage=overview" class="app-btn app-btn-primary" style="display: inline-flex; align-items: center; gap: 8px; margin: 0 auto;">
                    <span class="material-icons">arrow_back</span> Quay Về Tổng Quan
                </a>
            </div>
        </div>
    </div>';
} elseif (file_exists($module_path)) {
    include $module_path;
} else {
    echo '<div class="app-page-wrapper">';
    echo '  <div class="app-card" style="padding: 40px; text-align: center; margin: 30px auto; max-width: 500px;">';
    echo '    <span class="material-icons" style="font-size: 48px; color: var(--dx-danger); margin-bottom: 12px;">warning</span>';
    echo '    <h3 style="font-weight: 700; margin-bottom: 8px;">Không tìm thấy trang</h3>';
    echo '    <p style="color: var(--dx-text-muted); font-size: 13px; margin-bottom: 20px;">Module không tồn tại hoặc đã thay đổi: <code>' . htmlspecialchars($module_path) . '</code></p>';
    echo '    <a href="index.php?mainpage=dashboard&subpage=overview" class="app-btn app-btn-primary" style="margin: 0 auto;">Quay về Tổng quan</a>';
    echo '  </div>';
    echo '</div>';
}

include "includes/footer.php";
?>