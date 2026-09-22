<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
requireApiPermission(['mixer.view', 'api.mixer.export']);

$where = ["1=1"];
$params = [];
$types = "";

if (!empty($_GET['color_type'])) { $where[] = "color_type = ?"; $params[] = $_GET['color_type']; $types .= "s"; }
if (!empty($_GET['pipe_type'])) { $where[] = "pipe_type = ?"; $params[] = $_GET['pipe_type']; $types .= "s"; }
if (!empty($_GET['pipe_size'])) { $where[] = "pipe_size = ?"; $params[] = $_GET['pipe_size']; $types .= "s"; }
if (!empty($_GET['color_code'])) { $where[] = "color_code = ?"; $params[] = $_GET['color_code']; $types .= "s"; }

$whereSql = implode(" AND ", $where);

$stmt = $conn->prepare("SELECT * FROM color_mixer_settings WHERE $whereSql ORDER BY id DESC");
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Du_Lieu_Bo_Tron_Mau_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
// Ghi BOM UTF-8 chống lỗi hiển thị tiếng Việt trên Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['STT', 'Loại Màu', 'Loại Ống', 'Size Ống', 'Mã Màu', 'Tốc Độ Kéo (m/p)', 'Trục Lớn (RPM)', 'Trục Nhỏ (RPM)', 'Nhựa Nguyên Sinh (kg/h)', 'Lưu Lượng Màu (kg/h)', 'Người Cập Nhật', 'Ngày Cập Nhật']);

foreach ($rows as $index => $row) {
    fputcsv($output, [
        $index + 1,
        $row['color_type'],
        $row['pipe_type'],
        $row['pipe_size'],
        $row['color_code'],
        $row['hauler_speed'],
        $row['mixer_speed_large'],
        $row['mixer_speed_small'],
        $row['virgin_resin_output'],
        $row['color_masterbatch_output'],
        $row['updated_by_name'],
        $row['updated_at']
    ]);
}
fclose($output);