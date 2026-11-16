<?php

/**
 * Helper global kecil yang dipakai lintas aplikasi.
 */

/** Escape output HTML (anti-XSS). Selalu gunakan saat mencetak data. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Bangun URL lengkap dari path relatif, mis. base_url('/login'). */
function base_url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Path dasar aplikasi TANPA akhiran '/public'.
 * Menangani 3 skenario: vhost ke public/, subfolder .../public, dan
 * rewrite dari .htaccess root.
 */
function app_base_path(): string
{
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if (substr($dir, -7) === '/public') {
        $dir = substr($dir, 0, -7);
    }
    return $dir; // '' atau '/sikes-pondok-pesantren'
}

/** Path request saat ini (sudah bersih), mis. '/dashboard'. */
function current_path(): string
{
    $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = app_base_path();

    if ($base !== '' && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base));
    }
    // Sisa '/public' bila URL diakses langsung memakai /public
    if ($uri === '/public' || strpos($uri, '/public/') === 0) {
        $uri = '/' . ltrim(substr($uri, 7), '/');
    }
    $uri = '/' . trim($uri, '/');
    return $uri === '//' ? '/' : $uri;
}

/** Catat aktivitas ke tabel activity_logs (kegagalan log tidak memutus alur aplikasi). */
function log_activity(string $action, string $module = 'sistem', string $description = ''): void
{
    try {
        $user = \App\Core\Session::user();
        (new \App\Models\ActivityLog())->create([
            'user_id'     => $user['id'] ?? null,
            'action'      => $action,
            'module'      => $module,
            'description' => $description,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (\Throwable $e) {
        // diamkan: logging tidak boleh mengganggu proses utama
    }
}



/** Kirim respons JSON lalu hentikan eksekusi (untuk endpoint AJAX). */
function json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}