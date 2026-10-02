<?php
/**
 * API Quản Lý Thông Tin Nhân Viên (Thêm, Sửa, Lấy chi tiết, Xóa)
 * DX Plastic Group - Factory Management System
 */
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');

try {
    switch ($action) {
        // =====================================================================
        // 1. LẤY CHI TIẾT 1 NHÂN VIÊN THEO MÃ
        // =====================================================================
        case 'get':
            if (!hasPermission(['hrm.view', 'hrm.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập thông tin nhân viên.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $empCode = trim($_GET['code'] ?? ($_GET['id'] ?? ''));
            if (empty($empCode)) {
                echo json_encode(['success' => false, 'message' => 'Mã nhân viên không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("SELECT employee_code, full_name, gender, job_level, cost_center, work_group, work_shift, hire_date, resignation_date, use_shuttle_bus FROM employees WHERE employee_code = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param("s", $empCode);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Chuẩn hóa ngày nghỉ việc
            if ($row['resignation_date'] === '0000-00-00' || empty($row['resignation_date'])) {
                $row['resignation_date'] = '';
            }

            echo json_encode(['success' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. LƯU THÔNG TIN NHÂN VIÊN (THÊM MỚI HOẶC CHỈNH SỬA)
        // =====================================================================
        case 'save':
            if (!hasPermission(['hrm.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền quản lý thông tin nhân sự.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $isEdit = !empty($_POST['is_edit']) && ($_POST['is_edit'] === '1' || $_POST['is_edit'] === true || $_POST['is_edit'] === 'true');
            $code   = trim($_POST['employee_code'] ?? '');
            $name   = trim($_POST['full_name'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $job    = trim($_POST['job_level'] ?? '');
            $dept   = trim($_POST['cost_center'] ?? '');
            $group  = trim($_POST['work_group'] ?? '');
            $shift  = trim($_POST['work_shift'] ?? 'Ca 1');
            $useShuttleBus = isset($_POST['use_shuttle_bus']) ? intval($_POST['use_shuttle_bus']) : 0;
            $hireDate = !empty($_POST['hire_date']) ? trim($_POST['hire_date']) : NULL;
            $resigDate = !empty($_POST['resignation_date']) ? trim($_POST['resignation_date']) : NULL;

            if (empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Mã nhân viên.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Họ và tên nhân viên.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Chuẩn hóa định dạng ngày
            if ($hireDate === '' || $hireDate === '0000-00-00') $hireDate = NULL;
            if ($resigDate === '' || $resigDate === '0000-00-00') $resigDate = NULL;

            // Kiểm tra nhân viên đã tồn tại hay chưa
            $chkStmt = $conn->prepare("SELECT employee_code FROM employees WHERE employee_code = ? LIMIT 1");
            $chkStmt->bind_param("s", $code);
            $chkStmt->execute();
            $exists = (bool)$chkStmt->get_result()->fetch_assoc();
            $chkStmt->close();

            if ($isEdit || $exists) {
                // UPDATE NHÂN VIÊN
                $sql = "UPDATE employees SET 
                            full_name = ?, 
                            gender = ?, 
                            job_level = ?, 
                            cost_center = ?, 
                            work_group = ?, 
                            work_shift = ?, 
                            hire_date = ?, 
                            resignation_date = ?,
                            use_shuttle_bus = ? 
                        WHERE employee_code = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) throw new Exception($conn->error);
                $stmt->bind_param("ssssssssis", $name, $gender, $job, $dept, $group, $shift, $hireDate, $resigDate, $useShuttleBus, $code);
                $ok = $stmt->execute();
                $stmt->close();

                if ($ok) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Cập nhật thông tin nhân viên thành công!',
                        'employee_code' => $code
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . $conn->error], JSON_UNESCAPED_UNICODE);
                }
            } else {
                // INSERT NHÂN VIÊN MỚI
                $sql = "INSERT INTO employees (employee_code, full_name, gender, job_level, cost_center, work_group, work_shift, hire_date, resignation_date, use_shuttle_bus) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) throw new Exception($conn->error);
                $stmt->bind_param("sssssssssi", $code, $name, $gender, $job, $dept, $group, $shift, $hireDate, $resigDate, $useShuttleBus);
                $ok = $stmt->execute();
                $stmt->close();

                if ($ok) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Thêm mới nhân viên thành công!',
                        'employee_code' => $code
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm mới: ' . $conn->error], JSON_UNESCAPED_UNICODE);
                }
            }
            break;

        // =====================================================================
        // 3. XÓA NHÂN VIÊN
        // =====================================================================
        case 'delete':
            if (!hasPermission(['hrm.manage', 'admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa nhân sự.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $code = trim($_POST['employee_code'] ?? ($_GET['code'] ?? ''));
            if (empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Mã nhân viên không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM employees WHERE employee_code = ?");
            if (!$stmt) throw new Exception($conn->error);
            $stmt->bind_param("s", $code);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Đã xóa nhân viên thành công.'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa: ' . $conn->error], JSON_UNESCAPED_UNICODE);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ.'], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
