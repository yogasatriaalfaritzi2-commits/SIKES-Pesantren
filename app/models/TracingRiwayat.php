<?php

namespace App\Models;

use App\Core\Model;

class TracingRiwayat extends Model
{
    protected string $table = 'tracing_riwayat';

    public const AKSI_LABEL = [
        'kasus_dibuat'        => 'Kasus Dibuat',
        'kontak_dibuat'       => 'Kontak Digenerate',
        'kontak_disinkron'    => 'Kontak Disinkronkan',
        'pemeriksaan_dimulai' => 'Pemeriksaan Preventif Dibuat',
        'hasil_dicatat'       => 'Hasil Dicatat',
        'kasus_ditutup'       => 'Kasus Ditutup',
    ];

    public function add(int $kasusId, ?int $kontakId, string $aksi, string $keterangan, ?int $userId): void
    {
        try {
            $this->create([
                'kasus_id'   => $kasusId,
                'kontak_id'  => $kontakId,
                'aksi'       => $aksi,
                'keterangan' => mb_substr($keterangan, 0, 500),
                'user_id'    => $userId,
            ]);
        } catch (\Throwable $e) {
            // histori pelengkap — jangan ganggu proses utama
        }
    }

    public function forKasus(int $kasusId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.nama AS user_nama
             FROM tracing_riwayat r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.kasus_id = ?
             ORDER BY r.id DESC LIMIT 50",
            [$kasusId]
        );
    }
}