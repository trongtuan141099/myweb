<?php
// core/i18n.php
// Hệ thống Đa ngôn ngữ (i18n) tập trung - DX Plastic Group (Việt - Anh - Nhật)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Danh sách ngôn ngữ được hỗ trợ chuẩn
 */
function getSupportedLanguages() {
    return [
        'vi' => [
            'code'  => 'vi',
            'name'  => 'Tiếng Việt',
            'flag'  => '🇻🇳',
            'short' => 'VI',
            'locale'=> 'vi-VN'
        ],
        'en' => [
            'code'  => 'en',
            'name'  => 'English',
            'flag'  => '🇬🇧',
            'short' => 'EN',
            'locale'=> 'en-US'
        ],
        'ja' => [
            'code'  => 'ja',
            'name'  => '日本語',
            'flag'  => '🇯🇵',
            'short' => 'JA',
            'locale'=> 'ja-JP'
        ]
    ];
}

/**
 * Render cờ vector chuẩn SVG sắc nét cho đa trình duyệt (Tránh lỗi ô vuông trên Windows)
 */
function renderFlagSvg($lang, $width = 20, $height = 14) {
    $uid = substr(md5($lang . $width . $height . microtime()), 0, 6);
    switch ($lang) {
        case 'en':
            return '<svg class="lang-flag-svg" viewBox="0 0 60 40" width="' . $width . '" height="' . $height . '" aria-hidden="true" style="border-radius:2px;vertical-align:middle;box-shadow:0 0 1px rgba(0,0,0,0.3);flex-shrink:0;">'
                . '<clipPath id="dxgb_' . $uid . '"><rect width="60" height="40" rx="3"/></clipPath>'
                . '<g clip-path="url(#dxgb_' . $uid . ')">'
                . '<rect width="60" height="40" fill="#012169"/>'
                . '<path d="M0,0 L60,40 M60,0 L0,40" stroke="#fff" stroke-width="8"/>'
                . '<path d="M0,0 L60,40 M60,0 L0,40" stroke="#c8102e" stroke-width="4"/>'
                . '<path d="M30,0 v40 M0,20 h60" stroke="#fff" stroke-width="12"/>'
                . '<path d="M30,0 v40 M0,20 h60" stroke="#c8102e" stroke-width="7"/>'
                . '</g></svg>';
        case 'ja':
            return '<svg class="lang-flag-svg" viewBox="0 0 30 20" width="' . $width . '" height="' . $height . '" aria-hidden="true" style="border-radius:2px;vertical-align:middle;box-shadow:0 0 1px rgba(0,0,0,0.25);flex-shrink:0;">'
                . '<rect width="30" height="20" rx="2" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8"/>'
                . '<circle cx="15" cy="10" r="6" fill="#bc002d"/>'
                . '</svg>';
        case 'vi':
        default:
            return '<svg class="lang-flag-svg" viewBox="0 0 30 20" width="' . $width . '" height="' . $height . '" aria-hidden="true" style="border-radius:2px;vertical-align:middle;box-shadow:0 0 1px rgba(0,0,0,0.25);flex-shrink:0;">'
                . '<rect width="30" height="20" rx="2" fill="#da251d"/>'
                . '<polygon points="15,4 16.5,8.8 21.5,8.8 17.5,11.8 19,16.5 15,13.5 11,16.5 12.5,11.8 8.5,8.8 13.5,8.8" fill="#ff0"/>'
                . '</svg>';
    }
}

/**
 * Khởi tạo và xác định ngôn ngữ hiện tại
 */
function initI18n() {
    $supported = array_keys(getSupportedLanguages());
    $lang = 'vi';

    // 1. Kiểm tra tham số URL ?lang=
    if (isset($_GET['lang']) && in_array($_GET['lang'], $supported, true)) {
        $lang = $_GET['lang'];
        setCurrentLanguage($lang);
        return $lang;
    }

    // 2. Kiểm tra Session
    if (isset($_SESSION['app_language']) && in_array($_SESSION['app_language'], $supported, true)) {
        $lang = $_SESSION['app_language'];
        return $lang;
    }

    // 3. Kiểm tra Cookie
    if (isset($_COOKIE['app_language']) && in_array($_COOKIE['app_language'], $supported, true)) {
        $lang = $_COOKIE['app_language'];
        $_SESSION['app_language'] = $lang;
        return $lang;
    }

    // 4. Mặc định
    $_SESSION['app_language'] = $lang;
    return $lang;
}

/**
 * Lấy mã ngôn ngữ hiện tại (vi / en / ja)
 */
function getCurrentLanguage() {
    $supported = array_keys(getSupportedLanguages());
    if (isset($_SESSION['app_language']) && in_array($_SESSION['app_language'], $supported, true)) {
        return $_SESSION['app_language'];
    }
    return 'vi';
}

/**
 * Thiết lập ngôn ngữ mới
 */
function setCurrentLanguage($lang) {
    $supported = array_keys(getSupportedLanguages());
    if (!in_array($lang, $supported, true)) {
        $lang = 'vi';
    }
    $_SESSION['app_language'] = $lang;

    // Lưu cookie 365 ngày
    if (!headers_sent()) {
        setcookie('app_language', $lang, [
            'expires'  => time() + (365 * 24 * 3600),
            'path'     => '/',
            'samesite' => 'Lax'
        ]);
    }
    return $lang;
}

/**
 * Tải từ điển ngôn ngữ từ tệp JSON
 */
function getDictionary($lang = null, $forceReload = false) {
    if (!isset($GLOBALS['__DX_DICT_CACHE'])) {
        $GLOBALS['__DX_DICT_CACHE'] = [];
    }
    $lang = $lang ?: getCurrentLanguage();

    if (!$forceReload && isset($GLOBALS['__DX_DICT_CACHE'][$lang])) {
        return $GLOBALS['__DX_DICT_CACHE'][$lang];
    }

    $filePath = __DIR__ . "/../data/languages/{$lang}.json";
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $json = json_decode($content, true);
        if (is_array($json)) {
            $GLOBALS['__DX_DICT_CACHE'][$lang] = $json;
            return $json;
        }
    }

    // Fallback sang bản mặc định
    $defaultPath = __DIR__ . "/../data/languages/defaults/{$lang}.json";
    if (file_exists($defaultPath)) {
        $content = file_get_contents($defaultPath);
        $json = json_decode($content, true);
        if (is_array($json)) {
            $GLOBALS['__DX_DICT_CACHE'][$lang] = $json;
            return $json;
        }
    }

    $GLOBALS['__DX_DICT_CACHE'][$lang] = [];
    return $GLOBALS['__DX_DICT_CACHE'][$lang];
}

/**
 * Lấy tất cả từ điển để cấp cho Frontend JS
 */
function getAllDictionaries() {
    return [
        'vi' => getDictionary('vi'),
        'en' => getDictionary('en'),
        'ja' => getDictionary('ja')
    ];
}

/**
 * Lưu tệp từ điển ngôn ngữ
 */
function saveDictionaryFile($lang, array $data) {
    $supported = array_keys(getSupportedLanguages());
    if (!in_array($lang, $supported, true)) {
        return false;
    }
    $dir = __DIR__ . '/../data/languages';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $filePath = "{$dir}/{$lang}.json";
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $saved = file_put_contents($filePath, $json) !== false;
    if ($saved) {
        if (!isset($GLOBALS['__DX_DICT_CACHE'])) {
            $GLOBALS['__DX_DICT_CACHE'] = [];
        }
        $GLOBALS['__DX_DICT_CACHE'][$lang] = $data;
    }
    return $saved;
}

/**
 * Hàm dịch thuật chuẩn cho PHP Templates
 * @param string $key - Khóa dịch (VD: 'common.btn_save')
 * @param string|null $default - Giá trị mặc định nếu khóa chưa có
 * @param array $params - Tham số thay thế dạng [':name' => 'Value'] hoặc ['name' => 'Value']
 * @return string
 */
function __($key, $default = null, $params = []) {
    $lang = getCurrentLanguage();
    $dict = getDictionary($lang);

    $translated = $dict[$key] ?? null;

    // Nếu không tìm thấy trong ngôn ngữ hiện tại, thử tìm trong tiếng Việt
    if ($translated === null && $lang !== 'vi') {
        $viDict = getDictionary('vi');
        $translated = $viDict[$key] ?? null;
    }

    if ($translated === null) {
        $translated = ($default !== null) ? $default : $key;
    }

    // Thay thế tham số nếu có
    if (!empty($params) && is_array($params)) {
        foreach ($params as $pKey => $pVal) {
            $pKeyClean = ltrim($pKey, ':{}');
            $translated = str_replace(
                [":{$pKeyClean}", "{{$pKeyClean}}", "{" . $pKeyClean . "}"],
                (string)$pVal,
                $translated
            );
        }
    }

    return $translated;
}

/**
 * In thẻ Script chứa dữ liệu i18n cho Frontend
 */
function renderI18nScript() {
    $currentLang = getCurrentLanguage();
    $supportedLangs = getSupportedLanguages();
    $currentDict = getDictionary($currentLang);
    $allDicts = getAllDictionaries();

    $jsVersion = file_exists(__DIR__ . '/../js/i18n.js') ? filemtime(__DIR__ . '/../js/i18n.js') : '1.0';

    echo '<script>';
    echo 'window.__APP_LANG = ' . json_encode($currentLang) . ';';
    echo 'window.__APP_LANGS = ' . json_encode($supportedLangs, JSON_UNESCAPED_UNICODE) . ';';
    echo 'window.__I18N_DICT = ' . json_encode($currentDict, JSON_UNESCAPED_UNICODE) . ';';
    echo 'window.__I18N_ALL_DICTS = ' . json_encode($allDicts, JSON_UNESCAPED_UNICODE) . ';';
    echo '</script>' . "\n";
    echo '<script src="js/i18n.js?v=' . $jsVersion . '"></script>' . "\n";
}

// Tự động khởi tạo ngay khi nạp file
initI18n();
