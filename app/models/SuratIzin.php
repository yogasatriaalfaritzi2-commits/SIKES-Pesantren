<?php

namespace App\Models;

use App\Core\Model;

class SuratIzin extends Model
{
    protected string $table = 'surat_izin';

    public const STATUS_LABEL = [
        'aktif'      => 'Aktif',
        'kadaluarsa' => 'Kadaluarsa',
        'dibatalkan' => 'Dibatalkan',
    ];

    public const STATUS_COLOR = [
        'aktif'      => 'success',
        'kadaluarsa' => 'secondary',
        'dibatalkan' => 'danger',
    ];

    public const MAX_DAYS = 30; // batas wajar durasi izin (anti typo)

    private const BASE = "SELECT si.*, s.nis, s.nisn, s.nama AS santri_nama, s.jenis_kelamin,
            k.nama AS kamar_nama, b.nama AS blok_nama, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
            u.nama AS penerbit_nama, p.diagnosis AS pemeriksaan_diagnosis,
            p.tanggal_kunjungan AS pemeriksaan_tanggal
        FROM surat_izin si
        JOIN santri s          ON s.id  = si.santri_id
        LEFT JOIN kamar k      ON k.id  = s.kamar_id
        LEFT JOIN blok b       ON b.id  = k.blok_id
        LEFT JOIN asrama a     ON a.id  = b.asrama_id
        LEFT JOIN users u      ON u.id  = si.diterbitkan_id
        LEFT JOIN pemeriksaan p ON p.id = si.pemeriksaan_id";

    /** Token QR: 64 hex acak — cryptographically secure. */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /** Nomor surat otomatis dari id: {0000}/IZS/PP/{MM}/{YYYY}. */
    public static function generateNomor(int $id, string $tanggalMulai): string
    {
        $ts = strtotime($tanggalMulai) ?: time();
        return sprintf('%04d/IZS/PP/%s/%s', $id, date('m', $ts), date('Y', $ts));
    }

    /**
     * Self-heal: aktif yang tanggal_selesai-nya sudah lewat -> kadaluarsa.
     * Dipanggil di list/show/verify sehingga tidak butuh cron.
     */
    public function expireOverdue(): void
    {
        try {
            $this->db->execute(
                "UPDATE surat_izin SET status = 'kadaluarsa'
                 WHERE status = 'aktif' AND tanggal_selesai < CURDATE()"
            );
        } catch (\Throwable $e) {
            // jangan ganggu alur utama
        }
    }

    /** @var array f: q, status, date_from, date_to */
    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(si.nomor_surat LIKE ? OR s.nama LIKE ? OR s.nis LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['status']) && isset(self::STATUS_LABEL[$f['status']])) {
            $where[] = 'si.status = ?'; $params[] = $f['status'];
        }
        if (!empty($f['date_from'])) { $where[] = 'si.tanggal_mulai >= ?'; $params[] = $f['date_from']; }
        if (!empty($f['date_to']))   { $where[] = 'si.tanggal_mulai <= ?'; $params[] = $f['date_to']; }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM surat_izin si JOIN santri s ON s.id = si.santri_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql} ORDER BY si.id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE si.id = ? LIMIT 1', [$id]);
    }

    /** Pencarian untuk verifikasi QR publik (exact match token). */
    public function findByToken(string $token): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE si.token = ? LIMIT 1', [$token]);
    }

    /** Apakah ada surat AKTIF yang rentangnya bertumpang tindih? */
    public function hasOverlappingActive(int $santriId, string $mulai, string $selesai): bool
    {
        return (bool) $this->db->fetch(
            "SELECT id FROM surat_izin
             WHERE santri_id = ? AND status = 'aktif'
               AND tanggal_mulai <= ? AND tanggal_selesai >= ?
             LIMIT 1",
            [$santriId, $selesai, $mulai]
        );
    }

    /** Opsi pemeriksaan SELESAI milik satu santri (untuk dropdown form). */
    public function pemeriksaanOptions(int $santriId): array
    {
        return $this->db->fetchAll(
            "SELECT id, tanggal_kunjungan, diagnosis FROM pemeriksaan
             WHERE santri_id = ? AND status = 'selesai'
             ORDER BY tanggal_kunjungan DESC LIMIT 20",
            [$santriId]
        );
    }

    public function countActive(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM surat_izin WHERE status = 'aktif' AND tanggal_selesai >= CURDATE()"
        )['total'] ?? 0);
    }
}