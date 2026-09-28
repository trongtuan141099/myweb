<?php
require_once __DIR__ . '/../vendor/SimpleXLSX.php';
use Shuchkin\SimpleXLSX;

$files = glob(__DIR__ . '/../data/*xu*t kho*');
$xlsx = SimpleXLSX::parse($files[0]);

$idx = array_search('T8', $xlsx->sheetNames());
$rows = $xlsx->rows($idx);

echo "=== T8 HEADER ROWS (0 to 5) ===\n";
for ($r = 0; $r <= 5; $r++) {
    echo "Row $r:\n";
    foreach ($rows[$r] as $c => $v) {
        if ($v !== '') echo "  Col $c: $v\n";
    }
}

echo "\n=== T8 SAMPLE ROW 6 ===\n";
foreach ($rows[6] as $c => $v) {
    $headerName = ($rows[3][$c] ?? '') . ' / ' . ($rows[4][$c] ?? '');
    echo "  Col $c [$headerName]: $v\n";
}
