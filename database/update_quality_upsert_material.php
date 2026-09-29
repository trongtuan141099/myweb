<?php
/**
 * Migration: Add bobbin_time, upsert_key, material_group, and quality_material_rules table
 */

require_once __DIR__ . '/../config/db.php';
global $conn;

echo "=== MIGRATING QUALITY MODULE FOR UPSERT & MATERIAL RULES ===\n";

// 1. Add columns to quality_yield_records if not exist
$columnsToAdd = [
    'bobbin_time' => "ALTER TABLE `quality_yield_records` ADD COLUMN `bobbin_time` VARCHAR(20) DEFAULT '' AFTER `extrusion_date`",
    'upsert_key' => "ALTER TABLE `quality_yield_records` ADD COLUMN `upsert_key` VARCHAR(150) NOT NULL DEFAULT '' AFTER `id`",
    'material_group' => "ALTER TABLE `quality_yield_records` ADD COLUMN `material_group` ENUM('virgin', 'recycled', 'other') NOT NULL DEFAULT 'virgin' AFTER `material_type`"
];

foreach ($columnsToAdd as $col => $alterSql) {
    $check = $conn->query("SHOW COLUMNS FROM `quality_yield_records` LIKE '{$col}'");
    if ($check && $check->num_rows == 0) {
        if ($conn->query($alterSql)) {
            echo "  [OK] Added column `{$col}` to `quality_yield_records`.\n";
        } else {
            echo "  [FAIL] Add column `{$col}`: " . $conn->error . "\n";
        }
    } else {
        echo "  [INFO] Column `{$col}` already exists.\n";
    }
}

// 2. Create quality_material_rules table
$sqlRules = "CREATE TABLE IF NOT EXISTS `quality_material_rules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `char_code` VARCHAR(5) NOT NULL UNIQUE,
  `material_name` VARCHAR(100) NOT NULL,
  `material_group` ENUM('virgin', 'recycled') NOT NULL DEFAULT 'virgin',
  `description` VARCHAR(255) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_mat_char` (`char_code`),
  INDEX `idx_mat_group` (`material_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sqlRules)) {
    echo "  [OK] Table `quality_material_rules` ready.\n";
} else {
    echo "  [FAIL] Table `quality_material_rules`: " . $conn->error . "\n";
}

// 3. Seed default material classification rules
$defaultRules = [
    ['A', 'Nhựa Zin (Code A)', 'virgin', 'Vật liệu nguyên sinh - Ký tự A'],
    ['D', 'Nhựa Zin (Code D)', 'virgin', 'Vật liệu nguyên sinh - Ký tự D'],
    ['J', 'Nhựa Zin (Code J)', 'virgin', 'Vật liệu nguyên sinh - Ký tự J'],
    ['F', 'Nhựa Zin (Code F)', 'virgin', 'Vật liệu nguyên sinh - Ký tự F'],
    ['I', 'Nhựa Zin (Code I)', 'virgin', 'Vật liệu nguyên sinh - Ký tự I'],
    ['T', 'Nhựa Zin (Code T)', 'virgin', 'Vật liệu nguyên sinh - Ký tự T'],
    ['C', 'Nhựa Nghiền (Code C)', 'recycled', 'Vật liệu tái sinh/nghiền - Ký tự C'],
    ['B', 'Nhựa Nghiền (Code B)', 'recycled', 'Vật liệu tái sinh/nghiền - Ký tự B'],
    ['K', 'Nhựa Nghiền (Code K)', 'recycled', 'Vật liệu tái sinh/nghiền - Ký tự K'],
    ['E', 'Nhựa Nghiền (Code E)', 'recycled', 'Vật liệu tái sinh/nghiền - Ký tự E'],
    ['V', 'Nhựa Nghiền (Code V)', 'recycled', 'Vật liệu tái sinh/nghiền - Ký tự V']
];

$stmtRule = $conn->prepare("INSERT INTO `quality_material_rules` (`char_code`, `material_name`, `material_group`, `description`) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                               `material_name` = VALUES(`material_name`), 
                               `material_group` = VALUES(`material_group`),
                               `description` = VALUES(`description`)");

foreach ($defaultRules as $r) {
    $stmtRule->bind_param("ssss", $r[0], $r[1], $r[2], $r[3]);
    $stmtRule->execute();
}
$stmtRule->close();
echo "  [OK] Seeded default material rules.\n";

// 4. Update index on upsert_key in quality_yield_records
$checkIdx = $conn->query("SHOW INDEX FROM `quality_yield_records` WHERE Key_name = 'uk_upsert_key'");
if ($checkIdx && $checkIdx->num_rows == 0) {
    // Populate upsert_key first for any records that might have empty key
    $conn->query("UPDATE `quality_yield_records` 
                  SET `upsert_key` = CONCAT(COALESCE(`bobbin_time`,'00:00'), '|', COALESCE(`extrusion_date`,'0000-00-00'), '|', COALESCE(`komaki_machine`,''), '|', COALESCE(`komaki_date`,'0000-00-00'), '|', `id`)
                  WHERE `upsert_key` = ''");
    $conn->query("ALTER TABLE `quality_yield_records` ADD UNIQUE KEY `uk_upsert_key` (`upsert_key`)");
    echo "  [OK] Created UNIQUE KEY `uk_upsert_key` on `quality_yield_records`.\n";
} else {
    echo "  [INFO] Index `uk_upsert_key` already exists.\n";
}

echo "=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
