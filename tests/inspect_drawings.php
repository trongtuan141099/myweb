<?php
$zip = new ZipArchive();
$files = glob(__DIR__ . '/../data/*xu*t kho*');
if ($zip->open($files[0]) === true) {
    // Find drawings xml
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, 'drawings/drawing') !== false && strpos($name, '.xml') !== false) {
            echo "Found drawing: $name\n";
            $xml = $zip->getFromName($name);
            // check first 500 chars
            echo substr($xml, 0, 300) . "\n---\n";
        }
    }
    $zip->close();
}
