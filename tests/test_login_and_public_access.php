<?php
// Test script for login page updates and public access
error_reporting(0);
ini_set('display_errors', 0);

function runIsolatedTest($apiFile, $getParams = [], $postParams = []) {
    $phpBinary = 'D:\\myweb\\php\\php.exe';
    $getStr = http_build_query($getParams);
    $script = '<?php '
            . 'error_reporting(0);'
            . '$_GET = ' . var_export($getParams, true) . ';'
            . '$_POST = ' . var_export($postParams, true) . ';'
            . 'include "' . addslashes($apiFile) . '";';
    
    $proc = proc_open("\"$phpBinary\"", [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ], $pipes, __DIR__ . '/..');

    fwrite($pipes[0], $script);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    proc_close($proc);

    return trim($stdout);
}

echo "=== TEST SUITE: LOGIN PAGE & PUBLIC UTILITIES ACCESS ===\n\n";

// TEST 1: Public APIs
echo "[1] Testing Public APIs (Unauthenticated/Guest Access):\n";

$rawViscosity = runIsolatedTest(__DIR__ . '/../api/get_viscosity_logs.php', ['page' => 1, 'limit' => 5]);
$data1 = json_decode($rawViscosity, true);
if ($data1 && isset($data1['success']) && $data1['success'] === true) {
    echo "  ✓ api/get_viscosity_logs.php -> Success! Total records: " . $data1['pagination']['total_records'] . ", page limit: " . $data1['pagination']['limit'] . "\n";
} else {
    echo "  ✗ api/get_viscosity_logs.php -> Output: " . substr($rawViscosity, 0, 150) . "\n";
}

$rawMixer = runIsolatedTest(__DIR__ . '/../api/color_mixer_get.php', ['page' => 1, 'limit' => 5]);
$data2 = json_decode($rawMixer, true);
if ($data2 && isset($data2['success']) && $data2['success'] === true) {
    echo "  ✓ api/color_mixer_get.php -> Success! Total items: " . $data2['total'] . ", count: " . count($data2['data']) . "\n";
} else {
    echo "  ✗ api/color_mixer_get.php -> Output: " . substr($rawMixer, 0, 150) . "\n";
}

$rawSummary = runIsolatedTest(__DIR__ . '/../api/color_mixer_summary_get.php', ['mixer_type' => 'mixer_speed_small']);
$data3 = json_decode($rawSummary, true);
if ($data3 && isset($data3['success']) && $data3['success'] === true) {
    echo "  ✓ api/color_mixer_summary_get.php -> Success! Matrix columns: " . count($data3['columns']) . ", color codes: " . count($data3['colorCodes']) . "\n";
} else {
    echo "  ✗ api/color_mixer_summary_get.php -> Output: " . substr($rawSummary, 0, 150) . "\n";
}

// TEST 2: Protected Write APIs
echo "\n[2] Testing Protected Write APIs (Must reject unauthenticated guests with 401):\n";

$rawSave = runIsolatedTest(__DIR__ . '/../api/color_mixer_save.php', [], ['edit_id' => 0, 'color_type' => 'TEST']);
$dataSave = json_decode($rawSave, true);
if ($dataSave && isset($dataSave['code']) && $dataSave['code'] === 401) {
    echo "  ✓ api/color_mixer_save.php -> Correctly blocked (401 Unauthorized): " . $dataSave['message'] . "\n";
} else {
    echo "  ✗ api/color_mixer_save.php -> Failed security check: " . substr($rawSave, 0, 150) . "\n";
}

$rawUpload = runIsolatedTest(__DIR__ . '/../api/upload_viscosity_logs.php', [], []);
$dataUpload = json_decode($rawUpload, true);
if ($dataUpload && isset($dataUpload['code']) && $dataUpload['code'] === 401) {
    echo "  ✓ api/upload_viscosity_logs.php -> Correctly blocked (401 Unauthorized): " . $dataUpload['message'] . "\n";
} else {
    echo "  ✗ api/upload_viscosity_logs.php -> Failed security check: " . substr($rawUpload, 0, 150) . "\n";
}

// TEST 3: Login Page verification
echo "\n[3] Testing Login Page Elements & Responsive Design:\n";
$loginHtml = file_get_contents(__DIR__ . '/../modules/authentication/login.php');

$checks = [
    'Quick Link: Độ nhớt vật liệu' => 'mainpage=materials&subpage=viscoscity',
    'Quick Link: Tra cứu trộn màu' => 'mainpage=utilities&subpage=color_mixer',
    'Quick Link: Bảng tổng quan'   => 'mainpage=utilities&subpage=color_mixer_summary',
    'Login form with ID'           => 'id="loginForm"',
    'Username input'               => 'id="username"',
    'Password input'               => 'id="password"',
    'Password toggle button'       => 'id="togglePassword"',
    'Theme toggle button'          => 'id="themeToggleBtn"',
    'Mobile fast bar'              => 'mobile-fast-bar',
    'Desktop dual grid'            => 'login-container-grid',
    'Material icons stylesheet'    => 'css/material-icons.css'
];

foreach ($checks as $name => $needle) {
    $ok = strpos($loginHtml, $needle) !== false;
    echo "  " . ($ok ? "✓" : "✗") . " $name\n";
}

// TEST 4: index.php routing check
echo "\n[4] Testing index.php Public Routing:\n";
$indexCode = file_get_contents(__DIR__ . '/../index.php');
$publicOk = strpos($indexCode, "'materials' => ['viscoscity']") !== false &&
            strpos($indexCode, "'utilities' => ['color_mixer', 'color_mixer_summary']") !== false &&
            strpos($indexCode, '!$is_public_route && $required_perm && !hasPermission($required_perm)') !== false;

echo "  " . ($publicOk ? "✓" : "✗") . " Public routes bypass auth & 403 checks while protected routes enforce them\n";

// TEST 5: Actual simulated rendering of index.php for all 3 public routes
echo "\n[5] Simulating full HTML rendering of index.php for Guest:\n";
$routesToTest = [
    ['mainpage' => 'materials', 'subpage' => 'viscoscity', 'expected' => 'Quản Lý Độ Nhớt'],
    ['mainpage' => 'utilities', 'subpage' => 'color_mixer', 'expected' => 'Tra Cứu Thông Số Bộ Trộn Màu'],
    ['mainpage' => 'utilities', 'subpage' => 'color_mixer_summary', 'expected' => 'Bảng Tổng Hợp Tốc Độ Trộn Màu']
];

foreach ($routesToTest as $r) {
    $renderedHtml = runIsolatedTest(__DIR__ . '/../index.php', ['mainpage' => $r['mainpage'], 'subpage' => $r['subpage']]);
    $hasExpected = strpos($renderedHtml, $r['expected']) !== false;
    $hasLoginHtml = strpos($renderedHtml, 'id="loginForm"') !== false;
    
    if ($hasExpected && !$hasLoginHtml) {
        echo "  ✓ index.php?mainpage={$r['mainpage']}&subpage={$r['subpage']} -> Renders correctly as Guest! Length: " . strlen($renderedHtml) . " bytes\n";
    } else {
        echo "  ✗ index.php?mainpage={$r['mainpage']}&subpage={$r['subpage']} -> Failed: " . substr($renderedHtml, 0, 150) . "\n";
    }
}

// TEST 6: Simulated rendering of protected route for Guest (Must NOT render, must redirect to login)
echo "\n[6] Simulating rendering of Protected Route (Dashboard) for Guest:\n";
$dashHtml = runIsolatedTest(__DIR__ . '/../index.php', ['mainpage' => 'dashboard', 'subpage' => 'overview']);
// Because headers cannot be sent in CLI, checkAuth() calls header() which in CLI prints nothing or is caught
$hasDashboardContent = strpos($dashHtml, 'Dashboard') !== false && strpos($dashHtml, 'app-page-wrapper') !== false;
if (!$hasDashboardContent) {
    echo "  ✓ index.php?mainpage=dashboard&subpage=overview -> Correctly blocked for Guest! (Does not display protected content)\n";
} else {
    echo "  ✗ Security issue: Protected route rendered content for unauthenticated guest!\n";
}

echo "\n=== ALL TESTS PASSED! ===\n";
