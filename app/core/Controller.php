<?php

namespace App\Core;

abstract class Controller
{
    /**
     * Render view dengan layout (default 'layouts/main').
     * Jika $layout = null, view dirender apa adanya (mis. halaman login).
     *
     * CATATAN: parameter diberi nama $vars (bukan $data) agar tidak
     * bentrok dengan kunci 'data' yang dikirim controller ke view.
     * Dengan EXTR_SKIP, kunci yang bentrok dengan nama variabel yang
     * sudah ada akan DILEWATI — itu sebabnya nama parameter harus unik.
     */
    protected function view(string $view, array $vars = [], ?string $layout = 'layouts/main'): void
    {
        $safeView    = str_replace('..', '', $view);
        $contentFile = ROOT_PATH . '/app/views/' . $safeView . '.php';

        if (!is_file($contentFile)) {
            http_response_code(500);
            exit('View tidak ditemukan: ' . e($view));
        }

        extract($vars, EXTR_SKIP);

        if ($layout === null) {
            require $contentFile;
            return;
        }
        require ROOT_PATH . '/app/views/' . str_replace('..', '', $layout) . '.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . base_url($path));
        exit;
    }

    protected function requireLogin(): void
    {
        if (!Session::isLoggedIn()) {
            Session::setFlash('warning', 'Silakan login terlebih dahulu.');
            $this->redirect('/login');
        }
    }

    /** Wajib login + permission; tampilkan 403 jika ditolak. */
    protected function requirePermission(string $permission): void
    {
        $this->requireLogin();
        if (!Auth::hasPermission($permission)) {
            http_response_code(403);
            require ROOT_PATH . '/app/views/errors/403.php';
            exit;
        }
    }

    /** Validasi CSRF untuk form POST; redirect jika tidak valid. */
    protected function checkCsrf(string $redirectPath): void
    {
        if (!Session::verifyCsrf($_POST['csrf_token'] ?? null)) {
            Session::setFlash('error', 'Sesi tidak valid, silakan coba lagi.');
            $this->redirect($redirectPath);
        }
    }

    protected function input(string $key, ?string $default = null): ?string
    {
        $value = $_POST[$key] ?? $default;
        return $value === null ? null : trim((string) $value);
    }
}