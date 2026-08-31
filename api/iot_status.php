<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device_id = isset($_POST['device_id']) ? $_POST['device_id'] : '';
    $status    = isset($_POST['status']) ? $_POST['status'] : '';
    $ip        = isset($_POST['ip']) ? $_POST['ip'] : '';

    if (!empty($device_id) && !empty($status)) {
        // 1. Cập nhật trạng thái hiện tại
        $stmt1 = $conn->prepare("INSERT INTO device_status (device_id, ip_address, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE ip_address=?, status=?");
        $stmt1->bind_param("sssss", $device_id, $ip, $status, $ip, $status);
        $stmt1->execute();

        // 2. Ghi nhật ký vào lịch sử
        $stmt2 = $conn->prepare("INSERT INTO device_history (device_id, status) VALUES (?, ?)");
        $stmt2->bind_param("ss", $device_id, $status);
        $stmt2->execute();

        echo json_encode(["status" => "success", "message" => "Data logged successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid data"]);
    }
}
?>