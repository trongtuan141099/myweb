
<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';

// --- A. ESP32 GỬI API VỀ SERVER (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type        = $_POST['type'] ?? 'EVENT'; // 'HEARTBEAT' hoặc 'EVENT'
    $device_code = $_POST['device_id'] ?? '';
    $status      = $_POST['status'] ?? '';
    $ip          = $_POST['ip'] ?? '';

    if (!empty($device_code)) {
if ($type === 'HEARTBEAT') {
            // 1. Tìm trạng thái gần nhất trong lịch sử (nếu chưa có lịch sử thì mặc định là 'OFF')
            $last_status = 'OFF';
            $stmt_hist = $conn->prepare("SELECT status FROM device_historys WHERE device_code = ? ORDER BY id DESC LIMIT 1");
            $stmt_hist->bind_param("s", $device_code);
            $stmt_hist->execute();
            $res_hist = $stmt_hist->get_result();
            if ($row_hist = $res_hist->fetch_assoc()) {
                $last_status = $row_hist['status'];
            }
            $stmt_hist->close();

            // 2. Cập nhật last_seen, ip_address. 
            // Nếu thiết bị đang ở trạng thái 'OFFLINE', tự động khôi phục về $last_status và cập nhật ghi chú.
            $stmt = $conn->prepare("UPDATE devices 
                                  SET last_seen = NOW(), 
                                      ip_address = ?, 
                                      status = IF(status = 'OFFLINE', ?, status), 
                                      note = IF(status = 'OFFLINE', 'Đã khôi phục kết nối', note) 
                                  WHERE device_code = ?");
            $stmt->bind_param("sss", $ip, $last_status, $device_code);
            $stmt->execute();
            $stmt->close();

            echo json_encode(["status" => "heartbeat_ok"]);
        } 
        else if ($type === 'EVENT') {
            // Máy thay đổi trạng thái: Cập nhật trạng thái mới & GHI VÀO LỊCH SỬ
            $stmt = $conn->prepare("UPDATE devices SET status = ?, ip_address = ?, last_seen = NOW(), note = 'Kết nối ổn định 1' WHERE device_code = ?");
            $stmt->bind_param("sss", $status, $ip, $device_code);
            $stmt->execute();
            $stmt->close();

            // Lấy tên thiết bị
            $stmt_dev = $conn->prepare("SELECT device_name FROM devices WHERE device_code = ?");
            $stmt_dev->bind_param("s", $device_code);
            $stmt_dev->execute();
            $res_dev = $stmt_dev->get_result();
            $dev = $res_dev->fetch_assoc();
            $dev_name = $dev['device_name'] ?? $device_code;
            $stmt_dev->close();

            // Ghi nhật ký lịch sử
            $note = 'Thay đổi trạng thái';
            $stmt2 = $conn->prepare("INSERT INTO device_historys (device_code, device_name, status, note) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("ssss", $device_code, $dev_name, $status, $note);
            $stmt2->execute();
            $stmt2->close();

            echo json_encode(["status" => "event_logged"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Thiếu mã thiết bị"]);
    }
    exit;
}

// --- B. AJAX TRUY VẤN LẤY DỮ LIỆU GIÁM SÁT (GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    // ÉP CẬP NHẬT TRỰC TIẾP CẢ STATUS LẪN NOTE THÀNH OFFLINE KHI QUÁ 10 GIÂY KHÔNG CÓ HEARTBEAT
    $conn->query("UPDATE devices 
                  SET status='OFFLINE', note='Mất kết nối IoT' 
                  WHERE TIMESTAMPDIFF(SECOND, last_seen, NOW()) > 10");

    if ($action === 'get_devices') {
        // Lấy dữ liệu trực tiếp, lúc này status đã là 'OFFLINE' đồng bộ 100%
        $result = $conn->query("SELECT *, status AS display_status FROM devices ORDER BY id ASC");
        $devices = [];
        while ($row = $result->fetch_assoc()) {
            $devices[] = $row;
        }
        echo json_encode($devices);
        exit;
    }
    
    if ($action === 'save_config') {
        $id   = $_GET['id'] ?? 0;
        $name = $_GET['device_name'] ?? '';
        $code = $_GET['device_code'] ?? '';
        $ip   = $_GET['ip_address'] ?? '';

        $stmt = $conn->prepare("UPDATE devices SET device_name=?, device_code=?, ip_address=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $code, $ip, $id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Lỗi lưu cấu hình"]);
        }
        exit;
    }
}


// Cấp API trả về danh sách lịch sử có bộ lọc
    if ($action === 'get_history') {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';

        $sql = "SELECT * FROM device_historys WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $sql .= " AND (device_name LIKE ? OR device_code LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "ss";
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= "s";
        }

        $sql .= " ORDER BY id DESC LIMIT 200";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        echo json_encode($history);
        exit;
    }
?>