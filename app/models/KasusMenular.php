<?php

namespace App\Models;

use App\Core\Model;

class KasusMenular extends Model
{
    protected string $table = 'kasus_menular';

    public const STATUS_LABEL = ['aktif' => 'Aktif', 'selesai' => 'Selesai'];

    private const BASE = "SELECT km.*, s.nis, s.nama AS santri_nama, s.jenis_kelamin,
            k.id AS kamar_id, k.nama AS kamar_nama, b.nama AS blok_nama,
            a.nama AS asrama_nama, a.jenis AS asrama_jenis,
            u.nama AS pelapor_nama,
            (SELECT COUNT(*) FROM tracing_kontak tk WHERE tk.kasus_id = km.id) AS kontak_count,
            (SELECT COUNT(*) FROM tracing_kontak tk
              WHERE tk.kasus_id = km.id AND tk.status <> 'sudah_diperiksa') AS kontak_belum_selesai
        FROM kasus_menular km
        JOIN santri s      ON s.id  = km.santri_id
        LEFT JOIN kamar k  ON k.id  = s.kamar_id
        LEFT JOIN blok b   ON b.id  = k.blok_id
        LEFT JOIN asrama a ON a.id  = b.asrama_id
        LEFT JOIN users u  ON u.id  = km.dilaporkan_id";

    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(km.penyakit LIKE ? OR s.nama LIKE ? OR s.nis LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['status']) && in_array($f['status'], ['aktif', 'selesai'], true)) {
            $where[] = 'km.status_kasus = ?';
            $params[] = $f['status'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM kasus_menular km JOIN santri s ON s.id = km.santri_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql} ORDER BY km.status_kasus = 'aktif' DESC, km.tanggal_mulai DESC, km.id DESC
            LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE km.id = ? LIMIT 1', [$id]);
    }

    public function hasActiveCase(int $santriId): bool
    {
        return (bool) $this->db->fetch(
            "SELECT id FROM kasus_menular WHERE santri_id = ? AND status_kasus = 'aktif' LIMIT 1",
            [$santriId]
        );
    }

    /**
     * Kamar-kamar dengan kasus AKTIF (untuk warning).
     * Santri tanpa kamar ditampilkan dengan kamar '-'.
     */
    public function roomsWithActiveCases(): array
    {
        return $this->db->fetchAll(
            "SELECT km.id AS kasus_id, km.penyakit, km.tanggal_mulai,
                    s.id AS santri_id, s.nama AS santri_nama, s.nis,
                    k.id AS kamar_id, k.nama AS kamar_nama,
                    b.nama AS blok_nama, a.nama AS asrama_nama,
                    (SELECT COUNT(*) FROM tracing_kontak tk WHERE tk.kasus_id = km.id) AS kontak_count
             FROM kasus_menular km
             JOIN santri s      ON s.id = km.santri_id
             LEFT JOIN kamar k  ON k.id = s.kamar_id
             LEFT JOIN blok b   ON b.id = k.blok_id
             LEFT JOIN asrama a ON a.id = b.asrama_id
             WHERE km.status_kasus = 'aktif'
             ORDER BY km.tanggal_mulai DESC"
        );
    }

    public function countActive(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM kasus_menular WHERE status_kasus = 'aktif'"
        )['total'] ?? 0);
    }

    /**
     * Ambil penghuni kamar santri index (AKTIF, dikecualikan sang santri).
     * Ini sumber "daftar kontak potensial" — berdasarkan tempat tinggal sama,
     * BUKAN berdasarkan kesimpulan medis.
     */
    public function potentialContacts(int $santriId): array
    {
        $santri = $this->db->fetch('SELECT kamar_id FROM santri WHERE id = ? LIMIT 1', [$santriId]);
        if (!$santri || $santri['kamar_id'] === null) {
            return [];
        }
        return $this->db->fetchAll(
            "SELECT id, nis, nama, jenis_kelamin FROM santri
             WHERE kamar_id = ? AND id <> ? AND status = 'aktif'
             ORDER BY nama ASC",
            [$santri['kamar_id'], $santriId]
        );
    }
}