<?php
require_once __DIR__ . '/../config/db.php';
global $conn;

$res = $conn->query("SELECT id, username, full_name, role FROM users LIMIT 15");
echo "USERS:\n";
while ($r = $res->fetch_assoc()) {
    echo sprintf("  [%d] %-15s | %-20s | %s\n", $r['id'], $r['username'], $r['full_name'], $r['role']);
}
