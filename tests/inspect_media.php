<?php
$zip = new ZipArchive();
$files = glob(__DIR__ . '/../data/*xu*t kho*');
if ($zip->open($files[0]) === true) {
    echo "Zip contains " . $zip->numFiles . " entries.\n";
    $media = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, 'media/') !== false) {
            $media[] = $name;
        }
    }
    echo "Total media files: " . count($media) . "\n";
    for ($i = 0; $i < min(15, count($media)); $i++) {
        echo "   " . $media[$i] . "\n";
    }
    $zip->close();
}
