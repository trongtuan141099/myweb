<?php
require_once __DIR__ . '/../config/db.php';

echo "=== MIGRATING ot_explanations TABLE ===\n";

$cols = [];
$res = $conn->query("SHOW COLUMNS FROM ot_explanations");
if (!$res) {
    die("Error checking columns: " . $conn->error . "\n");
}
while ($r = $res->fetch_assoc()) {
    $cols[] = $r['Field'];
}

if (!in_array('start_time', $cols)) {
    $conn->query("ALTER TABLE ot_explanations ADD COLUMN start_time DATETIME DEFAULT NULL AFTER ot_date");
    echo "Added column: start_time\n";
} else {
    echo "Column start_time already exists\n";
}

if (!in_array('end_time', $cols)) {
    $conn->query("ALTER TABLE ot_explanations ADD COLUMN end_time DATETIME DEFAULT NULL AFTER start_time");
    echo "Added column: end_time\n";
} else {
    echo "Column end_time already exists\n";
}

if (!in_array('total_hours', $cols)) {
    $conn->query("ALTER TABLE ot_explanations ADD COLUMN total_hours DECIMAL(5,2) DEFAULT 0.00 AFTER end_time");
    echo "Added column: total_hours\n";
} else {
    echo "Column total_hours already exists\n";
}

if (!in_array('total_minutes', $cols)) {
    $conn->query("ALTER TABLE ot_explanations ADD COLUMN total_minutes INT(11) DEFAULT 0 AFTER total_hours");
    echo "Added column: total_minutes\n";
} else {
    echo "Column total_minutes already exists\n";
}

if (!in_array('is_manual', $cols)) {
    $conn->query("ALTER TABLE ot_explanations ADD COLUMN is_manual TINYINT(1) DEFAULT 0 AFTER total_minutes");
    echo "Added column: is_manual\n";
} else {
    echo "Column is_manual already exists\n";
}

// Make sure reconciliation_id allows 0 or is not strictly foreign-keyed
$conn->query("ALTER TABLE ot_explanations MODIFY COLUMN reconciliation_id BIGINT(20) DEFAULT 0");

echo "Migration completed successfully!\n";
