<?php
/**
 * Kiểm tra xem người dùng đã đăng nhập hay chưa
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);

if ($userId) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => (int)$userId,
            'username' => $_SESSION['username'] ?? ($_SESSION['user']['username'] ?? 'User'),
            'email' => $_SESSION['email'] ?? ($_SESSION['user']['email'] ?? ''),
            'fullname' => $_SESSION['fullname'] ?? ($_SESSION['user']['fullname'] ?? 'User')
        ]
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'message' => 'Chưa đăng nhập'
    ]);
}
