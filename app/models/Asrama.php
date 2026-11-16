<?php

namespace App\Models;

use App\Core\Model;

class Asrama extends Model
{
    protected string $table = 'asrama';

    public function paginate(int $page, int $perPage, string $q = '', string $jenis = ''): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if ($q !== '')     { $where[] = "(a.nama LIKE ? OR a.lokasi LIKE ?)"; array_push($params, "%{$q}%", "%{$q}%"); }
        if ($jenis !== '') { $where[] = "a.jenis = ?"; $params[] = $jenis; }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM asrama a{$whereSql}", $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM blok b WHERE b.asrama_id = a.id) AS blok_count,
                    (SELECT COUNT(*) FROM kamar k JOIN blok b ON b.id = k.blok_id WHERE b.asrama_id = a.id) AS kamar_count,
                    (SELECT COUNT(*) FROM santri s
                       JOIN kamar k ON k.id = s.kamar_id
                       JOIN blok b  ON b.id = k.blok_id
                      WHERE b.asrama_id = a.id AND s.status = 'aktif') AS penghuni_count
             FROM asrama a{$whereSql}
             ORDER BY a.nama ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows'=>$rows, 'total'=>$total, 'page'=>$page, 'pages'=>max(1, (int) ceil($total / $perPage))];
    }

    /** Dropdown asrama (form santri & blok). */
    public function options(): array
    {
        return $this->db->fetchAll("SELECT id, nama, jenis FROM asrama ORDER BY nama ASC");
    }

    public function blokCount(int $id): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM blok WHERE asrama_id = ?", [$id]
        )['total'] ?? 0);
    }
}