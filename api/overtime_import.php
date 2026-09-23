<?php
/**
 * API Import Dữ Liệu Tăng Ca (Kế Hoạch & Thực Tế)
 * DX Plastic Group - Overtime Management System
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../vendor/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8");
    }
}

if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.']);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$current_user = $_SESSION['user']['username'] ?? ($_SESSION['username'] ?? 'admin');

/**
 * Hàm phân tích ngày giờ từ chuỗi d/m/Y H:i:s sang Y-m-d H:i:s
 */
function parseDateTimeCustom($dateStr) {
    if (empty($dateStr)) return null;
    $dateStr = trim($dateStr);
    $dt = DateTime::createFromFormat('d/m/Y H:i:s', $dateStr);
    if ($dt) return $dt->format('Y-m-d H:i:s');
    $dt = DateTime::createFromFormat('d/m/Y H:i', $dateStr);
    if ($dt) return $dt->format('Y-m-d H:i:00');
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
    if ($dt) return $dt->format('Y-m-d H:i:s');
    $dt = DateTime::createFromFormat('d/m/Y', $dateStr);
    if ($dt) return $dt->format('Y-m-d 00:00:00');
    return null;
}

/**
 * Hàm xử lý import một file Excel (plan hoặc actual)
 */
function processExcelImport($conn, $filePath, $originalFileName, $fileType, $currentUser) {
    if (!file_exists($filePath)) {
        return ['success' => false, 'message' => 'Không tìm thấy tập tin cần nạp.'];
    }

    $xlsx = SimpleXLSX::parse($filePath);
    if (!$xlsx) {
        return ['success' => false, 'message' => 'Không thể đọc file Excel: ' . SimpleXLSX::parseError()];
    }

    $rows = $xlsx->rows();
    if (count($rows) < 3) {
        return ['success' => false, 'message' => 'File Excel không có dữ liệu (ít hơn 3 dòng).'];
    }

    // Tạo Batch Code
    $batchCode = 'IMP_' . date('Ymd_His') . '_' . rand(100, 999);
    $fileSize = filesize($filePath);
    $stmtBatch = $conn->prepare("INSERT INTO ot_import_batches (batch_code, file_type, file_name, file_size, total_rows, created_by, status) VALUES (?, ?, ?, ?, ?, ?, 'processing')");
    $totalDataRows = count($rows) - 2;
    $stmtBatch->bind_param("sssiss", $batchCode, $fileType, $originalFileName, $fileSize, $totalDataRows, $currentUser);
    $stmtBatch->execute();
    $batchId = $stmtBatch->insert_id;
    $stmtBatch->close();

    $inserted = 0;
    $updated = 0;
    $errors = 0;

    $conn->begin_transaction();

    try {
        if ($fileType === 'plan') {
            // Chuẩn bị câu lệnh UPSERT cho Kế hoạch
            $sqlPlan = "
                INSERT INTO ot_plans (
                    employee_code, full_name, group_name, team_name, ot_date,
                    start_time, end_time, total_minutes, direct_manager, indirect_manager,
                    reason, reject_reason, approval_status, import_batch_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    full_name = VALUES(full_name),
                    group_name = VALUES(group_name),
                    team_name = VALUES(team_name),
                    end_time = VALUES(end_time),
                    total_minutes = VALUES(total_minutes),
                    direct_manager = VALUES(direct_manager),
                    indirect_manager = VALUES(indirect_manager),
                    reason = VALUES(reason),
                    reject_reason = VALUES(reject_reason),
                    approval_status = VALUES(approval_status),
                    import_batch_id = VALUES(import_batch_id),
                    updated_at = CURRENT_TIMESTAMP
            ";
            $stmtUpsert = $conn->prepare($sqlPlan);

            for ($i = 2; $i < count($rows); $i++) {
                $r = $rows[$i];
                $lineNum = $i + 1;
                $empCode = trim($r[0] ?? '');
                $fullName = trim($r[1] ?? '');
                $groupName = trim($r[2] ?? '');
                $teamName = trim($r[3] ?? '');
                $startTimeStr = trim($r[4] ?? '');
                $endTimeStr = trim($r[5] ?? '');
                $totalMin = intval(trim($r[6] ?? 0));
                $directMgr = trim($r[7] ?? '');
                $indirectMgr = trim($r[8] ?? '');
                $reason = trim($r[9] ?? '');
                $rejectReason = trim($r[10] ?? '');
                $status = trim($r[11] ?? 'Chấp Nhận');

                // Validation
                if (empty($empCode)) {
                    $errors++;
                    logImportError($conn, $batchId, $lineNum, $r, 'Mã nhân viên rỗng');
                    continue;
                }

                $startTime = parseDateTimeCustom($startTimeStr);
                $endTime = parseDateTimeCustom($endTimeStr);
                if (!$startTime || !$endTime) {
                    $errors++;
                    logImportError($conn, $batchId, $lineNum, $r, "Thời gian không hợp lệ (Bắt đầu: '{$startTimeStr}', Kết thúc: '{$endTimeStr}')");
                    continue;
                }

                $otDate = date('Y-m-d', strtotime($startTime));

                // Kiểm tra xem đã tồn tại trước đó chưa để tính số bản ghi Insert vs Update
                $stmtCheck = $conn->prepare("SELECT id, full_name, total_minutes, reason FROM ot_plans WHERE employee_code = ? AND ot_date = ? AND start_time = ?");
                $stmtCheck->bind_param("sss", $empCode, $otDate, $startTime);
                $stmtCheck->execute();
                $existing = $stmtCheck->get_result()->fetch_assoc();
                $stmtCheck->close();

                // Chạy UPSERT
                $stmtUpsert->bind_param(
                    "sssssssisssssi",
                    $empCode, $fullName, $groupName, $teamName, $otDate,
                    $startTime, $endTime, $totalMin, $directMgr, $indirectMgr,
                    $reason, $rejectReason, $status, $batchId
                );
                $stmtUpsert->execute();

                if ($existing) {
                    $updated++;
                    logAuditTrail($conn, $batchId, $empCode, $otDate, 'plan', 'update', $existing, [
                        'full_name' => $fullName, 'total_minutes' => $totalMin, 'reason' => $reason
                    ], $currentUser);
                } else {
                    $inserted++;
                    logAuditTrail($conn, $batchId, $empCode, $otDate, 'plan', 'insert', null, [
                        'full_name' => $fullName, 'total_minutes' => $totalMin, 'reason' => $reason
                    ], $currentUser);
                }
            }
            $stmtUpsert->close();

        } else {
            // Chuẩn bị câu lệnh UPSERT cho Thực tế
            $sqlActual = "
                INSERT INTO ot_actuals (
                    employee_code, full_name, group_name, team_name, reason,
                    direct_manager, indirect_manager, ot_date, start_time_actual, end_time_actual,
                    total_minutes_actual, start_time_plan, end_time_plan, diff_minutes, diff_status,
                    reject_reason, approval_status, import_batch_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    full_name = VALUES(full_name),
                    group_name = VALUES(group_name),
                    team_name = VALUES(team_name),
                    reason = VALUES(reason),
                    direct_manager = VALUES(direct_manager),
                    indirect_manager = VALUES(indirect_manager),
                    end_time_actual = VALUES(end_time_actual),
                    total_minutes_actual = VALUES(total_minutes_actual),
                    start_time_plan = VALUES(start_time_plan),
                    end_time_plan = VALUES(end_time_plan),
                    diff_minutes = VALUES(diff_minutes),
                    diff_status = VALUES(diff_status),
                    reject_reason = VALUES(reject_reason),
                    approval_status = VALUES(approval_status),
                    import_batch_id = VALUES(import_batch_id),
                    updated_at = CURRENT_TIMESTAMP
            ";
            $stmtUpsert = $conn->prepare($sqlActual);

            for ($i = 2; $i < count($rows); $i++) {
                $r = $rows[$i];
                $lineNum = $i + 1;
                $empCode = trim($r[0] ?? '');
                $fullName = trim($r[1] ?? '');
                $groupName = trim($r[2] ?? '');
                $teamName = trim($r[3] ?? '');
                $reason = trim($r[4] ?? '');
                $directMgr = trim($r[5] ?? '');
                $indirectMgr = trim($r[6] ?? '');
                $startTimeTTStr = trim($r[7] ?? '');
                $endTimeTTStr = trim($r[8] ?? '');
                $totalMinTT = intval(trim($r[9] ?? 0));
                $startTimeKHStr = trim($r[10] ?? '');
                $endTimeKHStr = trim($r[11] ?? '');
                $diffMin = intval(trim($r[12] ?? 0));
                $rejectReason = trim($r[13] ?? '');
                $diffStatus = trim($r[14] ?? '');
                $approvalStatus = trim($r[15] ?? 'Đã duyệt');

                // Validation
                if (empty($empCode)) {
                    $errors++;
                    logImportError($conn, $batchId, $lineNum, $r, 'Mã nhân viên rỗng');
                    continue;
                }

                $startTimeTT = parseDateTimeCustom($startTimeTTStr);
                $endTimeTT = parseDateTimeCustom($endTimeTTStr);
                if (!$startTimeTT || !$endTimeTT) {
                    $errors++;
                    logImportError($conn, $batchId, $lineNum, $r, "Thời gian thực tế không hợp lệ ('{$startTimeTTStr}' - '{$endTimeTTStr}')");
                    continue;
                }

                $startTimeKH = parseDateTimeCustom($startTimeKHStr);
                $endTimeKH = parseDateTimeCustom($endTimeKHStr);
                $otDate = date('Y-m-d', strtotime($startTimeTT));

                // Kiểm tra xem đã tồn tại trước đó chưa
                $stmtCheck = $conn->prepare("SELECT id, full_name, total_minutes_actual, reason FROM ot_actuals WHERE employee_code = ? AND ot_date = ? AND start_time_actual = ?");
                $stmtCheck->bind_param("sss", $empCode, $otDate, $startTimeTT);
                $stmtCheck->execute();
                $existing = $stmtCheck->get_result()->fetch_assoc();
                $stmtCheck->close();

                // Chạy UPSERT
                $stmtUpsert->bind_param(
                    "ssssssssssississsi",
                    $empCode, $fullName, $groupName, $teamName, $reason,
                    $directMgr, $indirectMgr, $otDate, $startTimeTT, $endTimeTT,
                    $totalMinTT, $startTimeKH, $endTimeKH, $diffMin, $diffStatus,
                    $rejectReason, $approvalStatus, $batchId
                );
                $stmtUpsert->execute();

                if ($existing) {
                    $updated++;
                    logAuditTrail($conn, $batchId, $empCode, $otDate, 'actual', 'update', $existing, [
                        'full_name' => $fullName, 'total_minutes_actual' => $totalMinTT, 'diff_minutes' => $diffMin
                    ], $currentUser);
                } else {
                    $inserted++;
                    logAuditTrail($conn, $batchId, $empCode, $otDate, 'actual', 'insert', null, [
                        'full_name' => $fullName, 'total_minutes_actual' => $totalMinTT, 'diff_minutes' => $diffMin
                    ], $currentUser);
                }
            }
            $stmtUpsert->close();
        }

        // Cập nhật trạng thái Batch
        $stmtUpBatch = $conn->prepare("UPDATE ot_import_batches SET inserted_rows = ?, updated_rows = ?, error_rows = ?, status = 'completed' WHERE id = ?");
        $stmtUpBatch->bind_param("iiii", $inserted, $updated, $errors, $batchId);
        $stmtUpBatch->execute();
        $stmtUpBatch->close();

        $conn->commit();

        // Tự động tính toán lại bảng lũy kế năm
        recalculateYearlyAccumulations($conn);

        return [
            'success' => true,
            'batch_id' => $batchId,
            'batch_code' => $batchCode,
            'file_type' => $fileType,
            'total_rows' => $totalDataRows,
            'inserted_rows' => $inserted,
            'updated_rows' => $updated,
            'error_rows' => $errors,
            'message' => "Import {$fileType} thành công: {$inserted} thêm mới, {$updated} cập nhật ghi đè, {$errors} lỗi."
        ];

    } catch (Exception $e) {
        $conn->rollback();
        $conn->query("UPDATE ot_import_batches SET status = 'failed', error_message = '" . $conn->real_escape_string($e->getMessage()) . "' WHERE id = {$batchId}");
        return ['success' => false, 'message' => 'Lỗi trong quá trình xử lý: ' . $e->getMessage()];
    }
}

function logImportError($conn, $batchId, $rowIndex, $rawData, $reason) {
    $stmt = $conn->prepare("INSERT INTO ot_import_errors (batch_id, row_index, raw_data, error_reason) VALUES (?, ?, ?, ?)");
    $json = json_encode($rawData, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param("iiss", $batchId, $rowIndex, $json, $reason);
    $stmt->execute();
    $stmt->close();
}

function logAuditTrail($conn, $batchId, $empCode, $otDate, $recType, $action, $beforeData, $afterData, $changedBy) {
    $stmt = $conn->prepare("INSERT INTO ot_import_logs (batch_id, employee_code, ot_date, record_type, action, before_data, after_data, changed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $beforeJson = $beforeData ? json_encode($beforeData, JSON_UNESCAPED_UNICODE) : null;
    $afterJson = json_encode($afterData, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param("isssssss", $batchId, $empCode, $otDate, $recType, $action, $beforeJson, $afterJson, $changedBy);
    $stmt->execute();
    $stmt->close();
}

/**
 * Hàm tính toán lại bảng tổng hợp lũy kế năm ot_yearly_accumulations
 */
function recalculateYearlyAccumulations($conn) {
    // Lấy danh sách các năm có dữ liệu
    $yearsRes = $conn->query("SELECT DISTINCT YEAR(ot_date) as y FROM ot_actuals WHERE ot_date IS NOT NULL ORDER BY y DESC");
    $years = [];
    while ($r = $yearsRes->fetch_assoc()) {
        $years[] = intval($r['y']);
    }
    if (empty($years)) $years[] = intval(date('Y'));

    foreach ($years as $year) {
        // Gom tổng giờ từng tháng của từng nhân viên
        $sqlAgg = "
            SELECT 
                employee_code,
                SUM(CASE WHEN MONTH(ot_date) = 1 THEN total_hours_actual ELSE 0 END) AS m1,
                SUM(CASE WHEN MONTH(ot_date) = 2 THEN total_hours_actual ELSE 0 END) AS m2,
                SUM(CASE WHEN MONTH(ot_date) = 3 THEN total_hours_actual ELSE 0 END) AS m3,
                SUM(CASE WHEN MONTH(ot_date) = 4 THEN total_hours_actual ELSE 0 END) AS m4,
                SUM(CASE WHEN MONTH(ot_date) = 5 THEN total_hours_actual ELSE 0 END) AS m5,
                SUM(CASE WHEN MONTH(ot_date) = 6 THEN total_hours_actual ELSE 0 END) AS m6,
                SUM(CASE WHEN MONTH(ot_date) = 7 THEN total_hours_actual ELSE 0 END) AS m7,
                SUM(CASE WHEN MONTH(ot_date) = 8 THEN total_hours_actual ELSE 0 END) AS m8,
                SUM(CASE WHEN MONTH(ot_date) = 9 THEN total_hours_actual ELSE 0 END) AS m9,
                SUM(CASE WHEN MONTH(ot_date) = 10 THEN total_hours_actual ELSE 0 END) AS m10,
                SUM(CASE WHEN MONTH(ot_date) = 11 THEN total_hours_actual ELSE 0 END) AS m11,
                SUM(CASE WHEN MONTH(ot_date) = 12 THEN total_hours_actual ELSE 0 END) AS m12,
                SUM(total_hours_actual) AS total_year
            FROM ot_actuals
            WHERE YEAR(ot_date) = {$year}
            GROUP BY employee_code
        ";
        $aggRes = $conn->query($sqlAgg);
        if ($aggRes) {
            $stmtUpsertAcc = $conn->prepare("
                INSERT INTO ot_yearly_accumulations (
                    employee_code, year, total_hours_m1, total_hours_m2, total_hours_m3, total_hours_m4,
                    total_hours_m5, total_hours_m6, total_hours_m7, total_hours_m8, total_hours_m9,
                    total_hours_m10, total_hours_m11, total_hours_m12, total_hours_year, warning_level
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    total_hours_m1 = VALUES(total_hours_m1),
                    total_hours_m2 = VALUES(total_hours_m2),
                    total_hours_m3 = VALUES(total_hours_m3),
                    total_hours_m4 = VALUES(total_hours_m4),
                    total_hours_m5 = VALUES(total_hours_m5),
                    total_hours_m6 = VALUES(total_hours_m6),
                    total_hours_m7 = VALUES(total_hours_m7),
                    total_hours_m8 = VALUES(total_hours_m8),
                    total_hours_m9 = VALUES(total_hours_m9),
                    total_hours_m10 = VALUES(total_hours_m10),
                    total_hours_m11 = VALUES(total_hours_m11),
                    total_hours_m12 = VALUES(total_hours_m12),
                    total_hours_year = VALUES(total_hours_year),
                    warning_level = VALUES(warning_level),
                    last_updated_at = CURRENT_TIMESTAMP
            ");

            while ($row = $aggRes->fetch_assoc()) {
                $emp = $row['employee_code'];
                $ty = floatval($row['total_year']);
                $warning = ($ty >= 200.0) ? 'red' : (($ty >= 160.0) ? 'yellow' : 'green');
                $m1 = floatval($row['m1']); $m2 = floatval($row['m2']); $m3 = floatval($row['m3']);
                $m4 = floatval($row['m4']); $m5 = floatval($row['m5']); $m6 = floatval($row['m6']);
                $m7 = floatval($row['m7']); $m8 = floatval($row['m8']); $m9 = floatval($row['m9']);
                $m10 = floatval($row['m10']); $m11 = floatval($row['m11']); $m12 = floatval($row['m12']);

                $stmtUpsertAcc->bind_param(
                    "siddddddddddddds",
                    $emp, $year, $m1, $m2, $m3, $m4, $m5, $m6, $m7, $m8, $m9, $m10, $m11, $m12, $ty, $warning
                );
                $stmtUpsertAcc->execute();
            }
            $stmtUpsertAcc->close();
        }
    }
}

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
?>

