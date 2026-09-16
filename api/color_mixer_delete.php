<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../core/check_permission.php';

    // Trường hợp 1: Xóa nhiều mục được chọn (mảng JSON ids)
    if (!empty($_POST['ids'])) {
        $ids = json_decode($_POST['ids'], true);
        if (is_array($ids) && count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            
            $stmt = $conn->prepare("DELETE FROM color_mixer_settings WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();

            ob_clean();
            echo json_encode(['success' => true, 'message' => "Đã xóa thành công {$stmt->affected_rows} mục đã chọn!"]);
            exit;
        }
    }

    // Trường hợp 2: Xóa 1 mục đơn lẻ
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM color_mixer_settings WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Đã xóa bản ghi thành công']);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Dữ liệu ID không hợp lệ']);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý hệ thống: ' . $e->getMessage()]);
}