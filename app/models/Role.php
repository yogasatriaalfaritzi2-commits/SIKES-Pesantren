<?php

namespace App\Models;

use App\Core\Model;

class Role extends Model
{
    protected string $table = 'roles';

    public function allOrdered(): array
    {
        return $this->db->fetchAll("SELECT * FROM roles ORDER BY id ASC");
    }

    /** Role + jumlah pengguna & permission (untuk daftar role). */
    public function allWithStats(): array
    {
        return $this->db->fetchAll(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id)              AS users_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permissions_count
             FROM roles r ORDER BY r.id ASC"
        );
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql    = "SELECT id FROM roles WHERE slug = ?";
        $params = [$slug];
        if ($exceptId !== null) {
            $sql .= " AND id <> ?";
            $params[] = $exceptId;
        }
        return (bool) $this->db->fetch($sql . " LIMIT 1", $params);
    }

    /** Slug permission milik role (disimpan ke session saat login). */
    public function permissionSlugs(int $roleId): array
    {
        return array_column(
            $this->db->fetchAll(
                "SELECT p.slug FROM role_permissions rp
                 JOIN permissions p ON p.id = rp.permission_id
                 WHERE rp.role_id = ?",
                [$roleId]
            ),
            'slug'
        );
    }

    /** ID permission milik role (untuk form checkbox). */
    public function permissionIds(int $roleId): array
    {
        return array_map('intval', array_column(
            $this->db->fetchAll("SELECT permission_id FROM role_permissions WHERE role_id = ?", [$roleId]),
            'permission_id'
        ));
    }

    /** Ganti seluruh permission role (sinkronisasi penuh). */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->db->execute("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
        foreach ($permissionIds as $pid) {
            $this->db->execute(
                "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                [$roleId, $pid]
            );
        }
    }

    public function usersCount(int $roleId): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM users WHERE role_id = ?",
            [$roleId]
        )['total'] ?? 0);
    }
}