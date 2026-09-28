<?php
require_once __DIR__ . '/../config/db.php';
global $conn;

$res = $conn->query("SHOW TABLES");
echo "DATABASE TABLES:\n";
while ($r = $res->fetch_array()) {
    echo " - " . $r[0] . "\n";
}
