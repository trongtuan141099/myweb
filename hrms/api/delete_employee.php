<?php
require "../config/db.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete related attendance records first
    $conn->query("DELETE FROM attendance WHERE employee_id = $id");

    // Delete related leave requests
    $conn->query("DELETE FROM leave_requests WHERE employee_id = $id");

    // Delete related payroll records
    $conn->query("DELETE FROM payroll WHERE employee_id = $id");

    // Delete employee
    if ($conn->query("DELETE FROM employees WHERE id = $id")) {
        header("Location: ../index.php?mainpage=employees&subpage=list&msg=deleted");
    } else {
        header("Location: ../index.php?mainpage=employees&subpage=list&msg=error");
    }
} else {
    header("Location: ../index.php?mainpage=employees&subpage=list");
}
?>
