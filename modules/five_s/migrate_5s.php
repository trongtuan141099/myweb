<?php
require_once __DIR__ . '/../../config/db.php';
global $conn;

echo "Running Phase 2 Migration for 5S Module...\n";

// 1. Update five_s_standards with zone_id and note
$stdCols = [
    "zone_id" => "ALTER TABLE five_s_standards ADD COLUMN IF NOT EXISTS zone_id INT(11) DEFAULT NULL AFTER id",
    "note" => "ALTER TABLE five_s_standards ADD COLUMN IF NOT EXISTS note TEXT DEFAULT NULL AFTER evaluation_criteria"
];
foreach ($stdCols as $c => $sql) {
    if (!$conn->query($sql)) {
        echo "Error adding $c to five_s_standards: " . $conn->error . "\n";
    } else {
        echo " - Column five_s_standards.$c is ready.\n";
    }
}

// 2. Create five_s_plans table
$createPlans = "CREATE TABLE IF NOT EXISTS five_s_plans (
    id INT(11) NOT NULL AUTO_INCREMENT,
    plan_name VARCHAR(150) NOT NULL,
    frequency ENUM('daily','weekly','monthly','ad_hoc') DEFAULT 'daily',
    zone_ids_json TEXT NOT NULL,
    inspectors_json TEXT DEFAULT NULL,
    assignees_json TEXT DEFAULT NULL,
    approvers_json TEXT DEFAULT NULL,
    status ENUM('active','paused') DEFAULT 'active',
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    weekly_days_json VARCHAR(50) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT(11) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($createPlans)) {
    echo "Error creating five_s_plans: " . $conn->error . "\n";
} else {
    echo " - Table five_s_plans is ready.\n";
}

// 3. For any zone that has no standards in five_s_standards, seed initial standards from zone's checklist_items or defaults
$zonesRes = $conn->query("SELECT id, zone_name, checklist_items FROM five_s_zones");
if ($zonesRes) {
    while ($z = $zonesRes->fetch_assoc()) {
        $zId = (int)$z['id'];
        $chk = $conn->query("SELECT COUNT(*) AS c FROM five_s_standards WHERE zone_id = $zId")->fetch_assoc()['c'] ?? 0;
        if ($chk == 0) {
            // Check if zone has checklist_items
            $items = [];
            if (!empty($z['checklist_items'])) {
                $items = json_decode($z['checklist_items'], true);
            }
            if (empty($items)) {
                $items = [
                    ['id' => 'S1', 'category' => 'S1 - Sàng lọc', 'name' => 'Sàng lọc đồ dùng, công cụ', 'standard' => 'Loại bỏ đồ không cần thiết, phế liệu tại vị trí làm việc'],
                    ['id' => 'S2', 'category' => 'S2 - Sắp xếp', 'name' => 'Sắp xếp định vị công cụ', 'standard' => 'Đặt đúng vạch sơn, tem nhãn rõ ràng, dễ lấy'],
                    ['id' => 'S3', 'category' => 'S3 - Sạch sẽ', 'name' => 'Vệ sinh sàn và thiết bị', 'standard' => 'Sàn xưởng, máy móc sạch không có bụi bẩn, dầu nhớt đổ'],
                    ['id' => 'S4', 'category' => 'S4 - Săn sóc', 'name' => 'Duy trì bảng trực quan', 'standard' => 'Duy trì chuẩn 3S liên tục, bảng hướng dẫn cập nhật'],
                    ['id' => 'S5', 'category' => 'S5 - Sẵn sàng', 'name' => 'Kỷ luật và trang phục BHLĐ', 'standard' => 'Nhân viên mặc đủ BHLĐ, tự giác chấp hành nội quy']
                ];
            }

            $stmtIns = $conn->prepare("INSERT INTO five_s_standards (zone_id, group_name, standard_code, item_name, description, evaluation_criteria, severity_level) VALUES (?, ?, ?, ?, ?, ?, 'medium')");
            foreach ($items as $idx => $it) {
                $g = $it['category'] ?? 'S1 - Sàng lọc';
                $code = 'STD-' . ($it['id'] ?? ('S' . ($idx + 1)));
                $name = $it['name'] ?? 'Tiêu chuẩn kiểm tra';
                $stdDesc = $it['standard'] ?? $name;
                $stmtIns->bind_param("isssss", $zId, $g, $code, $name, $stdDesc, $stdDesc);
                $stmtIns->execute();
            }
            echo " - Seeded " . count($items) . " standards for zone [{$z['id']} - {$z['zone_name']}].\n";
        }
    }
}

// 4. Seed initial sample plan if five_s_plans is empty
$planCount = $conn->query("SELECT COUNT(*) AS c FROM five_s_plans")->fetch_assoc()['c'] ?? 0;
if ($planCount == 0) {
    $firstZoneId = $conn->query("SELECT id FROM five_s_zones LIMIT 1")->fetch_assoc()['id'] ?? 1;
    $today = date('Y-m-d');
    $stmtP = $conn->prepare("INSERT INTO five_s_plans (plan_name, frequency, zone_ids_json, inspectors_json, assignees_json, approvers_json, status, start_date, notes) VALUES (?, 'daily', ?, ?, ?, ?, 'active', ?, ?)");
    $pName = "Kế hoạch tuần tra 5S định kỳ tháng " . date('m/Y');
    $zJson = json_encode([(int)$firstZoneId]);
    $uJson = json_encode([1]);
    $notes = "Kế hoạch tự động kiểm tra hiện trường hàng ngày";
    $stmtP->bind_param("sssssss", $pName, $zJson, $uJson, $uJson, $uJson, $today, $notes);
    $stmtP->execute();
    echo " - Seeded sample active plan.\n";
}

echo "Phase 2 Migration finished successfully!\n";
