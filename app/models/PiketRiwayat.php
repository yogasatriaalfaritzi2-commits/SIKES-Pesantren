<?php

namespace App\Models;

use App\Core\Model;

class PiketRiwayat extends Model
{
    protected string $table = 'piket_riwayat';

    public const AKSI_LABEL = [
        'buat'    => 'Dibuat',
        'ubah'    => 'Diubah',
        'mulai'   => 'Dimulai',
        'selesai' => 'Diselesaikan',
    ];

    public function add(int $jadwalId, string $aksi, string $keterangan, ?int $userId): void
    {
        try {
            $this->create([
                'jadwal_id'  => $jadwalId,
                'aksi'       => $aksi,
                'keterangan' => mb_substr($keterangan, 0, 500),
                'user_id'    => $userId,
            ]);
        } catch (\Throwable $e) {
            // riwayat pelengkap — jangan ganggu proses utama
        }
    }

    public function forJadwal(int $jadwalId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.nama AS user_nama
             FROM piket_riwayat r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.jadwal_id = ?
             ORDER BY r.id DESC LIMIT 20",
            [$jadwalId]
        );
    }
}