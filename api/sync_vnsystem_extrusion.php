<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(300); // 5 phút

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

try {
    requireApiPermission(['production.data', 'api.production.extrusion_sync']);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'code' => 403, 'message' => 'Bạn không có quyền thực hiện thao tác này!']);
    exit;
}

require_once __DIR__ . '/../vendor/SimpleXLSX.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_last_sync';
$currentUser = $_SESSION['user']['username'] ?? 'SYSTEM';
$userIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

require_once __DIR__ . '/../core/extrusion_service.php';

// Helper: Phân loại Size ống (Đồng bộ quy chuẩn toàn hệ thống)
function extractPipeSize($productCode) {
    return calculateExtrusionPipeSize($productCode);
}

// Helper: Parse ngày từ Excel
function parseExcelDate($val, $default = '') {
    if (empty($val)) return !empty($default) ? $default : date('Y-m-d');
    if (is_numeric($val) && floatval($val) > 20000 && floatval($val) < 80000) {
        return gmdate('Y-m-d', (intval($val) - 25569) * 86400);
    }
    $val = trim((string)$val);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    $ts = strtotime($val);
    return $ts ? date('Y-m-d', $ts) : (!empty($default) ? $default : date('Y-m-d'));
}

// Helper: Gọi xác thực VNSYSTEM lấy Bearer Token
function authenticateVnsystem($authUrl, $username, $password) {
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu VNSYSTEM không được để trống!'];
    }

    $boundary = '----WebKitFormBoundarylJ9qTDcFgyUrGhpS';
    $postData = "--" . $boundary . "\r\n" .
                "Content-Disposition: form-data; name=\"UserName\"\r\n\r\n" .
                $username . "\r\n" .
                "--" . $boundary . "\r\n" .
                "Content-Disposition: form-data; name=\"Password\"\r\n\r\n" .
                $password . "\r\n" .
                "--" . $boundary . "--\r\n";

    $ch = curl_init($authUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            "Content-Type: multipart/form-data; boundary=" . $boundary
        ]
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'message' => 'Không thể kết nối đến máy chủ VNSYSTEM Auth (Port 8490): ' . $curlError];
    }

    $json = json_decode($response, true);
    if ($json && isset($json['result']) && $json['result'] === true && !empty($json['message'])) {
        return [
            'success' => true,
            'token' => $json['message']
        ];
    }

    $msg = $json['message'] ?? 'Tài khoản hoặc mật khẩu VNSYSTEM không chính xác!';
    return ['success' => false, 'message' => $msg];
}

// Helper: Tải báo cáo Extrusion Report dạng nhị phân Excel
function downloadExtrusionReport($baseUrl, $token, $timeType, $timeParam, $destPath) {
    // Format URL: https://vnsystem.smcmfg.com.vn:8492/Plastic/ExtrusionReport?typeReport=Production+Performance&timeType=Month&time=2026-08&ProductionType=P
    $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';
    $url = $baseUrl . $separator . "typeReport=Production+Performance&timeType=" . urlencode($timeType) . "&time=" . urlencode($timeParam) . "&ProductionType=P";

    $fp = fopen($destPath, 'w+');
    if (!$fp) {
        return ['success' => false, 'message' => 'Không thể tạo file tạm để ghi dữ liệu tải về!'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_FILE => $fp,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token,
            "Content-Type: application/x-www-form-urlencoded"
        ]
    ]);

    curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($curlError) {
        if (file_exists($destPath)) @unlink($destPath);
        return ['success' => false, 'message' => 'Lỗi tải báo cáo từ VNSYSTEM (Port 8492): ' . $curlError];
    }

    if ($httpCode !== 200) {
        if (file_exists($destPath)) @unlink($destPath);
        return ['success' => false, 'message' => 'Máy chủ VNSYSTEM trả về mã lỗi HTTP: ' . $httpCode];
    }

    if (filesize($destPath) < 1000) {
        // File quá nhỏ, có thể là thông báo lỗi dạng HTML hoặc JSON
        $content = file_get_contents($destPath);
        if (strpos($content, '{') === 0) {
            $errJson = json_decode($content, true);
            if (file_exists($destPath)) @unlink($destPath);
            return ['success' => false, 'message' => $errJson['message'] ?? 'Báo cáo không khả dụng trên VNSYSTEM!'];
        }
    }

    return ['success' => true, 'path' => $destPath];
}

// Xử lý các action
try {
    switch ($action) {
        // -------------------------------------------------------------
        // ACTION 1: LẤY THÔNG TIN LẦN ĐỒNG BỘ GẦN NHẤT & CẤU HÌNH
        // -------------------------------------------------------------
        case 'get_last_sync':
            // Lấy log gần nhất
            $resLog = $conn->query("SELECT * FROM vnsystem_sync_logs WHERE sync_type = 'EXTRUSION_REPORT' ORDER BY id DESC LIMIT 1");
            $lastSync = $resLog ? $resLog->fetch_assoc() : null;

            // Lấy cấu hình VNSYSTEM
            $resCfg = $conn->query("SELECT system_code, system_name, auth_url, report_url, username, is_active, last_sync_at, last_sync_status FROM vnsystem_config WHERE system_code = 'VNSYSTEM' LIMIT 1");
            $config = $resCfg ? $resCfg->fetch_assoc() : null;

            $formattedLastSync = null;
            if ($lastSync) {
                $startDt = $lastSync['start_time'] ? date('d/m/Y H:i:s', strtotime($lastSync['start_time'])) : '';
                $endDt   = $lastSync['end_time'] ? date('d/m/Y H:i:s', strtotime($lastSync['end_time'])) : '';
                $formattedLastSync = [
                    'id'               => $lastSync['id'],
                    'time_type'        => $lastSync['time_type'],
                    'time_param'       => $lastSync['time_param'],
                    'start_time'       => $startDt,
                    'end_time'         => $endDt,
                    'duration_seconds' => $lastSync['duration_seconds'],
                    'total_fetched'    => (int)$lastSync['total_fetched'],
                    'total_inserted'   => (int)$lastSync['total_inserted'],
                    'total_updated'    => (int)$lastSync['total_updated'],
                    'total_errors'     => (int)$lastSync['total_errors'],
                    'status'           => $lastSync['status'],
                    'error_message'    => $lastSync['error_message'],
                    'synced_by'        => $lastSync['synced_by']
                ];
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'last_sync' => $formattedLastSync,
                'config' => [
                    'username' => $config['username'] ?? '',
                    'is_active' => (bool)($config['is_active'] ?? 1),
                    'last_sync_status' => $config['last_sync_status'] ?? ''
                ]
            ]);
            break;

        // -------------------------------------------------------------
        // ACTION 2: LẤY LỊCH SỬ CÁC LẦN ĐỒNG BỘ (PAGINATED)
        // -------------------------------------------------------------
        case 'get_history':
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(10, min(100, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            $countRes = $conn->query("SELECT COUNT(*) AS total FROM vnsystem_sync_logs WHERE sync_type = 'EXTRUSION_REPORT'");
            $total = $countRes ? (int)$countRes->fetch_assoc()['total'] : 0;

            $stmt = $conn->prepare("
                SELECT id, time_type, time_param, start_time, end_time, duration_seconds, 
                       total_fetched, total_inserted, total_updated, total_errors, status, 
                       error_message, synced_by, created_at 
                FROM vnsystem_sync_logs 
                WHERE sync_type = 'EXTRUSION_REPORT' 
                ORDER BY id DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $res = $stmt->get_result();

            $history = [];
            while ($r = $res->fetch_assoc()) {
                $history[] = [
                    'id'               => $r['id'],
                    'time_type'        => $r['time_type'],
                    'time_param'       => $r['time_param'],
                    'start_time'       => $r['start_time'] ? date('d/m/Y H:i:s', strtotime($r['start_time'])) : '',
                    'end_time'         => $r['end_time'] ? date('d/m/Y H:i:s', strtotime($r['end_time'])) : '',
                    'duration_seconds' => $r['duration_seconds'],
                    'total_fetched'    => (int)$r['total_fetched'],
                    'total_inserted'   => (int)$r['total_inserted'],
                    'total_updated'    => (int)$r['total_updated'],
                    'total_errors'     => (int)$r['total_errors'],
                    'status'           => $r['status'],
                    'error_message'    => $r['error_message'],
                    'synced_by'        => $r['synced_by']
                ];
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'data' => $history,
                'pagination' => [
                    'current_page' => $page,
                    'limit' => $limit,
                    'total_records' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            break;

        // -------------------------------------------------------------
        // ACTION 3: LƯU CẤU HÌNH TÀI KHOẢN VNSYSTEM
        // -------------------------------------------------------------
        case 'save_config':
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($username)) {
                throw new Exception('Vui lòng nhập Username VNSYSTEM!');
            }

            if (!empty($password)) {
                $stmt = $conn->prepare("UPDATE vnsystem_config SET username = ?, password_encrypted = ?, cached_token = NULL, token_expired_at = NULL WHERE system_code = 'VNSYSTEM'");
                $stmt->bind_param("ss", $username, $password);
            } else {
                $stmt = $conn->prepare("UPDATE vnsystem_config SET username = ? WHERE system_code = 'VNSYSTEM'");
                $stmt->bind_param("s", $username);
            }
            $stmt->execute();

            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Lưu cấu hình tài khoản VNSYSTEM thành công!']);
            break;

        // -------------------------------------------------------------
        // ACTION 4: THỰC HIỆN ĐỒNG BỘ DỮ LIỆU EXTRUSION REPORT (MAIN SYNC)
        // -------------------------------------------------------------
        case 'sync':
            $startTime = date('Y-m-d H:i:s');
            $microStart = microtime(true);

            $mode = $_POST['mode'] ?? 'month';
            $month = trim($_POST['month'] ?? '');
            $startDate = trim($_POST['start_date'] ?? '');
            $endDate = trim($_POST['end_date'] ?? '');
            $inputUser = trim($_POST['username'] ?? '');
            $inputPass = trim($_POST['password'] ?? '');

            // Xác định tham số thời gian
            if ($mode === 'month') {
                if (empty($month)) $month = date('Y-m');
                $timeParam = $month;
                $filterStart = "$month-01";
                $filterEnd = date("Y-m-t", strtotime($filterStart));
            } else {
                if (empty($startDate)) $startDate = date('Y-m-01');
                if (empty($endDate)) $endDate = date('Y-m-t');
                $timeParam = "$startDate ~ $endDate";
                $filterStart = $startDate;
                $filterEnd = $endDate;
                // Tháng truy vấn đại diện
                $month = substr($startDate, 0, 7);
            }

            // Ghi nhận trước 1 bản ghi Log ở trạng thái PENDING
            $stmtInitLog = $conn->prepare("
                INSERT INTO vnsystem_sync_logs (
                    sync_type, time_type, time_param, start_time, status, synced_by, ip_address
                ) VALUES ('EXTRUSION_REPORT', ?, ?, ?, 'PENDING', ?, ?)
            ");
            $modeUpper = strtoupper($mode);
            $stmtInitLog->bind_param("sssss", $modeUpper, $timeParam, $startTime, $currentUser, $userIp);
            $stmtInitLog->execute();
            $syncBatchId = $stmtInitLog->insert_id;
            $stmtInitLog->close();

            // Lấy thông tin cấu hình từ CSDL
            $resCfg = $conn->query("SELECT * FROM vnsystem_config WHERE system_code = 'VNSYSTEM' LIMIT 1");
            $cfg = $resCfg ? $resCfg->fetch_assoc() : null;

            $authUrl   = $cfg['auth_url'] ?? 'https://vnsystem.smcmfg.com.vn:8490/Login/SignInVerify';
            $reportUrl = $cfg['report_url'] ?? 'https://vnsystem.smcmfg.com.vn:8492/Plastic/ExtrusionReport';
            $username  = !empty($inputUser) ? $inputUser : ($cfg['username'] ?? '');
            $password  = !empty($inputPass) ? $inputPass : ($cfg['password_encrypted'] ?? '');

            $useSample = !empty($_POST['use_sample']) || !empty($_GET['use_sample']);
            $tempDir = __DIR__ . '/../data';
            if (!is_dir($tempDir)) @mkdir($tempDir, 0777, true);
            $tempFile = $tempDir . '/temp_vnsystem_extrusion_' . date('Ymd_His') . '_' . rand(100, 999) . '.xlsx';

            if ($useSample) {
                // Sử dụng file mẫu Extrusion report trong thư mục data/
                $samplePath = __DIR__ . '/../data/Extrusion report.xlsx';
                if (!file_exists($samplePath)) {
                    $samplePath = __DIR__ . '/../data/Extrusion Report Sample.xlsx';
                }
                if (!file_exists($samplePath)) {
                    throw new Exception('Không tìm thấy file mẫu Extrusion report trong thư mục data!');
                }
                copy($samplePath, $tempFile);
            } else {
                if (empty($username) || empty($password)) {
                    throw new Exception('Chưa cấu hình tài khoản hoặc mật khẩu đăng nhập VNSYSTEM! Vui lòng nhập thông tin xác thực.');
                }

                // Lưu tài khoản mới nếu người dùng nhập mới
                if (!empty($inputUser) && !empty($inputPass) && (!empty($_POST['save_credentials']))) {
                    $stmtSave = $conn->prepare("UPDATE vnsystem_config SET username = ?, password_encrypted = ? WHERE system_code = 'VNSYSTEM'");
                    $stmtSave->bind_param("ss", $inputUser, $inputPass);
                    $stmtSave->execute();
                }

                // BƯỚC 1: XÁC THỰC LẤY TOKEN (ƯU TIÊN DÙNG CACHED TOKEN)
                $bearerToken = '';
                $now = date('Y-m-d H:i:s');
                if (!empty($cfg['cached_token']) && !empty($cfg['token_expired_at']) && $cfg['token_expired_at'] > $now && empty($inputUser)) {
                    $bearerToken = $cfg['cached_token'];
                } else {
                    $authResult = authenticateVnsystem($authUrl, $username, $password);
                    if (!$authResult['success']) {
                        throw new Exception($authResult['message']);
                    }
                    $bearerToken = $authResult['token'];

                    // Lưu cached token sử dụng trong 55 phút
                    $expiredAt = date('Y-m-d H:i:s', time() + 55 * 60);
                    $stmtToken = $conn->prepare("UPDATE vnsystem_config SET cached_token = ?, token_expired_at = ? WHERE system_code = 'VNSYSTEM'");
                    $stmtToken->bind_param("ss", $bearerToken, $expiredAt);
                    $stmtToken->execute();
                }

                // BƯỚC 2: TẢI BÁO CÁO EXTRUSION REPORT TỪ VNSYSTEM
                // timeType='Month' và time='YYYY-MM'
                $dlResult = downloadExtrusionReport($reportUrl, $bearerToken, 'Month', $month, $tempFile);
                if (!$dlResult['success']) {
                    // Nếu lỗi token hết hạn, thử đăng nhập lại 1 lần nữa
                    $reAuth = authenticateVnsystem($authUrl, $username, $password);
                    if ($reAuth['success']) {
                        $bearerToken = $reAuth['token'];
                        $expiredAt = date('Y-m-d H:i:s', time() + 55 * 60);
                        $conn->query("UPDATE vnsystem_config SET cached_token = '{$bearerToken}', token_expired_at = '{$expiredAt}' WHERE system_code = 'VNSYSTEM'");
                        $dlResult = downloadExtrusionReport($reportUrl, $bearerToken, 'Month', $month, $tempFile);
                    }
                }

                if (!$dlResult['success']) {
                    throw new Exception($dlResult['message']);
                }
            }

            // BƯỚC 3: GIẢI MÃ EXCEL VÀ TIẾN HÀNH UPSERT
            $xlsx = \Shuchkin\SimpleXLSX::parse($tempFile);
            if (!$xlsx) {
                @unlink($tempFile);
                throw new Exception('Lỗi đọc dữ liệu Excel tải về: ' . \Shuchkin\SimpleXLSX::parseError());
            }

            $rows = $xlsx->rows();
            if (count($rows) <= 1) {
                @unlink($tempFile);
                throw new Exception('Dữ liệu Extrusion Report trả về từ VNSYSTEM trống!');
            }

            // Tự động tìm dòng Tiêu đề (header row)
            $headerRowIdx = -1;
            foreach ($rows as $rIdx => $rData) {
                $joined = implode(' ', array_map('strval', $rData));
                if (stripos($joined, 'Ngày SX') !== false || stripos($joined, 'Mã CTSX') !== false || stripos($joined, 'Mã sản phẩm') !== false || stripos($joined, 'Ngay SX') !== false) {
                    $headerRowIdx = $rIdx;
                    break;
                }
            }
            if ($headerRowIdx === -1) {
                $headerRowIdx = 0; // Fallback dòng 0
            }

            $conn->begin_transaction();

            // SQL INSERT 44 cột kèm UPSERT
            $sqlUpsert = "INSERT INTO extrusion_actual_logs (
                import_date, production_date, employee_code, employee_name, shift, 
                mfg_order_code, product_code, pipe_size, cost_center, process_name, 
                device_code, finished_qty_m, finished_qty_kg, ng_qty_kg, hard_waste_qty_kg, 
                total_weight_kg, material_code, regrind_count, regrind_package_code, lot_in, 
                total_downtime, total_runtime, cycle_time, machine_efficiency, mold_code, 
                spider_code, production_order_code, is_test, material_type, material_ng_qty, 
                lot_material_ng, hdpe_qty, lio_clean_qty, ti_clean_qty, bobbin_pl7_3_count, 
                bobbin_pl7_3_meters, bobbin_pl4_7_count, bobbin_pl4_7_meters, printer_type, ink_type, 
                waiting_machine_count, data_source, sync_batch_id, record_hash
            ) VALUES (" . implode(',', array_fill(0, 44, '?')) . ")
            ON DUPLICATE KEY UPDATE
                import_date         = VALUES(import_date),
                employee_code       = VALUES(employee_code),
                employee_name       = VALUES(employee_name),
                finished_qty_m      = VALUES(finished_qty_m),
                finished_qty_kg     = VALUES(finished_qty_kg),
                ng_qty_kg           = VALUES(ng_qty_kg),
                hard_waste_qty_kg   = VALUES(hard_waste_qty_kg),
                total_weight_kg     = VALUES(total_weight_kg),
                total_downtime      = VALUES(total_downtime),
                total_runtime       = VALUES(total_runtime),
                cycle_time          = VALUES(cycle_time),
                machine_efficiency  = VALUES(machine_efficiency),
                bobbin_pl7_3_count  = VALUES(bobbin_pl7_3_count),
                bobbin_pl7_3_meters = VALUES(bobbin_pl7_3_meters),
                bobbin_pl4_7_count  = VALUES(bobbin_pl4_7_count),
                bobbin_pl4_7_meters = VALUES(bobbin_pl4_7_meters),
                data_source         = VALUES(data_source),
                sync_batch_id       = VALUES(sync_batch_id),
                updated_at          = CURRENT_TIMESTAMP";

            $stmtUpsert = $conn->prepare($sqlUpsert);
            if (!$stmtUpsert) {
                throw new Exception('Lỗi SQL Prepare: ' . $conn->error);
            }

            $types = "sssssssssssdddddsissddidsssisdsdddididssisis"; // 44 params exact match

            $totalFetched = 0;
            $totalInserted = 0;
            $totalUpdated = 0;
            $totalErrors = 0;
            $dailyTotals = [];

            for ($i = $headerRowIdx + 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                if (empty($r[1]) && empty($r[5]) && empty($r[6])) continue;
                if (in_array(trim((string)($r[6] ?? '')), ['Product Code', '品番']) || in_array(trim((string)($r[25] ?? '')), ['Production Code', '生産コード'])) continue;

                $productionDate = parseExcelDate($r[1] ?? '');
                
                // Lọc theo khoảng ngày nếu người dùng chọn mode range
                if (!empty($filterStart) && $productionDate < $filterStart) continue;
                if (!empty($filterEnd) && $productionDate > $filterEnd) continue;

                $importDate     = parseExcelDate($r[0] ?? '', $productionDate);
                $employeeCode   = trim((string)($r[2] ?? ''));
                $employeeName   = trim((string)($r[3] ?? ''));
                $shift          = trim((string)($r[4] ?? ''));
                $mfgOrderCode   = trim((string)($r[5] ?? ''));
                $productCode    = trim((string)($r[6] ?? ''));
                $pipeSize       = extractPipeSize($productCode);
                $costCenter     = trim((string)($r[7] ?? ''));
                $processName    = trim((string)($r[8] ?? ''));
                $deviceCode     = trim((string)($r[9] ?? ''));
                $finishedQtyM   = (float)($r[10] ?? 0);
                $finishedQtyKg  = (float)($r[11] ?? 0);
                $ngQtyKg        = (float)($r[12] ?? 0);
                $hardWasteQtyKg = (float)($r[13] ?? 0);
                $totalWeightKg  = (float)($r[14] ?? 0);
                $materialCode   = trim((string)($r[15] ?? ''));
                $regrindCount   = (int)($r[16] ?? 0);
                $regrindPkgCode = trim((string)($r[17] ?? ''));
                $lotIn          = trim((string)($r[18] ?? ''));
                $totalDowntime  = (float)($r[19] ?? 0);
                $totalRuntime   = (float)($r[20] ?? 0);
                $cycleTime      = (int)($r[21] ?? 0);
                $efficiency     = (float)($r[22] ?? 0);
                $moldCode       = trim((string)($r[23] ?? ''));
                $spiderCode     = trim((string)($r[24] ?? ''));
                $prodOrderCode  = trim((string)($r[25] ?? ''));
                $isTest         = (int)($r[26] ?? 0);
                $materialType   = trim((string)($r[27] ?? ''));
                $materialNgQty  = (float)($r[28] ?? 0);
                $lotMaterialNg  = trim((string)($r[29] ?? ''));
                $hdpeQty        = (float)($r[30] ?? 0);
                $lioCleanQty    = (float)($r[31] ?? 0);
                $tiCleanQty     = (float)($r[32] ?? 0);
                $bobbinPl73Cnt  = (int)($r[33] ?? 0);
                $bobbinPl73M    = (float)($r[34] ?? 0);
                $bobbinPl47Cnt  = (int)($r[35] ?? 0);
                $bobbinPl47M    = (float)($r[36] ?? 0);
                $printerType    = trim((string)($r[37] ?? ''));
                $inkType        = trim((string)($r[38] ?? ''));
                $waitingMchCnt  = (int)($r[39] ?? 0);

                // Khóa nhận diện chống trùng lặp duy nhất
                $recordKey = "{$productionDate}|{$shift}|{$mfgOrderCode}|{$productCode}|{$deviceCode}|" . (!empty($prodOrderCode) ? $prodOrderCode : $employeeCode);
                $recordHash = md5($recordKey);
                $dataSource = 'VNSYSTEM';

                $stmtUpsert->bind_param($types,
                    $importDate, $productionDate, $employeeCode, $employeeName, $shift,
                    $mfgOrderCode, $productCode, $pipeSize, $costCenter, $processName,
                    $deviceCode, $finishedQtyM, $finishedQtyKg, $ngQtyKg, $hardWasteQtyKg,
                    $totalWeightKg, $materialCode, $regrindCount, $regrindPkgCode, $lotIn,
                    $totalDowntime, $totalRuntime, $cycleTime, $efficiency, $moldCode,
                    $spiderCode, $prodOrderCode, $isTest, $materialType, $materialNgQty,
                    $lotMaterialNg, $hdpeQty, $lioCleanQty, $tiCleanQty, $bobbinPl73Cnt,
                    $bobbinPl73M, $bobbinPl47Cnt, $bobbinPl47M, $printerType, $inkType,
                    $waitingMchCnt, $dataSource, $syncBatchId, $recordHash
                );

                if ($stmtUpsert->execute()) {
                    $totalFetched++;
                    $affected = $stmtUpsert->affected_rows;
                    if ($affected === 1) {
                        $totalInserted++;
                    } elseif ($affected >= 2) {
                        $totalUpdated++;
                    } else {
                        // affected == 0: dòng đã tồn tại và dữ liệu không thay đổi
                        $totalUpdated++;
                    }

                    // Tích lũy tổng hợp theo Ngày & Size ống
                    $ym = date('Y-m', strtotime($productionDate));
                    $day = (int)date('d', strtotime($productionDate));
                    $dailyTotals[$ym][$pipeSize][$day] = ($dailyTotals[$ym][$pipeSize][$day] ?? 0) + $finishedQtyM;
                } else {
                    $totalErrors++;
                }
            }

            // BƯỚC 4: CẬP NHẬT BẢNG TỔNG HỢP production_actuals
            $stmtAct = $conn->prepare("
                INSERT INTO `production_actuals` (`year_month`, `pipe_size`, `day`, `actual_qty`) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE `actual_qty` = VALUES(`actual_qty`)
            ");
            if ($stmtAct) {
                foreach ($dailyTotals as $ym => $sizeGroup) {
                    foreach ($sizeGroup as $size => $days) {
                        foreach ($days as $d => $qty) {
                            $stmtAct->bind_param("ssid", $ym, $size, $d, $qty);
                            $stmtAct->execute();
                        }
                    }
                }
                $stmtAct->close();
            }

            $conn->commit();

            // Đồng bộ dữ liệu sang extrusion_productions (lấy extrusion_actual_logs làm Single Source of Truth)
            syncExtrusionLogsToProductions($conn);

            // Xóa file tạm
            if (file_exists($tempFile)) @unlink($tempFile);

            $endTime = date('Y-m-d H:i:s');
            $duration = round(microtime(true) - $microStart, 2);

            // BƯỚC 5: CẬP NHẬT LOG THÀNH CÔNG
            $stmtUpdLog = $conn->prepare("
                UPDATE vnsystem_sync_logs 
                SET end_time = ?, duration_seconds = ?, total_fetched = ?, 
                    total_inserted = ?, total_updated = ?, total_errors = ?, status = 'SUCCESS' 
                WHERE id = ?
            ");
            $stmtUpdLog->bind_param("sdiiiii", $endTime, $duration, $totalFetched, $totalInserted, $totalUpdated, $totalErrors, $syncBatchId);
            $stmtUpdLog->execute();
            $stmtUpdLog->close();

            // Cập nhật trạng thái config
            $conn->query("UPDATE vnsystem_config SET last_sync_at = '{$endTime}', last_sync_status = 'SUCCESS' WHERE system_code = 'VNSYSTEM'");

            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => "Đồng bộ VNSYSTEM thành công! Tổng số: {$totalFetched} bản ghi (Thêm mới: {$totalInserted}, Cập nhật: {$totalUpdated}) trong {$duration}s.",
                'summary' => [
                    'batch_id'       => $syncBatchId,
                    'time_param'     => $timeParam,
                    'total_fetched'  => $totalFetched,
                    'total_inserted' => $totalInserted,
                    'total_updated'  => $totalUpdated,
                    'total_errors'   => $totalErrors,
                    'duration'       => $duration,
                    'sync_time'      => date('d/m/Y H:i:s', strtotime($endTime)),
                    'synced_by'      => $currentUser
                ]
            ]);
            break;

        default:
            throw new Exception("Hành động không hợp lệ: $action");
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }

    $errMsg = $e->getMessage();
    $endTime = date('Y-m-d H:i:s');
    $duration = isset($microStart) ? round(microtime(true) - $microStart, 2) : 0;

    // Ghi log thất bại
    if (!empty($syncBatchId)) {
        $stmtFail = $conn->prepare("
            UPDATE vnsystem_sync_logs 
            SET end_time = ?, duration_seconds = ?, status = 'FAILED', error_message = ? 
            WHERE id = ?
        ");
        $stmtFail->bind_param("sdsi", $endTime, $duration, $errMsg, $syncBatchId);
        $stmtFail->execute();
        $stmtFail->close();
    }

    // Cập nhật config status
    $conn->query("UPDATE vnsystem_config SET last_sync_at = '{$endTime}', last_sync_status = 'FAILED' WHERE system_code = 'VNSYSTEM'");

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $errMsg
    ]);
}
?>
