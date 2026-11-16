<?php

namespace App\Models;

use App\Core\Model;

class BukuTamu extends Model
{
    protected string $table = 'buku_tamu';

    /** Pilihan hubungan penjenguk (dipakai form & validasi controller). */
    public const HUBUNGAN = [
        'Wali Santri', 'Ayah', 'Ibu', 'Kakak', 'Saudara',
        'Keluarga Lain', 'Teman', 'Lainnya',
    ];

    /**
     * Rantai SELECT standar: tamu + identitas santri yang dijenguk
     * + lokasi penempatan + nama petugas pencatat.
     */
    private const BASE = "SELECT bt.*, s.nis, s.nama AS santri_nama, s.jenis_kelamin,
            k.nama AS kamar_nama, b.nama AS blok_nama, a.nama AS asrama_nama,
            u.nama AS petugas_nama
        FROM buku_tamu bt
        JOIN santri s      ON s.id  = bt.santri_id
        LEFT JOIN kamar k  ON k.id  = s.kamar_id
        LEFT JOIN blok b   ON b.id  = k.blok_id
        LEFT JOIN asrama a ON a.id  = b.asrama_id
        LEFT JOIN users u  ON u.id  = bt.petugas_id";

    /**
     * Daftar tamu + filter:
     *   $f['q']      -> cari nama penjenguk / santri / NIS / telepon
     *   $f['date']   -> filter tanggal kunjungan
     *   $f['inside'] -> true = hanya tamu yang masih di dalam
     */
    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(bt.nama_penjenguk LIKE ? OR s.nama LIKE ? OR s.nis LIKE ? OR bt.no_telepon LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['date'])) {
            $where[] = 'bt.tanggal = ?';
            $params[] = $f['date'];
        }
        if (!empty($f['inside'])) {
            $where[] = 'bt.waktu_pulang IS NULL';
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM buku_tamu bt
             JOIN santri s ON s.id = bt.santri_id{$whereSql}",
            $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql}
            ORDER BY bt.tanggal DESC, bt.waktu_datang DESC, bt.id DESC
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

    /** Detail satu entri tamu + identitas lengkap. */
    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE bt.id = ? LIMIT 1', [$id]);
    }

    /** Jumlah tamu hari ini (kartu dashboard keamanan). */
    public function countToday(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM buku_tamu WHERE tanggal = CURDATE()"
        )['total'] ?? 0);
    }

    /** Jumlah tamu yang masih berada di dalam pondok. */
    public function countInside(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM buku_tamu WHERE waktu_pulang IS NULL"
        )['total'] ?? 0);
    }
}