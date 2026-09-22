<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

requireApiPermission(['hrm.manage', 'api.hrm.add_employee']);

$employee_code = trim($_POST["employee_code"] ?? '');
$full_name     = trim($_POST['full_name'] ?? '');
$hire_date     = trim($_POST['hire_date'] ?? date('Y-m-d'));

if (empty($employee_code) || empty($full_name)) {
    header("Location: ../index.php?mainpage=hrm&subpage=add_employee&status=error&message=Thiếu+thông+tin+bắt+buộc");
    exit;
}

$stmt = $conn->prepare("INSERT INTO employees (employee_code, full_name, hire_date) VALUES (?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("sss", $employee_code, $full_name, $hire_date);
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