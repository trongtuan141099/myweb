<?php
/**
 * Kiểm tra xem người dùng đã đăng nhập hay chưa
 */

session_start();

header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'fullname' => $_SESSION['fullname']
        ]
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'message' => 'Chưa đăng nhập'
    ]);
}
?>
