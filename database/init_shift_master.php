<?php
/**
 * Database Migration: Khởi tạo bảng Shift_Master (Cấu hình ca làm việc & nhận diện OT)
 * DX Plastic Group - Overtime Management System
 */

require_once __DIR__ . '/../config/db.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
    }
}

echo "=== MIGRATION: KHỞI TẠO BẢNG SHIFT_MASTER ===\n";

$sqlCreate = "
CREATE TABLE IF NOT EXISTS shift_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Mã ca: 1, 2, 3, HC',
    shift_name VARCHAR(100) NOT NULL COMMENT 'Tên ca làm việc',
    standard_start_time TIME NOT NULL COMMENT 'Giờ bắt đầu tiêu chuẩn',
    standard_end_time TIME NOT NULL COMMENT 'Giờ kết thúc tiêu chuẩn',
    ot_before_hours DECIMAL(4,2) NOT NULL DEFAULT 2.00 COMMENT 'Số giờ OT tối đa trước ca',
    ot_after_hours DECIMAL(4,2) NOT NULL DEFAULT 2.00 COMMENT 'Số giờ OT tối đa sau ca',
    detect_start_time TIME NOT NULL COMMENT 'Mốc đầu khoảng thời gian nhận diện',
    detect_end_time TIME NOT NULL COMMENT 'Mốc cuối khoảng thời gian nhận diện',
    priority INT NOT NULL DEFAULT 10 COMMENT 'Mức độ ưu tiên khi giải quyết giao thoa',
    active_flag TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Hoạt động, 0: Ngừng',
    is_cross_day TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: Ca qua đêm (VD ca 3)',
    description VARCHAR(255) NULL COMMENT 'Mô tả nghiệp vụ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sqlCreate)) {
    echo "1. Tạo bảng `shift_master` thành công (hoặc bảng đã tồn tại).\n";
} else {
    die("Lỗi tạo bảng `shift_master`: " . $conn->error . "\n");
}

// Cấu hình 4 ca làm việc theo quy định nghiệp vụ
$defaultShifts = [
    [
        'shift_code' => '1',
        'shift_name' => 'Ca 1',
        'standard_start_time' => '06:00:00',
        'standard_end_time' => '14:00:00',
        'ot_before_hours' => 2.00,
        'ot_after_hours' => 2.00,
        'detect_start_time' => '04:00:00',
        'detect_end_time' => '16:00:00',
        'priority' => 10,
        'active_flag' => 1,
        'is_cross_day' => 0,
        'description' => 'Ca sản xuất 1 (Tiêu chuẩn: 06:00~14:00, Khoảng nhận diện: 04:00~16:00)'
    ],
    [
        'shift_code' => '2',
        'shift_name' => 'Ca 2',
        'standard_start_time' => '14:00:00',
        'standard_end_time' => '22:00:00',
        'ot_before_hours' => 2.00,
        'ot_after_hours' => 2.00,
        'detect_start_time' => '12:00:00',
        'detect_end_time' => '24:00:00',
        'priority' => 20,
        'active_flag' => 1,
        'is_cross_day' => 0,
        'description' => 'Ca sản xuất 2 (Tiêu chuẩn: 14:00~22:00, Khoảng nhận diện: 12:00~24:00)'
    ],
    [
        'shift_code' => '3',
        'shift_name' => 'Ca 3',
        'standard_start_time' => '22:00:00',
        'standard_end_time' => '06:00:00',
        'ot_before_hours' => 2.00,
        'ot_after_hours' => 2.00,
        'detect_start_time' => '20:00:00',
        'detect_end_time' => '08:00:00',
        'priority' => 30,
        'active_flag' => 1,
        'is_cross_day' => 1,
        'description' => 'Ca sản xuất 3 qua đêm (Tiêu chuẩn: 22:00~06:00, Khoảng nhận diện: 20:00~08:00)'
    ],
    [
        'shift_code' => 'HC',
        'shift_name' => 'Hành Chính',
        'standard_start_time' => '07:45:00',
        'standard_end_time' => '16:30:00',
        'ot_before_hours' => 2.00,
        'ot_after_hours' => 4.00,
        'detect_start_time' => '05:45:00',
        'detect_end_time' => '20:00:00',
        'priority' => 15,
        'active_flag' => 1,
        'is_cross_day' => 0,
        'description' => 'Ca làm việc Hành Chính (Tiêu chuẩn: 07:45~16:30, Khoảng nhận diện: 05:45~20:00)'
    ]
];

$stmt = $conn->prepare("
    INSERT INTO shift_master (
        shift_code, shift_name, standard_start_time, standard_end_time,
        ot_before_hours, ot_after_hours, detect_start_time, detect_end_time,
        priority, active_flag, is_cross_day, description
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        shift_name = VALUES(shift_name),
        standard_start_time = VALUES(standard_start_time),
        standard_end_time = VALUES(standard_end_time),
        ot_before_hours = VALUES(ot_before_hours),
        ot_after_hours = VALUES(ot_after_hours),
        detect_start_time = VALUES(detect_start_time),
        detect_end_time = VALUES(detect_end_time),
        priority = VALUES(priority),
        active_flag = VALUES(active_flag),
        is_cross_day = VALUES(is_cross_day),
        description = VALUES(description),
        updated_at = CURRENT_TIMESTAMP
");

foreach ($defaultShifts as $s) {
    $stmt->bind_param(
        "ssssddssiiis",
        $s['shift_code'],
        $s['shift_name'],
        $s['standard_start_time'],
        $s['standard_end_time'],
        $s['ot_before_hours'],
        $s['ot_after_hours'],
        $s['detect_start_time'],
        $s['detect_end_time'],
        $s['priority'],
        $s['active_flag'],
        $s['is_cross_day'],
        $s['description']
    );
    $stmt->execute();
    echo " - Seed/Upsert ca [{$s['shift_code']}] {$s['shift_name']} ({$s['detect_start_time']} ~ {$s['detect_end_time']})\n";
}
$stmt->close();

echo "\n2. Kiểm tra dữ liệu trong bảng `shift_master`:\n";
$resCheck = $conn->query("SELECT * FROM shift_master ORDER BY priority ASC");
while ($r = $resCheck->fetch_assoc()) {
    echo "ID: {$r['id']} | Code: {$r['shift_code']} | Name: {$r['shift_name']} | Standard: {$r['standard_start_time']}~{$r['standard_end_time']} | Detect: {$r['detect_start_time']}~{$r['detect_end_time']} | Priority: {$r['priority']} | Active: {$r['active_flag']}\n";
}

echo "=== HOÀN TẤT MIGRATION BẢNG SHIFT_MASTER ===\n";
