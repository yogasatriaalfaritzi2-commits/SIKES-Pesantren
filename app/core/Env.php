<?php
/**
 * Loader file .env — TANPA namespace.
 *
 * PENTING: file ini sengaja tidak memakai namespace, agar helper
 * global env() tercipta di root namespace (dipakai oleh config.php
 * dan ai.php yang juga tanpa namespace).
 *
 * Dipanggil oleh public/index.php SEBELUM config dimuat.
 * Tidak menimpa variabel environment yang sudah ada di server.
 */

/**
 * Muat file .env dari path yang diberikan.
 */
function sikes_env_load(string $path): void
{
    static $loaded = false;
    if ($loaded || !is_file($path)) {
        $loaded = true;
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        $loaded = true;
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key   = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        // Buang komentar di akhir baris (hanya bila value tidak dikutip)
        if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
            $hash = strpos($value, ' #');
            if ($hash !== false) {
                $value = rtrim(substr($value, 0, $hash));
            }
        }

        // Buang kutip pembungkus
        $len = strlen($value);
        if ($len >= 2) {
            $f = $value[0];
            $l = $value[$len - 1];
            if (($f === '"' && $l === '"') || ($f === "'" && $l === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if ($key === '') {
            continue;
        }

        // Jangan timpa yang sudah ada di environment server
        if (getenv($key) === false && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
    $loaded = true;
}

/**
 * Helper global env() — WAJIB ada (dipakai config.php & ai.php).
 */
if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        $lower = strtolower($value);
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }
        if ($lower === 'null') {
            return null;
        }
        return $value;
    }
}