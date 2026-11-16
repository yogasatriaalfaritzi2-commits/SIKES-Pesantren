<?php

namespace App\Models;

use App\Core\Model;

class InspeksiKamar extends Model
{
    protected string $table = 'inspeksi_kamar';

    /** 8 parameter inspeksi: [kolom => label tampilan]. */
    public const PARAMS = [
        'ventilasi'      => 'Ventilasi',
        'lantai'         => 'Kebersihan Lantai',
        'kasur'          => 'Kebersihan Kasur / Ranjang',
        'pakaian'        => 'Kebersihan Pakaian',
        'jemur_pakaian'  => 'Aktivitas Jemur Pakaian',
        'kamar_mandi'    => 'Kebersihan Kamar Mandi',
        'kepadatan'      => 'Kepadatan Kamar',
        'lingkungan'     => 'Kondisi Lingkungan Sekitar',
    ];

    /** Bobot poin per nilai. */
    public const NILAI = ['baik' => 2, 'cukup' => 1, 'buruk' => 0];

    public const STATUS_LABEL = [
        'baik'            => 'Baik',
        'perlu_perhatian' => 'Perlu Perhatian',
        'risiko_tinggi'   => 'Risiko Tinggi',
    ];

    /** Warna Bootstrap per status ('belum' untuk kamar tanpa inspeksi). */
    public const STATUS_COLOR = [
        'baik'            => 'success',
        'perlu_perhatian' => 'warning',
        'risiko_tinggi'   => 'danger',
        'belum'           => 'secondary',
    ];

    /**
     * Hitung skor, persentase, dan status dari 8 nilai parameter.
     * Deterministik — murni aritmetika, bukan penilaian otomatis semu.
     */
    public static function hitung(array $params): array
    {
        $skor = 0;
        foreach (array_keys(self::PARAMS) as $f) {
            $skor += self::NILAI[$params[$f] ?? 'buruk'] ?? 0;
        }
        $maks   = count(self::PARAMS) * 2; // 16
        $persen = round($skor / $maks * 100, 2);

        if ($persen >= 75) {
            $status = 'baik';
        } elseif ($persen >= 50) {
            $status = 'perlu_perhatian';
        } else {
            $status = 'risiko_tinggi';
        }
        return ['skor' => $skor, 'maks' => $maks, 'persentase' => $persen, 'status' => $status];
    }

    /**
     * Dashboard kondisi kamar: setiap kamar + inspeksi TERBARINYA
     * (atau NULL bila belum pernah diinspeksi) + rekap per status.
     */
    public function kamarCondition(array $f = []): array
    {
        $where  = [];
        $params = [];
        if (!empty($f['asrama'])) { $where[] = 'a.id = ?'; $params[] = (int) $f['asrama']; }
        if (!empty($f['blok']))   { $where[] = 'b.id = ?'; $params[] = (int) $f['blok']; }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $rows = $this->db->fetchAll(
            "SELECT k.id, k.nama, k.kapasitas,
                    b.id AS blok_id, b.nama AS blok_nama,
                    a.id AS asrama_id, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
                    (SELECT COUNT(*) FROM santri s WHERE s.kamar_id = k.id AND s.status = 'aktif') AS terisi,
                    ik.id AS inspeksi_id, ik.tanggal_inspeksi, ik.skor, ik.persentase, ik.status
             FROM kamar k
             JOIN blok b   ON b.id = k.blok_id
             JOIN asrama a ON a.id = b.asrama_id
             LEFT JOIN inspeksi_kamar ik
               ON ik.kamar_id = k.id
              AND ik.id = (SELECT x.id FROM inspeksi_kamar x
                           WHERE x.kamar_id = k.id
                           ORDER BY x.tanggal_inspeksi DESC, x.id DESC LIMIT 1)
             {$whereSql}
             ORDER BY a.nama ASC, b.nama ASC, k.nama ASC",
            $params
        );

        $stats = ['baik' => 0, 'perlu_perhatian' => 0, 'risiko_tinggi' => 0, 'belum' => 0];
        foreach ($rows as $r) {
            $stats[$r['status'] ?? 'belum']++;
        }
        return ['rows' => $rows, 'stats' => $stats];
    }

    /** Histori inspeksi + filter (pencarian, asrama/blok, status, rentang tanggal). */
    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(k.nama LIKE ? OR b.nama LIKE ? OR a.nama LIKE ? OR ik.catatan_petugas LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['asrama'])) { $where[] = 'a.id = ?'; $params[] = (int) $f['asrama']; }
        if (!empty($f['blok']))   { $where[] = 'b.id = ?'; $params[] = (int) $f['blok']; }
        if (!empty($f['status'])) { $where[] = 'ik.status = ?'; $params[] = $f['status']; }
        if (!empty($f['date_from'])) { $where[] = 'ik.tanggal_inspeksi >= ?'; $params[] = $f['date_from']; }
        if (!empty($f['date_to']))   { $where[] = 'ik.tanggal_inspeksi <= ?'; $params[] = $f['date_to']; }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM inspeksi_kamar ik
                      JOIN kamar k   ON k.id  = ik.kamar_id
                      JOIN blok b    ON b.id  = k.blok_id
                      JOIN asrama a  ON a.id  = b.asrama_id
                      LEFT JOIN users u ON u.id = ik.petugas_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT ik.*, k.nama AS kamar_nama, k.kapasitas,
                    b.nama AS blok_nama, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
                    u.nama AS petugas_nama,
                    (SELECT COUNT(*) FROM santri s WHERE s.kamar_id = k.id AND s.status = 'aktif') AS terisi
             {$join}{$whereSql}
             ORDER BY ik.tanggal_inspeksi DESC, ik.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT ik.*, k.nama AS kamar_nama, k.kapasitas,
                    b.id AS blok_id, b.nama AS blok_nama,
                    a.id AS asrama_id, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
                    u.nama AS petugas_nama,
                    (SELECT COUNT(*) FROM santri s WHERE s.kamar_id = k.id AND s.status = 'aktif') AS terisi
             FROM inspeksi_kamar ik
             JOIN kamar k   ON k.id  = ik.kamar_id
             JOIN blok b    ON b.id  = k.blok_id
             JOIN asrama a  ON a.id  = b.asrama_id
             LEFT JOIN users u ON u.id = ik.petugas_id
             WHERE ik.id = ? LIMIT 1",
            [$id]
        );
    }

    /** Cegah duplikat: satu inspeksi per kamar per tanggal. */
    public function existsFor(int $kamarId, string $tanggal, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT id FROM inspeksi_kamar WHERE kamar_id = ? AND tanggal_inspeksi = ?';
        $params = [$kamarId, $tanggal];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (bool) $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /** Opsi kamar lengkap dengan lokasi (untuk select ber-optgroup di form). */
    public function kamarOptions(): array
    {
        return $this->db->fetchAll(
            "SELECT k.id, k.nama AS kamar_nama, k.kapasitas,
                    b.nama AS blok_nama, a.nama AS asrama_nama, a.jenis AS asrama_jenis
             FROM kamar k
             JOIN blok b   ON b.id = k.blok_id
             JOIN asrama a ON a.id = b.asrama_id
             ORDER BY a.nama ASC, b.nama ASC, k.nama ASC"
        );
    }
}