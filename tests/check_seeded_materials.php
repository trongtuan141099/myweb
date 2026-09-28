<?php
require_once __DIR__ . '/../config/db.php';
global $conn;

$res = $conn->query("
    SELECT id, item_code, item_name_vn, group_name, unit, pack_quantity, 
           category_type, default_machines, default_uses_per_machine, norm_per_use, 
           stock_current, reorder_point, bin_location, image_url 
    FROM warehouse_materials LIMIT 8
");
echo "SEEDED MATERIALS:\n";
while ($r = $res->fetch_assoc()) {
    echo sprintf(
        "[%d] %-15s | %-25s | %-12s | M:%-4s U:%-4s Norm:%-4s Pack:%-4s | Stock:%-6s ROP:%-5s | Bin: %-18s | Img: %s\n",
        $r['id'], $r['item_code'], mb_substr($r['item_name_vn'], 0, 25), $r['group_name'],
        $r['default_machines'], $r['default_uses_per_machine'], $r['norm_per_use'], $r['pack_quantity'],
        $r['stock_current'], $r['reorder_point'], $r['bin_location'], $r['image_url'] ? 'YES' : 'NO'
    );
}
