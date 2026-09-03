<?php
header('Content-Type: application/json');

// Đường dẫn lưu file PDF thật (vào thư mục documents/ ở gốc)
$uploadDir = __DIR__ . '/../documents/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Đường dẫn file lưu trữ dữ liệu JSON (vào thư mục data/)
$dataFile = __DIR__ . '/../data/documents.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $folderId = $_POST['folder_id'] ?? '';
    $docCode  = $_POST['doc_code'] ?? ('DOC-' . rand(1000, 9999));
    $title    = $_POST['title'] ?? $_FILES['file']['name'];

    $fileName = time() . '_' . basename($_FILES['file']['name']);
    $targetFilePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)) {
        // Đọc dữ liệu JSON hiện tại
        $currentData = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

        $newDoc = [
            'id'        => (string)time(),
            'doc_code'  => $docCode,
            'title'     => $title,
            'folder_id' => $folderId,
            'file_path' => '/myweb/documents/' . $fileName,
            'status'    => 'Active'
        ];

        $currentData[] = $newDoc;
        file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo json_encode(['success' => true, 'data' => $newDoc]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi upload file!']);
    }
}