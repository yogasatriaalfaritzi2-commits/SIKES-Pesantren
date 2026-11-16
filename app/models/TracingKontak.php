<?php

namespace App\Models;

use App\Core\Model;

class TracingKontak extends Model
{
    protected string $table = 'tracing_kontak';

    public const STATUS_LABEL = [
        'belum_diperiksa'     => 'Belum Diperiksa',
        'dalam_pemeriksaan'   => 'Dalam Pemeriksaan',
        'sudah_diperiksa'     => 'Sudah Diperiksa',
        'perlu_tindak_lanjut' => 'Perlu Tindak Lanjut',
    ];

    public const STATUS_COLOR = [
        'belum_diperiksa'     => 'secondary',
        'dalam_pemeriksaan'   => 'warning',
        'sudah_diperiksa'     => 'success',
        'perlu_tindak_lanjut' => 'danger',
    ];

    /** Daftar kontak potensial satu kasus + data santri & status pemeriksaan terkait. */
    public function forKasus(int $kasusId): array
    {
        return $this->db->fetchAll(
            "SELECT tk.*,
                    s.nis, s.nama AS santri_nama, s.jenis_kelamin,
                    p.status AS pemeriksaan_status
             FROM tracing_kontak tk
             JOIN santri s ON s.id = tk.santri_id
             LEFT JOIN pemeriksaan p ON p.id = tk.pemeriksaan_id
             WHERE tk.kasus_id = ?
             ORDER BY tk.id ASC",
            [$kasusId]
        );
    }

    public function findWithSantri(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT tk.*, s.nis, s.nama AS santri_nama, s.jenis_kelamin,
                    k.nama AS kamar_nama, b.nama AS blok_nama, a.nama AS asrama_nama
             FROM tracing_kontak tk
             JOIN santri s       ON s.id = tk.santri_id
             LEFT JOIN kamar k   ON k.id = s.kamar_id
             LEFT JOIN blok b    ON b.id = k.blok_id
             LEFT JOIN asrama a  ON a.id = b.asrama_id
             WHERE tk.id = ? LIMIT 1",
            [$id]
        );
    }

    /** Tambah kontak potensial (idempoten per kasus+santri). */
    public function addIfMissing(int $kasusId, int $santriId): bool
    {
        $exists = $this->db->fetch(
            'SELECT id FROM tracing_kontak WHERE kasus_id = ? AND santri_id = ? LIMIT 1',
            [$kasusId, $santriId]
        );
        if ($exists) {
            return false;
        }
        $this->db->execute(
            "INSERT INTO tracing_kontak (kasus_id, santri_id, status) VALUES (?, ?, 'belum_diperiksa')",
            [$kasusId, $santriId]
        );
        return true;
    }

    public function countByStatus(int $kasusId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT status, COUNT(*) AS total FROM tracing_kontak WHERE kasus_id = ? GROUP BY status',
            [$kasusId]
        );
        $out = [
            'belum_diperiksa'     => 0,
            'dalam_pemeriksaan'   => 0,
            'sudah_diperiksa'     => 0,
            'perlu_tindak_lanjut' => 0,
        ];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['total'];
        }
        return $out;
    }
}