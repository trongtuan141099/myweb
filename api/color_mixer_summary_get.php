<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/db.php';

    $mixer_type = $_GET['mixer_type'] ?? 'mixer_speed_small';
    if (!in_array($mixer_type, ['mixer_speed_small', 'mixer_speed_large'])) {
        $mixer_type = 'mixer_speed_small';
    }

    $where = ["1=1"];
    $params = [];
    $types = "";

    if (!empty($_GET['pipe_type'])) {
        $where[] = "pipe_type = ?";
        $params[] = $_GET['pipe_type'];
        $types .= "s";
    }

    $whereSql = implode(" AND ", $where);

    // 1. LẤY DANH SÁCH CÁC CỘT (SIZE ỐNG + TỐC ĐỘ KÉO) DUY NHẤT
    $sqlCols = "SELECT DISTINCT pipe_size, hauler_speed FROM color_mixer_settings WHERE $whereSql ORDER BY pipe_size ASC, hauler_speed ASC";
    $stmtCols = $conn->prepare($sqlCols);
    if ($types) $stmtCols->bind_param($types, ...$params);
    $stmtCols->execute();
    $columns = $stmtCols->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. LẤY TOÀN BỘ DỮ LIỆU TỐC ĐỘ THEO MÃ MÀU
    $sqlData = "SELECT pipe_size, hauler_speed, color_code, $mixer_type AS speed_val FROM color_mixer_settings WHERE $whereSql";
    $stmtData = $conn->prepare($sqlData);
    if ($types) $stmtData->bind_param($types, ...$params);
    $stmtData->execute();
    $rows = $stmtData->get_result()->fetch_all(MYSQLI_ASSOC);

    // Ghép dữ liệu thành mảng tra cứu dạng HashMap: Key = "Size_Speed_Color"
    $matrixData = [];
    foreach ($rows as $r) {
        $key = $r['pipe_size'] . '_' . $r['hauler_speed'] . '_' . $r['color_code'];
        $matrixData[$key] = $r['speed_val'];
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'columns' => $columns,
        'matrixData' => $matrixData
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý ma trận: ' . $e->getMessage()]);
}