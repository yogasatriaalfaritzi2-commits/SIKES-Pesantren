<?php
/**
 * Konfigurasi Aplikasi — SIKES Pondok Pesantren
 * ------------------------------------------------------------
 * Nilai dibaca dari file .env di root project (memakai helper
 * env() dari app/core/Env.php). Fallback aman bila kunci tidak
 * ditemukan — project tetap berjalan tanpa file .env.
 *
 * CATATAN: file ini TIDAK boleh memakai namespace, karena
 * dimuat langsung oleh public/index.php.
 */

/* ---------- Umum ---------- */
define('APP_NAME',    env('APP_NAME', 'SIKES Pondok Pesantren'));
define('APP_ENV',     env('APP_ENV', 'development'));   // development | production
define('APP_VERSION', env('APP_VERSION', '17.0'));

/* ---------- URL Dasar (deteksi otomatis) ---------- */
 $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
 $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
 $dir    = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
define('APP_URL', rtrim($scheme . '://' . $host . $dir, '/'));

/* ---------- Database ---------- */
define('DB_HOST',    env('DB_HOST', '127.0.0.1'));
define('DB_PORT',    env('DB_PORT', '3306'));
define('DB_NAME',    env('DB_NAME', 'sikes_pondok'));
define('DB_USER',    env('DB_USER', 'root'));
define('DB_PASS',    env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

/* ---------- Timezone ---------- */
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

/* ---------- Error reporting (mengikuti APP_ENV / APP_DEBUG) ---------- */
 $debug = filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOL);
if (APP_ENV === 'development' || $debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

/* ---------- Session (dipakai App\Core\Session) ---------- */
if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', env('SESSION_NAME', 'SIKES_SESSION'));
}

/* ---------- Mail / SMTP (Gmail) ---------- */
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', '587'));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));