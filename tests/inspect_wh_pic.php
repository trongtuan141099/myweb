<?php
require_once __DIR__ . '/../vendor/SimpleXLSX.php';
use Shuchkin\SimpleXLSX;

$files = glob(__DIR__ . '/../data/*xu*t kho*');
$xlsx = SimpleXLSX::parse($files[0]);

$idx = array_search('Picture', $xlsx->sheetNames());
$rows = $xlsx->rows($idx);

echo "=== Picture SHEET HEADERS (Row 4 & 5) ===\n";
foreach ($rows[4] as $c => $v) {
    if ($v !== '') echo "  Col $c [R4]: $v\n";
}
foreach ($rows[5] as $c => $v) {
    if ($v !== '') echo "  Col $c [R5]: $v\n";
}

echo "\n=== Picture SAMPLE ROWS (6 to 12) ===\n";
for ($r = 6; $r <= 12; $r++) {
    $row = $rows[$r] ?? [];
    echo "R$r: " . ($row[2] ?? '') . " | " . ($row[3] ?? '') . " | " . ($row[4] ?? '') . " | " . ($row[6] ?? '') . " | " . ($row[7] ?? '') . "\n";
}

$idxW = array_search('Wharehouse', $xlsx->sheetNames());
$rowsW = $xlsx->rows($idxW);
echo "\n=== Wharehouse SHEET HEADERS (Row 4 & 5) ===\n";
for ($r = 2; $r <= 5; $r++) {
    echo "Row $r:\n";
    foreach ($rowsW[$r] as $c => $v) {
        if ($v !== '') echo "  Col $c: $v\n";
    }
}
