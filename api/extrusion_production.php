<?php
/**
 * API Quản Lý & Tổng Hợp Sản Lượng Đùn Ép (Extrusion Production Management API)
 * DX Plastic Group - Production MES System
 * Bảng chuẩn dữ liệu (Single Source of Truth): extrusion_actual_logs
 */
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/extrusion_service.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền người dùng đối với phân hệ sản xuất
$userRole = $_SESSION['user']['role'] ?? 'viewer';
$isCli = (php_sapi_name() === 'cli');

if (!$isCli && !hasPermission(['production.data', 'production.view', 'production.plan', 'api.production.extrusion_get'])) {
    if ($userRole === 'viewer') {
        // Viewer có quyền xem báo cáo
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'code' => 403, 'message' => 'Bạn không có quyền truy cập API Quản lý sản xuất!'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

/**
 * Xử lý ngày tháng Excel sang định dạng chuẩn YYYY-MM-DD
 */
if (!function_exists('parseExtrusionDate')) {
    function parseExtrusionDate($val, $default = null) {
        if (empty($val)) return $default;
        $val = trim((string)$val);
        
        // Dạng YYYY-MM-DD hoặc YYYY-MM-DD HH:mm:ss
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $val, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        
        // Dạng DD/MM/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $val, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        
        // Số Serial Date của Excel (ví dụ: 45538)
        if (is_numeric($val) && floatval($val) > 20000 && floatval($val) < 80000) {
            $days = intval($val);
            $unixTime = ($days - 25569) * 86400;
            return gmdate('Y-m-d', $unixTime);
        }
        
        return $default;
    }
}

/**
 * Hàm xây dựng mệnh đề WHERE lọc dữ liệu trực tiếp trên bảng chuẩn extrusion_actual_logs
 */
if (!function_exists('buildExtrusionWhereClause')) {
function buildExtrusionWhereClause($conn, $params) {
    $where = "WHERE 1=1";
    // Loại trừ các dòng tiêu đề rác và dòng trống
    $where .= " AND p.product_code NOT IN ('Product Code', '品番') AND p.pipe_size != 'OTHER' AND p.pipe_size != ''";
    
    if (!empty($params['date_from'])) {
        $df = $conn->real_escape_string(trim($params['date_from']));
        $where .= " AND p.production_date >= '{$df}'";
    }
    if (!empty($params['date_to'])) {
        $dt = $conn->real_escape_string(trim($params['date_to']));
        $where .= " AND p.production_date <= '{$dt}'";
    }
    if (!empty($params['month'])) {
        $m = $conn->real_escape_string(trim($params['month']));
        $where .= " AND DATE_FORMAT(p.production_date, '%Y-%m') = '{$m}'";
    }
    if (!empty($params['year'])) {
        $y = intval($params['year']);
        $where .= " AND YEAR(p.production_date) = {$y}";
    }
    if (!empty($params['pipe_size']) && $params['pipe_size'] !== 'all') {
        $sz = $conn->real_escape_string(trim($params['pipe_size']));
        $where .= " AND p.pipe_size = '{$sz}'";
    }
    if (!empty($params['product_code'])) {
        $pc = $conn->real_escape_string(trim($params['product_code']));
        $where .= " AND p.product_code LIKE '%{$pc}%'";
    }
    if (!empty($params['machine_code']) && $params['machine_code'] !== 'all') {
        $mc = $conn->real_escape_string(trim($params['machine_code']));
        $where .= " AND p.device_code = '{$mc}'";
    }
    if (!empty($params['workshop']) && $params['workshop'] !== 'all') {
        $ws = $conn->real_escape_string(trim($params['workshop']));
        $where .= " AND (p.cost_center = '{$ws}' OR 'Xưởng Đùn Nhựa V61' = '{$ws}')";
    }
    if (!empty($params['search'])) {
        $s = $conn->real_escape_string(trim($params['search']));
        $where .= " AND (p.production_order_code LIKE '%{$s}%' OR p.mfg_order_code LIKE '%{$s}%' OR p.product_code LIKE '%{$s}%' OR p.employee_name LIKE '%{$s}%' OR p.device_code LIKE '%{$s}%' OR p.pipe_size LIKE '%{$s}%')";
    }
    
    return $where;
}
}

try {
    switch ($action) {
        // =====================================================================
        // 1. TÙY CHỌN BỘ LỌC ĐỘNG (FILTER OPTIONS) - TRUY XUẤT TỪ extrusion_actual_logs
        // =====================================================================
        case 'get_filter_options':
            // Danh sách Size đã chuẩn hóa từ bảng chuẩn extrusion_actual_logs
            $sizesRes = $conn->query("
                SELECT DISTINCT pipe_size 
                FROM extrusion_actual_logs 
                WHERE pipe_size IS NOT NULL AND pipe_size != '' AND pipe_size NOT IN ('OTHER', 'Product Code', '品番') 
                ORDER BY pipe_size ASC
            ");
            $sizes = [];
            while ($r = $sizesRes->fetch_assoc()) {
                $sizes[] = $r['pipe_size'];
            }

            // Danh sách Máy sản xuất từ device_code
            $machinesRes = $conn->query("
                SELECT DISTINCT device_code 
                FROM extrusion_actual_logs 
                WHERE device_code IS NOT NULL AND device_code != '' AND device_code NOT IN ('Machine Code', '管理№') 
                ORDER BY device_code ASC
            ");
            $machines = [];
            while ($r = $machinesRes->fetch_assoc()) {
                $machines[] = $r['device_code'];
            }

            // Danh sách Năm sản xuất
            $yearsRes = $conn->query("
                SELECT DISTINCT YEAR(production_date) AS production_year 
                FROM extrusion_actual_logs 
                WHERE production_date IS NOT NULL AND YEAR(production_date) > 0 
                ORDER BY production_year DESC
            ");
            $years = [];
            while ($r = $yearsRes->fetch_assoc()) {
                $years[] = intval($r['production_year']);
            }
            if (empty($years)) $years[] = intval(date('Y'));

            // Danh sách Xưởng sản xuất
            $workshops = ['Xưởng Đùn Nhựa V61'];

            echo json_encode([
                'success' => true,
                'sizes' => $sizes,
                'machines' => $machines,
                'years' => $years,
                'workshops' => $workshops
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. DASHBOARD TỔNG HỢP (KPI & BIỂU ĐỒ) - TRUY XUẤT TỪ extrusion_actual_logs
        // =====================================================================
        case 'get_dashboard':
            $where = buildExtrusionWhereClause($conn, $_GET);

            // 1. 4 Thẻ KPI chính
            $kpiSql = "
                SELECT 
                    COUNT(*) AS total_records,
                    COALESCE(SUM(p.finished_qty_m), 0) AS total_length_m,
                    COALESCE(SUM(p.finished_qty_kg), 0) AS total_weight_kg,
                    COALESCE(SUM(p.total_weight_kg), 0) AS grand_total_weight_kg,
                    COALESCE(SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count), 0) AS total_coils,
                    COUNT(DISTINCT p.pipe_size) AS count_sizes,
                    COUNT(DISTINCT p.product_code) AS count_products,
                    COUNT(DISTINCT p.device_code) AS count_machines
                FROM extrusion_actual_logs p
                {$where}
            ";
            $kpiRes = $conn->query($kpiSql);
            $kpis = $kpiRes ? $kpiRes->fetch_assoc() : [];

            // 2. Top 10 Size sản xuất nhiều nhất
            $topSizesSql = "
                SELECT 
                    p.pipe_size AS size_calculated,
                    COALESCE(SUM(p.finished_qty_m), 0) AS total_length_m,
                    COALESCE(SUM(p.finished_qty_kg), 0) AS total_weight_kg,
                    COALESCE(SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count), 0) AS total_coils,
                    COUNT(*) AS record_count
                FROM extrusion_actual_logs p
                {$where}
                GROUP BY p.pipe_size
                ORDER BY total_length_m DESC
                LIMIT 10
            ";
            $topSizesRes = $conn->query($topSizesSql);
            $topSizes = [];
            while ($r = $topSizesRes->fetch_assoc()) {
                $topSizes[] = $r;
            }

            // 3. Top 10 Mã sản phẩm sản xuất nhiều nhất
            $topProductsSql = "
                SELECT 
                    p.product_code,
                    p.pipe_size AS size_calculated,
                    COALESCE(SUM(p.finished_qty_m), 0) AS total_length_m,
                    COALESCE(SUM(p.finished_qty_kg), 0) AS total_weight_kg,
                    COALESCE(SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count), 0) AS total_coils
                FROM extrusion_actual_logs p
                {$where}
                GROUP BY p.product_code, p.pipe_size
                ORDER BY total_length_m DESC
                LIMIT 10
            ";
            $topProdRes = $conn->query($topProductsSql);
            $topProducts = [];
            while ($r = $topProdRes->fetch_assoc()) {
                $topProducts[] = $r;
            }

            // 4. Biểu đồ 1: Sản lượng theo tháng (Monthly Trend)
            $monthlySql = "
                SELECT 
                    DATE_FORMAT(p.production_date, '%Y-%m') AS production_month,
                    COALESCE(SUM(p.finished_qty_m), 0) AS total_length_m,
                    COALESCE(SUM(p.finished_qty_kg), 0) AS total_weight_kg,
                    COALESCE(SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count), 0) AS total_coils
                FROM extrusion_actual_logs p
                {$where}
                GROUP BY DATE_FORMAT(p.production_date, '%Y-%m')
                ORDER BY production_month ASC
            ";
            $monthRes = $conn->query($monthlySql);
            $monthlyChart = [
                'labels' => [],
                'length' => [],
                'weight' => [],
                'coils'  => []
            ];
            while ($r = $monthRes->fetch_assoc()) {
                $monthlyChart['labels'][] = $r['production_month'];
                $monthlyChart['length'][] = round(floatval($r['total_length_m']), 2);
                $monthlyChart['weight'][] = round(floatval($r['total_weight_kg']), 2);
                $monthlyChart['coils'][]  = intval($r['total_coils']);
            }

            // 5. Biểu đồ 2: Sản lượng theo máy đùn (Machine Output)
            $machineSql = "
                SELECT 
                    p.device_code AS machine_code,
                    COALESCE(SUM(p.finished_qty_m), 0) AS total_length_m,
                    COALESCE(SUM(p.finished_qty_kg), 0) AS total_weight_kg
                FROM extrusion_actual_logs p
                {$where}
                GROUP BY p.device_code
                ORDER BY total_length_m DESC
            ";
            $mcRes = $conn->query($machineSql);
            $machineChart = [
                'labels' => [],
                'length' => [],
                'weight' => []
            ];
            while ($r = $mcRes->fetch_assoc()) {
                $label = preg_replace('/.*MAYDUN\./', '', $r['machine_code']);
                $machineChart['labels'][] = $label;
                $machineChart['length'][] = round(floatval($r['total_length_m']), 2);
                $machineChart['weight'][] = round(floatval($r['total_weight_kg']), 2);
            }

            // 6. Biểu đồ 3: Tỷ trọng cơ cấu theo Size (Doughnut Share)
            $grandLength = floatval($kpis['total_length_m'] ?? 1);
            if ($grandLength <= 0) $grandLength = 1;

            $sizeShareLabels = [];
            $sizeShareData = [];
            $topLengthSum = 0;

            foreach ($topSizes as $idx => $ts) {
                if ($idx < 6) {
                    $sizeShareLabels[] = $ts['size_calculated'];
                    $sizeShareData[] = round(floatval($ts['total_length_m']), 2);
                    $topLengthSum += floatval($ts['total_length_m']);
                }
            }
            if ($grandLength > $topLengthSum) {
                $sizeShareLabels[] = 'Khác (Các Size còn lại)';
                $sizeShareData[] = round($grandLength - $topLengthSum, 2);
            }

            echo json_encode([
                'success' => true,
                'kpis' => $kpis,
                'top_sizes' => $topSizes,
                'top_products' => $topProducts,
                'charts' => [
                    'monthly' => $monthlyChart,
                    'machines' => $machineChart,
                    'size_share' => [
                        'labels' => $sizeShareLabels,
                        'data' => $sizeShareData
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 3. TỔNG HỢP DỮ LIỆU SẢN LƯỢNG (AGGREGATION ENGINE) - TRUY XUẤT TỪ extrusion_actual_logs
        // =====================================================================
        case 'get_aggregation':
            $type = $_GET['type'] ?? 'size';
            $sortCol = $_GET['sort_col'] ?? 'output';
            $sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');
            if (!in_array($sortOrder, ['ASC', 'DESC'])) $sortOrder = 'DESC';

            $where = buildExtrusionWhereClause($conn, $_GET);

            $orderField = 'total_length_m';
            if ($sortCol === 'weight') $orderField = 'total_weight_kg';
            elseif ($sortCol === 'coils') $orderField = 'total_coils';
            elseif ($sortCol === 'size' || $sortCol === 'name') $orderField = 'group_key';

            $sql = "";
            switch ($type) {
                // A. Tổng hợp theo Size ống
                case 'size':
                case 'range_size':
                    $sql = "
                        SELECT 
                            p.pipe_size AS group_key,
                            p.pipe_size,
                            p.pipe_size AS size_calculated,
                            COUNT(*) AS total_records,
                            COALESCE(SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count), 0) AS total_coils,
                            COALESCE(SUM(p.finished_qty_kg), 0) AS total_weight_kg,
                            COALESCE(SUM(p.finished_qty_m), 0) AS total_length_m,
                            COUNT(DISTINCT p.product_code) AS count_products,
                            COUNT(DISTINCT p.device_code) AS count_machines
                        FROM extrusion_actual_logs p
                        {$where}
                        GROUP BY p.pipe_size
                        ORDER BY {$orderField} {$sortOrder}
                    ";
                    break;

                // B. Tổng hợp theo Tháng
                case 'month':
                    $sql = "
                        SELECT 
                            DATE_FORMAT(p.production_date, '%Y-%m') AS group_key,
                            DATE_FORMAT(p.production_date, '%Y-%m') AS production_month,
                            COUNT(*) AS total_records,
                            COALESCE(SUM(p.finished_qty_m), 0) AS total_length_m,
                            COALESCE(SUM(p.finished_qty_kg), 0) AS total_weight_kg,
                            COALESCE(SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count), 0) AS total_coils,
                            COUNT(DISTINCT p.pipe_size) AS count_sizes,
                            COUNT(DISTINCT p.product_code) AS count_products
                        FROM extrusion_actual_logs p
                        {$where}
                        GROUP BY DATE_FORMAT(p.production_date, '%Y-%m')
                        ORDER BY production_month {$sortOrder}
                    ";
                    break;

                // C. Tổng hợp theo Mã Sản Phẩm
                case 'range_product':
                    $sql = "
                        SELECT 
                            p.product_code AS group_key,
                            p.product_code,
                            p.pipe_size AS size_calculated,
                            COUNT(*) AS total_records,
                            COALESCE(SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count), 0) AS total_coils,
                            COALESCE(SUM(p.finished_qty_kg), 0) AS total_weight_kg,
                            COALESCE(SUM(p.finished_qty_m), 0) AS total_length_m,
                            COUNT(DISTINCT p.device_code) AS count_machines
                        FROM extrusion_actual_logs p
                        {$where}
                        GROUP BY p.product_code, p.pipe_size
                        ORDER BY {$orderField} {$sortOrder}
                    ";
                    break;

                // D. Tổng hợp theo Máy sản xuất
                case 'range_machine':
                    $sql = "
                        SELECT 
                            p.device_code AS group_key,
                            p.device_code AS machine_code,
                            COUNT(*) AS total_records,
                            COALESCE(SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count), 0) AS total_coils,
                            COALESCE(SUM(p.finished_qty_kg), 0) AS total_weight_kg,
                            COALESCE(SUM(p.finished_qty_m), 0) AS total_length_m,
                            COUNT(DISTINCT p.pipe_size) AS count_sizes,
                            COALESCE(SUM(p.total_downtime), 0) AS total_stop_time,
                            COALESCE(SUM(p.total_runtime), 0) AS total_run_time
                        FROM extrusion_actual_logs p
                        {$where}
                        GROUP BY p.device_code
                        ORDER BY {$orderField} {$sortOrder}
                    ";
                    break;

                // E. Tổng hợp theo Xưởng
                case 'range_workshop':
                    $sql = "
                        SELECT 
                            'Xưởng Đùn Nhựa V61' AS group_key,
                            'Xưởng Đùn Nhựa V61' AS workshop,
                            COUNT(*) AS total_records,
                            COALESCE(SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count), 0) AS total_coils,
                            COALESCE(SUM(p.finished_qty_kg), 0) AS total_weight_kg,
                            COALESCE(SUM(p.finished_qty_m), 0) AS total_length_m,
                            COUNT(DISTINCT p.device_code) AS count_machines,
                            COUNT(DISTINCT p.pipe_size) AS count_sizes
                        FROM extrusion_actual_logs p
                        {$where}
                        GROUP BY 'Xưởng Đùn Nhựa V61'
                        ORDER BY {$orderField} {$sortOrder}
                    ";
                    break;
            }

            $res = $conn->query($sql);
            $rows = [];
            $sumLength = 0;
            $sumWeight = 0;
            $sumCoils = 0;
            $sumRecords = 0;

            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $l = floatval($r['total_length_m']);
                    $w = floatval($r['total_weight_kg']);
                    $c = intval($r['total_coils']);
                    $cnt = intval($r['total_records']);

                    $sumLength += $l;
                    $sumWeight += $w;
                    $sumCoils += $c;
                    $sumRecords += $cnt;

                    $rows[] = $r;
                }
            }

            // Tính tỷ trọng % cho từng dòng
            foreach ($rows as &$item) {
                $item['percentage'] = ($sumLength > 0) ? round(floatval($item['total_length_m']) / $sumLength * 100, 2) : 0;
            }
            unset($item);

            echo json_encode([
                'success' => true,
                'type' => $type,
                'sort_col' => $sortCol,
                'sort_order' => $sortOrder,
                'total_rows' => count($rows),
                'summary' => [
                    'total_length_m' => round($sumLength, 2),
                    'total_weight_kg' => round($sumWeight, 2),
                    'total_coils' => $sumCoils,
                    'total_records' => $sumRecords
                ],
                'data' => $rows
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 4. DANH SÁCH CHI TIẾT SẢN PHẨM & PHÂN TRANG (DATA LIST) - TRUY XUẤT TỪ extrusion_actual_logs
        // =====================================================================
        case 'get_data_list':
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(10, min(200, intval($_GET['limit'] ?? 25)));
            $offset = ($page - 1) * $limit;

            $where = buildExtrusionWhereClause($conn, $_GET);

            // Đếm tổng số bản ghi
            $countRes = $conn->query("SELECT COUNT(*) AS total FROM extrusion_actual_logs p {$where}");
            $total = $countRes ? intval($countRes->fetch_assoc()['total']) : 0;

            // Tính tổng nhanh theo bộ lọc
            $sumRes = $conn->query("
                SELECT 
                    COALESCE(SUM(p.finished_qty_m), 0) AS sum_length,
                    COALESCE(SUM(p.finished_qty_kg), 0) AS sum_weight,
                    COALESCE(SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count), 0) AS sum_coils
                FROM extrusion_actual_logs p
                {$where}
            ");
            $summary = $sumRes ? $sumRes->fetch_assoc() : ['sum_length' => 0, 'sum_weight' => 0, 'sum_coils' => 0];

            // Lấy danh sách phân trang từ extrusion_actual_logs
            $sql = "
                SELECT 
                    p.id,
                    p.production_order_code AS production_code,
                    p.production_date,
                    p.shift,
                    p.mfg_order_code AS directive_code,
                    p.product_code,
                    p.pipe_size AS size_calculated,
                    p.device_code AS machine_code,
                    'Xưởng Đùn Nhựa V61' AS workshop,
                    p.finished_qty_m AS finished_length,
                    p.finished_qty_kg AS finished_weight,
                    p.total_weight_kg AS total_weight,
                    (p.bobbin_pl7_3_count + p.bobbin_pl4_7_count) AS total_coils,
                    p.bobbin_pl7_3_count AS coils_pl7,
                    p.bobbin_pl7_3_meters AS length_pl7,
                    p.bobbin_pl4_7_count AS coils_pl4,
                    p.bobbin_pl4_7_meters AS length_pl4,
                    p.employee_name,
                    p.machine_efficiency AS availability_rate
                FROM extrusion_actual_logs p
                {$where}
                ORDER BY p.production_date DESC, p.id DESC
                LIMIT {$offset}, {$limit}
            ";
            $res = $conn->query($sql);
            $data = [];
            while ($r = $res->fetch_assoc()) {
                $data[] = $r;
            }

            echo json_encode([
                'success' => true,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'summary' => $summary,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 5. XEM CHI TIẾT 1 BẢN GHI (FULL 41 ATTRIBUTES) - TRUY XUẤT TỪ extrusion_actual_logs
        // =====================================================================
        case 'get_detail':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $conn->prepare("
                SELECT 
                    p.*,
                    p.production_order_code AS production_code,
                    p.import_date AS input_date,
                    p.mfg_order_code AS directive_code,
                    p.pipe_size AS size_calculated,
                    p.pipe_size AS size_original,
                    p.device_code AS machine_code,
                    'Xưởng Đùn Nhựa V61' AS workshop,
                    COALESCE(p.process_name, 'Extrusion') AS stage,
                    p.finished_qty_m AS finished_length,
                    p.finished_qty_kg AS finished_weight,
                    p.ng_qty_kg AS ng_weight,
                    p.hard_waste_qty_kg AS hard_weight,
                    p.total_weight_kg AS total_weight,
                    (p.bobbin_pl7_3_count + p.bobbin_pl4_7_count) AS total_coils,
                    p.bobbin_pl7_3_count AS coils_pl7,
                    p.bobbin_pl7_3_meters AS length_pl7,
                    p.bobbin_pl4_7_count AS coils_pl4,
                    p.bobbin_pl4_7_meters AS length_pl4,
                    p.total_downtime AS stop_time_total,
                    p.total_runtime AS run_time,
                    p.cycle_time,
                    p.machine_efficiency AS availability_rate,
                    p.regrind_count AS grind_num,
                    p.regrind_package_code AS grind_package_code,
                    p.is_test AS is_trial,
                    p.material_ng_qty AS ng_material,
                    p.lot_material_ng AS ng_material_lot,
                    p.hdpe_qty AS hdpe_material,
                    p.lio_clean_qty AS lio_clean,
                    p.ti_clean_qty AS ti_clean,
                    p.waiting_machine_count AS standby_machines
                FROM extrusion_actual_logs p 
                WHERE p.id = ? 
                LIMIT 1
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $detail = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$detail) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy dữ liệu mẻ sản xuất tương ứng']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $detail], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 6. ĐỒNG BỘ DỮ LIỆU THỦ CÔNG (MANUAL SYNC: SSOT -> extrusion_productions)
        // =====================================================================
        case 'sync_now':
            if (!$isCli && $userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer không có quyền đồng bộ!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $ok = syncExtrusionLogsToProductions($conn);
            $cntLogs = $conn->query("SELECT COUNT(*) as c FROM extrusion_actual_logs")->fetch_assoc()['c'];
            $cntProd = $conn->query("SELECT COUNT(*) as c FROM extrusion_productions")->fetch_assoc()['c'];

            echo json_encode([
                'success' => (bool)$ok,
                'message' => $ok ? 'Đồng bộ dữ liệu thành công từ bảng chuẩn extrusion_actual_logs!' : ('Lỗi đồng bộ: ' . $conn->error),
                'count_actual_logs' => intval($cntLogs),
                'count_productions' => intval($cntProd)
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 7. IMPORT DỮ LIỆU EXCEL & BULK UPSERT VÀO SSOT VÀ ĐỒNG BỘ SANG extrusion_productions
        // =====================================================================
        case 'import_excel':
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer chỉ có quyền xem, không thể thực hiện Import!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            set_time_limit(600);
            ini_set('memory_limit', '1024M');

            $source = $_POST['file_source'] ?? 'upload';
            $filePath = '';
            $origFileName = '';
            $fileSize = 0;

            if ($source === 'sample') {
                $filePath = __DIR__ . '/../data/Extrusion Report Sample.xlsx';
                if (!file_exists($filePath)) {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy file mẫu Extrusion Report Sample.xlsx trong thư mục data/']);
                    exit;
                }
                $origFileName = 'Extrusion Report Sample.xlsx';
                $fileSize = filesize($filePath);
            } else {
                if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file Excel hợp lệ để tải lên!']);
                    exit;
                }
                $filePath = $_FILES['file']['tmp_name'];
                $origFileName = $_FILES['file']['name'];
                $fileSize = $_FILES['file']['size'];

                $ext = strtolower(pathinfo($origFileName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['xlsx', 'xls', 'xlsm'])) {
                    echo json_encode(['success' => false, 'message' => 'Định dạng file không hỗ trợ! Vui lòng chọn file .xlsx hoặc .xlsm']);
                    exit;
                }
            }

            require_once __DIR__ . '/../vendor/SimpleXLSX.php';
            $xlsx = \Shuchkin\SimpleXLSX::parse($filePath);
            if (!$xlsx) {
                echo json_encode(['success' => false, 'message' => 'Lỗi đọc file Excel: ' . \Shuchkin\SimpleXLSX::parseError()]);
                exit;
            }

            $sheetRows = $xlsx->rows(0);
            $totalRows = count($sheetRows);
            if ($totalRows < 6) {
                echo json_encode(['success' => false, 'message' => 'File Excel không đúng cấu trúc (thiếu các dòng tiêu đề và dữ liệu)!']);
                exit;
            }

            $batchCode = 'IMP-' . date('Ymd-His') . '-' . rand(100, 999);
            $currentUser = $_SESSION['user']['username'] ?? 'SYSTEM';

            $stmtBatch = $conn->prepare("
                INSERT INTO extrusion_import_batches (
                    batch_code, file_name, file_size, total_rows, status, imported_by
                ) VALUES (?, ?, ?, ?, 'processing', ?)
            ");
            $stmtBatch->bind_param("ssiis", $batchCode, $origFileName, $fileSize, $totalRows, $currentUser);
            $stmtBatch->execute();
            $batchId = $stmtBatch->insert_id;
            $stmtBatch->close();

            $chunkSize = 1000;
            $buffer = [];
            $inserted = 0;
            $updated = 0;
            $errorCount = 0;
            $errorLog = [];

            for ($i = 5; $i < $totalRows; $i++) {
                $r = $sheetRows[$i];
                
                $prodCode = trim($r[25] ?? '');
                $directiveCode = trim($r[5] ?? '');
                $productCode = trim($r[6] ?? '');

                if (empty($prodCode) && empty($productCode) && empty($directiveCode)) {
                    continue;
                }

                // Bỏ qua dòng tiêu đề nếu lẫn vào dữ liệu
                if (in_array($productCode, ['Product Code', '品番']) || in_array($prodCode, ['Production Code', '生産コード'])) {
                    continue;
                }

                if (empty($prodCode)) {
                    $prodCode = 'GEN-' . date('Ymd') . '-' . $directiveCode . '-' . $productCode . '-' . $i;
                }

                // Chuẩn hóa Size ống theo đúng quy tắc kỹ thuật
                $calcSize = calculateExtrusionPipeSize($productCode);
                $origSize = trim($r[40] ?? '');

                $prodDate = parseExtrusionDate($r[1] ?? '', date('Y-m-d'));
                $inputDate = parseExtrusionDate($r[0] ?? '', $prodDate);
                $prodMonth = substr($prodDate, 0, 7);
                $prodYear = intval(substr($prodDate, 0, 4));

                $coilsPl7 = intval($r[33] ?? 0);
                $coilsPl4 = intval($r[35] ?? 0);
                $totalCoils = $coilsPl7 + $coilsPl4;

                $buffer[] = [
                    'import_batch_id'   => $batchId,
                    'production_code'   => $prodCode,
                    'input_date'        => $inputDate,
                    'production_date'   => $prodDate,
                    'production_month'  => $prodMonth,
                    'production_year'   => $prodYear,
                    'shift'             => trim($r[4] ?? ''),
                    'employee_code'     => trim($r[2] ?? ''),
                    'employee_name'     => trim($r[3] ?? ''),
                    'directive_code'    => $directiveCode,
                    'product_code'      => $productCode,
                    'size_original'     => $origSize,
                    'size_calculated'   => $calcSize,
                    'cost_center'       => trim($r[7] ?? 'A00330'),
                    'stage'             => trim($r[8] ?? 'Extrusion'),
                    'workshop'          => 'Xưởng Đùn Nhựa V61',
                    'machine_code'      => trim($r[9] ?? ''),
                    'mold_code'         => trim($r[23] ?? ''),
                    'spider_code'       => trim($r[24] ?? ''),
                    'finished_length'   => floatval($r[10] ?? 0),
                    'finished_weight'   => floatval($r[11] ?? 0),
                    'ng_weight'         => floatval($r[12] ?? 0),
                    'hard_weight'       => floatval($r[13] ?? 0),
                    'total_weight'      => floatval($r[14] ?? 0),
                    'total_coils'       => $totalCoils,
                    'coils_pl7'         => $coilsPl7,
                    'length_pl7'        => floatval($r[34] ?? 0),
                    'coils_pl4'         => $coilsPl4,
                    'length_pl4'        => floatval($r[36] ?? 0),
                    'stop_time_total'   => floatval($r[19] ?? 0),
                    'run_time'          => floatval($r[20] ?? 0),
                    'cycle_time'        => floatval($r[21] ?? 0),
                    'availability_rate' => floatval($r[22] ?? 0),
                    'material_code'     => trim($r[15] ?? ''),
                    'grind_num'         => intval($r[16] ?? 0),
                    'grind_package_code'=> trim($r[17] ?? ''),
                    'lot_in'            => trim($r[18] ?? ''),
                    'is_trial'          => intval($r[26] ?? 0),
                    'material_type'     => trim($r[27] ?? ''),
                    'ng_material'       => trim($r[28] ?? ''),
                    'ng_material_lot'   => trim($r[29] ?? ''),
                    'hdpe_material'     => trim($r[30] ?? ''),
                    'lio_clean'         => trim($r[31] ?? ''),
                    'ti_clean'          => trim($r[32] ?? ''),
                    'printer_type'      => trim($r[37] ?? ''),
                    'ink_type'          => trim($r[38] ?? ''),
                    'standby_machines'  => intval($r[39] ?? 0)
                ];

                if (count($buffer) >= $chunkSize) {
                    executeExtrusionChunkUpsert($conn, $buffer, $inserted, $updated, $errorCount, $errorLog);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                executeExtrusionChunkUpsert($conn, $buffer, $inserted, $updated, $errorCount, $errorLog);
                $buffer = [];
            }

            // Sau khi import, đồng bộ lại sang extrusion_productions
            syncExtrusionLogsToProductions($conn);

            $status = ($errorCount > 0 && $inserted === 0 && $updated === 0) ? 'failed' : 'success';
            $errJson = !empty($errorLog) ? json_encode(array_slice($errorLog, 0, 50), JSON_UNESCAPED_UNICODE) : null;

            $stmtUpBatch = $conn->prepare("
                UPDATE extrusion_import_batches
                SET inserted_rows = ?, updated_rows = ?, error_rows = ?, status = ?, error_log = ?
                WHERE id = ?
            ");
            $stmtUpBatch->bind_param("iiissi", $inserted, $updated, $errorCount, $status, $errJson, $batchId);
            $stmtUpBatch->execute();
            $stmtUpBatch->close();

            echo json_encode([
                'success' => true,
                'batch_id' => $batchId,
                'batch_code' => $batchCode,
                'file_name' => $origFileName,
                'inserted' => $inserted,
                'updated' => $updated,
                'errors' => $errorCount,
                'message' => "Import hoàn tất vào bảng chuẩn extrusion_actual_logs: Thêm mới {$inserted} dòng, Cập nhật {$updated} dòng." . ($errorCount > 0 ? " Lỗi {$errorCount} dòng." : "")
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 8. LỊCH SỬ CÁC ĐỢT IMPORT
        // =====================================================================
        case 'get_import_history':
            $res = $conn->query("
                SELECT id, batch_code, file_name, file_size, total_rows, inserted_rows, updated_rows, error_rows, status, imported_by, created_at
                FROM extrusion_import_batches
                ORDER BY id DESC
                LIMIT 50
            ");
            $history = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $history[] = $r;
                }
            }
            echo json_encode(['success' => true, 'data' => $history], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 9. XUẤT DỮ LIỆU EXCEL / CSV - TRUY XUẤT TỪ extrusion_actual_logs
        // =====================================================================
        case 'export_excel':
            $exportType = $_GET['export_type'] ?? 'summary_size';
            $where = buildExtrusionWhereClause($conn, $_GET);

            $exportDate = date('d/m/Y H:i');
            $exporter = $_SESSION['user']['name'] ?? ($_SESSION['user']['username'] ?? 'Hệ thống');

            $fileName = "Extrusion_Report_" . $exportType . "_" . date('Ymd_His') . ".csv";

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');

            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            $filterText = "Điều kiện lọc: ";
            if (!empty($_GET['month'])) $filterText .= "Tháng: " . $_GET['month'] . " | ";
            if (!empty($_GET['year'])) $filterText .= "Năm: " . $_GET['year'] . " | ";
            if (!empty($_GET['date_from']) || !empty($_GET['date_to'])) {
                $filterText .= "Từ: " . ($_GET['date_from'] ?? 'Đầu') . " Đến: " . ($_GET['date_to'] ?? 'Hiện tại') . " | ";
            }
            if (!empty($_GET['pipe_size']) && $_GET['pipe_size'] !== 'all') {
                $filterText .= "Size: " . $_GET['pipe_size'] . " | ";
            }
            if (!empty($_GET['machine_code']) && $_GET['machine_code'] !== 'all') {
                $filterText .= "Máy: " . $_GET['machine_code'] . " | ";
            }
            $filterText .= "Thời gian xuất: {$exportDate} | Người xuất: {$exporter}";

            switch ($exportType) {
                // Mẫu 1: Tổng hợp theo Size
                case 'summary_size':
                    fputcsv($out, ['BÁO CÁO TỔNG HỢP SẢN LƯỢNG ĐÙN ÉP THEO SIZE ỐNG (CHUẨN HÓA)']);
                    fputcsv($out, [$filterText]);
                    fputcsv($out, []);

                    fputcsv($out, ['STT', 'Size Ống (Col AO)', 'Số Lô/Mẫu', 'Tổng Số Cuộn (Bobin)', 'Tổng Trọng Lượng (kg)', 'Tổng Chiều Dài (m)', 'Tỷ Trọng (%)']);
                    
                    $res = $conn->query("
                        SELECT 
                            p.pipe_size AS size_calculated,
                            COUNT(*) AS cnt,
                            SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count) AS coils,
                            SUM(p.finished_qty_kg) AS weight,
                            SUM(p.finished_qty_m) AS length
                        FROM extrusion_actual_logs p
                        {$where}
                        GROUP BY p.pipe_size
                        ORDER BY length DESC
                    ");
                    $stt = 1;
                    $totCoils = 0; $totWeight = 0; $totLength = 0; $totCnt = 0;
                    $rowsTemp = [];
                    while ($r = $res->fetch_assoc()) {
                        $totCoils += intval($r['coils']);
                        $totWeight += floatval($r['weight']);
                        $totLength += floatval($r['length']);
                        $totCnt += intval($r['cnt']);
                        $rowsTemp[] = $r;
                    }

                    foreach ($rowsTemp as $r) {
                        $pct = ($totLength > 0) ? round(floatval($r['length']) / $totLength * 100, 2) : 0;
                        fputcsv($out, [
                            $stt++,
                            $r['size_calculated'],
                            number_format(intval($r['cnt'])),
                            number_format(intval($r['coils'])),
                            number_format(floatval($r['weight']), 2),
                            number_format(floatval($r['length']), 2),
                            $pct . '%'
                        ]);
                    }
                    fputcsv($out, ['TỔNG CỘNG', '', number_format($totCnt), number_format($totCoils), number_format($totWeight, 2), number_format($totLength, 2), '100%']);
                    break;

                // Mẫu 2: Tổng hợp theo Tháng
                case 'summary_month':
                    fputcsv($out, ['BÁO CÁO TỔNG HỢP SẢN LƯỢNG ĐÙN ÉP THEO THÁNG']);
                    fputcsv($out, [$filterText]);
                    fputcsv($out, []);

                    fputcsv($out, ['STT', 'Tháng', 'Tổng Chiều Dài (m)', 'Tổng Trọng Lượng (kg)', 'Tổng Số Cuộn', 'Số Loại Size', 'Số Mã Sản Phẩm']);
                    $res = $conn->query("
                        SELECT 
                            DATE_FORMAT(p.production_date, '%Y-%m') AS production_month,
                            SUM(p.finished_qty_m) AS length,
                            SUM(p.finished_qty_kg) AS weight,
                            SUM(p.bobbin_pl7_3_count + p.bobbin_pl4_7_count) AS coils,
                            COUNT(DISTINCT p.pipe_size) AS count_sizes,
                            COUNT(DISTINCT p.product_code) AS count_products
                        FROM extrusion_actual_logs p
                        {$where}
                        GROUP BY DATE_FORMAT(p.production_date, '%Y-%m')
                        ORDER BY production_month ASC
                    ");
                    $stt = 1;
                    $totLength = 0; $totWeight = 0; $totCoils = 0;
                    while ($r = $res->fetch_assoc()) {
                        $totLength += floatval($r['length']);
                        $totWeight += floatval($r['weight']);
                        $totCoils += intval($r['coils']);
                        fputcsv($out, [
                            $stt++,
                            $r['production_month'],
                            number_format(floatval($r['length']), 2),
                            number_format(floatval($r['weight']), 2),
                            number_format(intval($r['coils'])),
                            $r['count_sizes'],
                            $r['count_products']
                        ]);
                    }
                    fputcsv($out, ['TỔNG CỘNG', '', number_format($totLength, 2), number_format($totWeight, 2), number_format($totCoils), '', '']);
                    break;

                // Mẫu 3: Dữ liệu chi tiết đầy đủ
                case 'details':
                default:
                    fputcsv($out, ['BÁO CÁO CHI TIẾT SẢN LƯỢNG THÀNH PHẨM ĐÙN ÉP']);
                    fputcsv($out, [$filterText]);
                    fputcsv($out, []);

                    fputcsv($out, [
                        'Mã SX (Col Z)', 'Ngày Nhập', 'Ngày SX', 'Mã NV', 'Tên NV', 'Ca', 'Mã CTSX', 'Mã SP', 
                        'Size Tính Toán (AO)', 'Cost Center', 'Công Đoạn', 'Mã Thiết Bị', 'Thành Phẩm (m)', 
                        'KL Thành Phẩm (kg)', 'KL NG (kg)', 'KL Cứng (kg)', 'Tổng KL (kg)', 'Mã VL', 'Lot In',
                        'Mã Khuôn', 'Mã Spider', 'Tổng Cuộn', 'Bobin PL7', 'Mét PL7', 'Bobin PL4', 'Mét PL4',
                        'TG Dừng Máy (h)', 'TG Chạy Máy (h)', 'TG Chu Kỳ (s)', 'Khả Dụng (%)'
                    ]);

                    $res = $conn->query("
                        SELECT 
                            p.production_order_code AS production_code,
                            p.import_date AS input_date,
                            p.production_date,
                            p.employee_code,
                            p.employee_name,
                            p.shift,
                            p.mfg_order_code AS directive_code,
                            p.product_code,
                            p.pipe_size AS size_calculated,
                            p.cost_center,
                            COALESCE(p.process_name, 'Extrusion') AS stage,
                            p.device_code AS machine_code,
                            p.finished_qty_m AS finished_length,
                            p.finished_qty_kg AS finished_weight,
                            p.ng_qty_kg AS ng_weight,
                            p.hard_waste_qty_kg AS hard_weight,
                            p.total_weight_kg AS total_weight,
                            p.material_code,
                            p.lot_in,
                            p.mold_code,
                            p.spider_code,
                            (p.bobbin_pl7_3_count + p.bobbin_pl4_7_count) AS total_coils,
                            p.bobbin_pl7_3_count AS coils_pl7,
                            p.bobbin_pl7_3_meters AS length_pl7,
                            p.bobbin_pl4_7_count AS coils_pl4,
                            p.bobbin_pl4_7_meters AS length_pl4,
                            p.total_downtime AS stop_time_total,
                            p.total_runtime AS run_time,
                            p.cycle_time,
                            p.machine_efficiency AS availability_rate
                        FROM extrusion_actual_logs p 
                        {$where} 
                        ORDER BY p.production_date DESC, p.id DESC 
                        LIMIT 50000
                    ");
                    while ($r = $res->fetch_assoc()) {
                        fputcsv($out, [
                            $r['production_code'],
                            $r['input_date'],
                            $r['production_date'],
                            $r['employee_code'],
                            $r['employee_name'],
                            $r['shift'],
                            $r['directive_code'],
                            $r['product_code'],
                            $r['size_calculated'],
                            $r['cost_center'],
                            $r['stage'],
                            $r['machine_code'],
                            $r['finished_length'],
                            $r['finished_weight'],
                            $r['ng_weight'],
                            $r['hard_weight'],
                            $r['total_weight'],
                            $r['material_code'],
                            $r['lot_in'],
                            $r['mold_code'],
                            $r['spider_code'],
                            $r['total_coils'],
                            $r['coils_pl7'],
                            $r['length_pl7'],
                            $r['coils_pl4'],
                            $r['length_pl4'],
                            $r['stop_time_total'],
                            $r['run_time'],
                            $r['cycle_time'],
                            $r['availability_rate']
                        ]);
                    }
                    break;
            }

            fclose($out);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/**
 * Hàm thực thi Bulk UPSERT vào bảng chuẩn extrusion_actual_logs và đồng bộ sang extrusion_productions
 */
if (!function_exists('executeExtrusionChunkUpsert')) {
function executeExtrusionChunkUpsert($conn, $buffer, &$inserted, &$updated, &$errorCount, &$errorLog) {
    if (empty($buffer)) return;

    $logValues = [];
    foreach ($buffer as $row) {
        $batchId          = intval($row['import_batch_id']);
        $prodCode         = "'" . $conn->real_escape_string($row['production_code']) . "'";
        $inputDate        = $row['input_date'] ? ("'" . $conn->real_escape_string($row['input_date']) . "'") : "NULL";
        $prodDate         = "'" . $conn->real_escape_string($row['production_date']) . "'";
        $shift            = "'" . $conn->real_escape_string($row['shift']) . "'";
        $empCode          = "'" . $conn->real_escape_string($row['employee_code']) . "'";
        $empName          = "'" . $conn->real_escape_string($row['employee_name']) . "'";
        $dirCode          = "'" . $conn->real_escape_string($row['directive_code']) . "'";
        $prodCodeStr      = "'" . $conn->real_escape_string($row['product_code']) . "'";
        $sizeCalc         = "'" . $conn->real_escape_string($row['size_calculated']) . "'";
        $costCenter       = "'" . $conn->real_escape_string($row['cost_center']) . "'";
        $stage            = "'" . $conn->real_escape_string($row['stage']) . "'";
        $machCode         = "'" . $conn->real_escape_string($row['machine_code']) . "'";
        $moldCode         = "'" . $conn->real_escape_string($row['mold_code']) . "'";
        $spiderCode       = "'" . $conn->real_escape_string($row['spider_code']) . "'";
        $finLen           = floatval($row['finished_length']);
        $finWt            = floatval($row['finished_weight']);
        $ngWt             = floatval($row['ng_weight']);
        $hardWt           = floatval($row['hard_weight']);
        $totWt            = floatval($row['total_weight']);
        $coilsPl7         = intval($row['coils_pl7']);
        $lenPl7           = floatval($row['length_pl7']);
        $coilsPl4         = intval($row['coils_pl4']);
        $lenPl4           = floatval($row['length_pl4']);
        $stopTime         = floatval($row['stop_time_total']);
        $runTime          = floatval($row['run_time']);
        $cycleTime        = floatval($row['cycle_time']);
        $availRate        = floatval($row['availability_rate']);
        $matCode          = "'" . $conn->real_escape_string($row['material_code']) . "'";
        $grindNum         = intval($row['grind_num']);
        $grindPkg         = "'" . $conn->real_escape_string($row['grind_package_code']) . "'";
        $lotIn            = "'" . $conn->real_escape_string($row['lot_in']) . "'";
        $isTrial          = intval($row['is_trial']);
        $matType          = "'" . $conn->real_escape_string($row['material_type']) . "'";
        $ngMat            = floatval($row['ng_material'] ?? 0);
        $ngMatLot         = "'" . $conn->real_escape_string($row['ng_material_lot']) . "'";
        $hdpe             = floatval($row['hdpe_material'] ?? 0);
        $lioClean         = floatval($row['lio_clean'] ?? 0);
        $tiClean          = floatval($row['ti_clean'] ?? 0);
        $printer          = "'" . $conn->real_escape_string($row['printer_type']) . "'";
        $ink              = "'" . $conn->real_escape_string($row['ink_type']) . "'";
        $standby          = intval($row['standby_machines']);
        $recordHash       = "'" . md5("{$row['production_date']}|{$row['shift']}|{$row['directive_code']}|{$row['product_code']}|{$row['machine_code']}|{$row['production_code']}") . "'";

        $logValues[] = "({$inputDate}, {$prodDate}, {$empCode}, {$empName}, {$shift}, {$dirCode}, {$prodCodeStr}, {$sizeCalc}, {$costCenter}, {$stage}, {$machCode}, {$finLen}, {$finWt}, {$ngWt}, {$hardWt}, {$totWt}, {$matCode}, {$grindNum}, {$grindPkg}, {$lotIn}, {$stopTime}, {$runTime}, {$cycleTime}, {$availRate}, {$moldCode}, {$spiderCode}, {$prodCode}, {$isTrial}, {$matType}, {$ngMat}, {$ngMatLot}, {$hdpe}, {$lioClean}, {$tiClean}, {$coilsPl7}, {$lenPl7}, {$coilsPl4}, {$lenPl4}, {$printer}, {$ink}, {$standby}, 'EXCEL', {$batchId}, {$recordHash})";
    }

    $sqlLog = "
        INSERT INTO extrusion_actual_logs (
            import_date, production_date, employee_code, employee_name, shift,
            mfg_order_code, product_code, pipe_size, cost_center, process_name,
            device_code, finished_qty_m, finished_qty_kg, ng_qty_kg, hard_waste_qty_kg,
            total_weight_kg, material_code, regrind_count, regrind_package_code, lot_in,
            total_downtime, total_runtime, cycle_time, machine_efficiency, mold_code,
            spider_code, production_order_code, is_test, material_type, material_ng_qty,
            lot_material_ng, hdpe_qty, lio_clean_qty, ti_clean_qty, bobbin_pl7_3_count,
            bobbin_pl7_3_meters, bobbin_pl4_7_count, bobbin_pl4_7_meters, printer_type, ink_type,
            waiting_machine_count, data_source, sync_batch_id, record_hash
        ) VALUES " . implode(",\n", $logValues) . "
        ON DUPLICATE KEY UPDATE
            import_date = VALUES(import_date),
            employee_code = VALUES(employee_code),
            employee_name = VALUES(employee_name),
            finished_qty_m = VALUES(finished_qty_m),
            finished_qty_kg = VALUES(finished_qty_kg),
            ng_qty_kg = VALUES(ng_qty_kg),
            hard_waste_qty_kg = VALUES(hard_waste_qty_kg),
            total_weight_kg = VALUES(total_weight_kg),
            total_downtime = VALUES(total_downtime),
            total_runtime = VALUES(total_runtime),
            cycle_time = VALUES(cycle_time),
            machine_efficiency = VALUES(machine_efficiency),
            bobbin_pl7_3_count = VALUES(bobbin_pl7_3_count),
            bobbin_pl7_3_meters = VALUES(bobbin_pl7_3_meters),
            bobbin_pl4_7_count = VALUES(bobbin_pl4_7_count),
            bobbin_pl4_7_meters = VALUES(bobbin_pl4_7_meters),
            pipe_size = VALUES(pipe_size),
            data_source = VALUES(data_source),
            sync_batch_id = VALUES(sync_batch_id),
            updated_at = CURRENT_TIMESTAMP
    ";

    if ($conn->query($sqlLog)) {
        $affected = $conn->affected_rows;
        $batchCount = count($buffer);
        if ($affected <= $batchCount) {
            $inserted += $affected;
        } else {
            $upd = $affected - $batchCount;
            $ins = $batchCount - $upd;
            $inserted += max(0, $ins);
            $updated += max(0, $upd);
        }
    } else {
        $errorCount += count($buffer);
        $errorLog[] = $conn->error;
    }
}
}
?>
