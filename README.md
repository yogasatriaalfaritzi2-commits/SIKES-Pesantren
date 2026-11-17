# SIKES Pondok Pesantren
> **Sistem Informasi Kesehatan Pondok Pesantren Berbasis Web dengan Dukungan Kecerdasan Buatan (AI)**

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![AI Powered](https://img.shields.io/badge/AI-Groq%20Llama%203.3-f55036?style=flat)](https://groq.com/)

---

## 📌 Identitas Pengembang
* **Nama Lengkap** : Yoga Satria Alfaritzi
* **NIM**          : 41257231
* **Kelompok**     : 6 (Enam)
* **Tema Proyek**  : Sistem Informasi Kesehatan Pondok Pesantren Berbasis Web Terintegrasi Kecerdasan Buatan (AI)
* **Institusi**    : Proyek Akhir Rekayasa Perangkat Lunak / Web Development

---

## 🎬 Tautan Video Presentasi & Demo
Video presentasi wajib (menampilkan wajah di webcam, demo live sistem):
* 🎥 **Tautan Video Presentasi & Demo Sistem (YouTube)**:  
  👉 [https://youtu.be/m9qQC9NvvXk](https://youtu.be/m9qQC9NvvXk)

> **Catatan Struktur Presentasi di dalam Video:**
> 1. **Tahap 1: Product Requirement Document (PRD)** *(Latar Belakang, Problem Statement, & Solusi)*
> 2. **Tahap 2: Software Design Document (UML & UI/UX)** *(Arsitektur MVC, Use Case, Activity Diagram, & DB Schema)*
> 3. **Tahap 3: Final Live Demo & Technical Walkthrough** *(Demo langsung sistem di browser, RBAC 10 Role, E-Surat QR Code, Contact Tracing, & AI Assistant)*

---

## 📖 Deskripsi Aplikasi
**SIKES (Sistem Informasi Kesehatan) Pondok Pesantren** adalah platform manajemen layanan kesehatan komprehensif yang dirancang khusus untuk lingkungan pondok pesantren dengan mobilitas dan kepadatan santri yang tinggi. 

SIKES mendigitalisasi seluruh rantai proses kesehatan di pesantren, mulai dari **pencatatan rekam medis santri, manajemen antrean poliklinik/poskestren, kartu riwayat alergi, pemantauan status gizi kantin, perizinan kepulangan santri sakit terintegrasi verifikasi QR-Code, hingga penelusuran kontak erat (tracing) penyakit menular** antar kamar asrama santri.

### Fitur Utama Sistem:
* 🏥 **Rekam Medis Elektronik (RME) Santri**: Riwayat keluhan, diagnosa ICD-10 ringkas, tindakan medis, tensi/suhu tubuh, dan riwayat alergi obat.
* 🛡️ **Kontrol Akses Berlapis (RBAC)**: Mendukung **10 Role Pengguna** dengan **57 Hak Akses Spesifik**:
  1. `Super Admin` (Manajemen sistem menyeluruh)
  2. `Petugas Medis / Dokter` (Pemeriksaan & rekam medis)
  3. `Pengasuhan Santri` (Perizinan sakit & pantauan istirahat)
  4. `Keamanan / Pos Satpam` (Verifikasi QR Code izin keluar santri)
  5. `Admin Santri / Piket` (Pendaftaran antrean & skrining suhu)
  6. `Petugas Gizi & Dapur` (Diet khusus santri sakit)
  7. `Bendahara Poskestren` (Biaya pengobatan santri)
  8. `Kyai / Pimpinan Pesantren` (Dashboard eksekutif & monitoring wabah)
  9. `Santri` (Melihat status izin & edukasi kesehatan)
  10. `Wali Santri` (Notifikasi kesehatan putra-putrinya)
* 📜 **E-Surat Izin Istirahat / Pulang & QR Code Verifikasi**: Pembuatan surat izin otomatis dengan tanda tangan digital dan QR-Code yang dapat divalidasi langsung oleh pos keamanan (satpam) pesantren tanpa login.
* 🔍 **Contact Tracing Penyakit Menular**: Deteksi dini persebaran wabah (scabies, influenza, ISPA) berdasarkan penempatan santri di asrama, blok, dan kamar.
* 💊 **Manajemen Stok & Transaksi Obat Poskestren**: Kartu stok obat, peringatan kedaluwarsa otomatis, serta log pengurangan obat saat pemeriksaan.

---

## 🤖 Fitur Kecerdasan Buatan (AI Assistant)
SIKES dilengkapi modul asisten cerdas berbasis LLM (**Groq Cloud Llama-3.3-70B & Llama-3.1-8B**) yang dirancang untuk mendukung operasional tenaga medis pesantren:
1. **Triase & Rekomendasi Medis Pertama**: Memberikan panduan penanganan awal penyakit umum pesantren (misal: scabies, dermatitis, demam berdarah, tipoid) berdasarkan gejala yang diinputkan petugas medis.
2. **Perumusan Menu Gizi & Diet Khusus Santri**: Membantu staf dapur pesantren merumuskan pola diet makanan bagi santri yang sedang dalam masa pemulihan penyakit maag, diare, atau alergi tertentu.
3. **Deteksi Dini Klaster Wabah (AI Cluster Analysis)**: Menganalisis lonjakan tren keluhan serupa di satu blok kamar asrama dan memberikan rekomendasi tindakan isolasi serta sanitasi lingkungan.
4. **Widget Tanya Jawab Kesehatan Interaktif**: Konsultasi edukasi kesehatan santri langsung dari antarmuka web.

---

## 📂 Struktur Dokumen (Software Engineering Docs)
Dokumentasi lengkap proyek dapat diakses pada direktori [`/docs`](docs/):
* 📄 **[Product Requirement Document (PRD)](docs/PRD_SIKES_Pesantren.md)**: Analisis kebutuhan produk, user persona, problem statement, dan fitur fungsional.
* 📐 **[Software Design Document (SDD)](docs/SDD_SIKES_Pesantren.md)**: Arsitektur MVC, Use Case Diagram, Activity Diagram, Skema 32 Tabel Basis Data, dan Spesifikasi Keamanan.
* 🧪 **[Laporan Pengujian Sistem (Testing Report)](docs/Laporan_Pengujian_SIKES.md)**: Hasil pengujian fungsionalitas Black Box, skenario pengujian RBAC, dan validasi respons AI.
* 📊 **[Slide & Naskah Presentasi](docs/presentasi-individu/)**: Slide presentasi PowerPoint (`.pptx`), rancangan diagram usecase (`.svg`/`.png`), dan panduan naskah rekaman 12 menit.

---

## 💻 Kebutuhan Sistem (Prerequisites)
* **Web Server**: Apache (XAMPP / Laragon / Standalone Apache)
* **PHP**: Versi 8.1 atau lebih baru (ekstensi `pdo_mysql`, `curl`, `mbstring`, `openssl` aktif)
* **Database**: MySQL 5.7+ atau MariaDB 10.4+
* **Browser**: Google Chrome, Mozilla Firefox, Microsoft Edge, atau Safari (Versi Modern)

---

## 🚀 Panduan Instalasi & Menjalankan Proyek

### 1. Clone Repositori
Buka terminal / Git Bash di folder `htdocs` web server Anda (misal `d:/PROGRAM/XAMPP/htdocs/`):
```bash
git clone https://github.com/yogasatriaalfaritzi2-commits/SIKES-Pesantren.git
cd SIKES-Pesantren
```

### 2. Konfigurasi Lingkungan (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Sesuaikan konfigurasi koneksi database dan API Key AI:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=sikes_pondok
DB_USER=root
DB_PASS=

# Masukkan API Key Groq Anda untuk mengaktifkan AI Assistant
AI_API_KEY=gsk_your_groq_api_key_here
```

### 3. Impor Database
1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) atau aplikasi database client (DBeaver/Navicat).
2. Buat database baru bernama: `sikes_pondok`.
3. Impor file SQL yang berada di:
   ```
   database/sikes_db.sql
   ```
   *(Opsional: jalankan juga `database/migration_v16_modul_segera.sql` jika diperlukan penambahan tabel migrasi)*.

### 4. Menjalankan Aplikasi
1. Pastikan modul **Apache** dan **MySQL** pada XAMPP Control Panel sudah dalam status **Running**.
2. Akses aplikasi melalui browser di tautan:
   ```
   http://localhost/SIKES-Pesantren/public/login
   ```

---

## 🔑 Kredensial Akun Bawaan (Default Login)
Semua akun pengujian di bawah ini menggunakan kata sandi default: **`admin123`**

| Role / Peran | Username | Password Default | Akses Utama |
|---|---|---|---|
| **Super Admin** | `admin` | `admin123` | Akses penuh semua modul & pengaturan |
| **Petugas Medis** | `medis` | `admin123` | Rekam medis, resep obat, rawat inap |
| **Pengasuhan Santri** | `pengasuhan` | `admin123` | Perizinan kepulangan & pantauan santri |
| **Petugas Keamanan** | `keamanan` | `admin123` | Validasi QR Code izin keluar santri |
| **Piket Santri** | `santriadmin`| `admin123` | Pendaftaran antrean pasien & skrining |
| **Petugas Gizi & Dapur** | `dapur` | `admin123` | Manajemen menu diet santri sakit |
| **Bendahara Poskestren**| `bendahara` | `admin123` | Keuangan poskestren & pembayaran obat |
| **Pimpinan / Kyai** | `kyai` | `admin123` | Dashboard ringkasan eksekutif |
| **Santri** | `santri` | `admin123` | Portal santri & riwayat perizinan mandiri |
| **Wali Santri** | `wali` | `admin123` | Pemantauan status kesehatan anak |

---

## 🛡️ Aspek Keamanan Sistem
* **Per-Route Middleware Auth & RBAC**: Pengecekan izin otentikasi ketat di sisi server sebelum mengeksekusi controller.
* **Proteksi CSRF (Cross-Site Request Forgery)**: Token unik divalidasi pada setiap formulir POST.
* **Prepared Statements**: Seluruh kueri basis data memanfaatkan PDO prepared statements untuk mencegah ancaman SQL Injection.
* **Rate Limiting Login**: Penguncian akun otomatis selama 5 menit jika terjadi 5 kali kegagalan login berturut-turut pada username yang sama.
* **Enkripsi Sandi**: Menggunakan fungsi standar industri `password_hash()` dengan algoritma Bcrypt.

---

## ⚖️ Lisensi & Hak Cipta
Hak Cipta © 2026 **Yoga Satria Alfaritzi (NIM: 41257231)** - Kelompok 6.  
Dikembangkan sebagai bagian dari pemenuhan luaran tugas Proyek Akhir.
