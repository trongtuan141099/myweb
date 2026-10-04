<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../core/check_permission.php';
    require_once __DIR__ . '/../core/extrusion_service.php';
    requireApiPermission(['production.data', 'api.production.extrusion_delete']);

    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];

    if (empty($ids) || !is_array($ids)) {
        throw new Exception("Vui lòng chọn ít nhất một bản ghi để xóa!");
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    // Lấy danh sách production_order_code để đồng bộ xóa ở extrusion_productions
    $stmtCodes = $conn->prepare("SELECT production_order_code FROM extrusion_actual_logs WHERE id IN ($placeholders)");
    $stmtCodes->bind_param($types, ...$ids);
    $stmtCodes->execute();
    $codeRows = $stmtCodes->get_result()->fetch_all(MYSQLI_ASSOC);
    $prodCodes = array_filter(array_column($codeRows, 'production_order_code'));
    $stmtCodes->close();

    $stmt = $conn->prepare("DELETE FROM extrusion_actual_logs WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    // Đồng bộ xóa ở extrusion_productions
    if (!empty($prodCodes)) {
        syncDeleteExtrusionActualToProduction($conn, $prodCodes);
    }

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