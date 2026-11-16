-- ============================================================================
-- SIKES PONDOK PESANTREN — database/sikes_db.sql
-- ============================================================================
-- Sistem Informasi Kesehatan Pondok Pesantren
-- Versi    : 15.1 FINAL (gabungan Prompt/Tahap 1 s.d. 15)
-- Perbaikan vs 15.0 (anti error #1109 / dictionary korup):
--   1. Reset level DATABASE (DROP DATABASE) — lebih menyeluruh daripada
--      drop per-tabel; membersihkan entri dictionary yatim sekaligus.
--   2. FLUSH TABLES di akhir — memaksa MySQL menyegarkan cache tabel
--      sehingga information_schema langsung sinkron setelah import.
-- Import   : phpMyAdmin -> Import -> pilih file ini -> Go
--            (WAJIB: Stop MySQL + hapus folder mysql/data/sikes_pondok dulu,
--             lihat instruksi — agar sisa korupsi lama hilang total)
--
-- ISI      : 32 tabel + seed lengkap
-- AKUN DEMO (semua password: "password")
--   admin | medis | pengasuhan | keamanan | santriadmin |
--   dapur | bendahara | kyai | santri | wali
-- ============================================================================

-- Reset level DATABASE: menghapus seluruh skema + entri dictionary yatim
DROP DATABASE IF EXISTS sikes_pondok;

CREATE DATABASE sikes_pondok
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sikes_pondok;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. ROLES — 10 role/hak akses
-- ============================================================================
CREATE TABLE roles (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  is_system   TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = role terlindungi (bypass permission, tidak bisa dihapus/diubah)',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. PERMISSIONS — 57 permission (format slug: modul.aksi)
-- ============================================================================
CREATE TABLE permissions (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug   VARCHAR(100) NOT NULL UNIQUE,
  name   VARCHAR(150) NOT NULL,
  module VARCHAR(50)  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. USERS — akun pengguna aplikasi
-- ============================================================================
CREATE TABLE users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama       VARCHAR(100) NOT NULL,
  username   VARCHAR(50)  NOT NULL UNIQUE,
  email      VARCHAR(100) DEFAULT NULL,
  password   VARCHAR(255) NOT NULL COMMENT 'bcrypt via password_hash()',
  role_id    INT UNSIGNED NOT NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. ROLE_PERMISSIONS — pivot role <-> permission
-- ============================================================================
CREATE TABLE role_permissions (
  role_id       INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. ACTIVITY_LOGS — jejak aktivitas (audit)
-- ============================================================================
CREATE TABLE activity_logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED DEFAULT NULL COMMENT 'NULL = percobaan login gagal/anonim',
  action      VARCHAR(50)  NOT NULL COMMENT 'login|logout|create|update|delete|read',
  module      VARCHAR(50)  NOT NULL DEFAULT 'sistem',
  description VARCHAR(500) DEFAULT NULL,
  ip_address  VARCHAR(45)  DEFAULT NULL,
  user_agent  VARCHAR(255) DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_action  (action),
  INDEX idx_logs_created (created_at),
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. WALI_SANTRI
-- ============================================================================
CREATE TABLE wali_santri (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama       VARCHAR(100) NOT NULL,
  no_telepon VARCHAR(20)  NOT NULL,
  pekerjaan  VARCHAR(100) DEFAULT NULL,
  alamat     VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. ASRAMA — putra/putri
-- ============================================================================
CREATE TABLE asrama (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama       VARCHAR(100) NOT NULL,
  jenis      ENUM('putra','putri') NOT NULL,
  lokasi     VARCHAR(150) DEFAULT NULL,
  keterangan VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. BLOK — pembagian dalam asrama
-- ============================================================================
CREATE TABLE blok (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asrama_id  INT UNSIGNED NOT NULL,
  nama       VARCHAR(100) NOT NULL,
  keterangan VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_blok (asrama_id, nama),
  CONSTRAINT fk_blok_asrama FOREIGN KEY (asrama_id) REFERENCES asrama (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. KAMAR — kamar per blok + kapasitas
-- ============================================================================
CREATE TABLE kamar (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  blok_id    INT UNSIGNED NOT NULL,
  nama       VARCHAR(50)  NOT NULL,
  kapasitas  INT UNSIGNED NOT NULL DEFAULT 8,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_kamar (blok_id, nama),
  CONSTRAINT fk_kamar_blok FOREIGN KEY (blok_id) REFERENCES blok (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. SANTRI — hanya simpan kamar_id; asrama/blok diturunkan via JOIN
-- ============================================================================
CREATE TABLE santri (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nis           VARCHAR(20) NOT NULL UNIQUE,
  nisn          VARCHAR(15) DEFAULT NULL UNIQUE,
  nama          VARCHAR(100) NOT NULL,
  jenis_kelamin ENUM('L','P') NOT NULL,
  tempat_lahir  VARCHAR(100) DEFAULT NULL,
  tanggal_lahir DATE DEFAULT NULL,
  alamat        VARCHAR(255) DEFAULT NULL,
  no_telepon    VARCHAR(20) DEFAULT NULL,
  wali_id       INT UNSIGNED DEFAULT NULL,
  kamar_id      INT UNSIGNED DEFAULT NULL COMMENT 'NULL = belum ditempatkan',
  status        ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_santri_nama (nama),
  INDEX idx_santri_status (status),
  CONSTRAINT fk_santri_wali FOREIGN KEY (wali_id) REFERENCES wali_santri (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_santri_kamar FOREIGN KEY (kamar_id) REFERENCES kamar (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11. SKRINING_KESEHATAN — skrining awal (1 record per santri)
--     IMT dihitung aplikasi (rumus baku) — BUKAN diagnosis.
-- ============================================================================
CREATE TABLE skrining_kesehatan (
  id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  santri_id               INT UNSIGNED NOT NULL,
  tanggal_skrining        DATE NOT NULL,
  riwayat_penyakit_bawaan ENUM('tidak','ya') NOT NULL DEFAULT 'tidak',
  riwayat_bawaan_ket      VARCHAR(255) DEFAULT NULL,
  alergi_obat             ENUM('tidak','ya') NOT NULL DEFAULT 'tidak',
  alergi_obat_ket         VARCHAR(255) DEFAULT NULL,
  alergi_makanan          ENUM('tidak','ya') NOT NULL DEFAULT 'tidak',
  alergi_makanan_ket      VARCHAR(255) DEFAULT NULL,
  asma                    ENUM('tidak','ya') NOT NULL DEFAULT 'tidak',
  asma_ket                VARCHAR(255) DEFAULT NULL,
  epilepsi                ENUM('tidak','ya') NOT NULL DEFAULT 'tidak',
  epilepsi_ket            VARCHAR(255) DEFAULT NULL,
  penyakit_lain           ENUM('tidak','ya') NOT NULL DEFAULT 'tidak',
  penyakit_lain_ket       VARCHAR(255) DEFAULT NULL,
  tinggi_cm               DECIMAL(5,2) DEFAULT NULL,
  berat_kg                DECIMAL(5,2) DEFAULT NULL,
  imt                     DECIMAL(4,2) DEFAULT NULL,
  kategori_imt            VARCHAR(30)  DEFAULT NULL,
  golongan_darah          ENUM('A','B','AB','O','-') NOT NULL DEFAULT '-',
  rhesus                  ENUM('positif','negatif','-') NOT NULL DEFAULT '-',
  buta_warna              ENUM('belum','normal','kelainan') NOT NULL DEFAULT 'belum',
  buta_warna_ket          VARCHAR(255) DEFAULT NULL,
  gigi_status             ENUM('belum','baik','perlu_perawatan','perlu_rujukan') NOT NULL DEFAULT 'belum',
  gigi_ket                VARCHAR(255) DEFAULT NULL,
  fisik_status            ENUM('belum','sehat','ada_keluhan','perlu_rujukan') NOT NULL DEFAULT 'belum',
  fisik_ket               VARCHAR(255) DEFAULT NULL,
  catatan_medis           TEXT DEFAULT NULL,
  petugas_id              INT UNSIGNED DEFAULT NULL,
  created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_skrining_santri (santri_id),
  CONSTRAINT fk_skrining_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_skrining_petugas FOREIGN KEY (petugas_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 12. SKRINING_RIWAYAT — audit skrining
-- ============================================================================
CREATE TABLE skrining_riwayat (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  skrining_id  INT UNSIGNED NOT NULL,
  aksi         ENUM('buat','ubah') NOT NULL,
  data_sesudah TEXT DEFAULT NULL COMMENT 'JSON snapshot',
  user_id      INT UNSIGNED DEFAULT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_riwayat_skrining (skrining_id),
  CONSTRAINT fk_riwayat_skrining FOREIGN KEY (skrining_id) REFERENCES skrining_kesehatan (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_riwayat_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 13. PEMERIKSAAN — klinik harian
--     Rekaman medis TIDAK dihapus — hanya dibatalkan (status).
-- ============================================================================
CREATE TABLE pemeriksaan (
  id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  santri_id          INT UNSIGNED NOT NULL,
  tanggal_kunjungan  DATETIME NOT NULL,
  jenis_kunjungan    ENUM('rawat_jalan','darurat','kontrol','preventif') NOT NULL DEFAULT 'rawat_jalan',
  keluhan            TEXT NOT NULL,
  pemeriksaan_awal   TEXT DEFAULT NULL,
  suhu_c             DECIMAL(4,1) DEFAULT NULL COMMENT '33.0 - 45.0',
  td_sistol          SMALLINT UNSIGNED DEFAULT NULL COMMENT '50 - 300 mmHg',
  td_diastol         SMALLINT UNSIGNED DEFAULT NULL COMMENT '20 - 200 mmHg',
  nadi               SMALLINT UNSIGNED DEFAULT NULL COMMENT '20 - 250 x/menit',
  pernapasan         SMALLINT UNSIGNED DEFAULT NULL COMMENT '5 - 80 x/menit',
  hasil_pemeriksaan  TEXT DEFAULT NULL,
  diagnosis          TEXT DEFAULT NULL COMMENT 'isian manual tenaga medis',
  tindakan           TEXT DEFAULT NULL,
  resep_obat         TEXT DEFAULT NULL,
  catatan_lanjutan   TEXT DEFAULT NULL,
  tanggal_kontrol    DATE DEFAULT NULL,
  status             ENUM('menunggu','selesai','batal') NOT NULL DEFAULT 'menunggu',
  pemeriksa_id       INT UNSIGNED DEFAULT NULL,
  petugas_id         INT UNSIGNED DEFAULT NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pemeriksaan_tanggal (tanggal_kunjungan),
  INDEX idx_pemeriksaan_status  (status),
  CONSTRAINT fk_pemeriksaan_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pemeriksaan_pemeriksa FOREIGN KEY (pemeriksa_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_pemeriksaan_petugas FOREIGN KEY (petugas_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 14. PEMERIKSAAN_RIWAYAT — audit kunjungan
-- ============================================================================
CREATE TABLE pemeriksaan_riwayat (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pemeriksaan_id BIGINT UNSIGNED NOT NULL,
  aksi           ENUM('registrasi','periksa','ubah','batal') NOT NULL,
  data_sesudah   TEXT DEFAULT NULL COMMENT 'JSON snapshot + ringkasan',
  user_id        INT UNSIGNED DEFAULT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_priwayat_pemeriksaan (pemeriksaan_id),
  CONSTRAINT fk_priwayat_pemeriksaan FOREIGN KEY (pemeriksaan_id) REFERENCES pemeriksaan (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_priwayat_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 15. INSPEKSI_KAMAR — sanitasi (8 parameter; baik=2 cukup=1 buruk=0; maks 16)
--     Status: >=75% Baik | 50-74% Perlu Perhatian | <50% Risiko Tinggi
-- ============================================================================
CREATE TABLE inspeksi_kamar (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kamar_id          INT UNSIGNED NOT NULL,
  tanggal_inspeksi  DATE NOT NULL,
  ventilasi         ENUM('baik','cukup','buruk') NOT NULL DEFAULT 'baik',
  lantai            ENUM('baik','cukup','buruk') NOT NULL DEFAULT 'baik',
  kasur             ENUM('baik','cukup','buruk') NOT NULL DEFAULT 'baik',
  pakaian           ENUM('baik','cukup','buruk') NOT NULL DEFAULT 'baik',
  jemur_pakaian     ENUM('baik','cukup','buruk') NOT NULL DEFAULT 'baik',
  kamar_mandi       ENUM('baik','cukup','buruk') NOT NULL DEFAULT 'baik',
  kepadatan         ENUM('baik','cukup','buruk') NOT NULL DEFAULT 'baik',
  lingkungan        ENUM('baik','cukup','buruk') NOT NULL DEFAULT 'baik',
  skor              TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-16, dihitung aplikasi',
  persentase        DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT '0-100, dihitung aplikasi',
  status            ENUM('baik','perlu_perhatian','risiko_tinggi') NOT NULL DEFAULT 'baik',
  catatan_petugas   TEXT DEFAULT NULL,
  petugas_id        INT UNSIGNED DEFAULT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inspeksi (kamar_id, tanggal_inspeksi),
  INDEX idx_inspeksi_tanggal (tanggal_inspeksi),
  INDEX idx_inspeksi_status  (status),
  CONSTRAINT fk_inspeksi_kamar FOREIGN KEY (kamar_id) REFERENCES kamar (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_inspeksi_petugas FOREIGN KEY (petugas_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 16. INSPEKSI_RIWAYAT — audit inspeksi
-- ============================================================================
CREATE TABLE inspeksi_riwayat (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspeksi_id  BIGINT UNSIGNED NOT NULL,
  aksi         ENUM('buat','ubah') NOT NULL,
  data_sesudah TEXT DEFAULT NULL,
  user_id      INT UNSIGNED DEFAULT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_iriwayat_inspeksi (inspeksi_id),
  CONSTRAINT fk_iriwayat_inspeksi FOREIGN KEY (inspeksi_id) REFERENCES inspeksi_kamar (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_iriwayat_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 17. KASUS_MENULAR — kasus penyakit menular
-- ============================================================================
CREATE TABLE kasus_menular (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  santri_id       INT UNSIGNED NOT NULL,
  penyakit        VARCHAR(150) NOT NULL,
  tanggal_mulai   DATE NOT NULL,
  tanggal_selesai DATE DEFAULT NULL,
  status_kasus    ENUM('aktif','selesai') NOT NULL DEFAULT 'aktif',
  catatan         TEXT DEFAULT NULL,
  dilaporkan_id   INT UNSIGNED DEFAULT NULL COMMENT 'tenaga medis pelapor',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_kasus_status (status_kasus),
  INDEX idx_kasus_mulai  (tanggal_mulai),
  CONSTRAINT fk_kasus_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_kasus_pelapor FOREIGN KEY (dilaporkan_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 18. TRACING_KONTAK — kontak potensial per kasus
--     Berada di daftar ini TIDAK berarti pasti tertular — hanya penanda
--     pernah berbagi lingkungan kamar dengan kasus.
-- ============================================================================
CREATE TABLE tracing_kontak (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kasus_id          BIGINT UNSIGNED NOT NULL,
  santri_id         INT UNSIGNED NOT NULL,
  status            ENUM('belum_diperiksa','dalam_pemeriksaan','sudah_diperiksa','perlu_tindak_lanjut')
                    NOT NULL DEFAULT 'belum_diperiksa',
  pemeriksaan_id    BIGINT UNSIGNED DEFAULT NULL COMMENT 'pemeriksaan preventif terkait',
  hasil_pemeriksaan VARCHAR(500) DEFAULT NULL,
  catatan           TEXT DEFAULT NULL,
  ditangani_id      INT UNSIGNED DEFAULT NULL,
  examined_at       DATETIME DEFAULT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_kontak (kasus_id, santri_id),
  INDEX idx_kontak_status (status),
  CONSTRAINT fk_kontak_kasus FOREIGN KEY (kasus_id) REFERENCES kasus_menular (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_kontak_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_kontak_pemeriksaan FOREIGN KEY (pemeriksaan_id) REFERENCES pemeriksaan (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_kontak_petugas FOREIGN KEY (ditangani_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 19. TRACING_RIWAYAT — histori tracing per kasus
-- ============================================================================
CREATE TABLE tracing_riwayat (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kasus_id   BIGINT UNSIGNED NOT NULL,
  kontak_id  BIGINT UNSIGNED DEFAULT NULL,
  aksi       ENUM('kasus_dibuat','kontak_dibuat','kontak_disinkron','pemeriksaan_dimulai',
                  'hasil_dicatat','kasus_ditutup') NOT NULL,
  keterangan VARCHAR(500) DEFAULT NULL,
  user_id    INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_triwayat_kasus (kasus_id),
  CONSTRAINT fk_triwayat_kasus FOREIGN KEY (kasus_id) REFERENCES kasus_menular (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_triwayat_kontak FOREIGN KEY (kontak_id) REFERENCES tracing_kontak (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_triwayat_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 20. PIKET_JADWAL — piket kebersihan massal
--     Alur status satu arah: terjadwal -> berlangsung -> selesai
-- ============================================================================
CREATE TABLE piket_jadwal (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenis            ENUM('jemur_kasur','kerja_bakti','pembersihan_kamar',
                        'sanitasi_kamar_mandi','pemeriksaan_lingkungan') NOT NULL,
  nama_kegiatan    VARCHAR(150) NOT NULL,
  lokasi           VARCHAR(150) NOT NULL,
  tanggal          DATE NOT NULL,
  waktu_mulai      TIME NOT NULL,
  waktu_selesai    TIME DEFAULT NULL,
  penanggung_jawab VARCHAR(150) NOT NULL,
  status           ENUM('terjadwal','berlangsung','selesai') NOT NULL DEFAULT 'terjadwal',
  catatan          TEXT DEFAULT NULL,
  dibuat_id        INT UNSIGNED DEFAULT NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_piket_tanggal (tanggal),
  INDEX idx_piket_status  (status),
  INDEX idx_piket_jenis   (jenis),
  CONSTRAINT fk_piket_dibuat FOREIGN KEY (dibuat_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 21. PIKET_RIWAYAT — audit jadwal (constraint unik: fk_piketriwayat_*)
-- ============================================================================
CREATE TABLE piket_riwayat (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jadwal_id  BIGINT UNSIGNED NOT NULL,
  aksi       ENUM('buat','ubah','mulai','selesai') NOT NULL,
  keterangan VARCHAR(500) DEFAULT NULL,
  user_id    INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_piket_riwayat (jadwal_id),
  CONSTRAINT fk_piketriwayat_jadwal FOREIGN KEY (jadwal_id) REFERENCES piket_jadwal (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_piketriwayat_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 22. SURAT_IZIN — E-Surat Izin Sakit
--     token  : 64 hex acak (random_bytes) — kunci QR verifikasi publik
--     status : aktif | kadaluarsa (self-heal aplikasi) | dibatalkan
--     Surat immutable: hanya terbit & batal.
-- ============================================================================
CREATE TABLE surat_izin (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nomor_surat       VARCHAR(60) NOT NULL UNIQUE,
  token             CHAR(64) NOT NULL UNIQUE COMMENT 'kunci QR verifikasi publik',
  santri_id         INT UNSIGNED NOT NULL,
  pemeriksaan_id    BIGINT UNSIGNED DEFAULT NULL COMMENT 'rujukan pemeriksaan klinik (opsional)',
  tanggal_mulai     DATE NOT NULL,
  tanggal_selesai   DATE NOT NULL,
  keterangan_medis  TEXT NOT NULL COMMENT 'keterangan untuk keperluan administrasi',
  status            ENUM('aktif','kadaluarsa','dibatalkan') NOT NULL DEFAULT 'aktif',
  alasan_pembatalan VARCHAR(255) DEFAULT NULL,
  diterbitkan_id    INT UNSIGNED DEFAULT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_surat_status  (status),
  INDEX idx_surat_mulai   (tanggal_mulai),
  INDEX idx_surat_selesai (tanggal_selesai),
  CONSTRAINT fk_surat_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_surat_pemeriksaan FOREIGN KEY (pemeriksaan_id) REFERENCES pemeriksaan (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_surat_petugas FOREIGN KEY (diterbitkan_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 23. SURAT_IZIN_RIWAYAT — audit terbit/batal/verifikasi
--     user_id NULL pada 'diverifikasi' = verifikasi publik via QR
-- ============================================================================
CREATE TABLE surat_izin_riwayat (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  surat_id   BIGINT UNSIGNED NOT NULL,
  aksi       ENUM('terbit','batal','diverifikasi') NOT NULL,
  keterangan VARCHAR(500) DEFAULT NULL,
  user_id    INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sriwayat_surat (surat_id),
  CONSTRAINT fk_sriwayat_surat FOREIGN KEY (surat_id) REFERENCES surat_izin (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sriwayat_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 24. KEPULANGAN — workflow kepulangan santri sakit
--     Tahap 1 rekomendasi medis -> 2 approval keamanan ->
--     3 approval pengasuhan/kyai -> 4 izin keluar gerbang.
-- ============================================================================
CREATE TABLE kepulangan (
  id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  santri_id          INT UNSIGNED NOT NULL,
  pemeriksaan_id     BIGINT UNSIGNED DEFAULT NULL COMMENT 'dasar rekomendasi (opsional)',
  alasan_medis       TEXT NOT NULL COMMENT 'rekomendasi tenaga medis',
  alamat_tujuan      VARCHAR(255) NOT NULL,
  tanggal_pengajuan  DATE NOT NULL,
  status             ENUM('menunggu','disetujui','ditolak','selesai') NOT NULL DEFAULT 'menunggu',
  medis_id           INT UNSIGNED DEFAULT NULL,
  medis_catatan      TEXT DEFAULT NULL,
  keamanan_id        INT UNSIGNED DEFAULT NULL,
  keamanan_status    ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  keamanan_catatan   VARCHAR(500) DEFAULT NULL,
  keamanan_at        DATETIME DEFAULT NULL,
  pengasuhan_id      INT UNSIGNED DEFAULT NULL,
  pengasuhan_status  ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  pengasuhan_catatan VARCHAR(500) DEFAULT NULL,
  pengasuhan_at      DATETIME DEFAULT NULL,
  gerbang_id         INT UNSIGNED DEFAULT NULL,
  gerbang_at         DATETIME DEFAULT NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_kepulangan_status (status),
  CONSTRAINT fk_kepulangan_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_kepulangan_pemeriksaan FOREIGN KEY (pemeriksaan_id) REFERENCES pemeriksaan (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_kepulangan_medis FOREIGN KEY (medis_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_kepulangan_keamanan FOREIGN KEY (keamanan_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_kepulangan_pengasuhan FOREIGN KEY (pengasuhan_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_kepulangan_gerbang FOREIGN KEY (gerbang_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 25. KEPULANGAN_RIWAYAT — histori seluruh proses
-- ============================================================================
CREATE TABLE kepulangan_riwayat (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kepulangan_id BIGINT UNSIGNED NOT NULL,
  aksi          ENUM('rekomendasi','keamanan','pengasuhan','gerbang') NOT NULL,
  keterangan    VARCHAR(500) DEFAULT NULL,
  user_id       INT UNSIGNED DEFAULT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_kriwayat_kepulangan (kepulangan_id),
  CONSTRAINT fk_kriwayat_kepulangan FOREIGN KEY (kepulangan_id) REFERENCES kepulangan (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_kriwayat_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 26. BUKU_TAMU — buku tamu penjenguk santri sakit
-- ============================================================================
CREATE TABLE buku_tamu (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_penjenguk VARCHAR(100) NOT NULL,
  hubungan       VARCHAR(50)  NOT NULL,
  santri_id      INT UNSIGNED NOT NULL,
  no_telepon     VARCHAR(20) DEFAULT NULL,
  tanggal        DATE NOT NULL,
  waktu_datang   TIME NOT NULL,
  waktu_pulang   TIME DEFAULT NULL COMMENT 'NULL = masih di dalam',
  tujuan         VARCHAR(255) NOT NULL,
  catatan        TEXT DEFAULT NULL,
  petugas_id     INT UNSIGNED DEFAULT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tamu_tanggal (tanggal),
  CONSTRAINT fk_tamu_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_tamu_petugas FOREIGN KEY (petugas_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 27. GIZI_DIET — rekomendasi diet khusus (manual tenaga medis, bukan otomatis)
-- ============================================================================
CREATE TABLE gizi_diet (
  id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  santri_id             INT UNSIGNED NOT NULL,
  tanggal               DATE NOT NULL,
  jenis_diet            ENUM('bubur_halus','tidak_pedas','diet_lunak','lainnya') NOT NULL,
  jenis_lain            VARCHAR(100) DEFAULT NULL COMMENT 'wajib jika jenis_diet = lainnya',
  catatan_medis         TEXT DEFAULT NULL,
  status_distribusi     ENUM('belum','siap','selesai') NOT NULL DEFAULT 'belum',
  distribusi_at         DATETIME DEFAULT NULL,
  distribusi_petugas_id INT UNSIGNED DEFAULT NULL COMMENT 'petugas dapur',
  rekomendasi_id        INT UNSIGNED DEFAULT NULL COMMENT 'tenaga medis pemberi rekomendasi',
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_gizi (santri_id, tanggal, jenis_diet),
  INDEX idx_gizi_tanggal (tanggal),
  INDEX idx_gizi_status (status_distribusi),
  CONSTRAINT fk_gizi_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_gizi_rekomendasi FOREIGN KEY (rekomendasi_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_gizi_distribusi FOREIGN KEY (distribusi_petugas_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 28. BPJS_SANTRI — asuransi BPJS (1 record per santri)
-- ============================================================================
CREATE TABLE bpjs_santri (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  santri_id          INT UNSIGNED NOT NULL,
  nomor_bpjs         VARCHAR(20) NOT NULL,
  status             ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  faskes_tk1         VARCHAR(150) NOT NULL,
  catatan            VARCHAR(255) DEFAULT NULL,
  tanggal_pembaruan  DATE NOT NULL,
  petugas_id         INT UNSIGNED DEFAULT NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bpjs_santri (santri_id),
  UNIQUE KEY uq_bpjs_nomor (nomor_bpjs),
  CONSTRAINT fk_bpjs_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_bpjs_petugas FOREIGN KEY (petugas_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 29. RUJUKAN — surat rujukan ke faskes eksternal (nomor otomatis, immutable)
-- ============================================================================
CREATE TABLE rujukan (
  id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nomor_surat        VARCHAR(60) NOT NULL UNIQUE,
  santri_id          INT UNSIGNED NOT NULL,
  pemeriksaan_id     BIGINT UNSIGNED DEFAULT NULL COMMENT 'dasar pemeriksaan (opsional)',
  tujuan_faskes      VARCHAR(150) NOT NULL,
  keluhan            TEXT NOT NULL,
  hasil_pemeriksaan  TEXT NOT NULL,
  diagnosis_awal     TEXT NOT NULL COMMENT 'keterangan awal tenaga medis',
  tindakan_sementara TEXT DEFAULT NULL,
  rekomendasi        TEXT NOT NULL,
  tanggal_rujukan    DATE NOT NULL,
  penerbit_id        INT UNSIGNED DEFAULT NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_rujukan_tanggal (tanggal_rujukan),
  CONSTRAINT fk_rujukan_santri FOREIGN KEY (santri_id) REFERENCES santri (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_rujukan_pemeriksaan FOREIGN KEY (pemeriksaan_id) REFERENCES pemeriksaan (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_rujukan_penerbit FOREIGN KEY (penerbit_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 30. RUJUKAN_RIWAYAT — histori terbit/cetak
-- ============================================================================
CREATE TABLE rujukan_riwayat (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rujukan_id BIGINT UNSIGNED NOT NULL,
  aksi       ENUM('terbit','dicetak') NOT NULL,
  keterangan VARCHAR(500) DEFAULT NULL,
  user_id    INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rriwayat_rujukan (rujukan_id),
  CONSTRAINT fk_rriwayat_rujukan FOREIGN KEY (rujukan_id) REFERENCES rujukan (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_rriwayat_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 31. OBAT — master obat (stok HANYA berubah via transaksi aplikasi)
-- ============================================================================
CREATE TABLE obat (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama                VARCHAR(150) NOT NULL,
  kategori            ENUM('tablet','kapsul','sirup','salep','tetes','injeksi','lainnya') NOT NULL DEFAULT 'tablet',
  satuan              ENUM('tablet','kapsul','botol','tube','strip','sachet','vial','unit') NOT NULL DEFAULT 'tablet',
  stok                INT UNSIGNED NOT NULL DEFAULT 0,
  minimum_stok        INT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'batas peringatan stok menipis',
  harga               DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'harga per satuan',
  tanggal_kedaluwarsa DATE NOT NULL,
  catatan             VARCHAR(255) DEFAULT NULL,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_obat_nama (nama),
  INDEX idx_obat_kedaluwarsa (tanggal_kedaluwarsa),
  INDEX idx_obat_stok (stok)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 32. OBAT_TRANSAKSI — stok masuk/keluar
--     harga_satuan DI-SNAPSHOT; stok berubah via DATABASE TRANSACTION aplikasi.
-- ============================================================================
CREATE TABLE obat_transaksi (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  obat_id        INT UNSIGNED NOT NULL,
  jenis          ENUM('masuk','keluar') NOT NULL,
  jumlah         INT UNSIGNED NOT NULL,
  harga_satuan   DECIMAL(12,2) NOT NULL DEFAULT 0,
  pemeriksaan_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'terkait pemakaian pada pemeriksaan',
  keterangan     VARCHAR(255) DEFAULT NULL,
  user_id        INT UNSIGNED DEFAULT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_otrans_obat (obat_id),
  INDEX idx_otrans_jenis (jenis),
  INDEX idx_otrans_created (created_at),
  CONSTRAINT fk_otrans_obat FOREIGN KEY (obat_id) REFERENCES obat (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_otrans_pemeriksaan FOREIGN KEY (pemeriksaan_id) REFERENCES pemeriksaan (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_otrans_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEED 1: ROLES (10)
-- ============================================================================
INSERT INTO roles (id, name, slug, description, is_system) VALUES
(1,  'Super Admin',               'super-admin',  'Akses penuh ke seluruh sistem.',        1),
(2,  'Tenaga Medis / Poskestren', 'tenaga-medis', 'Petugas kesehatan poskestren.',         0),
(3,  'Pengasuhan',                'pengasuhan',   'Pembina & musyrif/musyrifah asrama.',   0),
(4,  'Keamanan',                  'keamanan',     'Petugas keamanan pondok.',              0),
(5,  'Admin Santri',              'admin-santri', 'Pengelola data santri & wali.',         0),
(6,  'Bagian Dapur / Gizi',       'dapur-gizi',   'Pengelola dapur dan jatah gizi.',       0),
(7,  'Bendahara',                 'bendahara',    'Pengelola keuangan pondok.',            0),
(8,  'Kyai / Pimpinan',           'kyai',         'Pimpinan pondok (akses pemantauan).',   0),
(9,  'Santri',                    'santri',       'Akun santri.',                          0),
(10, 'Wali Santri',               'wali-santri',  'Akun wali santri.',                     0);

-- ============================================================================
-- SEED 2: PERMISSIONS (57)
-- ============================================================================
INSERT INTO permissions (id, slug, name, module) VALUES
(1,  'users.view',         'Melihat daftar pengguna',        'users'),
(2,  'users.create',       'Menambah pengguna',              'users'),
(3,  'users.edit',         'Mengubah pengguna',              'users'),
(4,  'users.delete',       'Menghapus pengguna',             'users'),
(5,  'roles.view',         'Melihat daftar role',            'roles'),
(6,  'roles.manage',       'Mengelola role & permission',    'roles'),
(7,  'logs.view',          'Melihat log aktivitas',          'logs'),
(8,  'santri.view',        'Melihat data santri',            'santri'),
(9,  'santri.create',      'Menambah santri',                'santri'),
(10, 'santri.edit',        'Mengubah data santri',           'santri'),
(11, 'santri.delete',      'Menghapus data santri',          'santri'),
(12, 'pemeriksaan.view',   'Melihat pemeriksaan kesehatan',  'kesehatan'),
(13, 'pemeriksaan.create', 'Mencatat pemeriksaan',           'kesehatan'),
(14, 'pemeriksaan.edit',   'Mengubah pemeriksaan',           'kesehatan'),
(15, 'rekam_medis.view',   'Melihat rekam medis',            'kesehatan'),
(16, 'rekam_medis.create', 'Membuat rekam medis',            'kesehatan'),
(17, 'pengasuhan.view',    'Melihat data pengasuhan',        'pengasuhan'),
(18, 'pengasuhan.create',  'Mencatat perizinan/pelanggaran', 'pengasuhan'),
(19, 'pengasuhan.edit',    'Mengubah data pengasuhan',       'pengasuhan'),
(20, 'keamanan.view',      'Melihat data keamanan',          'keamanan'),
(21, 'keamanan.create',    'Mencatat laporan keamanan',      'keamanan'),
(22, 'gizi.view',          'Melihat daftar diet khusus',     'gizi'),
(23, 'gizi.create',        'Menambah data gizi (legacy)',    'gizi'),
(24, 'gizi.edit',          'Mengubah data gizi (legacy)',    'gizi'),
(25, 'keuangan.view',      'Melihat transaksi keuangan',     'keuangan'),
(26, 'keuangan.create',    'Mencatat transaksi',             'keuangan'),
(27, 'keuangan.manage',    'Verifikasi transaksi',           'keuangan'),
(28, 'laporan.view',       'Melihat laporan',                'laporan'),
(29, 'laporan.export',     'Mengekspor laporan',             'laporan'),
(30, 'profile.edit',       'Mengedit profil sendiri',        'profil'),
(31, 'wali.view',          'Melihat data wali santri',       'wali'),
(32, 'wali.create',        'Menambah wali santri',           'wali'),
(33, 'wali.edit',          'Mengubah wali santri',           'wali'),
(34, 'wali.delete',        'Menghapus wali santri',          'wali'),
(35, 'asrama.view',        'Melihat asrama/blok/kamar',      'asrama'),
(36, 'asrama.manage',      'Mengelola asrama/blok/kamar',    'asrama'),
(37, 'sanitasi.view',      'Melihat inspeksi sanitasi',      'sanitasi'),
(38, 'sanitasi.create',    'Melakukan inspeksi sanitasi',    'sanitasi'),
(39, 'sanitasi.edit',      'Mengubah/menghapus inspeksi',    'sanitasi'),
(40, 'piket.view',         'Melihat jadwal piket kebersihan',  'piket'),
(41, 'piket.manage',       'Mengelola jadwal piket kebersihan','piket'),
(42, 'surat.view',         'Melihat e-surat izin sakit',       'surat'),
(43, 'surat.create',       'Menerbitkan e-surat izin sakit',   'surat'),
(44, 'surat.edit',         'Membatalkan e-surat izin sakit',   'surat'),
(45, 'kepulangan.view',    'Melihat workflow kepulangan santri','kepulangan'),
(46, 'kepulangan.manage',  'Memproses workflow kepulangan',     'kepulangan'),
(47, 'buku_tamu.view',     'Melihat buku tamu penjenguk',       'buku_tamu'),
(48, 'buku_tamu.manage',   'Mencatat buku tamu penjenguk',      'buku_tamu'),
(49, 'gizi.rekomendasi',   'Menerbitkan rekomendasi diet khusus','gizi'),
(50, 'gizi.distribusi',    'Mengubah status distribusi dapur',  'gizi'),
(51, 'bpjs.view',          'Melihat data asuransi BPJS santri', 'asuransi'),
(52, 'bpjs.manage',        'Mengelola data asuransi BPJS',      'asuransi'),
(53, 'rujukan.view',       'Melihat surat rujukan eksternal',   'rujukan'),
(54, 'rujukan.create',     'Menerbitkan surat rujukan',         'rujukan'),
(55, 'obat.view',          'Melihat master obat & laporan',     'obat'),
(56, 'obat.manage',        'Mengelola master obat & stok masuk','obat'),
(57, 'obat.pemakaian',     'Mencatat pemakaian obat',           'obat');

-- ============================================================================
-- SEED 3: PEMETAAN ROLE -> PERMISSION
--   Super Admin (role 1) tidak butuh baris: bypass via is_system=1 di kode.
-- ============================================================================
INSERT INTO role_permissions (role_id, permission_id) VALUES
-- Tenaga Medis / Poskestren (2)
(2,8),(2,12),(2,13),(2,14),(2,15),(2,16),(2,29),(2,35),
(2,42),(2,43),(2,44),(2,45),(2,46),(2,22),(2,49),
(2,51),(2,52),(2,53),(2,54),(2,55),(2,56),(2,57),
-- Pengasuhan (3)
(3,8),(3,17),(3,18),(3,19),(3,29),(3,31),(3,35),(3,36),
(3,37),(3,38),(3,39),(3,40),(3,41),(3,42),(3,45),(3,46),
-- Keamanan (4)
(4,8),(4,20),(4,21),(4,37),(4,40),(4,45),(4,46),(4,47),(4,48),
-- Admin Santri (5)
(5,8),(5,9),(5,10),(5,11),(5,29),(5,31),(5,32),(5,33),(5,34),(5,35),(5,42),(5,45),(5,51),(5,52),
-- Dapur / Gizi (6)
(6,22),(6,23),(6,24),(6,50),
-- Bendahara (7)
(7,25),(7,26),(7,27),(7,29),(7,55),
-- Kyai / Pimpinan (8)
(8,7),(8,8),(8,12),(8,15),(8,17),(8,20),(8,22),(8,25),(8,28),(8,29),(8,31),(8,35),
(8,37),(8,40),(8,42),(8,45),(8,46),(8,51),(8,53),(8,55),
-- Santri (9) & Wali Santri (10)
(9,30),(10,30);

-- ============================================================================
-- SEED 4: AKUN DEMO (semua password: "password")
-- ============================================================================
INSERT INTO users (id, nama, username, email, password, role_id, is_active) VALUES
(1,  'Administrator',         'admin',       'admin@sikes.local',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
(2,  'Ust. Fatimah Az-Zahra', 'medis',       'medis@sikes.local',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1),
(3,  'Ust. Ahmad Fauzi',      'pengasuhan',  'pengasuhan@sikes.local',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1),
(4,  'Bpk. Budi Santoso',     'keamanan',    'keamanan@sikes.local',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1),
(5,  'Ustzh. Aisyah Rahman',  'santriadmin', 'santriadmin@sikes.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 1),
(6,  'Ibu Sari Wulandari',    'dapur',       'dapur@sikes.local',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 6, 1),
(7,  'Bpk. Dedi Kurniawan',   'bendahara',   'bendahara@sikes.local',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 7, 1),
(8,  'KH. Abdul Karim',       'kyai',        'kyai@sikes.local',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 8, 1),
(9,  'Ahmad Zaki',            'santri',      'santri@sikes.local',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9, 1),
(10, 'Bpk. Hasan Basri',      'wali',        'wali@sikes.local',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10, 1);

-- ============================================================================
-- SEED 5: DEMO MASTER DATA
-- ============================================================================
INSERT INTO asrama (id, nama, jenis, lokasi, keterangan) VALUES
(1, 'Asrama Al-Faruq', 'putra', 'Sisi timur masjid', 'Asrama putra utama'),
(2, 'Asrama Aisyah',   'putri', 'Sisi barat masjid', 'Asrama putri utama');

INSERT INTO blok (id, asrama_id, nama, keterangan) VALUES
(1, 1, 'Blok A', 'Lantai 1'),
(2, 1, 'Blok B', 'Lantai 2'),
(3, 2, 'Blok A', 'Lantai 1');

INSERT INTO kamar (id, blok_id, nama, kapasitas) VALUES
(1, 1, '101', 6),
(2, 1, '102', 6),
(3, 3, '201', 6);

INSERT INTO wali_santri (id, nama, no_telepon, pekerjaan, alamat) VALUES
(1, 'Hasan Basri', '081234567890', 'Petani',     'Jl. Merdeka No. 5, Bandung'),
(2, 'Siti Aminah', '089876543210', 'Wiraswasta', 'Jl. Sudirman No. 12, Bogor');

INSERT INTO santri (id, nis, nisn, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, no_telepon, wali_id, kamar_id, status) VALUES
(1, '2401001', '0051234567', 'Ahmad Zaki',       'L', 'Bandung', '2009-03-14', 'Jl. Merdeka No. 5, Bandung',  '081200000001', 1, 1,    'aktif'),
(2, '2401002', '0051234568', 'Muhammad Rizki',   'L', 'Bogor',   '2008-11-02', 'Jl. Sudirman No. 12, Bogor',  '081200000002', 2, 1,    'aktif'),
(3, '2402003', '0051234569', 'Aisyah Putri',     'P', 'Bandung', '2010-01-20', 'Jl. Melati No. 8, Bandung',   '081200000003', 2, 3,    'aktif'),
(4, '2402004', NULL,         'Fatimah Az-Zahra', 'P', 'Cianjur', '2009-07-30', 'Jl. Anggrek No. 3, Cianjur',  NULL,           2, 3,    'aktif'),
(5, '2301005', NULL,         'Abdullah Fahim',   'L', 'Bandung', '2007-05-17', 'Jl. Kenanga No. 9, Bandung',  '081200000005', 1, NULL, 'nonaktif');

-- ============================================================================
-- SEED 6: DEMO SKRINING
--   Santri 1: alergi obat + asma (WARNING) | 2: IMT berlebih | 3: alergi makanan
-- ============================================================================
INSERT INTO skrining_kesehatan
(id, santri_id, tanggal_skrining,
 riwayat_penyakit_bawaan, riwayat_bawaan_ket,
 alergi_obat, alergi_obat_ket, alergi_makanan, alergi_makanan_ket,
 asma, asma_ket, epilepsi, epilepsi_ket, penyakit_lain, penyakit_lain_ket,
 tinggi_cm, berat_kg, imt, kategori_imt, golongan_darah, rhesus,
 buta_warna, buta_warna_ket, gigi_status, gigi_ket, fisik_status, fisik_ket,
 catatan_medis, petugas_id) VALUES
(1, 1, '2025-07-15', 'tidak', NULL,
 'ya', 'Alergi penisilin (ruam kulit)', 'tidak', NULL,
 'ya', 'Asma ringan, terpicu debu & olahraga berat', 'tidak', NULL, 'tidak', NULL,
 165.00, 55.00, 20.20, 'Normal', 'A', 'positif',
 'normal', NULL, 'perlu_perawatan', 'Karies gigi geraham kanan bawah', 'sehat', NULL,
 'Wajib membawa inhaler. Hindari tugas kebersihan berdebu. Pantau saat ekstrakurikuler fisik.', 2),
(2, 2, '2025-07-15', 'tidak', NULL, 'tidak', NULL, 'tidak', NULL,
 'tidak', NULL, 'tidak', NULL, 'tidak', NULL,
 170.00, 74.00, 25.61, 'Berat Badan Berlebih', 'O', 'positif',
 'normal', NULL, 'baik', NULL, 'sehat', NULL,
 'Disarankan rutin olahraga dan pengaturan porsi makan.', 2),
(3, 3, '2025-07-16', 'tidak', NULL, 'tidak', NULL,
 'ya', 'Alergi udang & seafood (gatal-gatal)',
 'tidak', NULL, 'tidak', NULL, 'tidak', NULL,
 158.00, 52.00, 20.83, 'Normal', 'B', 'negatif',
 'normal', NULL, 'baik', NULL, 'ada_keluhan', 'Keluhan sering pusing saat pagi',
 'Wajib info dapur: jangan menyajikan menu seafood. Perlu cek tekanan darah berkala.', 2);

INSERT INTO skrining_riwayat (skrining_id, aksi, data_sesudah, user_id) VALUES
(1, 'buat', '{"ringkasan":"Skrining awal dibuat"}', 2),
(2, 'buat', '{"ringkasan":"Skrining awal dibuat"}', 2),
(3, 'buat', '{"ringkasan":"Skrining awal dibuat"}', 2);

-- ============================================================================
-- SEED 7: DEMO PEMERIKSAAN
-- ============================================================================
INSERT INTO pemeriksaan
(id, santri_id, tanggal_kunjungan, jenis_kunjungan, keluhan, pemeriksaan_awal,
 suhu_c, td_sistol, td_diastol, nadi, pernapasan,
 hasil_pemeriksaan, diagnosis, tindakan, resep_obat, catatan_lanjutan, tanggal_kontrol,
 status, pemeriksa_id, petugas_id) VALUES
(1, 1, NOW(), 'rawat_jalan',
 'Batuk berdeham sejak 2 hari, terasa sesak saat aktivitas',
 'Santri sadar, tampak sesak ringan. Riwayat asma (lihat skrining).',
 NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
 'menunggu', NULL, 2),
(2, 2, DATE_SUB(NOW(), INTERVAL 2 DAY), 'rawat_jalan',
 'Demam dan pilek sejak 3 hari, badan lemas',
 'Suhu meningkat, konjungtivitas tidak ada.',
 38.2, 120, 80, 96, 22,
 'Faring hiperemis, tonsil T1-T1, takipnea ringan, ronki (-)',
 'ISPA (Common Cold)',
 'Istirahat cukup, kompres hangat, perbanyak minum',
 'Paracetamol 500 mg 3x1 sesuai kebutuhan; Vitamin C 1x1',
 'Kembali bila demam > 3 hari atau sesak',
 NULL, 'selesai', 2, 2),
(3, 3, DATE_SUB(NOW(), INTERVAL 12 DAY), 'rawat_jalan',
 'Nyeri ulu hati setelah makan pedas, mual',
 'Epigastrium nyeri tekan ringan, defensa (-).',
 36.7, 110, 70, 88, 20,
 'Abdomen supple, epigastrium nyeri tekan, bising (+)',
 'Dugaan Gastritis',
 'Diet teratur, hindari makanan pedas/asam/kopi',
 'Antasida DOEN 3x1 tabel diminum sesudah makan',
 'Kontrol bila keluhan berlanjut lebih dari 3 hari',
 NULL, 'selesai', 2, 2),
(4, 4, DATE_SUB(NOW(), INTERVAL 1 DAY), 'darurat',
 'Pusing hebat, mual',
 'Registrasi awal; santri sempat berbaring di poskestren.',
 NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
 'batal', NULL, 2),
(5, 2, NOW(), 'preventif',
 'Pemeriksaan preventif — Contact Tracing kasus #1 (Varicella). Santri merupakan kontak potensial, TANPA keluhan saat ini.',
 'Asimtomatik. Dipanggil untuk skrining preventif.',
 NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
 'menunggu', NULL, 2);

INSERT INTO pemeriksaan_riwayat (pemeriksaan_id, aksi, data_sesudah, user_id) VALUES
(1, 'registrasi', '{"ringkasan":"Kunjungan teregistrasi, menunggu pemeriksaan"}', 2),
(2, 'registrasi', '{"ringkasan":"Kunjungan teregistrasi, menunggu pemeriksaan"}', 2),
(2, 'periksa',    '{"ringkasan":"Pemeriksaan diselesaikan. Diagnosis: ISPA (Common Cold)"}', 2),
(3, 'registrasi', '{"ringkasan":"Kunjungan teregistrasi, menunggu pemeriksaan"}', 2),
(3, 'periksa',    '{"ringkasan":"Pemeriksaan diselesaikan. Diagnosis: Dugaan Gastritis"}', 2),
(4, 'registrasi', '{"ringkasan":"Kunjungan teregistrasi, menunggu pemeriksaan"}', 2),
(4, 'batal',      '{"ringkasan":"Kunjungan dibatalkan"}', 2),
(5, 'registrasi', '{"ringkasan":"Pemeriksaan preventif tracing dibuat dari modul Contact Tracing"}', 2);

-- ============================================================================
-- SEED 8: DEMO INSPEKSI SANITASI
-- ============================================================================
INSERT INTO inspeksi_kamar
(id, kamar_id, tanggal_inspeksi,
 ventilasi, lantai, kasur, pakaian, jemur_pakaian, kamar_mandi, kepadatan, lingkungan,
 skor, persentase, status, catatan_petugas, petugas_id) VALUES
(1, 1, '2025-07-18', 'baik','baik','baik','cukup','baik','baik','baik','baik',
 15, 93.75, 'baik', 'Kamar rapi dan wangi. Sebagian pakaian belum dilipat — sudah dibina saat apel malam.', 3),
(2, 2, '2025-07-18', 'cukup','cukup','cukup','cukup','cukup','cukup','cukup','cukup',
 8, 50.00, 'perlu_perhatian', 'Semua aspek perlu perbaikan bertahap. Jadwal piket harian dipertegas dan dimonitor musyrif.', 3),
(3, 3, '2025-07-19', 'buruk','buruk','cukup','buruk','buruk','buruk','cukup','cukup',
 3, 18.75, 'risiko_tinggi', 'Kamar mandi sangat kotor, sampah menumpuk, tidak ada aktivitas jemur. Tindak lanjut segera bersama pengasuhan & bagian kebersihan. Inspeksi ulang pekan depan.', 3);

INSERT INTO inspeksi_riwayat (inspeksi_id, aksi, data_sesudah, user_id) VALUES
(1, 'buat', '{"ringkasan":"Inspeksi kamar 101 dibuat. Skor 15/16 (93.75%) - Baik"}', 3),
(2, 'buat', '{"ringkasan":"Inspeksi kamar 102 dibuat. Skor 8/16 (50%) - Perlu Perhatian"}', 3),
(3, 'buat', '{"ringkasan":"Inspeksi kamar 201 dibuat. Skor 3/16 (18.75%) - Risiko Tinggi"}', 3);

-- ============================================================================
-- SEED 9: DEMO CONTACT TRACING
-- ============================================================================
INSERT INTO kasus_menular
(id, santri_id, penyakit, tanggal_mulai, tanggal_selesai, status_kasus, catatan, dilaporkan_id) VALUES
(1, 1, 'Varicella (Cacar Air)', '2025-07-20', NULL, 'aktif',
 'Lesi vesikular khas. Santri diisolasi di ruang pemantauan poskestren. Kontak sekamar dipantau.', 2),
(2, 3, 'Scabies', '2025-07-05', '2025-07-19', 'selesai',
 'Terapi topikal selesai, lesi membaik. Kasus ditutup.', 2);

INSERT INTO tracing_kontak
(id, kasus_id, santri_id, status, pemeriksaan_id, hasil_pemeriksaan, catatan, ditangani_id, examined_at) VALUES
(1, 1, 2, 'dalam_pemeriksaan', 5, NULL, NULL, 2, NOW()),
(2, 2, 4, 'sudah_diperiksa', NULL,
 'Tidak ditemukan keluhan pada pemeriksaan preventif.', NULL, 2, '2025-07-12 09:00:00');

INSERT INTO tracing_riwayat (kasus_id, kontak_id, aksi, keterangan, user_id) VALUES
(1, NULL, 'kasus_dibuat',        'Kasus Varicella (Cacar Air) dicatat untuk Ahmad Zaki (kamar 101)', 2),
(1, NULL, 'kontak_dibuat',       '1 kontak potensial digenerate dari penghuni kamar 101', 2),
(1, 1,    'pemeriksaan_dimulai', 'Pemeriksaan preventif dibuat untuk Muhammad Rizki', 2),
(2, NULL, 'kasus_dibuat',        'Kasus Scabies dicatat untuk Aisyah Putri (kamar 201)', 2),
(2, NULL, 'kontak_dibuat',       '1 kontak potensial digenerate dari penghuni kamar 201', 2),
(2, 2,    'hasil_dicatat',       'Fatimah Az-Zahra: tidak ditemukan keluhan — sudah diperiksa', 2),
(2, NULL, 'kasus_ditutup',       'Kasus ditutup setelah terapi selesai', 2);

-- ============================================================================
-- SEED 10: DEMO PIKET
-- ============================================================================
INSERT INTO piket_jadwal
(id, jenis, nama_kegiatan, lokasi, tanggal, waktu_mulai, waktu_selesai,
 penanggung_jawab, status, catatan, dibuat_id) VALUES
(1, 'pembersihan_kamar', 'Piket Pembersihan Kamar Blok A', 'Asrama Al-Faruq — Blok A',
 CURDATE(), '06:30:00', '08:00:00', 'Ust. Ahmad Fauzi', 'terjadwal',
 'Fokus lantai dan kamar mandi setiap kamar.', 3),
(2, 'jemur_kasur', 'Jemur Kasur Massal', 'Halaman asrama putra & putri',
 DATE_ADD(CURDATE(), INTERVAL 2 DAY), '07:00:00', '09:00:00', 'Ust. Ahmad Fauzi', 'terjadwal',
 'Angkut kasur pagi; pantau cuaca; jemur balik sebelum Ashar.', 3),
(3, 'kerja_bakti', 'Kerja Bakti Lingkungan Pondok', 'Seluruh area pondok',
 DATE_SUB(CURDATE(), INTERVAL 1 DAY), '06:00:00', '08:30:00', 'Bpk. Budi Santoso', 'berlangsung', NULL, 3),
(4, 'sanitasi_kamar_mandi', 'Sanitasi Kamar Mandi Asrama Putri', 'Asrama Aisyah — Blok A',
 DATE_SUB(CURDATE(), INTERVAL 3 DAY), '15:00:00', '16:30:00', 'Ustzh. Aisyah Rahman', 'terjadwal',
 'Perlu diputuskan: dijadwalkan ulang atau ditandai selesai.', 3),
(5, 'pemeriksaan_lingkungan', 'Pemeriksaan Lingkungan & Drainase', 'Area belakang dapur & taman',
 DATE_SUB(CURDATE(), INTERVAL 7 DAY), '08:00:00', NULL, 'KH. Abdul Karim', 'selesai',
 'Temuan: drainase belakang dapur perlu dibersihkan pekan ini.', 3);

INSERT INTO piket_riwayat (jadwal_id, aksi, keterangan, user_id) VALUES
(1, 'buat', 'Jadwal dibuat', 3),
(2, 'buat', 'Jadwal dibuat', 3),
(3, 'buat', 'Jadwal dibuat', 3),
(3, 'mulai', 'Kegiatan dimulai', 3),
(4, 'buat', 'Jadwal dibuat', 3),
(5, 'buat', 'Jadwal dibuat', 3),
(5, 'mulai', 'Kegiatan dimulai', 3),
(5, 'selesai', 'Kegiatan diselesaikan', 3);

-- ============================================================================
-- SEED 11: DEMO E-SURAT IZIN SAKIT
-- ============================================================================
INSERT INTO surat_izin
(id, nomor_surat, token, santri_id, pemeriksaan_id,
 tanggal_mulai, tanggal_selesai, keterangan_medis,
 status, alasan_pembatalan, diterbitkan_id) VALUES
(1, CONCAT('0001/IZS/PP/', DATE_FORMAT(CURDATE(), '%m/%Y')),
    '7f9c2ad1c0e4b8a35d61f0e2b47c98a1d5e3f7b60824a9c1d6e8f3a5b7c9d0e1',
    2, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY),
    'Demam / ISPA — dianjurkan istirahat di rumah selama 3 hari.',
    'aktif', NULL, 2),
(2, CONCAT('0002/IZS/PP/', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 10 DAY), '%m/%Y')),
    '1a2b3c4d5e6f708192a3b4c5d6e7f8091a2b3c4d5e6f708192a3b4c5d6e7f809',
    3, 3, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY),
    'Dugaan gastritis — dianjurkan istirahat dan diet teratur selama 4 hari.',
    'kadaluarsa', NULL, 2),
(3, CONCAT('0003/IZS/PP/', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%m/%Y')),
    'ffeeddccbbaa99887766554433221100ffeeddccbbaa99887766554433221100',
    4, NULL, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY),
    'Pusing hebat — dianjurkan istirahat 3 hari.',
    'dibatalkan', 'Salah data periode — surat diterbitkan ulang.', 2);

INSERT INTO surat_izin_riwayat (surat_id, aksi, keterangan, user_id) VALUES
(1, 'terbit', 'Surat diterbitkan untuk Muhammad Rizki', 2),
(2, 'terbit', 'Surat diterbitkan untuk Aisyah Putri', 2),
(2, 'diverifikasi', 'Surat diverifikasi via QR (publik)', NULL),
(3, 'terbit', 'Surat diterbitkan untuk Fatimah Az-Zahra', 2),
(3, 'batal',  'Dibatalkan: Salah data periode — surat diterbitkan ulang.', 2);

-- ============================================================================
-- SEED 12: DEMO KEPULANGAN
-- ============================================================================
INSERT INTO kepulangan
(id, santri_id, pemeriksaan_id, alasan_medis, alamat_tujuan, tanggal_pengajuan, status,
 medis_id, medis_catatan,
 keamanan_id, keamanan_status, keamanan_catatan, keamanan_at,
 pengasuhan_id, pengasuhan_status, pengasuhan_catatan, pengasuhan_at,
 gerbang_id, gerbang_at) VALUES
(1, 1, NULL,
 'Varicella (cacar air) — memerlukan perawatan lanjutan di rumah dan isolasi keluarga sampai lesi mengering total.',
 'Jl. Merdeka No. 5, Bandung (rumah wali)', CURDATE(), 'selesai',
 2, 'Santri terisolasi di poskestren 3 hari, kondisi stabil. Direkomendasikan perawatan keluarga.',
 4, 'disetujui', 'Koordinasi pos jaga & identitas wali terverifikasi.', CONCAT(CURDATE(), ' 10:00:00'),
 3, 'disetujui', 'Wali sudah dihubungi; musyrif mendampingi persiapan.', CONCAT(CURDATE(), ' 11:00:00'),
 4, CONCAT(CURDATE(), ' 13:30:00')),
(2, 2, 2,
 'ISPA dengan demam 38.2°C — istirahat di rumah 3 hari sesuai surat izin sakit terkait.',
 'Jl. Sudirman No. 12, Bogor (rumah wali)', CURDATE(), 'menunggu',
 2, 'Perlu pemantauan wali di rumah; kondisi tidak memburuk.',
 NULL, 'menunggu', NULL, NULL,
 NULL, 'menunggu', NULL, NULL,
 NULL, NULL),
(3, 3, 3,
 'Dugaan gastritis berulang — kontrol ke dokter di kota dan istirahat di rumah.',
 'Jl. Sudirman No. 12, Bogor (rumah wali)', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'menunggu',
 2, 'Keluhan berulang; perlu pemeriksaan lanjutan di fasilitas kesehatan.',
 4, 'disetujui', 'Kendaraan antar-jemput wali tercatat di pos.', CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 09:15:00'),
 NULL, 'menunggu', NULL, NULL,
 NULL, NULL),
(4, 4, NULL,
 'Pusing berulang — dianjurkan istirahat di rumah dan pemeriksaan lanjutan.',
 'Jl. Anggrek No. 3, Cianjur (rumah orang tua)', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'disetujui',
 2, 'Keluhan sampai mual; pemeriksaan klinik batal karena santri dibawa pulang.',
 4, 'disetujui', 'Wali menjemuk langsung.', CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 14:00:00'),
 8, 'disetujui', 'Disetujui Kyai. Periode izin surat terkait sedang diperiksa ulang.', CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 16:00:00'),
 NULL, NULL),
(5, 5, NULL,
 'Rekomendasi pulang sementara untuk pemulihan.',
 'Jl. Kenanga No. 9, Bandung', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'ditolak',
 2, 'Rekomendasi awal.',
 4, 'ditolak', 'Ditolak: santri berstatus nonaktif dan wali belum mengonfirmasi penjemputan.',
 CONCAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), ' 08:45:00'),
 NULL, 'menunggu', NULL, NULL,
 NULL, NULL);

INSERT INTO kepulangan_riwayat (kepulangan_id, aksi, keterangan, user_id) VALUES
(1, 'rekomendasi', 'Rekomendasi medis diterbitkan', 2),
(1, 'keamanan',    'Disetujui oleh keamanan: Koordinasi pos jaga & identitas wali terverifikasi.', 4),
(1, 'pengasuhan',  'Disetujui oleh pengasuhan: Wali sudah dihubungi; musyrif mendampingi persiapan.', 3),
(1, 'gerbang',     'Santri keluar gerbang, dipulangkan bersama wali.', 4),
(2, 'rekomendasi', 'Rekomendasi medis diterbitkan', 2),
(3, 'rekomendasi', 'Rekomendasi medis diterbitkan', 2),
(3, 'keamanan',    'Disetujui oleh keamanan: Kendaraan antar-jemput wali tercatat di pos.', 4),
(4, 'rekomendasi', 'Rekomendasi medis diterbitkan', 2),
(4, 'keamanan',    'Disetujui oleh keamanan: Wali menjemuk langsung.', 4),
(4, 'pengasuhan',  'Disetujui oleh kyai: Periode izin surat terkait sedang diperiksa ulang.', 8),
(5, 'rekomendasi', 'Rekomendasi medis diterbitkan', 2),
(5, 'keamanan',    'Ditolak oleh keamanan: santri nonaktif & wali belum konfirmasi.', 4);

-- ============================================================================
-- SEED 13: DEMO BUKU TAMU
-- ============================================================================
INSERT INTO buku_tamu
(id, nama_penjenguk, hubungan, santri_id, no_telepon, tanggal,
 waktu_datang, waktu_pulang, tujuan, catatan, petugas_id) VALUES
(1, 'Hasan Basri', 'Wali Santri', 1, '081234567890', CURDATE(),
 '09:00:00', '10:30:00', 'Menjenguk ananda yang sakit (varicella) sebelum dipulangkan',
 'Membawa perlengkapan pakaian dan obat dari rumah.', 4),
(2, 'Siti Aminah', 'Ibu', 3, '089876543210', CURDATE(),
 '13:00:00', NULL, 'Menjenguk ananda yang sakit', NULL, 4),
(3, 'Siti Aminah', 'Ibu', 3, '089876543210', DATE_SUB(CURDATE(), INTERVAL 1 DAY),
 '14:00:00', '15:30:00', 'Menjenguk dan membawa makanan', NULL, 4);

-- ============================================================================
-- SEED 14: DEMO GIZI DIET
-- ============================================================================
INSERT INTO gizi_diet
(id, santri_id, tanggal, jenis_diet, jenis_lain, catatan_medis,
 status_distribusi, distribusi_at, distribusi_petugas_id, rekomendasi_id) VALUES
(1, 1, CURDATE(), 'bubur_halus', NULL,
 'Masa pemulihan varicella — makanan lembut dan hangat.',
 'siap', CONCAT(CURDATE(), ' 06:00:00'), 6, 2),
(2, 2, CURDATE(), 'tidak_pedas', NULL,
 'ISPA — hindari makanan pedas dan minuman dingin.',
 'belum', NULL, NULL, 2),
(3, 3, CURDATE(), 'diet_lunak', NULL,
 'Gastritis — porsi kecil, frekuensi sering.',
 'belum', NULL, NULL, 2),
(4, 3, CURDATE(), 'lainnya', 'Rendah Asam',
 'Pendamping diet lunak: hindari makanan asam/pedas.',
 'belum', NULL, NULL, 2),
(5, 4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'bubur_halus', NULL,
 'Pusing — diet halus sementara.',
 'selesai', CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 11:30:00'), 6, 2);

-- ============================================================================
-- SEED 15: DEMO BPJS
-- ============================================================================
INSERT INTO bpjs_santri
(id, santri_id, nomor_bpjs, status, faskes_tk1, catatan, tanggal_pembaruan, petugas_id) VALUES
(1, 1, '0001234567890', 'aktif',    'Puskesmas Cimahi Utara',   NULL, CURDATE(), 2),
(2, 2, '0002345678901', 'aktif',    'Puskesmas Bogor Tengah',   'Kelas 2 — kartu terverifikasi', CURDATE(), 2),
(3, 3, '0003456789012', 'nonaktif', 'Puskesmas Bandung Barat',  'Iuran tertunggak — perlu konfirmasi wali.', CURDATE(), 2);

-- ============================================================================
-- SEED 16: DEMO RUJUKAN
-- ============================================================================
INSERT INTO rujukan
(id, nomor_surat, santri_id, pemeriksaan_id, tujuan_faskes,
 keluhan, hasil_pemeriksaan, diagnosis_awal, tindakan_sementara, rekomendasi,
 tanggal_rujukan, penerbit_id) VALUES
(1, CONCAT('0001/RUJ/', DATE_FORMAT(CURDATE(), '%m/%Y')), 1, NULL,
 'RSUD Kota Bandung',
 'Batuk berdeham 2 hari, sesak saat aktivitas, riwayat asma.',
 'Sesak ringan, wheezing (-), SpO2 97%.',
 'Exaserbasi asma ringan (keterangan awal tenaga medis).',
 'Nebulisasi ringan di poskestren, observasi 2 jam.',
 'Evaluasi lanjutan di poli paru dan terapi maintenance.',
 CURDATE(), 2),
(2, CONCAT('0002/RUJ/', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '%m/%Y')), 2, 2,
 'RSUD Kota Bogor',
 'Demam 3 hari, pilek, badan lemas.',
 'Faring hiperemis, tonsil T1-T1.',
 'ISPA (keterangan awal tenaga medis).',
 'Paracetamol, istirahat, cairan cukup.',
 'Pemeriksaan laboratorium bila demam berlanjut lebih dari 3 hari.',
 DATE_SUB(CURDATE(), INTERVAL 5 DAY), 2);

INSERT INTO rujukan_riwayat (rujukan_id, aksi, keterangan, user_id) VALUES
(1, 'terbit',  'Surat rujukan diterbitkan untuk Ahmad Zaki', 2),
(1, 'dicetak', 'Surat dicetak', 2),
(2, 'terbit',  'Surat rujukan diterbitkan untuk Muhammad Rizki', 2);

-- ============================================================================
-- SEED 17: DEMO OBAT
--   Stok = hasil masuk - keluar (konsisten dengan transaksi di bawah)
-- ============================================================================
INSERT INTO obat
(id, nama, kategori, satuan, stok, minimum_stok, harga, tanggal_kedaluwarsa, catatan) VALUES
(1, 'Paracetamol 500mg', 'tablet',  'tablet', 8,  10, 350.00,  DATE_ADD(CURDATE(), INTERVAL 365 DAY), NULL),
(2, 'Amoxicillin 500mg', 'kapsul',  'kapsul', 120, 30, 900.00,  DATE_ADD(CURDATE(), INTERVAL 200 DAY), NULL),
(3, 'Antasida DOEN',     'tablet',  'tablet', 200, 50, 250.00,  DATE_ADD(CURDATE(), INTERVAL 300 DAY), NULL),
(4, 'OBH Sirup 100ml',   'sirup',   'botol',  15,  10, 12000.00, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'Cek stok berkala, sering diminta'),
(5, 'Salep Gentamicin',  'salep',   'tube',   22,  10, 18500.00, DATE_ADD(CURDATE(), INTERVAL 150 DAY), NULL),
(6, 'Vitamin C 500mg',   'tablet',  'tablet', 90,  40, 500.00,  DATE_SUB(CURDATE(), INTERVAL 5 DAY),  'KEDALUWARSA — jangan digunakan, ajukan pemusnahan'),
(7, 'Tetes Mata Insto',  'tetes',   'botol',  30,  10, 15000.00, DATE_ADD(CURDATE(), INTERVAL 90 DAY), NULL),
(8, 'ORS Sachet',        'lainnya', 'sachet', 5,   20, 1500.00,  DATE_ADD(CURDATE(), INTERVAL 400 DAY), 'Stok kritis — segera pengadaan');

INSERT INTO obat_transaksi
(id, obat_id, jenis, jumlah, harga_satuan, pemeriksaan_id, keterangan, user_id, created_at) VALUES
(1,  1, 'masuk',  50,  350.00,  NULL, 'Pengadaan awal bulan', 2, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(2,  1, 'keluar', 42,  350.00,  2,    'Pemakaian ISPA (demo)', 2, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3,  2, 'masuk',  150, 900.00,  NULL, 'Pengadaan awal bulan', 2, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(4,  2, 'keluar', 30,  900.00,  NULL, 'Pemakaian rutin poskestren', 2, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(5,  3, 'masuk',  250, 250.00,  NULL, 'Pengadaan awal bulan', 2, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(6,  3, 'keluar', 50,  250.00,  3,    'Pemakaian gastritis (demo)', 2, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(7,  6, 'masuk',  100, 500.00,  NULL, 'Pengadaan lama', 2, DATE_SUB(NOW(), INTERVAL 200 DAY)),
(8,  6, 'keluar', 10,  500.00,  NULL, 'Pemakaian sebelum kedaluwarsa', 2, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(9,  8, 'masuk',  25,  1500.00, NULL, 'Pengadaan', 2, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(10, 8, 'keluar', 20,  1500.00, NULL, 'Pemakaian sanitasi air', 2, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- ============================================================================
-- SEGARKAN CACHE TABEL — agar information_schema langsung sinkron
-- (perbaikan anti error #1109 setelah import)
-- ============================================================================
FLUSH TABLES;

-- ============================================================================
-- SELESAI
-- ============================================================================
