<?php
require_once __DIR__ . '/../config/db.php';
global $conn;

$aliasMap = [
    'MC-291CL' => 'resources/images/warehouse/MC-223BK.jpeg',
    'WL-220'   => 'resources/images/warehouse/WL-210.jpeg',
    'IC-2WT850' => 'resources/images/warehouse/IR-253WTSPB.jpeg',
    'IC-2BK101' => 'resources/images/warehouse/IC-223BK.jpeg',
    'MC-2WT850' => 'resources/images/warehouse/MC-253CL.jpeg',
    'LIOPLAX-LIOCLEAN-Z-A' => 'resources/images/warehouse/LIOCLEAN-Z-A.jpeg',
    'FAI75-150' => 'resources/images/warehouse/75-150WIREDIAMETE0_06.jpeg',
    'FAI75-100' => 'resources/images/warehouse/75-100WIREDIAMETE0_01.jpeg',
    'LGMT-3/0.4' => 'resources/images/warehouse/LGMT-3_0_4.jpeg',
    '3M-764'    => 'resources/images/warehouse/3M-764W-F1.jpeg'
];

foreach ($aliasMap as $code => $img) {
    if (file_exists(__DIR__ . '/../' . $img)) {
        $c = $conn->real_escape_string($code);
        $i = $conn->real_escape_string($img);
        $conn->query("UPDATE warehouse_materials SET image_url = '$i' WHERE item_code = '$c'");
    }
}

// Also check any code matching a file in resources/images/warehouse
$res = $conn->query("SELECT id, item_code, image_url FROM warehouse_materials");
while ($r = $res->fetch_assoc()) {
    if (empty($r['image_url'])) {
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $r['item_code']);
        $matches = glob(__DIR__ . '/../resources/images/warehouse/' . $clean . '.*');
        if (!empty($matches)) {
            $img = 'resources/images/warehouse/' . basename($matches[0]);
            $conn->query("UPDATE warehouse_materials SET image_url = '$img' WHERE id = " . $r['id']);
        }
    }
}

echo "Image mappings updated.\n";
