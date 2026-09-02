<?php
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';

    if (!empty($name)) {
        $sql = "INSERT INTO departments (name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ss", $name, $description);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Thêm phòng ban thành công!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $stmt->error]);
            }
            $stmt->close();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền tên phòng ban!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
