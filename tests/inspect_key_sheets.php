<?php
require_once __DIR__ . '/../vendor/SimpleXLSX.php';
use Shuchkin\SimpleXLSX;

$files = glob(__DIR__ . '/../data/*xu*t kho*');
$xlsx = SimpleXLSX::parse($files[0]);

function inspectSheet($xlsx, $sheetName, $maxRows = 20) {
    $idx = array_search($sheetName, $xlsx->sheetNames());
    if ($idx === false) {
        echo "Sheet $sheetName not found!\n";
        return;
    }
    $rows = $xlsx->rows($idx);
    echo "\n======================================================\n";
    echo "INSPECTING SHEET: $sheetName (Total Rows: " . count($rows) . ")\n";
    echo "======================================================\n";
    for ($r = 0; $r < min(count($rows), $maxRows); $r++) {
        $row = $rows[$r];
        $items = [];
        foreach ($row as $c => $val) {
            $val = trim((string)$val);
            if ($val !== '') {
                $items[] = "[$c] " . (mb_strlen($val) > 35 ? mb_substr($val, 0, 32) . '...' : $val);
            }
        }
        if (!empty($items)) {
            echo "R" . str_pad($r, 2, ' ', STR_PAD_LEFT) . ": " . implode(" | ", $items) . "\n";
        }
    }
}

inspectSheet($xlsx, 'T8', 25);
inspectSheet($xlsx, 'Master', 25);
inspectSheet($xlsx, 'Số lần VS', 20);
inspectSheet($xlsx, 'List', 10);
inspectSheet($xlsx, 'Wharehouse', 15);
