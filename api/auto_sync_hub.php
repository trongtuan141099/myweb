<?php
/**
 * API Trung Tâm Điều Phối & Đồng Bộ Tự Động Toàn Hệ Thống (Auto Sync Hub)
 * DX Plastic Group - Factory Management System
 * 
 * Điều phối lịch trình tự động & đếm ngược cho cả:
 * 1. HRM Leave Management (Phép nghỉ)
 * 2. Overtime Reconciliation (Tăng ca)
 */

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');

$isCli = (php_sapi_name() === 'cli') || (isset($argv) && count($argv) > 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8");
    }
}

if (!$isCli) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Cho phép người dùng đã đăng nhập có quyền xem HRM/OT hoặc admin
    if (!hasPermission(['hrm.view', 'hrm.leave_view', 'overtime.view', 'admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Truy cập bị từ chối.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$action = $_GET['action'] ?? ($_POST['action'] ?? ($isCli ? 'tick' : 'status'));

$leaveConfigFile = __DIR__ . '/../config/hrm_leave_sync_config.json';
$otConfigFile    = __DIR__ . '/../config/hrm_sync_config.json';

function getModuleScheduleStatus($configFile, $defaultInterval = 3) {
    $cfg = [];
    if (file_exists($configFile)) {
        $json = @file_get_contents($configFile);
        $cfg = @json_decode($json, true) ?: [];
    }

    $autoEnabled   = isset($cfg['auto_sync_enabled']) ? (bool)$cfg['auto_sync_enabled'] : true;
    $intervalHours = max(1, intval($cfg['sync_interval_hours'] ?? $defaultInterval));
    $intervalSec   = $intervalHours * 3600;
    $lastSyncTime  = !empty($cfg['last_sync_time']) ? strtotime($cfg['last_sync_time']) : 0;
    $timeSince     = ($lastSyncTime > 0) ? (time() - $lastSyncTime) : 99999999;
    $secondsRemaining = max(0, $intervalSec - $timeSince);
    $isDue         = $autoEnabled && ($timeSince >= $intervalSec);
    $nextSyncTime  = date('Y-m-d H:i:s', ($lastSyncTime > 0 ? $lastSyncTime : time()) + $intervalSec);

    return [
        'auto_sync_enabled'   => $autoEnabled,
        'interval_hours'      => $intervalHours,
        'interval_seconds'    => $intervalSec,
        'last_sync_time'      => $cfg['last_sync_time'] ?? null,
        'next_sync_time'      => $nextSyncTime,
        'seconds_remaining'   => $secondsRemaining,
        'is_due'              => $isDue,
        'last_status'         => $cfg['last_sync_status'] ?? 'none',
        'last_message'        => $cfg['last_sync_message'] ?? ''
    ];
}

switch ($action) {
    case 'status':
    case 'get_status':
        $leaveStatus = getModuleScheduleStatus($leaveConfigFile, 3);
        $otStatus    = getModuleScheduleStatus($otConfigFile, 1);

        echo json_encode([
            'success'   => true,
            'server_time' => date('Y-m-d H:i:s'),
            'leave'     => $leaveStatus,
            'overtime'  => $otStatus
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'tick':
    case 'check':
        $leaveStatus = getModuleScheduleStatus($leaveConfigFile, 3);
        $otStatus    = getModuleScheduleStatus($otConfigFile, 1);
        $results = ['leave' => null, 'overtime' => null];

        // 1. Kiểm tra và chạy Leave Sync nếu đến hạn
        if ($leaveStatus['is_due']) {
            require_once __DIR__ . '/../core/leave_service.php';
            require_once __DIR__ . '/hrm_leave_sync.php';
            // hrm_leave_sync có hàm syncSingleHrmAccountLeave
            $sqlAccs = "SELECT * FROM ot_hrm_accounts WHERE is_active = 1 ORDER BY id ASC";
            $resAccs = $conn->query($sqlAccs);
            $totIns = 0; $totUp = 0; $totErr = 0; $succ = 0; $accCount = 0;
            if ($resAccs) {
                while ($acc = $resAccs->fetch_assoc()) {
                    $accCount++;
                    $r = syncSingleHrmAccountLeave($conn, $acc, date('Y-m-01'), date('Y-m-t'));
                    if (!empty($r['success'])) $succ++;
                    if (!empty($r['stats'])) {
                        $totIns += $r['stats']['inserted'];
                        $totUp  += $r['stats']['updated'];
                        $totErr += $r['stats']['errors'];
                    }
                }
            }
            // Update leave config
            $lCfg = @json_decode(@file_get_contents($leaveConfigFile), true) ?: [];
            $lCfg['last_sync_time'] = date('Y-m-d H:i:s');
            $lCfg['last_sync_status'] = ($succ > 0) ? 'success' : 'failed';
            $lCfg['last_sync_message'] = "Tự động đồng bộ: {$succ}/{$accCount} tài khoản (+{$totIns} mới, {$totUp} cập nhật).";
            $lCfg['last_sync_stats'] = ['inserted' => $totIns, 'updated' => $totUp, 'errors' => $totErr];
            @file_put_contents($leaveConfigFile, json_encode($lCfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $results['leave'] = [
                'ran' => true,
                'message' => $lCfg['last_sync_message'],
                'stats' => $lCfg['last_sync_stats']
            ];
        }

        // 2. Kiểm tra và chạy Overtime Sync nếu đến hạn
        if ($otStatus['is_due']) {
            require_once __DIR__ . '/overtime_hrm_sync.php';
            $accs = getHrmAccounts($conn, true);
            $otCfg = loadGlobalHrmConfig($otConfigFile);
            $succ = 0; $totPlanIns = 0; $totPlanUp = 0; $totActIns = 0; $totActUp = 0;
            foreach ($accs as $acc) {
                $res = syncSingleHrmAccount($conn, $acc, $otCfg['sync_date_from'] ?? '', $otCfg['sync_date_to'] ?? '', 'AUTO_SYNC_HUB');
                if (!empty($res['success'])) {
                    $succ++;
                    $totPlanIns += $res['plan_result']['inserted_rows'] ?? 0;
                    $totPlanUp  += $res['plan_result']['updated_rows'] ?? 0;
                    $totActIns  += $res['actual_result']['inserted_rows'] ?? 0;
                    $totActUp   += $res['actual_result']['updated_rows'] ?? 0;
                }
            }
            $otCfg['last_sync_time'] = date('Y-m-d H:i:s');
            $otCfg['last_sync_status'] = ($succ > 0) ? 'success' : 'failed';
            $otCfg['last_sync_message'] = "Tự động đồng bộ: {$succ}/" . count($accs) . " tài khoản. KH (+{$totPlanIns}/{$totPlanUp}), TT (+{$totActIns}/{$totActUp}).";
            saveGlobalHrmConfig($otConfigFile, $otCfg);
            runReconciliationInternal($conn);
            $results['overtime'] = [
                'ran' => true,
                'message' => $otCfg['last_sync_message']
            ];
        }

        $freshLeave = getModuleScheduleStatus($leaveConfigFile, 3);
        $freshOt    = getModuleScheduleStatus($otConfigFile, 1);

        echo json_encode([
            'success'     => true,
            'server_time' => date('Y-m-d H:i:s'),
            'executed'    => $results,
            'leave'       => $freshLeave,
            'overtime'    => $freshOt
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)], JSON_UNESCAPED_UNICODE);
        break;
}
