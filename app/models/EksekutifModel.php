<?php

namespace App\Models;

use App\Core\Model;

/**
 * Agregator Dashboard Eksekutif (Kyai/Pimpinan).
 * Semua query read-only dari tabel yang sudah ada (tanpa skema baru).
 *
 * FIX v14.1: perbaikan rantai JOIN — tabel `kamar` hanya punya `blok_id`
 * (TIDAK punya `asrama_id`), sehingga urutan JOIN yang benar adalah:
 *   santri -> kamar (k.blok_id) -> blok (b.asrama_id) -> asrama
 * Rantai ini distandarkan pada konstanta JOIN_CHAIN agar konsisten
 * di seluruh method.
 *
 * $f = ['from' => Y-m-d, 'to' => Y-m-d, 'asrama' => int, 'blok' => int]
 */
class EksekutifModel extends Model
{
    protected string $table = 'santri';

    /**
     * Rantai JOIN standar (dipakai semua query berbasis santri).
     * Alias: s = santri, k = kamar, b = blok, a = asrama.
     */
    private const JOIN_CHAIN = "
        LEFT JOIN kamar k  ON k.id  = s.kamar_id
        LEFT JOIN blok b   ON b.id  = k.blok_id
        LEFT JOIN asrama a ON a.id  = b.asrama_id";

    /**
     * Scope filter asrama/blok — merujuk alias b (blok) yang WAJIB ada
     * di query pemanggil (via JOIN_CHAIN atau FROM blok b).
     */
    private function scopeSql(array $f): array
    {
        $w = [];
        $params = [];
        if (!empty($f['asrama'])) { $w[] = 'b.asrama_id = ?'; $params[] = (int) $f['asrama']; }
        if (!empty($f['blok']))   { $w[] = 'b.id = ?';        $params[] = (int) $f['blok']; }
        return [$w, $params];
    }

    /** Gabungkan kondisi scope menjadi string WHERE tambahan (" AND ..."). */
    private function scopeWhere(array $f): array
    {
        [$w, $params] = $this->scopeSql($f);
        return [$w ? ' AND ' . implode(' AND ', $w) : '', $params];
    }

    /* ---------- 1. Total santri ---------- */
    public function santriTotal(array $f): int
    {
        [$w, $params] = $this->scopeSql($f);
        $where = $w ? " WHERE s.status = 'aktif' AND " . implode(' AND ', $w)
                    : " WHERE s.status = 'aktif'";
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM santri s" . self::JOIN_CHAIN . $where,
            $params
        )['total'] ?? 0);
    }

    /* ---------- 2. Santri sedang sakit (kasus aktif + kunjungan menunggu) ---------- */
    public function santriSakit(array $f): int
    {
        [$scope, $params] = $this->scopeWhere($f);
        return (int) ($this->db->fetch(
            "SELECT COUNT(DISTINCT s.id) AS total
             FROM santri s" . self::JOIN_CHAIN . "
             WHERE s.status = 'aktif'" . $scope . "
               AND (
                 EXISTS (SELECT 1 FROM kasus_menular km WHERE km.santri_id = s.id AND km.status_kasus = 'aktif')
                 OR EXISTS (SELECT 1 FROM pemeriksaan p
                            WHERE p.santri_id = s.id AND p.status = 'menunggu')
               )",
            $params
        )['total'] ?? 0);
    }

    /* ---------- 3. Kasus kesehatan (kunjungan selesai) pada periode ---------- */
    public function kasusPeriode(array $f): int
    {
        [$scope, $params] = $this->scopeWhere($f);
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM pemeriksaan p
             JOIN santri s ON s.id = p.santri_id" . self::JOIN_CHAIN . "
             WHERE p.status = 'selesai'
               AND DATE(p.tanggal_kunjungan) BETWEEN ? AND ?" . $scope,
            array_merge([$f['from'], $f['to']], $params)
        )['total'] ?? 0);
    }

    /* ---------- 4. Penyakit terbanyak (top N) ---------- */
    public function penyakitTerbanyak(array $f, int $limit = 5): array
    {
        [$scope, $params] = $this->scopeWhere($f);
        return $this->db->fetchAll(
            "SELECT TRIM(p.diagnosis) AS penyakit, COUNT(*) AS total
             FROM pemeriksaan p
             JOIN santri s ON s.id = p.santri_id" . self::JOIN_CHAIN . "
             WHERE p.status = 'selesai'
               AND DATE(p.tanggal_kunjungan) BETWEEN ? AND ?
               AND p.diagnosis IS NOT NULL AND TRIM(p.diagnosis) <> ''" . $scope . "
             GROUP BY TRIM(p.diagnosis)
             ORDER BY total DESC
             LIMIT " . $limit,
            array_merge([$f['from'], $f['to']], $params)
        );
    }

    /* ---------- 5. Santri isolasi / rawat inap (kasus menular aktif) ---------- */
    public function rawatInap(array $f): int
    {
        [$scope, $params] = $this->scopeWhere($f);
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM kasus_menular km
             JOIN santri s ON s.id = km.santri_id" . self::JOIN_CHAIN . "
             WHERE km.status_kasus = 'aktif'" . $scope,
            $params
        )['total'] ?? 0);
    }

    /* ---------- 6. Jumlah rujukan periode ---------- */
    public function rujukanCount(array $f): int
    {
        [$scope, $params] = $this->scopeWhere($f);
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM rujukan rj
             JOIN santri s ON s.id = rj.santri_id" . self::JOIN_CHAIN . "
             WHERE rj.tanggal_rujukan BETWEEN ? AND ?" . $scope,
            array_merge([$f['from'], $f['to']], $params)
        )['total'] ?? 0);
    }

    /* ---------- 7. Jumlah izin sakit periode ---------- */
    public function izinCount(array $f): int
    {
        [$scope, $params] = $this->scopeWhere($f);
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM surat_izin si
             JOIN santri s ON s.id = si.santri_id" . self::JOIN_CHAIN . "
             WHERE si.status <> 'dibatalkan'
               AND si.tanggal_mulai BETWEEN ? AND ?" . $scope,
            array_merge([$f['from'], $f['to']], $params)
        )['total'] ?? 0);
    }

    /* ---------- 8. Kondisi sanitasi kamar (ringkas) ---------- */
    public function sanitasi(array $f): array
    {
        [$scope, $params] = $this->scopeWhere($f);
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(ROUND(AVG(ik.persentase), 1), 0) AS rata2,
                    COALESCE(SUM(ik.status = 'baik'), 0)            AS baik,
                    COALESCE(SUM(ik.status = 'perlu_perhatian'), 0) AS perhatian,
                    COALESCE(SUM(ik.status = 'risiko_tinggi'), 0)   AS risiko
             FROM inspeksi_kamar ik
             JOIN kamar k   ON k.id  = ik.kamar_id
             JOIN blok b    ON b.id  = k.blok_id
             JOIN asrama a  ON a.id  = b.asrama_id
             WHERE ik.id IN (
                SELECT MAX(x.id) FROM inspeksi_kamar x
                JOIN kamar kx  ON kx.id = x.kamar_id
                JOIN blok bx   ON bx.id = kx.blok_id
                JOIN asrama ax ON ax.id = bx.asrama_id
                GROUP BY x.kamar_id
             )" . $scope,
            $params
        ) ?: [];
        return [
            'total'     => (int) ($row['total'] ?? 0),
            'rata2'     => (float) ($row['rata2'] ?? 0),
            'baik'      => (int) ($row['baik'] ?? 0),
            'perhatian' => (int) ($row['perhatian'] ?? 0),
            'risiko'    => (int) ($row['risiko'] ?? 0),
        ];
    }

    /* ---------- 9. Pemakaian obat periode (obat tidak terikat asrama) ---------- */
    public function pemakaianObat(array $f): array
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(t.jumlah), 0) AS jumlah,
                    COALESCE(SUM(t.jumlah * t.harga_satuan), 0) AS biaya
             FROM obat_transaksi t
             WHERE t.jenis = 'keluar'
               AND DATE(t.created_at) BETWEEN ? AND ?",
            [$f['from'], $f['to']]
        ) ?: [];
        return [
            'jumlah' => (int) ($row['jumlah'] ?? 0),
            'biaya'  => (float) ($row['biaya'] ?? 0),
        ];
    }

    /* ================= GRAFIK ================= */

    /** Kasus / izin / rujukan per bulan (12 bulan terakhir). */
    public function trenBulanan(array $f): array
    {
        [$scope, $params] = $this->scopeWhere($f);

        $kasus = $this->db->fetchAll(
            "SELECT DATE_FORMAT(p.tanggal_kunjungan, '%Y-%m') AS ym, COUNT(*) AS total
             FROM pemeriksaan p
             JOIN santri s ON s.id = p.santri_id" . self::JOIN_CHAIN . "
             WHERE p.status = 'selesai'
               AND p.tanggal_kunjungan >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)" . $scope . "
             GROUP BY ym",
            $params
        );

        $izin = $this->db->fetchAll(
            "SELECT DATE_FORMAT(si.tanggal_mulai, '%Y-%m') AS ym, COUNT(*) AS total
             FROM surat_izin si
             JOIN santri s ON s.id = si.santri_id" . self::JOIN_CHAIN . "
             WHERE si.status <> 'dibatalkan'
               AND si.tanggal_mulai >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)" . $scope . "
             GROUP BY ym",
            $params
        );

        $rujukan = $this->db->fetchAll(
            "SELECT DATE_FORMAT(rj.tanggal_rujukan, '%Y-%m') AS ym, COUNT(*) AS total
             FROM rujukan rj
             JOIN santri s ON s.id = rj.santri_id" . self::JOIN_CHAIN . "
             WHERE rj.tanggal_rujukan >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)" . $scope . "
             GROUP BY ym",
            $params
        );

        // Isi 12 label bulan (nilai kosong = 0)
        $labels = [];
        $map = ['kasus' => [], 'izin' => [], 'rujukan' => []];
        foreach ($kasus as $r)   { $map['kasus'][$r['ym']]   = (int) $r['total']; }
        foreach ($izin as $r)    { $map['izin'][$r['ym']]    = (int) $r['total']; }
        foreach ($rujukan as $r) { $map['rujukan'][$r['ym']] = (int) $r['total']; }

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

    /** Top pemakaian obat pada periode (bar horizontal). */
    public function obatTop(array $f, int $limit = 5): array
    {
        return $this->db->fetchAll(
            "SELECT o.nama, SUM(t.jumlah) AS jumlah
             FROM obat_transaksi t
             JOIN obat o ON o.id = t.obat_id
             WHERE t.jenis = 'keluar'
               AND DATE(t.created_at) BETWEEN ? AND ?
             GROUP BY t.obat_id, o.nama
             ORDER BY jumlah DESC
             LIMIT " . $limit,
            [$f['from'], $f['to']]
        );
    }

    /** Heatmap per asrama/blok: kasus periode + kasus aktif + sanitasi risiko. */
    public function heatmap(array $f): array
    {
        [$scope, $params] = $this->scopeWhere($f);

        return $this->db->fetchAll(
            "SELECT a.nama AS asrama, a.jenis AS jenis, b.nama AS blok,
                    (SELECT COUNT(*) FROM pemeriksaan p
                       JOIN santri sx ON sx.id = p.santri_id
                      WHERE sx.kamar_id IN (SELECT k2.id FROM kamar k2 WHERE k2.blok_id = b.id)
                        AND p.status = 'selesai'
                        AND DATE(p.tanggal_kunjungan) BETWEEN ? AND ?) AS kasus,
                    (SELECT COUNT(*) FROM kasus_menular km
                       JOIN santri sx ON sx.id = km.santri_id
                      WHERE sx.kamar_id IN (SELECT k2.id FROM kamar k2 WHERE k2.blok_id = b.id)
                        AND km.status_kasus = 'aktif') AS aktif,
                    (SELECT COALESCE(SUM(x.status = 'risiko_tinggi'), 0)
                       FROM inspeksi_kamar x
                      WHERE x.kamar_id IN (SELECT k2.id FROM kamar k2 WHERE k2.blok_id = b.id)
                        AND x.id = (SELECT MAX(x2.id) FROM inspeksi_kamar x2 WHERE x2.kamar_id = x.kamar_id)) AS risiko
             FROM blok b
             JOIN asrama a ON a.id = b.asrama_id" . $scope . "
             ORDER BY a.nama ASC, b.nama ASC",
            array_merge([$f['from'], $f['to']], $params)
        );
    }
}