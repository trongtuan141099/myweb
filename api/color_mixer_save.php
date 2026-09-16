<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../core/check_permission.php';

    $edit_id = intval($_POST['edit_id'] ?? 0);
    $color_type = trim($_POST['color_type'] ?? '');
    $pipe_type = trim($_POST['pipe_type'] ?? '');
    $pipe_size = trim($_POST['pipe_size'] ?? '');
    $color_code = trim($_POST['color_code'] ?? '');
    $hauler_speed = floatval($_POST['hauler_speed'] ?? 0);
    $mixer_speed_large = trim($_POST['mixer_speed_large'] ?? '-');
    $mixer_speed_small = trim($_POST['mixer_speed_small'] ?? '-');
    $virgin_resin_output = floatval($_POST['virgin_resin'] ?? 0);
    $color_masterbatch_output = floatval($_POST['color_output'] ?? 0);

    if (empty($color_type) || empty($pipe_type) || empty($pipe_size) || empty($color_code)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ các thông tin bắt buộc']);
        exit;
    }

    $user_name = $_SESSION['user_fullname'] ?? $_SESSION['username'] ?? 'Admin User';
    $user_id = $_SESSION['user_id'] ?? 1;

    if ($edit_id > 0) {
        // CẬP NHẬT (UPDATE)
        $stmt = $conn->prepare("UPDATE color_mixer_settings SET 
            color_type = ?, 
            pipe_type = ?, 
            pipe_size = ?, 
            color_code = ?, 
            hauler_speed = ?, 
            mixer_speed_large = ?, 
            mixer_speed_small = ?, 
            virgin_resin_output = ?, 
            color_masterbatch_output = ?, 
            updated_by = ?, 
            updated_by_name = ?
            WHERE id = ?");
        $stmt->bind_param(
            "sssssssddisi",
            $color_type,
            $pipe_type,
            $pipe_size,
            $color_code,
            $hauler_speed,
            $mixer_speed_large,
            $mixer_speed_small,
            $virgin_resin_output,
            $color_masterbatch_output,
            $user_id,
            $user_name,
            $edit_id
        );
        $stmt->execute();
        $msg = "Đã cập nhật thông số thành công!";
    } else {
        // THÊM MỚI (INSERT)
        $stmt = $conn->prepare("INSERT INTO color_mixer_settings 
            (color_type, pipe_type, pipe_size, color_code, hauler_speed, mixer_speed_large, mixer_speed_small, virgin_resin_output, color_masterbatch_output, updated_by, updated_by_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssssssddis",
            $color_type,
            $pipe_type,
            $pipe_size,
            $color_code,
            $hauler_speed,
            $mixer_speed_large,
            $mixer_speed_small,
            $virgin_resin_output,
            $color_masterbatch_output,
            $user_id,
            $user_name
        );
        $stmt->execute();
        $msg = "Đã đăng ký thông số mới thành công!";
    }

    ob_clean();
    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý hệ thống: ' . $e->getMessage()]);
}