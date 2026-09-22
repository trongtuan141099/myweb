<?php
// api/manage_document_folders.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../core/check_permission.php';

$foldersFile = __DIR__ . '/../data/document_folders.json';
$docsFile = __DIR__ . '/../data/documents.json';

// Tải danh sách thư mục từ file hoặc khởi tạo mặc định
function loadFolders($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (is_array($data)) return $data;
    }
    return [];
}

// Lưu danh sách thư mục
function saveFolders($file, $folders) {
    return file_put_contents($file, json_encode($folders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Tìm và thêm node con vào cây đệ quy
function addFolderToTree(&$nodes, $parentId, $newFolder) {
    if (empty($parentId)) {
        $nodes[] = $newFolder;
        return true;
    }
    foreach ($nodes as &$node) {
        if ($node['id'] === $parentId) {
            if (!isset($node['children']) || !is_array($node['children'])) {
                $node['children'] = [];
            }
            $node['children'][] = $newFolder;
            return true;
        }
        if (!empty($node['children'])) {
            if (addFolderToTree($node['children'], $parentId, $newFolder)) {
                return true;
            }
        }
    }
    return false;
}

// Đổi tên node đệ quy
function updateFolderInTree(&$nodes, $folderId, $newName) {
    foreach ($nodes as &$node) {
        if ($node['id'] === $folderId) {
            $node['name'] = $newName;
            return true;
        }
        if (!empty($node['children'])) {
            if (updateFolderInTree($node['children'], $folderId, $newName)) {
                return true;
            }
        }
    }
    return false;
}

// Xóa node đệ quy
function deleteFolderFromTree(&$nodes, $folderId) {
    foreach ($nodes as $index => &$node) {
        if ($node['id'] === $folderId) {
            array_splice($nodes, $index, 1);
            return true;
        }
        if (!empty($node['children'])) {
            if (deleteFolderFromTree($node['children'], $folderId)) {
                return true;
            }
        }
    }
    return false;
}

// Tìm kiếm node theo ID
function findFolderNode($nodes, $folderId) {
    foreach ($nodes as $node) {
        if ($node['id'] === $folderId) return $node;
        if (!empty($node['children'])) {
            $found = findFolderNode($node['children'], $folderId);
            if ($found) return $found;
        }
    }
    return null;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

switch ($action) {
    case 'get':
        requireApiPermission(['document.view', 'api.document.get']);
        $folders = loadFolders($foldersFile);
        echo json_encode(['success' => true, 'data' => $folders]);
        break;

    case 'create':
        requireApiPermission(['document.edit', 'document.upload', 'api.document.edit']);
        $name = trim($_POST['name'] ?? '');
        $parentId = trim($_POST['parent_id'] ?? '');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Tên thư mục không được để trống!']);
            exit;
        }

        // Tạo ID duy nhất cho thư mục mới
        $cleanId = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '-', $name));
        $cleanId = trim(preg_replace('/-+/', '-', $cleanId), '-');
        if (empty($cleanId)) {
            $cleanId = 'folder_' . time();
        } else {
            $cleanId = $cleanId . '_' . substr(md5(uniqid(rand(), true)), 0, 4);
        }

        $newFolder = [
            'id' => $cleanId,
            'name' => $name,
            'children' => []
        ];

        $folders = loadFolders($foldersFile);
        $added = addFolderToTree($folders, $parentId, $newFolder);

        if (!$added && !empty($parentId)) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thư mục cha!']);
            exit;
        }

        saveFolders($foldersFile, $folders);
        echo json_encode(['success' => true, 'message' => 'Tạo thư mục thành công!', 'data' => $newFolder, 'folders' => $folders]);
        break;

    case 'update':
        requireApiPermission(['document.edit', 'api.document.edit']);
        $folderId = trim($_POST['id'] ?? '');
        $newName = trim($_POST['name'] ?? '');

        if (empty($folderId) || empty($newName)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID hoặc Tên thư mục mới!']);
            exit;
        }

        $folders = loadFolders($foldersFile);
        $updated = updateFolderInTree($folders, $folderId, $newName);

        if (!$updated) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thư mục cần sửa!']);
            exit;
        }

        saveFolders($foldersFile, $folders);
        echo json_encode(['success' => true, 'message' => 'Đổi tên thư mục thành công!', 'folders' => $folders]);
        break;

    case 'delete':
        requireApiPermission(['document.delete', 'api.document.delete']);
        $folderId = trim($_POST['id'] ?? '');

        if (empty($folderId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID thư mục cần xóa!']);
            exit;
        }

        $folders = loadFolders($foldersFile);
        $node = findFolderNode($folders, $folderId);

        if (!$node) {
            echo json_encode(['success' => false, 'message' => 'Thư mục không tồn tại!']);
            exit;
        }

        // Kiểm tra xem có thư mục con không
        if (!empty($node['children']) && count($node['children']) > 0) {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa thư mục vì vẫn còn thư mục con bên trong! Vui lòng xóa các thư mục con trước.']);
            exit;
        }

        // Kiểm tra xem có tài liệu nào thuộc thư mục này không
        $docs = file_exists($docsFile) ? json_decode(file_get_contents($docsFile), true) : [];
        if (is_array($docs)) {
            foreach ($docs as $d) {
                if (($d['folder_id'] ?? '') === $folderId) {
                    echo json_encode(['success' => false, 'message' => 'Không thể xóa thư mục vì có tài liệu bên trong! Vui lòng chuyển hoặc xóa tài liệu trước.']);
                    exit;
                }
            }
        }

        $deleted = deleteFolderFromTree($folders, $folderId);
        if ($deleted) {
            saveFolders($foldersFile, $folders);
            echo json_encode(['success' => true, 'message' => 'Xóa thư mục thành công!', 'folders' => $folders]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa thư mục!']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ!']);
        break;
}

