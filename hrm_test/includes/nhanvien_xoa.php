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

    $msnv=$_POST["msnv"] ?? '';
    $ten = $_POST['ten'] ??'';
    $ngayvao = $_POST['ngayvao'] ??'';

    $sql = "INSERT INTO nhanvien (msnv, hoten, ngayvao)
    VALUES ('$msnv', '$ten', '$ngayvao')";

    if ($conn->query($sql) === TRUE) {
    // echo "New record created successfully";
    header("Location: ../index.php?status=success");
    } else {
    echo "Error: " . $sql . "<br>" . $conn->error;
    }


    // echo "Mã số nhân viên: $msnv <br>";
    // echo "Tên: $ten <br>";  
    // echo "Ngày vào công ty: $ngayvao <br>";
?>