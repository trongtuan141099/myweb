<?php
/**
 * Core Leave Management Service (Phép Nghỉ Kế Hoạch & Thực Tế HRM)
 * DX Plastic Group - Factory Management System
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

/**
 * Phân tích ngày giờ sang Y-m-d (hỗ trợ dd/mm/yyyy và yyyy-mm-dd)
 */
if (!function_exists('parseLeaveDateCustom')) {
function parseLeaveDateCustom($str) {
    if (empty($str)) return null;
    $str = trim($str);
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }
    $ts = strtotime($str);
    if ($ts) return date('Y-m-d', $ts);
    return null;
}
}

/**
 * Phân loại Nhóm làm việc chuẩn xưởng sản xuất:
 * - Đùn TU (TU Extrusion)
 * - Đùn T (T Extrusion)
 * - Thiết bị (Bảo trì & Thiết bị)
 * - Shotblast (Bắn cát)
 * - Nghiền nhựa (Pulverize)
 * - Khác (Bộ phận khác / Giám sát)
 */
if (!function_exists('resolveWorkGroup')) {
function resolveWorkGroup($teamName, $costCenter = '', $empCode = '') {
    $t = mb_strtolower(trim(strval($teamName)), 'UTF-8');
    $cc = strtoupper(trim(strval($costCenter)));

    // 1. Nhóm Đùn TU
    if (stripos($t, 'tu') !== false || stripos($t, 'đùn tu') !== false || $cc === 'A00330' || $cc === 'A00340') {
        return 'Đùn TU';
    }

    // 2. Nhóm Đùn T
    if (stripos($t, 't extrusion') !== false || stripos($t, 'nylon t') !== false || stripos($t, 'đùn t') !== false || $cc === 'A00852' || $cc === 'A00853') {
        return 'Đùn T';
    }

    // 3. Nhóm Nghiền nhựa
    if (stripos($t, 'pulverize') !== false || stripos($t, 'nghiền') !== false || $cc === 'A00442') {
        return 'Nghiền nhựa';
    }

    // 4. Nhóm Thiết bị
    if (stripos($t, 'thiết bị') !== false || stripos($t, 'device') !== false || stripos($t, 'equipment') !== false || $cc === 'A00791' || $cc === 'A00792') {
        return 'Thiết bị';
    }

    // 5. Nhóm Shotblast (Bắn cát)
    if (stripos($t, 'shotblast') !== false || stripos($t, 'bắn cát') !== false || $cc === 'A00430') {
        return 'Shotblast';
    }

    if (!empty($teamName) && $teamName !== 'Plastic Extrusion' && $teamName !== 'Tổ sản xuất' && $teamName !== '-' && !preg_match('/^A\d+$/i', $teamName)) {
        return trim($teamName);
    }

    return 'Khác';
}
}

/**
 * Làm sạch tên loại nghỉ phép từ song ngữ Nhật - Việt (e.g. "有給休暇 / Nghỉ phép năm" -> "Nghỉ phép năm")
 */
if (!function_exists('cleanLeaveType')) {
function cleanLeaveType($raw) {
    if (empty($raw)) return 'Phép năm';
    $raw = trim($raw);
    if (strpos($raw, '/') !== false) {
        $parts = explode('/', $raw);
        $vn = trim(end($parts));
        if (!empty($vn)) return $vn;
    }
    return $raw;
}
}

/**
 * Làm sạch hình thức nghỉ (e.g. "Buổi chiều/半日休暇 - 午後に" -> "Buổi chiều")
 */
if (!function_exists('cleanLeaveShift')) {
function cleanLeaveShift($raw) {
    if (empty($raw)) return 'Cả ngày';
    $raw = trim($raw);
    if (stripos($raw, 'sáng') !== false || stripos($raw, '午前に') !== false) {
        return 'Buổi sáng';
    }
    if (stripos($raw, 'chiều') !== false || stripos($raw, '午後に') !== false) {
        return 'Buổi chiều';
    }
    if (stripos($raw, 'cả ngày') !== false || stripos($raw, '終日') !== false) {
        return 'Cả ngày';
    }
    if (strpos($raw, '/') !== false) {
        $parts = explode('/', $raw);
        return trim($parts[0]);
    }
    return $raw;
}
}

/**
 * Đọc file Excel từ HRM / thủ công và nạp vào bảng leave_actuals (UPSERT)
 * Tuân thủ chính xác format mẫu file data/DanhSachDuyetNghiPhep.xlsx
 */
function processLeaveExcelImport($conn, $filePath, $currentUser = 'HRM_SYNC') {
    if (!file_exists($filePath)) {
        return ['success' => false, 'message' => 'Không tìm thấy file: ' . htmlspecialchars($filePath)];
    }

    $xlsx = SimpleXLSX::parse($filePath);
    if (!$xlsx) {
        return ['success' => false, 'message' => 'Không thể đọc file Excel: ' . SimpleXLSX::parseError()];
    }

    $rows = $xlsx->rows();
    if (count($rows) < 2) {
        return ['success' => false, 'message' => 'File Excel không có dữ liệu'];
    }

    // 1. Tìm dòng tiêu đề (header) - Chính xác bỏ qua dòng tên báo cáo
    $headerRowIndex = -1;
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $rowText = implode(' ', array_map('strval', $rows[$i]));
        if ((stripos($rowText, 'mã') !== false || stripos($rowText, 'manhansu') !== false) 
            && (stripos($rowText, 'họ tên') !== false || stripos($rowText, 'tên') !== false || stripos($rowText, 'từ ngày') !== false)) {
            $headerRowIndex = $i;
            break;
        }
    }

    if ($headerRowIndex === -1) {
        // Fallback kiểm tra từ khóa chung
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $rowText = implode(' ', array_map('strval', $rows[$i]));
            if (stripos($rowText, 'từ ngày') !== false || stripos($rowText, 'đến ngày') !== false) {
                $headerRowIndex = $i;
                break;
            }
        }
    }

    if ($headerRowIndex === -1) {
        $headerRowIndex = 1; // Mặc định dòng 1 theo chuẩn DanhSachDuyetNghiPhep.xlsx
    }

    $header = $rows[$headerRowIndex];
    $colMap = [];
    foreach ($header as $idx => $colName) {
        $c = mb_strtolower(trim(strval($colName)), 'UTF-8');
        if (stripos($c, 'mã') !== false || stripos($c, 'manhansu') !== false || stripos($c, 'emp') !== false) {
            if (!isset($colMap['emp_code'])) $colMap['emp_code'] = $idx;
        } else if (stripos($c, 'họ tên') !== false || stripos($c, 'tên') !== false || stripos($c, 'nguoidangky') !== false) {
            if (!isset($colMap['full_name'])) $colMap['full_name'] = $idx;
        } else if (stripos($c, 'nhóm') !== false && stripos($c, 'loại') === false) {
            if (!isset($colMap['group_name'])) $colMap['group_name'] = $idx;
        } else if (stripos($c, 'tổ') !== false || stripos($c, 'đội') !== false) {
            if (!isset($colMap['team_name'])) $colMap['team_name'] = $idx;
        } else if (stripos($c, 'từ ngày') !== false || stripos($c, 'từ') !== false || stripos($c, 'tungay') !== false) {
            if (!isset($colMap['start_date'])) $colMap['start_date'] = $idx;
        } else if (stripos($c, 'đến ngày') !== false || stripos($c, 'đến') !== false || stripos($c, 'denngay') !== false) {
            if (!isset($colMap['end_date'])) $colMap['end_date'] = $idx;
        } else if (stripos($c, 'số ngày') !== false || stripos($c, 'songay') !== false) {
            if (!isset($colMap['leave_days'])) $colMap['leave_days'] = $idx;
        } else if (stripos($c, 'loại') !== false && stripos($c, 'chi tiết') === false) {
            if (!isset($colMap['leave_type'])) $colMap['leave_type'] = $idx;
        } else if (stripos($c, 'hình thức') !== false || stripos($c, 'buổi') !== false) {
            if (!isset($colMap['leave_shift'])) $colMap['leave_shift'] = $idx;
        } else if (stripos($c, 'lý do nghỉ') !== false || (stripos($c, 'lý do') !== false && stripos($c, 'từ chối') === false)) {
            if (!isset($colMap['reason'])) $colMap['reason'] = $idx;
        } else if (stripos($c, 'trực tiếp') !== false) {
            if (!isset($colMap['dm'])) $colMap['dm'] = $idx;
        } else if (stripos($c, 'gián tiếp') !== false) {
            if (!isset($colMap['idm'])) $colMap['idm'] = $idx;
        } else if (stripos($c, 'từ chối') !== false) {
            if (!isset($colMap['reject_reason'])) $colMap['reject_reason'] = $idx;
        } else if (stripos($c, 'trạng thái') !== false || stripos($c, 'isduyet') !== false) {
            if (!isset($colMap['status'])) $colMap['status'] = $idx;
        }
    }

    // Fallback mặc định theo index file DanhSachDuyetNghiPhep.xlsx
    if (!isset($colMap['emp_code']))    $colMap['emp_code'] = 0;
    if (!isset($colMap['full_name']))   $colMap['full_name'] = 1;
    if (!isset($colMap['group_name']))  $colMap['group_name'] = 2;
    if (!isset($colMap['team_name']))   $colMap['team_name'] = 3;
    if (!isset($colMap['start_date']))  $colMap['start_date'] = 4;
    if (!isset($colMap['end_date']))    $colMap['end_date'] = 5;
    if (!isset($colMap['leave_days']))  $colMap['leave_days'] = 6;
    if (!isset($colMap['leave_type']))  $colMap['leave_type'] = 7;
    if (!isset($colMap['leave_shift'])) $colMap['leave_shift'] = 8;
    if (!isset($colMap['reason']))      $colMap['reason'] = 9;
    if (!isset($colMap['dm']))          $colMap['dm'] = 10;
    if (!isset($colMap['idm']))         $colMap['idm'] = 11;
    if (!isset($colMap['reject_reason']))$colMap['reject_reason'] = 12;
    if (!isset($colMap['status']))      $colMap['status'] = 13;

    $inserted = 0;
    $updated = 0;
    $errors = 0;

    $stmtUpsert = $conn->prepare("
        INSERT INTO leave_actuals (
            hrm_record_id, employee_code, full_name, group_name, work_group, team_name,
            leave_date, start_date, end_date, leave_days, leave_type,
            leave_type_detail, leave_shift, reason, direct_manager, indirect_manager,
            approval_status, raw_data, synced_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE
            full_name = VALUES(full_name),
            group_name = VALUES(group_name),
            work_group = VALUES(work_group),
            team_name = VALUES(team_name),
            start_date = VALUES(start_date),
            end_date = VALUES(end_date),
            leave_days = VALUES(leave_days),
            leave_type = VALUES(leave_type),
            leave_type_detail = VALUES(leave_type_detail),
            leave_shift = VALUES(leave_shift),
            reason = VALUES(reason),
            direct_manager = VALUES(direct_manager),
            indirect_manager = VALUES(indirect_manager),
            approval_status = VALUES(approval_status),
            raw_data = VALUES(raw_data),
            synced_at = CURRENT_TIMESTAMP
    ");

    $conn->begin_transaction();

    try {
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            $empCode = trim(strval($r[$colMap['emp_code']] ?? ''));
            if (empty($empCode)) continue;

            $fullName = trim(strval($r[$colMap['full_name'] ?? -1] ?? ''));
            $groupName = trim(strval($r[$colMap['group_name'] ?? -1] ?? 'Plastic Extrusion'));
            $teamName = trim(strval($r[$colMap['team_name'] ?? -1] ?? ''));

            // Phân giải nhóm làm việc chuẩn xưởng
            $workGroup = resolveWorkGroup($teamName, '', $empCode);

            $startStr = trim(strval($r[$colMap['start_date'] ?? -1] ?? ''));
            $endStr = trim(strval($r[$colMap['end_date'] ?? -1] ?? $startStr));

            $startDate = parseLeaveDateCustom($startStr);
            $endDate = parseLeaveDateCustom($endStr);
            if (!$startDate) {
                $errors++;
                continue;
            }
            if (!$endDate || $endDate < $startDate) $endDate = $startDate;

            $totalDays = floatval(trim(strval($r[$colMap['leave_days'] ?? -1] ?? 1.0)));
            if ($totalDays <= 0) $totalDays = 1.0;

            $rawLeaveType = trim(strval($r[$colMap['leave_type'] ?? -1] ?? 'Phép năm'));
            $leaveType = cleanLeaveType($rawLeaveType);
            $leaveTypeDetail = $rawLeaveType;

            $rawShift = trim(strval($r[$colMap['leave_shift'] ?? -1] ?? 'Cả ngày'));
            $leaveShift = cleanLeaveShift($rawShift);

            $reason = trim(strval($r[$colMap['reason'] ?? -1] ?? ''));
            $dm = trim(strval($r[$colMap['dm'] ?? -1] ?? ''));
            $idm = trim(strval($r[$colMap['idm'] ?? -1] ?? ''));
            $status = trim(strval($r[$colMap['status'] ?? -1] ?? 'Đã duyệt'));
            if ($status === '1' || $status === 'true') $status = 'Đã duyệt';
            else if ($status === '0') $status = 'Chờ duyệt';
            else if ($status === '3') $status = 'Từ chối';

            $rawData = json_encode($r, JSON_UNESCAPED_UNICODE);

            // Tách theo từng ngày trong dải ngày [startDate, endDate]
            $startTs = strtotime($startDate);
            $endTs = strtotime($endDate);
            $currTs = $startTs;

            // Tính số ngày lịch
            $dayCount = max(1, floor(($endTs - $startTs) / 86400) + 1);
            $dailyLeaveDays = ($dayCount > 1) ? round($totalDays / $dayCount, 1) : $totalDays;

            while ($currTs <= $endTs) {
                $leaveDate = date('Y-m-d', $currTs);
                $shiftKey = ($leaveShift === 'Buổi sáng' ? 'S' : ($leaveShift === 'Buổi chiều' ? 'C' : 'F'));
                $recordId = "HRM_{$empCode}_{$leaveDate}_{$shiftKey}";

                // Kiểm tra xem đã tồn tại chưa
                $checkStmt = $conn->prepare("SELECT id FROM leave_actuals WHERE employee_code = ? AND leave_date = ?");
                $checkStmt->bind_param("ss", $empCode, $leaveDate);
                $checkStmt->execute();
                $existing = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();

                $stmtUpsert->bind_param(
                    "sssssssssdssssssss",
                    $recordId, $empCode, $fullName, $groupName, $workGroup, $teamName,
                    $leaveDate, $startDate, $endDate, $dailyLeaveDays, $leaveType,
                    $leaveTypeDetail, $leaveShift, $reason, $dm, $idm,
                    $status, $rawData
                );
                $stmtUpsert->execute();

                if ($existing) {
                    $updated++;
                } else {
                    $inserted++;
                }

                $currTs += 86400; // Tiến 1 ngày
            }
        }

        $stmtUpsert->close();
        $conn->commit();

        return [
            'success'  => true,
            'message'  => "Import phép thực tế thành công: {$inserted} thêm mới, {$updated} cập nhật, {$errors} lỗi.",
            'inserted' => $inserted,
            'updated'  => $updated,
            'errors'   => $errors
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Lỗi khi lưu dữ liệu phép: ' . $e->getMessage()];
    }
}

/**
 * Lấy danh sách các nhóm làm việc đã thiết lập
 */
function getEstablishedWorkGroups($conn) {
    $res = $conn->query("SELECT * FROM leave_work_groups ORDER BY sort_order ASC, id ASC");
    $groups = [];
    if ($res && $res->num_rows > 0) {
        while ($r = $res->fetch_assoc()) {
            $groups[] = $r;
        }
    } else {
        // Danh sách mặc định
        $groups = [
            ['group_code' => 'dun_tu',      'group_name' => 'Đùn TU',      'color_code' => '#3b82f6', 'sort_order' => 1],
            ['group_code' => 'dun_t',       'group_name' => 'Đùn T',       'color_code' => '#8b5cf6', 'sort_order' => 2],
            ['group_code' => 'thiet_bi',    'group_name' => 'Thiết bị',    'color_code' => '#06b6d4', 'sort_order' => 3],
            ['group_code' => 'shotblast',   'group_name' => 'Shotblast',   'color_code' => '#f59e0b', 'sort_order' => 4],
            ['group_code' => 'nghien_nhua', 'group_name' => 'Nghiền nhựa', 'color_code' => '#10b981', 'sort_order' => 5],
            ['group_code' => 'khac',        'group_name' => 'Khác',        'color_code' => '#64748b', 'sort_order' => 6]
        ];
    }
    return $groups;
}

/**
 * Lấy dữ liệu Ma trận Timeline Nghỉ Phép theo Tháng/Năm & Phân nhóm làm việc & Ca làm việc
 */
function getLeaveTimelineMatrix($conn, $month, $year, $groupFilter = '', $search = '', $mode = 'standard', $shiftFilter = '') {
    $month = intval($month);
    $year = intval($year);
    if ($month < 1 || $month > 12) $month = intval(date('m'));
    if ($year < 2020 || $year > 2030) $year = intval(date('Y'));

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $today = date('Y-m-d');
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

    // Lấy cấu hình các nhóm làm việc đã thiết lập
    $definedGroups = getEstablishedWorkGroups($conn);
    $groupOrderMap = [];
    foreach ($definedGroups as $idx => $dg) {
        $groupOrderMap[$dg['group_name']] = $idx;
    }

    // 1. Lấy danh sách nhân viên từ bảng employees (xử lý chính xác resignation_date '0000-00-00')
    $whereEmp = "WHERE (e.resignation_date IS NULL OR e.resignation_date = '0000-00-00' OR e.resignation_date >= '{$startDate}')";
    if (!empty($search)) {
        $s = $conn->real_escape_string($search);
        $whereEmp .= " AND (e.employee_code LIKE '%{$s}%' OR e.full_name LIKE '%{$s}%')";
    }
    if (!empty($shiftFilter)) {
        $sShift = $conn->real_escape_string($shiftFilter);
        $whereEmp .= " AND (e.work_shift = '{$sShift}')";
    }

    $sqlEmps = "
        SELECT 
            e.employee_code,
            e.full_name,
            e.cost_center,
            COALESCE(NULLIF(e.work_shift, ''), 'Ca 1') AS work_shift,
            COALESCE(NULLIF(e.work_group, ''), l.work_group, p.work_group, '') AS saved_work_group,
            COALESCE(l.team_name, a.team_name, p.team_name, '') AS team_name
        FROM employees e
        LEFT JOIN (
            SELECT employee_code, work_group, team_name 
            FROM leave_actuals 
            WHERE team_name IS NOT NULL AND team_name != '' 
            GROUP BY employee_code
        ) l ON e.employee_code = l.employee_code
        LEFT JOIN (
            SELECT employee_code, team_name 
            FROM ot_actuals 
            WHERE team_name IS NOT NULL AND team_name != '' 
            GROUP BY employee_code
        ) a ON e.employee_code = a.employee_code
        LEFT JOIN (
            SELECT employee_code, work_group, team_name 
            FROM leave_plans 
            WHERE team_name IS NOT NULL AND team_name != '' 
            GROUP BY employee_code
        ) p ON e.employee_code = p.employee_code
        {$whereEmp}
        ORDER BY e.employee_code ASC
    ";

    $resEmps = $conn->query($sqlEmps);
    $employees = [];
    $allWorkGroups = [];

    if ($resEmps) {
        while ($emp = $resEmps->fetch_assoc()) {
            // Xác định nhóm làm việc chuẩn xưởng
            $wg = !empty($emp['saved_work_group']) 
                ? $emp['saved_work_group'] 
                : resolveWorkGroup($emp['team_name'], $emp['cost_center'], $emp['employee_code']);

            $allWorkGroups[$wg] = true;
            if (!empty($groupFilter) && $wg !== $groupFilter) {
                continue;
            }

            $employees[$emp['employee_code']] = [
                'employee_code' => $emp['employee_code'],
                'full_name'     => $emp['full_name'],
                'work_group'    => $wg,
                'group_name'    => $wg,
                'work_shift'    => $emp['work_shift'] ?: 'Ca 1',
                'cost_center'   => $emp['cost_center'],
                'team_name'     => trim($emp['team_name'] ?: $wg),
            ];
        }
    }

    // 2. Lấy dữ liệu Phép Thực tế trong tháng
    $sqlActuals = "
        SELECT * FROM leave_actuals 
        WHERE leave_date BETWEEN '{$startDate}' AND '{$endDate}'
    ";
    $resActuals = $conn->query($sqlActuals);
    $actualsByEmpDate = [];
    $totalActualLeaves = 0;
    if ($resActuals) {
        while ($act = $resActuals->fetch_assoc()) {
            $empCode = $act['employee_code'];
            $d = $act['leave_date'];
            $actualsByEmpDate[$empCode][$d] = $act;
            $totalActualLeaves += floatval($act['leave_days']);

            // Nếu nhân viên có phép nhưng chưa có trong danh sách và không bị lọc ca
            if (!isset($employees[$empCode]) && empty($shiftFilter)) {
                $wg = !empty($act['work_group']) ? $act['work_group'] : resolveWorkGroup($act['team_name'], '', $empCode);
                $allWorkGroups[$wg] = true;
                if (empty($groupFilter) || $wg === $groupFilter) {
                    $employees[$empCode] = [
                        'employee_code' => $empCode,
                        'full_name'     => $act['full_name'] ?: $empCode,
                        'work_group'    => $wg,
                        'group_name'    => $wg,
                        'work_shift'    => $act['work_shift'] ?: 'Ca 1',
                        'cost_center'   => '',
                        'team_name'     => trim($act['team_name'] ?: $wg),
                    ];
                }
            }
        }
    }

    // 3. Lấy dữ liệu Phép Kế hoạch trong tháng (loại trừ các đơn bị từ chối)
    $sqlPlans = "
        SELECT * FROM leave_plans 
        WHERE leave_date BETWEEN '{$startDate}' AND '{$endDate}'
          AND status != 'rejected'
    ";
    $resPlans = $conn->query($sqlPlans);
    $plansByEmpDate = [];
    $totalPlannedLeaves = 0;
    if ($resPlans) {
        while ($pln = $resPlans->fetch_assoc()) {
            $empCode = $pln['employee_code'];
            $d = $pln['leave_date'];
            $plansByEmpDate[$empCode][$d] = $pln;
            $totalPlannedLeaves += floatval($pln['leave_days']);

            if (!isset($employees[$empCode]) && empty($shiftFilter)) {
                $wg = !empty($pln['work_group']) ? $pln['work_group'] : resolveWorkGroup($pln['team_name'], '', $empCode);
                $allWorkGroups[$wg] = true;
                if (empty($groupFilter) || $wg === $groupFilter) {
                    $employees[$empCode] = [
                        'employee_code' => $empCode,
                        'full_name'     => $pln['full_name'] ?: $empCode,
                        'work_group'    => $wg,
                        'group_name'    => $wg,
                        'work_shift'    => $pln['work_shift'] ?: 'Ca 1',
                        'cost_center'   => '',
                        'team_name'     => trim($pln['team_name'] ?: $wg),
                    ];
                }
            }
        }
    }

    // 4. Nhóm nhân viên theo work_group và tính ma trận ngày
    $groupedData = [];
    $dailyStatsTotal   = array_fill(1, $daysInMonth, 0);
    $dailyActualTotal  = array_fill(1, $daysInMonth, 0);
    $dailyPlannedTotal = array_fill(1, $daysInMonth, 0);
    $todayLeavesCount  = 0;

    foreach ($employees as $empCode => $emp) {
        $wg = $emp['work_group'];
        if (!isset($groupedData[$wg])) {
            $groupedData[$wg] = [
                'group_name'           => $wg,
                'work_group'           => $wg,
                'total_emps'           => 0,
                'employees'            => [],
                'daily_stats'          => array_fill(1, $daysInMonth, 0),
                'daily_actual_leaves'  => array_fill(1, $daysInMonth, 0),
                'daily_planned_leaves' => array_fill(1, $daysInMonth, 0),
                'daily_working'        => array_fill(1, $daysInMonth, 0)
            ];
        }
        $groupedData[$wg]['total_emps']++;

        $daysData = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayOfWeek = date('N', strtotime($dateStr)); // 1 (Thứ 2) -> 7 (Chủ nhật)
            $isSunday = ($dayOfWeek == 7);
            $isToday = ($dateStr === $today);
            $isPastOrToday = ($dateStr <= $today);

            $act = $actualsByEmpDate[$empCode][$dateStr] ?? null;
            $pln = $plansByEmpDate[$empCode][$dateStr] ?? null;

            // Đếm quân số theo quy tắc duy nhất:
            // <= Hôm nay: Chỉ tính phép thực tế từ HRM (Màu xanh lá)
            // > Hôm nay: Chỉ tính phép dự kiến kế hoạch (Màu vàng)
            if ($isPastOrToday) {
                if ($act) {
                    $groupedData[$wg]['daily_actual_leaves'][$d]++;
                    $dailyActualTotal[$d]++;
                }
            } else {
                if ($pln) {
                    $groupedData[$wg]['daily_planned_leaves'][$d]++;
                    $dailyPlannedTotal[$d]++;
                }
            }

            $cell = [
                'day'         => $d,
                'date'        => $dateStr,
                'day_of_week' => $dayOfWeek,
                'is_sunday'   => $isSunday,
                'is_today'    => $isToday,
                'state'       => 'none',
                'leave_type'  => '',
                'session'     => '',
                'reason'      => '',
                'tooltip'     => ''
            ];

            // Áp dụng Quy Tắc Màu Sắc Duy Nhất (Chỉ Xanh lá <= Hôm nay và Vàng > Hôm nay)
            if ($mode === 'standard') {
                if ($isPastOrToday) {
                    // <= Hôm nay: CHỈ hiển thị Phép thực tế từ HRM (Màu Xanh lá)
                    if ($act) {
                        $cell['state'] = 'actual';
                        $cell['leave_type'] = $act['leave_type'] ?: 'Phép nghỉ';
                        $cell['session'] = $act['leave_shift'] ?: 'Cả ngày';
                        $cell['reason'] = $act['reason'] ?: '';
                        $cell['tooltip'] = "Thực tế HRM: {$cell['leave_type']} ({$cell['session']})" . ($act['reason'] ? " - {$act['reason']}" : '');
                        $groupedData[$wg]['daily_stats'][$d]++;
                        $dailyStatsTotal[$d]++;
                        if ($isToday) $todayLeavesCount++;
                    }
                    // Tuyệt đối không sinh trạng thái khác cho mốc thời gian <= Hôm nay
                } else {
                    // > Hôm nay: CHỈ hiển thị Phép dự kiến kế hoạch (Màu Vàng)
                    if ($pln) {
                        $cell['state'] = 'plan';
                        $cell['leave_type'] = $pln['leave_type'];
                        $cell['session'] = $pln['session_type'] === 'all_day' ? 'Cả ngày' : ($pln['session_type'] === 'morning' ? 'Sáng' : 'Chiều');
                        $cell['reason'] = $pln['reason'] ?: '';
                        $statusText = ($pln['status'] === 'approved') ? 'Đã duyệt' : 'Chờ duyệt';
                        $cell['tooltip'] = "Dự kiến: {$cell['leave_type']} ({$cell['session']}) [{$statusText}]" . ($pln['reason'] ? " - {$pln['reason']}" : '');
                        $groupedData[$wg]['daily_stats'][$d]++;
                        $dailyStatsTotal[$d]++;
                    }
                }
            } else if ($mode === 'plan') {
                if ($pln) {
                    $cell['state'] = 'plan';
                    $cell['leave_type'] = $pln['leave_type'];
                    $cell['session'] = $pln['session_type'] === 'all_day' ? 'Cả ngày' : ($pln['session_type'] === 'morning' ? 'Sáng' : 'Chiều');
                    $cell['tooltip'] = "Kế hoạch: {$cell['leave_type']} ({$cell['session']})";
                    $groupedData[$wg]['daily_stats'][$d]++;
                    $dailyStatsTotal[$d]++;
                }
            } else if ($mode === 'actual') {
                if ($act) {
                    $cell['state'] = 'actual';
                    $cell['leave_type'] = $act['leave_type'] ?: 'Phép nghỉ';
                    $cell['session'] = $act['leave_shift'] ?: 'Cả ngày';
                    $cell['tooltip'] = "Thực tế: {$cell['leave_type']} ({$cell['session']})";
                    $groupedData[$wg]['daily_stats'][$d]++;
                    $dailyStatsTotal[$d]++;
                }
            } else if ($mode === 'compare') {
                if ($act && $pln) {
                    $cell['state'] = 'both';
                    $cell['tooltip'] = "Khớp cả KH & TT: {$act['leave_type']}";
                    $groupedData[$wg]['daily_stats'][$d]++;
                    $dailyStatsTotal[$d]++;
                } else if ($act) {
                    $cell['state'] = 'actual';
                    $cell['tooltip'] = "Có Thực tế nhưng thiếu KH: {$act['leave_type']}";
                    $groupedData[$wg]['daily_stats'][$d]++;
                    $dailyStatsTotal[$d]++;
                } else if ($pln) {
                    $cell['state'] = 'plan';
                    $cell['tooltip'] = "Có Kế hoạch nhưng chưa có TT: {$pln['leave_type']}";
                    $groupedData[$wg]['daily_stats'][$d]++;
                    $dailyStatsTotal[$d]++;
                }
            }

            $daysData[$d] = $cell;
        }

        $emp['days'] = $daysData;
        $groupedData[$wg]['employees'][] = $emp;
    }

    // 5. Tính số lượng quân số đi làm theo từng ngày cho mỗi nhóm
    $workGroupSummary = [];
    foreach ($groupedData as $wg => &$g) {
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $isPastOrToday = ($dateStr <= $today);

            // Số người nghỉ tính vào quân số: nếu quá khứ lấy thực tế, tương lai lấy dự kiến
            $effectiveLeaves = $isPastOrToday ? $g['daily_actual_leaves'][$d] : $g['daily_planned_leaves'][$d];
            $g['daily_working'][$d] = max(0, $g['total_emps'] - $effectiveLeaves);
        }

        $workGroupSummary[] = [
            'work_group'           => $wg,
            'total_emps'           => $g['total_emps'],
            'daily_leaves'         => $g['daily_stats'],
            'daily_actual_leaves'  => $g['daily_actual_leaves'],
            'daily_planned_leaves' => $g['daily_planned_leaves'],
            'daily_working'        => $g['daily_working']
        ];
    }
    unset($g);

    // Sắp xếp các nhóm làm việc theo thứ tự chuẩn
    uksort($groupedData, function($a, $b) use ($groupOrderMap) {
        $oa = $groupOrderMap[$a] ?? 999;
        $ob = $groupOrderMap[$b] ?? 999;
        return $oa - $ob;
    });

    usort($workGroupSummary, function($a, $b) use ($groupOrderMap) {
        $oa = $groupOrderMap[$a['work_group']] ?? 999;
        $ob = $groupOrderMap[$b['work_group']] ?? 999;
        return $oa - $ob;
    });

    // Sắp xếp danh sách tên nhóm cho dropdown filter
    $groupNames = array_keys($allWorkGroups);
    usort($groupNames, function($a, $b) use ($groupOrderMap) {
        $oa = $groupOrderMap[$a] ?? 999;
        $ob = $groupOrderMap[$b] ?? 999;
        return $oa - $ob;
    });

    return [
        'month'                => $month,
        'year'                 => $year,
        'days_in_month'        => $daysInMonth,
        'total_days'           => $daysInMonth,
        'today'                => $today,
        'mode'                 => $mode,
        'filter_group'         => $groupFilter,
        'filter_shift'         => $shiftFilter,
        'all_shifts'           => ['Ca 1', 'Ca 2', 'Ca 3', 'Hành chính'],
        'groups'               => array_values($groupedData),
        'work_group_summary'   => $workGroupSummary,
        'all_group_names'      => $groupNames,
        'total_employees'      => count($employees),
        'total_actual_leaves'  => $totalActualLeaves,
        'total_planned_leaves' => $totalPlannedLeaves,
        'today_leaves_count'   => $todayLeavesCount,
        'daily_stats_total'    => $dailyStatsTotal,
        'daily_actual_total'   => $dailyActualTotal,
        'daily_planned_total'  => $dailyPlannedTotal,
        'summary'              => [
            'daily_actual_leaves'  => $dailyActualTotal,
            'daily_planned_leaves' => $dailyPlannedTotal
        ]
    ];
}

/**
 * Đăng ký phép nghỉ kế hoạch (Hỗ trợ 1 ngày hoặc dải ngày)
 */
function createLeavePlanRecord($conn, $data, $currentUser = 'USER') {
    $empCode = trim($data['employee_code'] ?? '');
    $startDate = trim($data['start_date'] ?? '');
    $endDate = trim($data['end_date'] ?? $startDate);
    $sessionType = trim($data['session_type'] ?? 'all_day');
    $leaveType = cleanLeaveType(trim($data['leave_type'] ?? 'Phép năm'));
    $reason = trim($data['reason'] ?? '');
    $groupName = trim($data['group_name'] ?? 'Plastic Extrusion');
    $workGroup = trim($data['work_group'] ?? '');
    $teamName = trim($data['team_name'] ?? '');
    $fullName = trim($data['full_name'] ?? '');

    if (empty($empCode) || empty($startDate)) {
        return ['success' => false, 'message' => 'Vui lòng chọn nhân viên và ngày bắt đầu nghỉ.'];
    }

    // Tra cứu thông tin từ employees nếu thiếu
    $costCenter = '';
    $stmtE = $conn->prepare("SELECT full_name, cost_center FROM employees WHERE employee_code = ?");
    $stmtE->bind_param("s", $empCode);
    $stmtE->execute();
    $eRow = $stmtE->get_result()->fetch_assoc();
    $stmtE->close();
    if ($eRow) {
        if (empty($fullName)) $fullName = $eRow['full_name'];
        $costCenter = $eRow['cost_center'];
    }

    if (empty($workGroup)) {
        $workGroup = resolveWorkGroup($teamName, $costCenter, $empCode);
    }
    if (empty($teamName)) {
        $teamName = $workGroup;
    }

    $startTs = strtotime($startDate);
    $endTs = strtotime($endDate);
    if (!$startTs || !$endTs || $startTs > $endTs) {
        return ['success' => false, 'message' => 'Khoảng thời gian nghỉ không hợp lệ.'];
    }

    $leaveDays = ($sessionType === 'all_day') ? 1.0 : 0.5;
    $stmt = $conn->prepare("
        INSERT INTO leave_plans (
            employee_code, full_name, group_name, work_group, team_name, leave_date,
            session_type, leave_type, leave_days, reason, status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ON DUPLICATE KEY UPDATE
            full_name = VALUES(full_name),
            work_group = VALUES(work_group),
            team_name = VALUES(team_name),
            leave_type = VALUES(leave_type),
            leave_days = VALUES(leave_days),
            reason = VALUES(reason),
            status = 'pending',
            created_by = VALUES(created_by),
            updated_at = CURRENT_TIMESTAMP
    ");

    $count = 0;
    $currTs = $startTs;
    while ($currTs <= $endTs) {
        $leaveDate = date('Y-m-d', $currTs);
        $stmt->bind_param(
            "ssssssssdss",
            $empCode, $fullName, $groupName, $workGroup, $teamName, $leaveDate,
            $sessionType, $leaveType, $leaveDays, $reason, $currentUser
        );
        $stmt->execute();
        $count++;
        $currTs += 86400;
    }
    $stmt->close();

    return [
        'success' => true,
        'message' => "Đã đăng ký thành công {$count} ngày phép kế hoạch (Đang chờ Trưởng nhóm phê duyệt).",
        'days_count' => $count
    ];
}

/**
 * Trưởng nhóm duyệt đơn phép kế hoạch
 */
function approveLeavePlanRecord($conn, $planId, $currentUser, $note = '') {
    $stmt = $conn->prepare("
        UPDATE leave_plans 
        SET status = 'approved',
            approved_by = ?,
            approved_at = CURRENT_TIMESTAMP,
            approval_note = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $currentUser, $note, $planId);
    $ok = $stmt->execute();
    $stmt->close();

    return [
        'success' => $ok,
        'message' => $ok ? 'Đã phê duyệt chấp thuận đơn phép kế hoạch.' : 'Lỗi khi cập nhật trạng thái duyệt.'
    ];
}

/**
 * Trưởng nhóm từ chối đơn phép kế hoạch
 */
function rejectLeavePlanRecord($conn, $planId, $currentUser, $reason = '') {
    $stmt = $conn->prepare("
        UPDATE leave_plans 
        SET status = 'rejected',
            approved_by = ?,
            approved_at = CURRENT_TIMESTAMP,
            approval_note = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $currentUser, $reason, $planId);
    $ok = $stmt->execute();
    $stmt->close();

    return [
        'success' => $ok,
        'message' => $ok ? 'Đã từ chối đơn phép kế hoạch.' : 'Lỗi khi cập nhật trạng thái từ chối.'
    ];
}

/**
 * Hủy bỏ đơn phép kế hoạch
 */
function deleteLeavePlanRecord($conn, $planId) {
    $stmt = $conn->prepare("DELETE FROM leave_plans WHERE id = ?");
    $stmt->bind_param("i", $planId);
    $ok = $stmt->execute();
    $stmt->close();

    return [
        'success' => $ok,
        'message' => $ok ? 'Đã xóa bản ghi phép kế hoạch.' : 'Không thể xóa bản ghi.'
    ];
}

/**
 * Dashboard Quản Lý Phép Nghỉ (Tương tự Overtime Dashboard)
 */
function getLeaveDashboardData($conn, $month, $year) {
    $month = intval($month);
    $year  = intval($year);
    if ($month < 1 || $month > 12) $month = intval(date('m'));
    if ($year < 2020 || $year > 2030) $year = intval(date('Y'));

    $startDateMonth = sprintf('%04d-%02d-01', $year, $month);
    $endDateMonth   = date('Y-m-t', strtotime($startDateMonth));
    $startDateYear  = sprintf('%04d-01-01', $year);
    $endDateYear    = sprintf('%04d-12-31', $year);

    // 1. Tổng nhân sự đang làm việc
    $totalEmps = 0;
    $resTot = $conn->query("SELECT COUNT(*) as cnt FROM employees WHERE (resignation_date IS NULL OR resignation_date = '0000-00-00' OR resignation_date >= '{$startDateMonth}')");
    if ($resTot) $totalEmps = intval($resTot->fetch_assoc()['cnt']);

    // 2. Tổng ngày nghỉ thực tế trong tháng
    $sqlMonthAct = "
        SELECT 
            COUNT(DISTINCT employee_code) as emp_count,
            COUNT(*) as total_records,
            COALESCE(SUM(leave_days), 0) as total_days
        FROM leave_actuals 
        WHERE leave_date BETWEEN '{$startDateMonth}' AND '{$endDateMonth}'
    ";
    $resMonthAct = $conn->query($sqlMonthAct);
    $monthActRow = $resMonthAct ? $resMonthAct->fetch_assoc() : ['emp_count' => 0, 'total_records' => 0, 'total_days' => 0];

    // 3. Tổng ngày nghỉ thực tế trong năm (Lũy kế YTD)
    $sqlYearAct = "
        SELECT 
            COUNT(DISTINCT employee_code) as emp_count,
            COALESCE(SUM(leave_days), 0) as total_days
        FROM leave_actuals 
        WHERE leave_date BETWEEN '{$startDateYear}' AND '{$endDateYear}'
    ";
    $resYearAct = $conn->query($sqlYearAct);
    $yearActRow = $resYearAct ? $resYearAct->fetch_assoc() : ['emp_count' => 0, 'total_days' => 0];

    // 4. Thống kê kế hoạch tháng (Đã duyệt / Chờ duyệt / Từ chối)
    $sqlMonthPln = "
        SELECT 
            COUNT(*) as total_plans,
            COALESCE(SUM(CASE WHEN status = 'approved' THEN leave_days ELSE 0 END), 0) as approved_days,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
            COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) as approved_count
        FROM leave_plans 
        WHERE leave_date BETWEEN '{$startDateMonth}' AND '{$endDateMonth}'
    ";
    $resMonthPln = $conn->query($sqlMonthPln);
    $monthPlnRow = $resMonthPln ? $resMonthPln->fetch_assoc() : ['total_plans' => 0, 'approved_days' => 0, 'pending_count' => 0, 'approved_count' => 0];

    // 5. Xu hướng nghỉ phép 12 tháng trong năm (Monthly Trend)
    $monthlyTrendActual = array_fill(1, 12, 0.0);
    $monthlyTrendPlan   = array_fill(1, 12, 0.0);

    $sqlTrendAct = "
        SELECT MONTH(leave_date) as m, SUM(leave_days) as days
        FROM leave_actuals 
        WHERE leave_date BETWEEN '{$startDateYear}' AND '{$endDateYear}'
        GROUP BY MONTH(leave_date)
    ";
    $resTrendAct = $conn->query($sqlTrendAct);
    if ($resTrendAct) {
        while ($r = $resTrendAct->fetch_assoc()) {
            $monthlyTrendActual[intval($r['m'])] = round(floatval($r['days']), 1);
        }
    }

    $sqlTrendPln = "
        SELECT MONTH(leave_date) as m, SUM(leave_days) as days
        FROM leave_plans 
        WHERE leave_date BETWEEN '{$startDateYear}' AND '{$endDateYear}' AND status != 'rejected'
        GROUP BY MONTH(leave_date)
    ";
    $resTrendPln = $conn->query($sqlTrendPln);
    if ($resTrendPln) {
        while ($r = $resTrendPln->fetch_assoc()) {
            $monthlyTrendPlan[intval($r['m'])] = round(floatval($r['days']), 1);
        }
    }

    // 6. Tỷ lệ phân bổ theo nhóm làm việc (Dept Distribution) trong tháng
    $definedGroups = getEstablishedWorkGroups($conn);
    $deptData = [];
    foreach ($definedGroups as $dg) {
        $deptData[$dg['group_name']] = 0.0;
    }

    $sqlDept = "
        SELECT COALESCE(NULLIF(work_group, ''), 'Khác') as wg, SUM(leave_days) as days
        FROM leave_actuals 
        WHERE leave_date BETWEEN '{$startDateMonth}' AND '{$endDateMonth}'
        GROUP BY wg
    ";
    $resDept = $conn->query($sqlDept);
    if ($resDept) {
        while ($r = $resDept->fetch_assoc()) {
            $wg = $r['wg'];
            if (!isset($deptData[$wg])) $deptData[$wg] = 0.0;
            $deptData[$wg] += round(floatval($r['days']), 1);
        }
    }

    // 7. Cơ cấu loại hình nghỉ phép trong tháng
    $typeData = [];
    $sqlTypes = "
        SELECT COALESCE(NULLIF(leave_type, ''), 'Phép năm') as lt, SUM(leave_days) as days, COUNT(*) as cnt
        FROM leave_actuals 
        WHERE leave_date BETWEEN '{$startDateMonth}' AND '{$endDateMonth}'
        GROUP BY lt
        ORDER BY days DESC
    ";
    $resTypes = $conn->query($sqlTypes);
    if ($resTypes) {
        while ($r = $resTypes->fetch_assoc()) {
            $typeData[] = [
                'type_name'  => $r['lt'],
                'total_days' => round(floatval($r['days']), 1),
                'count'      => intval($r['cnt'])
            ];
        }
    }

    // 8. Top 10 nhân viên có số ngày nghỉ phép nhiều nhất trong năm
    $sqlTop10 = "
        SELECT 
            a.employee_code,
            COALESCE(e.full_name, a.full_name) as full_name,
            COALESCE(NULLIF(e.work_group, ''), a.work_group, 'Khác') as work_group,
            COALESCE(NULLIF(e.work_shift, ''), a.work_shift, 'Ca 1') as work_shift,
            COALESCE(e.cost_center, '') as cost_center,
            ROUND(SUM(a.leave_days), 1) as total_days_year,
            ROUND(SUM(CASE WHEN a.leave_date BETWEEN '{$startDateMonth}' AND '{$endDateMonth}' THEN a.leave_days ELSE 0 END), 1) as days_month
        FROM leave_actuals a
        LEFT JOIN employees e ON a.employee_code = e.employee_code
        WHERE a.leave_date BETWEEN '{$startDateYear}' AND '{$endDateYear}'
        GROUP BY a.employee_code
        ORDER BY total_days_year DESC
        LIMIT 10
    ";
    $resTop10 = $conn->query($sqlTop10);
    $topEmployees = [];
    if ($resTop10) {
        while ($r = $resTop10->fetch_assoc()) {
            $topEmployees[] = $r;
        }
    }

    // 9. Danh sách nhân viên cần chú ý (Nghỉ >= 8 ngày trong năm hoặc có nhiều ca nghỉ)
    $sqlCritical = "
        SELECT 
            a.employee_code,
            COALESCE(e.full_name, a.full_name) as full_name,
            COALESCE(NULLIF(e.work_group, ''), a.work_group, 'Khác') as work_group,
            COALESCE(NULLIF(e.work_shift, ''), a.work_shift, 'Ca 1') as work_shift,
            COALESCE(e.cost_center, '') as cost_center,
            ROUND(SUM(a.leave_days), 1) as total_days_year,
            ROUND(12.0 - SUM(a.leave_days), 1) as remaining_days,
            ROUND((SUM(a.leave_days) / 12.0) * 100, 1) as percent_limit
        FROM leave_actuals a
        LEFT JOIN employees e ON a.employee_code = e.employee_code
        WHERE a.leave_date BETWEEN '{$startDateYear}' AND '{$endDateYear}'
        GROUP BY a.employee_code
        HAVING total_days_year >= 6.0
        ORDER BY total_days_year DESC
        LIMIT 25
    ";
    $resCritical = $conn->query($sqlCritical);
    $criticalEmployees = [];
    if ($resCritical) {
        while ($r = $resCritical->fetch_assoc()) {
            $criticalEmployees[] = $r;
        }
    }

    return [
        'month' => $month,
        'year'  => $year,
        'kpi'   => [
            'total_employees'        => $totalEmps,
            'employees_with_leave'   => intval($monthActRow['emp_count']),
            'total_leave_days_month' => round(floatval($monthActRow['total_days']), 1),
            'total_leave_days_year'  => round(floatval($yearActRow['total_days']), 1),
            'planned_count'          => intval($monthPlnRow['total_plans']),
            'pending_plans'          => intval($monthPlnRow['pending_count']),
            'approved_plans'         => intval($monthPlnRow['approved_count']),
            'actual_records'         => intval($monthActRow['total_records'])
        ],
        'monthly_trend' => [
            'categories' => ['Thg 1', 'Thg 2', 'Thg 3', 'Thg 4', 'Thg 5', 'Thg 6', 'Thg 7', 'Thg 8', 'Thg 9', 'Thg 10', 'Thg 11', 'Thg 12'],
            'actual'     => array_values($monthlyTrendActual),
            'planned'    => array_values($monthlyTrendPlan)
        ],
        'dept_distribution' => [
            'labels' => array_keys($deptData),
            'series' => array_values($deptData)
        ],
        'type_distribution'  => $typeData,
        'leave_types'        => $typeData,
        'top_employees'      => $topEmployees,
        'critical_employees' => $criticalEmployees
    ];
}

/**
 * Tổng hợp toàn bộ dữ liệu nghỉ phép đã phê duyệt từ HRM (leave_actuals)
 */
function getHrmLeavesSummaryData($conn, $year, $month = 0, $group = '', $shift = '', $search = '', $page = 1, $limit = 50) {
    $year  = intval($year);
    $month = intval($month);
    $page  = max(1, intval($page));
    $limit = max(10, min(200, intval($limit)));
    $offset = ($page - 1) * $limit;

    if ($year < 2020 || $year > 2030) $year = intval(date('Y'));

    $where = "WHERE YEAR(a.leave_date) = {$year}";
    if ($month > 0 && $month <= 12) {
        $where .= " AND MONTH(a.leave_date) = {$month}";
    }
    if (!empty($group)) {
        $sGroup = $conn->real_escape_string($group);
        $where .= " AND (COALESCE(NULLIF(e.work_group, ''), a.work_group) = '{$sGroup}')";
    }
    if (!empty($shift)) {
        $sShift = $conn->real_escape_string($shift);
        $where .= " AND (COALESCE(NULLIF(e.work_shift, ''), a.work_shift) = '{$sShift}')";
    }
    if (!empty($search)) {
        $s = $conn->real_escape_string($search);
        $where .= " AND (a.employee_code LIKE '%{$s}%' OR a.full_name LIKE '%{$s}%' OR a.reason LIKE '%{$s}%')";
    }

    // Đếm tổng số bản ghi và tổng ngày nghỉ
    $sqlCount = "
        SELECT 
            COUNT(*) as total_rows,
            COALESCE(SUM(a.leave_days), 0) as total_leave_days,
            COUNT(DISTINCT a.employee_code) as distinct_employees
        FROM leave_actuals a
        LEFT JOIN employees e ON a.employee_code = e.employee_code
        {$where}
    ";
    $resCount = $conn->query($sqlCount);
    $statRow = $resCount ? $resCount->fetch_assoc() : ['total_rows' => 0, 'total_leave_days' => 0, 'distinct_employees' => 0];
    $totalRows = intval($statRow['total_rows']);
    $totalPages = ceil($totalRows / $limit);

    // Lấy danh sách phân trang
    $sqlList = "
        SELECT 
            a.*,
            COALESCE(NULLIF(e.work_group, ''), a.work_group, 'Khác') as final_work_group,
            COALESCE(NULLIF(e.work_shift, ''), a.work_shift, 'Ca 1') as final_work_shift,
            COALESCE(e.cost_center, '') as cost_center
        FROM leave_actuals a
        LEFT JOIN employees e ON a.employee_code = e.employee_code
        {$where}
        ORDER BY a.leave_date DESC, a.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    $resList = $conn->query($sqlList);
    $list = [];
    if ($resList) {
        while ($row = $resList->fetch_assoc()) {
            $list[] = $row;
        }
    }

    return [
        'data'  => $list,
        'total' => $totalRows,
        'summary' => [
            'total_rows'          => $totalRows,
            'total_leave_days'    => round(floatval($statRow['total_leave_days']), 1),
            'distinct_employees'  => intval($statRow['distinct_employees'])
        ],
        'pagination' => [
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'total_rows'   => $totalRows,
            'limit'        => $limit
        ]
    ];
}

/**
 * Tổng hợp thời gian nghỉ phép theo từng tháng (12 tháng) của các nhân sự
 */
function getMonthlyLeaveMatrixByEmployee($conn, $year, $group = '', $shift = '', $search = '') {
    $year = intval($year);
    if ($year < 2020 || $year > 2030) $year = intval(date('Y'));

    $where = "WHERE (e.resignation_date IS NULL OR e.resignation_date = '0000-00-00' OR YEAR(e.resignation_date) >= {$year})";
    if (!empty($group)) {
        $sGroup = $conn->real_escape_string($group);
        $where .= " AND (COALESCE(NULLIF(e.work_group, ''), l.work_group) = '{$sGroup}')";
    }
    if (!empty($shift)) {
        $sShift = $conn->real_escape_string($shift);
        $where .= " AND (COALESCE(NULLIF(e.work_shift, ''), l.work_shift) = '{$sShift}')";
    }
    if (!empty($search)) {
        $s = $conn->real_escape_string($search);
        $where .= " AND (e.employee_code LIKE '%{$s}%' OR e.full_name LIKE '%{$s}%')";
    }

    // 1. Lấy danh sách nhân viên
    $sqlEmps = "
        SELECT 
            e.employee_code,
            e.full_name,
            COALESCE(NULLIF(e.work_group, ''), l.work_group, 'Khác') as work_group,
            COALESCE(NULLIF(e.work_shift, ''), l.work_shift, 'Ca 1') as work_shift,
            COALESCE(e.cost_center, '') as cost_center
        FROM employees e
        LEFT JOIN (
            SELECT employee_code, work_group, work_shift 
            FROM leave_actuals 
            WHERE YEAR(leave_date) = {$year}
            GROUP BY employee_code
        ) l ON e.employee_code = l.employee_code
        {$where}
        ORDER BY work_group ASC, e.employee_code ASC
    ";
    $resEmps = $conn->query($sqlEmps);
    $employees = [];
    if ($resEmps) {
        while ($r = $resEmps->fetch_assoc()) {
            $employees[$r['employee_code']] = [
                'employee_code' => $r['employee_code'],
                'full_name'     => $r['full_name'],
                'work_group'    => $r['work_group'],
                'work_shift'    => $r['work_shift'],
                'cost_center'   => $r['cost_center'],
                'months'        => array_fill(1, 12, 0.0),
                'total_year'    => 0.0
            ];
        }
    }

    // 2. Lấy dữ liệu nghỉ từng tháng từ leave_actuals
    $sqlActuals = "
        SELECT 
            employee_code,
            MONTH(leave_date) as m,
            SUM(leave_days) as days
        FROM leave_actuals 
        WHERE YEAR(leave_date) = {$year}
        GROUP BY employee_code, MONTH(leave_date)
    ";
    $resAct = $conn->query($sqlActuals);
    if ($resAct) {
        while ($r = $resAct->fetch_assoc()) {
            $code = $r['employee_code'];
            $m    = intval($r['m']);
            $days = round(floatval($r['days']), 1);

            if (isset($employees[$code])) {
                $employees[$code]['months'][$m] = $days;
                $employees[$code]['total_year'] += $days;
            }
        }
    }

    // Tính tổng từng tháng cho toàn xưởng
    $monthlyTotals = array_fill(1, 12, 0.0);
    $grandTotal = 0.0;
    foreach ($employees as &$emp) {
        $emp['total_year'] = round($emp['total_year'], 1);
        for ($m = 1; $m <= 12; $m++) {
            $monthlyTotals[$m] += $emp['months'][$m];
            $grandTotal += $emp['months'][$m];
        }
    }
    unset($emp);

    return [
        'year'           => $year,
        'employees'      => array_values($employees),
        'monthly_totals' => array_map(function($v) { return round($v, 1); }, $monthlyTotals),
        'grand_total'    => round($grandTotal, 1)
    ];
}
