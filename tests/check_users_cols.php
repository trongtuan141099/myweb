<?php
require_once __DIR__ . '/../config/db.php';
global $conn;

$res = $conn->query("DESCRIBE users");
if ($res) {
    echo "users columns:\n";
    while ($r = $res->fetch_assoc()) {
        echo " - {$r['Field']} ({$r['Type']})\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$resData = $conn->query("SELECT * FROM users LIMIT 5");
if ($resData) {
    while ($r = $resData->fetch_assoc()) {
        print_r($r);
    }
}
