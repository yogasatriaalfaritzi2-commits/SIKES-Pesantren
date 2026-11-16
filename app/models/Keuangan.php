<?php

namespace App\Models;

use App\Core\Model;

class Keuangan extends Model
{
    protected string $table = 'keuangan_transaksi';

    public const JENIS = ['masuk' => 'Pemasukan', 'keluar' => 'Pengeluaran'];

    public const KATEGORI = [
        'iuran'       => 'Iuran Santri',
        'donasi'      => 'Donasi / Bantuan',
        'pengadaan'   => 'Pengadaan / Belanja',
        'operasional' => 'Operasional Harian',
        'lainnya'     => 'Lainnya',
    ];

    private const BASE = "SELECT t.*, u.nama AS user_nama
        FROM keuangan_transaksi t
        LEFT JOIN users u ON u.id = t.user_id";

    private function filterSql(array $f): array
    {
        $w = [];
        $params = [];
        if (!empty($f['q'])) {
            $w[] = '(t.keterangan LIKE ? OR t.referensi LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['jenis']) && isset(self::JENIS[$f['jenis']])) {
            $w[] = 't.jenis = ?'; $params[] = $f['jenis'];
        }
        if (!empty($f['kategori']) && isset(self::KATEGORI[$f['kategori']])) {
            $w[] = 't.kategori = ?'; $params[] = $f['kategori'];
        }
        if (!empty($f['date_from'])) { $w[] = 't.tanggal >= ?'; $params[] = $f['date_from']; }
        if (!empty($f['date_to']))   { $w[] = 't.tanggal <= ?'; $params[] = $f['date_to']; }
        return [$w ? ' WHERE ' . implode(' AND ', $w) : '', $params];
    }

    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        [$whereSql, $params] = $this->filterSql($f);

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM keuangan_transaksi t{$whereSql}", $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql} ORDER BY t.tanggal DESC, t.id DESC
            LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE t.id = ? LIMIT 1', [$id]);
    }

    /** Total masuk/keluar/saldo sesuai filter (kartu ringkasan). */
    public function totals(array $f): array
    {
        [$whereSql, $params] = $this->filterSql($f);
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(CASE WHEN t.jenis = 'masuk'  THEN t.jumlah END), 0) AS masuk,
                    COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.jumlah END), 0) AS keluar
             FROM keuangan_transaksi t{$whereSql}",
            $params
        ) ?: [];
        $masuk  = (float) ($row['masuk'] ?? 0);
        $keluar = (float) ($row['keluar'] ?? 0);
        return ['masuk' => $masuk, 'keluar' => $keluar, 'saldo' => $masuk - $keluar];
    }
}