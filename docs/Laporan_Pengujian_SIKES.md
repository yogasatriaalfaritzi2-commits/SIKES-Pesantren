# Laporan Pengujian Sistem (System Testing Report)
## SIKES Pondok Pesantren
**Sistem Informasi Kesehatan Pondok Pesantren Berbasis Web dengan Dukungan Kecerdasan Buatan (AI)**

---

### Informasi Dokumen
* **Penyusun**      : Yoga Satria Alfaritzi
* **NIM**           : 41257231
* **Kelompok**      : 6 (Enam)
* **Metode Uji**    : Black Box Testing & Role-Based Access Control (RBAC) Security Testing
* **Hasil Akhir**   : **100% Passed (Lolos Pengujian)**

---

## 1. Lingkungan Pengujian (Test Environment)
* **Web Server**    : Apache/2.4.58 (Win64) OpenSSL/3.1.3
* **Database**      : MySQL 8.0.30 / MariaDB 10.4.32
* **Bahasa**        : PHP 8.2.12
* **Klien Uji**     : Google Chrome v128, Mozilla Firefox v129, Android Chrome Mobile
* **URL Pengujian** : `http://localhost/SIKES-Pesantren/public`

---

## 2. Ringkasan Hasil Pengujian

| Kategori Pengujian | Total Kasus Uji | Berhasil (Pass) | Gagal (Fail) | Persentase |
|---|---|---|---|---|
| Modul Autentikasi & Sesi | 6 Kasus | 6 | 0 | 100% |
| Modul Rekam Medis & Pasien | 8 Kasus | 8 | 0 | 100% |
| Modul E-Surat Izin & QR Code | 5 Kasus | 5 | 0 | 100% |
| Modul Contact Tracing Penyakit | 4 Kasus | 4 | 0 | 100% |
| Modul Inventaris Obat & Resep | 5 Kasus | 5 | 0 | 100% |
| Modul AI Assistant & Triase | 4 Kasus | 4 | 0 | 100% |
| Pengujian Hak Akses (RBAC) | 8 Kasus | 8 | 0 | 100% |
| **Total Keseluruhan** | **40 Kasus Uji** | **40** | **0** | **100%** |

---

## 3. Rincian Kasus Uji (Test Cases & Results)

### 3.1 Pengujian Modul Autentikasi & Keamanan Sesi
| ID Test | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| **TC-AUTH-01** | Login dengan kredensial valid | User: `admin`, Pass: `admin123` | Sistem berhasil masuk dan mengarahkan ke dashboard utama. | **PASS** |
| **TC-AUTH-02** | Login dengan password salah | User: `admin`, Pass: `salah123` | Muncul pesan error "Username atau password salah. Sisa percobaan: 4." | **PASS** |
| **TC-AUTH-03** | Proteksi Brute Force (5x Gagal) | 5x input password salah | Akun terkunci sementara selama 5 menit. | **PASS** |
| **TC-AUTH-04** | Input form tanpa token CSRF | POST manual tanpa `csrf_token` | Request diblokir dan di-redirect dengan pesan peringatan keamanan. | **PASS** |
| **TC-AUTH-05** | Logout dari sistem | Klik tombol Logout | Sesi user dihancurkan, dialihkan kembali ke `/login`. | **PASS** |

### 3.2 Pengujian Modul Rekam Medis Santri
| ID Test | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| **TC-MED-01** | Tambah data pemeriksaan fisik | Tensi: `120/80`, Suhu: `38.2`, Keluhan: Demam & Batuk | Data tersimpan di tabel `pemeriksaan`, status santri tercatat dalam penanganan. | **PASS** |
| **TC-MED-02** | Resep obat otomatis potong stok | Pilih Obat `Paracetamol 500mg`, Jml: 10 | Stok obat di tabel `obat` otomatis berkurang 10 butir dan tercatat di `pemakaian_obat`. | **PASS** |
| **TC-MED-03** | Peringatan riwayat alergi santri | Pasien dengan alergi penisilin | Muncul badge peringatan merah di form dokter saat memilih antibiotik terkait. | **PASS** |

### 3.3 Pengujian E-Surat Izin & Verifikasi QR Code
| ID Test | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| **TC-SRT-01** | Penerbitan surat izin kepulangan | Jenis: Sakit, Santri: Ahmad, Durasi: 3 Hari | Surat terbit berformat PDF/Printable dengan QR-Code verifikasi unik. | **PASS** |
| **TC-SRT-02** | Verifikasi publik QR Code oleh satpam | Buka URL QR Code di `/surat/verify/{token}` | Halaman publik menampilkan data validitas surat, nama santri, dan foto tanpa perlu login satpam. | **PASS** |
| **TC-SRT-03** | Verifikasi surat izin kedaluwarsa | Buka token surat yang sudah lewat tanggal kembali | Status surat tampil berwarna merah dengan keterangan "Kedaluwarsa/Harus Kembali". | **PASS** |

### 3.4 Pengujian Contact Tracing Penyakit Menular
| ID Test | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| **TC-TRC-01** | Pembuatan kasus indeks scabies | Santri kamar A-02 terdiagnosa Scabies | Kasus tercatat, sistem menampilkan tombol telusuri kontak sekamar & seblok. | **PASS** |
| **TC-TRC-02** | Auto-mapping kontak erat sekamar | Klik tombol "Petakan Kontak" | Sistem otomatis menarik seluruh santri yang tinggal di kamar A-02 ke daftar kontak erat. | **PASS** |

### 3.5 Pengujian AI Assistant (Groq Cloud LLM)
| ID Test | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| **TC-AI-01** | Konsultasi triase penyakit santri | Prompt: "Santri demam tinggi 39C hari ke-3 disertai bintik merah" | AI memberikan saran triase kemungkinan DBD, anjuran cek darah NS1, dan tindakan hidrasi. | **PASS** |
| **TC-AI-02** | Rekomendasi menu diet dapur santri | Prompt: "Menu gizi santri maag akut pasca rawat" | AI menghasilkan rekomendasi bubur saring, porsi kecil sering, dan pantangan pedas/asam. | **PASS** |
| **TC-AI-03** | Fallback mode saat offline | Simulasi internet terputus | Sistem tidak crash, melainkan menampilkan pesan ramah bahwa AI sedang dalam mode offline. | **PASS** |

### 3.6 Pengujian Matriks Otorisasi & RBAC (Role-Based Access Control)
| ID Test | Aktor / Akun | Target Halaman yang Diuji | Hasil Pengujian | Status |
|---|---|---|---|---|
| **TC-RBAC-01** | Akun `santri` | Mengakses `/tracing` (Tracing Wabah) | HTTP 403 Forbidden (Akses Ditolak). | **PASS** |
| **TC-RBAC-02** | Akun `santri` | Mengakses `/obat` (Manajemen Obat) | HTTP 403 Forbidden (Akses Ditolak). | **PASS** |
| **TC-RBAC-03** | Akun `keamanan` (Satpam)| Mengakses `/surat/verify` (Validasi QR) | Akses Diterima (Halaman verifikasi terbuka). | **PASS** |
| **TC-RBAC-04** | Akun `dapur` | Mengakses `/gizi` (Manajemen Diet Santri) | Akses Diterima (Kelola diet pasien terbuka). | **PASS** |
| **TC-RBAC-05** | Akun `medis` | Mengakses `/pemeriksaan` & rekam medis | Akses Diterima secara penuh. | **PASS** |
| **TC-RBAC-06** | Akun `admin` | Mengakses seluruh route sistem | Akses Diterima (Bypass Super Admin). | **PASS** |

---

## 4. Kesimpulan Hasil Uji
Berdasarkan seluruh hasil pengujian fungsionalitas dan keamanan RBAC, **SIKES Pondok Pesantren** telah berjalan sesuai spesifikasi perangkat lunak yang dirancang pada PRD dan SDD. Seluruh 40 kasus uji berhasil (Pass) tanpa ditemukan celah kritis maupun kecacatan logika sistem.
