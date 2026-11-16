<?php

namespace App\Models;

use App\Core\Model;

class Pengasuhan extends Model
{
    protected string $table = 'pengasuhan_catatan';

    public const JENIS = ['izin' => 'Perizinan', 'pelanggaran' => 'Pelanggaran'];

    public const STATUS_LABEL = ['berjalan' => 'Berjalan', 'selesai' => 'Selesai'];
    public const STATUS_COLOR = ['berjalan' => 'warning', 'selesai' => 'success'];

    private const BASE = "SELECT pc.*, s.nis, s.nama AS santri_nama, s.jenis_kelamin,
            k.nama AS kamar_nama, b.nama AS blok_nama, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
            u.nama AS petugas_nama
        FROM pengasuhan_catatan pc
        JOIN santri s      ON s.id  = pc.santri_id
        LEFT JOIN kamar k  ON k.id  = s.kamar_id
        LEFT JOIN blok b   ON b.id  = k.blok_id
        LEFT JOIN asrama a ON a.id  = b.asrama_id
        LEFT JOIN users u  ON u.id  = pc.petugas_id";

    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(s.nama LIKE ? OR s.nis LIKE ? OR pc.keterangan LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['jenis']) && isset(self::JENIS[$f['jenis']])) {
            $where[] = 'pc.jenis = ?'; $params[] = $f['jenis'];
        }
        if (!empty($f['status']) && isset(self::STATUS_LABEL[$f['status']])) {
            $where[] = 'pc.status = ?'; $params[] = $f['status'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM pengasuhan_catatan pc JOIN santri s ON s.id = pc.santri_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql} ORDER BY pc.tanggal DESC, pc.id DESC
            LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE pc.id = ? LIMIT 1', [$id]);
    }

    /** Rekap kartu dashboard modul. */
    public function counts(): array
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(jenis = 'izin'    AND status = 'berjalan'), 0) AS izin,
                    COALESCE(SUM(jenis = 'pelanggaran' AND status = 'berjalan'), 0) AS pelanggaran,
                    COALESCE(SUM(status = 'selesai'), 0) AS selesai
             FROM pengasuhan_catatan"
        ) ?: [];
        return [
            'izin'        => (int) ($row['izin'] ?? 0),
            'pelanggaran' => (int) ($row['pelanggaran'] ?? 0),
            'selesai'     => (int) ($row['selesai'] ?? 0),
        ];
    }
}