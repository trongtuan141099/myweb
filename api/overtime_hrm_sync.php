<?php
/**
 * API Tự Động Kết Nối & Đồng Bộ Dữ Liệu Tăng Ca Từ Đa Tài Khoản HRM
 * DX Plastic Group - Overtime Management System
 */
header('Content-Type: application/json; charset=utf-8');

// Thiết lập múi giờ Việt Nam GMT+7
date_default_timezone_set('Asia/Ho_Chi_Minh');

$isCli = (php_sapi_name() === 'cli') || (isset($argv) && count($argv) > 1);

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

// Kiểm tra quyền nếu chạy qua web request
if (!$isCli) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    requireApiPermission('api.overtime.hrm_sync');
}

$configFile = __DIR__ . '/../config/hrm_sync_config.json';
$action = $_GET['action'] ?? ($_POST['action'] ?? ($isCli ? 'cron' : ''));

/**
 * Format ngày theo chuẩn yyyy-mm-dd
 */
if (!function_exists('formatHrmDate')) {
function formatHrmDate($d, $default) {
    if (empty($d)) return $default;
    $d = trim($d);
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $d, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        return $d;
    }
    return $default;
}
}

/**
 * Format hiển thị ngày giờ Việt Nam DD/MM/YYYY HH:mm:ss
 */
if (!function_exists('formatVnDateTime')) {
function formatVnDateTime($dtStr) {
    if (empty($dtStr)) return '-';
    $ts = strtotime($dtStr);
    if (!$ts) return $dtStr;
    return date('d/m/Y H:i:s', $ts);
}
}

/**
 * Nạp cấu hình toàn cục từ file JSON
 */
if (!function_exists('loadGlobalHrmConfig')) {
function loadGlobalHrmConfig($configFile) {
    $default = [
        'hrm_url' => 'https://hrm.smcmfg.com.vn',
        'login_endpoint' => 'https://hrm.smcmfg.com.vn/login',
        'portal_endpoint' => 'https://hrm.smcmfg.com.vn/TangCa/DuyetTangCaKeHoach',
        'plan_export_endpoint' => 'https://hrm.smcmfg.com.vn/Portal/TangCa/ExportDuyetTangCaKeHoach',
        'actual_export_endpoint' => 'https://hrm.smcmfg.com.vn/Portal/TangCa/ExportDuyetTangCaThucTe',
        'download_endpoint' => 'https://hrm.smcmfg.com.vn/Download/ExcelDownload',
        'sync_interval_hours' => 3,
        'auto_sync_enabled' => true,
        'sync_date_from' => '',
        'sync_date_to' => '',
        'last_sync_time' => null,
        'last_sync_status' => 'never_run',
        'last_sync_message' => 'Chưa từng thực hiện đồng bộ.',
        'last_sync_stats' => [
            'plan_inserted' => 0, 'plan_updated' => 0, 'plan_errors' => 0,
            'actual_inserted' => 0, 'actual_updated' => 0, 'actual_errors' => 0
        ]
    ];

    if (!file_exists($configFile)) {
        file_put_contents($configFile, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $default;
    }

    $data = json_decode(file_get_contents($configFile), true);
    if (!is_array($data)) return $default;
    return array_merge($default, $data);
}
}

/**
 * Lưu cấu hình toàn cục vào file JSON
 */
if (!function_exists('saveGlobalHrmConfig')) {
function saveGlobalHrmConfig($configFile, $config) {
    return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}
}

/**
 * Lấy danh sách các tài khoản HRM từ database ot_hrm_accounts
 */
if (!function_exists('getHrmAccounts')) {
function getHrmAccounts($conn, $activeOnly = false) {
    $sql = "SELECT * FROM ot_hrm_accounts";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY id ASC";
    $res = $conn->query($sql);
    $accounts = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $accounts[] = $row;
        }
    }
    return $accounts;
}
}

/**
 * Gửi yêu cầu HTTP POST với cURL
 */
if (!function_exists('postHrmRequest')) {
function postHrmRequest($url, $postFields, $cookieFile, $referer) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 35,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . $referer
        ],
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'raw'       => $raw,
        'error'     => $err,
        'json'      => json_decode($raw, true)
    ];
}
}

/**
 * Tải file streaming từ HRM về máy chủ
 */
if (!function_exists('downloadHrmFileStream')) {
function downloadHrmFileStream($url, $destinationFile, $cookieFile) {
    $fp = fopen($destinationFile, 'w+');
    if (!$fp) return false;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $fp,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    return ($httpCode === 200 && file_exists($destinationFile) && filesize($destinationFile) > 100);
}
}

/**
 * Thực hiện đồng bộ 01 tài khoản HRM cụ thể
 */
if (!function_exists('syncSingleHrmAccount')) {
function syncSingleHrmAccount($conn, $account, $dateFrom, $dateTo, $currentUser = 'HRM_AUTO_SYNC') {
    $accId = intval($account['id']);
    $username = trim($account['username']);
    $password = trim($account['password']);
    $accName = $account['account_name'] ?? "Tài khoản {$username}";

    $cookieFile = __DIR__ . "/../data/hrm_cookie_{$accId}.txt";
    $planFile = __DIR__ . "/../data/hrm_sync_plan_{$accId}.xlsx";
    $actualFile = __DIR__ . "/../data/hrm_sync_actual_{$accId}.xlsx";

    $steps = [];
    $urlsCalled = [];

    $addStep = function($num, $title, $method, $url, $status, $detail, $httpCode = null) use (&$steps, &$urlsCalled, $accName) {
        $steps[] = [
            'account'   => $accName,
            'step'      => $num,
            'title'     => $title,
            'method'    => $method,
            'url'       => $url,
            'status'    => $status, // 'success', 'warning', 'error'
            'http_code' => $httpCode,
            'detail'    => $detail,
            'time'      => date('H:i:s')
        ];
        if (!empty($url)) {
            $formatted = ($method ? strtoupper($method) . ' ' : '') . $url;
            if (!in_array($formatted, $urlsCalled)) {
                $urlsCalled[] = $formatted;
            }
        }
    };

    if (empty($username) || empty($password)) {
        $addStep(0, "Kiểm tra thông tin tài khoản [{$accName}]", 'LOCAL', '', 'error', 'Chưa cấu hình tài khoản hoặc mật khẩu.');
        return [
            'success'     => false,
            'account_id'  => $accId,
            'account_name'=> $accName,
            'message'     => "Tài khoản [{$accName}] chưa có username hoặc password.",
            'steps'       => $steps
        ];
    }

    $cookieDir = dirname($cookieFile);
    if (!file_exists($cookieDir)) @mkdir($cookieDir, 0777, true);
    if (file_exists($cookieFile)) @unlink($cookieFile);
    if (file_exists($planFile)) @unlink($planFile);
    if (file_exists($actualFile)) @unlink($actualFile);

    // 1. Bước 1: Khởi tạo phiên, lấy Cookie và Verification Token từ trang /login
    $loginUrl = !empty($account['login_endpoint']) ? $account['login_endpoint'] : 'https://hrm.smcmfg.com.vn/login';
    $ch = curl_init($loginUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    $loginHtml = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || empty($loginHtml)) {
        $errMsg = "Không thể kết nối đến máy chủ HRM (HTTP: {$httpCode}). " . ($curlErr ? 'Chi tiết: ' . $curlErr : '');
        $addStep(1, "Khởi tạo phiên [{$accName}]", 'GET', $loginUrl, 'error', $errMsg, $httpCode);
        return [
            'success'     => false,
            'account_id'  => $accId,
            'account_name'=> $accName,
            'message'     => $errMsg,
            'steps'       => $steps
        ];
    }

    $loginToken = '';
    if (preg_match('/<input\b[^>]*name=["\']__RequestVerificationToken["\'][^>]*value=["\']([^"\']*)["\']/i', $loginHtml, $match)) {
        $loginToken = $match[1];
    }
    $addStep(1, "Khởi tạo phiên [{$accName}]", 'GET', $loginUrl, 'success', 'Kết nối thành công. Nhận Login Token (' . substr($loginToken, 0, 10) . '...).', $httpCode);

    // 2. Bước 2: Đăng nhập
    $authUrl = 'https://hrm.smcmfg.com.vn/Account/GetLogin_KiemTraLoginLanDau';
    $salt = substr(base64_encode(date('D M d Y H:i:s') . ' GMT+0700 (Indochina Time)'), 0, 5);
    $encodedPassword = $salt . base64_encode($password);

    $postFields = [
        'Username'                  => $username,
        'Token'                     => $encodedPassword,
        'returnUrl'                 => '/login',
        'gRecaptchaResponse'        => '',
        'Remember'                  => 'true',
        'IsCaptcha'                 => 'false',
        '__RequestVerificationToken' => $loginToken
    ];

    $ch = curl_init($authUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_HTTPHEADER     => [
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . $loginUrl
        ],
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    $fullLoginResp = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $loginRespHeaders = substr($fullLoginResp, 0, $headerSize);
    $loginRespRaw = substr($fullLoginResp, $headerSize);

    if (preg_match_all('/^set-cookie:\s*([^=;]+)=([^;]+)/im', $loginRespHeaders, $cookieMatches, PREG_SET_ORDER)) {
        $extraCookies = "";
        foreach ($cookieMatches as $cm) {
            $cName = trim($cm[1]);
            $cVal = trim($cm[2]);
            $extraCookies .= "#HttpOnly_hrm.smcmfg.com.vn\tFALSE\t/\tTRUE\t2147483647\t{$cName}\t{$cVal}\n";
        }
        file_put_contents($cookieFile, $extraCookies, FILE_APPEND);
    }

    $loginResp = json_decode($loginRespRaw, true);
    if (!is_array($loginResp) || ($loginResp['StatusCode'] ?? 0) != 1) {
        $errMsg = $loginResp['StatusText'] ?? ($curlErr ?: 'Sai tên tài khoản hoặc mật khẩu HRM.');
        $addStep(2, "Xác thực [{$accName}]", 'POST', $authUrl, 'error', 'Đăng nhập không thành công: ' . $errMsg, $loginHttpCode);
        return [
            'success'     => false,
            'account_id'  => $accId,
            'account_name'=> $accName,
            'message'     => "Đăng nhập [{$accName}] không thành công: " . $errMsg,
            'steps'       => $steps
        ];
    }
    $addStep(2, "Xác thực [{$accName}]", 'POST', $authUrl, 'success', "Đăng nhập thành công với tài khoản [{$username}].", $loginHttpCode);

    // 3. Bước 3: Lấy Portal Token
    $portalUrl = !empty($account['portal_endpoint']) ? $account['portal_endpoint'] : 'https://hrm.smcmfg.com.vn/TangCa/DuyetTangCaKeHoach';
    $chPortal = curl_init($portalUrl);
    curl_setopt_array($chPortal, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    $portalHtml = curl_exec($chPortal);
    $portalHttpCode = curl_getinfo($chPortal, CURLINFO_HTTP_CODE);
    curl_close($chPortal);

    $portalToken = '';
    if (!empty($portalHtml) && preg_match('/<input\b[^>]*name=["\']__RequestVerificationToken["\'][^>]*value=["\']([^"\']*)["\']/i', $portalHtml, $matchPortal)) {
        $portalToken = $matchPortal[1];
    }
    if (empty($portalToken)) $portalToken = $loginToken;
    $addStep(3, "Trích xuất Portal Token [{$accName}]", 'GET', $portalUrl, 'success', 'Token sẵn sàng cho lệnh xuất.', $portalHttpCode);

    // 4. Bước 4: Xuất & Tải Kế hoạch
    $tuNgay = formatHrmDate($dateFrom, date('Y-01-01'));
    $denNgay = formatHrmDate($dateTo, date('Y-12-31'));

    $planExportUrl = !empty($account['plan_export_endpoint']) ? $account['plan_export_endpoint'] : 'https://hrm.smcmfg.com.vn/Portal/TangCa/ExportDuyetTangCaKeHoach';
    $planPostData = ['TuNgay' => $tuNgay, 'DenNgay' => $denNgay, '__RequestVerificationToken' => $portalToken];
    $planExportRes = postHrmRequest($planExportUrl, $planPostData, $cookieFile, $portalUrl);

    $planDownloadSuccess = false;
    if (!empty($planExportRes['json']) && ($planExportRes['json']['StatusCode'] ?? 0) > 0) {
        $downloadId = $planExportRes['json']['StatusText'];
        $planDownloadUrl = 'https://hrm.smcmfg.com.vn/Download/ExcelDownload?id=' . urlencode($downloadId) . '&name=' . urlencode('Danh sách Duyệt tăng ca kế hoạch');
        $planDownloadSuccess = downloadHrmFileStream($planDownloadUrl, $planFile, $cookieFile);
        if ($planDownloadSuccess) {
            $addStep(4, "Tải file Kế Hoạch [{$accName}]", 'GET', $planDownloadUrl, 'success', 'Tải file Kế hoạch thành công (' . round(filesize($planFile)/1024, 1) . ' KB).', 200);
        } else {
            $addStep(4, "Tải file Kế Hoạch [{$accName}]", 'GET', $planDownloadUrl, 'warning', 'Không tải được file Kế hoạch.');
        }
    } else {
        $addStep(4, "Yêu cầu Kế Hoạch [{$accName}]", 'POST', $planExportUrl, 'warning', 'HRM phản hồi: ' . ($planExportRes['json']['StatusText'] ?? 'Không có dữ liệu kế hoạch'));
    }

    // 5. Bước 5: Xuất & Tải Thực tế
    $actExportUrl = !empty($account['actual_export_endpoint']) ? $account['actual_export_endpoint'] : 'https://hrm.smcmfg.com.vn/Portal/TangCa/ExportDuyetTangCaThucTe';
    $actPostData = ['TuNgay' => $tuNgay, 'DenNgay' => $denNgay, '__RequestVerificationToken' => $portalToken];
    $actExportRes = postHrmRequest($actExportUrl, $actPostData, $cookieFile, 'https://hrm.smcmfg.com.vn/TangCa/DuyetTangCaThucTe');

    $actDownloadSuccess = false;
    if (!empty($actExportRes['json']) && ($actExportRes['json']['StatusCode'] ?? 0) > 0) {
        $downloadId = $actExportRes['json']['StatusText'];
        $actDownloadUrl = 'https://hrm.smcmfg.com.vn/Download/ExcelDownload?id=' . urlencode($downloadId) . '&name=' . urlencode('Danh sách duyệt tăng ca thực tế');
        $actDownloadSuccess = downloadHrmFileStream($actDownloadUrl, $actualFile, $cookieFile);
        if ($actDownloadSuccess) {
            $addStep(5, "Tải file Thực Tế [{$accName}]", 'GET', $actDownloadUrl, 'success', 'Tải file Thực tế thành công (' . round(filesize($actualFile)/1024, 1) . ' KB).', 200);
        } else {
            $addStep(5, "Tải file Thực Tế [{$accName}]", 'GET', $actDownloadUrl, 'warning', 'Không tải được file Thực tế.');
        }
    } else {
        $addStep(5, "Yêu cầu Thực Tế [{$accName}]", 'POST', $actExportUrl, 'warning', 'HRM phản hồi: ' . ($actExportRes['json']['StatusText'] ?? 'Không có dữ liệu thực tế'));
    }

    $planValid = file_exists($planFile) && filesize($planFile) > 100 && (substr(file_get_contents($planFile, false, null, 0, 2), 0, 2) === 'PK');
    $actValid = file_exists($actualFile) && filesize($actualFile) > 100 && (substr(file_get_contents($actualFile, false, null, 0, 2), 0, 2) === 'PK');

    if (!$planValid && !$actValid) {
        $errMsg = "Không thể lấy file Excel hợp lệ từ tài khoản [{$accName}].";
        $addStep(6, "Kiểm tra file Excel [{$accName}]", 'LOCAL', '', 'error', $errMsg);
        return [
            'success'     => false,
            'account_id'  => $accId,
            'account_name'=> $accName,
            'message'     => $errMsg,
            'steps'       => $steps
        ];
    }

    // 6. Bước 6: UPSERT vào CSDL
    $planResult = ['inserted_rows' => 0, 'updated_rows' => 0, 'error_rows' => 0];
    $actResult = ['inserted_rows' => 0, 'updated_rows' => 0, 'error_rows' => 0];

    if ($planValid) {
        $planResult = processExcelImport($conn, $planFile, "DanhSachDuyetTangCaKeHoach_HRM_{$username}.xlsx", 'plan', $currentUser);
        $addStep(6, "UPSERT Kế hoạch [{$accName}]", 'LOCAL', '', 'success', "+{$planResult['inserted_rows']} mới, sửa {$planResult['updated_rows']} dòng, {$planResult['error_rows']} lỗi.");
    }

    if ($actValid) {
        $actResult = processExcelImport($conn, $actualFile, "DanhSachDuyetTangCaThucTe_HRM_{$username}.xlsx", 'actual', $currentUser);
        $addStep(7, "UPSERT Thực tế [{$accName}]", 'LOCAL', '', 'success', "+{$actResult['inserted_rows']} mới, sửa {$actResult['updated_rows']} dòng, {$actResult['error_rows']} lỗi.");
    }

    // Cập nhật trạng thái và thời gian đồng bộ cho tài khoản này (Giờ Việt Nam GMT+7)
    $nowVn = date('Y-m-d H:i:s');
    $accStats = [
        'plan_inserted'   => $planResult['inserted_rows'] ?? 0,
        'plan_updated'    => $planResult['updated_rows'] ?? 0,
        'plan_errors'     => $planResult['error_rows'] ?? 0,
        'actual_inserted' => $actResult['inserted_rows'] ?? 0,
        'actual_updated'  => $actResult['updated_rows'] ?? 0,
        'actual_errors'   => $actResult['error_rows'] ?? 0,
    ];
    $statsJson = json_encode($accStats);
    $accMsg = "Đồng bộ thành công: KH (+{$accStats['plan_inserted']}/{$accStats['plan_updated']}), TT (+{$accStats['actual_inserted']}/{$accStats['actual_updated']})";

    $stmtUp = $conn->prepare("UPDATE ot_hrm_accounts SET last_sync_time = ?, last_sync_status = 'success', last_sync_message = ?, last_sync_stats = ? WHERE id = ?");
    $stmtUp->bind_param("sssi", $nowVn, $accMsg, $statsJson, $accId);
    $stmtUp->execute();
    $stmtUp->close();

    return [
        'success'         => true,
        'account_id'      => $accId,
        'account_name'    => $accName,
        'last_sync_time'  => $nowVn,
        'last_sync_time_formatted' => formatVnDateTime($nowVn),
        'message'         => $accMsg,
        'steps'           => $steps,
        'plan_result'     => $planResult,
        'actual_result'   => $actResult
    ];
}
}

try {
    switch ($action) {
        // =====================================================================
        // 1. LẤY CẤU HÌNH & DANH SÁCH TÀI KHOẢN HRM
        // =====================================================================
        case 'get_config':
            $globalCfg = loadGlobalHrmConfig($configFile);
            $accounts = getHrmAccounts($conn, false);

            $safeAccounts = [];
            foreach ($accounts as $acc) {
                $hasPwd = !empty($acc['password']);
                $stats = !empty($acc['last_sync_stats']) ? json_decode($acc['last_sync_stats'], true) : null;
                $safeAccounts[] = [
                    'id'                     => intval($acc['id']),
                    'account_name'           => $acc['account_name'],
                    'username'               => $acc['username'],
                    'has_password'           => $hasPwd,
                    'is_active'              => intval($acc['is_active']) === 1,
                    'last_sync_time'         => $acc['last_sync_time'],
                    'last_sync_time_formatted' => formatVnDateTime($acc['last_sync_time']),
                    'last_sync_status'       => $acc['last_sync_status'] ?? 'never_run',
                    'last_sync_message'      => $acc['last_sync_message'] ?? '',
                    'last_sync_stats'        => $stats,
                    'portal_endpoint'        => $acc['portal_endpoint'],
                    'plan_export_endpoint'   => $acc['plan_export_endpoint'],
                    'actual_export_endpoint' => $acc['actual_export_endpoint'],
                ];
            }

            echo json_encode([
                'success'       => true,
                'accounts'      => $safeAccounts,
                'global_config' => [
                    'auto_sync_enabled'        => $globalCfg['auto_sync_enabled'] ?? true,
                    'sync_interval_hours'      => $globalCfg['sync_interval_hours'] ?? 3,
                    'sync_date_from'           => $globalCfg['sync_date_from'] ?? '',
                    'sync_date_to'             => $globalCfg['sync_date_to'] ?? '',
                    'last_sync_time'           => $globalCfg['last_sync_time'] ?? null,
                    'last_sync_time_formatted' => formatVnDateTime($globalCfg['last_sync_time'] ?? null),
                    'last_sync_status'         => $globalCfg['last_sync_status'] ?? 'never_run',
                    'last_sync_message'        => $globalCfg['last_sync_message'] ?? ''
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. LƯU THÔNG TIN TÀI KHOẢN (THÊM MỚI HOẶC CẬP NHẬT)
        // =====================================================================
        case 'save_account':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $id = !empty($input['id']) ? intval($input['id']) : 0;
            $accountName = trim($input['account_name'] ?? '');
            $username = trim($input['username'] ?? '');
            $password = trim($input['password'] ?? '');
            $isActive = isset($input['is_active']) ? (filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : 1;

            if (empty($accountName) || empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Tên tài khoản và Tên đăng nhập HRM.']);
                exit;
            }

            if ($id > 0) {
                // Kiểm tra tài khoản tồn tại
                $stmtChk = $conn->prepare("SELECT password FROM ot_hrm_accounts WHERE id = ?");
                $stmtChk->bind_param("i", $id);
                $stmtChk->execute();
                $existing = $stmtChk->get_result()->fetch_assoc();
                $stmtChk->close();

                if (!$existing) {
                    echo json_encode(['success' => false, 'message' => 'Tài khoản không tồn tại.']);
                    exit;
                }

                // Nếu không nhập pass mới hoặc pass là masked -> giữ pass cũ
                if (empty($password) || $password === '••••••••') {
                    $password = $existing['password'];
                }

                $stmtUp = $conn->prepare("UPDATE ot_hrm_accounts SET account_name = ?, username = ?, password = ?, is_active = ? WHERE id = ?");
                $stmtUp->bind_param("sssii", $accountName, $username, $password, $isActive, $id);
                $stmtUp->execute();
                $stmtUp->close();

                echo json_encode(['success' => true, 'message' => "Đã cập nhật thông tin tài khoản [{$accountName}] thành công!"]);
            } else {
                // Thêm mới tài khoản
                if (empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mật khẩu cho tài khoản mới.']);
                    exit;
                }

                // Kiểm tra trùng username
                $stmtDup = $conn->prepare("SELECT id FROM ot_hrm_accounts WHERE username = ?");
                $stmtDup->bind_param("s", $username);
                $stmtDup->execute();
                if ($stmtDup->get_result()->fetch_assoc()) {
                    $stmtDup->close();
                    echo json_encode(['success' => false, 'message' => "Tài khoản HRM với mã [{$username}] đã tồn tại trong hệ thống."]);
                    exit;
                }
                $stmtDup->close();

                $stmtIns = $conn->prepare("INSERT INTO ot_hrm_accounts (account_name, username, password, is_active) VALUES (?, ?, ?, ?)");
                $stmtIns->bind_param("sssi", $accountName, $username, $password, $isActive);
                $stmtIns->execute();
                $newId = $stmtIns->insert_id;
                $stmtIns->close();

                echo json_encode(['success' => true, 'id' => $newId, 'message' => "Đã thêm mới tài khoản [{$accountName}] thành công!"]);
            }
            break;

        // =====================================================================
        // 3. XÓA TÀI KHOẢN HRM
        // =====================================================================
        case 'delete_account':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $id = intval($input['id'] ?? ($_GET['id'] ?? 0));

            // Kiểm tra số lượng tài khoản tối thiểu
            $totalAcc = intval($conn->query("SELECT COUNT(*) FROM ot_hrm_accounts")->fetch_row()[0]);
            if ($totalAcc <= 1) {
                echo json_encode(['success' => false, 'message' => 'Hệ thống cần duy trì tối thiểu 01 tài khoản HRM, không thể xóa tài khoản cuối cùng.']);
                exit;
            }

            $stmtDel = $conn->prepare("DELETE FROM ot_hrm_accounts WHERE id = ?");
            $stmtDel->bind_param("i", $id);
            $stmtDel->execute();
            $stmtDel->close();

            echo json_encode(['success' => true, 'message' => 'Đã xóa tài khoản HRM thành công!']);
            break;

        // =====================================================================
        // 4. BẬT / TẮT TRẠNG THÁI HOẠT ĐỘNG
        // =====================================================================
        case 'toggle_account_status':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $id = intval($input['id'] ?? 0);
            $isActive = !empty($input['is_active']) ? 1 : 0;

            $stmtTog = $conn->prepare("UPDATE ot_hrm_accounts SET is_active = ? WHERE id = ?");
            $stmtTog->bind_param("ii", $isActive, $id);
            $stmtTog->execute();
            $stmtTog->close();

            echo json_encode(['success' => true, 'message' => 'Đã cập nhật trạng thái tài khoản.']);
            break;

        // =====================================================================
        // 5. LƯU CẤU HÌNH TOÀN CỤC (CHU KỲ & THỜI GIAN ĐỒNG BỘ)
        // =====================================================================
        case 'save_config':
        case 'save_global_config':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $cfg = loadGlobalHrmConfig($configFile);
            if (isset($input['sync_interval_hours'])) {
                $cfg['sync_interval_hours'] = max(1, min(24, intval($input['sync_interval_hours'])));
            }
            if (isset($input['auto_sync_enabled'])) {
                $cfg['auto_sync_enabled'] = filter_var($input['auto_sync_enabled'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($input['sync_date_from'])) $cfg['sync_date_from'] = trim($input['sync_date_from']);
            if (isset($input['sync_date_to'])) $cfg['sync_date_to'] = trim($input['sync_date_to']);

            saveGlobalHrmConfig($configFile, $cfg);

            echo json_encode([
                'success' => true,
                'message' => 'Đã lưu cấu hình chu kỳ và thời gian tự động đồng bộ HRM thành công!',
                'sync_interval_hours' => $cfg['sync_interval_hours'],
                'auto_sync_enabled'   => $cfg['auto_sync_enabled']
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 6. KÍCH HOẠT ĐỒNG BỘ (ĐA TÀI KHOẢN HOẶC 1 TÀI KHOẢN)
        // =====================================================================
        case 'trigger_sync':
            $currentUser = $_SESSION['user']['username'] ?? ($_SESSION['username'] ?? 'USER_TRIGGER');
            $globalCfg = loadGlobalHrmConfig($configFile);

            $dateFrom = !empty($_POST['TuNgay']) ? trim($_POST['TuNgay']) : (!empty($_GET['TuNgay']) ? trim($_GET['TuNgay']) : ($globalCfg['sync_date_from'] ?? ''));
            $dateTo = !empty($_POST['DenNgay']) ? trim($_POST['DenNgay']) : (!empty($_GET['DenNgay']) ? trim($_GET['DenNgay']) : ($globalCfg['sync_date_to'] ?? ''));

            $targetAccountId = intval($_POST['account_id'] ?? ($_GET['account_id'] ?? 0));

            // Xác định danh sách tài khoản cần đồng bộ
            $accountsToSync = [];
            if ($targetAccountId > 0) {
                $stmtAcc = $conn->prepare("SELECT * FROM ot_hrm_accounts WHERE id = ?");
                $stmtAcc->bind_param("i", $targetAccountId);
                $stmtAcc->execute();
                $acc = $stmtAcc->get_result()->fetch_assoc();
                $stmtAcc->close();
                if ($acc) $accountsToSync[] = $acc;
            } else {
                $accountsToSync = getHrmAccounts($conn, true); // Lấy tất cả tài khoản is_active = 1
            }

            if (empty($accountsToSync)) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản HRM nào đang ở trạng thái Hoạt động để đồng bộ.']);
                exit;
            }

            $allSteps = [];
            $allUrls = [];
            $accountResults = [];
            $totPlanIns = 0; $totPlanUp = 0; $totPlanErr = 0;
            $totActIns = 0; $totActUp = 0; $totActErr = 0;
            $successCount = 0;

            foreach ($accountsToSync as $acc) {
                $res = syncSingleHrmAccount($conn, $acc, $dateFrom, $dateTo, $currentUser);
                $accountResults[] = $res;
                if (!empty($res['steps'])) {
                    $allSteps = array_merge($allSteps, $res['steps']);
                }

                if (!empty($res['success'])) {
                    $successCount++;
                    $totPlanIns += $res['plan_result']['inserted_rows'] ?? 0;
                    $totPlanUp  += $res['plan_result']['updated_rows'] ?? 0;
                    $totPlanErr += $res['plan_result']['error_rows'] ?? 0;
                    $totActIns  += $res['actual_result']['inserted_rows'] ?? 0;
                    $totActUp   += $res['actual_result']['updated_rows'] ?? 0;
                    $totActErr  += $res['actual_result']['error_rows'] ?? 0;
                }
            }

            // Tự động kích hoạt đối soát tổng hợp sau khi gộp toàn bộ dữ liệu từ các tài khoản
            $recSummary = runReconciliationInternal($conn);

            $nowVn = date('Y-m-d H:i:s');
            $nowVnFormatted = formatVnDateTime($nowVn);

            // Cập nhật cấu hình toàn cục
            $overallSuccess = ($successCount > 0);
            $globalCfg['last_sync_time'] = $nowVn;
            $globalCfg['last_sync_status'] = $overallSuccess ? 'success' : 'failed';
            $globalCfg['last_sync_message'] = "Đồng bộ {$successCount}/" . count($accountsToSync) . " tài khoản thành công. Gộp KH: +{$totPlanIns}/sửa {$totPlanUp}. TT: +{$totActIns}/sửa {$totActUp}.";
            $globalCfg['last_sync_stats'] = [
                'plan_inserted'   => $totPlanIns,
                'plan_updated'    => $totPlanUp,
                'plan_errors'     => $totPlanErr,
                'actual_inserted' => $totActIns,
                'actual_updated'  => $totActUp,
                'actual_errors'   => $totActErr
            ];
            saveGlobalHrmConfig($configFile, $globalCfg);

            echo json_encode([
                'success'                  => $overallSuccess,
                'message'                  => $globalCfg['last_sync_message'],
                'last_sync_time'           => $nowVn,
                'last_sync_time_formatted' => $nowVnFormatted,
                'accounts_count'           => count($accountsToSync),
                'success_accounts_count'   => $successCount,
                'account_results'          => $accountResults,
                'steps'                    => $allSteps,
                'totals'                   => [
                    'plan_inserted'   => $totPlanIns,
                    'plan_updated'    => $totPlanUp,
                    'plan_errors'     => $totPlanErr,
                    'actual_inserted' => $totActIns,
                    'actual_updated'  => $totActUp,
                    'actual_errors'   => $totActErr
                ],
                'reconciliation'           => $recSummary
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 7. CHẠY THEO LỊCH TRÌNH TỰ ĐỘNG (CRON / AUTO SCHEDULER)
        // =====================================================================
        case 'cron':
        case 'check_schedule':
            $globalCfg = loadGlobalHrmConfig($configFile);
            $intervalSec = intval($globalCfg['sync_interval_hours'] ?? 3) * 3600;
            $lastTime = !empty($globalCfg['last_sync_time']) ? strtotime($globalCfg['last_sync_time']) : 0;
            $timeSince = ($lastTime > 0) ? (time() - $lastTime) : 99999999;
            $secondsRemaining = max(0, $intervalSec - $timeSince);
            $nextSyncTime = date('Y-m-d H:i:s', ($lastTime > 0 ? $lastTime : time()) + $intervalSec);

            if (empty($globalCfg['auto_sync_enabled'])) {
                echo json_encode([
                    'success'           => true,
                    'ran_sync'          => false,
                    'auto_sync_enabled' => false,
                    'seconds_remaining' => -1,
                    'next_sync_time'    => null,
                    'last_sync_time'    => $globalCfg['last_sync_time'] ?? null,
                    'message'           => 'Tính năng tự động đồng bộ đang tắt.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($action === 'check_schedule' && $timeSince < $intervalSec) {
                $nextInMin = ceil(($intervalSec - $timeSince) / 60);
                echo json_encode([
                    'success'                  => true,
                    'ran_sync'                 => false,
                    'needs_sync'               => false,
                    'auto_sync_enabled'        => true,
                    'seconds_remaining'        => $secondsRemaining,
                    'next_sync_time'           => $nextSyncTime,
                    'message'                  => "Chưa đến lịch đồng bộ tiếp theo (còn khoảng {$nextInMin} phút).",
                    'last_sync_time'           => $globalCfg['last_sync_time'],
                    'last_sync_time_formatted' => formatVnDateTime($globalCfg['last_sync_time']),
                    'interval_hours'           => $globalCfg['sync_interval_hours']
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Kích hoạt đồng bộ tất cả tài khoản active
            $accountsToSync = getHrmAccounts($conn, true);
            $successCount = 0;
            $totPlanIns = 0; $totPlanUp = 0; $totActIns = 0; $totActUp = 0;

            foreach ($accountsToSync as $acc) {
                $res = syncSingleHrmAccount($conn, $acc, $globalCfg['sync_date_from'] ?? '', $globalCfg['sync_date_to'] ?? '', 'HRM_CRON_AUTO');
                if (!empty($res['success'])) {
                    $successCount++;
                    $totPlanIns += $res['plan_result']['inserted_rows'] ?? 0;
                    $totPlanUp  += $res['plan_result']['updated_rows'] ?? 0;
                    $totActIns  += $res['actual_result']['inserted_rows'] ?? 0;
                    $totActUp   += $res['actual_result']['updated_rows'] ?? 0;
                }
            }

            $recSummary = runReconciliationInternal($conn);
            $nowVn = date('Y-m-d H:i:s');

            $globalCfg['last_sync_time'] = $nowVn;
            $globalCfg['last_sync_status'] = ($successCount > 0) ? 'success' : 'failed';
            $globalCfg['last_sync_message'] = "CRON: Đồng bộ {$successCount}/" . count($accountsToSync) . " tài khoản. KH (+{$totPlanIns}/{$totPlanUp}), TT (+{$totActIns}/{$totActUp}).";
            saveGlobalHrmConfig($configFile, $globalCfg);

            echo json_encode([
                'success'                  => true,
                'ran_sync'                 => true,
                'auto_sync_enabled'        => true,
                'seconds_remaining'        => $intervalSec,
                'next_sync_time'           => date('Y-m-d H:i:s', time() + $intervalSec),
                'interval_hours'           => $globalCfg['sync_interval_hours'],
                'message'                  => $globalCfg['last_sync_message'],
                'last_sync_time'           => $nowVn,
                'last_sync_time_formatted' => formatVnDateTime($nowVn),
                'reconciliation'           => $recSummary
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý đồng bộ: ' . $e->getMessage()]);
}
