<?php
require "../config/db.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    if ($conn->query("DELETE FROM payroll WHERE id = $id")) {
        header("Location: ../index.php?mainpage=payroll&subpage=list&msg=deleted");
    } else {
        header("Location: ../index.php?mainpage=payroll&subpage=list&msg=error");
    }
} else {
    header("Location: ../index.php?mainpage=payroll&subpage=list");
}
?>
