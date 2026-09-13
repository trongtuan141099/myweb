<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../config/db.php';

    $page      = (int)($_GET['page'] ?? 1);
    $limit     = (int)($_GET['limit'] ?? 50);
    $startDate = $_GET['start_date'] ?? '';
    $endDate   = $_GET['end_date'] ?? '';
    $search    = trim($_GET['search'] ?? '');
    $offset    = ($page - 1) * $limit;

    $where = ["1=1"];
    $params = [];
    $types  = "";

    if (!empty($startDate) && !empty($endDate)) {
        $where[] = "received_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= "ss";
    }

    if (!empty($search)) {
        $where[] = "(lvf LIKE ? OR category LIKE ? OR part_code LIKE ? OR lot_no LIKE ?)";
        $s = "%$search%";
        $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
        $types .= "ssss";
    }

    $whereSql = implode(" AND ", $where);

    // Đếm tổng số bản ghi
    $countSql = "SELECT COUNT(*) as total FROM material_viscosity_logs WHERE $whereSql";
    $stmtC = $conn->prepare($countSql);
    if (!empty($types)) {
        $stmtC->bind_param($types, ...$params);
    }
    $stmtC->execute();
    $totalRecords = (int)$stmtC->get_result()->fetch_assoc()['total'];
    $totalPages   = ceil($totalRecords / $limit);

    // Lấy dữ liệu phân trang
    $dataSql = "SELECT * FROM material_viscosity_logs WHERE $whereSql ORDER BY received_date DESC, id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmtD = $conn->prepare($dataSql);
    $stmtD->bind_param($types, ...$params);
    $stmtD->execute();
    $rows = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $rows,
        'pagination' => [
            'current_page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>