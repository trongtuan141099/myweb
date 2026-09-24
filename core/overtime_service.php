<?php
/**
 * Core Overtime Service (Import, UPSERT & Reconciliation Engine)
 * DX Plastic Group - Overtime Management System
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

/**
 * Hàm phân tích ngày giờ từ chuỗi d/m/Y H:i:s hoặc d/m/Y H:i sang Y-m-d H:i:s
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
    $dt = DateTime::createFromFormat('Y-m-d', $dateStr);
    if ($dt) return $dt->format('Y-m-d 00:00:00');
    return null;
}

/**
 * Hàm xử lý import một file Excel (plan hoặc actual) với cơ chế UPSERT
 */
function processExcelImport($conn, $filePath, $originalFileName, $fileType, $currentUser = 'SYSTEM') {
    if (!file_exists($filePath)) {
        return ['success' => false, 'message' => 'Không tìm thấy tập tin cần nạp: ' . htmlspecialchars($filePath)];
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
    $totalDataRows = count($rows) - 2;

    $stmtBatch = $conn->prepare("INSERT INTO ot_import_batches (batch_code, file_type, file_name, file_size, total_rows, created_by, status) VALUES (?, ?, ?, ?, ?, ?, 'processing')");
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

                // Kiểm tra xem đã tồn tại trước đó chưa
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

function recalculateYearlyAccumulations($conn) {
    $yearsRes = $conn->query("SELECT DISTINCT YEAR(ot_date) as y FROM ot_actuals WHERE ot_date IS NOT NULL ORDER BY y DESC");
    $years = [];
    while ($r = $yearsRes->fetch_assoc()) {
        $years[] = intval($r['y']);
    }
    if (empty($years)) $years[] = intval(date('Y'));

    foreach ($years as $year) {
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

/**
 * Hàm chạy đối soát tự động nội bộ giữa ot_plans và ot_actuals
 */
function runReconciliationInternal($conn, $filterMonth = null, $filterYear = null) {
    $wherePlan = "WHERE 1=1";
    $whereActual = "WHERE 1=1";
    if ($filterYear) {
        $wherePlan .= " AND YEAR(ot_date) = " . intval($filterYear);
        $whereActual .= " AND YEAR(ot_date) = " . intval($filterYear);
    }
    if ($filterMonth) {
        $wherePlan .= " AND MONTH(ot_date) = " . intval($filterMonth);
        $whereActual .= " AND MONTH(ot_date) = " . intval($filterMonth);
    }

    $resPlans = $conn->query("SELECT * FROM ot_plans {$wherePlan} ORDER BY ot_date ASC, employee_code ASC");
    $plansByEmpDate = [];
    while ($p = $resPlans->fetch_assoc()) {
        $key = $p['employee_code'] . '_' . $p['ot_date'];
        $plansByEmpDate[$key][] = $p;
    }

    $resActuals = $conn->query("SELECT * FROM ot_actuals {$whereActual} ORDER BY ot_date ASC, employee_code ASC");
    $actualsByEmpDate = [];
    while ($a = $resActuals->fetch_assoc()) {
        $key = $a['employee_code'] . '_' . $a['ot_date'];
        $actualsByEmpDate[$key][] = $a;
    }

    $allKeys = array_unique(array_merge(array_keys($plansByEmpDate), array_keys($actualsByEmpDate)));

    $reconciledCount = 0;
    $matchedCount = 0;
    $planOnlyCount = 0;
    $actualOnlyCount = 0;
    $diffCount = 0;
    $overdueCount = 0;

    $conn->begin_transaction();

    foreach ($allKeys as $key) {
        $plans = $plansByEmpDate[$key] ?? [];
        $actuals = $actualsByEmpDate[$key] ?? [];

        list($empCode, $otDate) = explode('_', $key);

        // TH1: Có Kế hoạch nhưng KHÔNG CÓ Thực tế
        if (!empty($plans) && empty($actuals)) {
            foreach ($plans as $p) {
                $orderKey = $empCode . '_' . $otDate . '_' . date('H:i:s', strtotime($p['start_time']));
                $reconcileStatus = 'plan_only';
                $planMinutes = intval($p['total_minutes']);
                $actualMinutes = 0;
                $diffMinutes = -$planMinutes;
                $needsExplanation = 1;
                $violationType = 'Có kế hoạch nhưng không có thực tế';

                saveReconciliationRecordInternal(
                    $conn, $orderKey, $empCode, $otDate, $p['id'], null, $reconcileStatus,
                    $planMinutes, $actualMinutes, $diffMinutes, 0, 0, $needsExplanation, $violationType
                );
                $planOnlyCount++;
                $reconciledCount++;
            }
        }
        // TH2: Có Thực tế nhưng KHÔNG CÓ Kế hoạch
        else if (empty($plans) && !empty($actuals)) {
            foreach ($actuals as $a) {
                $timeStr = !empty($a['start_time_plan']) ? $a['start_time_plan'] : $a['start_time_actual'];
                $orderKey = $empCode . '_' . $otDate . '_' . date('H:i:s', strtotime($timeStr));
                $reconcileStatus = 'actual_only';
                $planMinutes = 0;
                $actualMinutes = intval($a['total_minutes_actual']);
                $diffMinutes = $actualMinutes;
                $needsExplanation = 1;
                $violationType = 'Có thực tế nhưng không có kế hoạch';

                saveReconciliationRecordInternal(
                    $conn, $orderKey, $empCode, $otDate, null, $a['id'], $reconcileStatus,
                    $planMinutes, $actualMinutes, $diffMinutes, 0, 0, $needsExplanation, $violationType
                );
                $actualOnlyCount++;
                $reconciledCount++;
            }
        }
        // TH3: CÓ CẢ HAI
        else {
            $usedActualIds = [];

            foreach ($plans as $p) {
                $bestActual = null;

                // 1. Thử ghép cặp bằng chính xác start_time_plan nếu file TT có lưu
                foreach ($actuals as $a) {
                    if (in_array($a['id'], $usedActualIds)) continue;
                    if (!empty($a['start_time_plan']) && $a['start_time_plan'] === $p['start_time']) {
                        $bestActual = $a;
                        break;
                    }
                }

                // 2. Nếu chưa ghép được, ghép theo thời gian bắt đầu gần nhất
                if (!$bestActual) {
                    $minDiffTime = PHP_INT_MAX;
                    foreach ($actuals as $a) {
                        if (in_array($a['id'], $usedActualIds)) continue;
                        $diffT = abs(strtotime($p['start_time']) - strtotime($a['start_time_actual']));
                        if ($diffT < $minDiffTime) {
                            $minDiffTime = $diffT;
                            $bestActual = $a;
                        }
                    }
                }

                $orderKey = $empCode . '_' . $otDate . '_' . date('H:i:s', strtotime($p['start_time']));

                if ($bestActual) {
                    $usedActualIds[] = $bestActual['id'];
                    $planMin = intval($p['total_minutes']);
                    $actMin = intval($bestActual['total_minutes_actual']);
                    $diffMin = $actMin - $planMin;

                    $isTimeMatch = ($p['start_time'] === $bestActual['start_time_actual']) && ($p['end_time'] === $bestActual['end_time_actual']) && ($diffMin === 0);

                    $approvalDaysDiff = 0;
                    $isOverdue = 0;
                    if (!empty($bestActual['approval_date'])) {
                        $otTimestamp = strtotime($otDate);
                        $appTimestamp = strtotime($bestActual['approval_date']);
                        $approvalDaysDiff = floor(($appTimestamp - $otTimestamp) / 86400);
                        if ($approvalDaysDiff > 3) {
                            $isOverdue = 1;
                        }
                    }

                    if ($isTimeMatch && !$isOverdue) {
                        $reconcileStatus = 'matched';
                        $needsExplanation = 0;
                        $violationType = '';
                        $matchedCount++;
                    } else if (!$isTimeMatch) {
                        $reconcileStatus = 'time_diff';
                        $needsExplanation = 1;
                        $violationType = $diffMin > 0 ? "Thực tế lớn hơn kế hoạch ({$diffMin} phút)" : "Thực tế nhỏ hơn kế hoạch (" . abs($diffMin) . " phút)";
                        $diffCount++;
                    } else {
                        $reconcileStatus = 'overdue';
                        $needsExplanation = 1;
                        $violationType = "Phê duyệt quá thời hạn 03 ngày ({$approvalDaysDiff} ngày)";
                        $overdueCount++;
                    }

                    saveReconciliationRecordInternal(
                        $conn, $orderKey, $empCode, $otDate, $p['id'], $bestActual['id'], $reconcileStatus,
                        $planMin, $actMin, $diffMin, $approvalDaysDiff, $isOverdue, $needsExplanation, $violationType
                    );
                    $reconciledCount++;
                } else {
                    $planMin = intval($p['total_minutes']);
                    saveReconciliationRecordInternal(
                        $conn, $orderKey, $empCode, $otDate, $p['id'], null, 'plan_only',
                        $planMin, 0, -$planMin, 0, 0, 1, 'Có kế hoạch nhưng không có thực tế'
                    );
                    $planOnlyCount++;
                    $reconciledCount++;
                }
            }

            // Các actual còn sót lại
            foreach ($actuals as $a) {
                if (!in_array($a['id'], $usedActualIds)) {
                    $timeStr = !empty($a['start_time_plan']) ? $a['start_time_plan'] : $a['start_time_actual'];
                    $orderKey = $empCode . '_' . $otDate . '_' . date('H:i:s', strtotime($timeStr));
                    $actMin = intval($a['total_minutes_actual']);
                    saveReconciliationRecordInternal(
                        $conn, $orderKey, $empCode, $otDate, null, $a['id'], 'actual_only',
                        0, $actMin, $actMin, 0, 0, 1, 'Có thực tế nhưng không có kế hoạch'
                    );
                    $actualOnlyCount++;
                    $reconciledCount++;
                }
            }
        }
    }

    $conn->commit();

    return [
        'total' => $reconciledCount,
        'matched' => $matchedCount,
        'plan_only' => $planOnlyCount,
        'actual_only' => $actualOnlyCount,
        'time_diff' => $diffCount,
        'overdue' => $overdueCount,
        'needs_explanation' => ($planOnlyCount + $actualOnlyCount + $diffCount + $overdueCount)
    ];
}

function saveReconciliationRecordInternal(
    $conn, $orderKey, $empCode, $otDate, $planId, $actualId, $status,
    $planMin, $actMin, $diffMin, $approvalDaysDiff, $isOverdue, $needsExplanation, $violationType
) {
    $stmt = $conn->prepare("
        INSERT INTO ot_reconciliations (
            order_key, employee_code, ot_date, plan_id, actual_id, reconcile_status,
            plan_minutes, actual_minutes, diff_minutes, approval_days_diff,
            is_overdue, needs_explanation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            plan_id = COALESCE(VALUES(plan_id), plan_id),
            actual_id = COALESCE(VALUES(actual_id), actual_id),
            reconcile_status = VALUES(reconcile_status),
            plan_minutes = VALUES(plan_minutes),
            actual_minutes = VALUES(actual_minutes),
            diff_minutes = VALUES(diff_minutes),
            approval_days_diff = VALUES(approval_days_diff),
            is_overdue = VALUES(is_overdue),
            needs_explanation = IF(is_explained = 1, 0, VALUES(needs_explanation)),
            last_reconciled_at = CURRENT_TIMESTAMP
    ");

    $stmt->bind_param(
        "sssiisiiiiii",
        $orderKey, $empCode, $otDate, $planId, $actualId, $status,
        $planMin, $actMin, $diffMin, $approvalDaysDiff, $isOverdue, $needsExplanation
    );
    $stmt->execute();
    $recId = $stmt->insert_id;
    if ($recId === 0) {
        $stmtGet = $conn->prepare("SELECT id FROM ot_reconciliations WHERE order_key = ?");
        $stmtGet->bind_param("s", $orderKey);
        $stmtGet->execute();
        $row = $stmtGet->get_result()->fetch_assoc();
        $recId = $row ? intval($row['id']) : 0;
        $stmtGet->close();
    }
    $stmt->close();

    // Lưu ý: Tuyệt đối không tự động sinh ticket giải trình ngầm ở đây.
    // Ticket giải trình chỉ được lưu khi Admin chủ động bấm nút "Yêu cầu giải trình" từ trang đối soát.
}

