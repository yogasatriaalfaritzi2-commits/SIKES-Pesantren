<?php

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class ObatTransaksi extends Model
{
    protected string $table = 'obat_transaksi';

    public const JENIS_LABEL = ['masuk' => 'Stok Masuk', 'keluar' => 'Stok Keluar / Pemakaian'];

    private const BASE = "SELECT t.*, o.nama AS obat_nama, o.satuan, o.minimum_stok,
            u.nama AS user_nama, p.id AS pem_id
        FROM obat_transaksi t
        JOIN obat o  ON o.id  = t.obat_id
        LEFT JOIN users u ON u.id = t.user_id
        LEFT JOIN pemeriksaan p ON p.id = t.pemeriksaan_id";

    /* ============================================================
       STOK MASUK — database transaction (konsistensi stok)
       ============================================================ */
    public function masukStok(int $obatId, int $jumlah, float $hargaSatuan, string $keterangan, int $userId): int
    {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $obat = $db->fetch('SELECT id, nama FROM obat WHERE id = ? FOR UPDATE', [$obatId]);
            if (!$obat) {
                throw new RuntimeException('Obat tidak ditemukan.');
            }

            $db->execute('UPDATE obat SET stok = stok + ? WHERE id = ?', [$jumlah, $obatId]);
            $db->execute(
                'INSERT INTO obat_transaksi (obat_id, jenis, jumlah, harga_satuan, keterangan, user_id)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$obatId, 'masuk', $jumlah, $hargaSatuan, $keterangan !== '' ? $keterangan : null, $userId]
            );
            $id = (int) $db->lastInsertId();

            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /* ============================================================
       STOK KELUAR / PEMAKAIAN — database transaction + cek stok
       & kedaluwarsa di DALAM lock (FOR UPDATE)
       ============================================================ */
    public function keluarStok(int $obatId, int $jumlah, ?int $pemeriksaanId, string $keterangan, int $userId): int
    {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $obat = $db->fetch('SELECT id, nama, stok, harga, tanggal_kedaluwarsa FROM obat WHERE id = ? FOR UPDATE', [$obatId]);
            if (!$obat) {
                throw new RuntimeException('Obat tidak ditemukan.');
            }
            if ($obat['tanggal_kedaluwarsa'] !== null && strtotime((string) $obat['tanggal_kedaluwarsa']) < strtotime(date('Y-m-d'))) {
                throw new RuntimeException("Obat \"{$obat['nama']}\" sudah kedaluwarsa dan tidak dapat digunakan.");
            }
            if ((int) $obat['stok'] < $jumlah) {
                throw new RuntimeException("Stok \"{$obat['nama']}\" tidak mencukupi (tersedia {$obat['stok']}).");
            }

            $db->execute('UPDATE obat SET stok = stok - ? WHERE id = ?', [$jumlah, $obatId]);
            $db->execute(
                'INSERT INTO obat_transaksi (obat_id, jenis, jumlah, harga_satuan, pemeriksaan_id, keterangan, user_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$obatId, 'keluar', $jumlah, (float) $obat['harga'], $pemeriksaanId,
                 $keterangan !== '' ? $keterangan : null, $userId]
            );
            $id = (int) $db->lastInsertId();

            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /* ================= Histori & laporan ================= */

    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['jenis']) && isset(self::JENIS_LABEL[$f['jenis']])) {
            $where[] = 't.jenis = ?'; $params[] = $f['jenis'];
        }
        if (!empty($f['date_from'])) { $where[] = 'DATE(t.created_at) >= ?'; $params[] = $f['date_from']; }
        if (!empty($f['date_to']))   { $where[] = 'DATE(t.created_at) <= ?'; $params[] = $f['date_to']; }
        if (!empty($f['q'])) {
            $where[] = '(o.nama LIKE ? OR t.keterangan LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%");
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM obat_transaksi t JOIN obat o ON o.id = t.obat_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . " {$whereSql} ORDER BY t.id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    /**
     * Laporan pemakaian per periode: nama obat, jumlah digunakan,
     * harga (snapshot rata-rata), total biaya. + grand total.
     */
    public function laporanPemakaian(string $dateFrom, string $dateTo): array
    {
        $rows = $this->db->fetchAll(
            "SELECT o.id, o.nama, o.satuan,
                    SUM(t.jumlah) AS jumlah,
                    ROUND(AVG(t.harga_satuan), 2) AS harga,
                    SUM(t.jumlah * t.harga_satuan) AS total_biaya
             FROM obat_transaksi t
             JOIN obat o ON o.id = t.obat_id
             WHERE t.jenis = 'keluar' AND DATE(t.created_at) BETWEEN ? AND ?
             GROUP BY t.obat_id, o.nama, o.satuan
             ORDER BY total_biaya DESC",
            [$dateFrom, $dateTo]
        );

        $grand = 0.0;
        foreach ($rows as $r) {
            $grand += (float) $r['total_biaya'];
        }
        return ['rows' => $rows, 'grand_total' => $grand];
    }

    /** Total biaya obat keluar pada bulan berjalan (dashboard). */
    public function biayaBulanIni(): float
    {
        return (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(jumlah * harga_satuan), 0) AS total
             FROM obat_transaksi
             WHERE jenis = 'keluar'
               AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"
        )['total'] ?? 0);
    }
}