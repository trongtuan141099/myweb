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

// Polyfills và Bridge Classes cho môi trường Offline
if (!class_exists('Composer\\Pcre\\Preg')) {
    eval('
    namespace Composer\\Pcre {
        class Preg {
            public static function isMatch(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): bool {
                return (bool) preg_match($pattern, $subject, $matches, $flags, $offset);
            }
            public static function match(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int {
                $res = preg_match($pattern, $subject, $matches, $flags, $offset);
                return $res === false ? 0 : $res;
            }
            public static function matchAll(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int {
                $res = preg_match_all($pattern, $subject, $matches, $flags, $offset);
                return $res === false ? 0 : $res;
            }
            public static function matchAllWithOffsets(string $pattern, string $subject, array &$matches, int $offset = 0): int {
                $res = preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE, $offset);
                return $res === false ? 0 : $res;
            }
            public static function replace($pattern, $replacement, $subject, int $limit = -1, ?int &$count = null) {
                return preg_replace($pattern, $replacement, $subject, $limit, $count);
            }
            public static function replaceCallback($pattern, callable $replacement, $subject, int $limit = -1, ?int &$count = null, int $flags = 0) {
                return preg_replace_callback($pattern, $replacement, $subject, $limit, $count, $flags);
            }
            public static function split(string $pattern, string $subject, int $limit = -1, int $flags = 0): array {
                $res = preg_split($pattern, $subject, $limit, $flags);
                return $res === false ? [] : $res;
            }
        }
    }
    ');
}

if (!interface_exists('Psr\\SimpleCache\\CacheInterface')) {
    eval('
    namespace Psr\\SimpleCache {
        interface CacheInterface {
            public function get(string $key, mixed $default = null): mixed;
            public function set(string $key, mixed $value, null|int|\\DateInterval $ttl = null): bool;
            public function delete(string $key): bool;
            public function clear(): bool;
            public function getMultiple(iterable $keys, mixed $default = null): iterable;
            public function setMultiple(iterable $values, null|int|\\DateInterval $ttl = null): bool;
            public function deleteMultiple(iterable $keys): bool;
            public function has(string $key): bool;
        }
    }
    ');
}

if (!class_exists('ZipStream\\ZipStream')) {
    eval('
    namespace ZipStream {
        class ZipStream {
            private string $tempFile;
            private \\ZipArchive $zip;
            private $outputStream;

            public function __construct(
                bool $enableZip64 = false,
                $outputStream = null,
                bool $sendHttpHeaders = false,
                bool $defaultEnableZeroHeader = false
            ) {
                $this->outputStream = $outputStream;
                $this->tempFile = tempnam(sys_get_temp_dir(), "zip");
                $this->zip = new \\ZipArchive();
                $this->zip->open($this->tempFile, \\ZipArchive::CREATE | \\ZipArchive::OVERWRITE);
            }

            public function addFile(string $fileName, string $data): void {
                $this->zip->addFromString($fileName, $data);
            }

            public function finish(): void {
                $this->zip->close();
                if (is_resource($this->outputStream)) {
                    $fp = fopen($this->tempFile, "rb");
                    stream_copy_to_stream($fp, $this->outputStream);
                    fclose($fp);
                }
                if (file_exists($this->tempFile)) {
                    @unlink($this->tempFile);
                }
            }
        }
    }
    ');
}