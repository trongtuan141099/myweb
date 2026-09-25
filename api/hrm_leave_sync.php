<?php
/**
 * API Tự Động Kết Nối & Đồng Bộ Dữ Liệu Phép Nghỉ Từ HRM
 * DX Plastic Group - Factory Management System
 */
header('Content-Type: application/json; charset=utf-8');

// Thiết lập múi giờ Việt Nam GMT+7
date_default_timezone_set('Asia/Ho_Chi_Minh');

$isCli = (php_sapi_name() === 'cli') || (isset($argv) && count($argv) > 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/leave_service.php';

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
    // Cho phép người dùng có quyền hrm.leave_sync hoặc hrm.view hoặc hrm.manage
    if (!hasPermission(['hrm.leave_sync', 'hrm.view', 'hrm.manage', 'api.hrm.leave', 'api.hrm.leave_sync', 'admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập API đồng bộ phép nghỉ HRM.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$configFile = __DIR__ . '/../config/hrm_leave_sync_config.json';
$action = $_GET['action'] ?? ($_POST['action'] ?? ($isCli ? 'cron' : ''));

if (!function_exists('formatLeaveHrmDate')) {
function formatLeaveHrmDate($d, $default) {
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

if (!function_exists('loadGlobalLeaveConfig')) {
function loadGlobalLeaveConfig($configFile) {
    $default = [
        'hrm_url' => 'https://hrm.smcmfg.com.vn',
        'login_endpoint' => 'https://hrm.smcmfg.com.vn/login',
        'auth_endpoint' => 'https://hrm.smcmfg.com.vn/Account/GetLogin_KiemTraLoginLanDau',
        'portal_endpoint' => 'https://hrm.smcmfg.com.vn/NghiPhep/DuyetPhep',
        'export_endpoint' => 'https://hrm.smcmfg.com.vn/Portal/NghiPhep/ExportDeDuyetNghiPhep',
        'download_endpoint' => 'https://hrm.smcmfg.com.vn/Download/ExcelDownload',
        'sync_interval_hours' => 3,
        'auto_sync_enabled' => true,
        'sync_date_from' => '',
        'sync_date_to' => '',
        'last_sync_time' => null,
        'last_sync_status' => 'never_run',
        'last_sync_message' => 'Chưa từng thực hiện đồng bộ phép.',
        'last_sync_stats' => [
            'inserted' => 0, 'updated' => 0, 'errors' => 0
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

if (!function_exists('saveGlobalLeaveConfig')) {
function saveGlobalLeaveConfig($configFile, $config) {
    return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}
}

if (!function_exists('postLeaveHrmRequest')) {
function postLeaveHrmRequest($url, $postFields, $cookieFile, $referer) {
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

if (!function_exists('downloadLeaveFileStream')) {
function downloadLeaveFileStream($url, $destinationFile, $cookieFile) {
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
    curl_close($ch);
    fclose($fp);

    return ($httpCode === 200 && file_exists($destinationFile) && filesize($destinationFile) > 100);
}
}

if (!function_exists('syncSingleHrmAccountLeave')) {
function syncSingleHrmAccountLeave($conn, $account, $dateFrom, $dateTo, $currentUser = 'HRM_LEAVE_SYNC', $customConfig = null) {
    global $configFile;
    if ($customConfig === null) {
        $customConfig = loadGlobalLeaveConfig($configFile ?? (__DIR__ . '/../config/hrm_leave_sync_config.json'));
    }

    $accId = intval($account['id']);
    $username = trim($account['username']);
    $password = trim($account['password']);
    $accName = $account['account_name'] ?? "Tài khoản {$username}";

    $loginUrl = !empty($customConfig['login_endpoint']) ? $customConfig['login_endpoint'] : 'https://hrm.smcmfg.com.vn/login';
    $authUrl = !empty($customConfig['auth_endpoint']) ? $customConfig['auth_endpoint'] : 'https://hrm.smcmfg.com.vn/Account/GetLogin_KiemTraLoginLanDau';
    $portalUrl = !empty($customConfig['portal_endpoint']) ? $customConfig['portal_endpoint'] : 'https://hrm.smcmfg.com.vn/NghiPhep/DuyetPhep';
    $exportUrl = !empty($customConfig['export_endpoint']) ? $customConfig['export_endpoint'] : 'https://hrm.smcmfg.com.vn/Portal/NghiPhep/ExportDeDuyetNghiPhep';
    $downloadBase = !empty($customConfig['download_endpoint']) ? $customConfig['download_endpoint'] : 'https://hrm.smcmfg.com.vn/Download/ExcelDownload';

    $cookieFile = __DIR__ . '/../data/hrm_cookie_leave_' . $accId . '.txt';
    $leaveFile = __DIR__ . '/../data/hrm_sync_leave_' . $accId . '.xlsx';

    $steps = [];
    $urlsCalled = [];

    $addStep = function($num, $title, $method, $url, $status, $detail, $httpCode = null) use (&$steps, &$urlsCalled, $accName) {
        $steps[] = [
            'account'   => $accName,
            'step'      => $num,
            'title'     => $title,
            'method'    => $method,
            'url'       => $url,
            'status'    => $status,
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
    touch($cookieFile);
    $cookieFile = realpath($cookieFile);
    if (file_exists($leaveFile)) @unlink($leaveFile);

    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    // 1. Bước 1: Khởi tạo phiên, lấy Cookie và Verification Token từ /login
    $ch = curl_init($loginUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $fullInitResp = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $initRespHeaders = substr($fullInitResp, 0, $headerSize);
    $loginHtml = substr($fullInitResp, $headerSize);

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

    if (preg_match_all('/^set-cookie:\s*([^=;]+)=([^;]+)/im', $initRespHeaders, $cookieMatches, PREG_SET_ORDER)) {
        $initCookies = "";
        foreach ($cookieMatches as $cm) {
            $cName = trim($cm[1]);
            $cVal = trim($cm[2]);
            $initCookies .= "#HttpOnly_hrm.smcmfg.com.vn\tFALSE\t/\tTRUE\t2147483647\t{$cName}\t{$cVal}\n";
        }
        file_put_contents($cookieFile, $initCookies, FILE_APPEND);
    }

    $loginToken = '';
    if (preg_match('/<input\b[^>]*name=["\']__RequestVerificationToken["\'][^>]*value=["\']([^"\']*)["\']/i', $loginHtml, $match)) {
        $loginToken = $match[1];
    }
    $addStep(1, "Khởi tạo phiên [{$accName}]", 'GET', $loginUrl, 'success', 'Kết nối thành công. Nhận Login Token (' . substr($loginToken, 0, 10) . '...).', $httpCode);

    // 2. Bước 2: Đăng nhập
    $salt = substr(base64_encode(date('D M d Y H:i:s') . ' GMT+0700 (Indochina Time)'), 0, 5);
    $encodedPassword = $salt . base64_encode($password);

    $chAuth = curl_init($authUrl);
    curl_setopt_array($chAuth, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'Username'                  => $username,
            'Token'                     => $encodedPassword,
            'returnUrl'                 => '/login',
            'gRecaptchaResponse'        => '',
            'Remember'                  => 'true',
            'IsCaptcha'                 => 'false',
            '__RequestVerificationToken' => $loginToken
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_HTTPHEADER     => [
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . $loginUrl
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $fullLoginResp = curl_exec($chAuth);
    $headerSize = curl_getinfo($chAuth, CURLINFO_HEADER_SIZE);
    $loginHttpCode = curl_getinfo($chAuth, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($chAuth);
    curl_close($chAuth);

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

    // 3. Bước 3: Lấy Portal Token từ /NghiPhep/DuyetPhep
    $chPortal = curl_init($portalUrl);
    curl_setopt_array($chPortal, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $portalHtml = curl_exec($chPortal);
    $portalHttpCode = curl_getinfo($chPortal, CURLINFO_HTTP_CODE);
    curl_close($chPortal);

    $portalToken = '';
    if (!empty($portalHtml) && preg_match('/<input\b[^>]*name=["\']__RequestVerificationToken["\'][^>]*value=["\']([^"\']*)["\']/i', $portalHtml, $matchPortal)) {
        $portalToken = $matchPortal[1];
    }
    if (empty($portalToken)) $portalToken = $loginToken;
    $addStep(3, "Trích xuất Portal Token [{$accName}]", 'GET', $portalUrl, 'success', 'Token xác thực phép nghỉ đã sẵn sàng.', $portalHttpCode);

    // 4. Bước 4: Yêu cầu xuất dữ liệu phép nghỉ: POST /Portal/NghiPhep/ExportDeDuyetNghiPhep
    $tuNgay = formatLeaveHrmDate($dateFrom, date('Y-m-01'));
    $denNgay = formatLeaveHrmDate($dateTo, date('Y-m-t'));

    $chExport = curl_init($exportUrl);
    curl_setopt_array($chExport, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'TuNgay'                     => $tuNgay,
            'DenNgay'                    => $denNgay,
            '__RequestVerificationToken' => $portalToken
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . $portalUrl
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $exportRaw = curl_exec($chExport);
    $exportHttpCode = curl_getinfo($chExport, CURLINFO_HTTP_CODE);
    curl_close($chExport);
    $exportJson = json_decode($exportRaw, true);

    $leaveDownloadSuccess = false;
    $inserted = 0;
    $updated = 0;
    $errors = 0;

    if (!empty($exportJson) && ($exportJson['StatusCode'] ?? 0) > 0) {
        $downloadId = $exportJson['StatusText'];
        $downloadUrl = $downloadBase . '?id=' . urlencode($downloadId) . '&name=' . urlencode('Danh sách duyệt nghỉ phép');
        $leaveDownloadSuccess = downloadLeaveFileStream($downloadUrl, $leaveFile, $cookieFile);

        if ($leaveDownloadSuccess) {
            $addStep(4, "Tải file Phép Nghỉ [{$accName}]", 'GET', $downloadUrl, 'success', 'Tải file Phép nghỉ thành công (' . round(filesize($leaveFile)/1024, 1) . ' KB).', 200);

            // Import vào DB
            $importRes = processLeaveExcelImport($conn, $leaveFile, "HRM_SYNC_{$username}");
            if ($importRes['success']) {
                $inserted = $importRes['inserted'] ?? 0;
                $updated  = $importRes['updated'] ?? 0;
                $errors   = $importRes['errors'] ?? 0;
                $addStep(5, "Nạp cơ sở dữ liệu [{$accName}]", 'SQL_UPSERT', '', 'success', "Lưu thành công: {$inserted} bản ghi mới, {$updated} cập nhật.");
            } else {
                $addStep(5, "Nạp cơ sở dữ liệu [{$accName}]", 'SQL_UPSERT', '', 'error', 'Lỗi phân tích Excel: ' . $importRes['message']);
            }
        } else {
            $addStep(4, "Tải file Phép Nghỉ [{$accName}]", 'GET', $downloadUrl, 'warning', 'Không tải được file Phép nghỉ từ liên kết streaming.');
        }
    } else {
        $msg = $exportJson['StatusText'] ?? (substr(strip_tags($exportRaw), 0, 150) ?: 'HRM phản hồi không có dữ liệu nghỉ phép hoặc phiên hết hạn.');
        $addStep(4, "Yêu cầu Xuất Phép Nghỉ [{$accName}]", 'POST', $exportUrl, 'warning', 'HRM: ' . $msg, $exportHttpCode);
    }

    if (file_exists($cookieFile)) @unlink($cookieFile);

    return [
        'success'      => $leaveDownloadSuccess,
        'account_id'   => $accId,
        'account_name' => $accName,
        'message'      => $leaveDownloadSuccess ? "Đồng bộ phép [{$accName}] thành công." : "Tải dữ liệu [{$accName}] chưa hoàn tất.",
        'stats'        => [
            'inserted' => $inserted,
            'updated'  => $updated,
            'errors'   => $errors
        ],
        'steps'        => $steps,
        'urls_called'  => $urlsCalled
    ];
}
}

// Xử lý các Action
switch ($action) {
    case 'get_config':
        $config = loadGlobalLeaveConfig($configFile);
        $resAccs = $conn->query("SELECT id, account_name, username, is_active, last_sync_time, last_sync_status, last_sync_message FROM ot_hrm_accounts ORDER BY id ASC");
        $accounts = [];
        if ($resAccs) {
            while ($r = $resAccs->fetch_assoc()) {
                $accounts[] = $r;
            }
        }
        echo json_encode(['success' => true, 'config' => $config, 'accounts' => $accounts], JSON_UNESCAPED_UNICODE);
        break;

    case 'save_config':
        $config = loadGlobalLeaveConfig($configFile);
        $config['hrm_url']             = trim($_POST['hrm_url'] ?? ($config['hrm_url'] ?? 'https://hrm.smcmfg.com.vn'));
        $config['login_endpoint']      = trim($_POST['login_endpoint'] ?? ($config['login_endpoint'] ?? 'https://hrm.smcmfg.com.vn/login'));
        $config['auth_endpoint']       = trim($_POST['auth_endpoint'] ?? ($config['auth_endpoint'] ?? 'https://hrm.smcmfg.com.vn/Account/GetLogin_KiemTraLoginLanDau'));
        $config['portal_endpoint']     = trim($_POST['portal_endpoint'] ?? ($config['portal_endpoint'] ?? 'https://hrm.smcmfg.com.vn/NghiPhep/DuyetPhep'));
        $config['export_endpoint']     = trim($_POST['export_endpoint'] ?? ($config['export_endpoint'] ?? 'https://hrm.smcmfg.com.vn/Portal/NghiPhep/ExportDeDuyetNghiPhep'));
        $config['download_endpoint']   = trim($_POST['download_endpoint'] ?? ($config['download_endpoint'] ?? 'https://hrm.smcmfg.com.vn/Download/ExcelDownload'));
        $config['sync_interval_hours'] = max(1, intval($_POST['sync_interval_hours'] ?? 3));
        $config['auto_sync_enabled']   = filter_var($_POST['auto_sync_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $config['sync_date_from']      = trim($_POST['sync_date_from'] ?? '');
        $config['sync_date_to']        = trim($_POST['sync_date_to'] ?? '');

        if (saveGlobalLeaveConfig($configFile, $config)) {
            echo json_encode(['success' => true, 'message' => 'Lưu cấu hình kết nối HRM thành công.', 'config' => $config], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể ghi file cấu hình.'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'get_accounts':
        $resAccs = $conn->query("SELECT id, account_name, username, is_active, last_sync_time, last_sync_status, last_sync_message FROM ot_hrm_accounts ORDER BY id ASC");
        $accounts = [];
        if ($resAccs) {
            while ($r = $resAccs->fetch_assoc()) {
                $accounts[] = $r;
            }
        }
        echo json_encode(['success' => true, 'accounts' => $accounts], JSON_UNESCAPED_UNICODE);
        break;

    case 'save_account':
        $accId = intval($_POST['id'] ?? 0);
        $accName = trim($_POST['account_name'] ?? '');
        $u = trim($_POST['username'] ?? '');
        $p = trim($_POST['password'] ?? '');
        $isActive = isset($_POST['is_active']) ? (intval($_POST['is_active']) ? 1 : 0) : 1;

        if (empty($u)) {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập không được để trống.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($accId > 0) {
            if (!empty($p)) {
                $stmt = $conn->prepare("UPDATE ot_hrm_accounts SET account_name=?, username=?, password=?, is_active=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param("sssii", $accName, $u, $p, $isActive, $accId);
            } else {
                $stmt = $conn->prepare("UPDATE ot_hrm_accounts SET account_name=?, username=?, is_active=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param("ssii", $accName, $u, $isActive, $accId);
            }
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => "Đã cập nhật tài khoản [{$u}]."], JSON_UNESCAPED_UNICODE);
        } else {
            if (empty($p)) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu không được để trống khi thêm tài khoản mới.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO ot_hrm_accounts (account_name, username, password, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("sssi", $accName, $u, $p, $isActive);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            echo json_encode(['success' => true, 'message' => "Đã thêm tài khoản mới [{$u}].", 'id' => $newId], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'delete_account':
        $accId = intval($_POST['id'] ?? 0);
        if ($accId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tài khoản không hợp lệ.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM ot_hrm_accounts WHERE id=?");
        $stmt->bind_param("i", $accId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => "Đã xóa tài khoản."], JSON_UNESCAPED_UNICODE);
        break;

    case 'test_connection':
        $config = loadGlobalLeaveConfig($configFile);
        $accId = intval($_POST['account_id'] ?? 0);
        $acc = null;
        if ($accId > 0) {
            $res = $conn->query("SELECT * FROM ot_hrm_accounts WHERE id={$accId}");
            if ($res) $acc = $res->fetch_assoc();
        } else {
            $acc = [
                'id' => 999,
                'account_name' => 'Tài khoản thử nghiệm',
                'username' => trim($_POST['username'] ?? ''),
                'password' => trim($_POST['password'] ?? '')
            ];
        }

        if (!$acc || empty($acc['username']) || empty($acc['password'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp đầy đủ tên đăng nhập và mật khẩu.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $tempConfig = $config;
        if (!empty($_POST['login_endpoint'])) $tempConfig['login_endpoint'] = trim($_POST['login_endpoint']);
        if (!empty($_POST['auth_endpoint'])) $tempConfig['auth_endpoint'] = trim($_POST['auth_endpoint']);
        if (!empty($_POST['portal_endpoint'])) $tempConfig['portal_endpoint'] = trim($_POST['portal_endpoint']);
        if (!empty($_POST['export_endpoint'])) $tempConfig['export_endpoint'] = trim($_POST['export_endpoint']);
        if (!empty($_POST['download_endpoint'])) $tempConfig['download_endpoint'] = trim($_POST['download_endpoint']);

        $testRes = syncSingleHrmAccountLeave($conn, $acc, date('Y-m-01'), date('Y-m-t'), 'TEST_SYNC', $tempConfig);
        echo json_encode([
            'success'     => $testRes['success'],
            'message'     => $testRes['success'] ? 'Kết nối và trích xuất dữ liệu HRM thành công!' : ('Kết nối không thành công: ' . $testRes['message']),
            'steps'       => $testRes['steps'],
            'urls_called' => $testRes['urls_called'],
            'stats'       => $testRes['stats'] ?? null
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'check_schedule':
        $config = loadGlobalLeaveConfig($configFile);
        $autoEnabled = !empty($config['auto_sync_enabled']);
        $intervalHours = floatval($config['sync_interval_hours'] ?? 3);
        $intervalSec = max(300, intval($intervalHours * 3600));

        $lastTime = !empty($config['last_sync_time']) ? strtotime($config['last_sync_time']) : 0;
        $now = time();
        $timeSinceLast = ($lastTime > 0) ? ($now - $lastTime) : $intervalSec;
        $secondsRemaining = max(0, $intervalSec - $timeSinceLast);
        $nextSyncTimestamp = ($lastTime > 0) ? ($lastTime + $intervalSec) : ($now + $secondsRemaining);

        $ranSync = false;

        // Nếu đã đến hạn và auto_sync đang bật -> tự động thực hiện đồng bộ
        if ($autoEnabled && $timeSinceLast >= $intervalSec) {
            $dateFrom = $config['sync_date_from'] ?: date('Y-m-01');
            $dateTo   = $config['sync_date_to'] ?: date('Y-m-t');

            $resAccs = $conn->query("SELECT * FROM ot_hrm_accounts WHERE is_active = 1 ORDER BY id ASC");
            $accounts = [];
            if ($resAccs) {
                while ($row = $resAccs->fetch_assoc()) $accounts[] = $row;
            }

            if (!empty($accounts)) {
                $totalInserted = 0;
                $totalUpdated = 0;
                $totalErrors = 0;
                $successAccs = 0;
                $allSteps = [];
                $allUrls = [];

                foreach ($accounts as $acc) {
                    $accResult = syncSingleHrmAccountLeave($conn, $acc, $dateFrom, $dateTo);
                    $allSteps = array_merge($allSteps, $accResult['steps'] ?? []);
                    if (!empty($accResult['urls_called'])) {
                        $allUrls = array_unique(array_merge($allUrls, $accResult['urls_called']));
                    }
                    if (!empty($accResult['stats'])) {
                        $totalInserted += $accResult['stats']['inserted'];
                        $totalUpdated  += $accResult['stats']['updated'];
                        $totalErrors   += $accResult['stats']['errors'];
                    }
                    if (!empty($accResult['success'])) $successAccs++;
                }

                $isOverallSuccess = ($successAccs > 0);
                $syncStatus = $isOverallSuccess ? 'success' : 'failed';
                $syncMsg = $isOverallSuccess 
                    ? "Tự động đồng bộ ({$successAccs}/" . count($accounts) . " tài khoản): +{$totalInserted} bản ghi mới, {$totalUpdated} cập nhật."
                    : "Tự động đồng bộ thất bại toàn bộ các tài khoản HRM.";

                $config['last_sync_time']    = date('Y-m-d H:i:s');
                $config['last_sync_status']  = $syncStatus;
                $config['last_sync_message'] = $syncMsg;
                $config['last_sync_stats']   = [
                    'inserted' => $totalInserted,
                    'updated'  => $totalUpdated,
                    'errors'   => $totalErrors
                ];
                saveGlobalLeaveConfig($configFile, $config);

                $ranSync = true;
                $secondsRemaining = $intervalSec;
                $nextSyncTimestamp = time() + $intervalSec;
            }
        }

        echo json_encode([
            'success'              => true,
            'auto_sync_enabled'    => $autoEnabled,
            'interval_hours'       => $intervalHours,
            'interval_seconds'     => $intervalSec,
            'seconds_remaining'    => $secondsRemaining,
            'next_sync_time'       => date('Y-m-d H:i:s', $nextSyncTimestamp),
            'next_sync_formatted'  => date('d/m/Y H:i:s', $nextSyncTimestamp),
            'last_sync_time'       => $config['last_sync_time'] ?? null,
            'last_sync_status'     => $config['last_sync_status'] ?? 'never_run',
            'last_sync_message'    => $config['last_sync_message'] ?? '',
            'ran_sync'             => $ranSync
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'trigger_sync':
    case 'cron':
    case 'sync':
        $config = loadGlobalLeaveConfig($configFile);

        $dateFrom = $_POST['date_from'] ?? ($_GET['date_from'] ?? ($config['sync_date_from'] ?: date('Y-m-01')));
        $dateTo   = $_POST['date_to'] ?? ($_GET['date_to'] ?? ($config['sync_date_to'] ?: date('Y-m-t')));

        $targetAccId = intval($_POST['account_id'] ?? ($_GET['account_id'] ?? 0));

        // Lấy danh sách tài khoản HRM active
        $sqlAccs = "SELECT * FROM ot_hrm_accounts WHERE is_active = 1";
        if ($targetAccId > 0) {
            $sqlAccs .= " AND id = {$targetAccId}";
        }
        $sqlAccs .= " ORDER BY id ASC";
        $resAccs = $conn->query($sqlAccs);

        $accounts = [];
        if ($resAccs) {
            while ($row = $resAccs->fetch_assoc()) {
                $accounts[] = $row;
            }
        }

        if (empty($accounts)) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy tài khoản HRM nào đang hoạt động. Vui lòng cấu hình tài khoản trong hệ thống.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $allSteps = [];
        $allUrls = [];
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalErrors = 0;
        $successAccs = 0;

        foreach ($accounts as $acc) {
            $accResult = syncSingleHrmAccountLeave($conn, $acc, $dateFrom, $dateTo);
            $allSteps = array_merge($allSteps, $accResult['steps'] ?? []);
            if (!empty($accResult['urls_called'])) {
                $allUrls = array_unique(array_merge($allUrls, $accResult['urls_called']));
            }
            if (!empty($accResult['stats'])) {
                $totalInserted += $accResult['stats']['inserted'];
                $totalUpdated  += $accResult['stats']['updated'];
                $totalErrors   += $accResult['stats']['errors'];
            }
            if (!empty($accResult['success'])) {
                $successAccs++;
            }
        }

        $isOverallSuccess = ($successAccs > 0);
        $syncStatus = $isOverallSuccess ? 'success' : 'failed';
        $syncMsg = $isOverallSuccess 
            ? "Đồng bộ hoàn tất ({$successAccs}/" . count($accounts) . " tài khoản): +{$totalInserted} bản ghi mới, {$totalUpdated} cập nhật."
            : "Đồng bộ thất bại toàn bộ các tài khoản HRM.";

        // Cập nhật cấu hình
        $config['last_sync_time']    = date('Y-m-d H:i:s');
        $config['last_sync_status']  = $syncStatus;
        $config['last_sync_message'] = $syncMsg;
        $config['last_sync_stats']   = [
            'inserted' => $totalInserted,
            'updated'  => $totalUpdated,
            'errors'   => $totalErrors
        ];
        saveGlobalLeaveConfig($configFile, $config);

        echo json_encode([
            'success'        => $isOverallSuccess,
            'message'        => $syncMsg,
            'inserted_count' => $totalInserted,
            'updated_count'  => $totalUpdated,
            'error_count'    => $totalErrors,
            'stats'          => $config['last_sync_stats'],
            'steps'          => $allSteps,
            'urls_called'    => array_values($allUrls),
            'last_sync'      => [
                'time'    => $config['last_sync_time'],
                'status'  => $syncStatus,
                'message' => $syncMsg
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'upload_excel':
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file Excel hợp lệ.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $fileTmp = $_FILES['excel_file']['tmp_name'];
        $fileName = $_FILES['excel_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận định dạng Excel (.xlsx, .xls)'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $curUser = $_SESSION['username'] ?? 'HRM_MANUAL_UPLOAD';
        $res = processLeaveExcelImport($conn, $fileTmp, $curUser);

        // Lưu log đồng bộ
        if ($res['success']) {
            $config = loadGlobalLeaveConfig($configFile);
            $config['last_sync_time'] = date('Y-m-d H:i:s');
            $config['last_sync_status'] = 'success';
            $config['last_sync_message'] = "Import thủ công file {$fileName}: +{$res['inserted']} mới, {$res['updated']} cập nhật.";
            $config['last_sync_stats'] = [
                'inserted' => $res['inserted'],
                'updated'  => $res['updated'],
                'errors'   => $res['errors']
            ];
            saveGlobalLeaveConfig($configFile, $config);
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ.'], JSON_UNESCAPED_UNICODE);
        break;
}

