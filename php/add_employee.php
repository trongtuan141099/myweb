<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "myweb";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }
    // echo "Connected successfully";

    $employee_code=$_POST["employee_code"] ?? '';
    $full_name = $_POST['full_name'] ??'';
    $hire_date = $_POST['hire_date'] ??'';

    $sql = "INSERT INTO employees (employee_code, full_name, hire_date)
    VALUES ('$employee_code', '$full_name', '$hire_date')";

    if ($conn->query($sql) === TRUE) {
    // echo "New record created successfully";
    header("Location: ../index.php?status=success");
    } else {
    echo "Error: " . $sql . "<br>" . $conn->error;
    }


    // echo "Mã số nhân viên: $employee_code <br>";
    // echo "Tên: $full_name <br>";  
    // echo "Ngày vào công ty: $hire_date <br>";
?>