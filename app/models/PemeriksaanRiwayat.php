<?php

namespace App\Models;

use App\Core\Model;

class PemeriksaanRiwayat extends Model
{
    protected string $table = 'pemeriksaan_riwayat';

    public function forPemeriksaan(int $pemeriksaanId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.nama AS user_nama
             FROM pemeriksaan_riwayat r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.pemeriksaan_id = ?
             ORDER BY r.id DESC LIMIT 15",
            [$pemeriksaanId]
        );
    }
}