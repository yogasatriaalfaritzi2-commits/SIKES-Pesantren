<?php

namespace App\Models;

use App\Core\Model;

class SkriningRiwayat extends Model
{
    protected string $table = 'skrining_riwayat';

    /** Riwayat satu skrining (terbaru dulu, maks 10). */
    public function forSkrining(int $skriningId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.nama AS user_nama
             FROM skrining_riwayat r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.skrining_id = ?
             ORDER BY r.id DESC LIMIT 10",
            [$skriningId]
        );
    }

    /** Riwayat global + filter santri/pencarian (halaman riwayat pemeriksaan). */
    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['santri_id'])) { $where[] = 'sk.santri_id = ?'; $params[] = (int) $f['santri_id']; }
        if (!empty($f['q'])) {
            $where[] = '(s.nama LIKE ? OR s.nis LIKE ? OR r.data_sesudah LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM skrining_riwayat r
                      JOIN skrining_kesehatan sk ON sk.id = r.skrining_id
                      JOIN santri s              ON s.id = sk.santri_id
                      LEFT JOIN users u          ON u.id = r.user_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT r.*, s.nama AS santri_nama, s.nis, u.nama AS user_nama
             {$join}{$whereSql}
             ORDER BY r.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }
}