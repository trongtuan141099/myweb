<?php
/**
 * API Quản Lý Chất Lượng & Theo Dõi Tỉ Lệ Thành Phẩm (良品率)
 * DX Plastic Group - Factory Management System
 */
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/quality_service.php';

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

// Kiểm tra quyền truy cập cơ bản
if (!hasPermission(['quality.view', 'quality.manage', 'quality.investigate', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập dữ liệu quản lý chất lượng.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$curUser = $_SESSION['username'] ?? ($_SESSION['user']['username'] ?? 'USER');
$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_dashboard');

try {
    switch ($action) {
        // =====================================================================
        // 1. LẤY DỮ LIỆU TỔNG QUAN DASHBOARD & BIỂU ĐỒ & MA TRẬN
        // =====================================================================
        case 'get_dashboard':
            $filters = [
                'date_type'         => trim($_GET['date_type'] ?? 'komaki'),
                'period_mode'       => trim($_GET['period_mode'] ?? 'day'),
                'date_from'         => trim($_GET['date_from'] ?? ''),
                'date_to'           => trim($_GET['date_to'] ?? ''),
                'year'              => intval($_GET['year'] ?? date('Y')),
                'month'             => intval($_GET['month'] ?? date('m')),
                'extrusion_machine' => trim($_GET['extrusion_machine'] ?? ''),
                'komaki_machine'    => trim($_GET['komaki_machine'] ?? ''),
                'size'              => trim($_GET['size'] ?? ''),
                'material_type'     => trim($_GET['material_type'] ?? ''),
                'material_group'    => trim($_GET['material_group'] ?? ''),
                'quality_status'    => trim($_GET['quality_status'] ?? ''),
                'search'            => trim($_GET['search'] ?? '')
            ];

            $data = getYieldDashboardStats($conn, $filters);
            echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. LẤY DANH SÁCH BẢN GHI THÀNH PHẨM (PHÂN TRANG)
        // =====================================================================
        case 'get_records':
            $page  = max(1, intval($_GET['page'] ?? 1));
            $limit = max(10, min(200, intval($_GET['limit'] ?? 25)));
            $filters = [
                'date_type'         => trim($_GET['date_type'] ?? 'komaki'),
                'period_mode'       => trim($_GET['period_mode'] ?? 'day'),
                'date_from'         => trim($_GET['date_from'] ?? ''),
                'date_to'           => trim($_GET['date_to'] ?? ''),
                'year'              => intval($_GET['year'] ?? date('Y')),
                'month'             => intval($_GET['month'] ?? date('m')),
                'extrusion_machine' => trim($_GET['extrusion_machine'] ?? ''),
                'komaki_machine'    => trim($_GET['komaki_machine'] ?? ''),
                'size'              => trim($_GET['size'] ?? ''),
                'material_type'     => trim($_GET['material_type'] ?? ''),
                'material_group'    => trim($_GET['material_group'] ?? ''),
                'quality_status'    => trim($_GET['quality_status'] ?? ''),
                'search'            => trim($_GET['search'] ?? '')
            ];

            $res = getYieldRecordsList($conn, $filters, $page, $limit);
            echo json_encode(array_merge(['success' => true], $res), JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 3. LƯU BẢN GHI THÀNH PHẨM (THÊM / SỬA THỦ CÔNG)
        // =====================================================================
        case 'save_record':
            if (!hasPermission(['quality.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa dữ liệu thành phẩm.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $res = saveYieldRecord($conn, $_POST, $curUser);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 4. XÓA BẢN GHI THÀNH PHẨM
        // =====================================================================
        case 'delete_record':
            if (!hasPermission(['quality.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa dữ liệu thành phẩm.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $id = intval($_POST['id'] ?? 0);
            $res = deleteYieldRecord($conn, $id);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 5. CẤU HÌNH MỐC TIÊU CHUẨN BENCHMARK
        // =====================================================================
        case 'get_benchmarks':
            $list = getQualityBenchmarksList($conn);
            echo json_encode(['success' => true, 'data' => $list], JSON_UNESCAPED_UNICODE);
            break;

        case 'save_benchmark':
            if (!hasPermission(['quality.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cấu hình mốc tiêu chuẩn benchmark.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $res = saveQualityBenchmark($conn, $_POST, $curUser);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 6. QUẢN LÝ PHIẾU YÊU CẦU ĐIỀU TRA & ĐỐI ỨNG BẤT THƯỜNG
        // =====================================================================
        case 'get_investigations':
            $filters = [
                'status' => trim($_GET['status'] ?? 'all'),
                'search' => trim($_GET['search'] ?? ''),
                'size'   => trim($_GET['size'] ?? '')
            ];
            $list = getQualityInvestigationsList($conn, $filters);
            echo json_encode(['success' => true, 'data' => $list], JSON_UNESCAPED_UNICODE);
            break;

        case 'save_investigation':
            if (!hasPermission(['quality.investigate', 'quality.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền tạo hoặc chỉnh sửa phiếu điều tra bất thường.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $res = saveQualityInvestigation($conn, $_POST, $curUser);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        case 'delete_investigation':
            if (!hasPermission(['quality.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa phiếu điều tra.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $id = intval($_POST['id'] ?? 0);
            $res = deleteQualityInvestigation($conn, $id);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 7. LẤY CÁC TÙY CHỌN BỘ LỌC (SIZE, MÁY, NĂM...)
        // =====================================================================
        case 'get_filter_options':
            $opts = getQualityFilterOptions($conn);
            echo json_encode(array_merge(['success' => true], $opts), JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 8. CẤU HÌNH QUY TẮC PHÂN LOẠI NGUYÊN VẬT LIỆU (KÝ TỰ THỨ 2 CỦA LOT)
        // =====================================================================
        case 'get_material_rules':
            $rules = array_values(getQualityMaterialRules($conn));
            echo json_encode(['success' => true, 'data' => $rules], JSON_UNESCAPED_UNICODE);
            break;

        case 'save_material_rule':
            if (!hasPermission(['quality.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cấu hình quy tắc phân loại vật liệu.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $res = saveQualityMaterialRule($conn, $_POST);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        case 'delete_material_rule':
            if (!hasPermission(['quality.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa quy tắc phân loại vật liệu.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $id = intval($_POST['id'] ?? 0);
            $res = deleteQualityMaterialRule($conn, $id);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
