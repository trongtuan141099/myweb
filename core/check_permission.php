<?php
// core/check_permission.php
// Middleware xác thực & phân quyền tập trung - DX Plastic Group

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Tự động đồng bộ vai trò và danh sách quyền hạn người dùng từ DB & file cấu hình
 */
function syncUserAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Nếu chưa đăng nhập, không thực hiện đồng bộ
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['user']['id'])) {
        return;
    }

    $userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0);
    if (!$userId) {
        return;
    }

    // Kết nối CSDL để kiểm tra vai trò và trạng thái tài khoản mới nhất
    global $conn;
    if (!$conn || !($conn instanceof mysqli)) {
        $dbConfig = __DIR__ . '/../config/db.php';
        if (file_exists($dbConfig)) {
            require_once $dbConfig;
        }
    }

    $currentRole = null;
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
        $stmt = $conn->prepare("SELECT id, username, fullname, email, role, status FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user) {
                // Nếu tài khoản bị khóa/hủy kích hoạt -> Hủy phiên ngay lập tức
                if ($user['status'] !== 'active') {
                    unset($_SESSION['user'], $_SESSION['user_id'], $_SESSION['username']);
                    session_destroy();
                    return;
                }
                // Đồng bộ thông tin người dùng từ DB
                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $currentRole          = $user['role'] ?? 'viewer';
            }
        }
    }

    // Nếu không lấy được từ DB, sử dụng role đang lưu trong session
    if (!$currentRole) {
        $currentRole = $_SESSION['user']['role'] ?? 'viewer';
    }

    // Nạp lại ma trận quyền mới nhất từ config/permission.php
    $configFile = __DIR__ . '/../config/permission.php';
    $rolePerms = ['dashboard.view', 'document.view'];
    if (file_exists($configFile)) {
        $permConfig = require $configFile;
        if (isset($permConfig['roles_map'][$currentRole])) {
            $rolePerms = $permConfig['roles_map'][$currentRole];
        }
    }

    // Cập nhật cấu trúc $_SESSION['user']
    $_SESSION['user'] = [
        'id'          => $userId,
        'username'    => $_SESSION['username'] ?? 'User',
        'fullname'    => $_SESSION['fullname'] ?? 'User',
        'role'        => $currentRole,
        'permissions' => $rolePerms
    ];
}

/**
 * Bắt buộc Đăng nhập cho các trang Web (Routes)
 */
function checkAuth() {
    syncUserAuth();
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
        header("Location: index.php?mainpage=authentication&subpage=login");
        exit;
    }
}

/**
 * Kiểm tra xem người dùng hiện tại có quyền hay không (PHP Backend)
 * $permissionCodes: một chuỗi mã quyền hoặc mảng các mã quyền (nếu thỏa bất kỳ mã nào -> true)
 */
function hasPermission($permissionCodes) {
    if (!isset($_SESSION['user'])) {
        syncUserAuth();
    }

    // Nếu không có thông tin user, không cấp quyền
    if (!isset($_SESSION['user']['role'])) {
        return false;
    }

    $role = $_SESSION['user']['role'];

    // Quản trị viên (Admin) luôn có toàn quyền tuyệt đối
    if ($role === 'admin') {
        return true;
    }

    $userPerms = $_SESSION['user']['permissions'] ?? [];
    if (!is_array($userPerms)) {
        return false;
    }

    $codes = is_array($permissionCodes) ? $permissionCodes : [$permissionCodes];
    foreach ($codes as $code) {
        if (in_array($code, $userPerms, true)) {
            return true;
        }
    }

    return false;
}

/**
 * Chặn và kiểm tra quyền truy cập Web Route (Dành cho trang giao diện)
 */
function requirePermission($permissionCodes) {
    checkAuth();
    if (!hasPermission($permissionCodes)) {
        http_response_code(403);
        echo '
        <div class="app-card" style="padding: 48px 24px; text-align: center; margin: 40px auto; max-width: 540px; border-top: 4px solid var(--dx-danger, #ef4444);">
            <div style="width: 64px; height: 64px; background: rgba(239, 68, 68, 0.12); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <span class="material-icons" style="font-size: 36px; color: var(--dx-danger, #ef4444);">gpp_bad</span>
            </div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--dx-text-main); margin-bottom: 8px;">403 - KHÔNG CÓ QUYỀN TRUY CẬP</h2>
            <p style="color: var(--dx-text-muted); font-size: 13.5px; line-height: 1.5; margin-bottom: 24px;">
                Tài khoản của bạn với vai trò <strong>[' . htmlspecialchars($_SESSION['user']['role'] ?? 'viewer') . ']</strong> không được phân quyền để xem hoặc thao tác trên trang này.<br>
                Vui lòng liên hệ Quản trị viên hệ thống để được cấp quyền bổ sung.
            </p>
            <div>
                <a href="index.php?mainpage=dashboard&subpage=overview" class="app-btn app-btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                    <span class="material-icons">arrow_back</span> Quay Về Trang Chủ
                </a>
            </div>
        </div>';
        exit;
    }
}

/**
 * Chặn và kiểm tra quyền gọi API Endpoints (Dành cho API JSON)
 */
function requireApiPermission($permissionCodes) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Luôn đồng bộ trạng thái mới nhất từ DB & config
    syncUserAuth();

    // 1. Kiểm tra xác thực (Authentication)
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'code'    => 401,
            'message' => 'Phiên làm việc đã hết hạn hoặc chưa đăng nhập. Vui lòng đăng nhập lại!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Kiểm tra phân quyền (Authorization)
    if (!hasPermission($permissionCodes)) {
        http_response_code(403);
        $req = is_array($permissionCodes) ? implode(', ', $permissionCodes) : $permissionCodes;
        echo json_encode([
            'success'       => false,
            'code'          => 403,
            'message'       => "Bạn không có quyền thực hiện thao tác này! (Vai trò hiện tại: [{$_SESSION['user']['role']}])",
            'required_perm' => $req,
            'current_role'  => $_SESSION['user']['role'] ?? 'viewer'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Xuất biến phân quyền sang Frontend (JavaScript)
 */
function renderPermissionScript() {
    syncUserAuth();
    $permissions = $_SESSION['user']['permissions'] ?? [];
    $role = $_SESSION['user']['role'] ?? 'viewer';
    $jsonPermissions = json_encode($permissions, JSON_UNESCAPED_UNICODE);
    $jsonRole = json_encode($role, JSON_UNESCAPED_UNICODE);
    echo "
    <script>
        window.CURRENT_USER_ROLE = {$jsonRole};
        window.CURRENT_USER_PERMISSIONS = {$jsonPermissions};
        function hasPermission(codes) {
            if (window.CURRENT_USER_ROLE === 'admin') return true;
            if (!Array.isArray(window.CURRENT_USER_PERMISSIONS)) return false;
            if (Array.isArray(codes)) {
                return codes.some(c => window.CURRENT_USER_PERMISSIONS.includes(c));
            }
            return window.CURRENT_USER_PERMISSIONS.includes(codes);
        }
    </script>
    ";
}