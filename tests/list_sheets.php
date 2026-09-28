<?php
require_once __DIR__ . '/../vendor/SimpleXLSX.php';
use Shuchkin\SimpleXLSX;

$files = glob(__DIR__ . '/../data/*xu*t kho*');
$xlsx = SimpleXLSX::parse($files[0]);

foreach ($xlsx->sheetNames() as $idx => $name) {
    $rows = $xlsx->rows($idx);
    echo sprintf("[%02d] %-35s (Rows: %d)\n", $idx, $name, count($rows));
}
