<?php
/**
 * API Quản Lý & Tra Cứu Cấu Hình Ca Làm Việc (Shift_Master API)
 * DX Plastic Group - Overtime Management System
 */

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/shift_service.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = trim($_REQUEST['action'] ?? 'list');

try {
    switch ($action) {
        // =====================================================================
        // 1. DANH SÁCH CẤU HÌNH CA LÀM VIỆC (SHIFT_MASTER)
        // =====================================================================
        case 'list':
            $shifts = ShiftMasterService::getActiveShifts($conn, true);
            echo json_encode([
                'success' => true,
                'total' => count($shifts),
                'data' => $shifts
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. TEST THUẬT TOÁN NHẬN DIỆN CA (KIỂM THỬ THỜI GIAN OT)
        // =====================================================================
        case 'test_detect':
            $startTime = trim($_REQUEST['start_time'] ?? '');
            $endTime = trim($_REQUEST['end_time'] ?? '');
            $reason = trim($_REQUEST['reason'] ?? '');
            $workGroup = trim($_REQUEST['work_group'] ?? '');
            $costCenter = trim($_REQUEST['cost_center'] ?? '');

            if (empty($startTime) || empty($endTime)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Vui lòng cung cấp start_time và end_time.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $detectedCode = ShiftMasterService::determineShift(
                $startTime,
                $endTime,
                [
                    'reason' => $reason,
                    'work_group' => $workGroup,
                    'cost_center' => $costCenter
                ],
                $conn
            );

            // Tìm thông tin tên ca tương ứng
            $shifts = ShiftMasterService::getActiveShifts($conn);
            $shiftName = $detectedCode;
            foreach ($shifts as $s) {
                if ($s['shift_code'] === $detectedCode) {
                    $shiftName = $s['shift_name'];
                    break;
                }
            }

            echo json_encode([
                'success' => true,
                'input' => [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'reason' => $reason,
                    'work_group' => $workGroup
                ],
                'detected_shift_code' => $detectedCode,
                'detected_shift_name' => $shiftName
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 3. CẬP NHẬT CẤU HÌNH CA LÀM VIỆC (DÀNH CHO QUẢN TRỊ VIÊN)
        // =====================================================================
        case 'save':
            if (!hasPermission(['overtime.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật cấu hình ca làm việc.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $shiftCode = trim($_POST['shift_code'] ?? '');
            $shiftName = trim($_POST['shift_name'] ?? '');
            $stdStart = trim($_POST['standard_start_time'] ?? '');
            $stdEnd = trim($_POST['standard_end_time'] ?? '');
            $otBefore = floatval($_POST['ot_before_hours'] ?? 2.0);
            $otAfter = floatval($_POST['ot_after_hours'] ?? 2.0);
            $detStart = trim($_POST['detect_start_time'] ?? '');
            $detEnd = trim($_POST['detect_end_time'] ?? '');
            $priority = intval($_POST['priority'] ?? 10);
            $activeFlag = isset($_POST['active_flag']) ? intval($_POST['active_flag']) : 1;
            $isCrossDay = isset($_POST['is_cross_day']) ? intval($_POST['is_cross_day']) : 0;
            $desc = trim($_POST['description'] ?? '');

            if (empty($shiftCode) || empty($shiftName) || empty($stdStart) || empty($stdEnd)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("
                INSERT INTO shift_master (
                    shift_code, shift_name, standard_start_time, standard_end_time,
                    ot_before_hours, ot_after_hours, detect_start_time, detect_end_time,
                    priority, active_flag, is_cross_day, description
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    shift_name = VALUES(shift_name),
                    standard_start_time = VALUES(standard_start_time),
                    standard_end_time = VALUES(standard_end_time),
                    ot_before_hours = VALUES(ot_before_hours),
                    ot_after_hours = VALUES(ot_after_hours),
                    detect_start_time = VALUES(detect_start_time),
                    detect_end_time = VALUES(detect_end_time),
                    priority = VALUES(priority),
                    active_flag = VALUES(active_flag),
                    is_cross_day = VALUES(is_cross_day),
                    description = VALUES(description),
                    updated_at = CURRENT_TIMESTAMP
            ");
            if (!$stmt) throw new Exception($conn->error);
            $stmt->bind_param("ssssddssiiis", $shiftCode, $shiftName, $stdStart, $stdEnd, $otBefore, $otAfter, $detStart, $detEnd, $priority, $activeFlag, $isCrossDay, $desc);
            $ok = $stmt->execute();
            $stmt->close();

            // Clear static cache
            ShiftMasterService::getActiveShifts($conn, true);

            echo json_encode([
                'success' => $ok,
                'message' => $ok ? 'Lưu cấu hình ca thành công!' : 'Lỗi khi lưu: ' . $conn->error
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ.'], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
