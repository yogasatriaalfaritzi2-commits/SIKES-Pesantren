<?php

namespace App\Core;

/**
 * Bootstrap aplikasi: autoloader, session, security headers, dan routing.
 * v17.3: sinkronisasi permission otomatis per request.
 */
class App
{
    public function __construct()
    {
        $this->registerAutoloader();
        Session::start();
    }

    public function run(): void
    {
        $this->sendSecurityHeaders();

        // ============================================================
        // SINKRONISASI PERMISSION (v17.3):
        // - Perubahan role/permission di halaman Role & Hak Akses
        //   LANGSUNG aktif untuk semua user (tanpa login ulang).
        // - User yang dinonaktifkan otomatis keluar dari session.
        // ============================================================
        if (Session::isLoggedIn()) {
            Session::refreshPermissions();
        }

        $router = new Router();
        require ROOT_PATH . '/app/config/routes.php';
        $router->dispatch();
    }

    /**
     * Security headers (Tahap 15).
     */
    private function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: "
            . "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; "
            . "img-src 'self' data:;");
    }

    /**
     * Autoloader sederhana berbasis namespace.
     */
    private function registerAutoloader(): void
    {
        spl_autoload_register(function (string $class): void {
            $prefix = 'App\\';
            if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file = ROOT_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }
}