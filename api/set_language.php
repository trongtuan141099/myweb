<?php
/**
 * api/set_language.php
 * Thiết lập ngôn ngữ hệ thống cho phiên làm việc hiện tại
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/i18n.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$lang = $_POST['lang'] ?? ($_GET['lang'] ?? '');

// Nếu gửi raw json body
if (empty($lang)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $json = json_decode($rawInput, true);
        if (isset($json['lang'])) {
            $lang = $json['lang'];
        }
    }
}

$lang = trim(strtolower($lang));
$supported = array_keys(getSupportedLanguages());

if (!in_array($lang, $supported, true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Ngôn ngữ không hợp lệ. Chỉ hỗ trợ: ' . implode(', ', $supported)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

setCurrentLanguage($lang);

echo json_encode([
    'success' => true,
    'lang' => $lang,
    'supported' => getSupportedLanguages(),
    'message' => 'Chuyển đổi ngôn ngữ thành công sang ' . strtoupper($lang)
], JSON_UNESCAPED_UNICODE);
exit;
