<?php
/**
 * API Xuất Excel/CSV Toàn Bộ Danh Sách Nhân Viên
 * DX Plastic Group - Factory Management System
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!hasPermission(['hrm.view', 'hrm.manage', 'admin'])) {
    http_response_code(403);
    die('Bạn không có quyền xuất dữ liệu danh sách nhân sự.');
}

// Bộ lọc (nếu có)
$dept   = trim($_GET['department'] ?? '');
$gender = trim($_GET['gender'] ?? '');
$group  = trim($_GET['group'] ?? '');
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = "WHERE 1=1";
if (!empty($dept)) {
    $sDept = $conn->real_escape_string($dept);
    $where .= " AND cost_center = '{$sDept}'";
}
if (!empty($gender)) {
    $sGender = $conn->real_escape_string($gender);
    $where .= " AND gender = '{$sGender}'";
}
if (!empty($group)) {
    $sGroup = $conn->real_escape_string($group);
    $where .= " AND work_group = '{$sGroup}'";
}
if ($status === 'active') {
    $where .= " AND (resignation_date IS NULL OR resignation_date = '0000-00-00')";
} elseif ($status === 'resigned') {
    $where .= " AND (resignation_date IS NOT NULL AND resignation_date != '0000-00-00')";
}
if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $where .= " AND (employee_code LIKE '%{$s}%' OR full_name LIKE '%{$s}%' OR cost_center LIKE '%{$s}%')";
}

$sql = "SELECT 
            employee_code,
            full_name,
            gender,
            job_level,
            cost_center,
            work_group,
            work_shift,
            hire_date,
            resignation_date
        FROM employees
        {$where}
        ORDER BY (CASE WHEN resignation_date IS NOT NULL AND resignation_date != '0000-00-00' THEN 1 ELSE 0 END) ASC, hire_date DESC, employee_code ASC";

$result = $conn->query($sql);

$filename = "Danh_Sach_Nhan_Vien_" . date('Ymd_His') . ".csv";

if (!headers_sent()) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Ghi UTF-8 BOM để Excel hiển thị tiếng Việt chuẩn xác 100% không bị vỡ font
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Tiêu đề cột
fputcsv($output, [
    'STT',
    'Mã Nhân Viên',
    'Họ và Tên',
    'Giới Tính',
    'Cấp Bậc / Vị Trí',
    'Phòng Ban (Cost Center)',
    'Nhóm Làm Việc',
    'Ca Làm Việc',
    'Ngày Vào Công Ty',
    'Ngày Nghỉ Việc',
    'Trạng Thái Lao Động'
]);

$stt = 1;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $hasResigned = !empty($row['resignation_date']) && $row['resignation_date'] !== '0000-00-00';
        $resignationDateStr = $hasResigned ? $row['resignation_date'] : '';
        $statusStr = $hasResigned ? 'Đã nghỉ việc' : 'Đang làm việc';

        fputcsv($output, [
            $stt++,
            $row['employee_code'],
            $row['full_name'],
            $row['gender'] ?? '',
            $row['job_level'] ?? '',
            $row['cost_center'] ?? '',
            $row['work_group'] ?? '',
            $row['work_shift'] ?? '',
            (!empty($row['hire_date']) && $row['hire_date'] !== '0000-00-00') ? $row['hire_date'] : '',
            $resignationDateStr,
            $statusStr
        ]);
    }
}

fclose($output);
exit;
