<?php

namespace App\Models;

use App\Core\Model;

class Obat extends Model
{
    protected string $table = 'obat';

    public const KATEGORI = [
        'tablet'  => 'Tablet',
        'kapsul'  => 'Kapsul',
        'sirup'   => 'Sirup',
        'salep'   => 'Salep',
        'tetes'   => 'Tetes',
        'injeksi' => 'Injeksi',
        'lainnya' => 'Lainnya',
    ];

    public const SATUAN = [
        'tablet' => 'Tablet',
        'kapsul' => 'Kapsul',
        'botol'  => 'Botol',
        'tube'   => 'Tube',
        'strip'  => 'Strip',
        'sachet' => 'Sachet',
        'vial'   => 'Vial',
        'unit'   => 'Unit',
    ];

    /** Hari batas "mendekati kedaluwarsa". */
    public const EXP_WARNING_DAYS = 90;

    /* ================= Helper status ================= */

    public function isExpired(?string $tgl): bool
    {
        return $tgl !== null && $tgl !== '' && strtotime($tgl) < strtotime(date('Y-m-d'));
    }

    public function isNearExpiry(?string $tgl): bool
    {
        if ($tgl === null || $tgl === '') {
            return false;
        }
        $diff = (int) floor((strtotime($tgl) - strtotime(date('Y-m-d'))) / 86400);
        return $diff >= 0 && $diff <= self::EXP_WARNING_DAYS;
    }

    public function isLowStock(array $row): bool
    {
        return (int) $row['stok'] <= (int) $row['minimum_stok'];
    }

    /* ================= Master: daftar & detail ================= */

    public function paginate(int $page, int $perPage, array $f = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where  = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = 'o.nama LIKE ?';
            $params[] = "%{$f['q']}%";
        }
        if (!empty($f['kategori']) && isset(self::KATEGORI[$f['kategori']])) {
            $where[] = 'o.kategori = ?';
            $params[] = $f['kategori'];
        }
        if (!empty($f['flag'])) {
            if ($f['flag'] === 'menipis')     { $where[] = 'o.stok <= o.minimum_stok'; }
            if ($f['flag'] === 'kedaluwarsa') { $where[] = 'o.tanggal_kedaluwarsa < CURDATE()'; }
            if ($f['flag'] === 'mendekati')   {
                $where[] = 'o.tanggal_kedaluwarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL '
                    . self::EXP_WARNING_DAYS . ' DAY)';
            }
            if ($f['flag'] === 'aman')        {
                $where[] = 'o.stok > o.minimum_stok AND o.tanggal_kedaluwarsa > DATE_ADD(CURDATE(), INTERVAL '
                    . self::EXP_WARNING_DAYS . ' DAY)';
            }
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS total FROM obat o{$whereSql}",
            $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT o.* FROM obat o{$whereSql}
             ORDER BY (o.stok <= o.minimum_stok) DESC, o.tanggal_kedaluwarsa ASC, o.nama ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    /** Cek nama unik (master). */
    public function namaExists(string $nama, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT id FROM obat WHERE nama = ?';
        $params = [$nama];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (bool) $this->db->fetch($sql . ' LIMIT 1', $params);
    }

    /**
     * Cek apakah obat punya riwayat transaksi (BARU v16.1 —
     * menggantikan akses $db dari controller; proteksi hapus).
     */
    public function hasTransactions(int $id): bool
    {
        return (bool) $this->db->fetch(
            'SELECT id FROM obat_transaksi WHERE obat_id = ? LIMIT 1',
            [$id]
        );
    }

    /** Dropdown obat (form stok masuk & pemakaian). */
    public function options(): array
    {
        return $this->db->fetchAll(
            "SELECT id, nama, satuan, stok, harga, tanggal_kedaluwarsa FROM obat ORDER BY nama ASC"
        );
    }

    /* ================= Dashboard / peringatan ================= */

    public function count(): int
    {
        return (int) ($this->db->fetch(
            'SELECT COUNT(*) AS total FROM obat'
        )['total'] ?? 0);
    }

    public function countLowStock(): int
    {
        return (int) ($this->db->fetch(
            'SELECT COUNT(*) AS total FROM obat WHERE stok <= minimum_stok'
        )['total'] ?? 0);
    }

    public function countNearExpiry(): int
    {
        return (int) ($this->db->fetch(
            'SELECT COUNT(*) AS total FROM obat
             WHERE tanggal_kedaluwarsa >= CURDATE()
               AND tanggal_kedaluwarsa <= DATE_ADD(CURDATE(), INTERVAL ' . self::EXP_WARNING_DAYS . ' DAY)'
        )['total'] ?? 0);
    }

    public function countExpired(): int
    {
        return (int) ($this->db->fetch(
            'SELECT COUNT(*) AS total FROM obat WHERE tanggal_kedaluwarsa < CURDATE()'
        )['total'] ?? 0);
    }

    /** Nilai persediaan (stok × harga). */
    public function nilaiPersediaan(): float
    {
        return (float) ($this->db->fetch(
            'SELECT COALESCE(SUM(stok * harga), 0) AS total FROM obat'
        )['total'] ?? 0);
    }
}