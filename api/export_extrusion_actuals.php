<?php
ob_start();
require_once __DIR__ . '/../config/db.php';

$mode     = $_GET['mode'] ?? 'month';
$pipeSize = $_GET['pipe_size'] ?? 'ALL';
$search   = trim($_GET['search'] ?? '');

if ($mode === 'month') {
    $month = $_GET['month'] ?? date('Y-m');
    $startDate = "$month-01";
    $endDate   = date("Y-m-t", strtotime($startDate));
} else {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate   = $_GET['end_date'] ?? date('Y-m-t');
}

$where = ["production_date BETWEEN ? AND ?"];
$params = [$startDate, $endDate];
$types = "ss";

if ($pipeSize !== 'ALL') {
    $where[] = "TRIM(UPPER(pipe_size)) = ?";
    $params[] = strtoupper(trim($pipeSize));
    $types .= "s";
}

if (!empty($search)) {
    $where[] = "(employee_code LIKE ? OR employee_name LIKE ? OR mfg_order_code LIKE ? OR product_code LIKE ?)";
    $searchKey = "%$search%";
    array_push($params, $searchKey, $searchKey, $searchKey, $searchKey);
    $types .= "ssss";
}

$whereClause = implode(" AND ", $where);
$stmt = $conn->prepare("SELECT * FROM extrusion_actual_logs WHERE $whereClause ORDER BY production_date ASC");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$filename = "Extrusion_Actuals_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
// UTF-8 BOM để Excel đọc tiếng Việt không bị lỗi font
fputs($output, "\xEF\xBB\xBF");

if (!empty($data)) {
    fputcsv($output, array_keys($data[0]));
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
}
fclose($output);
exit();
?>