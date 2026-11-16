<?php

namespace App\Models;

use App\Core\Model;

class KepulanganRiwayat extends Model
{
    protected string $table = 'kepulangan_riwayat';

    public const AKSI_LABEL = [
        'rekomendasi' => 'Rekomendasi Medis',
        'keamanan'    => 'Approval Keamanan',
        'pengasuhan'  => 'Approval Pengasuhan/Kyai',
        'gerbang'     => 'Izin Keluar Gerbang',
    ];

    public function add(int $kepulanganId, string $aksi, string $keterangan, ?int $userId): void
    {
        try {
            $this->create([
                'kepulangan_id' => $kepulanganId,
                'aksi'          => $aksi,
                'keterangan'    => mb_substr($keterangan, 0, 500),
                'user_id'       => $userId,
            ]);
        } catch (\Throwable $e) {
            // histori pelengkap — jangan ganggu proses utama
        }
    }

    public function forKepulangan(int $kepulanganId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.nama AS user_nama
             FROM kepulangan_riwayat r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.kepulangan_id = ?
             ORDER BY r.id ASC LIMIT 50",
            [$kepulanganId]
        );
    }
}