<?php
/**
 * Test HTTP Endpoints via cURL
 */

require_once __DIR__ . '/../config/db.php';
$latestRes = $conn->query("SELECT id FROM warehouse_issues ORDER BY id DESC LIMIT 1");
$realIssueId = ($latestRes && $r = $latestRes->fetch_assoc()) ? $r['id'] : 1;

$pages = [
    'index.php?mainpage=warehouse&subpage=issue_request',
    'index.php?mainpage=warehouse&subpage=approval',
    'index.php?mainpage=warehouse&subpage=materials',
    'index.php?mainpage=warehouse&subpage=reorder_tracking',
    'index.php?mainpage=warehouse&subpage=dashboard',
    'api/warehouse.php?action=get_materials',
    'api/warehouse.php?action=get_reorder_tracking',
    'api/warehouse.php?action=render_issue_pdf_html&issue_id=' . $realIssueId,
    'api/warehouse.php?action=export_issue_excel&issue_id=' . $realIssueId,
    'api/warehouse.php?action=export_issues_monthly_excel'
];

echo "=== KIỂM THỬ TRUY CẬP HTTP ENDPOINTS (APACHE SERVER) ===\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');

// Login as admin
curl_setopt($ch, CURLOPT_URL, 'http://localhost/myweb/api/login_process.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'username' => 'admin',
    'password' => 'admin'
]));
$loginRes = curl_exec($ch);

// Test each page
foreach ($pages as $p) {
    $url = 'http://localhost/myweb/' . $p;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, false);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $len = strlen($html);
    
    // Check for PHP fatal errors or notices in output
    $hasFatal = (stripos($html, 'Fatal error') !== false || stripos($html, 'Parse error') !== false);
    $statusText = ($httpCode === 200 && !$hasFatal) ? "[PASS]" : "[FAIL]";
    
    echo "{$statusText} {$p}\n";
    echo "       HTTP Code: {$httpCode} | Length: {$len} bytes\n";
    if ($hasFatal) {
        preg_match('/(Fatal error[^\n<]+)/i', $html, $m);
        echo "       Lỗi: " . ($m[1] ?? 'Fatal error detected') . "\n";
    }
}

curl_close($ch);
if (file_exists(__DIR__ . '/cookie.txt')) {
    @unlink(__DIR__ . '/cookie.txt');
}
