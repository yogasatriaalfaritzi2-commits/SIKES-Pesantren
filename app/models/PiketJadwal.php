<?php

namespace App\Models;

use App\Core\Model;

class PiketJadwal extends Model
{
    protected string $table = 'piket_jadwal';

    public const JENIS = [
        'jemur_kasur'            => 'Jemur Kasur',
        'kerja_bakti'            => 'Kerja Bakti',
        'pembersihan_kamar'      => 'Pembersihan Kamar',
        'sanitasi_kamar_mandi'   => 'Sanitasi Kamar Mandi',
        'pemeriksaan_lingkungan' => 'Pemeriksaan Lingkungan',
    ];

    public const STATUS_LABEL = [
        'terjadwal'   => 'Terjadwal',
        'berlangsung' => 'Berlangsung',
        'selesai'     => 'Selesai',
    ];

    public const STATUS_COLOR = [
        'terjadwal'   => 'info',
        'berlangsung' => 'warning',
        'selesai'     => 'success',
    ];

    /** Daftar jadwal + filter (pencarian, jenis, status, rentang tanggal). */
    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(pj.nama_kegiatan LIKE ? OR pj.lokasi LIKE ? OR pj.penanggung_jawab LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['jenis'])  && isset(self::JENIS[$f['jenis']]))        { $where[] = 'pj.jenis = ?';  $params[] = $f['jenis']; }
        if (!empty($f['status']) && isset(self::STATUS_LABEL[$f['status']])) { $where[] = 'pj.status = ?'; $params[] = $f['status']; }
        if (!empty($f['date_from'])) { $where[] = 'pj.tanggal >= ?'; $params[] = $f['date_from']; }
        if (!empty($f['date_to']))   { $where[] = 'pj.tanggal <= ?'; $params[] = $f['date_to']; }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM piket_jadwal pj{$whereSql}", $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT pj.* FROM piket_jadwal pj{$whereSql}
             ORDER BY pj.tanggal DESC, pj.waktu_mulai DESC, pj.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    /**
     * Detail satu jadwal — mengembalikan SELURUH kolom piket_jadwal
     * (status, nama_kegiatan, jenis, tanggal, waktu_mulai, dst.)
     * + nama pembuat.
     */
    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT pj.*, u.nama AS dibuat_nama
             FROM piket_jadwal pj
             LEFT JOIN users u ON u.id = pj.dibuat_id
             WHERE pj.id = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * Reminder dashboard: kegiatan BELUM SELESAI dibagi 3 grup.
     */
    public function reminders(int $futureDays = 7): array
    {
        $rows = $this->db->fetchAll(
            "SELECT pj.* FROM piket_jadwal pj
             WHERE pj.status <> 'selesai'
               AND pj.tanggal <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY pj.tanggal ASC, pj.waktu_mulai ASC
             LIMIT 50",
            [$futureDays]
        );

        $today = date('Y-m-d');
        $out   = ['terlewat' => [], 'hari_ini' => [], 'mendatang' => []];
        foreach ($rows as $r) {
            if ($r['tanggal'] < $today) {
                $out['terlewat'][] = $r;
            } elseif ($r['tanggal'] === $today) {
                $out['hari_ini'][] = $r;
            } else {
                $out['mendatang'][] = $r;
            }
        }
        return $out;
    }

    /** Event kalender untuk satu bulan (Y-m). */
    public function calendarEvents(int $year, int $month): array
    {
        return $this->db->fetchAll(
            "SELECT id, jenis, nama_kegiatan, lokasi, tanggal, waktu_mulai, waktu_selesai, status
             FROM piket_jadwal
             WHERE YEAR(tanggal) = ? AND MONTH(tanggal) = ?
             ORDER BY tanggal ASC, waktu_mulai ASC",
            [$year, $month]
        );
    }

    public function countUnfinished(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM piket_jadwal WHERE status <> 'selesai'"
        )['total'] ?? 0);
    }
}