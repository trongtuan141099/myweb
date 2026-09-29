<?php
/**
 * Quality Management Service (Quản lý chất lượng & Theo dõi tỉ lệ thành phẩm 良品率)
 * DX Plastic Group - Factory Management System
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

/**
 * Chuyển đổi ngày từ số sê-ri Excel hoặc chuỗi ngày sang chuẩn Y-m-d
 */
if (!function_exists('parseQualityDate')) {
function parseQualityDate($val) {
    if (empty($val)) return null;
    $val = trim((string)$val);
    if (is_numeric($val) && floatval($val) > 30000 && floatval($val) < 60000) {
        $days = intval($val);
        $base = new DateTime('1899-12-30');
        $base->modify("+{$days} days");
        return $base->format('Y-m-d');
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }
    $ts = strtotime($val);
    if ($ts) return date('Y-m-d', $ts);
    return null;
}
}

/**
 * Chuẩn hóa giờ bobin (từ số thập phân Excel hoặc chuỗi giờ sang định dạng H:i)
 */
if (!function_exists('formatQualityBobbinTime')) {
function formatQualityBobbinTime($val) {
    if ($val === null || $val === '') return '00:00';
    $val = trim((string)$val);
    if (is_numeric($val)) {
        $num = floatval($val);
        // Phân số trong ngày (0.0 đến 1.0 trong Excel)
        if ($num >= 0 && $num < 1.0) {
            $totalSeconds = (int)round($num * 86400);
            $h = (int)floor($totalSeconds / 3600) % 24;
            $m = (int)floor(($totalSeconds % 3600) / 60);
            return sprintf('%02d:%02d', $h, $m);
        }
        // Trường hợp ngày + giờ
        if ($num >= 1.0 && $num < 60000) {
            $fraction = $num - floor($num);
            $totalSeconds = (int)round($fraction * 86400);
            $h = (int)floor($totalSeconds / 3600) % 24;
            $m = (int)floor(($totalSeconds % 3600) / 60);
            return sprintf('%02d:%02d', $h, $m);
        }
    }
    if (preg_match('/^(\d{1,2}):(\d{1,2})/', $val, $m)) {
        return sprintf('%02d:%02d', intval($m[1]), intval($m[2]));
    }
    return $val ?: '00:00';
}
}

/**
 * Tạo mã định danh duy nhất (Unique ID) gồm tổ hợp 4 trường:
 * Giờ bobin, Ngày đùn, Máy cuộn, Ngày cuộn
 */
if (!function_exists('generateYieldUpsertKey')) {
function generateYieldUpsertKey($bobbinTime, $extrusionDate, $komakiMachine, $komakiDate) {
    $bTime = formatQualityBobbinTime($bobbinTime);
    $eDate = parseQualityDate($extrusionDate) ?: '0000-00-00';
    $kMac  = strtoupper(trim((string)$komakiMachine)) ?: 'ST01';
    $kDate = parseQualityDate($komakiDate) ?: '0000-00-00';
    return "{$bTime}|{$eDate}|{$kMac}|{$kDate}";
}
}

/**
 * Lấy danh sách quy tắc phân loại nguyên vật liệu theo ký tự thứ 2
 */
if (!function_exists('getQualityMaterialRules')) {
function getQualityMaterialRules($conn) {
    $rules = [];
    $res = $conn->query("SELECT * FROM quality_material_rules ORDER BY material_group ASC, char_code ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rules[strtoupper(trim($r['char_code']))] = $r;
        }
    }
    return $rules;
}
}

/**
 * Lưu hoặc cập nhật quy tắc phân loại nguyên vật liệu
 */
if (!function_exists('saveQualityMaterialRule')) {
function saveQualityMaterialRule($conn, $data) {
    $id = !empty($data['id']) ? intval($data['id']) : 0;
    $charCode = strtoupper(trim($data['char_code'] ?? ''));
    $name = trim($data['material_name'] ?? '');
    $group = (isset($data['material_group']) && $data['material_group'] === 'recycled') ? 'recycled' : 'virgin';
    $desc = trim($data['description'] ?? '');

    if (empty($charCode) || empty($name)) {
        return ['success' => false, 'message' => 'Vui lòng nhập Ký tự mã LOT và Tên vật liệu!'];
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE quality_material_rules SET char_code = ?, material_name = ?, material_group = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $charCode, $name, $group, $desc, $id);
        $ok = $stmt->execute();
        $err = $stmt->error ?: $conn->error;
        $stmt->close();
        return ['success' => $ok, 'message' => $ok ? 'Cập nhật quy tắc vật liệu thành công!' : $err];
    } else {
        $stmt = $conn->prepare("INSERT INTO quality_material_rules (char_code, material_name, material_group, description) 
                                VALUES (?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE material_name = VALUES(material_name), material_group = VALUES(material_group), description = VALUES(description)");
        $stmt->bind_param("ssss", $charCode, $name, $group, $desc);
        $ok = $stmt->execute();
        $err = $stmt->error ?: $conn->error;
        $newId = $stmt->insert_id;
        $stmt->close();
        return ['success' => $ok, 'message' => $ok ? 'Lưu quy tắc vật liệu thành công!' : $err, 'id' => $newId];
    }
}
}

/**
 * Xóa quy tắc phân loại vật liệu
 */
if (!function_exists('deleteQualityMaterialRule')) {
function deleteQualityMaterialRule($conn, $id) {
    $id = intval($id);
    $stmt = $conn->prepare("DELETE FROM quality_material_rules WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return ['success' => $ok, 'message' => $ok ? 'Đã xóa quy tắc vật liệu.' : $conn->error];
}
}

/**
 * Nhận diện loại nguyên vật liệu dựa trên ký tự thứ 2 từ trái sang phải của mã LOT
 */
if (!function_exists('resolveMaterialByLot')) {
function resolveMaterialByLot($lotNo, $rules = null, $conn = null) {
    $lotNo = trim((string)$lotNo);
    $c2 = (strlen($lotNo) >= 2) ? strtoupper(substr($lotNo, 1, 1)) : '';

    if ($rules === null && $conn !== null) {
        $rules = getQualityMaterialRules($conn);
    }

    if (!empty($c2) && isset($rules[$c2])) {
        $r = $rules[$c2];
        return [
            'char_code'      => $c2,
            'material_name'  => $r['material_name'],
            'material_group' => $r['material_group'],
            'group_label'    => ($r['material_group'] === 'recycled') ? 'Vật liệu nghiền' : 'Vật liệu nguyên sinh'
        ];
    }

    // Mặc định dự phòng nếu chưa cấu hình ký tự
    $isRecycled = in_array($c2, ['C', 'B', 'K', 'E', 'V']);
    return [
        'char_code'      => $c2,
        'material_name'  => $isRecycled ? 'Nhựa Nghiền (' . $c2 . ')' : 'Nhựa Zin (' . ($c2 ?: 'N/A') . ')',
        'material_group' => $isRecycled ? 'recycled' : 'virgin',
        'group_label'    => $isRecycled ? 'Vật liệu nghiền' : 'Vật liệu nguyên sinh'
    ];
}
}

/**
 * Lấy các tùy chọn cho bộ lọc (Size, Máy đùn, Máy cuộn, Vật liệu, Năm...)
 */
function getQualityFilterOptions($conn) {
    $sizes = [];
    $res = $conn->query("SELECT DISTINCT size FROM quality_yield_records WHERE size IS NOT NULL AND size != '' ORDER BY size ASC");
    if ($res) while ($r = $res->fetch_assoc()) $sizes[] = $r['size'];

    $extMachines = [];
    $res = $conn->query("SELECT DISTINCT extrusion_machine FROM quality_yield_records WHERE extrusion_machine IS NOT NULL AND extrusion_machine != '' ORDER BY extrusion_machine ASC");
    if ($res) while ($r = $res->fetch_assoc()) $extMachines[] = $r['extrusion_machine'];

    $komMachines = [];
    $res = $conn->query("SELECT DISTINCT komaki_machine FROM quality_yield_records WHERE komaki_machine IS NOT NULL AND komaki_machine != '' ORDER BY komaki_machine ASC");
    if ($res) while ($r = $res->fetch_assoc()) $komMachines[] = $r['komaki_machine'];

    $materials = [];
    $res = $conn->query("SELECT DISTINCT material_type FROM quality_yield_records WHERE material_type IS NOT NULL AND material_type != '' ORDER BY material_type ASC");
    if ($res) while ($r = $res->fetch_assoc()) $materials[] = $r['material_type'];

    // Lấy khoảng năm có dữ liệu
    $years = [];
    $res = $conn->query("SELECT DISTINCT YEAR(komaki_date) as y FROM quality_yield_records WHERE komaki_date IS NOT NULL UNION SELECT DISTINCT YEAR(extrusion_date) as y FROM quality_yield_records WHERE extrusion_date IS NOT NULL ORDER BY y DESC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            if (!empty($r['y'])) $years[] = intval($r['y']);
        }
    }
    if (empty($years)) $years[] = intval(date('Y'));

    return [
        'sizes' => $sizes,
        'extrusion_machines' => $extMachines,
        'komaki_machines' => $komMachines,
        'materials' => $materials,
        'material_rules' => array_values(getQualityMaterialRules($conn)),
        'material_groups' => [
            'all' => 'Tất cả vật liệu',
            'virgin' => 'Vật liệu nguyên sinh (Zin)',
            'recycled' => 'Vật liệu nghiền (Tái sinh)'
        ],
        'years' => $years
    ];
}

/**
 * Xây dựng mệnh đề WHERE dựa trên các bộ lọc
 */
function buildQualityWhereClause($conn, $filters) {
    $where = "WHERE 1=1";
    $dateCol = (!empty($filters['date_type']) && $filters['date_type'] === 'extrusion') ? 'r.extrusion_date' : 'r.komaki_date';

    $periodMode = $filters['period_mode'] ?? 'day';
    if ($periodMode === 'month') {
        $year = intval($filters['year'] ?? date('Y'));
        $month = intval($filters['month'] ?? 0);
        $where .= " AND YEAR({$dateCol}) = {$year}";
        if ($month > 0 && $month <= 12) {
            $where .= " AND MONTH({$dateCol}) = {$month}";
        }
    } else {
        // Mode Day (Khoảng ngày)
        if (!empty($filters['date_from'])) {
            $df = $conn->real_escape_string($filters['date_from']);
            $where .= " AND {$dateCol} >= '{$df}'";
        }
        if (!empty($filters['date_to'])) {
            $dt = $conn->real_escape_string($filters['date_to']);
            $where .= " AND {$dateCol} <= '{$dt}'";
        }
    }

    if (!empty($filters['extrusion_machine']) && $filters['extrusion_machine'] !== 'ALL') {
        $em = $conn->real_escape_string($filters['extrusion_machine']);
        $where .= " AND r.extrusion_machine = '{$em}'";
    }

    if (!empty($filters['komaki_machine'])) {
        $km = $conn->real_escape_string($filters['komaki_machine']);
        $where .= " AND r.komaki_machine = '{$km}'";
    }

    if (!empty($filters['size'])) {
        $sz = $conn->real_escape_string($filters['size']);
        $where .= " AND r.size = '{$sz}'";
    }

    if (!empty($filters['material_type'])) {
        $mt = $conn->real_escape_string($filters['material_type']);
        $where .= " AND r.material_type = '{$mt}'";
    }

    // Lọc theo nhóm nguyên vật liệu (Nguyên sinh vs Nghiền)
    if (!empty($filters['material_group']) && in_array($filters['material_group'], ['virgin', 'recycled'])) {
        $mg = $conn->real_escape_string($filters['material_group']);
        $where .= " AND r.material_group = '{$mg}'";
    }

    if (!empty($filters['search'])) {
        $s = $conn->real_escape_string($filters['search']);
        $where .= " AND (r.lot_no LIKE '%{$s}%' OR r.product_code LIKE '%{$s}%' OR r.size LIKE '%{$s}%')";
    }

    // Lọc theo trạng thái chất lượng (đạt benchmark, cảnh báo, bất thường)
    if (!empty($filters['quality_status'])) {
        $st = $filters['quality_status'];
        if ($st === 'pass') {
            $where .= " AND r.yield_rate >= r.benchmark_rate";
        } elseif ($st === 'warning') {
            $where .= " AND r.yield_rate < r.benchmark_rate AND r.yield_rate >= 95.00";
        } elseif ($st === 'danger') {
            $where .= " AND r.yield_rate < 95.00";
        }
    }

    return [$where, $dateCol];
}

/**
 * Tổng hợp dữ liệu Dashboard & Thống kê Tỉ lệ thành phẩm
 */
function getYieldDashboardStats($conn, $filters) {
    list($where, $dateCol) = buildQualityWhereClause($conn, $filters);

    // 1. Thống kê KPI tổng quan
    $sqlKpi = "
        SELECT 
            COUNT(*) as total_lots,
            COALESCE(SUM(r.total_qty), 0) as total_produced,
            COALESCE(SUM(r.good_qty), 0) as total_good,
            COALESCE(SUM(r.defect_qty), 0) as total_defect,
            COALESCE(AVG(r.yield_rate), 0) as avg_yield_rate,
            COALESCE(AVG(r.benchmark_rate), 98.00) as avg_benchmark,
            SUM(CASE WHEN r.yield_rate >= r.benchmark_rate THEN 1 ELSE 0 END) as count_pass,
            SUM(CASE WHEN r.yield_rate < r.benchmark_rate AND r.yield_rate >= 95.00 THEN 1 ELSE 0 END) as count_warning,
            SUM(CASE WHEN r.yield_rate < 95.00 THEN 1 ELSE 0 END) as count_danger,
            COALESCE(SUM(r.defect_a1), 0) as sum_a1,
            COALESCE(SUM(r.defect_a2), 0) as sum_a2,
            COALESCE(SUM(r.defect_a3), 0) as sum_a3,
            COALESCE(SUM(r.defect_a4), 0) as sum_a4,
            COALESCE(SUM(r.defect_a5), 0) as sum_a5
        FROM quality_yield_records r
        {$where}
    ";
    $resKpi = $conn->query($sqlKpi);
    $kpi = $resKpi ? $resKpi->fetch_assoc() : [];

    $totalProduced = intval($kpi['total_produced'] ?? 0);
    $totalGood     = intval($kpi['total_good'] ?? 0);
    $totalDefect   = intval($kpi['total_defect'] ?? 0);
    $realYieldRate = ($totalProduced > 0) ? round(($totalGood / $totalProduced) * 100, 2) : 0.00;
    $realDefectRate = round(100.0 - $realYieldRate, 2);

    // Đếm số lượng phiếu điều tra đang chờ xử lý
    $resPendingInv = $conn->query("SELECT COUNT(*) as c FROM quality_investigations WHERE result_status NOT IN ('Hoàn thành', 'Closed')");
    $pendingInvCount = $resPendingInv ? intval($resPendingInv->fetch_assoc()['c']) : 0;

    // 2. Thống kê theo Nhóm Nguyên Vật Liệu (Vật liệu nguyên sinh vs Vật liệu nghiền)
    $sqlMat = "
        SELECT 
            COALESCE(r.material_group, 'virgin') as mat_grp,
            COUNT(*) as lot_count,
            COALESCE(SUM(r.total_qty), 0) as total_produced,
            COALESCE(SUM(r.good_qty), 0) as total_good,
            COALESCE(SUM(r.defect_qty), 0) as total_defect,
            ROUND((SUM(r.good_qty) / NULLIF(SUM(r.total_qty), 0)) * 100, 2) as yield_rate
        FROM quality_yield_records r
        {$where}
        GROUP BY r.material_group
    ";
    $resMat = $conn->query($sqlMat);
    $matSummary = [
        'virgin' => ['name' => 'Vật liệu nguyên sinh', 'produced' => 0, 'good' => 0, 'defect' => 0, 'yield_rate' => 0.0, 'lots' => 0, 'ratio_pct' => 0.0],
        'recycled' => ['name' => 'Vật liệu nghiền', 'produced' => 0, 'good' => 0, 'defect' => 0, 'yield_rate' => 0.0, 'lots' => 0, 'ratio_pct' => 0.0]
    ];
    if ($resMat) {
        while ($mRow = $resMat->fetch_assoc()) {
            $grp = ($mRow['mat_grp'] === 'recycled') ? 'recycled' : 'virgin';
            $matSummary[$grp]['produced']   = intval($mRow['total_produced']);
            $matSummary[$grp]['good']       = intval($mRow['total_good']);
            $matSummary[$grp]['defect']     = intval($mRow['total_defect']);
            $matSummary[$grp]['yield_rate'] = floatval($mRow['yield_rate'] ?? 0);
            $matSummary[$grp]['lots']       = intval($mRow['lot_count']);
        }
    }
    $sumProduced = $matSummary['virgin']['produced'] + $matSummary['recycled']['produced'];
    if ($sumProduced > 0) {
        $matSummary['virgin']['ratio_pct'] = round(($matSummary['virgin']['produced'] / $sumProduced) * 100, 1);
        $matSummary['recycled']['ratio_pct'] = round(($matSummary['recycled']['produced'] / $sumProduced) * 100, 1);
    }

    // 3. Xu hướng tỉ lệ thành phẩm theo thời gian (Trend Line Chart)
    $sqlTrend = "
        SELECT 
            {$dateCol} as report_date,
            SUM(r.total_qty) as produced,
            SUM(r.good_qty) as good,
            ROUND((SUM(r.good_qty) / NULLIF(SUM(r.total_qty), 0)) * 100, 2) as yield_rate,
            ROUND(AVG(r.benchmark_rate), 2) as benchmark_rate
        FROM quality_yield_records r
        {$where}
        AND {$dateCol} IS NOT NULL
        GROUP BY {$dateCol}
        ORDER BY {$dateCol} ASC
        LIMIT 60
    ";
    $resTrend = $conn->query($sqlTrend);
    $trendData = [];
    if ($resTrend) {
        while ($t = $resTrend->fetch_assoc()) {
            $trendData[] = [
                'date' => $t['report_date'],
                'yield_rate' => floatval($t['yield_rate'] ?? 0),
                'benchmark_rate' => floatval($t['benchmark_rate'] ?? 98.00),
                'produced' => intval($t['produced']),
                'good' => intval($t['good'])
            ];
        }
    }

    // 4. Biểu đồ Cột Chồng (Stacked Bar Chart) phân tích lỗi A1-A5 theo Line Máy Đùn & Nhóm Vật Liệu
    $sqlStacked = "
        SELECT 
            r.extrusion_machine,
            COALESCE(SUM(r.total_qty), 0) as total_produced,
            COALESCE(SUM(r.good_qty), 0) as total_good,
            COALESCE(SUM(r.defect_qty), 0) as total_defect,
            COALESCE(SUM(r.defect_a1), 0) as sum_a1,
            COALESCE(SUM(r.defect_a2), 0) as sum_a2,
            COALESCE(SUM(r.defect_a3), 0) as sum_a3,
            COALESCE(SUM(r.defect_a4), 0) as sum_a4,
            COALESCE(SUM(r.defect_a5), 0) as sum_a5,
            COALESCE(SUM(CASE WHEN r.material_group = 'virgin' THEN r.defect_qty ELSE 0 END), 0) as defect_virgin,
            COALESCE(SUM(CASE WHEN r.material_group = 'recycled' THEN r.defect_qty ELSE 0 END), 0) as defect_recycled,
            COALESCE(SUM(CASE WHEN r.material_group = 'virgin' THEN r.total_qty ELSE 0 END), 0) as produced_virgin,
            COALESCE(SUM(CASE WHEN r.material_group = 'recycled' THEN r.total_qty ELSE 0 END), 0) as produced_recycled
        FROM quality_yield_records r
        {$where}
        AND r.extrusion_machine IS NOT NULL AND r.extrusion_machine != ''
        GROUP BY r.extrusion_machine
        ORDER BY total_defect DESC
    ";
    $resStacked = $conn->query($sqlStacked);
    $stackedCategories = [];
    $stackedA1 = [];
    $stackedA2 = [];
    $stackedA3 = [];
    $stackedA4 = [];
    $stackedA5 = [];
    $stackedVirginDefects = [];
    $stackedRecycledDefects = [];
    $extData = [];

    if ($resStacked) {
        while ($st = $resStacked->fetch_assoc()) {
            $mac = $st['extrusion_machine'];
            $stackedCategories[] = $mac;
            $stackedA1[] = intval($st['sum_a1']);
            $stackedA2[] = intval($st['sum_a2']);
            $stackedA3[] = intval($st['sum_a3']);
            $stackedA4[] = intval($st['sum_a4']);
            $stackedA5[] = intval($st['sum_a5']);
            $stackedVirginDefects[] = intval($st['defect_virgin']);
            $stackedRecycledDefects[] = intval($st['defect_recycled']);

            $p = intval($st['total_produced']);
            $g = intval($st['total_good']);
            $r = ($p > 0) ? round(($g / $p) * 100, 2) : 0;
            $extData[] = [
                'machine' => $mac,
                'yield_rate' => $r,
                'benchmark_rate' => 98.00,
                'produced' => $p,
                'good' => $g,
                'defect' => intval($st['total_defect'])
            ];
        }
    }

    $stackedDefectData = [
        'categories' => $stackedCategories,
        'series_a1'  => $stackedA1,
        'series_a2'  => $stackedA2,
        'series_a3'  => $stackedA3,
        'series_a4'  => $stackedA4,
        'series_a5'  => $stackedA5,
        'series_virgin'   => $stackedVirginDefects,
        'series_recycled' => $stackedRecycledDefects
    ];

    // 5. Hiển thị Chi Tiết Theo Từng Line Máy Đùn (Multi-Line Chart View)
    $sqlLinesDaily = "
        SELECT 
            r.extrusion_machine,
            {$dateCol} as report_date,
            SUM(r.total_qty) as produced,
            SUM(r.good_qty) as good,
            ROUND((SUM(r.good_qty) / NULLIF(SUM(r.total_qty), 0)) * 100, 1) as yield_rate
        FROM quality_yield_records r
        {$where}
        AND r.extrusion_machine != '' AND {$dateCol} IS NOT NULL
        GROUP BY r.extrusion_machine, {$dateCol}
        ORDER BY r.extrusion_machine ASC, {$dateCol} ASC
    ";
    $resLd = $conn->query($sqlLinesDaily);
    $linesDaily = [];
    if ($resLd) {
        while ($ld = $resLd->fetch_assoc()) {
            $linesDaily[$ld['extrusion_machine']][] = [
                'date' => $ld['report_date'],
                'rate' => floatval($ld['yield_rate'] ?? 0),
                'produced' => intval($ld['produced'])
            ];
        }
    }

    $sqlLinesMeta = "
        SELECT 
            r.extrusion_machine,
            COALESCE(SUM(r.total_qty), 0) as total_produced,
            COALESCE(SUM(r.good_qty), 0) as total_good,
            COALESCE(SUM(r.defect_qty), 0) as total_defect,
            ROUND((SUM(r.good_qty) / NULLIF(SUM(r.total_qty), 0)) * 100, 2) as yield_rate,
            COALESCE(AVG(r.benchmark_rate), 98.00) as benchmark_rate,
            COALESCE(SUM(r.defect_a1), 0) as sum_a1,
            COALESCE(SUM(r.defect_a2), 0) as sum_a2,
            COALESCE(SUM(r.defect_a3), 0) as sum_a3,
            COALESCE(SUM(r.defect_a4), 0) as sum_a4,
            COALESCE(SUM(r.defect_a5), 0) as sum_a5,
            COALESCE(SUM(CASE WHEN r.material_group = 'virgin' THEN r.total_qty ELSE 0 END), 0) as virgin_qty,
            COALESCE(SUM(CASE WHEN r.material_group = 'recycled' THEN r.total_qty ELSE 0 END), 0) as recycled_qty
        FROM quality_yield_records r
        {$where}
        AND r.extrusion_machine != ''
        GROUP BY r.extrusion_machine
        ORDER BY r.extrusion_machine ASC
    ";
    $resLm = $conn->query($sqlLinesMeta);
    $linesData = [];
    if ($resLm) {
        while ($lm = $resLm->fetch_assoc()) {
            $mac = $lm['extrusion_machine'];
            $yRate = floatval($lm['yield_rate'] ?? 0);
            $bmRate = round(floatval($lm['benchmark_rate'] ?? 98.00), 2);
            $totProd = intval($lm['total_produced']);
            $vQty = intval($lm['virgin_qty']);
            $rQty = intval($lm['recycled_qty']);
            $vPct = ($totProd > 0) ? round(($vQty / $totProd) * 100, 1) : 0;
            $rPct = ($totProd > 0) ? round(($rQty / $totProd) * 100, 1) : 0;

            $status = ($yRate >= $bmRate) ? 'pass' : (($yRate >= 95.0) ? 'warning' : 'danger');

            $linesData[] = [
                'machine'        => $mac,
                'total_produced' => $totProd,
                'total_good'     => intval($lm['total_good']),
                'total_defect'   => intval($lm['total_defect']),
                'yield_rate'     => $yRate,
                'benchmark_rate' => $bmRate,
                'status'         => $status,
                'virgin_qty'     => $vQty,
                'recycled_qty'   => $rQty,
                'virgin_pct'     => $vPct,
                'recycled_pct'   => $rPct,
                'defects'        => [
                    'a1' => intval($lm['sum_a1']),
                    'a2' => intval($lm['sum_a2']),
                    'a3' => intval($lm['sum_a3']),
                    'a4' => intval($lm['sum_a4']),
                    'a5' => intval($lm['sum_a5'])
                ],
                'daily_trend'    => $linesDaily[$mac] ?? []
            ];
        }
    }

    // 6. So sánh theo Kích cỡ ống (Size Comparison)
    $sqlSize = "
        SELECT 
            r.size,
            SUM(r.total_qty) as produced,
            SUM(r.good_qty) as good,
            ROUND((SUM(r.good_qty) / NULLIF(SUM(r.total_qty), 0)) * 100, 2) as yield_rate,
            ROUND(AVG(r.benchmark_rate), 2) as benchmark_rate
        FROM quality_yield_records r
        {$where}
        AND r.size IS NOT NULL AND r.size != ''
        GROUP BY r.size
        ORDER BY produced DESC
        LIMIT 10
    ";
    $resSize = $conn->query($sqlSize);
    $sizeData = [];
    if ($resSize) {
        while ($s = $resSize->fetch_assoc()) {
            $sizeData[] = [
                'size' => $s['size'],
                'yield_rate' => floatval($s['yield_rate'] ?? 0),
                'benchmark_rate' => floatval($s['benchmark_rate'] ?? 98.00),
                'produced' => intval($s['produced']),
                'good' => intval($s['good'])
            ];
        }
    }

    // 7. Phân tích nguyên nhân lỗi (Pareto Defect Breakdown)
    $sumDefects = intval($kpi['sum_a1']) + intval($kpi['sum_a2']) + intval($kpi['sum_a3']) + intval($kpi['sum_a4']) + intval($kpi['sum_a5']);
    if ($sumDefects <= 0) $sumDefects = max(1, $totalDefect);

    $defectList = [
        ['code' => 'A1', 'name' => 'Ngoại quan A1 (Trầy, gel, vón...)', 'count' => intval($kpi['sum_a1'])],
        ['code' => 'A2', 'name' => 'Giới hạn trên A2 (Vượt dung sai)', 'count' => intval($kpi['sum_a2'])],
        ['code' => 'A3', 'name' => 'Giới hạn dưới A3 (Hụt dung sai)', 'count' => intval($kpi['sum_a3'])],
        ['code' => 'A4', 'name' => 'Độ dẹt A4 (Móp / Dẹt)', 'count' => intval($kpi['sum_a4'])],
        ['code' => 'A5', 'name' => 'Bất thường khác A5', 'count' => intval($kpi['sum_a5'])],
    ];

    usort($defectList, function($a, $b) {
        return $b['count'] - $a['count'];
    });

    $cumPercent = 0.0;
    foreach ($defectList as &$d) {
        $pct = round(($d['count'] / $sumDefects) * 100, 1);
        $cumPercent += $pct;
        $d['percent'] = $pct;
        $d['cum_percent'] = min(100.0, round($cumPercent, 1));
    }
    unset($d);

    // 8. Ma trận tỉ lệ thành phẩm theo Máy và Ngày trong tháng (Mô phỏng Sheet 良品率)
    $matrixYear = intval($filters['year'] ?? date('Y'));
    $matrixMonth = intval($filters['month'] ?? date('m'));
    if ($matrixMonth <= 0) $matrixMonth = intval(date('m'));

    $matrixData = getYieldMatrixByMonth($conn, $matrixYear, $matrixMonth, $filters['size'] ?? '');

    return [
        'kpi' => [
            'total_lots' => intval($kpi['total_lots'] ?? 0),
            'total_produced' => $totalProduced,
            'total_good' => $totalGood,
            'total_defect' => $totalDefect,
            'yield_rate' => $realYieldRate,
            'defect_rate' => $realDefectRate,
            'benchmark_rate' => round(floatval($kpi['avg_benchmark'] ?? 98.00), 2),
            'count_pass' => intval($kpi['count_pass'] ?? 0),
            'count_warning' => intval($kpi['count_warning'] ?? 0),
            'count_danger' => intval($kpi['count_danger'] ?? 0),
            'pending_investigations' => $pendingInvCount
        ],
        'material_summary'     => $matSummary,
        'stacked_defect_data'  => $stackedDefectData,
        'lines_data'           => $linesData,
        'trend'                => $trendData,
        'extrusion_comparison' => $extData,
        'size_comparison'      => $sizeData,
        'pareto_defects'       => $defectList,
        'matrix'               => $matrixData
    ];
}

/**
 * Xây dựng bảng Ma trận Tỉ lệ thành phẩm (良品率) theo Máy đùn & Ngày trong tháng
 */
function getYieldMatrixByMonth($conn, $year, $month, $sizeFilter = '') {
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $where = "WHERE YEAR(komaki_date) = {$year} AND MONTH(komaki_date) = {$month} AND extrusion_machine != ''";
    if (!empty($sizeFilter)) {
        $s = $conn->real_escape_string($sizeFilter);
        $where .= " AND size = '{$s}'";
    }

    // 1. Lấy danh sách máy đùn có sản xuất trong tháng
    $sqlMachines = "SELECT DISTINCT extrusion_machine FROM quality_yield_records {$where} ORDER BY extrusion_machine ASC";
    $resMac = $conn->query($sqlMachines);
    $machines = [];
    if ($resMac) {
        while ($r = $resMac->fetch_assoc()) {
            $machines[] = $r['extrusion_machine'];
        }
    }
    if (empty($machines)) {
        $machines = ['PL02', 'PL03', 'PL07', 'PL08', 'PL14', 'PL16', 'PL17', 'PL18'];
    }

    // 2. Lấy dữ liệu tỉ lệ thành phẩm theo máy và ngày
    $sqlDaily = "
        SELECT 
            extrusion_machine,
            DAY(komaki_date) as d,
            SUM(good_qty) as good,
            SUM(total_qty) as total,
            ROUND((SUM(good_qty) / NULLIF(SUM(total_qty), 0)) * 100, 1) as rate
        FROM quality_yield_records
        {$where}
        GROUP BY extrusion_machine, DAY(komaki_date)
    ";
    $resDaily = $conn->query($sqlDaily);
    $grid = [];
    foreach ($machines as $m) {
        $grid[$m] = array_fill(1, $daysInMonth, null);
    }

    if ($resDaily) {
        while ($r = $resDaily->fetch_assoc()) {
            $mac = $r['extrusion_machine'];
            $d = intval($r['d']);
            if (isset($grid[$mac])) {
                $grid[$mac][$d] = floatval($r['rate']);
            }
        }
    }

    // Tính tổng từng ngày toàn xưởng
    $dayTotals = array_fill(1, $daysInMonth, null);
    $sqlDayTotal = "
        SELECT 
            DAY(komaki_date) as d,
            ROUND((SUM(good_qty) / NULLIF(SUM(total_qty), 0)) * 100, 1) as rate
        FROM quality_yield_records
        {$where}
        GROUP BY DAY(komaki_date)
    ";
    $resDt = $conn->query($sqlDayTotal);
    if ($resDt) {
        while ($r = $resDt->fetch_assoc()) {
            $d = intval($r['d']);
            $dayTotals[$d] = floatval($r['rate']);
        }
    }

    // Tính trung bình cả tháng của từng máy
    $machineAverages = [];
    foreach ($grid as $mac => $dayVals) {
        $nonNull = array_filter($dayVals, function($v) { return $v !== null; });
        $machineAverages[$mac] = count($nonNull) > 0 ? round(array_sum($nonNull) / count($nonNull), 1) : null;
    }

    return [
        'year' => $year,
        'month' => $month,
        'days_in_month' => $daysInMonth,
        'machines' => $machines,
        'grid' => $grid,
        'machine_averages' => $machineAverages,
        'day_totals' => $dayTotals
    ];
}

/**
 * Lấy danh sách phân trang bản ghi tỉ lệ thành phẩm
 */
function getYieldRecordsList($conn, $filters, $page = 1, $limit = 25) {
    list($where, $dateCol) = buildQualityWhereClause($conn, $filters);
    $page = max(1, intval($page));
    $limit = max(10, min(200, intval($limit)));
    $offset = ($page - 1) * $limit;

    // Đếm tổng số dòng
    $sqlCount = "SELECT COUNT(*) as total FROM quality_yield_records r {$where}";
    $resCount = $conn->query($sqlCount);
    $total = $resCount ? intval($resCount->fetch_assoc()['total']) : 0;
    $totalPages = ceil($total / $limit);

    // Lấy dữ liệu phân trang
    $sql = "
        SELECT 
            r.*,
            (SELECT COUNT(*) FROM quality_investigations i WHERE i.lot_no = r.lot_no) as inv_count
        FROM quality_yield_records r
        {$where}
        ORDER BY {$dateCol} DESC, r.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    $res = $conn->query($sql);
    $list = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $r['yield_rate'] = floatval($r['yield_rate']);
            $r['benchmark_rate'] = floatval($r['benchmark_rate']);
            $r['is_pass'] = ($r['yield_rate'] >= $r['benchmark_rate']);
            $r['is_danger'] = ($r['yield_rate'] < 95.00);
            $list[] = $r;
        }
    }

    return [
        'data' => $list,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $total,
            'limit' => $limit
        ]
    ];
}

/**
 * Lưu hoặc cập nhật bản ghi thành phẩm
 */
function saveYieldRecord($conn, $data, $currentUser = 'ADMIN') {
    $id = !empty($data['id']) ? intval($data['id']) : 0;
    $komakiDate = parseQualityDate($data['komaki_date'] ?? date('Y-m-d'));
    $komakiMachine = trim($data['komaki_machine'] ?? 'ST01');
    $size = trim($data['size'] ?? '');
    $lotNo = trim($data['lot_no'] ?? '');
    $productCode = trim($data['product_code'] ?? '');
    $extrusionDate = parseQualityDate($data['extrusion_date'] ?? null);
    $extrusionMachine = trim($data['extrusion_machine'] ?? 'PL08');
    $shift = trim($data['shift'] ?? 'Ca 1');
    $materialType = trim($data['material_type'] ?? 'Zin');

    $goodQty = intval($data['good_qty'] ?? 0);
    $totalQty = intval($data['total_qty'] ?? 0);
    if ($totalQty <= 0 && $goodQty > 0) $totalQty = $goodQty;

    // Tự động tính tỉ lệ thành phẩm
    if (isset($data['yield_rate']) && is_numeric($data['yield_rate']) && floatval($data['yield_rate']) > 0) {
        $yieldRate = round(floatval($data['yield_rate']), 2);
    } elseif ($totalQty > 0) {
        $yieldRate = round(($goodQty / $totalQty) * 100, 2);
    } else {
        $yieldRate = 100.00;
    }

    $bobbinTime = formatQualityBobbinTime($data['bobbin_time'] ?? '');
    $matInfo = resolveMaterialByLot($lotNo, null, $conn);
    $materialGroup = $matInfo['material_group'];
    if (empty($materialType) || $materialType === 'Zin') {
        $materialType = $matInfo['material_name'];
    }
    $upsertKey = generateYieldUpsertKey($bobbinTime, $extrusionDate, $komakiMachine, $komakiDate);

    $defectQty = intval($data['defect_qty'] ?? ($totalQty - $goodQty));
    if ($defectQty < 0) $defectQty = 0;

    $benchmark = !empty($data['benchmark_rate']) ? floatval($data['benchmark_rate']) : 98.00;

    $defA1 = intval($data['defect_a1'] ?? 0);
    $defA2 = intval($data['defect_a2'] ?? 0);
    $defA3 = intval($data['defect_a3'] ?? 0);
    $defA4 = intval($data['defect_a4'] ?? 0);
    $defA5 = intval($data['defect_a5'] ?? 0);
    $bobbinLen = intval($data['bobbin_length'] ?? 0);
    $note = trim($data['note'] ?? '');

    if (empty($size) || empty($lotNo)) {
        return ['success' => false, 'message' => 'Vui lòng nhập Size và Số LOT sản phẩm!'];
    }

    if ($id > 0) {
        // CẬP NHẬT
        $sql = "UPDATE quality_yield_records SET 
                    komaki_date = ?, komaki_machine = ?, size = ?, lot_no = ?, product_code = ?,
                    extrusion_date = ?, bobbin_time = ?, extrusion_machine = ?, shift = ?, material_type = ?, material_group = ?,
                    good_qty = ?, total_qty = ?, yield_rate = ?, defect_qty = ?, benchmark_rate = ?,
                    defect_a1 = ?, defect_a2 = ?, defect_a3 = ?, defect_a4 = ?, defect_a5 = ?,
                    bobbin_length = ?, note = ?, upsert_key = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => $conn->error];
        $stmt->bind_param("sssssssssssiididiiiiiissi",
            $komakiDate, $komakiMachine, $size, $lotNo, $productCode,
            $extrusionDate, $bobbinTime, $extrusionMachine, $shift, $materialType, $materialGroup,
            $goodQty, $totalQty, $yieldRate, $defectQty, $benchmark,
            $defA1, $defA2, $defA3, $defA4, $defA5, $bobbinLen, $note, $upsertKey, $id
        );
        $ok = $stmt->execute();
        $err = $stmt->error ?: $conn->error;
        $stmt->close();
        return ['success' => $ok, 'message' => $ok ? 'Cập nhật bản ghi thành phẩm thành công!' : $err, 'id' => $id];
    } else {
        // THÊM MỚI
        $sql = "INSERT INTO quality_yield_records (
                    upsert_key, komaki_date, komaki_machine, size, lot_no, product_code,
                    extrusion_date, bobbin_time, extrusion_machine, shift, material_type, material_group,
                    good_qty, total_qty, yield_rate, defect_qty, benchmark_rate,
                    defect_a1, defect_a2, defect_a3, defect_a4, defect_a5,
                    bobbin_length, note, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => $conn->error];
        $stmt->bind_param("ssssssssssssiididiiiiiiss",
            $upsertKey, $komakiDate, $komakiMachine, $size, $lotNo, $productCode,
            $extrusionDate, $bobbinTime, $extrusionMachine, $shift, $materialType, $materialGroup,
            $goodQty, $totalQty, $yieldRate, $defectQty, $benchmark,
            $defA1, $defA2, $defA3, $defA4, $defA5, $bobbinLen, $note, $currentUser
        );
        $ok = $stmt->execute();
        $err = $stmt->error ?: $conn->error;
        $newId = $stmt->insert_id;
        $stmt->close();
        return ['success' => $ok, 'message' => $ok ? 'Thêm mới bản ghi thành phẩm thành công!' : $err, 'id' => $newId];
    }
}

/**
 * Xóa bản ghi thành phẩm
 */
function deleteYieldRecord($conn, $id) {
    $id = intval($id);
    $stmt = $conn->prepare("DELETE FROM quality_yield_records WHERE id = ?");
    if (!$stmt) return ['success' => false, 'message' => $conn->error];
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return ['success' => $ok, 'message' => $ok ? 'Đã xóa bản ghi thành công.' : $conn->error];
}

/**
 * Lấy danh sách mốc tiêu chuẩn (Benchmark)
 */
function getQualityBenchmarksList($conn) {
    $res = $conn->query("SELECT * FROM quality_benchmarks ORDER BY (size = 'ALL') DESC, size ASC, extrusion_machine ASC");
    $list = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $r['benchmark_rate'] = floatval($r['benchmark_rate']);
            $r['min_acceptable_rate'] = floatval($r['min_acceptable_rate']);
            $list[] = $r;
        }
    }
    return $list;
}

/**
 * Lưu hoặc cập nhật mốc tiêu chuẩn Benchmark
 */
function saveQualityBenchmark($conn, $data, $currentUser = 'ADMIN') {
    $size = trim($data['size'] ?? 'ALL');
    $machine = trim($data['extrusion_machine'] ?? 'ALL');
    $benchmark = floatval($data['benchmark_rate'] ?? 98.00);
    $minAccept = floatval($data['min_acceptable_rate'] ?? 95.00);
    $desc = trim($data['description'] ?? '');

    $sql = "INSERT INTO quality_benchmarks (size, extrusion_machine, benchmark_rate, min_acceptable_rate, description, updated_by)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                benchmark_rate = VALUES(benchmark_rate),
                min_acceptable_rate = VALUES(min_acceptable_rate),
                description = VALUES(description),
                updated_by = VALUES(updated_by)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return ['success' => false, 'message' => $conn->error];
    $stmt->bind_param("ssddss", $size, $machine, $benchmark, $minAccept, $desc, $currentUser);
    $ok = $stmt->execute();
    $stmt->close();
    return ['success' => $ok, 'message' => $ok ? 'Đã lưu cấu hình mốc tiêu chuẩn Benchmark!' : $conn->error];
}

/**
 * Lấy danh sách Phiếu Yêu cầu Điều tra & Đối ứng Bất thường
 */
function getQualityInvestigationsList($conn, $filters = []) {
    $where = "WHERE 1=1";
    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $st = $conn->real_escape_string($filters['status']);
        $where .= " AND i.result_status = '{$st}'";
    }
    if (!empty($filters['search'])) {
        $s = $conn->real_escape_string($filters['search']);
        $where .= " AND (i.investigation_code LIKE '%{$s}%' OR i.lot_no LIKE '%{$s}%' OR i.product_code LIKE '%{$s}%' OR i.assigned_to LIKE '%{$s}%')";
    }
    if (!empty($filters['size'])) {
        $sz = $conn->real_escape_string($filters['size']);
        $where .= " AND i.size = '{$sz}'";
    }

    $sql = "SELECT i.* FROM quality_investigations i {$where} ORDER BY i.investigation_date DESC, i.id DESC";
    $res = $conn->query($sql);
    $list = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $r['yield_rate'] = floatval($r['yield_rate']);
            $r['rate_a1'] = floatval($r['rate_a1']);
            $r['rate_a2'] = floatval($r['rate_a2']);
            $r['rate_a3'] = floatval($r['rate_a3']);
            $r['rate_a4'] = floatval($r['rate_a4']);
            $r['rate_a5'] = floatval($r['rate_a5']);
            $list[] = $r;
        }
    }
    return $list;
}

/**
 * Tạo hoặc cập nhật Phiếu Yêu cầu Điều tra
 */
function saveQualityInvestigation($conn, $data, $currentUser = 'ADMIN') {
    $id = !empty($data['id']) ? intval($data['id']) : 0;
    $yieldRecordId = !empty($data['yield_record_id']) ? intval($data['yield_record_id']) : NULL;
    $invDate = parseQualityDate($data['investigation_date'] ?? date('Y-m-d'));
    $size = trim($data['size'] ?? '');
    $prodCode = trim($data['product_code'] ?? '');
    $extMachine = trim($data['extrusion_machine'] ?? '');
    $extDate = parseQualityDate($data['extrusion_date'] ?? null);
    $komDate = parseQualityDate($data['komaki_date'] ?? null);
    $lotNo = trim($data['lot_no'] ?? '');

    $yieldRate = floatval($data['yield_rate'] ?? 0);
    $rA1 = floatval($data['rate_a1'] ?? 0);
    $rA2 = floatval($data['rate_a2'] ?? 0);
    $rA3 = floatval($data['rate_a3'] ?? 0);
    $rA4 = floatval($data['rate_a4'] ?? 0);
    $rA5 = floatval($data['rate_a5'] ?? 0);

    $statusDesc = trim($data['status_description'] ?? 'Phát sinh bất thường tỉ lệ thành phẩm');
    $rootCause = trim($data['root_cause'] ?? '');
    $countermeasure = trim($data['countermeasure'] ?? '');
    $assignedTo = trim($data['assigned_to'] ?? '');
    $resultStatus = trim($data['result_status'] ?? 'Chờ điều tra');

    if (empty($size) || empty($prodCode)) {
        return ['success' => false, 'message' => 'Vui lòng cung cấp Size và Mã sản phẩm!'];
    }

    if ($id > 0) {
        $sql = "UPDATE quality_investigations SET 
                    investigation_date = ?, size = ?, product_code = ?, extrusion_machine = ?,
                    extrusion_date = ?, komaki_date = ?, lot_no = ?, yield_rate = ?,
                    rate_a1 = ?, rate_a2 = ?, rate_a3 = ?, rate_a4 = ?, rate_a5 = ?,
                    status_description = ?, root_cause = ?, countermeasure = ?, assigned_to = ?, result_status = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => $conn->error];
        $stmt->bind_param("sssssssddddddsssssi",
            $invDate, $size, $prodCode, $extMachine,
            $extDate, $komDate, $lotNo, $yieldRate,
            $rA1, $rA2, $rA3, $rA4, $rA5,
            $statusDesc, $rootCause, $countermeasure, $assignedTo, $resultStatus, $id
        );
        $ok = $stmt->execute();
        $err = $stmt->error ?: $conn->error;
        $stmt->close();
        return ['success' => $ok, 'message' => $ok ? 'Cập nhật phiếu điều tra thành công!' : $err, 'id' => $id];
    } else {
        // Sinh mã phiếu tự động đảm bảo duy nhất
        $prefix = 'INV-' . date('Ym') . '-';
        $seq = 1;
        $resCode = $conn->query("SELECT COUNT(*) as c FROM quality_investigations WHERE investigation_code LIKE '{$prefix}%'");
        if ($resCode) $seq = intval($resCode->fetch_assoc()['c']) + 1;
        do {
            $invCode = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
            $check = $conn->query("SELECT id FROM quality_investigations WHERE investigation_code = '{$invCode}'");
            if ($check && $check->num_rows > 0) {
                $seq++;
            } else {
                break;
            }
        } while ($seq < 9999);

        $sql = "INSERT INTO quality_investigations (
                    investigation_code, yield_record_id, investigation_date, size, product_code,
                    extrusion_machine, extrusion_date, komaki_date, lot_no, yield_rate,
                    rate_a1, rate_a2, rate_a3, rate_a4, rate_a5,
                    status_description, root_cause, countermeasure, assigned_to, result_status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => $conn->error];
        $stmt->bind_param("sisssssssddddddssssss",
            $invCode, $yieldRecordId, $invDate, $size, $prodCode,
            $extMachine, $extDate, $komDate, $lotNo, $yieldRate,
            $rA1, $rA2, $rA3, $rA4, $rA5,
            $statusDesc, $rootCause, $countermeasure, $assignedTo, $resultStatus, $currentUser
        );
        $ok = $stmt->execute();
        $err = $stmt->error ?: $conn->error;
        $newId = $stmt->insert_id;
        $stmt->close();
        return ['success' => $ok, 'message' => $ok ? "Đã tạo phiếu điều tra [{$invCode}] thành công!" : $err, 'id' => $newId, 'code' => $invCode];
    }
}

/**
 * Xóa Phiếu điều tra
 */
function deleteQualityInvestigation($conn, $id) {
    $id = intval($id);
    $stmt = $conn->prepare("DELETE FROM quality_investigations WHERE id = ?");
    if (!$stmt) return ['success' => false, 'message' => $conn->error];
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return ['success' => $ok, 'message' => $ok ? 'Đã xóa phiếu điều tra thành công.' : $conn->error];
}

/**
 * Xử lý Import file Excel & CSV (Hỗ trợ Upsert thông minh theo khóa 4 trường: Giờ bobin, Ngày đùn, Máy cuộn, Ngày cuộn)
 * Cấu trúc các trường khớp chính xác sheet データ trong 26年09月生産進捗(TU).xlsm
 */
function processYieldExcelImport($conn, $filePath, $currentUser = 'IMPORT_USER') {
    if (!file_exists($filePath)) {
        return ['success' => false, 'message' => 'Không tìm thấy file tải lên: ' . htmlspecialchars($filePath)];
    }

    $materialRules = getQualityMaterialRules($conn);
    $inserted = 0;
    $updated  = 0;
    $skipped  = 0;

    // Prepared statements cho check, update và insert
    $stmtCheck = $conn->prepare("SELECT id FROM quality_yield_records WHERE upsert_key = ? LIMIT 1");
    
    $stmtUpdate = $conn->prepare("UPDATE quality_yield_records SET 
        komaki_date = ?, komaki_machine = ?, size = ?, lot_no = ?, product_code = ?,
        extrusion_date = ?, bobbin_time = ?, extrusion_machine = ?, shift = ?, material_type = ?, material_group = ?,
        good_qty = ?, total_qty = ?, yield_rate = ?, defect_qty = ?, benchmark_rate = ?,
        defect_a1 = ?, defect_a2 = ?, defect_a3 = ?, defect_a4 = ?, defect_a5 = ?,
        defect_details = ?, bobbin_length = ?
        WHERE id = ?");

    $stmtInsert = $conn->prepare("INSERT INTO quality_yield_records (
        upsert_key, komaki_date, komaki_machine, size, lot_no, product_code,
        extrusion_date, bobbin_time, extrusion_machine, shift, material_type, material_group,
        good_qty, total_qty, yield_rate, defect_qty, benchmark_rate,
        defect_a1, defect_a2, defect_a3, defect_a4, defect_a5,
        defect_details, bobbin_length, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // =========================================================================
    // XỬ LÝ CHO FILE CSV
    // =========================================================================
    if ($ext === 'csv') {
        $handle = fopen($filePath, 'r');
        if (!$handle) return ['success' => false, 'message' => 'Không thể mở file CSV.'];

        // Kiểm tra UTF-8 BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rowIdx = 0;
        while (($row = fgetcsv($handle, 4096, ',')) !== false) {
            $rowIdx++;
            if ($rowIdx <= 3) continue; // Bỏ qua 3 dòng tiêu đề

            $kDate = parseQualityDate($row[0] ?? '');
            $size  = trim($row[2] ?? '');
            $lot   = trim($row[3] ?? '');
            if (empty($kDate) || empty($size) || empty($lot)) {
                $skipped++;
                continue;
            }

            $kMach   = !empty($row[1]) ? trim($row[1]) : 'ST01';
            $pCode   = trim($row[4] ?? '');
            $extDate = parseQualityDate($row[5] ?? null);
            $good    = intval($row[6] ?? 0);
            $total   = intval($row[7] ?? 0);
            if ($total <= 0 && $good > 0) $total = $good;

            $rawYield = floatval($row[8] ?? 0);
            if ($rawYield > 0 && $rawYield <= 1.0) {
                $rate = round($rawYield * 100, 2);
            } elseif ($total > 0) {
                $rate = round(($good / $total) * 100, 2);
            } else {
                $rate = 100.0;
            }

            $def     = intval($row[9] ?? ($total - $good));
            $dA1     = intval($row[10] ?? 0);
            $dA2     = intval($row[12] ?? 0);
            $dA3     = intval($row[13] ?? 0);
            $dA4     = intval($row[14] ?? 0);
            $dA5     = intval($row[15] ?? 0);

            // Sub-defects Q -> AG (index 16 -> 32)
            $subDefects = [
                'tray'        => intval($row[16] ?? 0),
                'von_cuc'     => intval($row[17] ?? 0),
                'di_vat'      => intval($row[18] ?? 0),
                'dinh_gen'    => intval($row[19] ?? 0),
                'xuoc'        => intval($row[20] ?? 0),
                'gel'         => intval($row[21] ?? 0),
                'cham_trang'  => intval($row[22] ?? 0),
                'bien_dang'   => intval($row[23] ?? 0),
                'vet_lom'     => intval($row[24] ?? 0),
                'bot_khi'     => intval($row[25] ?? 0),
                'vo'          => intval($row[26] ?? 0),
                'in_han'      => intval($row[27] ?? 0),
                'cao_chu'     => intval($row[28] ?? 0),
                'rong_chu'    => intval($row[29] ?? 0),
                'nd_in'       => intval($row[30] ?? 0),
                'thieu_net'   => intval($row[31] ?? 0),
                'mau_sac'     => intval($row[32] ?? 0)
            ];
            $defectJson = json_encode($subDefects, JSON_UNESCAPED_UNICODE);

            $bTime   = formatQualityBobbinTime($row[33] ?? '');
            $bLen    = intval($row[34] ?? 0);
            $shift   = !empty($row[36]) ? 'Ca ' . $row[36] : 'Ca 1';
            $bench   = !empty($row[40]) ? floatval($row[40]) : 98.00;
            $extMach = !empty($row[41]) ? trim($row[41]) : 'PL08';

            // Nhận diện nhóm nguyên vật liệu theo ký tự thứ 2 của LOT
            $matInfo = resolveMaterialByLot($lot, $materialRules, $conn);
            $matGroup = $matInfo['material_group'];
            $matType  = !empty($row[39]) ? trim($row[39]) : $matInfo['material_name'];

            // Khóa định danh 4 trường: Giờ bobin, Ngày đùn, Máy cuộn, Ngày cuộn
            $upsertKey = generateYieldUpsertKey($bTime, $extDate, $kMach, $kDate);

            // Kiểm tra trùng lặp khóa để Upsert
            $stmtCheck->bind_param("s", $upsertKey);
            $stmtCheck->execute();
            $chkRes = $stmtCheck->get_result();
            $existing = $chkRes ? $chkRes->fetch_assoc() : null;

            if ($existing) {
                // CẬP NHẬT (UPDATE)
                $existId = intval($existing['id']);
                $stmtUpdate->bind_param("sssssssssssiididiiiiiisi",
                    $kDate, $kMach, $size, $lot, $pCode,
                    $extDate, $bTime, $extMach, $shift, $matType, $matGroup,
                    $good, $total, $rate, $def, $bench,
                    $dA1, $dA2, $dA3, $dA4, $dA5,
                    $defectJson, $bLen, $existId
                );
                $stmtUpdate->execute();
                $updated++;
            } else {
                // THÊM MỚI (INSERT)
                $stmtInsert->bind_param("ssssssssssssiididiiiiiiss",
                    $upsertKey, $kDate, $kMach, $size, $lot, $pCode,
                    $extDate, $bTime, $extMach, $shift, $matType, $matGroup,
                    $good, $total, $rate, $def, $bench,
                    $dA1, $dA2, $dA3, $dA4, $dA5,
                    $defectJson, $bLen, $currentUser
                );
                $stmtInsert->execute();
                $inserted++;
            }
        }
        fclose($handle);

        $stmtCheck->close();
        $stmtUpdate->close();
        $stmtInsert->close();

        return [
            'success' => true,
            'message' => "Import CSV hoàn tất! Thêm mới: {$inserted} dòng, Cập nhật (Upsert): {$updated} dòng, Bỏ qua: {$skipped} dòng.",
            'inserted' => $inserted,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'total'    => ($inserted + $updated + $skipped)
        ];
    }

    // =========================================================================
    // XỬ LÝ CHO FILE EXCEL (.xlsx / .xlsm)
    // =========================================================================
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        return ['success' => false, 'message' => 'Không thể giải nén cấu trúc file Excel (.xlsx/.xlsm).'];
    }

    // Phân tích sheets và shared strings qua streaming XML
    $wbXml = $zip->getFromName('xl/workbook.xml');
    $wbRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    $rels = [];
    if ($wbRelsXml) {
        $xmlRels = simplexml_load_string($wbRelsXml);
        if ($xmlRels) {
            foreach ($xmlRels->Relationship as $rel) {
                $rels[(string)$rel['Id']] = (string)$rel['Target'];
            }
        }
    }

    $targetFile = null;
    if ($wbXml) {
        $xmlWb = simplexml_load_string($wbXml);
        if ($xmlWb && isset($xmlWb->sheets->sheet)) {
            foreach ($xmlWb->sheets->sheet as $s) {
                $name = (string)$s['name'];
                $rId = (string)$s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                $target = $rels[$rId] ?? '';
                if ($name === 'データ' || $name === 'Data' || $name === 'Dữ liệu') {
                    $targetFile = 'xl/' . ltrim($target, '/');
                    break;
                }
            }
            if (!$targetFile) {
                foreach ($xmlWb->sheets->sheet as $s) {
                    $name = (string)$s['name'];
                    $rId = (string)$s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                    $target = $rels[$rId] ?? '';
                    if (mb_strpos($name, 'データ') !== false || mb_strpos($name, 'Dữ liệu') !== false) {
                        $targetFile = 'xl/' . ltrim($target, '/');
                        break;
                    }
                }
            }
        }
    }

    if (!$targetFile) {
        // Tìm sheet đầu tiên
        $targetFile = 'xl/' . ltrim($rels['rId1'] ?? 'worksheets/sheet1.xml', '/');
    }

    // Đọc Shared Strings
    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $reader = new XMLReader();
        $reader->XML($ssXml);
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'si') {
                $si = simplexml_load_string($reader->readOuterXML());
                $text = '';
                if (isset($si->t)) $text = (string)$si->t;
                elseif (isset($si->r)) {
                    foreach ($si->r as $r) $text .= (string)$r->t;
                }
                $sharedStrings[] = $text;
            }
        }
        $reader->close();
        unset($ssXml);
    }

    // Mở stream sheet dữ liệu
    $reader = new XMLReader();
    if (!$reader->open('zip://' . $zip->filename . '#' . $targetFile)) {
        $zip->close();
        return ['success' => false, 'message' => "Không thể mở sheet dữ liệu `{$targetFile}` trong file Excel."];
    }

    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'row') {
            $rNum = intval($reader->getAttribute('r'));
            if ($rNum <= 3) continue; // Bỏ qua 3 dòng đầu (Tiêu đề sheet データ)

            $rowObj = simplexml_load_string($reader->readOuterXML());
            $cols = [];
            foreach ($rowObj->c as $c) {
                $ref = (string)$c['r'];
                preg_match('/^([A-Z]+)/', $ref, $m);
                $col = $m[1] ?? $ref;
                $type = (string)$c['t'];
                $val = isset($c->v) ? (string)$c->v : (isset($c->is->t) ? (string)$c->is->t : '');
                if ($type === 's' && isset($sharedStrings[intval($val)])) {
                    $val = $sharedStrings[intval($val)];
                }
                $cols[$col] = trim($val);
            }

            $kDate = parseQualityDate($cols['A'] ?? '');
            $size  = trim($cols['C'] ?? '');
            $lot   = trim($cols['D'] ?? '');
            $pCode = trim($cols['E'] ?? '');

            if (empty($kDate) || empty($size) || empty($lot)) {
                $skipped++;
                continue;
            }

            $kMach   = !empty($cols['B']) ? trim($cols['B']) : 'ST01';
            $extDate = parseQualityDate($cols['F'] ?? null);
            $good    = intval($cols['G'] ?? 0);
            $total   = intval($cols['H'] ?? 0);
            if ($total <= 0 && $good > 0) $total = $good;

            $rawYield = floatval($cols['I'] ?? 0);
            if ($rawYield > 0 && $rawYield <= 1.0) {
                $rate = round($rawYield * 100, 2);
            } elseif ($total > 0) {
                $rate = round(($good / $total) * 100, 2);
            } else {
                $rate = 100.0;
            }

            $def     = intval($cols['J'] ?? ($total - $good));
            $extMach = !empty($cols['BA']) ? trim($cols['BA']) : 'PL08';
            $shift   = !empty($cols['AK']) ? 'Ca ' . $cols['AK'] : 'Ca 1';
            $bench   = !empty($cols['AS']) ? floatval($cols['AS']) : 98.00;

            $dA1     = intval($cols['K'] ?? 0);
            $dA2     = intval($cols['M'] ?? 0);
            $dA3     = intval($cols['N'] ?? 0);
            $dA4     = intval($cols['O'] ?? 0);
            $dA5     = intval($cols['P'] ?? 0);

            // Sub-defects Q -> AG
            $subDefects = [
                'tray'        => intval($cols['Q'] ?? 0),
                'von_cuc'     => intval($cols['R'] ?? 0),
                'di_vat'      => intval($cols['S'] ?? 0),
                'dinh_gen'    => intval($cols['T'] ?? 0),
                'xuoc'        => intval($cols['U'] ?? 0),
                'gel'         => intval($cols['V'] ?? 0),
                'cham_trang'  => intval($cols['W'] ?? 0),
                'bien_dang'   => intval($cols['X'] ?? 0),
                'vet_lom'     => intval($cols['Y'] ?? 0),
                'bot_khi'     => intval($cols['Z'] ?? 0),
                'vo'          => intval($cols['AA'] ?? 0),
                'in_han'      => intval($cols['AB'] ?? 0),
                'cao_chu'     => intval($cols['AC'] ?? 0),
                'rong_chu'    => intval($cols['AD'] ?? 0),
                'nd_in'       => intval($cols['AE'] ?? 0),
                'thieu_net'   => intval($cols['AF'] ?? 0),
                'mau_sac'     => intval($cols['AG'] ?? 0)
            ];
            $defectJson = json_encode($subDefects, JSON_UNESCAPED_UNICODE);

            $bTime = formatQualityBobbinTime($cols['AH'] ?? '');
            $bLen  = intval($cols['AI'] ?? 0);

            // Nhận diện nhóm nguyên vật liệu theo ký tự thứ 2 của LOT
            $matInfo = resolveMaterialByLot($lot, $materialRules, $conn);
            $matGroup = $matInfo['material_group'];
            $matType  = !empty($cols['AN']) ? trim($cols['AN']) : $matInfo['material_name'];

            // Khóa định danh 4 trường: Giờ bobin, Ngày đùn, Máy cuộn, Ngày cuộn
            $upsertKey = generateYieldUpsertKey($bTime, $extDate, $kMach, $kDate);

            // Kiểm tra trùng lặp khóa để Upsert
            $stmtCheck->bind_param("s", $upsertKey);
            $stmtCheck->execute();
            $chkRes = $stmtCheck->get_result();
            $existing = $chkRes ? $chkRes->fetch_assoc() : null;

            if ($existing) {
                // CẬP NHẬT (UPDATE)
                $existId = intval($existing['id']);
                $stmtUpdate->bind_param("sssssssssssiididiiiiiisi",
                    $kDate, $kMach, $size, $lot, $pCode,
                    $extDate, $bTime, $extMach, $shift, $matType, $matGroup,
                    $good, $total, $rate, $def, $bench,
                    $dA1, $dA2, $dA3, $dA4, $dA5,
                    $defectJson, $bLen, $existId
                );
                $stmtUpdate->execute();
                $updated++;
            } else {
                // THÊM MỚI (INSERT)
                $stmtInsert->bind_param("ssssssssssssiididiiiiiiss",
                    $upsertKey, $kDate, $kMach, $size, $lot, $pCode,
                    $extDate, $bTime, $extMach, $shift, $matType, $matGroup,
                    $good, $total, $rate, $def, $bench,
                    $dA1, $dA2, $dA3, $dA4, $dA5,
                    $defectJson, $bLen, $currentUser
                );
                $stmtInsert->execute();
                $inserted++;
            }
        }
    }

    $reader->close();
    $stmtCheck->close();
    $stmtUpdate->close();
    $stmtInsert->close();
    $zip->close();

    return [
        'success' => true,
        'message' => "Import Excel hoàn tất! Thêm mới: {$inserted} dòng, Cập nhật (Upsert): {$updated} dòng, Bỏ qua: {$skipped} dòng.",
        'inserted' => $inserted,
        'updated'  => $updated,
        'skipped'  => $skipped,
        'total'    => ($inserted + $updated + $skipped)
    ];
}

/**
 * Xuất file Excel (.xlsx) chuẩn cấu trúc sheet データ trong 26年09月生産進捗(TU).xlsm
 * Cho phép người dùng tải về chỉnh sửa và upload ngược trở lại hệ thống (Upsert)
 */
function createQualityXlsxExport($filename, $records, $sheetName = 'データ') {
    $zip = new ZipArchive();
    if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>');

    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="' . htmlspecialchars($sheetName, ENT_XML1) . '" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

    // Xây dựng danh sách dòng theo chuẩn sheet データ
    $rows = [];
    $rows[] = ['BẢNG THEO DÕI SẢN XUẤT VÀ TỈ LỆ THÀNH PHẨM (26年09月生産進捗 - 良品率)'];
    $rows[] = []; // Row 2 để trống hoặc ghi chú
    
    // Row 3: Các cột chuẩn từ A đến BA
    $rows[] = [
        '小巻日', 'Máy cuộn', 'サイズ', 'LOT', '製品品番', '成形日/Ngay dun',
        '良品数', '設定数', '良品率', '不良数 No.1-5',
        '外観(A1)', 'A2-A5', 'A2 上限異常', 'A3 下限異常', 'A4 偏平異常', 'A5 その他異常',
        'こすれ Trầy', '滞留物 Vón cục', '異物 Dị Vật', 'メヤニ Dính gèn', '傷 Xước', 'ゲル Gel', 'ゆがみ Chấm trắng', '変形 Biến dạng', '縦すじ Vết lõm kéo dài', '気泡 Bọt khí', '割れ Vỡ', '圧痕転写 Vết in hằn', '印字高さ Độ cao chữ', '印字太さ Độ rộng chữ', '印字内容 Nội dung in', '文字かすれ Thiếu nét', 'MAU SAC',
        'GIỜ ĐÙN', 'CHIỀU DÀI BOBIN', 'CHIỀU DÀI CHẠY TAY', 'CA CUỘN', 'Vật liệu', 'Vật liệu lần', 'Column1',
        'Tieu chuan', 'Máy'
    ];

    foreach ($records as $r) {
        $sub = !empty($r['defect_details']) ? json_decode($r['defect_details'], true) : [];
        $sumA2A5 = intval($r['defect_a2'] ?? 0) + intval($r['defect_a3'] ?? 0) + intval($r['defect_a4'] ?? 0) + intval($r['defect_a5'] ?? 0);
        $rows[] = [
            $r['komaki_date'] ?? '',
            $r['komaki_machine'] ?? '',
            $r['size'] ?? '',
            $r['lot_no'] ?? '',
            $r['product_code'] ?? '',
            $r['extrusion_date'] ?? '',
            intval($r['good_qty'] ?? 0),
            intval($r['total_qty'] ?? 0),
            round(floatval($r['yield_rate'] ?? 0) / 100, 4),
            intval($r['defect_qty'] ?? 0),
            intval($r['defect_a1'] ?? 0),
            $sumA2A5,
            intval($r['defect_a2'] ?? 0),
            intval($r['defect_a3'] ?? 0),
            intval($r['defect_a4'] ?? 0),
            intval($r['defect_a5'] ?? 0),
            intval($sub['tray'] ?? 0),
            intval($sub['von_cuc'] ?? 0),
            intval($sub['di_vat'] ?? 0),
            intval($sub['dinh_gen'] ?? 0),
            intval($sub['xuoc'] ?? 0),
            intval($sub['gel'] ?? 0),
            intval($sub['cham_trang'] ?? 0),
            intval($sub['bien_dang'] ?? 0),
            intval($sub['vet_lom'] ?? 0),
            intval($sub['bot_khi'] ?? 0),
            intval($sub['vo'] ?? 0),
            intval($sub['in_han'] ?? 0),
            intval($sub['cao_chu'] ?? 0),
            intval($sub['rong_chu'] ?? 0),
            intval($sub['nd_in'] ?? 0),
            intval($sub['thieu_net'] ?? 0),
            intval($sub['mau_sac'] ?? 0),
            $r['bobbin_time'] ?? '00:00',
            intval($r['bobbin_length'] ?? 0),
            0,
            str_replace('Ca ', '', $r['shift'] ?? '1'),
            0,
            substr($r['lot_no'] ?? '', 1, 1),
            $r['material_type'] ?? 'zin',
            floatval($r['benchmark_rate'] ?? 98.00),
            $r['extrusion_machine'] ?? 'PL08'
        ];
    }

    // Build worksheet XML
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
    $xml .= '<sheetData>' . "\n";

    $rNum = 1;
    foreach ($rows as $row) {
        $xml .= '<row r="' . $rNum . '">' . "\n";
        $cIndex = 1;
        foreach ($row as $colVal) {
            $colLetter = '';
            $temp = $cIndex;
            while ($temp > 0) {
                $rem = ($temp - 1) % 26;
                $colLetter = chr(65 + $rem) . $colLetter;
                $temp = intdiv($temp - 1, 26);
            }
            $cellRef = $colLetter . $rNum;

            if ($colVal === null || $colVal === '') {
                // Ô trống
            } elseif (is_numeric($colVal) && !preg_match('/^0[0-9]+/', (string)$colVal)) {
                $xml .= '<c r="' . $cellRef . '"><v>' . $colVal . '</v></c>';
            } else {
                $xml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$colVal, ENT_XML1) . '</t></is></c>';
            }
            $cIndex++;
        }
        $xml .= '</row>' . "\n";
        $rNum++;
    }

    $xml .= '</sheetData></worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
    $zip->close();
    return true;
}

