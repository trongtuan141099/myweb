<?php
/**
 * API Tự Động Kết Nối & Đồng Bộ Dữ Liệu Tăng Ca Từ HRM
 * DX Plastic Group - Overtime Management System
 */
header('Content-Type: application/json; charset=utf-8');

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
$cookieFile = __DIR__ . '/../data/hrm_cookie.txt';
$planFile = __DIR__ . '/../data/hrm_sync_plan.xlsx';
$actualFile = __DIR__ . '/../data/hrm_sync_actual.xlsx';

$action = $_GET['action'] ?? ($_POST['action'] ?? ($isCli ? 'cron' : ''));

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

function loadHrmConfig($configFile) {
    $default = [
        'hrm_url' => 'https://hrm.smcmfg.com.vn',
        'login_endpoint' => 'https://hrm.smcmfg.com.vn/login',
        'portal_endpoint' => 'https://hrm.smcmfg.com.vn/TangCa/DuyetTangCaKeHoach',
        'plan_export_endpoint' => 'https://hrm.smcmfg.com.vn/Portal/TangCa/ExportDuyetTangCaKeHoach',
        'actual_export_endpoint' => 'https://hrm.smcmfg.com.vn/Portal/TangCa/ExportDuyetTangCaThucTe',
        'download_endpoint' => 'https://hrm.smcmfg.com.vn/Download/ExcelDownload',
        'username' => '',
        'password' => '',
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
    unset($data['plan_url'], $data['actual_url']);
    return array_merge($default, $data);
}

function saveHrmConfig($configFile, $config) {
    unset($config['plan_url'], $config['actual_url']);
    return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

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
    curl_close($ch);
    fclose($fp);

    return ($httpCode === 200 && file_exists($destinationFile) && filesize($destinationFile) > 100);
}

function executeHrmSync($conn, $config, $cookieFile, $planFile, $actualFile, $currentUser = 'HRM_AUTO_SYNC') {
    $steps = [];
    $urlsCalled = [];

    $addStep = function($num, $title, $method, $url, $status, $detail, $httpCode = null) use (&$steps, &$urlsCalled) {
        $steps[] = [
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

    if (empty($config['username']) || empty($config['password'])) {
        $addStep(0, 'Kiểm tra thông tin tài khoản', 'LOCAL', '', 'error', 'Chưa cấu hình tài khoản hoặc mật khẩu HRM.');
        return [
            'success'     => false,
            'message'     => 'Chưa cấu hình tài khoản hoặc mật khẩu đăng nhập hệ thống HRM. Vui lòng thiết lập trước.',
            'steps'       => $steps,
            'urls_called' => $urlsCalled
        ];
    }

    if (!function_exists('curl_init')) {
        $addStep(0, 'Kiểm tra module cURL', 'LOCAL', '', 'error', 'Máy chủ PHP chưa bật extension cURL.');
        return [
            'success'     => false,
            'message'     => 'Máy chủ PHP chưa bật thư viện cURL để kết nối HTTP.',
            'steps'       => $steps,
            'urls_called' => $urlsCalled
        ];
    }

    $cookieDir = dirname($cookieFile);
    if (!file_exists($cookieDir)) {
        @mkdir($cookieDir, 0777, true);
    }
    $dataDir = dirname($planFile);
    if (!file_exists($dataDir)) {
        @mkdir($dataDir, 0777, true);
    }

    // Luôn dọn dẹp cookie và file cũ trước khi bắt đầu phiên làm việc mới
    if (file_exists($cookieFile)) @unlink($cookieFile);
    if (file_exists($planFile)) @unlink($planFile);
    if (file_exists($actualFile)) @unlink($actualFile);

    // 1. Bước 1: Khởi tạo phiên, lấy Cookie và Verification Token từ trang /login
    $loginUrl = !empty($config['login_endpoint']) ? $config['login_endpoint'] : 'https://hrm.smcmfg.com.vn/login';
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
        $errMsg = 'Không thể kết nối đến máy chủ HRM (HTTP Code: ' . $httpCode . '). ' . ($curlErr ? 'Chi tiết: ' . $curlErr : 'Vui lòng kiểm tra kết nối mạng.');
        $addStep(1, 'Khởi tạo phiên & Truy cập trang đăng nhập', 'GET', $loginUrl, 'error', $errMsg, $httpCode);
        return [
            'success'     => false,
            'message'     => $errMsg,
            'steps'       => $steps,
            'urls_called' => $urlsCalled
        ];
    }

    // Trích xuất __RequestVerificationToken từ trang đăng nhập
    $loginToken = '';
    if (preg_match('/<input\b[^>]*name=["\']__RequestVerificationToken["\'][^>]*value=["\']([^"\']*)["\']/i', $loginHtml, $match)) {
        $loginToken = $match[1];
    }
    $addStep(1, 'Khởi tạo phiên & Truy cập trang đăng nhập', 'GET', $loginUrl, 'success', 'Kết nối thành công. Nhận Session Cookie & Login Token (' . substr($loginToken, 0, 10) . '...).', $httpCode);

    // 2. Bước 2: Thực hiện đăng nhập qua POST /Account/GetLogin_KiemTraLoginLanDau
    $authUrl = 'https://hrm.smcmfg.com.vn/Account/GetLogin_KiemTraLoginLanDau';
    // Mô phỏng hàm Base64.encode2 từ HRM client bundle
    $salt = substr(base64_encode(date('D M d Y H:i:s') . ' GMT+0700 (Indochina Time)'), 0, 5);
    $encodedPassword = $salt . base64_encode($config['password']);

    $postFields = [
        'Username'                  => $config['username'],
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
        CURLOPT_POSTFIELDS     => $postFields, // Mảng array để cURL tự động đóng gói multipart/form-data
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

    // Trích xuất toàn bộ Set-Cookie (đặc biệt là .bscsoft_core_h & session_login) và ghi đè vào file cookie để đảm bảo xác thực
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
        $addStep(2, 'Xác thực tài khoản HRM', 'POST', $authUrl, 'error', 'Đăng nhập không thành công: ' . $errMsg, $loginHttpCode);
        return [
            'success'     => false,
            'message'     => 'Đăng nhập vào HRM không thành công: ' . $errMsg,
            'steps'       => $steps,
            'urls_called' => $urlsCalled
        ];
    }

    $addStep(2, 'Xác thực tài khoản HRM', 'POST', $authUrl, 'success', "Đăng nhập thành công với tài khoản [{$config['username']}]. Đã lưu cookie xác thực.", $loginHttpCode);

    // 3. Bước 3: Lấy __RequestVerificationToken từ thẻ hidden sau khi đăng nhập (/TangCa/DuyetTangCaKeHoach)
    $portalUrl = !empty($config['portal_endpoint']) ? $config['portal_endpoint'] : 'https://hrm.smcmfg.com.vn/TangCa/DuyetTangCaKeHoach';
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
    $portalEffUrl = curl_getinfo($chPortal, CURLINFO_EFFECTIVE_URL);
    curl_close($chPortal);

    $portalToken = '';
    if (!empty($portalHtml) && preg_match('/<input\b[^>]*name=["\']__RequestVerificationToken["\'][^>]*value=["\']([^"\']*)["\']/i', $portalHtml, $matchPortal)) {
        $portalToken = $matchPortal[1];
    }
    if (empty($portalToken)) {
        $portalToken = $loginToken;
        $addStep(3, 'Truy cập Portal & Trích xuất Verification Token', 'GET', $portalUrl, 'warning', "Sử dụng Token từ phiên đăng nhập. EffURL: {$portalEffUrl}.", $portalHttpCode);
    } else {
        $addStep(3, 'Truy cập Portal & Trích xuất Verification Token', 'GET', $portalUrl, 'success', 'Trích xuất __RequestVerificationToken thành công (' . substr($portalToken, 0, 12) . "...). EffURL: {$portalEffUrl}.", $portalHttpCode);
    }

    // 4. Bước 4: Chuẩn bị tham số Từ Ngày - Đến Ngày (chuẩn yyyy-MM-dd theo mẫu Ref)
    $tuNgay = formatHrmDate($config['sync_date_from'] ?? '', date('Y-01-01'));
    $denNgay = formatHrmDate($config['sync_date_to'] ?? '', date('Y-12-31'));

    // 5. Bước 5: Gọi Endpoint POST Tăng Ca Kế Hoạch (/Portal/TangCa/ExportDuyetTangCaKeHoach)
    $planExportUrl = !empty($config['plan_export_endpoint']) ? $config['plan_export_endpoint'] : 'https://hrm.smcmfg.com.vn/Portal/TangCa/ExportDuyetTangCaKeHoach';
    $planPostData = [
        'TuNgay' => $tuNgay,
        'DenNgay' => $denNgay,
        '__RequestVerificationToken' => $portalToken
    ];

    $planDownloadSuccess = false;
    $planDownloadUrl = '';
    $planExportRes = postHrmRequest($planExportUrl, $planPostData, $cookieFile, $portalUrl);
    if (!empty($planExportRes['json']) && ($planExportRes['json']['StatusCode'] ?? 0) > 0) {
        $downloadId = $planExportRes['json']['StatusText'];
        $planDownloadUrl = 'https://hrm.smcmfg.com.vn/Download/ExcelDownload?id=' . urlencode($downloadId) . '&name=' . urlencode('Danh sách Duyệt tăng ca kế hoạch');
        $addStep(4, 'Gửi yêu cầu xuất file Kế Hoạch', 'POST', $planExportUrl, 'success', "HRM tạo file Kế hoạch thành công (ID: {$downloadId}). Kỳ: {$tuNgay} -> {$denNgay}.", $planExportRes['http_code']);
        
        $planDownloadSuccess = downloadHrmFileStream($planDownloadUrl, $planFile, $cookieFile);
        if ($planDownloadSuccess) {
            $addStep(5, 'Tải file Excel Kế Hoạch', 'GET', $planDownloadUrl, 'success', 'Tải file Kế hoạch thành công (' . round(filesize($planFile)/1024, 1) . ' KB).', 200);
        } else {
            $addStep(5, 'Tải file Excel Kế Hoạch', 'GET', $planDownloadUrl, 'error', 'Không thể tải file từ liên kết tải động HRM.', 0);
        }
    } else {
        $errDetail = $planExportRes['json']['StatusText'] ?? ($planExportRes['error'] ?: 'Máy chủ HRM phản hồi mã lỗi: ' . ($planExportRes['json']['StatusCode'] ?? -1));
        $addStep(4, 'Gửi yêu cầu xuất file Kế Hoạch', 'POST', $planExportUrl, 'error', 'Yêu cầu xuất Kế hoạch thất bại: ' . $errDetail, $planExportRes['http_code']);
    }

    // 6. Bước 6: Gọi Endpoint POST Tăng Ca Thực Tế (/Portal/TangCa/ExportDuyetTangCaThucTe)
    $actExportUrl = !empty($config['actual_export_endpoint']) ? $config['actual_export_endpoint'] : 'https://hrm.smcmfg.com.vn/Portal/TangCa/ExportDuyetTangCaThucTe';
    $actPostData = [
        'TuNgay' => $tuNgay,
        'DenNgay' => $denNgay,
        '__RequestVerificationToken' => $portalToken
    ];

    $actDownloadSuccess = false;
    $actExportReferer = 'https://hrm.smcmfg.com.vn/TangCa/DuyetTangCaThucTe';
    $actExportRes = postHrmRequest($actExportUrl, $actPostData, $cookieFile, $actExportReferer);
    if (!empty($actExportRes['json']) && ($actExportRes['json']['StatusCode'] ?? 0) > 0) {
        $downloadId = $actExportRes['json']['StatusText'];
        $actDownloadUrl = 'https://hrm.smcmfg.com.vn/Download/ExcelDownload?id=' . urlencode($downloadId) . '&name=' . urlencode('Danh sách duyệt tăng ca thực tế');
        $addStep(6, 'Gửi yêu cầu xuất file Thực Tế', 'POST', $actExportUrl, 'success', "HRM tạo file Thực tế thành công (ID: {$downloadId}). Kỳ: {$tuNgay} -> {$denNgay}.", $actExportRes['http_code']);

        $actDownloadSuccess = downloadHrmFileStream($actDownloadUrl, $actualFile, $cookieFile);
        if ($actDownloadSuccess) {
            $addStep(7, 'Tải file Excel Thực Tế', 'GET', $actDownloadUrl, 'success', 'Tải file Thực tế thành công (' . round(filesize($actualFile)/1024, 1) . ' KB).', 200);
        } else {
            $addStep(7, 'Tải file Excel Thực Tế', 'GET', $actDownloadUrl, 'error', 'Không thể tải file từ liên kết tải động HRM.', 0);
        }
    } else {
        $errDetail = $actExportRes['json']['StatusText'] ?? ($actExportRes['error'] ?: 'Máy chủ HRM phản hồi mã lỗi: ' . ($actExportRes['json']['StatusCode'] ?? -1));
        $addStep(6, 'Gửi yêu cầu xuất file Thực Tế', 'POST', $actExportUrl, 'error', 'Yêu cầu xuất Thực tế thất bại: ' . $errDetail, $actExportRes['http_code']);
    }

    // 7. Bước 7: Kiểm tra tính hợp lệ của file Excel tải về (kiểm tra header PK zip)
    $planValid = file_exists($planFile) && filesize($planFile) > 100 && (substr(file_get_contents($planFile, false, null, 0, 2), 0, 2) === 'PK');
    $actValid = file_exists($actualFile) && filesize($actualFile) > 100 && (substr(file_get_contents($actualFile, false, null, 0, 2), 0, 2) === 'PK');

    if (!$planValid && !$actValid) {
        $planErr = !empty($planExportRes['json']['StatusText']) ? $planExportRes['json']['StatusText'] : ($planExportRes['error'] ?: 'Không có file Kế hoạch');
        $actErr = !empty($actExportRes['json']['StatusText']) ? $actExportRes['json']['StatusText'] : ($actExportRes['error'] ?: 'Không có file Thực tế');
        $errMsg = 'Không thể tải file Excel từ máy chủ HRM. Phản hồi HRM: KH [' . $planErr . '], TT [' . $actErr . '].';
        $addStep(8, 'Kiểm tra tính toàn vẹn file Excel', 'LOCAL', '', 'error', $errMsg);
        return [
            'success'     => false,
            'message'     => $errMsg,
            'steps'       => $steps,
            'urls_called' => $urlsCalled
        ];
    }
    $addStep(8, 'Kiểm tra tính toàn vẹn file Excel', 'LOCAL', '', 'success', 'Xác thực cấu trúc định dạng Excel (PK header) thành công. Kế hoạch: ' . ($planValid ? 'OK' : 'Không có') . ' | Thực tế: ' . ($actValid ? 'OK' : 'Không có') . '.');

    $planResult = ['inserted_rows' => 0, 'updated_rows' => 0, 'error_rows' => 0];
    $actResult = ['inserted_rows' => 0, 'updated_rows' => 0, 'error_rows' => 0];

    // 8. Bước 8: Xử lý UPSERT Kế hoạch
    if ($planValid) {
        $planResult = processExcelImport($conn, $planFile, 'DanhSachDuyetTangCaKeHoach_HRM.xlsx', 'plan', $currentUser);
        $addStep(9, 'Nạp dữ liệu Kế hoạch (UPSERT)', 'LOCAL', '', 'success', "+{$planResult['inserted_rows']} bản ghi mới, cập nhật {$planResult['updated_rows']} bản ghi, {$planResult['error_rows']} lỗi.");
    }

    // 9. Bước 9: Xử lý UPSERT Thực tế
    if ($actValid) {
        $actResult = processExcelImport($conn, $actualFile, 'DanhSachDuyetTangCaThucTe_HRM.xlsx', 'actual', $currentUser);
        $addStep(10, 'Nạp dữ liệu Thực tế (UPSERT)', 'LOCAL', '', 'success', "+{$actResult['inserted_rows']} bản ghi mới, cập nhật {$actResult['updated_rows']} bản ghi, {$actResult['error_rows']} lỗi.");
    }

    // 10. Bước 10: Tự động chạy đối soát cập nhật trạng thái
    $recSummary = runReconciliationInternal($conn);
    $addStep(11, 'Đối soát tự động 2 bước', 'LOCAL', '', 'success', "Đối soát hoàn tất {$recSummary['total']} ca. Khớp: {$recSummary['matched']}, Chờ xong Bước 2: {$recSummary['plan_only']}, Cần giải trình: {$recSummary['needs_explanation']}.");

    return [
        'success'         => true,
        'message'         => "Đồng bộ HRM thành công! Kế hoạch: +{$planResult['inserted_rows']} / sửa {$planResult['updated_rows']}. Thực tế: +{$actResult['inserted_rows']} / sửa {$actResult['updated_rows']}.",
        'steps'           => $steps,
        'urls_called'     => $urlsCalled,
        'plan_result'     => $planResult,
        'actual_result'   => $actResult,
        'reconciliation'  => $recSummary
    ];
}

try {
    switch ($action) {
        // =====================================================================
        // 1. LẤY CẤU HÌNH ĐỒNG BỘ HRM
        // =====================================================================
        case 'get_config':
            $cfg = loadHrmConfig($configFile);
            $safeCfg = $cfg;
            $safeCfg['has_password'] = !empty($cfg['password']);
            $safeCfg['password'] = !empty($cfg['password']) ? '••••••••' : '';
            echo json_encode(['success' => true, 'config' => $safeCfg], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. LƯU CẤU HÌNH ĐỒNG BỘ HRM
        // =====================================================================
        case 'save_config':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $cfg = loadHrmConfig($configFile);

            if (isset($input['username'])) $cfg['username'] = trim($input['username']);
            if (isset($input['password']) && trim($input['password']) !== '' && trim($input['password']) !== '••••••••') {
                $cfg['password'] = trim($input['password']);
            }
            if (isset($input['sync_interval_hours'])) {
                $hrs = max(1, min(24, intval($input['sync_interval_hours'])));
                $cfg['sync_interval_hours'] = $hrs;
            }
            if (isset($input['auto_sync_enabled'])) {
                $cfg['auto_sync_enabled'] = filter_var($input['auto_sync_enabled'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($input['sync_date_from'])) $cfg['sync_date_from'] = trim($input['sync_date_from']);
            if (isset($input['sync_date_to'])) $cfg['sync_date_to'] = trim($input['sync_date_to']);
            if (!empty($input['portal_endpoint'])) $cfg['portal_endpoint'] = trim($input['portal_endpoint']);
            if (!empty($input['plan_export_endpoint'])) $cfg['plan_export_endpoint'] = trim($input['plan_export_endpoint']);
            if (!empty($input['actual_export_endpoint'])) $cfg['actual_export_endpoint'] = trim($input['actual_export_endpoint']);
            unset($cfg['plan_url'], $cfg['actual_url']);

            saveHrmConfig($configFile, $cfg);

            echo json_encode([
                'success' => true,
                'message' => 'Đã lưu cấu hình kết nối & chu kỳ tự động đồng bộ HRM thành công!',
                'sync_interval_hours' => $cfg['sync_interval_hours'],
                'auto_sync_enabled' => $cfg['auto_sync_enabled']
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 3. KÍCH HOẠT ĐỒNG BỘ TỨC THÌ (TRIGGER SYNC NOW)
        // =====================================================================
        case 'trigger_sync':
            $cfg = loadHrmConfig($configFile);
            $currentUser = $_SESSION['user']['username'] ?? ($_SESSION['username'] ?? 'USER_TRIGGER');

            if (!empty($_POST['TuNgay'])) $cfg['sync_date_from'] = trim($_POST['TuNgay']);
            if (!empty($_POST['DenNgay'])) $cfg['sync_date_to'] = trim($_POST['DenNgay']);
            if (!empty($_GET['TuNgay'])) $cfg['sync_date_from'] = trim($_GET['TuNgay']);
            if (!empty($_GET['DenNgay'])) $cfg['sync_date_to'] = trim($_GET['DenNgay']);

            $syncRes = executeHrmSync($conn, $cfg, $cookieFile, $planFile, $actualFile, $currentUser);

            // Cập nhật kết quả vào file config
            $cfg['last_sync_time'] = date('Y-m-d H:i:s');
            $cfg['last_sync_status'] = $syncRes['success'] ? 'success' : 'failed';
            $cfg['last_sync_message'] = $syncRes['message'];
            if ($syncRes['success']) {
                $cfg['last_sync_stats'] = [
                    'plan_inserted' => $syncRes['plan_result']['inserted_rows'] ?? 0,
                    'plan_updated'  => $syncRes['plan_result']['updated_rows'] ?? 0,
                    'plan_errors'   => $syncRes['plan_result']['error_rows'] ?? 0,
                    'actual_inserted' => $syncRes['actual_result']['inserted_rows'] ?? 0,
                    'actual_updated'  => $syncRes['actual_result']['updated_rows'] ?? 0,
                    'actual_errors'   => $syncRes['actual_result']['error_rows'] ?? 0,
                ];
            }
            saveHrmConfig($configFile, $cfg);

            echo json_encode($syncRes, JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 4. CHẠY THEO LỊCH TRÌNH (CRON / BACKGROUND AUTO TRIGGER)
        // =====================================================================
        case 'cron':
        case 'check_schedule':
            $cfg = loadHrmConfig($configFile);
            if (!$cfg['auto_sync_enabled']) {
                echo json_encode(['success' => true, 'ran_sync' => false, 'message' => 'Tính năng tự động đồng bộ đang tắt.']);
                exit;
            }

            $intervalSec = intval($cfg['sync_interval_hours'] ?? 3) * 3600;
            $lastTime = !empty($cfg['last_sync_time']) ? strtotime($cfg['last_sync_time']) : 0;
            $timeSince = time() - $lastTime;

            if ($timeSince < $intervalSec) {
                $nextInMin = ceil(($intervalSec - $timeSince) / 60);
                echo json_encode([
                    'success' => true,
                    'ran_sync' => false,
                    'message' => "Chưa đến lịch đồng bộ tiếp theo (còn khoảng {$nextInMin} phút).",
                    'last_sync_time' => $cfg['last_sync_time'],
                    'interval_hours' => $cfg['sync_interval_hours']
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Đã đến hạn chạy -> Thực hiện đồng bộ
            $syncRes = executeHrmSync($conn, $cfg, $cookieFile, $planFile, $actualFile, 'HRM_CRON_AUTO');
            $cfg['last_sync_time'] = date('Y-m-d H:i:s');
            $cfg['last_sync_status'] = $syncRes['success'] ? 'success' : 'failed';
            $cfg['last_sync_message'] = $syncRes['message'];
            if ($syncRes['success']) {
                $cfg['last_sync_stats'] = [
                    'plan_inserted' => $syncRes['plan_result']['inserted_rows'] ?? 0,
                    'plan_updated'  => $syncRes['plan_result']['updated_rows'] ?? 0,
                    'plan_errors'   => $syncRes['plan_result']['error_rows'] ?? 0,
                    'actual_inserted' => $syncRes['actual_result']['inserted_rows'] ?? 0,
                    'actual_updated'  => $syncRes['actual_result']['updated_rows'] ?? 0,
                    'actual_errors'   => $syncRes['actual_result']['error_rows'] ?? 0,
                ];
            }
            saveHrmConfig($configFile, $cfg);

            $syncRes['ran_sync'] = true;
            echo json_encode($syncRes, JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý đồng bộ: ' . $e->getMessage()]);
}

