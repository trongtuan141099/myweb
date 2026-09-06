<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Bắt buộc Đăng nhập
 */
function checkAuth() {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
        header("Location: index.php?mainpage=authentication&subpage=login");
        exit;
    }
}

/**
 * Kiểm tra quyền Backend (PHP)
 */
function hasPermission($permissionCode) {
    if (!isset($_SESSION['user']['permissions']) || !is_array($_SESSION['user']['permissions'])) {
        return false;
    }
    return in_array($permissionCode, $_SESSION['user']['permissions']);
}

/**
 * Xuất biến phân quyền sang Frontend (JavaScript)
 */
function renderPermissionScript() {
    $permissions = $_SESSION['user']['permissions'] ?? [];
    $jsonPermissions = json_encode($permissions);
    echo "
    <script>
        window.CURRENT_USER_PERMISSIONS = {$jsonPermissions};
        function hasPermission(code) {
            return Array.isArray(window.CURRENT_USER_PERMISSIONS) && window.CURRENT_USER_PERMISSIONS.includes(code);
        }
    </script>
    ";
}
?>