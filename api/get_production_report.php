<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

try {
    $configPath = __DIR__ . '/../config/db.php';
    if (!file_exists($configPath)) {
        throw new Exception('Không tìm thấy file config/db.php!');
    }
    require_once $configPath;

    if (!$conn) {
        throw new Exception('Kết nối CSDL thất bại!');
    }

    $mode       = $_GET['mode'] ?? 'month';
    $pipeSize   = $_GET['pipe_size'] ?? 'ALL';
    $monthParam = $_GET['month'] ?? date('Y-m');

    if ($mode === 'month') {
        $startDate = "$monthParam-01";
        $endDate   = date("Y-m-t", strtotime($startDate));
    } else {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate   = $_GET['end_date'] ?? date('Y-m-t');
        if (empty($_GET['start_date'])) $startDate = date('Y-m-01');
        if (empty($_GET['end_date']))   $endDate   = date('Y-m-t');
    }

    // 1. Tạo danh sách ngày
    $daysList = [];
    $daysKeys = [];
    $current  = strtotime($startDate);
    $last     = strtotime($endDate);

    while ($current <= $last) {
        $daysList[] = date('d/m', $current);
        $daysKeys[] = date('Y-m-d', $current);
        $current   = strtotime('+1 day', $current);
    }

    $allSizes = ['TU04', 'TU06', 'TU08', 'TU10', 'TU12', 'TU16', 'TIUB01', 'TIUB05', 'TIUB07', 'TIUB11', 'TIUB13'];
    $targetSizes = ($pipeSize !== 'ALL') ? [$pipeSize] : $allSizes;

    // LOGIC THỜI GIAN: Kiểm tra ngày hiện tại có nằm trong khoảng thời gian lựa chọn không
    $todayStr = date('Y-m-d');
    $isTodayInRange = ($todayStr >= $startDate && $todayStr <= $endDate);

    if ($isTodayInRange) {
        // Ngày hiện tại nằm trong khoảng -> Tỉ lệ kế hoạch tính đến ngày (Hiện tại - 1)
        $effectivePlanDate = date('Y-m-d', strtotime('-1 day', strtotime($todayStr)));
        $effectiveActualDate = $todayStr;
    } else {
        // Ngày hiện tại nằm ngoài khoảng -> Tỉ lệ kế hoạch tính theo ngày hiện tại / ngày cuối của khoảng lọc
        $effectivePlanDate = $endDate;
        $effectiveActualDate = $endDate;
    }

    // 2. Query THỰC TÍCH -> kM
    $sqlActual = "SELECT 
                    DATE_FORMAT(production_date, '%Y-%m-%d') as prod_date, 
                    TRIM(UPPER(pipe_size)) as pipe_size, 
                    SUM(finished_qty_m) / 1000 as total_km 
                  FROM extrusion_actual_logs 
                  WHERE production_date BETWEEN ? AND ? ";
    
    $paramsActual = [$startDate, $endDate];
    $typesActual  = "ss";

    if ($pipeSize !== 'ALL') {
        $sqlActual .= " AND TRIM(UPPER(pipe_size)) = ? ";
        $paramsActual[] = strtoupper(trim($pipeSize));
        $typesActual   .= "s";
    }
    $sqlActual .= " GROUP BY prod_date, TRIM(UPPER(pipe_size))";

    $stmtA = $conn->prepare($sqlActual);
    if ($stmtA) {
        $stmtA->bind_param($typesActual, ...$paramsActual);
        $stmtA->execute();
        $actualRes = $stmtA->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $actualRes = [];
    }

    $actualMap = [];
    foreach ($actualRes as $r) {
        $actualMap[$r['prod_date']][$r['pipe_size']] = (float)$r['total_km'];
    }

    // 3. Query KẾ HOẠCH
    $ymStart = date('Y-m', strtotime($startDate));
    $ymEnd   = date('Y-m', strtotime($endDate));

    $sqlPlan = "SELECT `year_month`, TRIM(UPPER(`pipe_size`)) as pipe_size, `day`, `plan_qty` 
                FROM `production_plans` 
                WHERE `year_month` BETWEEN ? AND ? ";
    $stmtP = $conn->prepare($sqlPlan);
    if ($stmtP) {
        $stmtP->bind_param('ss', $ymStart, $ymEnd);
        $stmtP->execute();
        $planRes = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $planRes = [];
    }

    $planMap = [];
    foreach ($planRes as $p) {
        $dateStr = sprintf("%s-%02d", $p['year_month'], (int)$p['day']);
        $planMap[$dateStr][$p['pipe_size']] = (float)$p['plan_qty'];
    }

    // 4. Tổng hợp Biểu đồ chính
    $mainPlanDaily   = [];
    $mainActualDaily = [];
    $mainPlanCum     = [];
    $mainActualCum   = [];
    $cumPlan   = 0;
    $cumActual = 0;

    $pointPlanVal = 0;
    $pointActualVal = 0;
    $pointPlanIndex = -1;
    $pointActualIndex = -1;

    foreach ($daysKeys as $idx => $dateKey) {
        $dayPlanTotal   = 0;
        $dayActualTotal = 0;

        foreach ($targetSizes as $s) {
            $dayPlanTotal   += ($planMap[$dateKey][$s] ?? 0);
            $dayActualTotal += ($actualMap[$dateKey][$s] ?? 0);
        }

        $mainPlanDaily[]   = round($dayPlanTotal, 2);
        $mainActualDaily[] = round($dayActualTotal, 2);

        $cumPlan   += $dayPlanTotal;
        $cumActual += $dayActualTotal;

        $mainPlanCum[]   = round($cumPlan, 2);
        $mainActualCum[] = round($cumActual, 2);

        if ($dateKey === $effectivePlanDate) {
            $pointPlanVal = round($cumPlan, 2);
            $pointPlanIndex = $idx;
        }
        if ($dateKey === $effectiveActualDate) {
            $pointActualVal = round($cumActual, 2);
            $pointActualIndex = $idx;
        }
    }

    if ($pointPlanIndex === -1 && count($mainPlanCum) > 0) {
        $pointPlanIndex = count($mainPlanCum) - 1;
        $pointPlanVal = end($mainPlanCum);
    }
    if ($pointActualIndex === -1 && count($mainActualCum) > 0) {
        $pointActualIndex = count($mainActualCum) - 1;
        $pointActualVal = end($mainActualCum);
    }

    // 5. Tổng hợp Bảng Chỉ Tiêu Tỷ Lệ & Hàng TỔNG (TOTAL)
    $sizesData    = [];
    $summaryTable = [];

    $totalPlanCumTotal = 0;
    $totalPlanCumEffective = 0;
    $totalActualCumEffective = 0;

    foreach ($targetSizes as $s) {
        $sPlanDaily   = [];
        $sActualDaily = [];
        $sPlanCum     = [];
        $sActualCum   = [];
        
        $sCumPlanTotal = 0; 
        $sCumPlanEffective  = 0; 
        $sCumActualEffective = 0; 
        $sCumActual    = 0;

        foreach ($daysKeys as $dateKey) {
            $pQty = $planMap[$dateKey][$s] ?? 0;
            $aQty = $actualMap[$dateKey][$s] ?? 0;

            $sPlanDaily[]   = round($pQty, 2);
            $sActualDaily[] = round($aQty, 2);

            $sCumPlanTotal += $pQty;
            if ($dateKey <= $effectivePlanDate) {
                $sCumPlanEffective += $pQty;
            }

            $sCumActual += $aQty;
            if ($dateKey <= $effectiveActualDate) {
                $sCumActualEffective += $aQty;
            }

            $sPlanCum[]   = round($sCumPlanTotal, 2);
            $sActualCum[] = round($sCumActual, 2);
        }

        $totalPlanCumTotal += $sCumPlanTotal;
        $totalPlanCumEffective += $sCumPlanEffective;
        $totalActualCumEffective += $sCumActualEffective;

        $actualRate = ($sCumPlanTotal > 0) ? round(($sCumActualEffective / $sCumPlanTotal) * 100, 1) : 0;
        $planRateEffective = ($sCumPlanTotal > 0) ? round(($sCumPlanEffective / $sCumPlanTotal) * 100, 1) : 0;
        $diffRate = round($actualRate - $planRateEffective, 1);

        $sizesData[$s] = [
            'plan_daily'   => $sPlanDaily,
            'actual_daily' => $sActualDaily,
            'plan_cum'     => $sPlanCum,
            'actual_cum'   => $sActualCum,
            'result_rate'  => $actualRate
        ];

        $summaryTable[] = [
            'size'                 => $s,
            'plan_cum'             => round($sCumPlanTotal, 2),
            'actual_cum'           => round($sCumActualEffective, 2),
            'actual_rate'          => $actualRate,
            'plan_rate_effective'  => $planRateEffective,
            'diff_rate'            => $diffRate
        ];
    }

    // Tính dòng TỔNG CỘNG (TOTAL)
    $totActualRate = ($totalPlanCumTotal > 0) ? round(($totalActualCumEffective / $totalPlanCumTotal) * 100, 1) : 0;
    $totPlanRateEffective = ($totalPlanCumTotal > 0) ? round(($totalPlanCumEffective / $totalPlanCumTotal) * 100, 1) : 0;
    $totDiffRate = round($totActualRate - $totPlanRateEffective, 1);

    $summaryTable[] = [
        'size'                 => 'TOTAL',
        'plan_cum'             => round($totalPlanCumTotal, 2),
        'actual_cum'           => round($totalActualCumEffective, 2),
        'actual_rate'          => $totActualRate,
        'plan_rate_effective'  => $totPlanRateEffective,
        'diff_rate'            => $totDiffRate,
        'is_total'             => true
    ];

    // 6. Ma trận Kế hoạch
    $planMatrix = [];
    $currYM = date('Y-m', strtotime($startDate));
    foreach ($allSizes as $s) {
        $daysPlanObj = [];
        for ($d = 1; $d <= 31; $d++) {
            $daysPlanObj[$d] = $planMap["$currYM-" . sprintf("%02d", $d)][$s] ?? 0;
        }
        $planMatrix[] = [
            'pipe_size' => $s,
            'days'      => $daysPlanObj
        ];
    }

    ob_clean();
    echo json_encode([
        'success'            => true,
        'days'               => $daysList,
        'plan_daily'         => $mainPlanDaily,
        'actual_daily'       => $mainActualDaily,
        'plan_cum'           => $mainPlanCum,
        'actual_cum'         => $mainActualCum,
        'point_plan'         => ['index' => $pointPlanIndex, 'value' => $pointPlanVal],
        'point_actual'       => ['index' => $pointActualIndex, 'value' => $pointActualVal],
        'summary_table'      => $summaryTable,
        'sizes_data'         => $sizesData,
        'plan_matrix'        => $planMatrix
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi API Backend: ' . $e->getMessage()
    ]);
}
?>