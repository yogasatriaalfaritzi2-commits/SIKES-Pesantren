<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    private const SELECT_WITH_ROLE =
        "SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.is_system AS role_is_system
         FROM users u JOIN roles r ON r.id = u.role_id";

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetch(self::SELECT_WITH_ROLE . " WHERE u.username = ? LIMIT 1", [$username]);
    }

    public function findWithRole(int $id): ?array
    {
        return $this->db->fetch(self::SELECT_WITH_ROLE . " WHERE u.id = ? LIMIT 1", [$id]);
    }

    /** Pencarian + paginasi sederhana. */
    public function paginate(int $page, int $perPage, string $q = ''): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = '';
        $params = [];

        if ($q !== '') {
            $where  = " WHERE u.nama LIKE ? OR u.username LIKE ? OR u.email LIKE ?";
            $params = array_fill(0, 3, "%{$q}%");
        }

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total FROM users u{$where}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::SELECT_WITH_ROLE . "{$where} ORDER BY u.nama ASC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = "SELECT id FROM users WHERE username = ?";
        $params = [$username];
        if ($exceptId !== null) {
            $sql .= " AND id <> ?";
            $params[] = $exceptId;
        }
        return (bool) $this->db->fetch($sql . " LIMIT 1", $params);
    }

    public function updateLastLogin(int $id): bool
    {
        return $this->db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$id]) >= 0;
    }
}