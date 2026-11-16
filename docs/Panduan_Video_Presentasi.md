# Panduan Video Presentasi & Demo Sistem (Luaran Wajib)
## SIKES Pondok Pesantren
**Identitas Mahasiswa:**
* **Nama**     : Yoga Satria Alfaritzi
* **NIM**      : 41257231
* **Kelompok** : 6

---

### ⚠️ Ketentuan Wajib Video Presentasi:
1. **Wajah Wajib Terlihat Jelas di Webcam** (Picture-in-Picture di pojok layar).
2. **Demo Bersifat Live (Langsung di Browser)**, bukan sekadar slide screenshot.
3. Kualitas audio jernih dan kursor mouse terlihat saat berinteraksi.

---

## 📹 TAHAP 1: Presentasi PRD (Product Requirement Document)
* **Target Durasi**: 3 – 5 Menit
* **Materi yang Ditampilkan**: Slide Pembuka / Dokumen `docs/PRD_SIKES_Pesantren.md`
* **Poin Pembahasan**:
  1. **Perkenalan Diri (0:00 - 0:45)**:  
     Sebutkan nama lengkap, NIM (41257231), kelompok 6, dan judul proyek: *Sistem Informasi Kesehatan Pondok Pesantren Berbasis Web Terintegrasi AI (SIKES)*.
  2. **Latar Belakang & Masalah Riil Pesantren (0:45 - 2:00)**:  
     Jelaskan kepadatan santri di asrama yang menyebabkan penularan penyakit menular (seperti Scabies dan ISPA) sangat cepat, pencatatan rekam medis yang masih manual, dan rawannya pemalsuan surat izin keluar santri di gerbang pesantren.
  3. **Solusi & Fitur Unggulan SIKES (2:00 - 3:30)**:  
     Digitalisasi rekam medis santri, otomatisasi contact tracing wabah sekamar dan seblok asrama, validasi surat izin berbasis QR Code publik, serta fitur asisten AI pintar.
  4. **Target Pengguna & Batasan (3:30 - 4:30)**:  
     Menjangkau 10 aktor peran mulai dari Santri, Dokter/Medis, Pengasuhan, Satpam, hingga Kyai pimpinan pesantren.

---

## 📹 TAHAP 2: Presentasi SDD (UML & UI/UX Design)
* **Target Durasi**: 5 – 7 Menit
* **Materi yang Ditampilkan**: Diagram SVG/PNG di `docs/presentasi-individu/figma/` dan Dokumen `docs/SDD_SIKES_Pesantren.md`
* **Poin Pembahasan**:
  1. **Arsitektur Perangkat Lunak MVC (0:00 - 1:30)**:  
     Jelaskan bagaimana Front Controller `public/index.php` memproses request, Router menangani per-route middleware RBAC, controller mengeksekusi logika, dan model berkomunikasi dengan MySQL secara aman via PDO.
  2. **Use Case Diagram & 10 Aktor Peran (1:30 - 3:30)**:  
     Buka file gambar `figma/preview/01-usecase-overview.png` dan `06-aktor-peran.png`. Jelaskan pembagian wewenang masing-masing peran (Super Admin, Medis, Pengasuhan, Satpam, dll.).
  3. **Activity Diagram Alur Surat QR Code & Tracing Wabah (3:30 - 5:00)**:  
     Tampilkan diagram `figma/preview/02-activity-kepulangan.png` (alur surat perizinan santri sampai diverifikasi satpam) dan `04-activity-tracing.png` (deteksi kontak erat sekamar asrama).
  4. **Desain Basis Data (5:00 - 6:30)**:  
     Jelaskan relasi tabel utama dari 32 tabel (users, permissions, santri, asrama, kamar, pemeriksaan, tracing_kasus, dan resep obat).

---

## 📹 TAHAP 3: Final Live Demo & Technical Walkthrough
* **Target Durasi**: 8 – 12 Menit
* **Materi yang Ditampilkan**: Live Browser `http://localhost/SIKES-Pesantren/public`
* **Skenario Demo Praktik**:
  1. **Pembuka & Tinjauan Dashboard Admin (0:00 - 1:30)**:  
     Login sebagai `admin` (password: `admin123`). Tunjukkan widget ringkasan statistik santri sakit, kamar isolasi, dan peringatan stok obat menipis.
  2. **Demo Rekam Medis & Pengurangan Stok Obat (1:30 - 3:30)**:  
     Buka menu Pemeriksaan Medis. Tambahkan pemeriksaan baru untuk santri, input tensi/suhu, berikan diagnosa, dan pilih resep obat. Tunjukkan bahwa stok obat di inventaris otomatis berkurang secara real-time.
  3. **Demo E-Surat Izin & Scan QR-Code Publik (3:30 - 5:30)**:  
     Cetak e-surat izin kepulangan santri sakit. Buka tautan verifikasi QR Code di tab baru/incognito. Tunjukkan bahwa satpam gerbang dapat memvalidasi keaslian surat tanpa perlu login.
  4. **Demo Contact Tracing Wabah Penyakit Menular (5:30 - 7:30)**:  
     Buka menu Tracing. Buat kasus indeks scabies pada seorang santri, lalu klik "Petakan Kontak Erat". Tunjukkan bagaimana sistem secara cerdas langsung menampilkan seluruh teman sekamar santri tersebut untuk segera diisolasi.
  5. **Demo Fitur Kecerdasan Buatan (AI Assistant) (7:30 - 9:30)**:  
     Buka menu Asisten AI (`/ai`). Berikan pertanyaan triase klinis (misal: penanganan santri scabies atau DBD) dan rekomendasi diet gizi untuk santri maag akut. Tunjukkan respons cerdas dari Groq Llama-3.3.
  6. **Demo Keamanan & Uji RBAC (9:30 - 11:00)**:  
     Logout dari admin, lalu login sebagai akun `santri` (`admin123`). Coba buka URL terlarang `/tracing` atau `/obat`. Tunjukkan bahwa sistem dengan sigap menolak akses dengan halaman **403 Forbidden**.
  7. **Penutup (11:00 - 12:00)**:  
     Simpulkan keunggulan sistem SIKES dalam menjaga ekosistem kesehatan pesantren dan ucapkan terima kasih.
