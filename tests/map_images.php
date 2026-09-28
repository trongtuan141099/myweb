<?php
$zip = new ZipArchive();
$files = glob(__DIR__ . '/../data/*xu*t kho*');
if ($zip->open($files[0]) !== true) {
    die("Cannot open xlsm file\n");
}

@mkdir(__DIR__ . '/../resources/images/warehouse', 0777, true);

// 1. Read drawing2.xml.rels to map rId -> target image
$relsXml = $zip->getFromName('xl/drawings/_rels/drawing2.xml.rels');
$relMap = [];
if ($relsXml) {
    $sxml = simplexml_load_string($relsXml);
    foreach ($sxml->Relationship as $rel) {
        $id = (string)$rel['Id'];
        $target = (string)$rel['Target'];
        // Target is like ../media/image1.jpeg
        $targetFile = 'xl/' . ltrim(str_replace('../', '', $target), '/');
        $relMap[$id] = $targetFile;
    }
}
echo "Found " . count($relMap) . " relationships in drawing2.xml.rels\n";

// 2. Read drawing2.xml to map row -> rId
$drawingXml = $zip->getFromName('xl/drawings/drawing2.xml');
$rowImageMap = [];
if ($drawingXml) {
    $xml = simplexml_load_string($drawingXml);
    // register namespaces
    $xml->registerXPathNamespace('xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
    $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
    $xml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

    foreach ($xml->xpath('//xdr:twoCellAnchor') as $anchor) {
        $fromRow = (int)$anchor->xpath('xdr:from/xdr:row')[0];
        $fromCol = (int)$anchor->xpath('xdr:from/xdr:col')[0];
        $blip = $anchor->xpath('.//a:blip');
        if (!empty($blip)) {
            $rId = (string)$blip[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->embed;
            if ($rId && isset($relMap[$rId])) {
                $rowImageMap[$fromRow] = $relMap[$rId];
            }
        }
    }
}
echo "Mapped " . count($rowImageMap) . " rows to images in sheet Picture\n";

// Read sheet Picture to map row -> product code
require_once __DIR__ . '/../vendor/SimpleXLSX.php';
$xlsx = \Shuchkin\SimpleXLSX::parse($files[0]);
$idxPic = array_search('Picture', $xlsx->sheetNames());
$rowsPic = $xlsx->rows($idxPic);

$productImageMap = [];
foreach ($rowImageMap as $r => $mediaPath) {
    $code = trim($rowsPic[$r][2] ?? '');
    if ($code) {
        $ext = pathinfo($mediaPath, PATHINFO_EXTENSION);
        $cleanCode = preg_replace('/[^a-zA-Z0-9_-]/', '_', $code);
        $destName = $cleanCode . '.' . $ext;
        $destPath = __DIR__ . '/../resources/images/warehouse/' . $destName;
        $content = $zip->getFromName($mediaPath);
        if ($content) {
            file_put_contents($destPath, $content);
            $productImageMap[$code] = 'resources/images/warehouse/' . $destName;
        }
    }
}

echo "Extracted and mapped " . count($productImageMap) . " product images:\n";
foreach (array_slice($productImageMap, 0, 10, true) as $code => $imgPath) {
    echo "  $code => $imgPath\n";
}

$zip->close();
