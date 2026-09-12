<?php
/**
 * Autoloader thủ công cho PhpSpreadsheet (Chạy Offline 100% không cần Composer)
 */
spl_autoload_register(function ($class) {
    // Chỉ xử lý các Class thuộc Namespace PhpOffice\PhpSpreadsheet
    $prefix = 'PhpOffice\\PhpSpreadsheet\\';
    $base_dir = __DIR__ . '/src/PhpSpreadsheet/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Autoload phụ trợ cho Psr\SimpleCache nếu có
spl_autoload_register(function ($class) {
    $prefix = 'Psr\\SimpleCache\\';
    $base_dir = __DIR__ . '/src/Psr/SimpleCache/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});