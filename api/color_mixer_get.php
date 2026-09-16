<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, intval($_GET['limit'] ?? 15));
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];
$types = "";

if (!empty($_GET['color_type'])) { $where[] = "color_type = ?"; $params[] = $_GET['color_type']; $types .= "s"; }
if (!empty($_GET['pipe_type'])) { $where[] = "pipe_type = ?"; $params[] = $_GET['pipe_type']; $types .= "s"; }
if (!empty($_GET['pipe_size'])) { $where[] = "pipe_size = ?"; $params[] = $_GET['pipe_size']; $types .= "s"; }
if (!empty($_GET['color_code'])) { $where[] = "color_code = ?"; $params[] = $_GET['color_code']; $types .= "s"; }

$whereSql = implode(" AND ", $where);

// Đếm tổng số bản ghi
$stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM color_mixer_settings WHERE $whereSql");
if ($types) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'];

// Lấy danh sách bản ghi
$stmtData = $conn->prepare("SELECT * FROM color_mixer_settings WHERE $whereSql ORDER BY id DESC LIMIT ? OFFSET ?");
$typesData = $types . "ii";
$paramsData = array_merge($params, [$limit, $offset]);
$stmtData->bind_param($typesData, ...$paramsData);
$stmtData->execute();
$data = $stmtData->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'total' => $total,
    'page' => $page,
    'total_pages' => ceil($total / $limit),
    'data' => $data
]);