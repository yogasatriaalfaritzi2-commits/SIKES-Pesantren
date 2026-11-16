-- ============================================================================
-- SIKES — MIGRASI v16: Modul "Segera" → Aktif
--   1. Pengasuhan  : tabel pengasuhan_catatan
--   2. Keuangan    : tabel keuangan_transaksi
--   (Rekam Medis & Laporan tidak butuh tabel baru — agregasi data existing)
--
-- IDEMPOTEN: CREATE TABLE IF NOT EXISTS + INSERT IGNORE
--   -> aman dijalankan berulang kali, TIDAK menghapus data yang sudah ada.
-- Import: phpMyAdmin -> Import -> pilih file ini -> Go
-- ============================================================================

USE sikes_pondok;

-- ============================================================================
-- 1. PENGASUHAN_CATATAN — perizinan & pelanggaran santri
-- ============================================================================
CREATE TABLE IF NOT EXISTS pengasuhan_catatan (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  santri_id  INT UNSIGNED NOT NULL,
  jenis      ENUM('izin','pelanggaran') NOT NULL,
  keterangan VARCHAR(255) NOT NULL,
  tanggal    DATE NOT NULL,
  tindakan   VARCHAR(255) DEFAULT NULL COMMENT 'tindak lanjut/sanksi',
  status     ENUM('berjalan','selesai') NOT NULL DEFAULT 'berjalan',
  petugas_id INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pcat_tanggal (tanggal),
  INDEX idx_pcat_jenis   (jenis),
  INDEX idx_pcat_status  (status),
  CONSTRAINT fk_pcat_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pcat_petugas FOREIGN KEY (petugas_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. KEUANGAN_TRANSAKSI — pemasukan & pengeluaran pondok
-- ============================================================================
CREATE TABLE IF NOT EXISTS keuangan_transaksi (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenis      ENUM('masuk','keluar') NOT NULL,
  kategori   VARCHAR(50) NOT NULL,
  jumlah     DECIMAL(14,2) NOT NULL,
  tanggal    DATE NOT NULL,
  keterangan VARCHAR(255) DEFAULT NULL,
  referensi  VARCHAR(100) DEFAULT NULL COMMENT 'no. bukti/faktur',
  user_id    INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ktran_tanggal  (tanggal),
  INDEX idx_ktran_jenis    (jenis),
  INDEX idx_ktran_kategori (kategori),
  CONSTRAINT fk_ktran_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. DEMO DATA (INSERT IGNORE — tidak duplikat bila dijalankan ulang)
-- ============================================================================
INSERT IGNORE INTO pengasuhan_catatan
(id, santri_id, jenis, keterangan, tanggal, tindakan, status, petugas_id) VALUES
(1, 2, 'izin', 'Izin pulang kampung 3 hari (acara keluarga).', CURDATE(), NULL, 'berjalan', 3),
(2, 3, 'pelanggaran', 'Keluar asrama malam tanpa izin musyrif.',
 DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Pembinaan apel malam; kewajiban piket tambahan.', 'selesai', 3);

INSERT IGNORE INTO keuangan_transaksi
(id, jenis, kategori, jumlah, tanggal, keterangan, referensi, user_id) VALUES
(1, 'masuk',  'iuran',       5000000, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Iuran kesehatan santri bulan ini', 'INV/001', 7),
(2, 'masuk',  'donasi',      2500000, DATE_SUB(CURDATE(), INTERVAL 5 DAY),  'Bantuan alumni untuk poskestren',  'DN/001',  7),
(3, 'keluar', 'pengadaan',   1850000, DATE_SUB(CURDATE(), INTERVAL 3 DAY),  'Pengadaan obat batch pertama',     'PO/001',  7),
(4, 'keluar', 'operasional',  450000, CURDATE(),                            'Operasional harian poskestren',    'OP/001',  7);

FLUSH TABLES;