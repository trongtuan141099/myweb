<?php
/**
 * Seeder: Nhập dữ liệu ban đầu từ file 26年09月生産進捗(TU).xlsm
 * Nạp sheet データ -> quality_yield_records
 * Nạp sheet Đối ứng -> quality_investigations
 */
ini_set('memory_limit', '1024M');
require_once __DIR__ . '/../config/db.php';

function excelDateToYmd($val) {
    if (empty($val)) return null;
    $val = trim($val);
    if (is_numeric($val) && floatval($val) > 30000 && floatval($val) < 60000) {
        $days = intval($val);
        $base = new DateTime('1899-12-30');
        $base->modify("+{$days} days");
        return $base->format('Y-m-d');
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $val, $m)) {
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }
    $ts = strtotime($val);
    if ($ts) return date('Y-m-d', $ts);
    return null;
}

$file = __DIR__ . '/../data/26年09月生産進捗(TU).xlsm';
if (!file_exists($file)) {
    die("File not found: $file\n");
}

$zip = new ZipArchive();
if ($zip->open($file) !== true) die("Error zip");

// Get shared strings
$sharedStrings = [];
$ssXml = $zip->getFromName('xl/sharedStrings.xml');
if ($ssXml) {
    $reader = new XMLReader();
    $reader->XML($ssXml);
    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'si') {
            $si = simplexml_load_string($reader->readOuterXML());
            $text = '';
            if (isset($si->t)) $text = (string)$si->t;
            elseif (isset($si->r)) {
                foreach ($si->r as $r) $text .= (string)$r->t;
            }
            $sharedStrings[] = $text;
        }
    }
    $reader->close();
}

// 1. IMPORT SHEET: データ (xl/worksheets/sheet13.xml)
echo "Importing sheet データ into quality_yield_records...\n";
$reader = new XMLReader();
$reader->open('zip://' . $zip->filename . '#xl/worksheets/sheet13.xml');

$insertedCount = 0;
$rowNum = 0;

$stmtInsert = $conn->prepare("INSERT INTO `quality_yield_records` (
    `komaki_date`, `komaki_machine`, `size`, `lot_no`, `product_code`, 
    `extrusion_date`, `extrusion_machine`, `shift`, `material_type`, 
    `good_qty`, `total_qty`, `yield_rate`, `defect_qty`, `benchmark_rate`, 
    `defect_a1`, `defect_a2`, `defect_a3`, `defect_a4`, `defect_a5`, 
    `bobbin_length`, `created_by`
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'IMPORT_SEED')");

while ($reader->read()) {
    if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'row') {
        $rNum = intval($reader->getAttribute('r'));
        if ($rNum <= 3) continue; // Skip header lines (Row 1-3)

        $rowObj = simplexml_load_string($reader->readOuterXML());
        $cols = [];
        foreach ($rowObj->c as $c) {
            $ref = (string)$c['r'];
            preg_match('/^([A-Z]+)/', $ref, $m);
            $col = $m[1] ?? $ref;
            $type = (string)$c['t'];
            $val = isset($c->v) ? (string)$c->v : (isset($c->is->t) ? (string)$c->is->t : '');
            if ($type === 's' && isset($sharedStrings[intval($val)])) {
                $val = $sharedStrings[intval($val)];
            }
            $cols[$col] = trim($val);
        }

        $komakiDate = excelDateToYmd($cols['A'] ?? '');
        $size = $cols['C'] ?? '';
        $lotNo = $cols['D'] ?? '';
        $productCode = $cols['E'] ?? '';

        if (empty($komakiDate) || empty($size) || empty($lotNo)) {
            continue; // Skip invalid or empty row
        }

        $komakiMachine = !empty($cols['B']) ? $cols['B'] : 'ST01';
        $extrusionDate = excelDateToYmd($cols['F'] ?? '');
        $goodQty = intval($cols['G'] ?? 0);
        $totalQty = intval($cols['H'] ?? 0);
        if ($totalQty <= 0 && $goodQty > 0) $totalQty = $goodQty;

        $rawYield = floatval($cols['I'] ?? 0);
        if ($rawYield > 0 && $rawYield <= 1.0) {
            $yieldRate = round($rawYield * 100, 2);
        } elseif ($totalQty > 0) {
            $yieldRate = round(($goodQty / $totalQty) * 100, 2);
        } else {
            $yieldRate = 100.0;
        }

        $defectQty = intval($cols['J'] ?? ($totalQty - $goodQty));
        $extMachine = !empty($cols['BA']) ? $cols['BA'] : (!empty($cols['E']) ? 'PL08' : 'PL08');
        $shift = !empty($cols['AK']) ? 'Ca ' . $cols['AK'] : 'Ca 1';
        $material = !empty($cols['AN']) ? $cols['AN'] : (!empty($cols['AL']) ? $cols['AL'] : 'Zin');
        $benchmark = !empty($cols['AS']) ? floatval($cols['AS']) : 98.00;

        $defA1 = intval($cols['K'] ?? 0);
        $defA2 = intval($cols['M'] ?? 0);
        $defA3 = intval($cols['N'] ?? 0);
        $defA4 = intval($cols['O'] ?? 0);
        $defA5 = intval($cols['P'] ?? 0);
        $bobbinLen = intval($cols['AI'] ?? 0);

        $stmtInsert->bind_param("sssssssssiididiiiiii", 
            $komakiDate, $komakiMachine, $size, $lotNo, $productCode,
            $extrusionDate, $extMachine, $shift, $material,
            $goodQty, $totalQty, $yieldRate, $defectQty, $benchmark,
            $defA1, $defA2, $defA3, $defA4, $defA5, $bobbinLen
        );
        $stmtInsert->execute();
        $insertedCount++;

        // Limit seed to first 300 rows for high performance, while having full statistical variety
        if ($insertedCount >= 300) break;
    }
}
$reader->close();
$stmtInsert->close();
echo "  [OK] Seeded $insertedCount yield records.\n";

// 2. IMPORT SHEET: Đối ứng (xl/worksheets/sheet12.xml)
echo "Importing sheet Đối ứng into quality_investigations...\n";
$reader = new XMLReader();
$reader->open('zip://' . $zip->filename . '#xl/worksheets/sheet12.xml');

$invCount = 0;
$stmtInv = $conn->prepare("INSERT INTO `quality_investigations` (
    `investigation_code`, `investigation_date`, `size`, `product_code`, 
    `extrusion_machine`, `extrusion_date`, `komaki_date`, `lot_no`, 
    `yield_rate`, `rate_a1`, `rate_a2`, `rate_a3`, `rate_a4`, `rate_a5`, 
    `status_description`, `root_cause`, `countermeasure`, `assigned_to`, `result_status`
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE `status_description` = VALUES(`status_description`), `root_cause` = VALUES(`root_cause`), `countermeasure` = VALUES(`countermeasure`)");

while ($reader->read()) {
    if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'row') {
        $rNum = intval($reader->getAttribute('r'));
        if ($rNum <= 3) continue;

        $rowObj = simplexml_load_string($reader->readOuterXML());
        $cols = [];
        foreach ($rowObj->c as $c) {
            $ref = (string)$c['r'];
            preg_match('/^([A-Z]+)/', $ref, $m);
            $col = $m[1] ?? $ref;
            $type = (string)$c['t'];
            $val = isset($c->v) ? (string)$c->v : (isset($c->is->t) ? (string)$c->is->t : '');
            if ($type === 's' && isset($sharedStrings[intval($val)])) {
                $val = $sharedStrings[intval($val)];
            }
            $cols[$col] = trim($val);
        }

        $size = $cols['C'] ?? '';
        $code = $cols['D'] ?? '';
        if (empty($size) || empty($code)) continue;

        $invCode = 'INV-202609-' . str_pad($rNum, 3, '0', STR_PAD_LEFT);
        $invDate = excelDateToYmd($cols['B'] ?? date('Y-m-d'));
        if (empty($invDate)) $invDate = '2026-09-03';
        $machine = $cols['E'] ?? 'PL14';
        $extDate = excelDateToYmd($cols['F'] ?? '');
        $komDate = excelDateToYmd($cols['G'] ?? '');
        $lotNo = $cols['H'] ?? '';

        $rawRate = floatval($cols['I'] ?? 0);
        $yieldRate = ($rawRate <= 1.0 && $rawRate > 0) ? round($rawRate * 100, 2) : round($rawRate, 2);

        $rA1 = round(floatval($cols['J'] ?? 0) * 100, 2);
        $rA2 = round(floatval($cols['K'] ?? 0) * 100, 2);
        $rA3 = round(floatval($cols['L'] ?? 0) * 100, 2);
        $rA4 = round(floatval($cols['M'] ?? 0) * 100, 2);
        $rA5 = round(floatval($cols['N'] ?? 0) * 100, 2);

        $statusDesc = !empty($cols['O']) ? $cols['O'] : 'Phát sinh lỗi bất thường';
        $rootCause = $cols['P'] ?? '';
        $countermeasure = $cols['Q'] ?? '';
        $assignedTo = !empty($cols['R']) ? $cols['R'] : 'Đội QC / Kỹ thuật';
        $resultStatus = !empty($cols['S']) ? $cols['S'] : 'Đang xử lý';

        $stmtInv->bind_param("ssssssssddddddsssss",
            $invCode, $invDate, $size, $code,
            $machine, $extDate, $komDate, $lotNo,
            $yieldRate, $rA1, $rA2, $rA3, $rA4, $rA5,
            $statusDesc, $rootCause, $countermeasure, $assignedTo, $resultStatus
        );
        $stmtInv->execute();
        $invCount++;
    }
}
$reader->close();
$stmtInv->close();
$zip->close();

echo "  [OK] Seeded $invCount investigation records.\n";
echo "=== SEEDING COMPLETED ===\n";
