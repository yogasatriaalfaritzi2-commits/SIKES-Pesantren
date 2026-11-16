<?php

namespace App\Models;

use App\Core\Model;

class Blok extends Model
{
    protected string $table = 'blok';

    public function paginate(int $page, int $perPage, string $q = '', int $asramaId = 0): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if ($q !== '')        { $where[] = '(b.nama LIKE ? OR a.nama LIKE ?)'; array_push($params, "%{$q}%", "%{$q}%"); }
        if ($asramaId > 0)    { $where[] = 'a.id = ?'; $params[] = $asramaId; }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM blok b JOIN asrama a ON a.id = b.asrama_id';

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total{$join}{$whereSql}", $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT b.*, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
                    (SELECT COUNT(*) FROM kamar k WHERE k.blok_id = b.id) AS kamar_count
             {$join}{$whereSql}
             ORDER BY a.nama ASC, b.nama ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    /** Dropdown blok ter-filter per asrama (cascading AJAX). */
    public function optionsByAsrama(int $asramaId): array
    {
        if ($asramaId <= 0) {
            return [];
        }
        return $this->db->fetchAll(
            "SELECT id, nama FROM blok WHERE asrama_id = ? ORDER BY nama ASC",
            [$asramaId]
        );
    }

    /** Semua blok + nama asrama (untuk optgroup di form kamar/dapur). */
    public function allWithAsrama(): array
    {
        return $this->db->fetchAll(
            "SELECT b.id, b.nama, b.asrama_id, a.nama AS asrama_nama, a.jenis AS asrama_jenis
             FROM blok b JOIN asrama a ON a.id = b.asrama_id
             ORDER BY a.nama ASC, b.nama ASC"
        );
    }

    public function kamarCount(int $id): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM kamar WHERE blok_id = ?",
            [$id]
        )['total'] ?? 0);
    }

    /**
     * Cek unik nama blok dalam satu asrama (BARU v16.1 —
     * menggantikan akses $db dari controller).
     */
    public function existsInAsrama(string $nama, int $asramaId, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT id FROM blok WHERE asrama_id = ? AND nama = ?';
        $params = [$asramaId, $nama];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (bool) $this->db->fetch($sql . ' LIMIT 1', $params);
    }
}