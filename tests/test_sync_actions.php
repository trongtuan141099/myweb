<?php
error_reporting(0);
ini_set('display_errors', '0');
// Test HRM leave sync actions and auto sync hub
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['role_id'] = 1;

echo "=== TEST HRM LEAVE SYNC ACTIONS ===\n";

// 1. check_schedule
ob_start();
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'check_schedule';
require 'api/hrm_leave_sync.php';
$out1 = ob_get_clean();
$json1 = json_decode($out1, true);
echo "1. check_schedule result: " . ($json1['success'] ? "SUCCESS" : "FAILED") . "\n";
echo "   Auto sync enabled: " . ($json1['auto_sync_enabled'] ? "Yes" : "No") . "\n";
echo "   Seconds remaining: " . ($json1['seconds_remaining'] ?? 'N/A') . "\n";
echo "   Next sync time: " . ($json1['next_sync_time'] ?? 'N/A') . "\n\n";

// 2. action=sync (legacy fallback)
ob_start();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'sync';
$_GET = [];
require 'api/hrm_leave_sync.php';
$out2 = ob_get_clean();
$json2 = json_decode($out2, true);
echo "2. action=sync result: " . ($json2['success'] ? "SUCCESS" : "FAILED") . "\n";
echo "   Message: " . ($json2['message'] ?? $json2['error'] ?? 'N/A') . "\n";
echo "   Inserted: " . ($json2['inserted_count'] ?? 0) . "\n\n";

// 4. Test api/hrm_leave.php forwarding for check_schedule
echo "=== TEST API/HRM_LEAVE.PHP FORWARDING ===\n";
ob_start();
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'check_schedule';
require 'api/hrm_leave.php';
$out4 = ob_get_clean();
$json4 = json_decode($out4, true);
echo "4. hrm_leave.php?action=check_schedule result: " . ($json4['success'] ? "SUCCESS" : "FAILED") . "\n";
echo "   Seconds remaining: " . ($json4['seconds_remaining'] ?? 'N/A') . "\n";

