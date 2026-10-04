<?php
/**
 * API Quản Lý Đơn Đặt Hàng (Đơn B)
 * Phân hệ: Quản Lý Đơn Hàng (Orders Management) - DX Plastic Group
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../vendor/autoload.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (file_exists(__DIR__ . '/../config/db.php')) {
        require_once __DIR__ . '/../config/db.php';
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra xác thực (Authentication)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'code'    => 401,
        'message' => 'Phiên làm việc đã hết hạn hoặc chưa đăng nhập. Vui lòng đăng nhập lại!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentUser = $_SESSION['user'] ?? [
    'id'       => $_SESSION['user_id'] ?? 1,
    'username' => $_SESSION['username'] ?? 'User',
    'fullname' => $_SESSION['fullname'] ?? 'User',
    'role'     => $_SESSION['role'] ?? 'viewer'
];
$userRole     = $currentUser['role'] ?? 'viewer';
$userName     = $currentUser['username'] ?? 'User';
$userFullName = $currentUser['fullname'] ?? $userName;

$action = trim($_REQUEST['action'] ?? 'list');

// Helper: Trích xuất số mét từ mã sản phẩm
if (!function_exists('extractMetersFromCode')) {
    function extractMetersFromCode($code) {
        if (!$code) return 0;
        if (preg_match('/-(\d+)/', $code, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }
}

// Helper: Phân tích ngày tháng từ Excel / string
if (!function_exists('parseOrderDate')) {
    function parseOrderDate($val) {
        if ($val === null || $val === '' || $val === '#N/A' || $val === 'Chưa xác định' || $val === '#') {
            return null;
        }
        if (is_numeric($val) && $val > 10000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }
        if (is_string($val)) {
            $val = trim($val);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                return $val;
            }
            $ts = strtotime($val);
            if ($ts && $ts > 0 && date('Y', $ts) > 2000 && date('Y', $ts) < 2100) {
                return date('Y-m-d', $ts);
            }
        }
        return null;
    }
}

// Helper: Ghi log lịch sử thao tác (Audit trail)
if (!function_exists('logDonBAction')) {
    function logDonBAction($conn, $donBId, $maDonHang, $department, $userName, $actionText, $content) {
        $stmt = $conn->prepare("INSERT INTO don_b_history (don_b_id, ma_don_hang, department, user_name, action, content, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("isssss", $donBId, $maDonHang, $department, $userName, $actionText, $content);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Helper: Thêm thông báo nội bộ
if (!function_exists('sendDonBNotification')) {
    function sendDonBNotification($conn, $donBId, $maDonHang, $targetDept, $title, $message) {
        $stmt = $conn->prepare("INSERT INTO don_b_notifications (don_b_id, ma_don_hang, target_dept, title, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("issss", $donBId, $maDonHang, $targetDept, $title, $message);
            $stmt->execute();
            $stmt->close();
        }
        
        $link = "index.php?mainpage=orders&subpage=don_b&order_code=" . urlencode($maDonHang);
        $resUsers = $conn->query("SELECT id FROM users WHERE status = 'active' LIMIT 50");
        if ($resUsers) {
            $stmtSys = $conn->prepare("INSERT INTO notifications (user_id, title, message, link, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            if ($stmtSys) {
                while ($u = $resUsers->fetch_assoc()) {
                    $uid = (int)$u['id'];
                    $stmtSys->bind_param("isss", $uid, $title, $message, $link);
                    $stmtSys->execute();
                }
                $stmtSys->close();
            }
        }
    }
}

try {
    switch ($action) {

        // ==========================================
        // 1. DANH SÁCH ĐƠN HÀNG VÀ LỌC NÂNG CAO
        // ==========================================
        case 'list': {
            requireApiPermission(['orders.view', 'api.orders.get', 'admin']);

            $search     = trim($_GET['search'] ?? '');
            $tab        = trim($_GET['tab'] ?? 'all');
            $dunFilter  = trim($_GET['dun_status'] ?? '');
            $cuonFilter = trim($_GET['cuon_status'] ?? '');
            $page       = max(1, (int)($_GET['page'] ?? 1));
            $limit      = max(10, min(500, (int)($_GET['limit'] ?? 25)));
            $offset     = ($page - 1) * $limit;

            $where  = ["1=1"];
            $params = [];
            $types  = "";

            if ($search !== '') {
                $where[]  = "(ma_don_hang LIKE ? OR ma_san_pham LIKE ? OR so_phieu_nhap LIKE ? OR ma_khach_hang LIKE ? OR ten_khach_hang LIKE ?)";
                $like     = "%{$search}%";
                $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
                $types   .= "sssss";
            }

            switch ($tab) {
                case 'don_b_all':
                    $where[] = "phan_loai_don = 'B'";
                    break;
                case 'alert_5days':
                    // Kỳ hạn nhập kho trong 5 ngày tới hoặc quá hạn mà chưa có thực tích
                    $where[] = "ngay_yc_nhap_kho IS NOT NULL AND DATEDIFF(ngay_yc_nhap_kho, CURDATE()) <= 5 AND (cuon_da_sx = 0 OR cuon_da_sx IS NULL) AND tinh_trang_nhap_kho != 'Hoàn thành'";
                    break;
                case 'pending_confirm':
                    // Đơn B mà Đùn hoặc Cuộn chưa xác nhận
                    $where[] = "phan_loai_don = 'B' AND (dun_xac_nhan = 'Chưa xác định' OR cuon_xac_nhan = 'Chưa xác định' OR dun_xac_nhan = '' OR cuon_xac_nhan = '')";
                    break;
                case 'in_production':
                    // Đang sản xuất: Đang thực hiện và còn thiếu > 0
                    $where[] = "tinh_trang_nhap_kho = 'Đang thực hiện' AND con_thieu > 0";
                    break;
                case 'completed_pending_a':
                    // Hoàn thành nhưng vẫn là Đơn B, chờ PC duyệt chuyển sang A
                    $where[] = "phan_loai_don = 'B' AND (con_thieu <= 0 OR tinh_trang_nhap_kho = 'Hoàn thành')";
                    break;
                case 'converted_a':
                    // Đã chuyển sang Đơn A
                    $where[] = "phan_loai_don = 'A'";
                    break;
                case 'rejected':
                    $where[] = "(dun_xac_nhan = 'Từ chối' OR cuon_xac_nhan = 'Từ chối' OR tinh_trang_nhap_kho = 'Từ chối')";
                    break;
            }

            if ($dunFilter !== '') {
                $where[]  = "dun_xac_nhan = ?";
                $params[] = $dunFilter;
                $types   .= "s";
            }
            if ($cuonFilter !== '') {
                $where[]  = "cuon_xac_nhan = ?";
                $params[] = $cuonFilter;
                $types   .= "s";
            }

            $whereSql = implode(' AND ', $where);

            // Đếm tổng bản ghi
            $countSql = "SELECT COUNT(*) AS total FROM don_b WHERE {$whereSql}";
            $stmtCount = $conn->prepare($countSql);
            if (!empty($params)) {
                $stmtCount->bind_param($types, ...$params);
            }
            $stmtCount->execute();
            $totalRows = (int)$stmtCount->get_result()->fetch_assoc()['total'];
            $stmtCount->close();

            // Lấy dữ liệu với phân trang và tính toán cảnh báo 5 ngày
            $selectSql = "
                SELECT 
                    id, ngay_cap_nhat, ma_san_pham, so_met_quy_cach, so_phieu_nhap, ma_don_hang,
                    ngay_nhan_don, ky_han_giao_hang, ngay_yc_nhap_kho, ma_khach_hang, ten_khach_hang,
                    so_luong_dat, tong_met_can, phuong_thuc_van_chuyen, phan_loai_don, ngay_du_kien_xuat,
                    ngay_xuat_thuc_te, pc_note, dun_xac_nhan, cuon_xac_nhan, san_xuat_note,
                    tinh_trang_nhap_kho, ngay_chuyen_b_to_a, cuon_da_sx, tong_met_da_sx, con_thieu,
                    tinh_trang_bobin, ctsx_status, last_action_group, last_action_user,
                    CASE 
                        WHEN ngay_yc_nhap_kho IS NOT NULL THEN DATEDIFF(ngay_yc_nhap_kho, CURDATE()) 
                        ELSE NULL 
                    END AS days_to_due
                FROM don_b 
                WHERE {$whereSql}
                ORDER BY 
                    CASE 
                        WHEN ngay_yc_nhap_kho IS NOT NULL AND DATEDIFF(ngay_yc_nhap_kho, CURDATE()) <= 5 AND (cuon_da_sx = 0 OR cuon_da_sx IS NULL) AND tinh_trang_nhap_kho != 'Hoàn thành' THEN 0 
                        ELSE 1 
                    END ASC,
                    ngay_cap_nhat DESC, id DESC
                LIMIT ? OFFSET ?
            ";

            $stmtData = $conn->prepare($selectSql);
            $bindParams = $params;
            $bindParams[] = $limit;
            $bindParams[] = $offset;
            $bindTypes = $types . "ii";
            $stmtData->bind_param($bindTypes, ...$bindParams);
            $stmtData->execute();
            $result = $stmtData->get_result();

            $items = [];
            while ($row = $result->fetch_assoc()) {
                $qty    = (int)$row['so_luong_dat'];
                $spec   = (int)$row['so_met_quy_cach'];
                $actual = (int)$row['cuon_da_sx'];
                $row['tong_met_can']   = $spec * $qty;
                $row['tong_met_da_sx'] = $spec * $actual;
                
                if ($row['tinh_trang_nhap_kho'] === 'Hoàn thành') {
                    $row['con_thieu'] = 0;
                } else {
                    $row['con_thieu'] = max(0, $qty - $actual);
                }

                $days = $row['days_to_due'] !== null ? (int)$row['days_to_due'] : null;
                $isAlert5Days = ($days !== null && $days <= 5 && $actual <= 0 && $row['tinh_trang_nhap_kho'] !== 'Hoàn thành');
                $row['is_alert_5days'] = $isAlert5Days;
                $row['alert_badge_class'] = '';
                if ($isAlert5Days) {
                    $row['alert_badge_class'] = ($days < 0) ? 'danger-overdue' : 'danger-urgent';
                }

                $row['can_convert_to_a'] = ($row['phan_loai_don'] === 'B' && ($row['con_thieu'] <= 0 || $row['tinh_trang_nhap_kho'] === 'Hoàn thành'));

                $items[] = $row;
            }
            $stmtData->close();

            echo json_encode([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total_rows' => $totalRows,
                    'total_pages' => ceil($totalRows / $limit)
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        // ==========================================
        // 2. THỐNG KÊ TỔNG QUAN (KPI STATS)
        // ==========================================
        case 'stats': {
            requireApiPermission(['orders.view', 'api.orders.get', 'admin']);

            $statsSql = "
                SELECT 
                    COUNT(*) AS total_all,
                    SUM(CASE WHEN phan_loai_don = 'B' THEN 1 ELSE 0 END) AS total_b,
                    SUM(CASE WHEN phan_loai_don = 'A' THEN 1 ELSE 0 END) AS total_a,
                    SUM(CASE WHEN ngay_yc_nhap_kho IS NOT NULL AND DATEDIFF(ngay_yc_nhap_kho, CURDATE()) <= 5 AND (cuon_da_sx = 0 OR cuon_da_sx IS NULL) AND tinh_trang_nhap_kho != 'Hoàn thành' THEN 1 ELSE 0 END) AS alert_5days_count,
                    SUM(CASE WHEN phan_loai_don = 'B' AND (dun_xac_nhan = 'Chưa xác định' OR dun_xac_nhan = '') THEN 1 ELSE 0 END) AS dun_pending_count,
                    SUM(CASE WHEN phan_loai_don = 'B' AND (cuon_xac_nhan = 'Chưa xác định' OR cuon_xac_nhan = '') THEN 1 ELSE 0 END) AS cuon_pending_count,
                    SUM(CASE WHEN tinh_trang_nhap_kho = 'Đang thực hiện' AND con_thieu > 0 THEN 1 ELSE 0 END) AS in_production_count,
                    SUM(CASE WHEN phan_loai_don = 'B' AND (con_thieu <= 0 OR tinh_trang_nhap_kho = 'Hoàn thành') THEN 1 ELSE 0 END) AS completed_pending_a_count,
                    SUM(CASE WHEN dun_xac_nhan = 'Từ chối' OR cuon_xac_nhan = 'Từ chối' OR tinh_trang_nhap_kho = 'Từ chối' THEN 1 ELSE 0 END) AS rejected_count,
                    COALESCE(SUM(CASE WHEN phan_loai_don = 'B' THEN tong_met_can ELSE 0 END), 0) AS total_met_can_b,
                    COALESCE(SUM(CASE WHEN phan_loai_don = 'B' THEN tong_met_da_sx ELSE 0 END), 0) AS total_met_da_sx_b
                FROM don_b
            ";
            $res = $conn->query($statsSql);
            $stats = $res ? $res->fetch_assoc() : [];

            echo json_encode([
                'success' => true,
                'stats' => $stats
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        // ==========================================
        // 3. CHI TIẾT ĐƠN HÀNG VÀ LỊCH SỬ THAO TÁC
        // ==========================================
        case 'get': {
            requireApiPermission(['orders.view', 'api.orders.get', 'admin']);

            $id   = (int)($_GET['id'] ?? 0);
            $code = trim($_GET['code'] ?? '');

            if ($id > 0) {
                $stmt = $conn->prepare("SELECT * FROM don_b WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $id);
            } else {
                $stmt = $conn->prepare("SELECT * FROM don_b WHERE ma_don_hang = ? LIMIT 1");
                $stmt->bind_param("s", $code);
            }
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Lấy lịch sử
            $history = [];
            $stmtHist = $conn->prepare("SELECT * FROM don_b_history WHERE don_b_id = ? ORDER BY id DESC LIMIT 50");
            $orderId = (int)$order['id'];
            $stmtHist->bind_param("i", $orderId);
            $stmtHist->execute();
            $resHist = $stmtHist->get_result();
            while ($h = $resHist->fetch_assoc()) {
                $history[] = $h;
            }
            $stmtHist->close();

            echo json_encode([
                'success' => true,
                'data' => $order,
                'history' => $history
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        // ==========================================
        // 4. TẠO / CẬP NHẬT ĐƠN THỦ CÔNG (PC / ADMIN)
        // ==========================================
        case 'save_manual': {
            requireApiPermission(['orders.create', 'orders.edit', 'api.orders.save', 'admin']);

            $id             = (int)($_POST['id'] ?? 0);
            $maSanPham      = trim($_POST['ma_san_pham'] ?? '');
            $soPhieuNhap    = trim($_POST['so_phieu_nhap'] ?? '');
            $maDonHang      = trim($_POST['ma_don_hang'] ?? '');
            $ngayNhanDon    = parseOrderDate($_POST['ngay_nhan_don'] ?? null);
            $kyHanGiaoHang  = parseOrderDate($_POST['ky_han_giao_hang'] ?? null);
            $ngayYcNhapKho  = parseOrderDate($_POST['ngay_yc_nhap_kho'] ?? null);
            $maKhachHang    = trim($_POST['ma_khach_hang'] ?? '');
            $tenKhachHang   = trim($_POST['ten_khach_hang'] ?? '');
            $soLuongDat     = max(1, (int)($_POST['so_luong_dat'] ?? 0));
            $phuongThuc     = trim($_POST['phuong_thuc_van_chuyen'] ?? 'SEA');
            $phanLoai       = strtoupper(trim($_POST['phan_loai_don'] ?? 'B'));
            if ($phanLoai !== 'A' && $phanLoai !== 'B') $phanLoai = 'B';
            $ngayDuKienXuat = parseOrderDate($_POST['ngay_du_kien_xuat'] ?? null);
            $pcNote         = trim($_POST['pc_note'] ?? '');
            $customMetQuyCach = (int)($_POST['so_met_quy_cach'] ?? 0);

            if ($maDonHang === '') {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Mã đơn hàng (オーダー).'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($maSanPham === '') {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Mã sản phẩm (品番).'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $soMetQuyCach = $customMetQuyCach > 0 ? $customMetQuyCach : extractMetersFromCode($maSanPham);
            $tongMetCan   = $soMetQuyCach * $soLuongDat;

            // Kiểm tra trùng theo ma_don_hang
            $stmtCheck = $conn->prepare("SELECT id, cuon_da_sx, tinh_trang_nhap_kho, phan_loai_don FROM don_b WHERE ma_don_hang = ? LIMIT 1");
            $stmtCheck->bind_param("s", $maDonHang);
            $stmtCheck->execute();
            $existing = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();

            $isInsert = false;
            $donBId = 0;

            if ($existing) {
                // Đã tồn tại: Cập nhật thông tin mới (không tạo bản ghi trùng)
                $donBId = (int)$existing['id'];
                $cuonDaSx = (int)$existing['cuon_da_sx'];
                $tinhTrangNhapKho = $existing['tinh_trang_nhap_kho'];
                $tongMetDaSx = $soMetQuyCach * $cuonDaSx;
                $conThieu = max(0, $soLuongDat - $cuonDaSx);
                if ($conThieu <= 0) {
                    $tinhTrangNhapKho = 'Hoàn thành';
                }

                $stmtUpdate = $conn->prepare("
                    UPDATE don_b SET 
                        ngay_cap_nhat = NOW(),
                        ma_san_pham = ?,
                        so_met_quy_cach = ?,
                        so_phieu_nhap = ?,
                        ngay_nhan_don = ?,
                        ky_han_giao_hang = ?,
                        ngay_yc_nhap_kho = ?,
                        ma_khach_hang = ?,
                        ten_khach_hang = ?,
                        so_luong_dat = ?,
                        tong_met_can = ?,
                        phuong_thuc_van_chuyen = ?,
                        phan_loai_don = ?,
                        ngay_du_kien_xuat = ?,
                        pc_note = ?,
                        tong_met_da_sx = ?,
                        con_thieu = ?,
                        tinh_trang_nhap_kho = ?,
                        last_action_group = 'PC',
                        last_action_user = ?
                    WHERE id = ?
                ");
                $stmtUpdate->bind_param(
                    "sissssssiissssiissi",
                    $maSanPham, $soMetQuyCach, $soPhieuNhap, $ngayNhanDon, $kyHanGiaoHang,
                    $ngayYcNhapKho, $maKhachHang, $tenKhachHang, $soLuongDat, $tongMetCan,
                    $phuongThuc, $phanLoai, $ngayDuKienXuat, $pcNote, $tongMetDaSx,
                    $conThieu, $tinhTrangNhapKho, $userName, $donBId
                );
                $stmtUpdate->execute();
                $stmtUpdate->close();

                logDonBAction($conn, $donBId, $maDonHang, 'PC', $userName, 'Cập nhật thông tin đơn hàng', "PC cập nhật thông tin đơn hàng: $maDonHang (Loại: $phanLoai, SL đặt: $soLuongDat)");
                $msg = "Đã cập nhật đơn hàng thành công (Mã: $maDonHang).";
            } else {
                // Tạo mới
                $isInsert = true;
                $cuonDaSx = 0;
                $tongMetDaSx = 0;
                $conThieu = $soLuongDat;
                $tinhTrangNhapKho = 'Đang thực hiện';
                $dunXacNhan = 'Chưa xác định';
                $cuonXacNhan = 'Chưa xác định';

                $stmtInsert = $conn->prepare("
                    INSERT INTO don_b (
                        ngay_cap_nhat, ma_san_pham, so_met_quy_cach, so_phieu_nhap, ma_don_hang,
                        ngay_nhan_don, ky_han_giao_hang, ngay_yc_nhap_kho, ma_khach_hang, ten_khach_hang,
                        so_luong_dat, tong_met_can, phuong_thuc_van_chuyen, phan_loai_don, ngay_du_kien_xuat,
                        pc_note, dun_xac_nhan, cuon_xac_nhan, tinh_trang_nhap_kho, cuon_da_sx,
                        tong_met_da_sx, con_thieu, last_action_group, last_action_user
                    ) VALUES (
                        NOW(), ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, 'PC', ?
                    )
                ");
                $stmtInsert->bind_param(
                    "sisssssssiisssssssiiis",
                    $maSanPham, $soMetQuyCach, $soPhieuNhap, $maDonHang,
                    $ngayNhanDon, $kyHanGiaoHang, $ngayYcNhapKho, $maKhachHang, $tenKhachHang,
                    $soLuongDat, $tongMetCan, $phuongThuc, $phanLoai, $ngayDuKienXuat,
                    $pcNote, $dunXacNhan, $cuonXacNhan, $tinhTrangNhapKho, $cuonDaSx,
                    $tongMetDaSx, $conThieu, $userName
                );
                $stmtInsert->execute();
                $donBId = (int)$conn->insert_id;
                $stmtInsert->close();

                logDonBAction($conn, $donBId, $maDonHang, 'PC', $userName, 'Khởi tạo đơn hàng mới', "PC khởi tạo đơn hàng mới: $maDonHang (Loại: $phanLoai, SP: $maSanPham, SL: $soLuongDat cuộn)");

                if ($phanLoai === 'B') {
                    $notifyTitle = "Đơn B mới cần xác nhận: $maDonHang";
                    $notifyContent = "PC vừa khởi tạo Đơn B ($maSanPham - $soLuongDat cuộn). Hạn nhập kho: " . ($ngayYcNhapKho ?: 'Chưa xác định') . ". Vui lòng kiểm tra và xác nhận.";
                    sendDonBNotification($conn, $donBId, $maDonHang, 'DUN', $notifyTitle, $notifyContent);
                    sendDonBNotification($conn, $donBId, $maDonHang, 'CUON', $notifyTitle, $notifyContent);
                }

                $msg = "Đã khởi tạo đơn hàng mới thành công (Mã: $maDonHang).";
            }

            echo json_encode([
                'success' => true,
                'message' => $msg,
                'don_b_id' => $donBId,
                'is_insert' => $isInsert
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        // ==========================================
        // 5. XÁC NHẬN BỘ PHẬN ĐÙN NHỰA
        // ==========================================
        case 'dun_confirm': {
            requireApiPermission(['orders.dun_confirm', 'api.orders.dun_confirm', 'admin']);

            $id             = (int)($_POST['id'] ?? 0);
            $status         = trim($_POST['status'] ?? 'OK');
            $ngayDuKienXuat = parseOrderDate($_POST['ngay_du_kien_xuat'] ?? null);
            $note           = trim($_POST['san_xuat_note'] ?? '');

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mã ID không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmtGet = $conn->prepare("SELECT ma_don_hang, tinh_trang_nhap_kho, san_xuat_note FROM don_b WHERE id = ? LIMIT 1");
            $stmtGet->bind_param("i", $id);
            $stmtGet->execute();
            $order = $stmtGet->get_result()->fetch_assoc();
            $stmtGet->close();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $tinhTrang = $order['tinh_trang_nhap_kho'];
            if ($status === 'Từ chối') {
                $tinhTrang = 'Từ chối';
            } elseif ($status === 'OK' && $tinhTrang === 'Từ chối') {
                $tinhTrang = 'Đang thực hiện';
            }

            $newNote = $order['san_xuat_note'] ?? '';
            if ($note !== '') {
                $prefix = "[Đùn " . date('d/m') . "]: ";
                $newNote = trim($newNote . "\n" . $prefix . $note);
            }

            $stmtUpdate = $conn->prepare("
                UPDATE don_b SET 
                    dun_xac_nhan = ?,
                    tinh_trang_nhap_kho = ?,
                    san_xuat_note = ?,
                    ngay_du_kien_xuat = COALESCE(?, ngay_du_kien_xuat),
                    ngay_cap_nhat = NOW(),
                    last_action_group = 'Đùn',
                    last_action_user = ?
                WHERE id = ?
            ");
            $stmtUpdate->bind_param("sssssi", $status, $tinhTrang, $newNote, $ngayDuKienXuat, $userName, $id);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            $logText = "Bộ phận Đùn xác nhận: " . ($status === 'OK' ? 'Xác nhận (OK)' : $status);
            if ($ngayDuKienXuat) $logText .= " | Ngày dự kiến: $ngayDuKienXuat";
            if ($note) $logText .= " | Ghi chú: $note";
            logDonBAction($conn, $id, $order['ma_don_hang'], 'Đùn', $userName, 'Đùn xác nhận', $logText);

            if ($status === 'Từ chối') {
                sendDonBNotification($conn, $id, $order['ma_don_hang'], 'PC', "Đùn TỪ CHỐI đơn hàng: {$order['ma_don_hang']}", "Bộ phận Đùn đã từ chối đơn hàng {$order['ma_don_hang']}. Lý do: $note");
            }

            echo json_encode([
                'success' => true,
                'message' => "Đã cập nhật xác nhận Đùn thành công: $status"
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        // ==========================================
        // 6. CẬP NHẬT THỰC TÍCH BỘ PHẬN CUỘN NHỰA
        // ==========================================
        case 'cuon_update': {
            requireApiPermission(['orders.cuon_confirm', 'api.orders.cuon_update', 'admin']);

            $id             = (int)($_POST['id'] ?? 0);
            $cuonDaSx       = max(0, (int)($_POST['cuon_da_sx'] ?? 0));
            $cuonConfirm    = trim($_POST['cuon_xac_nhan'] ?? '');
            $tinhTrangBobin = trim($_POST['tinh_trang_bobin'] ?? '');
            $note           = trim($_POST['san_xuat_note'] ?? '');

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mã ID không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmtGet = $conn->prepare("SELECT ma_don_hang, so_luong_dat, so_met_quy_cach, cuon_xac_nhan, san_xuat_note, tinh_trang_nhap_kho, phan_loai_don FROM don_b WHERE id = ? LIMIT 1");
            $stmtGet->bind_param("i", $id);
            $stmtGet->execute();
            $order = $stmtGet->get_result()->fetch_assoc();
            $stmtGet->close();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $soLuongDat = (int)$order['so_luong_dat'];
            $soMetQuyCach = (int)$order['so_met_quy_cach'];
            $tongMetDaSx = $soMetQuyCach * $cuonDaSx;
            $conThieu = max(0, $soLuongDat - $cuonDaSx);

            $statusConfirm = $cuonConfirm !== '' ? $cuonConfirm : ($order['cuon_xac_nhan'] ?: 'OK');
            $tinhTrangNhapKho = $order['tinh_trang_nhap_kho'];

            if ($statusConfirm === 'Từ chối') {
                $tinhTrangNhapKho = 'Từ chối';
            } elseif ($conThieu <= 0) {
                $tinhTrangNhapKho = 'Hoàn thành';
            } else {
                if ($tinhTrangNhapKho === 'Hoàn thành' || $tinhTrangNhapKho === 'Từ chối') {
                    $tinhTrangNhapKho = 'Đang thực hiện';
                }
            }

            $newNote = $order['san_xuat_note'] ?? '';
            if ($note !== '') {
                $prefix = "[Cuộn " . date('d/m') . "]: ";
                $newNote = trim($newNote . "\n" . $prefix . $note);
            }

            $stmtUpdate = $conn->prepare("
                UPDATE don_b SET 
                    cuon_xac_nhan = ?,
                    cuon_da_sx = ?,
                    tong_met_da_sx = ?,
                    con_thieu = ?,
                    tinh_trang_nhap_kho = ?,
                    tinh_trang_bobin = COALESCE(NULLIF(?, ''), tinh_trang_bobin),
                    san_xuat_note = ?,
                    ngay_cap_nhat = NOW(),
                    last_action_group = 'Cuộn',
                    last_action_user = ?
                WHERE id = ?
            ");
            $stmtUpdate->bind_param(
                "siiissssi",
                $statusConfirm, $cuonDaSx, $tongMetDaSx, $conThieu, $tinhTrangNhapKho,
                $tinhTrangBobin, $newNote, $userName, $id
            );
            $stmtUpdate->execute();
            $stmtUpdate->close();

            $logText = "Bộ phận Cuộn cập nhật thực tích: $cuonDaSx cuộn (Thiếu: $conThieu cuộn, Tổng mét SX: $tongMetDaSx m) | Tình trạng: $tinhTrangNhapKho";
            if ($tinhTrangBobin) $logText .= " | Bobin: $tinhTrangBobin";
            logDonBAction($conn, $id, $order['ma_don_hang'], 'Cuộn', $userName, 'Cập nhật thực tích', $logText);

            if ($conThieu <= 0 && $order['phan_loai_don'] === 'B') {
                sendDonBNotification(
                    $conn, $id, $order['ma_don_hang'], 'PC',
                    "Đơn B ĐÃ HOÀN THÀNH: {$order['ma_don_hang']}",
                    "Bộ phận Cuộn đã sản xuất đủ thực tích ($cuonDaSx / $soLuongDat cuộn). Chờ PC duyệt chuyển Đơn B sang Đơn A."
                );
            }

            echo json_encode([
                'success' => true,
                'message' => "Đã cập nhật thực tích thành công ($cuonDaSx cuộn). Còn thiếu: $conThieu cuộn.",
                'con_thieu' => $conThieu,
                'tinh_trang_nhap_kho' => $tinhTrangNhapKho,
                'is_completed' => ($conThieu <= 0)
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        // ==========================================
        // 7. XÉT DUYỆT CHUYỂN B SANG A (PC / ADMIN)
        // ==========================================
        case 'approve_b_to_a': {
            requireApiPermission(['orders.approve_a', 'api.orders.approve_a', 'admin']);

            $id         = (int)($_POST['id'] ?? 0);
            $ngayChuyen = parseOrderDate($_POST['ngay_chuyen_b_to_a'] ?? date('Y-m-d'));
            if (!$ngayChuyen) $ngayChuyen = date('Y-m-d');
            $note       = trim($_POST['pc_note'] ?? '');

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mã ID không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmtGet = $conn->prepare("SELECT ma_don_hang, phan_loai_don, con_thieu, pc_note FROM don_b WHERE id = ? LIMIT 1");
            $stmtGet->bind_param("i", $id);
            $stmtGet->execute();
            $order = $stmtGet->get_result()->fetch_assoc();
            $stmtGet->close();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $newPcNote = $order['pc_note'] ?? '';
            $statusTag = "Đã chuyển B => A ($ngayChuyen)";
            if (strpos($newPcNote, 'Đã chuyển B => A') === false) {
                $newPcNote = trim($newPcNote . " | " . $statusTag);
            }
            if ($note !== '') {
                $newPcNote = trim($newPcNote . " (" . $note . ")");
            }

            $stmtApprove = $conn->prepare("
                UPDATE don_b SET 
                    phan_loai_don = 'A',
                    ngay_chuyen_b_to_a = ?,
                    pc_note = ?,
                    ngay_cap_nhat = NOW(),
                    last_action_group = 'PC',
                    last_action_user = ?
                WHERE id = ?
            ");
            $stmtApprove->bind_param("sssi", $ngayChuyen, $newPcNote, $userName, $id);
            $stmtApprove->execute();
            $stmtApprove->close();

            logDonBAction($conn, $id, $order['ma_don_hang'], 'PC', $userName, 'Xét duyệt chuyển B => A', "PC xác nhận chuyển Đơn B thành Đơn A vào ngày $ngayChuyen. Ghi chú: $newPcNote");

            sendDonBNotification($conn, $id, $order['ma_don_hang'], 'DUN', "Đơn B => A đã được duyệt: {$order['ma_don_hang']}", "PC đã hoàn tất xét duyệt chuyển Đơn B thành Đơn A cho mã {$order['ma_don_hang']}.");
            sendDonBNotification($conn, $id, $order['ma_don_hang'], 'CUON', "Đơn B => A đã được duyệt: {$order['ma_don_hang']}", "PC đã hoàn tất xét duyệt chuyển Đơn B thành Đơn A cho mã {$order['ma_don_hang']}.");

            echo json_encode([
                'success' => true,
                'message' => "Đã duyệt chuyển Đơn B sang Đơn A thành công (Ngày chuyển: $ngayChuyen)."
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        // ==========================================
        // 8. IMPORT FILE EXCEL (PC / ADMIN)
        // ==========================================
        case 'import_excel': {
            requireApiPermission(['orders.create', 'api.orders.save', 'admin']);

            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file Excel hợp lệ (.xlsx, .xls).'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $tmpFile  = $_FILES['excel_file']['tmp_name'];
            $fileName = $_FILES['excel_file']['name'];
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($ext, ['xlsx', 'xls', 'xlsm'], true)) {
                echo json_encode(['success' => false, 'message' => 'Định dạng file không được hỗ trợ. Vui lòng tải lên file Excel (.xlsx).'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(['ĐƠN B', 'Đơn B']);

            try {
                $spreadsheet = $reader->load($tmpFile);
            } catch (\Throwable $e) {
                try {
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($tmpFile);
                } catch (\Throwable $e2) {
                    echo json_encode(['success' => false, 'message' => 'Lỗi đọc file Excel: ' . $e2->getMessage()], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }

            $sheet = $spreadsheet->getSheetByName('ĐƠN B') ?: ($spreadsheet->getSheetByName('Đơn B') ?: $spreadsheet->getActiveSheet());
            $highestRow = $sheet->getHighestRow();

            $cleanCell = function($cell) {
                if (!$cell) return null;
                $val = $cell->getValue();
                if (is_string($val) && strpos($val, '=') === 0) {
                    $old = $cell->getOldCalculatedValue();
                    if ($old !== null && $old !== '') $val = $old;
                }
                return is_string($val) ? trim($val) : $val;
            };

            $insertedCount = 0;
            $updatedCount  = 0;
            $skippedCount  = 0;
            $newBOrders    = [];

            $headerRowIdx = 1;
            for ($r = 1; $r <= 5; $r++) {
                $cVal = (string)$cleanCell($sheet->getCell("C$r"));
                if (strpos($cVal, '品番') !== false || strpos($cVal, 'Mã sản phẩm') !== false) {
                    $headerRowIdx = $r;
                    break;
                }
            }

            $startRow = $headerRowIdx + 1;
            $rowSample = (string)$cleanCell($sheet->getCell("C$startRow"));
            if ($rowSample === '' || is_numeric($rowSample)) {
                $startRow++;
            }

            $stmtUpsert = $conn->prepare("
                INSERT INTO don_b (
                    ngay_cap_nhat, ma_san_pham, so_met_quy_cach, so_phieu_nhap, ma_don_hang,
                    ngay_nhan_don, ky_han_giao_hang, ngay_yc_nhap_kho, ma_khach_hang, ten_khach_hang,
                    so_luong_dat, tong_met_can, phuong_thuc_van_chuyen, phan_loai_don, ngay_du_kien_xuat,
                    ngay_xuat_thuc_te, pc_note, dun_xac_nhan, cuon_xac_nhan, san_xuat_note,
                    tinh_trang_nhap_kho, ngay_chuyen_b_to_a, cuon_da_sx, tong_met_da_sx, con_thieu,
                    tinh_trang_bobin, ctsx_status, last_action_group, last_action_user
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, 'PC', ?
                )
                ON DUPLICATE KEY UPDATE
                    ngay_cap_nhat = VALUES(ngay_cap_nhat),
                    ma_san_pham = VALUES(ma_san_pham),
                    so_met_quy_cach = VALUES(so_met_quy_cach),
                    so_phieu_nhap = VALUES(so_phieu_nhap),
                    ngay_nhan_don = VALUES(ngay_nhan_don),
                    ky_han_giao_hang = VALUES(ky_han_giao_hang),
                    ngay_yc_nhap_kho = VALUES(ngay_yc_nhap_kho),
                    ma_khach_hang = VALUES(ma_khach_hang),
                    ten_khach_hang = VALUES(ten_khach_hang),
                    so_luong_dat = VALUES(so_luong_dat),
                    tong_met_can = VALUES(tong_met_can),
                    phuong_thuc_van_chuyen = VALUES(phuong_thuc_van_chuyen),
                    phan_loai_don = VALUES(phan_loai_don),
                    ngay_du_kien_xuat = VALUES(ngay_du_kien_xuat),
                    ngay_xuat_thuc_te = VALUES(ngay_xuat_thuc_te),
                    pc_note = VALUES(pc_note),
                    dun_xac_nhan = VALUES(dun_xac_nhan),
                    cuon_xac_nhan = VALUES(cuon_xac_nhan),
                    san_xuat_note = VALUES(san_xuat_note),
                    tinh_trang_nhap_kho = VALUES(tinh_trang_nhap_kho),
                    ngay_chuyen_b_to_a = VALUES(ngay_chuyen_b_to_a),
                    cuon_da_sx = VALUES(cuon_da_sx),
                    tong_met_da_sx = VALUES(tong_met_da_sx),
                    con_thieu = VALUES(con_thieu),
                    tinh_trang_bobin = VALUES(tinh_trang_bobin),
                    ctsx_status = VALUES(ctsx_status),
                    last_action_group = 'PC',
                    last_action_user = VALUES(last_action_user)
            ");

            for ($r = $startRow; $r <= $highestRow; $r++) {
                $maSanPham   = $cleanCell($sheet->getCell("C$r"));
                $soPhieuNhap = $cleanCell($sheet->getCell("D$r"));
                $maDonHang   = $cleanCell($sheet->getCell("E$r"));

                if (!$maSanPham || $maSanPham === '品番') continue;

                if (!$maDonHang || $maDonHang === '#' || $maDonHang === '#N/A') {
                    if ($soPhieuNhap && $soPhieuNhap !== '#' && $soPhieuNhap !== '手配No.') {
                        $maDonHang = 'NO-ORD-' . $soPhieuNhap;
                    } else {
                        $maDonHang = 'ROW-' . $r . '-' . $maSanPham;
                    }
                }

                $ngayCapNhat = parseOrderDate($cleanCell($sheet->getCell("A$r")));
                if (!$ngayCapNhat) $ngayCapNhat = date('Y-m-d H:i:s');
                else $ngayCapNhat .= ' ' . date('H:i:s');

                $ngayNhanDon    = parseOrderDate($cleanCell($sheet->getCell("F$r")));
                $kyHanGiaoHang  = parseOrderDate($cleanCell($sheet->getCell("G$r")));
                $maKhachHang    = $cleanCell($sheet->getCell("H$r"));
                $soLuongDat     = max(0, (int)$cleanCell($sheet->getCell("I$r")));
                $phuongThuc     = $cleanCell($sheet->getCell("J$r")) ?: 'SEA';
                $phanLoai       = strtoupper($cleanCell($sheet->getCell("K$r")) ?: 'B');
                if ($phanLoai !== 'A' && $phanLoai !== 'B') $phanLoai = 'B';

                $ctsxStatus     = (string)$cleanCell($sheet->getCell("P$r"));
                $tenKhachHang   = $cleanCell($sheet->getCell("R$r"));
                $ngayDuKienXuat = parseOrderDate($cleanCell($sheet->getCell("S$r")));
                $ngayXuatThucTe = parseOrderDate($cleanCell($sheet->getCell("T$r")));
                $ngayYcNhapKho  = parseOrderDate($cleanCell($sheet->getCell("U$r")));
                $pcNote         = $cleanCell($sheet->getCell("V$r"));

                $dunXacNhan      = $cleanCell($sheet->getCell("W$r")) ?: 'Chưa xác định';
                $cuonXacNhan     = $cleanCell($sheet->getCell("X$r")) ?: 'Chưa xác định';
                $sanXuatNote     = $cleanCell($sheet->getCell("Y$r"));
                $tinhTrangNhapKho = $cleanCell($sheet->getCell("Z$r")) ?: 'Đang thực hiện';
                $ngayChuyenBToA  = parseOrderDate($cleanCell($sheet->getCell("AA$r")));

                $cuonDaSx    = max(0, (int)$cleanCell($sheet->getCell("AB$r")));
                $conThieuRaw = $cleanCell($sheet->getCell("AC$r"));

                $soMetQuyCach = extractMetersFromCode($maSanPham);
                $tongMetCan   = $soMetQuyCach * $soLuongDat;
                $tongMetDaSx  = $soMetQuyCach * $cuonDaSx;

                if ($tinhTrangNhapKho === 'Hoàn thành') {
                    $conThieu = 0;
                } elseif (is_numeric($conThieuRaw)) {
                    $conThieu = (int)$conThieuRaw;
                } else {
                    $conThieu = max(0, $soLuongDat - $cuonDaSx);
                }

                $tinhTrangBobin = $cleanCell($sheet->getCell("AD$r"));

                $stmtUpsert->bind_param(
                    "ssissssssiissssssssssiisiss",
                    $ngayCapNhat, $maSanPham, $soMetQuyCach, $soPhieuNhap, $maDonHang,
                    $ngayNhanDon, $kyHanGiaoHang, $ngayYcNhapKho, $maKhachHang, $tenKhachHang,
                    $soLuongDat, $tongMetCan, $phuongThuc, $phanLoai, $ngayDuKienXuat,
                    $ngayXuatThucTe, $pcNote, $dunXacNhan, $cuonXacNhan, $sanXuatNote,
                    $tinhTrangNhapKho, $ngayChuyenBToA, $cuonDaSx, $tongMetDaSx, $conThieu,
                    $tinhTrangBobin, $ctsxStatus, $userName
                );

                if ($stmtUpsert->execute()) {
                    if ($stmtUpsert->affected_rows === 1) {
                        $insertedCount++;
                        if ($phanLoai === 'B') {
                            $newBOrders[] = $maDonHang;
                        }
                    } else {
                        $updatedCount++;
                    }
                } else {
                    $skippedCount++;
                }
            }
            $stmtUpsert->close();

            if (!empty($newBOrders)) {
                $sampleList = implode(', ', array_slice($newBOrders, 0, 5));
                if (count($newBOrders) > 5) $sampleList .= "... (+ " . (count($newBOrders) - 5) . " đơn)";
                $notifyTitle = "Import file: " . count($newBOrders) . " Đơn B mới";
                $notifyMsg = "PC vừa upload file Excel với " . count($newBOrders) . " Đơn B mới: $sampleList. Hiện trường vui lòng xác nhận.";
                sendDonBNotification($conn, null, 'BATCH_IMPORT', 'DUN', $notifyTitle, $notifyMsg);
                sendDonBNotification($conn, null, 'BATCH_IMPORT', 'CUON', $notifyTitle, $notifyMsg);
            }

            echo json_encode([
                'success' => true,
                'message' => "Import hoàn tất! Thêm mới: $insertedCount, Cập nhật: $updatedCount, Bỏ qua/Lỗi: $skippedCount.",
                'stats' => [
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'skipped' => $skippedCount,
                    'total_processed' => $insertedCount + $updatedCount + $skippedCount
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        // ==========================================
        // 9. XUẤT EXCEL THEO SHEET ĐƠN B (EXPORT)
        // ==========================================
        case 'export_excel': {
            requireApiPermission(['orders.export', 'orders.view', 'admin']);

            $tab    = trim($_GET['tab'] ?? 'all');
            $search = trim($_GET['search'] ?? '');

            $where = ["1=1"];
            if ($search !== '') {
                $where[] = "(ma_don_hang LIKE '%" . $conn->real_escape_string($search) . "%' OR ma_san_pham LIKE '%" . $conn->real_escape_string($search) . "%')";
            }
            if ($tab === 'don_b_all') $where[] = "phan_loai_don = 'B'";
            elseif ($tab === 'alert_5days') $where[] = "ngay_yc_nhap_kho IS NOT NULL AND DATEDIFF(ngay_yc_nhap_kho, CURDATE()) <= 5 AND (cuon_da_sx = 0 OR cuon_da_sx IS NULL) AND tinh_trang_nhap_kho != 'Hoàn thành'";
            elseif ($tab === 'in_production') $where[] = "tinh_trang_nhap_kho = 'Đang thực hiện' AND con_thieu > 0";
            elseif ($tab === 'completed_pending_a') $where[] = "phan_loai_don = 'B' AND (con_thieu <= 0 OR tinh_trang_nhap_kho = 'Hoàn thành')";
            elseif ($tab === 'converted_a') $where[] = "phan_loai_don = 'A'";

            $sql = "SELECT * FROM don_b WHERE " . implode(' AND ', $where) . " ORDER BY id DESC";
            $res = $conn->query($sql);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('ĐƠN B');

            $headers = [
                'A1' => 'Ngày cập nhật', 'C1' => '品番', 'D1' => '手配No.', 'E1' => 'オーダー',
                'F1' => 'Ngày nhận đơn', 'G1' => 'Kỳ hạn đơn hàng', 'H1' => '客先コード', 'I1' => '受注数',
                'J1' => '発送方法', 'K1' => 'A/B', 'P1' => '+_có CTSX -_ chưa CTSX', 'R1' => '客先',
                'S1' => 'Ngày dự kiến xuất hàng', 'T1' => 'Ngày xuất thực tế', 'U1' => 'Ngày y/c nhập kho',
                'V1' => 'PC note', 'W1' => 'Đùn xác nhận', 'X1' => 'Cuộn xác nhận', 'Y1' => 'Sản xuất note',
                'Z1' => 'Tình trạng nhập kho', 'AA1' => 'Ngày chuyển B=>A', 'AB1' => 'Cuộn đã SX',
                'AC1' => 'Còn thiếu', 'AD1' => 'Tình trạng bobin'
            ];

            foreach ($headers as $cell => $val) {
                $sheet->setCellValue($cell, $val);
                $sheet->getStyle($cell)->getFont()->setBold(true);
            }

            $r = 2;
            while ($row = $res->fetch_assoc()) {
                $sheet->setCellValue("A$r", substr($row['ngay_cap_nhat'], 0, 10));
                $sheet->setCellValue("C$r", $row['ma_san_pham']);
                $sheet->setCellValue("D$r", $row['so_phieu_nhap']);
                $sheet->setCellValue("E$r", $row['ma_don_hang']);
                $sheet->setCellValue("F$r", $row['ngay_nhan_don']);
                $sheet->setCellValue("G$r", $row['ky_han_giao_hang']);
                $sheet->setCellValue("H$r", $row['ma_khach_hang']);
                $sheet->setCellValue("I$r", (int)$row['so_luong_dat']);
                $sheet->setCellValue("J$r", $row['phuong_thuc_van_chuyen']);
                $sheet->setCellValue("K$r", $row['phan_loai_don']);
                $sheet->setCellValue("P$r", $row['ctsx_status']);
                $sheet->setCellValue("R$r", $row['ten_khach_hang']);
                $sheet->setCellValue("S$r", $row['ngay_du_kien_xuat']);
                $sheet->setCellValue("T$r", $row['ngay_xuat_thuc_te']);
                $sheet->setCellValue("U$r", $row['ngay_yc_nhap_kho']);
                $sheet->setCellValue("V$r", $row['pc_note']);
                $sheet->setCellValue("W$r", $row['dun_xac_nhan']);
                $sheet->setCellValue("X$r", $row['cuon_xac_nhan']);
                $sheet->setCellValue("Y$r", $row['san_xuat_note']);
                $sheet->setCellValue("Z$r", $row['tinh_trang_nhap_kho']);
                $sheet->setCellValue("AA$r", $row['ngay_chuyen_b_to_a']);
                $sheet->setCellValue("AB$r", (int)$row['cuon_da_sx']);
                $sheet->setCellValue("AC$r", (int)$row['con_thieu']);
                $sheet->setCellValue("AD$r", $row['tinh_trang_bobin']);
                $r++;
            }

            $exportFileName = "Don_B_Export_" . date('Ymd_His') . ".xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"$exportFileName\"");
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }

        // ==========================================
        // 10. THÔNG BÁO BỘ PHẬN
        // ==========================================
        case 'get_notifications': {
            requireApiPermission(['orders.view', 'api.orders.get', 'admin']);

            $dept = strtoupper(trim($_GET['dept'] ?? 'ALL'));
            $where = ["1=1"];
            if ($dept !== 'ALL' && $dept !== 'ADMIN') {
                $where[] = "(target_dept = '" . $conn->real_escape_string($dept) . "' OR target_dept = 'ALL')";
            }
            $sql = "SELECT * FROM don_b_notifications WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT 20";
            $res = $conn->query($sql);
            $notifs = [];
            while ($r = $res->fetch_assoc()) {
                $notifs[] = $r;
            }
            echo json_encode(['success' => true, 'notifications' => $notifs], JSON_UNESCAPED_UNICODE);
            break;
        }

        // ==========================================
        // 11. XÓA ĐƠN HÀNG (PC / ADMIN)
        // ==========================================
        case 'delete': {
            requireApiPermission(['orders.delete', 'api.orders.delete', 'admin']);

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mã ID không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmtGet = $conn->prepare("SELECT ma_don_hang FROM don_b WHERE id = ? LIMIT 1");
            $stmtGet->bind_param("i", $id);
            $stmtGet->execute();
            $order = $stmtGet->get_result()->fetch_assoc();
            $stmtGet->close();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmtDel = $conn->prepare("DELETE FROM don_b WHERE id = ?");
            $stmtDel->bind_param("i", $id);
            $stmtDel->execute();
            $stmtDel->close();

            logDonBAction($conn, $id, $order['ma_don_hang'], 'PC', $userName, 'Xóa đơn hàng', "PC xóa đơn hàng khỏi hệ thống: {$order['ma_don_hang']}");

            echo json_encode(['success' => true, 'message' => "Đã xóa đơn hàng {$order['ma_don_hang']} thành công."], JSON_UNESCAPED_UNICODE);
            break;
        }

        default:
            echo json_encode(['success' => false, 'message' => "Action không hợp lệ: $action"], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi xử lý API: ' . $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

