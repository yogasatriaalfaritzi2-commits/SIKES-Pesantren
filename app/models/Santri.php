<?php

namespace App\Models;

use App\Core\Model;

class Santri extends Model
{
    protected string $table = 'santri';

    private const BASE = "SELECT s.*,
            w.nama AS wali_nama, w.no_telepon AS wali_telepon,
            k.nama AS kamar_nama,
            b.id AS blok_id, b.nama AS blok_nama,
            a.id AS asrama_id, a.nama AS asrama_nama, a.jenis AS asrama_jenis
        FROM santri s
        LEFT JOIN wali_santri w ON w.id = s.wali_id
        LEFT JOIN kamar k       ON k.id = s.kamar_id
        LEFT JOIN blok b        ON b.id = k.blok_id
        LEFT JOIN asrama a      ON a.id = b.asrama_id";

    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(s.nama LIKE ? OR s.nis LIKE ? OR s.nisn LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['jk']))     { $where[] = 's.jenis_kelamin = ?'; $params[] = $f['jk']; }
        if (!empty($f['asrama'])) { $where[] = 'a.id = ?';            $params[] = (int) $f['asrama']; }
        if (!empty($f['status'])) { $where[] = 's.status = ?';        $params[] = $f['status']; }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM santri s
                      LEFT JOIN kamar k  ON k.id = s.kamar_id
                      LEFT JOIN blok b   ON b.id = k.blok_id
                      LEFT JOIN asrama a ON a.id = b.asrama_id';

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total{$join}{$whereSql}", $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql} ORDER BY s.nama ASC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows'=>$rows, 'total'=>$total, 'page'=>$page, 'pages'=>max(1, (int) ceil($total / $perPage))];
    }

    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE s.id = ? LIMIT 1', [$id]);
    }

    public function nisExists(string $nis, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM santri WHERE nis = ?'; $params = [$nis];
        if ($exceptId !== null) { $sql .= ' AND id <> ?'; $params[] = $exceptId; }
        return (bool) $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    public function nisnExists(string $nisn, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM santri WHERE nisn = ?'; $params = [$nisn];
        if ($exceptId !== null) { $sql .= ' AND id <> ?'; $params[] = $exceptId; }
        return (bool) $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    public function countActive(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM santri WHERE status = 'aktif'"
        )['total'] ?? 0);
    }

    public function countInactive(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM santri WHERE status = 'nonaktif'"
        )['total'] ?? 0);
    }

    public function countPutra(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM santri WHERE jenis_kelamin = 'L'"
        )['total'] ?? 0);
    }

    public function countPutri(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM santri WHERE jenis_kelamin = 'P'"
        )['total'] ?? 0);
    }

    public function countBelumKamar(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM santri WHERE kamar_id IS NULL"
        )['total'] ?? 0);
    }

    public function allDetailed(): array
    {
        return $this->db->fetchAll(
            self::BASE . " ORDER BY s.id ASC"
        );
    }
}
