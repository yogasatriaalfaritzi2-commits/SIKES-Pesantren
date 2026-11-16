<?php

namespace App\Models;

use App\Core\Model;

class ActivityLog extends Model
{
    protected string $table = 'activity_logs';
    protected string $primaryKey = 'id';

    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "(al.description LIKE ? OR u.nama LIKE ? OR u.username LIKE ?)";
            array_push($params, '%' . $filters['q'] . '%', '%' . $filters['q'] . '%', '%' . $filters['q'] . '%');
        }
        if (!empty($filters['action'])) {
            $where[] = "al.action = ?";
            $params[] = $filters['action'];
        }
        if (!empty($filters['date'])) {
            $where[] = "DATE(al.created_at) = ?";
            $params[] = $filters['date'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id{$whereSql}",
            $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT al.*, u.nama AS user_nama, u.username, r.name AS role_name
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             {$whereSql}
             ORDER BY al.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function countToday(string $action): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM activity_logs
             WHERE action = ? AND DATE(created_at) = CURDATE()",
            [$action]
        )['total'] ?? 0);
    }
}