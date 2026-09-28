<?php
require_once __DIR__ . '/../vendor/SimpleXLSX.php';
use Shuchkin\SimpleXLSX;

$files = glob(__DIR__ . '/../data/*xu*t kho*');
$xlsx = SimpleXLSX::parse($files[0]);

$idx = array_search('T8', $xlsx->sheetNames());
$rows = $xlsx->rows($idx);

echo "Total rows in T8: " . count($rows) . "\n";
$groups = [];
$items = [];

for ($r = 4; $r < count($rows); $r++) {
    $row = $rows[$r];
    $code = trim($row[3] ?? '');
    if (empty($code) || $code === 'Product ID') continue;
    $grp = trim($row[0] ?: ($row[2] ?: 'Khác'));
    $nameEn = trim($row[4] ?? '');
    $nameVn = trim($row[5] ?? '');
    $machines = floatval($row[6] ?? 0);
    $usesPerMachine = floatval($row[7] ?? 0);
    $unitPerUse = floatval($row[9] ?? 0);
    $totalQty = floatval($row[10] ?? 0);
    $fieldStock = floatval($row[11] ?? 0);
    $theoExport = floatval($row[13] ?? 0);
    $actualExport = floatval($row[14] ?? 0);
    $unit = trim($row[15] ?? '');
    $stockRemain = floatval($row[21] ?? 0);
    $freq = trim($row[23] ?? '');
    $bin = trim($row[25] ?? '');
    $rop = trim($row[26] ?? '');

    $groups[$grp] = ($groups[$grp] ?? 0) + 1;
    $items[] = [
        'row' => $r,
        'group' => $grp,
        'code' => $code,
        'name' => $nameVn ?: $nameEn,
        'unit' => $unit,
        'machines' => $machines,
        'uses_per_machine' => $usesPerMachine,
        'unit_per_use' => $unitPerUse,
        'total_qty' => $totalQty,
        'field_stock' => $fieldStock,
        'actual_export' => $actualExport,
        'stock_remain' => $stockRemain,
        'bin' => $bin,
        'rop' => $rop
    ];
}

echo "\nGROUPS FOUND:\n";
foreach ($groups as $g => $count) {
    echo " - $g: $count items\n";
}

echo "\nTOTAL VALID ITEMS: " . count($items) . "\n";
echo "\nFIRST 10 ITEMS:\n";
for ($i = 0; $i < min(10, count($items)); $i++) {
    $it = $items[$i];
    echo sprintf(" [%02d] %-15s | %-30s | %-15s | M:%-4s U:%-4s Norm:%-4s => Tot:%-5s Field:%-4s Act:%-5s %-4s | Bin: %s\n",
        $it['row'], $it['code'], mb_substr($it['name'], 0, 30), mb_substr($it['group'], 0, 15),
        $it['machines'], $it['uses_per_machine'], $it['unit_per_use'],
        $it['total_qty'], $it['field_stock'], $it['actual_export'], $it['unit'], $it['bin']
    );
}
