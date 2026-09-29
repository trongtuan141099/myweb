<?php
ini_set('memory_limit', '1024M');

$file = __DIR__ . '/../data/26年09月生産進捗(TU).xlsm';

$zip = new ZipArchive();
if ($zip->open($file) !== true) {
    die("Cannot open zip archive: $file\n");
}

// 1. Read workbook.xml to map sheet names to sheet files
$wbXml = $zip->getFromName('xl/workbook.xml');
$wbRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

// Map r:id to target filename
$rels = [];
$xmlRels = simplexml_load_string($wbRelsXml);
foreach ($xmlRels->Relationship as $rel) {
    $rels[(string)$rel['Id']] = (string)$rel['Target'];
}

// Map sheet name to file
$sheetFiles = [];
$xmlWb = simplexml_load_string($wbXml);
foreach ($xmlWb->sheets->sheet as $s) {
    $name = (string)$s['name'];
    $rId = (string)$s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
    $target = $rels[$rId] ?? '';
    if (strpos($target, 'worksheets/') !== false) {
        $sheetFiles[$name] = 'xl/' . ltrim($target, '/');
    }
}

echo "Sheet mappings:\n";
foreach ($sheetFiles as $name => $f) {
    if (in_array($name, ['データ', '良品率', 'Đối ứng', '毎日報告', 'Report'])) {
        echo "  - $name => $f\n";
    }
}

// Read sharedStrings.xml (stream or read)
echo "Loading shared strings...\n";
$sharedStrings = [];
$ssXml = $zip->getFromName('xl/sharedStrings.xml');
if ($ssXml) {
    $reader = new XMLReader();
    $reader->XML($ssXml);
    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'si') {
            $siXml = $reader->readOuterXML();
            $si = simplexml_load_string($siXml);
            $text = '';
            if (isset($si->t)) {
                $text = (string)$si->t;
            } elseif (isset($si->r)) {
                foreach ($si->r as $r) {
                    $text .= (string)$r->t;
                }
            }
            $sharedStrings[] = $text;
        }
    }
    $reader->close();
    unset($ssXml);
}
echo "Shared strings loaded: " . count($sharedStrings) . "\n";

// Function to inspect top rows of a worksheet XML using XMLReader
function inspectSheetXml($zip, $sheetFile, $sheetName, $sharedStrings, $maxRows = 10) {
    echo "\n======================================================\n";
    echo "INSPECTING SHEET: $sheetName ($sheetFile)\n";
    echo "======================================================\n";

    $stream = $zip->getStream($sheetFile);
    if (!$stream) {
        echo "Cannot open stream for $sheetFile\n";
        return;
    }

    $reader = new XMLReader();
    $reader->open('zip://' . $zip->filename . '#' . $sheetFile);

    $rowCount = 0;
    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'row') {
            $rNum = $reader->getAttribute('r');
            $rowXml = $reader->readOuterXML();
            $rowObj = simplexml_load_string($rowXml);
            $cols = [];
            foreach ($rowObj->c as $c) {
                $cellRef = (string)$c['r'];
                $type = (string)$c['t'];
                $val = isset($c->v) ? (string)$c->v : (isset($c->is->t) ? (string)$c->is->t : '');
                if ($type === 's' && isset($sharedStrings[intval($val)])) {
                    $val = $sharedStrings[intval($val)];
                }
                if ($val !== '') {
                    $cols[$cellRef] = $val;
                }
            }
            if (!empty($cols)) {
                echo "Row $rNum (" . count($cols) . " cells): " . json_encode($cols, JSON_UNESCAPED_UNICODE) . "\n";
                $rowCount++;
                if ($rowCount >= $maxRows) break;
            }
        }
    }
    $reader->close();
}

foreach (['データ', '良品率', 'Đối ứng'] as $target) {
    if (isset($sheetFiles[$target])) {
        inspectSheetXml($zip, $sheetFiles[$target], $target, $sharedStrings, 10);
    }
}

$zip->close();
