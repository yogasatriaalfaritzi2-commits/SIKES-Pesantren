<?php

namespace App\Models;

use App\Core\Model;

class Kamar extends Model
{
    protected string $table = 'kamar';

    public function paginate(int $page, int $perPage, string $q = '', int $asramaId = 0): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if ($q !== '')     { $where[] = '(k.nama LIKE ? OR b.nama LIKE ? OR a.nama LIKE ?)'; array_push($params, "%{$q}%", "%{$q}%", "%{$q}%"); }
        if ($asramaId > 0) { $where[] = 'a.id = ?'; $params[] = $asramaId; }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $join     = ' FROM kamar k JOIN blok b ON b.id = k.blok_id JOIN asrama a ON a.id = b.asrama_id';

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total{$join}{$whereSql}", $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT k.*, b.nama AS blok_nama, a.nama AS asrama_nama, a.jenis AS asrama_jenis,
                    (SELECT COUNT(*) FROM santri s WHERE s.kamar_id = k.id AND s.status = 'aktif') AS terisi
             {$join}{$whereSql}
             ORDER BY a.nama ASC, b.nama ASC, k.nama ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    /** Detail kamar + lokasi lengkap (validasi penempatan santri). */
    public function findWithLocation(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT k.*, b.id AS blok_id, b.nama AS blok_nama,
                    a.id AS asrama_id, a.nama AS asrama_nama, a.jenis AS asrama_jenis
             FROM kamar k
             JOIN blok b   ON b.id = k.blok_id
             JOIN asrama a ON a.id = b.asrama_id
             WHERE k.id = ? LIMIT 1",
            [$id]
        );
    }

    /** Jumlah penghuni AKTIF (untuk cek kapasitas). */
    public function countOccupants(int $kamarId, ?int $excludeSantriId = null): int
    {
        $sql    = "SELECT COUNT(*) AS total FROM santri WHERE kamar_id = ? AND status = 'aktif'";
        $params = [$kamarId];
        if ($excludeSantriId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeSantriId;
        }
        return (int) ($this->db->fetch($sql, $params)['total'] ?? 0);
    }

    /** Semua santri yang menunjuk kamar ini (proteksi hapus). */
    public function countSantri(int $kamarId): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM santri WHERE kamar_id = ?",
            [$kamarId]
        )['total'] ?? 0);
    }

    /** Daftar penghuni kamar (halaman detail). */
    public function occupants(int $kamarId): array
    {
        return $this->db->fetchAll(
            "SELECT id, nis, nama, jenis_kelamin, status
             FROM santri WHERE kamar_id = ? ORDER BY nama ASC",
            [$kamarId]
        );
    }

    /** Dropdown kamar per blok, lengkap okupansi (cascading AJAX). */
    public function optionsByBlok(int $blokId): array
    {
        return $this->db->fetchAll(
            "SELECT k.id, k.nama, k.kapasitas,
                    (SELECT COUNT(*) FROM santri s WHERE s.kamar_id = k.id AND s.status = 'aktif') AS terisi
             FROM kamar k WHERE k.blok_id = ? ORDER BY k.nama ASC",
            [$blokId]
        );
    }

    public function countOccupied(): int
    {
        return (int) ($this->db->fetch(
            "SELECT COUNT(DISTINCT kamar_id) AS total FROM santri
             WHERE kamar_id IS NOT NULL AND status = 'aktif'"
        )['total'] ?? 0);
    }

    /**
     * Cek unik nama kamar dalam satu blok (BARU v16.1 —
     * menggantikan akses $db dari controller).
     */
    public function existsInBlok(string $nama, int $blokId, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT id FROM kamar WHERE blok_id = ? AND nama = ?';
        $params = [$blokId, $nama];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (bool) $this->db->fetch($sql . ' LIMIT 1', $params);
    }
}