<?php
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';

    if (!empty($email)) {
        $result = $conn->query("SELECT id FROM employees WHERE email = '$email'");
        echo $result->num_rows > 0 ? '1' : '0';
    } else {
        echo '0';
    }
}
?>
