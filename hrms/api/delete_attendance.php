<?php
require "../config/db.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    if ($conn->query("DELETE FROM attendance WHERE id = $id")) {
        header("Location: ../index.php?mainpage=attendance&subpage=list&msg=deleted");
    } else {
        header("Location: ../index.php?mainpage=attendance&subpage=list&msg=error");
    }
} else {
    header("Location: ../index.php?mainpage=attendance&subpage=list");
}
?>
