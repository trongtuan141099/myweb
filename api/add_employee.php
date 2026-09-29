<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['hrm.manage', 'api.hrm.add_employee']);

$employee_code = trim($_POST["employee_code"] ?? '');
$full_name     = trim($_POST['full_name'] ?? '');
$gender        = trim($_POST['gender'] ?? '');
$job_level     = trim($_POST['job_level'] ?? '');
$cost_center   = trim($_POST['cost_center'] ?? '');
$work_group    = trim($_POST['work_group'] ?? '');
$work_shift    = trim($_POST['work_shift'] ?? 'Ca 1');
$hire_date     = !empty($_POST['hire_date']) ? trim($_POST['hire_date']) : NULL;
$resignation_date = !empty($_POST['resignation_date']) ? trim($_POST['resignation_date']) : NULL;

if (empty($employee_code) || empty($full_name)) {
    header("Location: ../index.php?mainpage=hrm&subpage=add_employee&status=error&message=Thiếu+thông+tin+bắt+buộc");
    exit;
}

if ($hire_date === '' || $hire_date === '0000-00-00') $hire_date = NULL;
if ($resignation_date === '' || $resignation_date === '0000-00-00') $resignation_date = NULL;

$sql = "INSERT INTO employees (employee_code, full_name, gender, job_level, cost_center, work_group, work_shift, hire_date, resignation_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            full_name = VALUES(full_name),
            gender = VALUES(gender),
            job_level = VALUES(job_level),
            cost_center = VALUES(cost_center),
            work_group = VALUES(work_group),
            work_shift = VALUES(work_shift),
            hire_date = VALUES(hire_date),
            resignation_date = VALUES(resignation_date)";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("sssssssss", $employee_code, $full_name, $gender, $job_level, $cost_center, $work_group, $work_shift, $hire_date, $resignation_date);
    if ($stmt->execute()) {
        header("Location: ../index.php?mainpage=hrm&subpage=list&status=success");
        exit;
    } else {
        header("Location: ../index.php?mainpage=hrm&subpage=add_employee&status=error&message=" . urlencode($stmt->error));
        exit;
    }
} else {
    header("Location: ../index.php?mainpage=hrm&subpage=add_employee&status=error&message=" . urlencode($conn->error));
    exit;
}