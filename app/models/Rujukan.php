<?php

namespace App\Models;

use App\Core\Model;

class Rujukan extends Model
{
    protected string $table = 'rujukan';

    /** Nomor otomatis dari id: {0000}/RUJ/{MM}/{YYYY}. */
    public static function generateNomor(int $id, string $tanggal): string
    {
        $ts = strtotime($tanggal) ?: time();
        return sprintf('%04d/RUJ/%s/%s', $id, date('m', $ts), date('Y', $ts));
    }

    private const BASE = "SELECT rj.*, s.nis, s.nisn, s.nama AS santri_nama, s.jenis_kelamin,
            s.tempat_lahir, s.tanggal_lahir, s.alamat AS santri_alamat,
            k.nama AS kamar_nama, b.nama AS blok_nama, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
            u.nama AS penerbit_nama, p.diagnosis AS pemeriksaan_diagnosis,
            p.tanggal_kunjungan AS pemeriksaan_tanggal
        FROM rujukan rj
        JOIN santri s          ON s.id  = rj.santri_id
        LEFT JOIN kamar k      ON k.id  = s.kamar_id
        LEFT JOIN blok b       ON b.id  = k.blok_id
        LEFT JOIN asrama a     ON a.id  = b.asrama_id
        LEFT JOIN users u      ON u.id  = rj.penerbit_id
        LEFT JOIN pemeriksaan p ON p.id = rj.pemeriksaan_id";

    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(rj.nomor_surat LIKE ? OR s.nama LIKE ? OR s.nis LIKE ? OR rj.tujuan_faskes LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['date_from'])) { $where[] = 'rj.tanggal_rujukan >= ?'; $params[] = $f['date_from']; }
        if (!empty($f['date_to']))   { $where[] = 'rj.tanggal_rujukan <= ?'; $params[] = $f['date_to']; }
        if (!empty($f['santri_id'])) { $where[] = 'rj.santri_id = ?';        $params[] = (int) $f['santri_id']; }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM rujukan rj JOIN santri s ON s.id = rj.santri_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql} ORDER BY rj.id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE rj.id = ? LIMIT 1', [$id]);
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
}