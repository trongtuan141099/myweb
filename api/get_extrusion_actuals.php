<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

try {
    require_once __DIR__ . '/../config/db.php';

    $page     = max(1, (int)($_GET['page'] ?? 1));
    $limit    = max(10, min(200, (int)($_GET['limit'] ?? 50)));
    $offset   = ($page - 1) * $limit;

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
        $where[] = "(employee_code LIKE ? OR employee_name LIKE ? OR mfg_order_code LIKE ? OR product_code LIKE ? OR device_code LIKE ?)";
        $searchKey = "%$search%";
        array_push($params, $searchKey, $searchKey, $searchKey, $searchKey, $searchKey);
        $types .= "sssss";
    }

    $whereClause = implode(" AND ", $where);

    // Count tổng số bản ghi
    $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM extrusion_actual_logs WHERE $whereClause");
    $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $totalRecords = (int)$stmtCount->get_result()->fetch_assoc()['total'];

    // Query lấy danh sách
    $sqlData = "SELECT id, production_date, employee_code, employee_name, shift, 
                       mfg_order_code, product_code, pipe_size, device_code, 
                       finished_qty_m, finished_qty_kg, ng_qty_kg, hard_waste_qty_kg, 
                       total_downtime, total_runtime, machine_efficiency 
                FROM extrusion_actual_logs 
                WHERE $whereClause 
                ORDER BY production_date DESC, id DESC 
                LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmtData = $conn->prepare($sqlData);
    $stmtData->bind_param($types, ...$params);
    $stmtData->execute();
    $data = $stmtData->get_result()->fetch_all(MYSQLI_ASSOC);

    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => ceil($totalRecords / $limit)
        ]
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>