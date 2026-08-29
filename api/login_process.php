<?php
/**
 * Xử lý đăng nhập - kiểm tra dữ liệu với database
 */

header('Content-Type: application/json; charset=utf-8');

// Nhập file config
require_once '../config/db.php';

// Nhận dữ liệu từ request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$response = [
    'success' => false,
    'message' => '',
    'user' => null
];

// Kiểm tra dữ liệu đầu vào
if (empty($data['username']) || empty($data['password'])) {
    $response['message'] = 'Vui lòng nhập tên đăng nhập và mật khẩu';
    echo json_encode($response);
    exit;
}

$username = $data['username'];
$password = $data['password'];

// Chuẩn bị câu lệnh SQL an toàn (sử dụng prepared statement)
$sql = "SELECT id, username, email, fullname, password, status FROM users 
        WHERE (username = ? OR email = ?) LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $response['message'] = 'Lỗi database: ' . $conn->error;
    echo json_encode($response);
    exit;
}

// Bind parameters
$stmt->bind_param("ss", $username, $username);

// Thực thi query
if (!$stmt->execute()) {
    $response['message'] = 'Lỗi thực thi: ' . $stmt->error;
    echo json_encode($response);
    exit;
}

$result = $stmt->get_result();

// Kiểm tra xem có tài khoản nào tìm thấy không
if ($result->num_rows === 0) {
    $response['message'] = 'Tên đăng nhập hoặc email không tồn tại';
    echo json_encode($response);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

// Kiểm tra trạng thái tài khoản
if ($user['status'] !== 'active') {
    $response['message'] = 'Tài khoản của bạn đã bị khóa hoặc không hoạt động';
    echo json_encode($response);
    $stmt->close();
    $conn->close();
    exit;
}

// Kiểm tra mật khẩu (so sánh trực tiếp - không dùng hash)
if ($password !== $user['password']) {
    $response['message'] = 'Mật khẩu không chính xác';
    echo json_encode($response);
    $stmt->close();
    $conn->close();
    exit;
}

// Đăng nhập thành công - cập nhật thời gian đăng nhập cuối cùng
$user_id = $user['id'];
$update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $user_id);
$update_stmt->execute();
$update_stmt->close();

// Ghi lịch sử đăng nhập
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$log_sql = "INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)";
$log_stmt = $conn->prepare($log_sql);
$log_stmt->bind_param("iss", $user_id, $ip, $userAgent);
$log_stmt->execute();
$log_stmt->close();

// Tạo session
session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['fullname'] = $user['fullname'];

// Trả về response thành công
$response['success'] = true;
$response['message'] = 'Đăng nhập thành công';
$response['user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'fullname' => $user['fullname']
];

echo json_encode($response);

$stmt->close();
$conn->close();
?>
