<?php

namespace App\Core;

class Session
{
    /**
     * Naikkan angka ini SETIAP KALI struktur data session berubah.
     */
    private const VERSION = '3';

    /* ---------- Batas login gagal (per-username) ---------- */
    private const MAX_ATTEMPTS = 5;    // fallback; bisa dioverride via .env
    private const LOCK_SECONDS = 300;  // 5 menit

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_name(defined('SESSION_NAME') ? SESSION_NAME : 'SIKES_SESSION');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        // Session buatan versi aplikasi lama -> buang, mulai bersih.
        if (($_SESSION['_version'] ?? null) !== self::VERSION) {
            $_SESSION = [];
            $_SESSION['_version'] = self::VERSION;
        }
    }

    /* ============================================================
       LOGIN THROTTLE — PER-USERNAME (v17.3)
       Setiap akun memiliki hitungan gagalnya sendiri, sehingga
       pengujian banyak akun tidak saling mengunci.
       ============================================================ */

    private static function failKey(string $username): string
    {
        return '_auth_fail_' . md5(strtolower(trim($username)));
    }

    private static function maxAttempts(): int
    {
        return (int) \env('LOGIN_MAX_ATTEMPTS', self::MAX_ATTEMPTS);
    }

    private static function lockSeconds(): int
    {
        return (int) \env('LOGIN_LOCK_SECONDS', self::LOCK_SECONDS);
    }

    public static function failedCount(string $username): int
    {
        $key = self::failKey($username);
        $f   = $_SESSION[$key] ?? null;
        if (!is_array($f)) {
            return 0;
        }
        // Kedaluwarsa -> reset otomatis
        if (time() - (int) $f['last'] >= self::lockSeconds()) {
            unset($_SESSION[$key]);
            return 0;
        }
        return (int) $f['count'];
    }

    public static function isLocked(string $username): bool
    {
        if (self::failedCount($username) < self::maxAttempts()) {
            return false;
        }
        return self::remainingLockSeconds($username) > 0;
    }

    public static function remainingLockSeconds(string $username): int
    {
        $key = self::failKey($username);
        $f   = $_SESSION[$key] ?? null;
        if (!is_array($f)) {
            return 0;
        }
        return max(0, self::lockSeconds() - (time() - (int) $f['last']));
    }

    public static function registerFailedLogin(string $username): void
    {
        $key = self::failKey($username);
        $f   = $_SESSION[$key] ?? ['count' => 0, 'last' => 0];
        $f['count']++;
        $f['last'] = time();
        $_SESSION[$key] = $f;
    }

    public static function clearFailedLogins(string $username): void
    {
        unset($_SESSION[self::failKey($username)]);
    }

    /* ============================================================
       SINKRONISASI PERMISSION & STATUS (v17.3)
       Dipanggil setiap request untuk user yang login:
       - Perubahan permission/role di DB LANGSUNG aktif (tanpa login ulang)
       - User yang dinonaktifkan otomatis keluar
       - Role yang dihapus dari DB -> logout
       ============================================================ */
    public static function refreshPermissions(): void
    {
        $user = self::user();
        if (!$user) {
            return;
        }

        try {
            $db = Database::getInstance();

            // 1. User masih ada & aktif?
            $u = $db->fetch(
                'SELECT is_active FROM users WHERE id = ? LIMIT 1',
                [$user['id']]
            );
            if (!$u || (int) $u['is_active'] !== 1) {
                self::logout();
                return;
            }

            // 2. Role masih ada?
            $role = $db->fetch(
                'SELECT id, name, slug, is_system FROM roles WHERE id = ? LIMIT 1',
                [$user['role_id']]
            );
            if (!$role) {
                self::logout();
                return;
            }

            // 3. Muat ulang permission dari DB (satu query kecil)
            $isSuper     = (bool) $role['is_system'];
            $permissions = [];
            if (!$isSuper) {
                $rows = $db->fetchAll(
                    "SELECT p.slug
                     FROM role_permissions rp
                     JOIN permissions p ON p.id = rp.permission_id
                     WHERE rp.role_id = ?",
                    [$user['role_id']]
                );
                $permissions = array_column($rows, 'slug');
            }

            // 4. Perbarui session di tempat (tanpa regenerate id)
            $_SESSION['user']['role_id']     = (int) $role['id'];
            $_SESSION['user']['role_name']   = $role['name'];
            $_SESSION['user']['role_slug']   = $role['slug'];
            $_SESSION['user']['is_super']    = $isSuper;
            $_SESSION['user']['permissions'] = array_values($permissions);
        } catch (\Throwable $e) {
            // DB bermasalah -> pakai permission lama di session (tidak fatal)
        }
    }

    /* ============================================================
       DATA LOGIN & RBAC
       ============================================================ */

    public static function isLoggedIn(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        $user = $_SESSION['user'] ?? null;

        if (!is_array($user)
            || !isset($user['id'], $user['nama'], $user['username'],
                      $user['role_id'], $user['role_name'], $user['role_slug'])) {
            unset($_SESSION['user']);
            return null;
        }
        return $user;
    }

    public static function login(array $user, array $role, array $permissions): void
    {
        session_regenerate_id(true); // cegah session fixation
        self::clearFailedLogins((string) $user['username']);
        $_SESSION['user'] = [
            'id'          => (int) $user['id'],
            'nama'        => $user['nama'],
            'username'    => $user['username'],
            'role_id'     => (int) $role['id'],
            'role_name'   => $role['name'],
            'role_slug'   => $role['slug'],
            'is_super'    => (bool) $role['is_system'],
            'permissions' => array_values($permissions),
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /* ============================================================
       FLASH MESSAGE
       ============================================================ */

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }

    public static function getFlash(string $type): ?string
    {
        if (isset($_SESSION['flash'][$type])) {
            $msg = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $msg;
        }
        return null;
    }

    /* ============================================================
       CSRF
       ============================================================ */

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}