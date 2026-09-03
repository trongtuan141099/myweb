<?php
header('Content-Type: application/json');

$dataFile = __DIR__ . '/../data/documents.json';

if (file_exists($dataFile)) {
    echo file_get_contents($dataFile);
} else {
    // Nếu chưa có dữ liệu, trả về mảng rỗng dạng JSON thay vì báo lỗi
    echo json_encode([]);
}
?>