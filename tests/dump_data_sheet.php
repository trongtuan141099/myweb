<?php
ini_set('memory_limit', '1024M');

$file = __DIR__ . '/../data/26年09月生産進捗(TU).xlsm';
$zip = new ZipArchive();
if ($zip->open($file) !== true) die("Error zip");

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

function dumpRows($zip, $sheetFile, $maxRows = 15) {
    $reader = new XMLReader();
    $reader->open('zip://' . $zip->filename . '#' . $sheetFile);
    global $sharedStrings;
    $count = 0;
    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'row') {
            $rNum = $reader->getAttribute('r');
            $rowObj = simplexml_load_string($reader->readOuterXML());
            $cols = [];
            foreach ($rowObj->c as $c) {
                $ref = (string)$c['r'];
                preg_match('/^([A-Z]+)/', $ref, $m);
                $colLetters = $m[1] ?? $ref;
                $type = (string)$c['t'];
                $val = isset($c->v) ? (string)$c->v : (isset($c->is->t) ? (string)$c->is->t : '');
                if ($type === 's' && isset($sharedStrings[intval($val)])) {
                    $val = $sharedStrings[intval($val)];
                }
                if ($val !== '') {
                    $cols[$colLetters] = $val;
                }
            }
            echo "Row $rNum: " . json_encode($cols, JSON_UNESCAPED_UNICODE) . "\n";
            $count++;
            if ($count >= $maxRows) break;
        }
    }
    $reader->close();
}

echo "=== SHEET: 良品率 (xl/worksheets/sheet14.xml) ===\n";
dumpRows($zip, 'xl/worksheets/sheet14.xml', 20);

echo "\n=== SHEET: Đối ứng (xl/worksheets/sheet12.xml) ===\n";
dumpRows($zip, 'xl/worksheets/sheet12.xml', 10);

$zip->close();
