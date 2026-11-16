<?php

namespace App\Models;

use App\Core\Model;

class InspeksiRiwayat extends Model
{
    protected string $table = 'inspeksi_riwayat';

    public function forInspeksi(int $inspeksiId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.nama AS user_nama
             FROM inspeksi_riwayat r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.inspeksi_id = ?
             ORDER BY r.id DESC LIMIT 10",
            [$inspeksiId]
        );
    }
}