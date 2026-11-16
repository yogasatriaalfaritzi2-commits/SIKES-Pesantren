<?php

namespace App\Core;

/**
 * Helper otorisasi: membaca identitas yang tersimpan di session.
 */
final class Auth
{
    public static function check(): bool
    {
        return Session::isLoggedIn();
    }

    public static function user(): ?array
    {
        return Session::user();
    }

    public static function id(): ?int
    {
        return Session::user()['id'] ?? null;
    }

    /** Super Admin (role sistem) lolos semua pemeriksaan permission. */
    public static function isSuper(): bool
    {
        return (bool) (Session::user()['is_super'] ?? false);
    }

    public static function name(): ?string
    {
        return Session::user()['nama'] ?? null;
    }

    public static function roleName(): ?string
    {
        return Session::user()['role_name'] ?? null;
    }

    public static function roleSlug(): ?string
    {
        return Session::user()['role_slug'] ?? null;
    }

    public static function hasRole(string $slug): bool
    {
        return self::roleSlug() === $slug;
    }

    public static function hasPermission(string $permission): bool
    {
        $user = Session::user();
        if (!$user) {
            return false;
        }
        if (!empty($user['is_super'])) {
            return true;
        }
        return in_array($permission, $user['permissions'] ?? [], true);
    }

    public static function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if (self::hasPermission($p)) {
                return true;
            }
        }
        return false;
    }
}
