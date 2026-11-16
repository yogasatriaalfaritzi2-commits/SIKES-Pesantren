<?php

namespace App\Models;

use App\Core\Model;

/**
 * Pusat Laporan — agregasi lintas modul (read-only + ekspor).
 */
class Laporan extends Model
{
    protected string $table = 'santri';

    /** Santri aktif per asrama (+ blok & kamar). */
    public function santriPerAsrama(): array
    {
        return $this->db->fetchAll(
            "SELECT a.nama AS asrama, a.jenis,
                    (SELECT COUNT(*) FROM blok b WHERE b.asrama_id = a.id) AS blok,
                    (SELECT COUNT(*) FROM kamar k WHERE k.blok_id IN
                        (SELECT b2.id FROM blok b2 WHERE b2.asrama_id = a.id)) AS kamar,
                    (SELECT COUNT(*) FROM santri s
                      JOIN kamar k ON k.id = s.kamar_id
                      JOIN blok b  ON b.id = k.blok_id
                     WHERE b.asrama_id = a.id AND s.status = 'aktif') AS santri_aktif
             FROM asrama a ORDER BY a.nama ASC"
        );
    }

    /** Tren 12 bulan: kasus, izin sakit, rujukan (tanpa scope). */
    public function trenBulanan(): array
    {
        $kasus = $this->db->fetchAll(
            "SELECT DATE_FORMAT(tanggal_kunjungan, '%Y-%m') AS ym, COUNT(*) AS total
             FROM pemeriksaan
             WHERE status = 'selesai'
               AND tanggal_kunjungan >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY ym"
        );
        $izin = $this->db->fetchAll(
            "SELECT DATE_FORMAT(tanggal_mulai, '%Y-%m') AS ym, COUNT(*) AS total
             FROM surat_izin
             WHERE status <> 'dibatalkan'
               AND tanggal_mulai >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY ym"
        );
        $rujukan = $this->db->fetchAll(
            "SELECT DATE_FORMAT(tanggal_rujukan, '%Y-%m') AS ym, COUNT(*) AS total
             FROM rujukan
             WHERE tanggal_rujukan >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY ym"
        );

        $map = ['kasus' => [], 'izin' => [], 'rujukan' => []];
        foreach ($kasus as $r)   { $map['kasus'][$r['ym']]   = (int) $r['total']; }
        foreach ($izin as $r)    { $map['izin'][$r['ym']]    = (int) $r['total']; }
        foreach ($rujukan as $r) { $map['rujukan'][$r['ym']] = (int) $r['total']; }

        $labels = [];
        for ($i = 11; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} month"));
            $labels[] = date('m/y', strtotime("-{$i} month"));
            foreach (array_keys($map) as $k) {
                $map[$k][$ym] = $map[$k][$ym] ?? 0;
            }
        }
        return [
            'labels'  => $labels,
            'kasus'   => array_values($map['kasus']),
            'izin'    => array_values($map['izin']),
            'rujukan' => array_values($map['rujukan']),
        ];
    }

    /** Biaya obat keluar per bulan (12 bulan) + grand total. */
    public function biayaObatBulanan(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym,
                    SUM(jumlah) AS item,
                    SUM(jumlah * harga_satuan) AS biaya
             FROM obat_transaksi
             WHERE jenis = 'keluar'
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY ym"
        );

        $map = [];
        foreach ($rows as $r) {
            $map[$r['ym']] = ['item' => (int) $r['item'], 'biaya' => (float) $r['biaya']];
        }

        $labels = [];
        $item   = [];
        $biaya  = [];
        $grand  = 0.0;
        for ($i = 11; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} month"));
            $labels[] = date('m/y', strtotime("-{$i} month"));
            $it = (int) ($map[$ym]['item'] ?? 0);
            $by = (float) ($map[$ym]['biaya'] ?? 0);
            $item[]  = $it;
            $biaya[] = $by;
            $grand  += $by;
        }
        return ['labels' => $labels, 'item' => $item, 'biaya' => $biaya, 'grand' => $grand];
    }
}