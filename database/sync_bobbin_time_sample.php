<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/quality_service.php';
global $conn;

echo "=== SYNCING BOBBIN TIME & MATERIAL GROUP FOR EXISTING RECORDS ===\n";

$filePath = __DIR__ . '/../data/26年09月生産進捗(TU).xlsm';
$zip = new ZipArchive();
if ($zip->open($filePath) !== true) { echo "Cannot open zip\n"; exit; }

$sharedStrings = [];
if ($ssXml = $zip->getFromName('xl/sharedStrings.xml')) {
    $reader = new XMLReader();
    $reader->xml($ssXml);
    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'si') {
            $el = new SimpleXMLElement($reader->readOuterXml());
            $text = '';
            if (isset($el->t)) $text = (string)$el->t;
            elseif (isset($el->r)) {
                foreach ($el->r as $run) $text .= (string)$run->t;
            }
            $sharedStrings[] = $text;
        }
    }
    $reader->close();
}

$sheetXml = $zip->getFromName('xl/worksheets/sheet13.xml');
$reader = new XMLReader();
$reader->xml($sheetXml);

$count = 0;
$stmtUpdate = $conn->prepare("UPDATE `quality_yield_records` 
                              SET `bobbin_time` = ?, `material_group` = ?, `upsert_key` = ?
                              WHERE `id` = ?");

while ($reader->read()) {
    if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'row') {
        $rNum = intval($reader->getAttribute('r'));
        if ($rNum >= 4) {
            $rowXml = new SimpleXMLElement($reader->readOuterXml());
            $rowCols = [];
            foreach ($rowXml->c as $c) {
                $ref = (string)$c['r'];
                $col = preg_replace('/[0-9]/', '', $ref);
                $type = (string)$c['t'];
                $val = isset($c->v) ? (string)$c->v : '';
                if ($type == 's' && isset($sharedStrings[intval($val)])) {
                    $val = $sharedStrings[intval($val)];
                }
                $rowCols[$col] = $val;
            }
            $kDate = parseQualityDate($rowCols['A'] ?? '');
            $size = trim($rowCols['C'] ?? '');
            $lotNo = trim($rowCols['D'] ?? '');
            if (empty($kDate) || empty($size) || empty($lotNo)) continue;

            $recordId = ($rNum - 3); // 1-to-1 match with the first 300 rows seeded
            if ($recordId > 300) break;

            $bTimeRaw = trim($rowCols['AH'] ?? '');
            $bTime = '00:00';
            if (is_numeric($bTimeRaw)) {
                $num = floatval($bTimeRaw);
                $sec = (int)round($num * 86400);
                $h = floor($sec / 3600) % 24;
                $m = floor(($sec % 3600) / 60);
                $bTime = sprintf('%02d:%02d', $h, $m);
            } elseif (!empty($bTimeRaw)) {
                $bTime = $bTimeRaw;
            }

            // Material group
            $char2 = (strlen($lotNo) >= 2) ? strtoupper(substr($lotNo, 1, 1)) : '';
            $matGroup = in_array($char2, ['C', 'B', 'K', 'E', 'V']) ? 'recycled' : 'virgin';

            $kMac = trim($rowCols['B'] ?? 'ST01');
            $eDate = parseQualityDate($rowCols['F'] ?? null) ?: '0000-00-00';
            $upsertKey = "{$bTime}|{$eDate}|{$kMac}|{$kDate}|{$recordId}"; // Unique during migration

            $stmtUpdate->bind_param("sssi", $bTime, $matGroup, $upsertKey, $recordId);
            $stmtUpdate->execute();
            $count++;
        }
    }
}
$reader->close();
$stmtUpdate->close();
$zip->close();

echo "  [OK] Synchronized $count records with bobbin_time and material_group.\n";
