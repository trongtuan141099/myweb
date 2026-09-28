<?php
// database/seed_warehouse_data.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

global $conn;

echo "=== SEEDING WAREHOUSE DATA FROM TÍNH TOÁN XUẤT KHO T8.XLSM ===\n";

$files = glob(__DIR__ . '/../data/*xu*t kho*');
if (empty($files)) {
    die("Error: File Tính toán xuất kho T8.xlsm not found in data/!\n");
}
$filePath = $files[0];
$xlsx = SimpleXLSX::parse($filePath);
if (!$xlsx) {
    die("SimpleXLSX Error: " . SimpleXLSX::parseError() . "\n");
}

// 1. Ensure images are mapped
$imagesDir = 'resources/images/warehouse';
@mkdir(__DIR__ . '/../' . $imagesDir, 0777, true);

// 2. Parse sheet Master
$idxMaster = array_search('Master', $xlsx->sheetNames());
$masterInfo = [];
if ($idxMaster !== false) {
    $rowsM = $xlsx->rows($idxMaster);
    for ($r = 2; $r < count($rowsM); $r++) {
        $code = trim($rowsM[$r][2] ?? '');
        if (!$code) continue;
        $typeStr = trim($rowsM[$r][8] ?? '');
        $whType  = trim($rowsM[$r][10] ?? 'NON SAP');
        $cat = (mb_stripos($typeStr, 'tiêu hao') !== false) ? 'consumable' : 'irregular';
        $masterInfo[$code] = [
            'category_type'  => $cat,
            'warehouse_type' => $whType ?: 'NON SAP',
            'notes'          => trim($rowsM[$r][9] ?? '')
        ];
    }
}
echo "Parsed " . count($masterInfo) . " items from Master sheet.\n";

// 3. Parse sheet Picture
$idxPic = array_search('Picture', $xlsx->sheetNames());
$picInfo = [];
if ($idxPic !== false) {
    $rowsP = $xlsx->rows($idxPic);
    for ($r = 6; $r < count($rowsP); $r++) {
        $code = trim($rowsP[$r][2] ?? '');
        if (!$code) continue;
        $desc = trim($rowsP[$r][17] ?? '');
        $packQty = floatval($rowsP[$r][13] ?? 1);
        if ($packQty <= 0) $packQty = 1;
        $picInfo[$code] = [
            'pack_quantity'  => $packQty,
            'packaging_desc' => $desc
        ];
    }
}
echo "Parsed " . count($picInfo) . " items from Picture sheet.\n";

// 4. Parse sheet Wharehouse
$idxWh = array_search('Wharehouse', $xlsx->sheetNames());
$whStock = [];
if ($idxWh !== false) {
    $rowsW = $xlsx->rows($idxWh);
    for ($r = 5; $r < count($rowsW); $r++) {
        $codeSap = trim($rowsW[$r][0] ?? '');
        if ($codeSap && $codeSap !== 'Product ID') {
            $whStock[$codeSap] = [
                'stock' => floatval($rowsW[$r][3] ?? 0),
                'bin'   => trim($rowsW[$r][4] ?? '')
            ];
        }
        $codeNonSap = trim($rowsW[$r][8] ?? '');
        if ($codeNonSap && $codeNonSap !== 'Product ID') {
            $whStock[$codeNonSap] = [
                'stock' => floatval($rowsW[$r][11] ?? 0),
                'bin'   => trim($rowsW[$r][12] ?? '')
            ];
        }
    }
}
echo "Parsed " . count($whStock) . " stock entries from Wharehouse sheet.\n";

// 5. Parse sheet T8 and insert
$idxT8 = array_search('T8', $xlsx->sheetNames());
if ($idxT8 === false) {
    die("Sheet T8 not found!\n");
}
$rowsT8 = $xlsx->rows($idxT8);

$insertedMaterials = 0;
$insertedHistory = 0;

$stmtInsert = $conn->prepare("
    INSERT INTO warehouse_materials (
        item_code, item_name_vn, item_name_en, group_name, unit,
        packaging_spec, pack_quantity, category_type, warehouse_type,
        bin_location, image_url, norm_per_use, default_machines,
        default_uses_per_machine, stock_initial, stock_current,
        reorder_point, reorder_qty, avg_monthly_consumption, notes, frequency_text
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        item_name_vn = VALUES(item_name_vn),
        item_name_en = VALUES(item_name_en),
        group_name = VALUES(group_name),
        unit = VALUES(unit),
        packaging_spec = VALUES(packaging_spec),
        pack_quantity = VALUES(pack_quantity),
        category_type = VALUES(category_type),
        warehouse_type = VALUES(warehouse_type),
        bin_location = VALUES(bin_location),
        image_url = VALUES(image_url),
        norm_per_use = VALUES(norm_per_use),
        default_machines = VALUES(default_machines),
        default_uses_per_machine = VALUES(default_uses_per_machine),
        stock_initial = VALUES(stock_initial),
        stock_current = VALUES(stock_current),
        reorder_point = VALUES(reorder_point),
        reorder_qty = VALUES(reorder_qty),
        avg_monthly_consumption = VALUES(avg_monthly_consumption),
        notes = VALUES(notes),
        frequency_text = VALUES(frequency_text)
");

$stmtHist = $conn->prepare("
    INSERT INTO warehouse_material_history (material_id, item_code, year, month, issued_qty)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE issued_qty = VALUES(issued_qty)
");

for ($r = 4; $r < count($rowsT8); $r++) {
    $row = $rowsT8[$r];
    $code = trim($row[3] ?? '');
    if (!$code || $code === 'Product ID') continue;

    $grpRaw = trim($row[0] ?: ($row[2] ?: ''));
    if (mb_stripos($grpRaw, 'thiết bị') !== false) {
        $grp = 'Thiết bị';
    } elseif (mb_stripos($grpRaw, 'khuôn') !== false) {
        $grp = 'Bảo trì khuôn';
    } elseif (mb_stripos($grpRaw, 'sản xuất') !== false) {
        $grp = 'Sản xuất';
    } elseif (mb_stripos($grpRaw, 'nghiền') !== false) {
        $grp = 'Nghiền';
    } else {
        $grp = $grpRaw ?: 'Sản xuất';
    }

    $nameEn = trim($row[4] ?? '');
    $nameVn = trim($row[5] ?? '') ?: $nameEn;
    $machines = max(0, floatval($row[6] ?? 0));
    $usesPerMachine = max(0, floatval($row[7] ?? 0));
    $norm = max(0, floatval($row[9] ?? 0));
    if ($norm <= 0 && $machines > 0 && $usesPerMachine > 0) {
        $norm = 1.0;
    }
    $unit = trim($row[15] ?? '') ?: 'Ea';

    // Stocks & ROP
    $stockRemain = floatval($row[21] ?? 0);
    if ($stockRemain <= 0 && isset($whStock[$code])) {
        $stockRemain = $whStock[$code]['stock'];
    }
    $stockInitial = $stockRemain;

    $bin = trim($row[25] ?? '');
    if (!$bin && isset($whStock[$code])) {
        $bin = $whStock[$code]['bin'];
    }

    $rop = floatval($row[26] ?? 0);
    $moq = floatval($row[28] ?? 0);
    $freq = trim($row[23] ?? '1 lần/tháng');
    $notes = trim($row[24] ?? '');

    // Category and Warehouse
    $cat = $masterInfo[$code]['category_type'] ?? 'consumable';
    $whType = $masterInfo[$code]['warehouse_type'] ?? 'NON SAP';
    if ($machines == 0 && $usesPerMachine == 0) {
        $cat = 'irregular';
    }

    // Pack spec
    $packQty = 1.0;
    $packSpec = '1 ' . $unit . '/gói';
    if (isset($picInfo[$code])) {
        $packQty = $picInfo[$code]['pack_quantity'];
        $packSpec = "Đóng gói {$packQty} {$unit}";
    } elseif (in_array(strtolower($unit), ['box', 'lot', 'set'])) {
        $packQty = (strtolower($unit) === 'set') ? 10.0 : 5.0;
        $packSpec = "Đóng gói {$packQty} {$unit}";
    }

    // Check image file
    $imgCleanCode = preg_replace('/[^a-zA-Z0-9_-]/', '_', $code);
    $imgUrl = '';
    $possibleImgs = glob(__DIR__ . '/../resources/images/warehouse/' . $imgCleanCode . '.*');
    if (!empty($possibleImgs)) {
        $imgUrl = 'resources/images/warehouse/' . basename($possibleImgs[0]);
    }

    // Past 3 months issued quantities (T5, T6, T7 of 2026)
    $hM3 = floatval($row[18] ?? 0); // Tháng 5.2026
    $hM2 = floatval($row[19] ?? 0); // Tháng 6.2026
    $hM1 = floatval($row[20] ?? 0); // Tháng 7.2026
    if ($hM1 == 0 && floatval($row[14] ?? 0) > 0) {
        $hM1 = floatval($row[14] ?? 0);
    }
    $avg3m = round(($hM1 + $hM2 + $hM3) / 3, 2);
    if ($avg3m <= 0 && $machines > 0 && $usesPerMachine > 0 && $norm > 0) {
        $avg3m = round($machines * $usesPerMachine * $norm, 2);
    }

    if ($rop <= 0) {
        $rop = round($avg3m * 0.8, 1);
    }
    if ($moq <= 0) {
        $moq = max(round($avg3m * 2, 0), 10);
    }

    // Types:
    // s(code), s(nameVn), s(nameEn), s(grp), s(unit),
    // s(packSpec), d(packQty), s(cat), s(whType),
    // s(bin), s(imgUrl), d(norm), d(machines),
    // d(usesPerMachine), d(stockInitial), d(stockRemain),
    // d(rop), d(moq), d(avg3m), s(notes), s(freq)
    $types = "ssssssdssssdddddddss";
    // Count: 6 strings, 1 double, 4 strings, 8 doubles, 2 strings = 21 values
    $stmtInsert->bind_param(
        "ssssssdssssddddddddss",
        $code, $nameVn, $nameEn, $grp, $unit,
        $packSpec, $packQty, $cat, $whType,
        $bin, $imgUrl, $norm, $machines,
        $usesPerMachine, $stockInitial, $stockRemain,
        $rop, $moq, $avg3m, $notes, $freq
    );

    if ($stmtInsert->execute()) {
        $insertedMaterials++;
        $matId = $conn->insert_id ?: 0;
        if (!$matId) {
            $rGet = $conn->query("SELECT id FROM warehouse_materials WHERE item_code = '" . $conn->real_escape_string($code) . "'");
            if ($rGet && $rRow = $rGet->fetch_assoc()) $matId = intval($rRow['id']);
        }

        // Insert history for months 5, 6, 7 of 2026
        if ($matId > 0) {
            $mHistList = [
                ['year' => 2026, 'month' => 5, 'qty' => $hM3],
                ['year' => 2026, 'month' => 6, 'qty' => $hM2],
                ['year' => 2026, 'month' => 7, 'qty' => $hM1]
            ];
            foreach ($mHistList as $mh) {
                $y = $mh['year'];
                $m = $mh['month'];
                $q = $mh['qty'];
                $stmtHist->bind_param("isidd", $matId, $code, $y, $m, $q);
                if ($stmtHist->execute()) $insertedHistory++;
            }
        }
    } else {
        echo "Error inserting $code: " . $stmtInsert->error . "\n";
    }
}

echo "Successfully inserted/updated $insertedMaterials materials!\n";
echo "Successfully inserted/updated $insertedHistory history entries!\n";

// 6. Default Approvers
echo "=== SEEDING DEFAULT APPROVERS ===\n";
$defaultApprovers = [
    // Checkers (Người kiểm tra)
    ['role_type' => 'checker', 'group_name' => 'Thiết bị', 'username' => 'checker_tb', 'full_name' => 'Nguyễn Văn Kiểm (Kỹ sư Thiết bị)'],
    ['role_type' => 'checker', 'group_name' => 'Bảo trì khuôn', 'username' => 'checker_khuon', 'full_name' => 'Trần Văn Khuôn (Tổ trưởng Khuôn)'],
    ['role_type' => 'checker', 'group_name' => 'Sản xuất', 'username' => 'checker_sx', 'full_name' => 'Lê Văn Chuyền (Trưởng ca Sản xuất)'],
    ['role_type' => 'checker', 'group_name' => 'Nghiền', 'username' => 'checker_nghien', 'full_name' => 'Phạm Văn Nghiền (Tổ trưởng Nghiền)'],
    ['role_type' => 'checker', 'group_name' => 'ALL', 'username' => 'admin', 'full_name' => 'Admin User'],

    // Managers (Quản lý)
    ['role_type' => 'manager', 'group_name' => 'ALL', 'username' => 'manager_factory', 'full_name' => 'Hoàng Minh Quản Lý (Quản đốc Xưởng)'],
    ['role_type' => 'manager', 'group_name' => 'Thiết bị', 'username' => 'manager_tb', 'full_name' => 'Vũ Văn Trưởng (Trưởng bộ phận Thiết bị)'],
    ['role_type' => 'manager', 'group_name' => 'Sản xuất', 'username' => 'manager_sx', 'full_name' => 'Đỗ Văn Thành (Trưởng bộ phận Sản xuất)'],
    ['role_type' => 'manager', 'group_name' => 'ALL', 'username' => 'admin', 'full_name' => 'Admin User'],

    // Warehouse Admins (Admin Kho / Thủ kho)
    ['role_type' => 'admin_warehouse', 'group_name' => 'ALL', 'username' => 'admin', 'full_name' => 'Admin User (Thủ kho chính)'],
    ['role_type' => 'admin_warehouse', 'group_name' => 'ALL', 'username' => 'kho_truong', 'full_name' => 'Phan Thị Thủ Kho (Thủ kho)'],

    // Receivers (Nhân viên hiện trường nhận bàn giao)
    ['role_type' => 'receiver', 'group_name' => 'Thiết bị', 'username' => 'user_tb', 'full_name' => 'Nguyễn Hữu Hiện Trường (KTV Thiết bị)'],
    ['role_type' => 'receiver', 'group_name' => 'Sản xuất', 'username' => 'user_sx', 'full_name' => 'Đinh Văn Công Nhân (Đại diện Chuyền Đùn)'],
    ['role_type' => 'receiver', 'group_name' => 'ALL', 'username' => 'user', 'full_name' => 'User Nhân Viên']
];

$stmtApp = $conn->prepare("
    INSERT INTO warehouse_approvers (role_type, group_name, username, full_name, is_active)
    VALUES (?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)
");

foreach ($defaultApprovers as $app) {
    $stmtApp->bind_param("ssss", $app['role_type'], $app['group_name'], $app['username'], $app['full_name']);
    $stmtApp->execute();
}
echo "Default approvers seeded.\n";

echo "=== SEEDING COMPLETED SUCCESSFULLY ===\n";
