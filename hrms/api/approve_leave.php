<?php
require "../config/db.php";

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    $status = $action == 'approve' ? 'Approved' : 'Rejected';

    if ($conn->query("UPDATE leave_requests SET status = '$status' WHERE id = $id")) {
        header("Location: ../index.php?mainpage=leave&subpage=approval&msg=success");
    } else {
        header("Location: ../index.php?mainpage=leave&subpage=approval&msg=error");
    }
} else {
    header("Location: ../index.php?mainpage=leave&subpage=approval");
}
?>
