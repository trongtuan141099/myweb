<?php
/**
 * Automated Verification Test for Quality Management - Yield Rate Tracking
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/quality_service.php';

global $conn;

echo "=== START TESTING QUALITY MODULE ===\n\n";

// 1. Test Filter Options
echo "1. Testing getQualityFilterOptions()...\n";
$filterOpts = getQualityFilterOptions($conn);
echo "   - Sizes found: " . count($filterOpts['sizes']) . " (e.g. " . implode(', ', array_slice($filterOpts['sizes'], 0, 3)) . ")\n";
echo "   - Extrusion machines: " . count($filterOpts['extrusion_machines']) . " (" . implode(', ', $filterOpts['extrusion_machines']) . ")\n";
echo "   - Komaki machines: " . count($filterOpts['komaki_machines']) . " (" . implode(', ', $filterOpts['komaki_machines']) . ")\n";
assert(count($filterOpts['sizes']) > 0, "No sizes found");
assert(count($filterOpts['extrusion_machines']) > 0, "No extrusion machines found");
echo "   -> [PASS] Filter options verified.\n\n";

// 2. Test Dashboard Retrieval
echo "2. Testing getYieldDashboardStats()...\n";
$dashboard = getYieldDashboardStats($conn, [
    'period_mode' => 'month',
    'year' => 2026,
    'month' => 9
]);

assert(isset($dashboard['kpi']), "Dashboard KPI missing");
echo "   - KPI Total Produced: " . number_format($dashboard['kpi']['total_produced']) . "\n";
echo "   - KPI Total Good: " . number_format($dashboard['kpi']['total_good']) . "\n";
echo "   - KPI Average Yield: " . $dashboard['kpi']['yield_rate'] . "%\n";
echo "   - KPI Benchmark Yield: " . $dashboard['kpi']['benchmark_rate'] . "%\n";
echo "   - KPI Total Defects: " . number_format($dashboard['kpi']['total_defect']) . "\n";
echo "   - Trend data points: " . count($dashboard['trend']) . "\n";
echo "   - Extruder comparison count: " . count($dashboard['extrusion_comparison']) . "\n";
echo "   - Size comparison count: " . count($dashboard['size_comparison']) . "\n";
echo "   - Pareto defects count: " . count($dashboard['pareto_defects']) . "\n";
echo "   - Matrix machines count: " . count($dashboard['matrix']['machines']) . "\n";

assert($dashboard['kpi']['total_produced'] > 0, "Total produced should be > 0");
assert(count($dashboard['trend']) > 0, "Trend points should be > 0");
assert(count($dashboard['pareto_defects']) === 5, "Pareto defects should have 5 categories (A1-A5)");
assert(count($dashboard['matrix']['machines']) > 0, "Matrix machines should be > 0");
echo "   -> [PASS] Dashboard data structure and calculations verified.\n\n";

// 3. Test Yield Records Listing with Filters
echo "3. Testing getYieldRecordsList()...\n";
$records = getYieldRecordsList($conn, [
    'period_mode' => 'month',
    'year' => 2026,
    'month' => 9
], 1, 10);
assert(isset($records['data']), "Records missing data array");
echo "   - Total records matching filter: " . $records['pagination']['total_records'] . "\n";
echo "   - Rows fetched on page 1: " . count($records['data']) . "\n";
if (count($records['data']) > 0) {
    $first = $records['data'][0];
    echo "   - Sample Row 1: Lot {$first['lot_no']}, Size: {$first['size']}, Extruder: {$first['extrusion_machine']}, Yield: {$first['yield_rate']}%\n";
}
assert($records['pagination']['total_records'] > 0, "Total records should be > 0");
echo "   -> [PASS] Records listing and pagination verified.\n\n";

// 4. Test Manual Record CRUD
echo "4. Testing Manual CRUD (saveYieldRecord & deleteYieldRecord)...\n";
$testRecord = [
    'komaki_date' => '2026-09-29',
    'extrusion_date' => '2026-09-28',
    'komaki_machine' => 'ST01',
    'extrusion_machine' => 'PL08',
    'size' => 'TEST-01',
    'lot_no' => 'LOT-TEST-999',
    'product_code' => 'PROD-TEST',
    'material_type' => 'Zin',
    'good_qty' => 950,
    'total_qty' => 1000,
    'defect_qty' => 50,
    'defect_a1' => 20,
    'defect_a2' => 10,
    'defect_a3' => 10,
    'defect_a4' => 5,
    'defect_a5' => 5,
    'note' => 'Test entry automated'
];

$saveRes = saveYieldRecord($conn, $testRecord, 'TEST_SUITE');
assert($saveRes['success'] === true, "Failed to save record: " . ($saveRes['message'] ?? ''));
$newRecordId = $saveRes['id'];
echo "   - Created test record ID: {$newRecordId}\n";

// Update the record
$testRecord['id'] = $newRecordId;
$testRecord['good_qty'] = 980;
$testRecord['defect_qty'] = 20;
$updateRes = saveYieldRecord($conn, $testRecord, 'TEST_SUITE');
assert($updateRes['success'] === true, "Failed to update record: " . ($updateRes['message'] ?? ''));
echo "   - Updated test record good_qty to 980\n";

// Delete the record
$delRes = deleteYieldRecord($conn, $newRecordId);
assert($delRes['success'] === true, "Failed to delete record: " . ($delRes['message'] ?? ''));
echo "   - Deleted test record ID: {$newRecordId}\n";
echo "   -> [PASS] Manual Record CRUD verified.\n\n";

// 5. Test Benchmark CRUD
echo "5. Testing Benchmark Management...\n";
$benchRes = saveQualityBenchmark($conn, [
    'size' => 'TEST-SIZE-BM',
    'extrusion_machine' => 'PL08',
    'benchmark_rate' => 98.50,
    'min_acceptable_rate' => 95.00,
    'description' => 'Benchmark test note'
], 'TEST_SUITE');
assert($benchRes['success'] === true, "Failed to save benchmark: " . ($benchRes['message'] ?? ''));
echo "   - Saved benchmark for TEST-SIZE-BM\n";

$allBenchmarks = getQualityBenchmarksList($conn);
$foundBm = false;
foreach ($allBenchmarks as $bm) {
    if ($bm['size'] === 'TEST-SIZE-BM') {
        $foundBm = true;
        break;
    }
}
assert($foundBm, "TEST-SIZE-BM not found in benchmarks list");
echo "   - Benchmark found in list\n";

// Clean up benchmark
$conn->query("DELETE FROM quality_benchmarks WHERE size = 'TEST-SIZE-BM'");
echo "   -> [PASS] Benchmark management verified.\n\n";

// 6. Test Investigation Ticket Creation & Workflow
echo "6. Testing Investigation Ticket Management...\n";
$invData = [
    'yield_record_id' => null,
    'investigation_date' => '2026-09-29',
    'size' => '25x50',
    'product_code' => 'PROD-INV',
    'extrusion_machine' => 'PL05',
    'komaki_machine' => 'ST02',
    'extrusion_date' => '2026-09-20',
    'komaki_date' => '2026-09-21',
    'lot_no' => 'LOT-INV-TEST',
    'yield_rate' => 91.20,
    'rate_a1' => 5.2,
    'rate_a2' => 2.1,
    'rate_a3' => 0.5,
    'rate_a4' => 0.6,
    'rate_a5' => 0.4,
    'status_description' => 'Tỉ lệ A1 cao bất thường do xước đầu đùn',
    'root_cause' => 'Kẹt cặn nhựa tại die head',
    'countermeasure' => 'Vệ sinh và bảo dưỡng die head máy PL05',
    'assigned_to' => 'Nguyễn Văn Test',
    'result_status' => 'Đang đối ứng'
];

$invRes = saveQualityInvestigation($conn, $invData, 'TEST_SUITE');
assert($invRes['success'] === true, "Failed to create investigation ticket: " . ($invRes['message'] ?? ''));
$invId = $invRes['id'];
$invCode = $invRes['code'];
echo "   - Created investigation ticket ID: {$invId}, Code: {$invCode}\n";

// Retrieve investigations list
$invList = getQualityInvestigationsList($conn, ['search' => 'LOT-INV-TEST']);
$foundInv = false;
foreach ($invList as $item) {
    if ($item['id'] == $invId) {
        $foundInv = true;
        assert($item['result_status'] === 'Đang đối ứng', "Status mismatch");
        break;
    }
}
assert($foundInv, "Investigation ticket not retrieved in list");
echo "   - Retrieved investigation ticket correctly\n";

// Clean up
deleteQualityInvestigation($conn, $invId);
echo "   -> [PASS] Investigation ticket workflow verified.\n\n";

// 7. Test PDF and Excel export output
echo "7. Testing PDF Rendering & Excel Output...\n";
$_GET['month'] = 9;
$_GET['year'] = 2026;
$_GET['period_mode'] = 'month';

// Test PDF HTML renderer
$_GET['action'] = 'render_pdf_html';
ob_start();
include __DIR__ . '/../api/quality_export.php';
$pdfHtml = ob_get_clean();

assert(strpos($pdfHtml, 'BÁO CÁO TỔNG HỢP TỈ LỆ THÀNH PHẨM (良品率)') !== false, "PDF HTML missing header");
assert(strpos($pdfHtml, 'BẢNG PHÂN BỐ LỖI THEO NGUYÊN NHÂN (PARETO A1 - A5)') !== false, "PDF HTML missing Pareto section");
assert(strpos($pdfHtml, 'ĐỐI ỨNG BẤT THƯỜNG TRONG KỲ') !== false, "PDF HTML missing Countermeasure section");
echo "   - Generated PDF Printable HTML successfully (" . strlen($pdfHtml) . " bytes)\n";
echo "   -> [PASS] Export & Report generation verified.\n\n";

echo "=== ALL VERIFICATION TESTS PASSED SUCCESSFULLY! ===\n";
