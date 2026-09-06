<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

$month = $_GET['month'] ?? date('Y-m');
$pipeSize = $_GET['pipe_size'] ?? 'ALL';

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)substr($month, 5, 2), (int)substr($month, 0, 4));

$planDaily   = array_fill(1, $daysInMonth, 0.0);
$actualDaily = array_fill(1, $daysInMonth, 0.0);

// 1. Kế hoạch
$planSql = "SELECT day, SUM(plan_qty) as total FROM production_plans WHERE year_month = ?";
if ($pipeSize !== 'ALL') $planSql .= " AND pipe_size = '$pipeSize'";
$planSql .= " GROUP BY day";

$stmt = $conn->prepare($planSql);
$stmt->bind_param("s", $month);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $planDaily[(int)$row['day']] = (float)$row['total'];
}
$stmt->close();

// 2. Thực tích
$actualSql = "SELECT day, SUM(actual_qty) as total FROM production_actuals WHERE year_month = ?";
if ($pipeSize !== 'ALL') $actualSql .= " AND pipe_size = '$pipeSize'";
$actualSql .= " GROUP BY day";

$stmt = $conn->prepare($actualSql);
$stmt->bind_param("s", $month);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $actualDaily[(int)$row['day']] = (float)$row['total'];
}
$stmt->close();

// 3. Lũy tích
$planCum = []; $actualCum = [];
$pSum = 0; $aSum = 0;

for ($d = 1; $d <= $daysInMonth; $d++) {
    $pSum += $planDaily[$d];
    $pCum[] = round($pSum, 2);

    $aSum += $actualDaily[$d];
    $aCum[] = round($aSum, 2);
}

// 4. Bảng Kết quả tỷ lệ từng Size
$summaryTable = [];
$sizes = ['TU04', 'TU06', 'TU08', 'TU10', 'TU12', 'TU16', 'TIUB01', 'TIUB05', 'TIUB07', 'TIUB11', 'TIUB13'];

foreach ($sizes as $s) {
    $sPlan   = (float)($conn->query("SELECT SUM(plan_qty) as total FROM production_plans WHERE year_month = '$month' AND pipe_size = '$s'")->fetch_assoc()['total'] ?? 0);
    $sActual = (float)($conn->query("SELECT SUM(actual_qty) as total FROM production_actuals WHERE year_month = '$month' AND pipe_size = '$s'")->fetch_assoc()['total'] ?? 0);

    $planRate   = ($pSum > 0) ? round(($sPlan / $pSum) * 100, 2) : 0;
    $actualRate = ($sPlan > 0) ? round(($sActual / $sPlan) * 100, 2) : 0;
    $resultRate = ($actualRate > 0) ? round($actualRate * 0.9, 2) : 0;

    $summaryTable[] = [
        'size' => $s,
        'plan_rate' => $planRate,
        'actual_rate' => $actualRate,
        'result_rate' => $resultRate
    ];
}

// 5. Ma trận Kế hoạch đầy đủ cho Popup Chỉnh Sửa
$planMatrix = [];
foreach ($sizes as $s) {
    $daysData = array_fill(1, $daysInMonth, 0.0);
    $mRes = $conn->query("SELECT day, plan_qty FROM production_plans WHERE year_month = '$month' AND pipe_size = '$s'");
    while ($r = $mRes->fetch_assoc()) {
        $daysData[(int)$r['day']] = (float)$r['plan_qty'];
    }
    $planMatrix[] = [
        'pipe_size' => $s,
        'days' => $daysData
    ];
}

echo json_encode([
    'success' => true,
    'days' => array_keys($planDaily),
    'plan_daily' => array_values($planDaily),
    'actual_daily' => array_values($actualDaily),
    'plan_cum' => $pCum,
    'actual_cum' => $aCum,
    'summary_table' => $summaryTable,
    'plan_matrix' => $planMatrix,
    'total_plan' => $pSum,
    'total_actual' => $aSum
]);
?>