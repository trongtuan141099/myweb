<?php
require_once __DIR__ . '/../config/db.php';
global $conn;

$res = $conn->query("DESCRIBE inventory_campaigns");
if ($res) {
    echo "inventory_campaigns:\n";
    while ($r = $res->fetch_assoc()) {
        echo " - {$r['Field']} ({$r['Type']})\n";
    }
}
