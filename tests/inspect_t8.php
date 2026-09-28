<?php
require_once __DIR__ . '/../vendor/SimpleXLSX.php';
use Shuchkin\SimpleXLSX;

$files = glob(__DIR__ . '/../data/*xu*t kho*');
$xlsx = SimpleXLSX::parse($files[0]);

function printSheetDetail($xlsx, $sheetName, $maxRows = 30) {
    $idx = array_search($sheetName, $xlsx->sheetNames());
    $rows = $xlsx->rows($idx);
    echo "\n======================================================\n";
    echo "FULL DETAIL: $sheetName (Total Rows: " . count($rows) . ")\n";
    echo "======================================================\n";
    for ($r = 0; $r < min(count($rows), $maxRows); $r++) {
        $row = $rows[$r];
        echo "Row " . sprintf('%02d', $r) . ":\n";
        foreach ($row as $c => $val) {
            $val = trim((string)$val);
            if ($val !== '') {
                echo "   Col " . sprintf('%02d', $c) . ": $val\n";
            }
        }
    }
}

printSheetDetail($xlsx, 'T8', 12);
printSheetDetail($xlsx, 'Master', 10);
printSheetDetail($xlsx, 'Picture', 10);
