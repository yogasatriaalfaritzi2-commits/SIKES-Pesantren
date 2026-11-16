<?php
declare(strict_types=1);

/**
 * Front Controller — SIKES Pondok Pesantren
 * Versi 17.1 : memuat .env SEBELUM config (perbaikan "Perlu API key")
 */

define('ROOT_PATH', dirname(__DIR__));

/* ------------------------------------------------------------------
 * 0. Muat .env + helper env()  (WAJIB sebelum config)
 * ------------------------------------------------------------------ */
 $envLoader = ROOT_PATH . '/app/core/Env.php';
if (is_file($envLoader)) {
    require $envLoader;

    if (function_exists('sikes_env_load')) {
        sikes_env_load(ROOT_PATH . '/.env');
    }
}

/* ------------------------------------------------------------------
 * PENGAMAN: fungsi env() SELALU tersedia — walau Env.php hilang.
 * Tanpa .env, nilai jatuh ke default (aplikasi tetap jalan).
 * ------------------------------------------------------------------ */
if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = getenv($key);
        return ($value === false) ? $default : $value;
    }
}

/* ------------------------------------------------------------------
 * 1. Konfigurasi aplikasi (membaca dari .env)
 * ------------------------------------------------------------------ */
require ROOT_PATH . '/app/config/config.php';
require ROOT_PATH . '/app/config/ai.php';

/* ------------------------------------------------------------------
 * 2. Helper global
 * ------------------------------------------------------------------ */
require ROOT_PATH . '/app/core/helpers.php';

/* ------------------------------------------------------------------
 * 3. Jalankan aplikasi
 * ------------------------------------------------------------------ */
require ROOT_PATH . '/app/core/App.php';

(new App\Core\App())->run();