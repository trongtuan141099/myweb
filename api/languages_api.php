<?php
/**
 * api/languages_api.php
 * API quản trị từ điển đa ngôn ngữ (Dành cho Admin & Quản trị viên)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/i18n.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Kiểm tra quyền quản trị
$userRole = $_SESSION['user']['role'] ?? '';
$hasAdminPerm = hasPermission(['role.manage', 'system.language', 'admin']) || ($userRole === 'admin');

if (!$hasAdminPerm) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền quản trị cài đặt ngôn ngữ!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_all');

// Lấy payload JSON nếu gửi dạng application/json
$rawInput = file_get_contents('php://input');
$jsonPayload = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $jsonPayload = $decoded;
        if (isset($jsonPayload['action'])) {
            $action = $jsonPayload['action'];
        }
    }
}

$dicts = getAllDictionaries();
$viDict = $dicts['vi'] ?? [];
$enDict = $dicts['en'] ?? [];
$jaDict = $dicts['ja'] ?? [];

switch ($action) {
    case 'get_all':
        // Hợp nhất toàn bộ danh sách key từ cả 3 từ điển
        $allKeys = array_unique(array_merge(array_keys($viDict), array_keys($enDict), array_keys($jaDict)));
        sort($allKeys);

        $items = [];
        $groups = [];
        $viCount = 0;
        $enCount = 0;
        $jaCount = 0;

        foreach ($allKeys as $k) {
            $parts = explode('.', $k);
            $group = count($parts) > 1 ? $parts[0] : 'other';
            $groups[$group] = true;

            $viVal = $viDict[$k] ?? '';
            $enVal = $enDict[$k] ?? '';
            $jaVal = $jaDict[$k] ?? '';

            if (trim($viVal) !== '') $viCount++;
            if (trim($enVal) !== '') $enCount++;
            if (trim($jaVal) !== '') $jaCount++;

            $items[] = [
                'key'   => $k,
                'group' => $group,
                'vi'    => $viVal,
                'en'    => $enVal,
                'ja'    => $jaVal
            ];
        }

        $totalKeys = count($allKeys);
        $stats = [
            'total_keys' => $totalKeys,
            'vi_count'   => $viCount,
            'en_count'   => $enCount,
            'ja_count'   => $jaCount,
            'vi_percent' => $totalKeys ? round(($viCount / $totalKeys) * 100, 1) : 0,
            'en_percent' => $totalKeys ? round(($enCount / $totalKeys) * 100, 1) : 0,
            'ja_percent' => $totalKeys ? round(($jaCount / $totalKeys) * 100, 1) : 0
        ];

        echo json_encode([
            'success' => true,
            'stats'   => $stats,
            'groups'  => array_keys($groups),
            'items'   => $items
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'save_key':
        $key = trim($jsonPayload['key'] ?? ($_POST['key'] ?? ''));
        $vi  = trim($jsonPayload['vi'] ?? ($_POST['vi'] ?? ''));
        $en  = trim($jsonPayload['en'] ?? ($_POST['en'] ?? ''));
        $ja  = trim($jsonPayload['ja'] ?? ($_POST['ja'] ?? ''));

        if (empty($key)) {
            echo json_encode(['success' => false, 'message' => 'Tên khóa không được để trống!'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Chuẩn hóa tên khóa: chỉ chữ, số, dấu chấm và gạch dưới
        $key = preg_replace('/[^a-zA-Z0-9_\.]/', '', $key);

        $viDict[$key] = $vi;
        $enDict[$key] = $en;
        $jaDict[$key] = $ja;

        saveDictionaryFile('vi', $viDict);
        saveDictionaryFile('en', $enDict);
        saveDictionaryFile('ja', $jaDict);

        echo json_encode([
            'success' => true,
            'message' => "Đã lưu khóa '{$key}' thành công vào cả 3 từ điển!",
            'item' => [
                'key'   => $key,
                'group' => explode('.', $key)[0],
                'vi'    => $vi,
                'en'    => $en,
                'ja'    => $ja
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'batch_save':
        $items = $jsonPayload['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Không có dữ liệu cập nhật!'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $count = 0;
        foreach ($items as $item) {
            $k = trim($item['key'] ?? '');
            if (!$k) continue;
            $k = preg_replace('/[^a-zA-Z0-9_\.]/', '', $k);

            $viDict[$k] = trim($item['vi'] ?? '');
            $enDict[$k] = trim($item['en'] ?? '');
            $jaDict[$k] = trim($item['ja'] ?? '');
            $count++;
        }

        saveDictionaryFile('vi', $viDict);
        saveDictionaryFile('en', $enDict);
        saveDictionaryFile('ja', $jaDict);

        echo json_encode([
            'success' => true,
            'message' => "Đã lưu thành công {$count} từ khóa vào hệ thống!",
            'count'   => $count
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'delete_key':
        $key = trim($jsonPayload['key'] ?? ($_POST['key'] ?? ''));
        if (empty($key)) {
            echo json_encode(['success' => false, 'message' => 'Tên khóa không hợp lệ!'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        unset($viDict[$key]);
        unset($enDict[$key]);
        unset($jaDict[$key]);

        saveDictionaryFile('vi', $viDict);
        saveDictionaryFile('en', $enDict);
        saveDictionaryFile('ja', $jaDict);

        echo json_encode([
            'success' => true,
            'message' => "Đã xóa thành công khóa '{$key}' khỏi từ điển!"
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'reset_defaults':
        $defaultsDir = __DIR__ . '/../data/languages/defaults';
        if (is_dir($defaultsDir)) {
            foreach (['vi', 'en', 'ja'] as $l) {
                $file = "{$defaultsDir}/{$l}.json";
                if (file_exists($file)) {
                    copy($file, __DIR__ . "/../data/languages/{$l}.json");
                }
            }
            echo json_encode([
                'success' => true,
                'message' => 'Đã khôi phục từ điển về cấu hình mặc định ban đầu!'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tệp từ điển mặc định!'], JSON_UNESCAPED_UNICODE);
        exit;

    case 'export_json':
        echo json_encode([
            'success' => true,
            'dictionaries' => [
                'vi' => $viDict,
                'en' => $enDict,
                'ja' => $jaDict
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Thao tác không được hỗ trợ!'], JSON_UNESCAPED_UNICODE);
        exit;
}
