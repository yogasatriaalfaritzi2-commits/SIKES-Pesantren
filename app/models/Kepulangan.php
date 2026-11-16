<?php

namespace App\Models;

use App\Core\Model;

class Kepulangan extends Model
{
    protected string $table = 'kepulangan';

    public const STATUS_LABEL = [
        'menunggu'  => 'Menunggu',
        'disetujui' => 'Disetujui',
        'ditolak'   => 'Ditolak',
        'selesai'   => 'Selesai',
    ];

    public const STATUS_COLOR = [
        'menunggu'  => 'warning',
        'disetujui' => 'info',
        'ditolak'   => 'danger',
        'selesai'   => 'success',
    ];

    /** Label tahap aktif (untuk daftar). */
    public const STAGE_LABEL = [
        'keamanan'   => 'Menunggu Approval Keamanan',
        'pengasuhan' => 'Menunggu Approval Pengasuhan/Kyai',
        'gerbang'    => 'Siap Keluar Gerbang',
        'selesai'    => 'Selesai — Telah Keluar',
        'ditolak'    => 'Ditolak',
    ];

    private const BASE = "SELECT kp.*,
            s.nis, s.nama AS santri_nama, s.jenis_kelamin,
            k.nama AS kamar_nama, b.nama AS blok_nama, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
            u1.nama AS medis_nama, u2.nama AS keamanan_nama,
            u3.nama AS pengasuhan_nama, u4.nama AS gerbang_nama,
            p.diagnosis AS pemeriksaan_diagnosis
        FROM kepulangan kp
        JOIN santri s          ON s.id  = kp.santri_id
        LEFT JOIN kamar k      ON k.id  = s.kamar_id
        LEFT JOIN blok b       ON b.id  = k.blok_id
        LEFT JOIN asrama a     ON a.id  = b.asrama_id
        LEFT JOIN users u1     ON u1.id = kp.medis_id
        LEFT JOIN users u2     ON u2.id = kp.keamanan_id
        LEFT JOIN users u3     ON u3.id = kp.pengasuhan_id
        LEFT JOIN users u4     ON u4.id = kp.gerbang_id
        LEFT JOIN pemeriksaan p ON p.id = kp.pemeriksaan_id";

    /**
     * Tahap aktif saat ini (sumber kebenaran alur):
     * keamanan -> pengasuhan -> gerbang -> selesai | ditolak
     */
    public static function currentStage(array $row): string
    {
        if ($row['status'] === 'ditolak') return 'ditolak';
        if ($row['status'] === 'selesai') return 'selesai';
        if ($row['keamanan_status'] === 'menunggu')   return 'keamanan';
        if ($row['pengasuhan_status'] === 'menunggu') return 'pengasuhan';
        return 'gerbang'; // status = 'disetujui'
    }

    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(s.nama LIKE ? OR s.nis LIKE ? OR kp.alamat_tujuan LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['status']) && isset(self::STATUS_LABEL[$f['status']])) {
            $where[] = 'kp.status = ?'; $params[] = $f['status'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM kepulangan kp JOIN santri s ON s.id = kp.santri_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql} ORDER BY kp.id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE kp.id = ? LIMIT 1', [$id]);
    }

    /** Satu santri hanya boleh punya SATU proses aktif. */
    public function hasActiveProcess(int $santriId): bool
    {
        return (bool) $this->db->fetch(
            "SELECT id FROM kepulangan WHERE santri_id = ? AND status IN ('menunggu','disetujui') LIMIT 1",
            [$santriId]
        );
    }

    /** Opsi pemeriksaan SELESAI milik satu santri (dropdown form). */
    public function pemeriksaanOptions(int $santriId): array
    {
        return $this->db->fetchAll(
            "SELECT id, tanggal_kunjungan, diagnosis FROM pemeriksaan
             WHERE santri_id = ? AND status = 'selesai'
             ORDER BY tanggal_kunjungan DESC LIMIT 20",
            [$santriId]
        );
    }

    public function countPendingKeamanan(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM kepulangan WHERE status = 'menunggu' AND keamanan_status = 'menunggu'"
        )['total'] ?? 0);
    }

    public function countReadyGate(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM kepulangan WHERE status = 'disetujui'"
        )['total'] ?? 0);
    }
}