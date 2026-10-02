<?php
/**
 * API Quản Lý & Tổng Hợp Sản Lượng Đùn Ép (Extrusion Production Management API)
 * DX Plastic Group - Production MES System
 */
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

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
 * Thuật toán tính Size Ống chuẩn hóa theo đúng logic Cột công thức AO trong Excel mẫu
 * _xlfn.IFS(
 *    LEFT(G,4)="HF2B", MID(G,6,6),
 *    LEFT(G,4)="TIUB", LEFT(G,6),
 *    LEFT(G,3)="TIA",  LEFT(G,5),
 *    LEFT(G,2)="TU",   LEFT(G,6),
 *    LEFT(G,1)="T",    LEFT(G,5)
 * )
 */
if (!function_exists('calculateExtrusionPipeSize')) {
    function calculateExtrusionPipeSize($productCode) {
        $code = strtoupper(trim((string)$productCode));
        if ($code === '') {
            return '#N/A';
        }

        // 1. Tiền tố HF2B: MID(G, 6, 6) trong Excel là 1-based, offset 5 trong PHP
        if (substr($code, 0, 4) === 'HF2B') {
            return substr($code, 5, 6);
        }

        // 2. Tiền tố TIUB: LEFT(G, 6)
        if (substr($code, 0, 4) === 'TIUB') {
            return substr($code, 0, 6);
        }

        // 3. Tiền tố TIA: LEFT(G, 5)
        if (substr($code, 0, 3) === 'TIA') {
            return substr($code, 0, 5);
        }

        // 4. Tiền tố TU: LEFT(G, 6)
        if (substr($code, 0, 2) === 'TU') {
            return substr($code, 0, 6);
        }

        // 5. Tiền tố T: LEFT(G, 5)
        if (substr($code, 0, 1) === 'T') {
            return substr($code, 0, 5);
        }

        // Không khớp các mẫu quy chuẩn
        return '#N/A';
    }
}

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
 * Hàm xây dựng mệnh đề WHERE lọc dữ liệu
 */
if (!function_exists('buildExtrusionWhereClause')) {
function buildExtrusionWhereClause($conn, $params) {
    $where = "WHERE 1=1";
    
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
        $where .= " AND p.production_month = '{$m}'";
    }
    if (!empty($params['year'])) {
        $y = intval($params['year']);
        $where .= " AND p.production_year = {$y}";
    }
    if (!empty($params['pipe_size']) && $params['pipe_size'] !== 'all') {
        $sz = $conn->real_escape_string(trim($params['pipe_size']));
        $where .= " AND p.size_calculated = '{$sz}'";
    }
    if (!empty($params['product_code'])) {
        $pc = $conn->real_escape_string(trim($params['product_code']));
        $where .= " AND p.product_code LIKE '%{$pc}%'";
    }
    if (!empty($params['machine_code']) && $params['machine_code'] !== 'all') {
        $mc = $conn->real_escape_string(trim($params['machine_code']));
        $where .= " AND p.machine_code = '{$mc}'";
    }
    if (!empty($params['workshop']) && $params['workshop'] !== 'all') {
        $ws = $conn->real_escape_string(trim($params['workshop']));
        $where .= " AND p.workshop = '{$ws}'";
    }
    if (!empty($params['search'])) {
        $s = $conn->real_escape_string(trim($params['search']));
        $where .= " AND (p.production_code LIKE '%{$s}%' OR p.directive_code LIKE '%{$s}%' OR p.product_code LIKE '%{$s}%' OR p.employee_name LIKE '%{$s}%' OR p.machine_code LIKE '%{$s}%' OR p.size_calculated LIKE '%{$s}%')";
    }
    
    return $where;
}
}

try {
    switch ($action) {
        // =====================================================================
        // 1. TÙY CHỌN BỘ LỌC ĐỘNG (FILTER OPTIONS)
        // =====================================================================
        case 'get_filter_options':
            // Danh sách Size đã chuẩn hóa
            $sizesRes = $conn->query("SELECT DISTINCT size_calculated FROM extrusion_productions WHERE size_calculated IS NOT NULL AND size_calculated != '' ORDER BY size_calculated ASC");
            $sizes = [];
            while ($r = $sizesRes->fetch_assoc()) {
                $sizes[] = $r['size_calculated'];
            }

            // Danh sách Máy sản xuất
            $machinesRes = $conn->query("SELECT DISTINCT machine_code FROM extrusion_productions WHERE machine_code IS NOT NULL AND machine_code != '' ORDER BY machine_code ASC");
            $machines = [];
            while ($r = $machinesRes->fetch_assoc()) {
                $machines[] = $r['machine_code'];
            }

            // Danh sách Năm sản xuất
            $yearsRes = $conn->query("SELECT DISTINCT production_year FROM extrusion_productions WHERE production_year > 0 ORDER BY production_year DESC");
            $years = [];
            while ($r = $yearsRes->fetch_assoc()) {
                $years[] = intval($r['production_year']);
            }
            if (empty($years)) $years[] = intval(date('Y'));

            // Danh sách Xưởng sản xuất
            $wsRes = $conn->query("SELECT DISTINCT workshop FROM extrusion_productions WHERE workshop IS NOT NULL AND workshop != '' ORDER BY workshop ASC");
            $workshops = [];
            while ($r = $wsRes->fetch_assoc()) {
                $workshops[] = $r['workshop'];
            }

            echo json_encode([
                'success' => true,
                'sizes' => $sizes,
                'machines' => $machines,
                'years' => $years,
                'workshops' => $workshops
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. DASHBOARD TỔNG HỢP (KPI & BIỂU ĐỒ)
        // =====================================================================
        case 'get_dashboard':
            $where = buildExtrusionWhereClause($conn, $_GET);

            // 1. 4 Thẻ KPI chính
            $kpiSql = "
                SELECT 
                    COUNT(*) AS total_records,
                    COALESCE(SUM(p.finished_length), 0) AS total_length_m,
                    COALESCE(SUM(p.finished_weight), 0) AS total_weight_kg,
                    COALESCE(SUM(p.total_weight), 0) AS grand_total_weight_kg,
                    COALESCE(SUM(p.total_coils), 0) AS total_coils,
                    COUNT(DISTINCT p.size_calculated) AS count_sizes,
                    COUNT(DISTINCT p.product_code) AS count_products,
                    COUNT(DISTINCT p.machine_code) AS count_machines
                FROM extrusion_productions p
                {$where}
            ";
            $kpiRes = $conn->query($kpiSql);
            $kpis = $kpiRes ? $kpiRes->fetch_assoc() : [];

            // 2. Top 10 Size sản xuất nhiều nhất (theo tổng chiều dài m)
            $topSizesSql = "
                SELECT 
                    p.size_calculated,
                    COALESCE(SUM(p.finished_length), 0) AS total_length_m,
                    COALESCE(SUM(p.finished_weight), 0) AS total_weight_kg,
                    COALESCE(SUM(p.total_coils), 0) AS total_coils,
                    COUNT(*) AS record_count
                FROM extrusion_productions p
                {$where}
                GROUP BY p.size_calculated
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
                    p.size_calculated,
                    COALESCE(SUM(p.finished_length), 0) AS total_length_m,
                    COALESCE(SUM(p.finished_weight), 0) AS total_weight_kg,
                    COALESCE(SUM(p.total_coils), 0) AS total_coils
                FROM extrusion_productions p
                {$where}
                GROUP BY p.product_code, p.size_calculated
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
                    p.production_month,
                    COALESCE(SUM(p.finished_length), 0) AS total_length_m,
                    COALESCE(SUM(p.finished_weight), 0) AS total_weight_kg,
                    COALESCE(SUM(p.total_coils), 0) AS total_coils
                FROM extrusion_productions p
                {$where}
                GROUP BY p.production_month
                ORDER BY p.production_month ASC
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
                    p.machine_code,
                    COALESCE(SUM(p.finished_length), 0) AS total_length_m,
                    COALESCE(SUM(p.finished_weight), 0) AS total_weight_kg
                FROM extrusion_productions p
                {$where}
                GROUP BY p.machine_code
                ORDER BY total_length_m DESC
            ";
            $mcRes = $conn->query($machineSql);
            $machineChart = [
                'labels' => [],
                'length' => [],
                'weight' => []
            ];
            while ($r = $mcRes->fetch_assoc()) {
                // Rút gọn nhãn máy hiển thị (V61-MAYDUN.PL17 -> PL17)
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
        // 3. TỔNG HỢP DỮ LIỆU SẢN LƯỢNG (AGGREGATION ENGINE)
        // =====================================================================
        case 'get_aggregation':
            $type = $_GET['type'] ?? 'size'; // 'size', 'month', 'range_size', 'range_product', 'range_machine', 'range_workshop'
            $sortCol = $_GET['sort_col'] ?? 'output'; // 'output' (length), 'weight', 'coils', 'size'
            $sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');
            if (!in_array($sortOrder, ['ASC', 'DESC'])) $sortOrder = 'DESC';

            $where = buildExtrusionWhereClause($conn, $_GET);

            // Xác định cột ORDER BY
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
                            p.size_calculated AS group_key,
                            p.size_calculated AS pipe_size,
                            COUNT(*) AS total_records,
                            COALESCE(SUM(p.total_coils), 0) AS total_coils,
                            COALESCE(SUM(p.finished_weight), 0) AS total_weight_kg,
                            COALESCE(SUM(p.finished_length), 0) AS total_length_m,
                            COUNT(DISTINCT p.product_code) AS count_products,
                            COUNT(DISTINCT p.machine_code) AS count_machines
                        FROM extrusion_productions p
                        {$where}
                        GROUP BY p.size_calculated
                        ORDER BY {$orderField} {$sortOrder}
                    ";
                    break;

                // B. Tổng hợp theo Tháng
                case 'month':
                    $sql = "
                        SELECT 
                            p.production_month AS group_key,
                            p.production_month,
                            COUNT(*) AS total_records,
                            COALESCE(SUM(p.finished_length), 0) AS total_length_m,
                            COALESCE(SUM(p.finished_weight), 0) AS total_weight_kg,
                            COALESCE(SUM(p.total_coils), 0) AS total_coils,
                            COUNT(DISTINCT p.size_calculated) AS count_sizes,
                            COUNT(DISTINCT p.product_code) AS count_products
                        FROM extrusion_productions p
                        {$where}
                        GROUP BY p.production_month
                        ORDER BY p.production_month {$sortOrder}
                    ";
                    break;

                // C. Tổng hợp theo Mã Sản Phẩm
                case 'range_product':
                    $sql = "
                        SELECT 
                            p.product_code AS group_key,
                            p.product_code,
                            p.size_calculated,
                            COUNT(*) AS total_records,
                            COALESCE(SUM(p.total_coils), 0) AS total_coils,
                            COALESCE(SUM(p.finished_weight), 0) AS total_weight_kg,
                            COALESCE(SUM(p.finished_length), 0) AS total_length_m,
                            COUNT(DISTINCT p.machine_code) AS count_machines
                        FROM extrusion_productions p
                        {$where}
                        GROUP BY p.product_code, p.size_calculated
                        ORDER BY {$orderField} {$sortOrder}
                    ";
                    break;

                // C. Tổng hợp theo Máy sản xuất
                case 'range_machine':
                    $sql = "
                        SELECT 
                            p.machine_code AS group_key,
                            p.machine_code,
                            COUNT(*) AS total_records,
                            COALESCE(SUM(p.total_coils), 0) AS total_coils,
                            COALESCE(SUM(p.finished_weight), 0) AS total_weight_kg,
                            COALESCE(SUM(p.finished_length), 0) AS total_length_m,
                            COUNT(DISTINCT p.size_calculated) AS count_sizes,
                            COALESCE(SUM(p.stop_time_total), 0) AS total_stop_time,
                            COALESCE(SUM(p.run_time), 0) AS total_run_time
                        FROM extrusion_productions p
                        {$where}
                        GROUP BY p.machine_code
                        ORDER BY {$orderField} {$sortOrder}
                    ";
                    break;

                // C. Tổng hợp theo Xưởng
                case 'range_workshop':
                    $sql = "
                        SELECT 
                            p.workshop AS group_key,
                            p.workshop,
                            COUNT(*) AS total_records,
                            COALESCE(SUM(p.total_coils), 0) AS total_coils,
                            COALESCE(SUM(p.finished_weight), 0) AS total_weight_kg,
                            COALESCE(SUM(p.finished_length), 0) AS total_length_m,
                            COUNT(DISTINCT p.machine_code) AS count_machines,
                            COUNT(DISTINCT p.size_calculated) AS count_sizes
                        FROM extrusion_productions p
                        {$where}
                        GROUP BY p.workshop
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
        // 4. DANH SÁCH CHI TIẾT SẢN PHẨM & PHÂN TRANG (DATA LIST)
        // =====================================================================
        case 'get_data_list':
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(10, min(200, intval($_GET['limit'] ?? 25)));
            $offset = ($page - 1) * $limit;

            $where = buildExtrusionWhereClause($conn, $_GET);

            // Đếm tổng số bản ghi
            $countRes = $conn->query("SELECT COUNT(*) AS total FROM extrusion_productions p {$where}");
            $total = $countRes ? intval($countRes->fetch_assoc()['total']) : 0;

            // Tính tổng nhanh theo bộ lọc
            $sumRes = $conn->query("
                SELECT 
                    COALESCE(SUM(p.finished_length), 0) AS sum_length,
                    COALESCE(SUM(p.finished_weight), 0) AS sum_weight,
                    COALESCE(SUM(p.total_coils), 0) AS sum_coils
                FROM extrusion_productions p
                {$where}
            ");
            $summary = $sumRes ? $sumRes->fetch_assoc() : ['sum_length' => 0, 'sum_weight' => 0, 'sum_coils' => 0];

            // Lấy danh sách phân trang
            $sql = "
                SELECT 
                    p.id,
                    p.production_code,
                    p.production_date,
                    p.shift,
                    p.directive_code,
                    p.product_code,
                    p.size_calculated,
                    p.machine_code,
                    p.workshop,
                    p.finished_length,
                    p.finished_weight,
                    p.total_weight,
                    p.total_coils,
                    p.coils_pl7,
                    p.length_pl7,
                    p.coils_pl4,
                    p.length_pl4,
                    p.employee_name,
                    p.availability_rate
                FROM extrusion_productions p
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
        // 5. XEM CHI TIẾT 1 BẢN GHI (FULL 41 ATTRIBUTES)
        // =====================================================================
        case 'get_detail':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $conn->prepare("SELECT * FROM extrusion_productions WHERE id = ? LIMIT 1");
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
        // 6. IMPORT DỮ LIỆU EXCEL & BULK UPSERT (CHUNK 1,000 ROWS)
        // =====================================================================
        case 'import_excel':
            // Yêu cầu quyền quản lý/nhập dữ liệu
            if ($userRole === 'viewer') {
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 403, 'message' => 'Tài khoản Viewer chỉ có quyền xem, không thể thực hiện Import!'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            set_time_limit(600); // 10 phút cho file lớn >100.000 dòng
            ini_set('memory_limit', '1024M');

            $source = $_POST['file_source'] ?? 'upload';
            $filePath = '';
            $origFileName = '';
            $fileSize = 0;

            if ($source === 'sample') {
                // Tải trực tiếp file mẫu trong thư mục data/
                $filePath = __DIR__ . '/../data/Extrusion Report Sample.xlsx';
                if (!file_exists($filePath)) {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy file mẫu Extrusion Report Sample.xlsx trong thư mục data/']);
                    exit;
                }
                $origFileName = 'Extrusion Report Sample.xlsx';
                $fileSize = filesize($filePath);
            } else {
                // File người dùng upload
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

            // Đọc sheet đầu tiên (DATA_PLASTIC)
            $sheetRows = $xlsx->rows(0);
            $totalRows = count($sheetRows);
            if ($totalRows < 6) {
                echo json_encode(['success' => false, 'message' => 'File Excel không đúng cấu trúc (thiếu các dòng tiêu đề và dữ liệu)!']);
                exit;
            }

            // Tạo mã Batch Import
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

            // Bắt đầu đọc dữ liệu từ dòng 6 (index 5)
            for ($i = 5; $i < $totalRows; $i++) {
                $r = $sheetRows[$i];
                
                $prodCode = trim($r[25] ?? ''); // Col Z
                $directiveCode = trim($r[5] ?? ''); // Col F
                $productCode = trim($r[6] ?? '');   // Col G

                // Bỏ qua dòng trống hoàn toàn
                if (empty($prodCode) && empty($productCode) && empty($directiveCode)) {
                    continue;
                }

                // Nếu thiếu Mã SX, tự sinh mã tạm dựa trên Chỉ thị + Sản phẩm + Dòng
                if (empty($prodCode)) {
                    $prodCode = 'GEN-' . date('Ymd') . '-' . $directiveCode . '-' . $productCode . '-' . $i;
                }

                // Tính toán Size ống theo công thức AO
                $calcSize = calculateExtrusionPipeSize($productCode);
                $origSize = trim($r[40] ?? ''); // Col AO nếu có sẵn

                $prodDate = parseExtrusionDate($r[1] ?? '', date('Y-m-d')); // Col B
                $inputDate = parseExtrusionDate($r[0] ?? '', $prodDate);    // Col A
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

            // Flush số dòng còn lại
            if (!empty($buffer)) {
                executeExtrusionChunkUpsert($conn, $buffer, $inserted, $updated, $errorCount, $errorLog);
                $buffer = [];
            }

            // Cập nhật kết quả Batch
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
                'total_read' => ($totalRows - 5),
                'inserted' => $inserted,
                'updated' => $updated,
                'errors' => $errorCount,
                'message' => "Import hoàn tất! Thêm mới: {$inserted} dòng, Cập nhật (UPSERT): {$updated} dòng, Lỗi: {$errorCount} dòng."
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 7. LỊCH SỬ CÁC ĐỢT IMPORT (AUDIT BATCHES)
        // =====================================================================
        case 'get_import_history':
            $res = $conn->query("SELECT * FROM extrusion_import_batches ORDER BY id DESC LIMIT 50");
            $batches = [];
            while ($r = $res->fetch_assoc()) {
                $batches[] = $r;
            }
            echo json_encode(['success' => true, 'data' => $batches], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 8. XUẤT EXCEL (EXPORT ENGINE CÓ TIÊU ĐỀ & BOM UTF-8)
        // =====================================================================
        case 'export_excel':
            $exportType = $_GET['export_type'] ?? 'summary_size';
            $where = buildExtrusionWhereClause($conn, $_GET);
            $exportDate = date('d/m/Y H:i:s');
            $exporter = $_SESSION['user']['fullname'] ?? ($_SESSION['user']['username'] ?? 'User');

            // Tạo header HTTP để tải file CSV với UTF-8 BOM
            $fileName = "BaoCaoSanLuongDunEp_" . $exportType . "_" . date('Ymd_His') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $out = fopen('php://output', 'w');
            // Ghi UTF-8 BOM để Excel tự động nhận diện tiếng Việt có dấu
            fputs($out, "\xEF\xBB\xBF");

            // Phần tiêu đề doanh nghiệp
            fputcsv($out, ['CÔNG TY TNHH NHỰA SMC (VIỆT NAM) - TẬP ĐOÀN DX PLASTIC GROUP']);
            fputcsv($out, ['HỆ THỐNG QUẢN LÝ SẢN XUẤT MES - PHÂN HỆ ĐÙN ÉP ỐNG NHỰA']);
            
            $filterText = "Bộ lọc áp dụng: ";
            if (!empty($_GET['date_from']) || !empty($_GET['date_to'])) {
                $filterText .= "Từ ngày " . ($_GET['date_from'] ?: '...') . " đến " . ($_GET['date_to'] ?: '...') . " | ";
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
                    fputcsv($out, ['BÁO CÁO TỔNG HỢP SẢN LƯỢNG ĐÙN ÉP THEO SIZE ỐNG']);
                    fputcsv($out, [$filterText]);
                    fputcsv($out, []); // Dòng trống

                    fputcsv($out, ['STT', 'Size Ống (Col AO)', 'Số Lô/Mẫu', 'Tổng Số Cuộn (Bobin)', 'Tổng Trọng Lượng (kg)', 'Tổng Chiều Dài (m)', 'Tỷ Trọng (%)']);
                    
                    $res = $conn->query("
                        SELECT 
                            size_calculated,
                            COUNT(*) AS cnt,
                            SUM(total_coils) AS coils,
                            SUM(finished_weight) AS weight,
                            SUM(finished_length) AS length
                        FROM extrusion_productions p
                        {$where}
                        GROUP BY size_calculated
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
                    // Dòng tổng cộng
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
                            production_month,
                            SUM(finished_length) AS length,
                            SUM(finished_weight) AS weight,
                            SUM(total_coils) AS coils,
                            COUNT(DISTINCT size_calculated) AS count_sizes,
                            COUNT(DISTINCT product_code) AS count_products
                        FROM extrusion_productions p
                        {$where}
                        GROUP BY production_month
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

                    $res = $conn->query("SELECT * FROM extrusion_productions p {$where} ORDER BY production_date DESC, id DESC LIMIT 50000");
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
 * Hàm thực thi Bulk UPSERT theo lô để tối ưu hiệu năng
 */
if (!function_exists('executeExtrusionChunkUpsert')) {
function executeExtrusionChunkUpsert($conn, $buffer, &$inserted, &$updated, &$errorCount, &$errorLog) {
    if (empty($buffer)) return;

    $values = [];
    foreach ($buffer as $row) {
        $batchId          = intval($row['import_batch_id']);
        $prodCode         = "'" . $conn->real_escape_string($row['production_code']) . "'";
        $inputDate        = $row['input_date'] ? ("'" . $conn->real_escape_string($row['input_date']) . "'") : "NULL";
        $prodDate         = "'" . $conn->real_escape_string($row['production_date']) . "'";
        $prodMonth        = "'" . $conn->real_escape_string($row['production_month']) . "'";
        $prodYear         = intval($row['production_year']);
        $shift            = "'" . $conn->real_escape_string($row['shift']) . "'";
        $empCode          = "'" . $conn->real_escape_string($row['employee_code']) . "'";
        $empName          = "'" . $conn->real_escape_string($row['employee_name']) . "'";
        $dirCode          = "'" . $conn->real_escape_string($row['directive_code']) . "'";
        $prodCodeStr      = "'" . $conn->real_escape_string($row['product_code']) . "'";
        $sizeOrig         = "'" . $conn->real_escape_string($row['size_original']) . "'";
        $sizeCalc         = "'" . $conn->real_escape_string($row['size_calculated']) . "'";
        $costCenter       = "'" . $conn->real_escape_string($row['cost_center']) . "'";
        $stage            = "'" . $conn->real_escape_string($row['stage']) . "'";
        $workshop         = "'" . $conn->real_escape_string($row['workshop']) . "'";
        $machCode         = "'" . $conn->real_escape_string($row['machine_code']) . "'";
        $moldCode         = "'" . $conn->real_escape_string($row['mold_code']) . "'";
        $spiderCode       = "'" . $conn->real_escape_string($row['spider_code']) . "'";
        $finLen           = floatval($row['finished_length']);
        $finWt            = floatval($row['finished_weight']);
        $ngWt             = floatval($row['ng_weight']);
        $hardWt           = floatval($row['hard_weight']);
        $totWt            = floatval($row['total_weight']);
        $totCoils         = intval($row['total_coils']);
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
        $ngMat            = "'" . $conn->real_escape_string($row['ng_material']) . "'";
        $ngMatLot         = "'" . $conn->real_escape_string($row['ng_material_lot']) . "'";
        $hdpe             = "'" . $conn->real_escape_string($row['hdpe_material']) . "'";
        $lioClean         = "'" . $conn->real_escape_string($row['lio_clean']) . "'";
        $tiClean          = "'" . $conn->real_escape_string($row['ti_clean']) . "'";
        $printer          = "'" . $conn->real_escape_string($row['printer_type']) . "'";
        $ink              = "'" . $conn->real_escape_string($row['ink_type']) . "'";
        $standby          = intval($row['standby_machines']);

        $values[] = "({$batchId}, {$prodCode}, {$inputDate}, {$prodDate}, {$prodMonth}, {$prodYear}, {$shift}, {$empCode}, {$empName}, {$dirCode}, {$prodCodeStr}, {$sizeOrig}, {$sizeCalc}, {$costCenter}, {$stage}, {$workshop}, {$machCode}, {$moldCode}, {$spiderCode}, {$finLen}, {$finWt}, {$ngWt}, {$hardWt}, {$totWt}, {$totCoils}, {$coilsPl7}, {$lenPl7}, {$coilsPl4}, {$lenPl4}, {$stopTime}, {$runTime}, {$cycleTime}, {$availRate}, {$matCode}, {$grindNum}, {$grindPkg}, {$lotIn}, {$isTrial}, {$matType}, {$ngMat}, {$ngMatLot}, {$hdpe}, {$lioClean}, {$tiClean}, {$printer}, {$ink}, {$standby})";
    }

    $sql = "
        INSERT INTO extrusion_productions (
            import_batch_id, production_code, input_date, production_date, production_month, production_year, shift,
            employee_code, employee_name, directive_code, product_code, size_original, size_calculated,
            cost_center, stage, workshop, machine_code, mold_code, spider_code,
            finished_length, finished_weight, ng_weight, hard_weight, total_weight, total_coils,
            coils_pl7, length_pl7, coils_pl4, length_pl4, stop_time_total, run_time, cycle_time, availability_rate,
            material_code, grind_num, grind_package_code, lot_in, is_trial, material_type,
            ng_material, ng_material_lot, hdpe_material, lio_clean, ti_clean, printer_type, ink_type, standby_machines
        ) VALUES " . implode(",\n", $values) . "
        ON DUPLICATE KEY UPDATE
            import_batch_id   = VALUES(import_batch_id),
            input_date        = VALUES(input_date),
            production_date   = VALUES(production_date),
            production_month  = VALUES(production_month),
            production_year   = VALUES(production_year),
            shift             = VALUES(shift),
            employee_code     = VALUES(employee_code),
            employee_name     = VALUES(employee_name),
            directive_code    = VALUES(directive_code),
            product_code      = VALUES(product_code),
            size_original     = VALUES(size_original),
            size_calculated   = VALUES(size_calculated),
            cost_center       = VALUES(cost_center),
            stage             = VALUES(stage),
            workshop          = VALUES(workshop),
            machine_code      = VALUES(machine_code),
            mold_code         = VALUES(mold_code),
            spider_code       = VALUES(spider_code),
            finished_length   = VALUES(finished_length),
            finished_weight   = VALUES(finished_weight),
            ng_weight         = VALUES(ng_weight),
            hard_weight       = VALUES(hard_weight),
            total_weight      = VALUES(total_weight),
            total_coils       = VALUES(total_coils),
            coils_pl7         = VALUES(coils_pl7),
            length_pl7        = VALUES(length_pl7),
            coils_pl4         = VALUES(coils_pl4),
            length_pl4        = VALUES(length_pl4),
            stop_time_total   = VALUES(stop_time_total),
            run_time          = VALUES(run_time),
            cycle_time        = VALUES(cycle_time),
            availability_rate = VALUES(availability_rate),
            material_code     = VALUES(material_code),
            grind_num         = VALUES(grind_num),
            grind_package_code= VALUES(grind_package_code),
            lot_in            = VALUES(lot_in),
            is_trial          = VALUES(is_trial),
            material_type     = VALUES(material_type),
            ng_material       = VALUES(ng_material),
            ng_material_lot   = VALUES(ng_material_lot),
            hdpe_material     = VALUES(hdpe_material),
            lio_clean         = VALUES(lio_clean),
            ti_clean          = VALUES(ti_clean),
            printer_type      = VALUES(printer_type),
            ink_type          = VALUES(ink_type),
            standby_machines  = VALUES(standby_machines),
            updated_at        = CURRENT_TIMESTAMP
    ";

    if ($conn->query($sql)) {
        // Trong MySQL ON DUPLICATE KEY UPDATE:
        // affected_rows = 1 nếu INSERT mới, 2 nếu UPDATE, 0 nếu không thay đổi
        $affected = $conn->affected_rows;
        $batchCount = count($buffer);
        // Ước tính số dòng INSERT vs UPDATE
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

