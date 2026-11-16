<?php

namespace App\Models;

use App\Core\Model;

class SuratIzinRiwayat extends Model
{
    protected string $table = 'surat_izin_riwayat';

    public const AKSI_LABEL = [
        'terbit'       => 'Diterbitkan',
        'batal'        => 'Dibatalkan',
        'diverifikasi' => 'Diverifikasi (QR)',
    ];

    public function add(int $suratId, string $aksi, string $keterangan, ?int $userId): void
    {
        try {
            $this->create([
                'surat_id'   => $suratId,
                'aksi'       => $aksi,
                'keterangan' => mb_substr($keterangan, 0, 500),
                'user_id'    => $userId,
            ]);
        } catch (\Throwable $e) {
            // riwayat pelengkap — jangan ganggu proses utama
        }
    }

    public function forSurat(int $suratId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.nama AS user_nama
             FROM surat_izin_riwayat r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.surat_id = ?
             ORDER BY r.id DESC LIMIT 30",
            [$suratId]
        );
    }
}