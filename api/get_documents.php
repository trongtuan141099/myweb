<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../core/check_permission.php';
requireApiPermission(['document.view', 'api.document.get']);

$dataFile = __DIR__ . '/../data/documents.json';

if (file_exists($dataFile)) {
    echo file_get_contents($dataFile);
} else {
    // Nếu chưa có dữ liệu, trả về mảng rỗng dạng JSON thay vì báo lỗi
    echo json_encode([]);
}
?>