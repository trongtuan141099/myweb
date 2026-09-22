<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../core/check_permission.php';

    requireApiPermission(['mixer.edit', 'api.mixer.import']);

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn tập tin CSV để tải lên']);
        exit;
    }

    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if ($handle === false) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Không thể đọc nội dung file CSV']);
        exit;
    }

    // Đọc bỏ qua dòng header
    fgetcsv($handle, 1000, ',');

    $user_name = $_SESSION['user_fullname'] ?? $_SESSION['username'] ?? 'Admin User';
    $user_id = $_SESSION['user_id'] ?? 1;

    $conn->begin_transaction();
    $updatedCount = 0;
    $insertedCount = 0;

    // Chuẩn bị câu lệnh Kiểm tra trùng lắp (phân biệt cả tốc độ kéo hauler_speed)
    $stmtCheck = $conn->prepare("SELECT id FROM color_mixer_settings WHERE color_type = ? AND pipe_type = ? AND pipe_size = ? AND color_code = ? AND hauler_speed = ? LIMIT 1");
    
    // Câu lệnh Update ghi đè khi trùng khớp đúng size, màu và tốc độ kéo
    $stmtUpdate = $conn->prepare("UPDATE color_mixer_settings SET 
        mixer_speed_large = ?, mixer_speed_small = ?, virgin_resin_output = ?, color_masterbatch_output = ?, updated_by = ?, updated_by_name = ? 
        WHERE id = ?");

    // Câu lệnh Insert mới
    $stmtInsert = $conn->prepare("INSERT INTO color_mixer_settings 
        (color_type, pipe_type, pipe_size, color_code, hauler_speed, mixer_speed_large, mixer_speed_small, virgin_resin_output, color_masterbatch_output, updated_by, updated_by_name) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        if (empty(array_filter($row))) continue;

        if (count($row) >= 9) {
            $color_type = trim($row[0]);
            $pipe_type = trim($row[1]);
            $pipe_size = trim($row[2]);
            $color_code = trim($row[3]);
            $hauler_speed = floatval(trim($row[4]));
            $mixer_speed_large = trim($row[5]);
            $mixer_speed_small = trim($row[6]);
            $virgin_resin_output = floatval(trim($row[7]));
            $color_masterbatch_output = floatval(trim($row[8]));

            // Kiểm tra trùng theo cả tốc độ kéo
            $stmtCheck->bind_param("ssssd", $color_type, $pipe_type, $pipe_size, $color_code, $hauler_speed);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();

            if ($resCheck->num_rows > 0) {
                // TỒN TẠI -> THỰC HIỆN GHI ĐÈ CẬP NHẬT
                $existingId = $resCheck->fetch_assoc()['id'];
                $stmtUpdate->bind_param("ssddisi", $mixer_speed_large, $mixer_speed_small, $virgin_resin_output, $color_masterbatch_output, $user_id, $user_name, $existingId);
                $stmtUpdate->execute();
                $updatedCount++;
            } else {
                // CHƯA CÓ -> THÊM MỚI
                $stmtInsert->bind_param("ssssdssddis", $color_type, $pipe_type, $pipe_size, $color_code, $hauler_speed, $mixer_speed_large, $mixer_speed_small, $virgin_resin_output, $color_masterbatch_output, $user_id, $user_name);
                $stmtInsert->execute();
                $insertedCount++;
            }
        }
    }

    fclose($handle);
    $conn->commit();

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => "Hoàn tất xử lý CSV! Đã cập nhật ghi đè {$updatedCount} dòng trùng lắp và thêm mới {$insertedCount} dòng."
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollback();
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý hệ thống: ' . $e->getMessage()]);
}