# Software Design Document (SDD)
## SIKES Pondok Pesantren
**Sistem Informasi Kesehatan Pondok Pesantren Berbasis Web dengan Dukungan Kecerdasan Buatan (AI)**

---

### Informasi Dokumen
* **Penyusun**      : Yoga Satria Alfaritzi
* **NIM**           : 41257231
* **Kelompok**      : 6 (Enam)
* **Versi Dokumen** : 1.0 (Final)
* **Status**        : Approved

---

## 1. Arsitektur Perangkat Lunak
SIKES dirancang menggunakan pola arsitektur **Model-View-Controller (MVC)** murni (native PHP tanpa framework bloated) untuk memaksimalkan performa, transparansi kode, dan kemudahan deployment di server lokal XAMPP.

```
                  +-----------------------+
                  |     Web Browser       |
                  +-----------+-----------+
                              | HTTP(S) Request
                              v
                  +-----------------------+
                  |  public/index.php     | (Front Controller)
                  +-----------+-----------+
                              |
                              v
                  +-----------------------+
                  |   app/core/Router     | (Route Matching & Middleware Auth)
                  +-----------+-----------+
                              |
         +--------------------+--------------------+
         |                                         |
         v                                         v
+-----------------+                      +-------------------+
|   Controllers   |<-------------------->|      Models       |
| (Business Logic)|                      | (PDO Prepared DB) |
+--------+--------+                      +---------+---------+
         |                                         |
         | Operasi View & Data                     | SQL Query
         v                                         v
+-----------------+                      +-------------------+
|      Views      |                      |  MySQL / MariaDB  |
| (Bootstrap 5.3) |                      |    (32 Tabel)     |
+-----------------+                      +-------------------+
         |
         +---------> Groq AI Cloud API (Llama 3.3 LLM Endpoint)
```

---

## 2. Diagram Perancangan Sistem (UML)

### 2.1 Use Case Diagram Overview
Sistem membagi akses fungsional ke dalam 10 aktor peran. File diagram visual resolusi tinggi tersedia pada direktori:
* File Vector SVG: [`docs/presentasi-individu/figma/01-usecase-overview.svg`](presentasi-individu/figma/01-usecase-overview.svg)
* File Render PNG: [`docs/presentasi-individu/figma/preview/01-usecase-overview.png`](presentasi-individu/figma/preview/01-usecase-overview.png)

Aktivitas Utama Peran:
* **Petugas Medis**: Input Pemeriksaan, Kelola Resep Obat, Rekomendasi Surat Sakit, Konsultasi Triase AI.
* **Pengasuhan**: Validasi Surat Izin Pulang/Istirahat, Pantau Santri Isolasi.
* **Satpam / Keamanan**: Scan & Verifikasi QR Code Surat Keluar.
* **Petugas Dapur**: Kelola Menu Diet Santri Sakit.
* **Bendahara**: Input Transaksi Biaya Pengobatan.
* **Kyai**: Tinjau Laporan & Statistik Kesehatan Pesantren.

### 2.2 Activity Diagram: Alur Perizinan Kepulangan & Verifikasi QR Code
* File Diagram: [`docs/presentasi-individu/figma/02-activity-kepulangan.svg`](presentasi-individu/figma/02-activity-kepulangan.svg)
1. Petugas Medis membuat rekomendasi istirahat/pulang santri di modul rekam medis.
2. Pengasuhan memvalidasi dan menandatangani e-surat izin secara digital.
3. Sistem secara otomatis men-generate token unik dan **QR-Code validasi publik**.
4. Saat santri dijemput wali dan melewati gerbang, Satpam memindai QR-Code via kamera HP/scanner.
5. Server memvalidasi hash tanda tangan dan menampilkan status keabsahan surat secara real-time.

### 2.3 Activity Diagram: Contact Tracing Wabah Penyakit Menular
* File Diagram: [`docs/presentasi-individu/figma/04-activity-tracing.svg`](presentasi-individu/figma/04-activity-tracing.svg)
1. Petugas Medis mendiagnosa santri terindikasi penyakit menular (misal Scabies).
2. Sistem menandai status santri sebagai "Kasus Indeks (Index Case)".
3. Sistem mengeksekusi algoritma relasional berbasis penempatan:
   - Level 1: Santri satu kamar tidur.
   - Level 2: Santri satu blok asrama.
4. Daftar kontak erat diterbitkan ke Petugas Medis & Pengasuhan untuk dilakukan skrining massal dan isolasi mandiri.

---

## 3. Desain Basis Data (Database Design)
Basis data `sikes_pondok` memiliki total **32 tabel relasional** dengan struktur normalisasi hingga 3NF:

1. **Kelompok Autentikasi & RBAC**:
   - `users`: ID, username, password (bcrypt), email, role_id, is_active, last_login.
   - `roles`: ID, nama_role, slug, is_system.
   - `permissions`: ID, permission_name, slug, grup.
   - `role_permissions`: role_id, permission_id.
   - `activity_logs`: ID, user_id, action, modul, deskripsi, ip_address, created_at.
2. **Kelompok Master Santri & Asrama**:
   - `santri`: ID, nis, nama, jenis_kelamin, kamar_id, status, wali_id.
   - `asrama`, `blok`, `kamar`: Struktur penempatan asrama santri.
   - `wali_santri`: Data kontak orang tua/wali santri.
3. **Kelompok Layanan Medis**:
   - `pemeriksaan`: ID, santri_id, user_id (dokter), tanggal, keluhan, tensi, suhu, diagnosa, tindakan, status.
   - `skrining_kesehatan`: Skrining berkala santri.
   - `rujukan_eksternal`: Data rujukan ke RS/Puskesmas terdekat.
   - `rawat_inap`: Santri yang diisolasi/dirawat di poskestren.
4. **Kelompok Logistik & Obat**:
   - `obat`: ID, kode_obat, nama_obat, jenis, stok, satuan, tanggal_kadaluarsa.
   - `obat_masuk` & `pemakaian_obat`: Transaksi mutasi stok obat.
5. **Kelompok Perizinan & Tracing**:
   - `surat_izin`: ID, nomor_surat, santri_id, jenis_izin, tanggal_mulai, tanggal_selesai, token_qr, status.
   - `tracing_kasus`: ID, penyakit, kasus_indeks_id, status_klaster, rekomendasi_ai.
   - `tracing_kontak`: ID, tracing_id, santri_kontak_id, status_gejala, tindakan.
6. **Kelompok Pendukung (Gizi, Keuangan, Sanitasi)**:
   - `gizi_diet`: Menu makanan khusus pasien santri.
   - `keuangan_transaksi`: Rekapitulasi pembayaran poskestren.
   - `inspeksi_sanitasi`: Evaluasi kebersihan kamar asrama.

---

## 4. Keamanan Sistem (Security Architecture)
* **Anti-Brute Force Protection**: Pengecekan limit gagal login per username (`Session::isLocked($username)`), akun dikunci selama 300 detik setelah 5 kali gagal.
* **Token CSRF Dinamis**: Digenerate menggunakan `bin2hex(random_bytes(32))` dan diverifikasi di controller via `$this->checkCsrf()`.
* **Proteksi XSS**: Seluruh output variabel ke view di-escape menggunakan fungsi pembantu `e($string)` yang membungkus `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
