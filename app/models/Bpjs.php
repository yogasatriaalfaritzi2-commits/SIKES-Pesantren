<?php

namespace App\Models;

use App\Core\Model;

class Bpjs extends Model
{
    protected string $table = 'bpjs_santri';

    public const STATUS_LABEL = ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'];
    public const STATUS_COLOR = ['aktif' => 'success', 'nonaktif' => 'secondary'];

    private const BASE = "SELECT bp.*, s.nis, s.nama AS santri_nama, s.jenis_kelamin,
            k.nama AS kamar_nama, b.nama AS blok_nama, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
            u.nama AS petugas_nama
        FROM bpjs_santri bp
        JOIN santri s      ON s.id  = bp.santri_id
        LEFT JOIN kamar k  ON k.id  = s.kamar_id
        LEFT JOIN blok b   ON b.id  = k.blok_id
        LEFT JOIN asrama a ON a.id  = b.asrama_id
        LEFT JOIN users u  ON u.id  = bp.petugas_id";

    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(s.nama LIKE ? OR s.nis LIKE ? OR bp.nomor_bpjs LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['status']) && isset(self::STATUS_LABEL[$f['status']])) {
            $where[] = 'bp.status = ?'; $params[] = $f['status'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM bpjs_santri bp JOIN santri s ON s.id = bp.santri_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql} ORDER BY s.nama ASC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE bp.id = ? LIMIT 1', [$id]);
    }

    /** Satu record per santri. */
    public function findForSantri(int $santriId): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE bp.santri_id = ? LIMIT 1', [$santriId]);
    }

    public function nomorExists(string $nomor, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT id FROM bpjs_santri WHERE nomor_bpjs = ?';
        $params = [$nomor];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (bool) $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    public function countActive(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM bpjs_santri WHERE status = 'aktif'"
        )['total'] ?? 0);
    }
}