<?php
require_once __DIR__ . '/../vendor/SimpleXLSX.php';
use Shuchkin\SimpleXLSX;

$files = glob(__DIR__ . '/../data/*xu*t kho*');
if (empty($files)) {
    die("File not found!\n");
}
$filePath = $files[0];
echo "Found file: " . basename($filePath) . "\n";

$xlsx = SimpleXLSX::parse($filePath);
if (!$xlsx) {
    echo "SimpleXLSX parse error: " . SimpleXLSX::parseError() . "\n";
    exit;
}

$sheetNames = $xlsx->sheetNames();
echo "Sheet names (" . count($sheetNames) . "):\n";
foreach ($sheetNames as $idx => $sName) {
    echo "  [$idx] $sName\n";
}

foreach ($sheetNames as $idx => $sName) {
    $rows = $xlsx->rows($idx);
    echo "\n======================================================\n";
    echo "SHEET [$idx]: $sName (Total Rows: " . count($rows) . ")\n";
    echo "======================================================\n";
    
    $limit = min(count($rows), 15);
    for ($r = 0; $r < $limit; $r++) {
        $row = $rows[$r];
        $nonEmpty = [];
        foreach ($row as $colIdx => $val) {
            if ($val !== '' && $val !== null) {
                $nonEmpty[] = "Col $colIdx: " . mb_substr((string)$val, 0, 45);
            }
        }
        if (!empty($nonEmpty)) {
            echo "Row $r: " . implode(" | ", array_slice($nonEmpty, 0, 15)) . "\n";
        }
    }
}
