<?php

namespace App\Models;

use App\Core\Model;

class SkriningKesehatan extends Model
{
    protected string $table = 'skrining_kesehatan';

    /** Field kondisi khusus: [kolom_flag => label tampilan] */
    public const KONDISI = [
        'alergi_obat'             => 'Alergi Obat',
        'alergi_makanan'          => 'Alergi Makanan',
        'asma'                    => 'Asma',
        'epilepsi'                => 'Epilepsi',
        'riwayat_penyakit_bawaan' => 'Riwayat Penyakit Bawaan',
        'penyakit_lain'           => 'Penyakit Bawaan Lainnya',
    ];

    /**
     * Ambil daftar peringatan kondisi khusus dari satu baris skrining.
     * Murni membaca flag isian tenaga medis — bukan bentuk diagnosis.
     */
    public static function warningsFrom(?array $s): array
    {
        if (!$s) {
            return [];
        }
        $w = [];
        foreach (self::KONDISI as $field => $label) {
            if (($s[$field] ?? 'tidak') === 'ya') {
                $w[] = $label;
            }
        }
        return $w;
    }

    /**
     * Hitung IMT (berat kg / tinggi m^2) + kategori referensi Kemenkes.
     * Bersifat SKRINING — bukan diagnosis.
     */
    public static function hitungImt(?float $berat, ?float $tinggi): ?array
    {
        if (!$berat || !$tinggi || $tinggi <= 0) {
            return null;
        }
        $m   = $tinggi / 100;
        $imt = round($berat / ($m * $m), 2);

        if ($imt < 18.5) {
            $kat = 'Berat Badan Kurang';
        } elseif ($imt < 25.0) {
            $kat = 'Normal';
        } elseif ($imt <= 27.0) {
            $kat = 'Berat Badan Berlebih';
        } else {
            $kat = 'Obesitas';
        }
        return ['imt' => $imt, 'kategori' => $kat];
    }

    /** Daftar santri + status skriningnya (untuk halaman daftar skrining). */
    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(s.nama LIKE ? OR s.nis LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['jk']))     { $where[] = 's.jenis_kelamin = ?'; $params[] = $f['jk']; }
        if (!empty($f['asrama'])) { $where[] = 'a.id = ?';            $params[] = (int) $f['asrama']; }
        if (($f['status'] ?? '') === 'sudah') { $where[] = 'sk.id IS NOT NULL'; }
        if (($f['status'] ?? '') === 'belum') { $where[] = 'sk.id IS NULL'; }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM santri s
                      LEFT JOIN skrining_kesehatan sk ON sk.santri_id = s.id
                      LEFT JOIN kamar k  ON k.id  = s.kamar_id
                      LEFT JOIN blok b   ON b.id  = k.blok_id
                      LEFT JOIN asrama a ON a.id  = b.asrama_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT s.id AS santri_id, s.nis, s.nama, s.jenis_kelamin, s.status AS santri_status,
                    a.nama AS asrama_nama, b.nama AS blok_nama, k.nama AS kamar_nama,
                    sk.id AS skrining_id, sk.*
             {$join}{$whereSql}
             ORDER BY (sk.id IS NULL) DESC, s.nama ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    /** Detail skrining + data santri + lokasi + nama petugas. */
    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT sk.*, s.nis, s.nisn, s.nama AS santri_nama, s.jenis_kelamin,
                    s.tempat_lahir, s.tanggal_lahir, s.status AS santri_status,
                    k.nama AS kamar_nama, b.nama AS blok_nama,
                    a.nama AS asrama_nama, a.jenis AS asrama_jenis,
                    w.nama AS wali_nama, w.no_telepon AS wali_telepon,
                    u.nama AS petugas_nama
             FROM skrining_kesehatan sk
             JOIN santri s        ON s.id  = sk.santri_id
             LEFT JOIN kamar k    ON k.id  = s.kamar_id
             LEFT JOIN blok b     ON b.id  = k.blok_id
             LEFT JOIN asrama a   ON a.id  = b.asrama_id
             LEFT JOIN wali_santri w ON w.id = s.wali_id
             LEFT JOIN users u    ON u.id  = sk.petugas_id
             WHERE sk.id = ? LIMIT 1",
            [$id]
        );
    }

    public function findBySantri(int $santriId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM skrining_kesehatan WHERE santri_id = ? LIMIT 1",
            [$santriId]
        );
    }

    /** Dropdown santri (form input skrining & filter riwayat). */
    public function santriOptions(): array
    {
        return $this->db->fetchAll(
            "SELECT s.id, s.nis, s.nama, s.jenis_kelamin, s.status
             FROM santri s
             ORDER BY (s.status = 'aktif') DESC, s.nama ASC"
        );
    }

    public function countScreened(): int
    {
        return (int) ($this->db->fetch("SELECT COUNT(*) AS total FROM skrining_kesehatan")['total'] ?? 0);
    }

    /** Santri dengan minimal satu kondisi khusus (flag 'ya'). */
    public function countFlagged(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM skrining_kesehatan
             WHERE alergi_obat = 'ya' OR alergi_makanan = 'ya' OR asma = 'ya'
                OR epilepsi = 'ya' OR riwayat_penyakit_bawaan = 'ya' OR penyakit_lain = 'ya'"
        )['total'] ?? 0);
    }

    public function countNotScreened(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM santri s
             WHERE NOT EXISTS (SELECT 1 FROM skrining_kesehatan sk WHERE sk.santri_id = s.id)"
        )['total'] ?? 0);
    }
}