<?php
/**
 * Migration Script: Tạo bảng cho Module Quản Lý Chất Lượng (Quality Management)
 * - quality_yield_records (Dữ liệu thành phẩm & tỉ lệ 良品率)
 * - quality_benchmarks (Mốc tiêu chuẩn đánh giá)
 * - quality_investigations (Phiếu yêu cầu điều tra & Đối ứng bất thường)
 */
require_once __DIR__ . '/../config/db.php';

echo "=== MIGRATING QUALITY MODULE TABLES ===\n";

// 1. Bảng Dữ liệu Thành phẩm & Tỉ lệ 良品率
$sql1 = "CREATE TABLE IF NOT EXISTS `quality_yield_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `komaki_date` DATE NOT NULL,
  `komaki_machine` VARCHAR(50) DEFAULT 'ST01',
  `size` VARCHAR(50) NOT NULL,
  `lot_no` VARCHAR(50) NOT NULL,
  `product_code` VARCHAR(100) NOT NULL,
  `extrusion_date` DATE DEFAULT NULL,
  `extrusion_machine` VARCHAR(50) DEFAULT 'PL08',
  `shift` VARCHAR(20) DEFAULT 'Ca 1',
  `material_type` VARCHAR(100) DEFAULT 'Zin',
  `good_qty` INT NOT NULL DEFAULT 0,
  `total_qty` INT NOT NULL DEFAULT 0,
  `yield_rate` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `defect_qty` INT NOT NULL DEFAULT 0,
  `benchmark_rate` DECIMAL(5,2) DEFAULT 98.00,
  `defect_a1` INT DEFAULT 0,
  `defect_a2` INT DEFAULT 0,
  `defect_a3` INT DEFAULT 0,
  `defect_a4` INT DEFAULT 0,
  `defect_a5` INT DEFAULT 0,
  `defect_details` TEXT DEFAULT NULL,
  `bobbin_length` INT DEFAULT 0,
  `note` TEXT DEFAULT NULL,
  `created_by` VARCHAR(50) DEFAULT 'SYSTEM',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_komaki_date` (`komaki_date`),
  INDEX `idx_extrusion_date` (`extrusion_date`),
  INDEX `idx_size` (`size`),
  INDEX `idx_ext_machine` (`extrusion_machine`),
  INDEX `idx_kom_machine` (`komaki_machine`),
  INDEX `idx_lot_no` (`lot_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql1)) {
    echo "  [OK] Table `quality_yield_records` ready.\n";
} else {
    echo "  [FAIL] Table `quality_yield_records`: " . $conn->error . "\n";
}

// 2. Bảng Tiêu chuẩn Benchmark
$sql2 = "CREATE TABLE IF NOT EXISTS `quality_benchmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `size` VARCHAR(50) NOT NULL DEFAULT 'ALL',
  `extrusion_machine` VARCHAR(50) NOT NULL DEFAULT 'ALL',
  `benchmark_rate` DECIMAL(5,2) NOT NULL DEFAULT 98.00,
  `min_acceptable_rate` DECIMAL(5,2) NOT NULL DEFAULT 95.00,
  `description` VARCHAR(255) DEFAULT '',
  `updated_by` VARCHAR(50) DEFAULT 'ADMIN',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_size_machine` (`size`, `extrusion_machine`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql2)) {
    echo "  [OK] Table `quality_benchmarks` ready.\n";
} else {
    echo "  [FAIL] Table `quality_benchmarks`: " . $conn->error . "\n";
}

// 3. Bảng Phiếu Yêu cầu Điều tra & Đối ứng
$sql3 = "CREATE TABLE IF NOT EXISTS `quality_investigations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `investigation_code` VARCHAR(50) NOT NULL UNIQUE,
  `yield_record_id` INT DEFAULT NULL,
  `investigation_date` DATE NOT NULL,
  `size` VARCHAR(50) NOT NULL,
  `product_code` VARCHAR(100) NOT NULL,
  `extrusion_machine` VARCHAR(50) DEFAULT '',
  `extrusion_date` DATE DEFAULT NULL,
  `komaki_date` DATE DEFAULT NULL,
  `lot_no` VARCHAR(50) DEFAULT '',
  `yield_rate` DECIMAL(6,2) DEFAULT 0.00,
  `rate_a1` DECIMAL(5,2) DEFAULT 0.00,
  `rate_a2` DECIMAL(5,2) DEFAULT 0.00,
  `rate_a3` DECIMAL(5,2) DEFAULT 0.00,
  `rate_a4` DECIMAL(5,2) DEFAULT 0.00,
  `rate_a5` DECIMAL(5,2) DEFAULT 0.00,
  `status_description` VARCHAR(255) DEFAULT 'Phát sinh bất thường',
  `root_cause` TEXT DEFAULT NULL,
  `countermeasure` TEXT DEFAULT NULL,
  `assigned_to` VARCHAR(100) DEFAULT '',
  `result_status` VARCHAR(50) DEFAULT 'Chờ điều tra',
  `created_by` VARCHAR(50) DEFAULT 'ADMIN',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_inv_date` (`investigation_date`),
  INDEX `idx_inv_status` (`result_status`),
  INDEX `idx_inv_size` (`size`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql3)) {
    echo "  [OK] Table `quality_investigations` ready.\n";
} else {
    echo "  [FAIL] Table `quality_investigations`: " . $conn->error . "\n";
}

// 4. Khởi tạo benchmark mặc định
$defaultBenchmarks = [
    ['ALL', 'ALL', 98.00, 95.00, 'Tiêu chuẩn toàn xưởng'],
    ['TU0425', 'ALL', 92.00, 88.00, 'Tiêu chuẩn size nhỏ TU0425'],
    ['TU0604', 'ALL', 98.00, 95.00, 'Tiêu chuẩn size TU0604'],
    ['TU0805', 'ALL', 98.00, 95.00, 'Tiêu chuẩn size TU0805'],
    ['TU1065', 'ALL', 96.00, 92.00, 'Tiêu chuẩn size TU1065'],
    ['TIUB07', 'ALL', 95.00, 90.00, 'Tiêu chuẩn size TIUB07'],
    ['ALL', 'PL07', 98.00, 94.00, 'Tiêu chuẩn máy PL07'],
    ['ALL', 'PL14', 96.00, 92.00, 'Tiêu chuẩn máy PL14'],
    ['ALL', 'PL17', 95.00, 90.00, 'Tiêu chuẩn máy PL17']
];

foreach ($defaultBenchmarks as $b) {
    $stmt = $conn->prepare("INSERT INTO `quality_benchmarks` (`size`, `extrusion_machine`, `benchmark_rate`, `min_acceptable_rate`, `description`) 
                            VALUES (?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE `benchmark_rate` = VALUES(`benchmark_rate`), `min_acceptable_rate` = VALUES(`min_acceptable_rate`), `description` = VALUES(`description`)");
    $stmt->bind_param("ssdds", $b[0], $b[1], $b[2], $b[3], $b[4]);
    $stmt->execute();
    $stmt->close();
}
echo "  [OK] Seeded default benchmarks.\n";

echo "=== MIGRATION COMPLETE ===\n";
