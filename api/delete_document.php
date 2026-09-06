<?php
header('Content-Type: application/json');

$uploadDir = __DIR__ . '/../documents/';
$dataFile = __DIR__ . '/../data/documents.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';

    if (!$id || !file_exists($dataFile)) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài liệu!']);
        exit;
    }

    $documents = json_decode(file_get_contents($dataFile), true);
    $newDocuments = [];
    $fileToDelete = '';

    foreach ($documents as $doc) {
        if ($doc['id'] == $id) {
            $fileToDelete = $doc['file_path'];
        } else {
            $newDocuments[] = $doc;
        }
    }

    // Xóa file vật lý trên Server
    if ($fileToDelete) {
        $realPath = __DIR__ . '/..' . str_replace('/myweb', '', $fileToDelete);
        if (file_exists($realPath)) {
            unlink($realPath);
        }
    }

    file_put_contents($dataFile, json_encode($newDocuments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true]);
}