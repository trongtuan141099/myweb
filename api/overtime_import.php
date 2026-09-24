<?php
/**
 * API Import Dữ Liệu Tăng Ca (Kế Hoạch & Thực Tế)
 * DX Plastic Group - Overtime Management System
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/overtime_service.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bắt buộc quyền api.overtime.import
requireApiPermission('api.overtime.import');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$current_user = $_SESSION['user']['username'] ?? ($_SESSION['username'] ?? 'admin');

// =========================================================================
// ROUTER XỬ LÝ ACTIONS
// =========================================================================
try {
    switch ($action) {
        // Upload file Excel trực tiếp từ máy tính
        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
                exit;
            }

            $fileType = $_POST['file_type'] ?? 'plan';
            if (!in_array($fileType, ['plan', 'actual'])) {
                echo json_encode(['success' => false, 'message' => 'Loại dữ liệu import không hợp lệ']);
                exit;
            }

            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng chọn tập tin Excel (.xlsx) để tải lên']);
                exit;
            }

            $tmpPath = $_FILES['excel_file']['tmp_name'];
            $fileName = $_FILES['excel_file']['name'];
            $res = processExcelImport($conn, $tmpPath, $fileName, $fileType, $current_user);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        // Tiện ích nạp nhanh 2 file mẫu từ thư mục data/
        case 'import_data_files':
            $planFile = __DIR__ . '/../data/DanhSachDuyetTangCaKeHoach.xlsx';
            $actualFile = __DIR__ . '/../data/DanhSachDuyetTangCaThucTe.xlsx';

            $results = [];
            if (file_exists($planFile)) {
                $results['plan'] = processExcelImport($conn, $planFile, 'DanhSachDuyetTangCaKeHoach.xlsx', 'plan', $current_user);
            } else {
                $results['plan'] = ['success' => false, 'message' => 'Không tìm thấy file DanhSachDuyetTangCaKeHoach.xlsx'];
            }

            if (file_exists($actualFile)) {
                $results['actual'] = processExcelImport($conn, $actualFile, 'DanhSachDuyetTangCaThucTe.xlsx', 'actual', $current_user);
            } else {
                $results['actual'] = ['success' => false, 'message' => 'Không tìm thấy file DanhSachDuyetTangCaThucTe.xlsx'];
            }

            // Tự động chạy đối soát sau khi nạp
            runReconciliationInternal($conn);

            echo json_encode([
                'success' => true,
                'message' => 'Đã hoàn tất nạp 2 file dữ liệu tăng ca từ thư mục Data!',
                'results' => $results
            ], JSON_UNESCAPED_UNICODE);
            break;

        // Lấy danh sách các đợt import
        case 'get_batches':
            $res = $conn->query("SELECT * FROM ot_import_batches ORDER BY id DESC LIMIT 50");
            $batches = [];
            while ($row = $res->fetch_assoc()) {
                $batches[] = $row;
            }
            echo json_encode(['success' => true, 'batches' => $batches], JSON_UNESCAPED_UNICODE);
            break;

        // Lấy danh sách dòng lỗi của một batch
        case 'get_batch_errors':
            $batchId = intval($_GET['batch_id'] ?? 0);
            $stmt = $conn->prepare("SELECT * FROM ot_import_errors WHERE batch_id = ? ORDER BY id ASC");
            $stmt->bind_param("i", $batchId);
            $stmt->execute();
            $errors = [];
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $errors[] = $r;
            }
            $stmt->close();
            echo json_encode(['success' => true, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            break;

        // Lấy lịch sử audit log của một batch
        case 'get_batch_logs':
            $batchId = intval($_GET['batch_id'] ?? 0);
            $stmt = $conn->prepare("SELECT * FROM ot_import_logs WHERE batch_id = ? ORDER BY id ASC LIMIT 100");
            $stmt->bind_param("i", $batchId);
            $stmt->execute();
            $logs = [];
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $logs[] = $r;
            }
            $stmt->close();
            echo json_encode(['success' => true, 'logs' => $logs], JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
