<?php

namespace App\Models;

use App\Core\Model;

/**
 * Rekam Medis = KONSOLIDASI read-only seluruh riwayat kesehatan per santri.
 * Tidak butuh tabel baru — agregasi dari tabel yang sudah ada.
 */
class RekamMedis extends Model
{
    protected string $table = 'santri';

    /** Daftar santri + rekap jumlah kejadian kesehatan. */
    public function ringkasan(int $page, int $perPage, string $q = ''): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = '';
        $params = [];
        if ($q !== '') {
            $where   = ' WHERE (s.nama LIKE ? OR s.nis LIKE ?)';
            $params  = ["%{$q}%", "%{$q}%"];
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM santri s{$where}", $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT s.id, s.nis, s.nama, s.jenis_kelamin, s.status,
                    k.nama AS kamar_nama, b.nama AS blok_nama, a.nama AS asrama_nama,
                    (SELECT COUNT(*) FROM pemeriksaan p
                      WHERE p.santri_id = s.id AND p.status = 'selesai') AS kunjungan,
                    (SELECT MAX(p.tanggal_kunjungan) FROM pemeriksaan p
                      WHERE p.santri_id = s.id) AS kunjungan_terakhir,
                    (SELECT COUNT(*) FROM kasus_menular km
                      WHERE km.santri_id = s.id AND km.status_kasus = 'aktif') AS kasus_aktif,
                    (SELECT COUNT(*) FROM surat_izin si
                      WHERE si.santri_id = s.id AND si.status <> 'dibatalkan') AS izin,
                    (SELECT COUNT(*) FROM rujukan rj
                      WHERE rj.santri_id = s.id) AS rujukan
             FROM santri s
             LEFT JOIN kamar k  ON k.id  = s.kamar_id
             LEFT JOIN blok b   ON b.id  = k.blok_id
             LEFT JOIN asrama a ON a.id  = b.asrama_id
             {$where}
             ORDER BY s.nama ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function skrining(int $santriId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM skrining_kesehatan WHERE santri_id = ? LIMIT 1',
            [$santriId]
        );
    }

    public function pemeriksaan(int $santriId): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, u.nama AS pemeriksa_nama
             FROM pemeriksaan p
             LEFT JOIN users u ON u.id = p.pemeriksa_id
             WHERE p.santri_id = ?
             ORDER BY p.tanggal_kunjungan DESC",
            [$santriId]
        );
    }

    public function kasus(int $santriId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM kasus_menular WHERE santri_id = ? ORDER BY tanggal_mulai DESC",
            [$santriId]
        );
    }

    public function izin(int $santriId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM surat_izin WHERE santri_id = ? ORDER BY tanggal_mulai DESC",
            [$santriId]
        );
    }

    public function rujukan(int $santriId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM rujukan WHERE santri_id = ? ORDER BY tanggal_rujukan DESC",
            [$santriId]
        );
    }
}