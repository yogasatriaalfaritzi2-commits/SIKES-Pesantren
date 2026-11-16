<?php

namespace App\Models;

use App\Core\Model;

class WaliSantri extends Model
{
    protected string $table = 'wali_santri';

    public function paginate(int $page, int $perPage, string $q = ''): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = '';
        $params = [];

        if ($q !== '') {
            $where  = " WHERE w.nama LIKE ? OR w.no_telepon LIKE ?";
            $params = ["%{$q}%", "%{$q}%"];
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM wali_santri w{$where}", $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT w.*,
                    (SELECT COUNT(*) FROM santri s WHERE s.wali_id = w.id) AS santri_count
             FROM wali_santri w{$where}
             ORDER BY w.nama ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows'=>$rows, 'total'=>$total, 'page'=>$page, 'pages'=>max(1, (int) ceil($total / $perPage))];
    }

    /** Dropdown wali (form santri). */
    public function options(): array
    {
        return $this->db->fetchAll("SELECT id, nama FROM wali_santri ORDER BY nama ASC");
    }

    public function santriCount(int $id): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM santri WHERE wali_id = ?", [$id]
        )['total'] ?? 0);
    }
}