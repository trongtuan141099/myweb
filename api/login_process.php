


<?php
    header('Content-Type: application/json; charset=utf-8');
    require_once '../config/db.php';

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $response = [
        'success' => false,
        'message' => '',
        'user' => null
    ];

    if (empty($data['username']) || empty($data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên đăng nhập và mật khẩu']);
        exit;
    }

    $username = $data['username'];
    $password = $data['password'];

    // Lấy thông tin user kèm cột role mới
    $sql = "SELECT id, username, email, fullname, password, status, role FROM users WHERE (username = ? OR email = ?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || $user['status'] !== 'active' || $password !== $user['password']) {
        echo json_encode(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không chính xác']);
        exit;
    }

    // Đọc bảng Map quyền
    $configPermissions = require_once '../config/permission.php';
    $userRole = $user['role'] ?? 'viewer';
    $userPermissions = $configPermissions['roles_map'][$userRole] ?? ['document.view'];

    // Khởi tạo Session chuẩn toàn hệ thống
    session_start();
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['fullname'] = $user['fullname'];

    // Mảng user tập trung dùng cho Phân quyền
    $_SESSION['user'] = [
        'id'          => $user['id'],
        'username'    => $user['username'],
        'fullname'    => $user['fullname'],
        'role'        => $userRole,
        'permissions' => $userPermissions
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công',
        'user'    => $_SESSION['user']
    ]);
?>