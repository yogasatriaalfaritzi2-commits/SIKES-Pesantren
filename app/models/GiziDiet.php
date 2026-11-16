<?php

namespace App\Models;

use App\Core\Model;

class GiziDiet extends Model
{
    protected string $table = 'gizi_diet';

    public const JENIS = [
        'bubur_halus' => 'Bubur Halus',
        'tidak_pedas' => 'Tidak Pedas',
        'diet_lunak'  => 'Diet Lunak',
        'lainnya'     => 'Lainnya',
    ];

    public const STATUS_LABEL = [
        'belum'   => 'Belum Diproses',
        'siap'    => 'Siap Diambil',
        'selesai' => 'Selesai',
    ];

    public const STATUS_COLOR = [
        'belum'   => 'secondary',
        'siap'    => 'info',
        'selesai' => 'success',
    ];

    private const BASE = "SELECT gd.*,
            s.nis, s.nama AS santri_nama, s.jenis_kelamin,
            k.id AS kamar_id, k.nama AS kamar_nama, b.nama AS blok_nama,
            a.id AS asrama_id, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
            u1.nama AS rekomendasi_nama, u2.nama AS distribusi_nama
        FROM gizi_diet gd
        JOIN santri s          ON s.id  = gd.santri_id
        LEFT JOIN kamar k      ON k.id  = s.kamar_id
        LEFT JOIN blok b       ON b.id  = k.blok_id
        LEFT JOIN asrama a     ON a.id  = b.asrama_id
        LEFT JOIN users u1     ON u1.id = gd.rekomendasi_id
        LEFT JOIN users u2     ON u2.id = gd.distribusi_petugas_id";

    /** Nama jenis diet siap tampil (menangani 'lainnya'). */
    public static function displayJenis(array $row): string
    {
        if ($row['jenis_diet'] === 'lainnya') {
            return ($row['jenis_lain'] ?? '') !== '' ? $row['jenis_lain'] : 'Lainnya';
        }
        return self::JENIS[$row['jenis_diet']] ?? $row['jenis_diet'];
    }

    /** @var array f: q, tanggal, asrama, kamar, status */
    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(s.nama LIKE ? OR s.nis LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['tanggal'])) { $where[] = 'gd.tanggal = ?'; $params[] = $f['tanggal']; }
        if (!empty($f['asrama']))  { $where[] = 'a.id = ?';       $params[] = (int) $f['asrama']; }
        if (!empty($f['kamar']))   { $where[] = 'k.id = ?';       $params[] = (int) $f['kamar']; }
        if (!empty($f['status']) && isset(self::STATUS_LABEL[$f['status']])) {
            $where[] = 'gd.status_distribusi = ?'; $params[] = $f['status'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM gizi_diet gd JOIN santri s ON s.id = gd.santri_id
                      LEFT JOIN kamar k  ON k.id = s.kamar_id
                      LEFT JOIN blok b   ON b.id = k.blok_id
                      LEFT JOIN asrama a ON a.id = b.asrama_id';

        $total = (int) ($this->db->fetch("SELECT COUNT(*) AS total{$join}{$whereSql}", $params)['total'] ?? 0);

        $rows = $this->db->fetchAll(
            self::BASE . "{$whereSql} ORDER BY a.nama ASC, b.nama ASC, k.nama ASC, s.nama ASC
            LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function findDetail(int $id): ?array
    {
        return $this->db->fetch(self::BASE . ' WHERE gd.id = ? LIMIT 1', [$id]);
    }

    /** Rekap satu tanggal: jumlah santri unik + distribusi per status. */
    public function statsFor(string $tanggal): array
    {
        $row = $this->db->fetch(
            "SELECT COUNT(DISTINCT santri_id) AS santri, COUNT(*) AS total,
                    COALESCE(SUM(status_distribusi = 'belum'), 0)   AS belum,
                    COALESCE(SUM(status_distribusi = 'siap'), 0)    AS siap,
                    COALESCE(SUM(status_distribusi = 'selesai'), 0) AS selesai
             FROM gizi_diet WHERE tanggal = ?",
            [$tanggal]
        ) ?: [];

        return [
            'santri'  => (int) ($row['santri']  ?? 0),
            'total'   => (int) ($row['total']   ?? 0),
            'belum'   => (int) ($row['belum']   ?? 0),
            'siap'    => (int) ($row['siap']    ?? 0),
            'selesai' => (int) ($row['selesai'] ?? 0),
        ];
    }

    /** Semua baris diet satu tanggal (untuk cetak label massal). */
    public function listForDate(string $tanggal, int $asramaId = 0, int $kamarId = 0): array
    {
        $where  = ['gd.tanggal = ?'];
        $params = [$tanggal];
        if ($asramaId > 0) { $where[] = 'a.id = ?'; $params[] = $asramaId; }
        if ($kamarId > 0)  { $where[] = 'k.id = ?'; $params[] = $kamarId; }

        return $this->db->fetchAll(
            self::BASE . ' WHERE ' . implode(' AND ', $where) .
            ' ORDER BY a.nama ASC, b.nama ASC, k.nama ASC, s.nama ASC',
            $params
        );
    }

    /** Cegah duplikat (santri, tanggal, jenis) — kecuali id sendiri saat edit. */
    public function existsFor(int $santriId, string $tanggal, string $jenis, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT id FROM gizi_diet WHERE santri_id = ? AND tanggal = ? AND jenis_diet = ?';
        $params = [$santriId, $tanggal, $jenis];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (bool) $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /** Opsi kamar lengkap dengan lokasi (filter dropdown ber-optgroup). */
    public function kamarOptions(): array
    {
        return $this->db->fetchAll(
            "SELECT k.id, k.nama AS kamar_nama, b.nama AS blok_nama,
                    a.nama AS asrama_nama, a.jenis AS asrama_jenis
             FROM kamar k
             JOIN blok b   ON b.id = k.blok_id
             JOIN asrama a ON a.id = b.asrama_id
             ORDER BY a.nama ASC, b.nama ASC, k.nama ASC"
        );
    }
}