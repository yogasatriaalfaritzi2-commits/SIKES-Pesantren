<?php

namespace App\Core;

/**
 * Router dengan middleware akses sederhana.
 * Parameter ke-3 rute = permission yang dibutuhkan:
 *   null            -> publik
 *   'auth'          -> wajib login saja
 *   'users.view'    -> wajib login + punya permission
 */
class Router
{
    /** @var array<int, array{method:string, path:string, handler:string, permission:?string}> */
    private array $routes = [];

    public function get(string $path, string $handler, ?string $permission = null): void  { $this->add('GET', $path, $handler, $permission); }
    public function post(string $path, string $handler, ?string $permission = null): void { $this->add('POST', $path, $handler, $permission); }

    private function add(string $method, string $path, string $handler, ?string $permission): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'permission');
    }

    public function dispatch(): void
    {
        $httpMethod = strtoupper($_SERVER['REQUEST_METHOD']);
        $uri        = current_path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $httpMethod) {
                continue;
            }
            if (preg_match($this->toRegex($route['path']), $uri, $matches)) {
                // ===== Middleware: cek akses sebelum controller dijalankan =====
                if ($route['permission'] !== null) {
                    if (!Session::isLoggedIn()) {
                        Session::setFlash('warning', 'Silakan login terlebih dahulu.');
                        header('Location: ' . base_url('/login'));
                        exit;
                    }
                    if ($route['permission'] !== 'auth' && !Auth::hasPermission($route['permission'])) {
                        $this->forbidden();
                        return;
                    }
                }

                $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
                $this->invoke($route['handler'], $params);
                return;
            }
        }

        $this->notFound();
    }

    private function toRegex(string $path): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?<$1>[a-zA-Z0-9_-]+)', $path);
        return '#^' . $regex . '$#';
    }

    private function invoke(string $handler, array $params): void
    {
        [$controllerName, $action] = explode('@', $handler);
        $class = "App\\Controllers\\{$controllerName}";

        if (!class_exists($class) || !method_exists($class, $action)) {
            $this->notFound();
            return;
        }
        (new $class())->{$action}(...$params);
    }

    private function forbidden(): void
    {
        http_response_code(403);
        $file = ROOT_PATH . '/app/views/errors/403.php';
        if (is_file($file)) {
            require $file;
            return;
        }
        exit('403 - Akses ditolak.');
    }

    private function notFound(): void
    {
        http_response_code(404);
        $file = ROOT_PATH . '/app/views/errors/404.php';
        if (is_file($file)) {
            require $file;
            return;
        }
        exit('404 - Halaman tidak ditemukan.');
    }
}