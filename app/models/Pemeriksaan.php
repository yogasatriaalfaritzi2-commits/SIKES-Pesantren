<?php

namespace App\Models;

use App\Core\Model;

class Pemeriksaan extends Model
{
    protected string $table = 'pemeriksaan';

    public const STATUS_LABEL = [
        'menunggu' => 'Menunggu',
        'selesai'  => 'Selesai',
        'batal'    => 'Batal',
    ];

    public const JENIS_LABEL = [
        'rawat_jalan' => 'Rawat Jalan',
        'darurat'     => 'Darurat',
        'kontrol'     => 'Kontrol',
        'preventif'   => 'Preventif (Tracing)',
    ];

    /**
     * Rantai JOIN standar (kamar hanya punya blok_id; blok punya asrama_id).
     * Alias: p = pemeriksaan, s = santri, k = kamar, b = blok, a = asrama,
     *        u1 = pemeriksa, u2 = petugas.
     */
    private const BASE = "SELECT p.*,
            s.nis, s.nisn, s.nama AS santri_nama, s.jenis_kelamin, s.tanggal_lahir,
            s.status AS santri_status,
            k.nama AS kamar_nama, b.nama AS blok_nama,
            a.nama AS asrama_nama, a.jenis AS asrama_jenis,
            u1.nama AS pemeriksa_nama, u2.nama AS petugas_nama
        FROM pemeriksaan p
        JOIN santri s          ON s.id  = p.santri_id
        LEFT JOIN kamar k      ON k.id  = s.kamar_id
        LEFT JOIN blok b       ON b.id  = k.blok_id
        LEFT JOIN asrama a     ON a.id  = b.asrama_id
        LEFT JOIN users u1     ON u1.id = p.pemeriksa_id
        LEFT JOIN users u2     ON u2.id = p.petugas_id";

    /** Daftar kunjungan: cari nama/NIS + filter tanggal & status. */
    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(s.nama LIKE ? OR s.nis LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['date_from'])) { $where[] = 'DATE(p.tanggal_kunjungan) >= ?'; $params[] = $f['date_from']; }
        if (!empty($f['date_to']))   { $where[] = 'DATE(p.tanggal_kunjungan) <= ?'; $params[] = $f['date_to']; }
        if (!empty($f['status']) && isset(self::STATUS_LABEL[$f['status']])) {
            $where[] = 'p.status = ?'; $params[] = $f['status'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM pemeriksaan p JOIN santri s ON s.id = p.santri_id';

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total{$join}{$whereSql}", $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql} ORDER BY p.tanggal_kunjungan DESC, p.id DESC
            LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    /** Detail satu kunjungan — mengembalikan SELURUH kolom + identitas santri. */
    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE p.id = ? LIMIT 1', [$id]);
    }

    /** Seluruh kunjungan seorang santri (halaman Riwayat Kesehatan). */
    public function allForSantri(int $santriId): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, u1.nama AS pemeriksa_nama
             FROM pemeriksaan p
             LEFT JOIN users u1 ON u1.id = p.pemeriksa_id
             WHERE p.santri_id = ?
             ORDER BY p.tanggal_kunjungan DESC, p.id DESC",
            [$santriId]
        );
    }

    public function countWaiting(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM pemeriksaan WHERE status = 'menunggu'"
        )['total'] ?? 0);
    }

    public function countToday(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM pemeriksaan
             WHERE DATE(tanggal_kunjungan) = CURDATE() AND status <> 'batal'"
        )['total'] ?? 0);
    }
}