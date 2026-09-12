<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

try {
    require_once __DIR__ . '/../config/db.php';

    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];

    if (empty($ids) || !is_array($ids)) {
        throw new Exception("Vui lòng chọn ít nhất một bản ghi để xóa!");
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare("DELETE FROM extrusion_actual_logs WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => "Đã xóa thành công " . $stmt->affected_rows . " bản ghi thực tích!"
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>