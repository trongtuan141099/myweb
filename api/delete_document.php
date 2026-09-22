<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../core/check_permission.php';
requireApiPermission(['document.delete', 'api.document.delete']);

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
        $cleanRel = ltrim(str_replace(['/myweb/', 'myweb/'], '', $fileToDelete), '/');
        $realPath = __DIR__ . '/../' . $cleanRel;
        if (file_exists($realPath) && is_file($realPath)) {
            unlink($realPath);
        }
    }

    file_put_contents($dataFile, json_encode($newDocuments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true]);
}