<?php

namespace App\Models;

use App\Core\Model;

class RujukanRiwayat extends Model
{
    protected string $table = 'rujukan_riwayat';

    public const AKSI_LABEL = ['terbit' => 'Diterbitkan', 'dicetak' => 'Dicetak'];

    public function add(int $rujukanId, string $aksi, string $keterangan, ?int $userId): void
    {
        try {
            $this->create([
                'rujukan_id' => $rujukanId,
                'aksi'       => $aksi,
                'keterangan' => mb_substr($keterangan, 0, 500),
                'user_id'    => $userId,
            ]);
        } catch (\Throwable $e) {
            // riwayat pelengkap — jangan ganggu proses utama
        }
    }

    public function forRujukan(int $rujukanId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.nama AS user_nama
             FROM rujukan_riwayat r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.rujukan_id = ?
             ORDER BY r.id DESC LIMIT 30",
            [$rujukanId]
        );
    }
}