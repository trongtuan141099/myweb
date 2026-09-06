<?php
header('Content-Type: application/json');

$uploadDir = __DIR__ . '/../documents/';
$dataFile = __DIR__ . '/../data/documents.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = $_POST['id'] ?? '';
    $docCode  = $_POST['doc_code'] ?? '';
    $title    = $_POST['title'] ?? '';
    $folderId = $_POST['folder_id'] ?? '';

    if (!$id || !file_exists($dataFile)) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài liệu!']);
        exit;
    }

    $documents = json_decode(file_get_contents($dataFile), true);
    $foundIndex = -1;

    for ($i = 0; $i < count($documents); $i++) {
        if ($documents[$i]['id'] == $id) {
            $foundIndex = $i;
            break;
        }
    }

    if ($foundIndex === -1) {
        echo json_encode(['success' => false, 'message' => 'Tài liệu không tồn tại!']);
        exit;
    }

    // Cập nhật thông tin cơ bản
    $documents[$foundIndex]['doc_code']  = $docCode;
    $documents[$foundIndex]['title']     = $title;
    $documents[$foundIndex]['folder_id'] = $folderId;

    // Nếu người dùng chọn file PDF mới để đè
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileName = date('dmY_His') . '_' . basename($_FILES['file']['name']);
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)) {
            $documents[$foundIndex]['file_path'] = '/myweb/documents/' . $fileName;
        }
    }

    file_put_contents($dataFile, json_encode($documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true, 'data' => $documents[$foundIndex]]);
}