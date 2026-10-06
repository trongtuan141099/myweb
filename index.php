<?php
session_start();
require_once "config/db.php";
require_once "core/check_permission.php";
require_once "core/i18n.php";

// Lấy tham số điều hướng mainpage và subpage
$mainpage = isset($_GET['mainpage']) ? trim($_GET['mainpage']) : 'dashboard';
$subpage  = isset($_GET['subpage'])  ? trim($_GET['subpage'])  : 'overview';

// Bảo vệ an toàn chống Directory Traversal
$mainpage = preg_replace('/[^a-zA-Z0-9_-]/', '', $mainpage);
$subpage  = preg_replace('/[^a-zA-Z0-9_-]/', '', $subpage);

// Chuẩn hóa điều hướng cho phân hệ Quản trị ngôn ngữ
if ($mainpage === 'system' && $subpage === 'languages') {
    $subpage = 'language_settings';
}

// Chuẩn hóa điều hướng cho phân hệ Quản lý Đơn hàng & chuyển hướng các link cũ
if ($mainpage === 'orders' && ($subpage === 'overview' || $subpage === 'index' || empty($subpage))) {
    $subpage = 'don_b';
}
if ($mainpage === 'production' && $subpage === 'don_b') {
    header("Location: index.php?mainpage=orders&subpage=don_b");
    exit;
}
if ($mainpage === 'don_b') {
    header("Location: index.php?mainpage=orders&subpage=don_b");
    exit;
}

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

// Danh sách các tiện ích công khai (cho phép truy cập nhanh không cần đăng nhập)
$public_routes = [
    'materials' => ['viscoscity'],
    'utilities' => ['color_mixer', 'color_mixer_summary']
];
$is_public_route = isset($public_routes[$mainpage]) && in_array($subpage, $public_routes[$mainpage], true);

// 2. Kiểm tra bắt buộc đăng nhập và đồng bộ vai trò/quyền mới nhất
if ($is_public_route) {
    // Nếu là tiện ích công khai và đã có phiên đăng nhập, đồng bộ quyền người dùng
    if (isset($_SESSION['user_id']) || isset($_SESSION['user'])) {
        syncUserAuth();
    }
} else {
    // Với các trang nội bộ khác, bắt buộc phải đăng nhập hợp lệ
    checkAuth();
}

// 3. Định nghĩa ma trận quyền Route toàn hệ thống
$route_permissions = [
    'dashboard' => [
        'overview' => 'dashboard.view',
        'banners'  => 'dashboard.view'
    ],
    'production' => [
        'production_planing' => 'production.plan',
        'production_data'    => 'production.data',
        'extrusion_summary'  => 'production.data'
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
        'roles'             => 'role.manage',
        'language_settings' => 'role.manage',
        'languages'         => 'role.manage'
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
    ],
    'warehouse' => [
        'issue_request'    => 'warehouse.view',
        'approval'         => 'warehouse.view',
        'materials'        => 'warehouse.view',
        'reorder_tracking' => 'warehouse.view',
        'dashboard'        => 'warehouse.view',
        'settings'         => 'warehouse.settings'
    ],
    'quality' => [
        'yield_tracking'   => 'quality.view'
    ],
    'orders' => [
        'don_b'    => 'orders.view',
        'index'    => 'orders.view',
        'overview' => 'orders.view'
    ]
];

$module_path = "modules/{$mainpage}/{$subpage}.php";

// 4. Nạp Khung Giao Diện Chuẩn Sản Xuất (Master Layout)
include "includes/layout_head.php";
include "includes/sidebar.php";
include "includes/header.php";

// 5. Kiểm tra phân quyền truy cập Web Route
$required_perm = $route_permissions[$mainpage][$subpage] ?? null;

if (!$is_public_route && $required_perm && !hasPermission($required_perm)) {
    // Chặn hiển thị và thông báo 403 Forbidden
    $userRole = $_SESSION['user']['role'] ?? 'viewer';
    echo '
    <div class="app-page-wrapper">
        <div class="app-card" style="padding: 48px 24px; text-align: center; margin: 40px auto; max-width: 560px; border-top: 4px solid var(--dx-danger, #ef4444);">
            <div style="width: 64px; height: 64px; background: rgba(239, 68, 68, 0.12); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <span class="material-icons" style="font-size: 36px; color: var(--dx-danger, #ef4444);">gpp_bad</span>
            </div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--dx-text-main); margin-bottom: 8px;" data-i18n="app.forbidden_title">' . __('app.forbidden_title', '403 - KHÔNG CÓ QUYỀN TRUY CẬP') . '</h2>
            <p style="color: var(--dx-text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 24px;" data-i18n="app.forbidden_desc">
                ' . __('app.forbidden_desc', 'Tài khoản của bạn chưa được cấp quyền truy cập chức năng này. Vui lòng liên hệ Quản trị viên để được cấp thêm quyền sử dụng.') . '
            </p>
            <div>
                <a href="index.php?mainpage=dashboard&subpage=overview" class="app-btn app-btn-primary" style="display: inline-flex; align-items: center; gap: 8px; margin: 0 auto;" data-i18n="app.back_to_dashboard">
                    <span class="material-icons">arrow_back</span> ' . __('app.back_to_dashboard', 'Quay Về Tổng Quan') . '
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
    echo '    <h3 style="font-weight: 700; margin-bottom: 8px;" data-i18n="app.not_found_title">' . __('app.not_found_title', '404 - Không tìm thấy trang') . '</h3>';
    echo '    <p style="color: var(--dx-text-muted); font-size: 13px; margin-bottom: 20px;" data-i18n="app.not_found_desc">' . __('app.not_found_desc', 'Module không tồn tại hoặc đã thay đổi đường dẫn.') . '</p>';
    echo '    <a href="index.php?mainpage=dashboard&subpage=overview" class="app-btn app-btn-primary" style="margin: 0 auto;" data-i18n="app.back_to_dashboard">' . __('app.back_to_dashboard', 'Quay Về Tổng Quan') . '</a>';
    echo '  </div>';
    echo '</div>';
}

include "includes/footer.php";
?>