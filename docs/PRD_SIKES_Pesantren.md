# Product Requirement Document (PRD)
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

## 1. Pendahuluan & Latar Belakang Masalah
Pondok pesantren memiliki karakteristik unik dengan ribuan santri yang tinggal bersama dalam lingkungan komunal (asrama, kamar, dan ruang belajar bersama). Kondisi ini menciptakan tantangan besar di bidang kesehatan:
1. **Risiko Penularan Penyakit Menular Cepat**: Penyakit seperti Scabies (kudis), Infeksi Saluran Pernapasan Akut (ISPA), konjungtivitis, dan diare sering kali merebak dengan cepat antar kamar asrama karena minimnya data pelacakan kontak erat (contact tracing).
2. **Pencatatan Rekam Medis Masih Konvensional**: Riwayat alergi obat, riwayat penyakit kronis santri, dan riwayat pemeriksaan fisik sering tercatat di buku fisik yang rentan hilang, rusak, atau sulit diakses saat keadaan darurat.
3. **Mekanisme Perizinan Sakit yang Belum Terintegrasi**: Santri yang sakit dan diperbolehkan pulang atau beristirahat membutuhkan verifikasi otentik agar tidak disalahgunakan saat melewati pos keamanan/satpam gerbang pesantren.
4. **Keterbatasan Jumlah Tenaga Medis Poskestren**: Rasio santri dengan tenaga medis sering tidak seimbang, sehingga dibutuhkan asisten pintar yang dapat membantu triase awal, rekomendasi gizi, dan deteksi dini lonjakan kasus.

---

## 2. Tujuan Produk (Product Goals)
* Membangun platform web terintegrasi untuk mendigitalkan seluruh rantai operasional Pos Kesehatan Pesantren (Poskestren).
* Mengimplementasikan sistem **Contact Tracing Asrama** untuk memetakan dan memutus rantai penularan wabah santri.
* Menerbitkan **E-Surat Izin Istirahat / Kepulangan dengan Verifikasi QR-Code** yang dapat dicek validitasnya oleh pos satpam secara real-time.
* Mengintegrasikan teknologi **Generative AI (Groq LLM Llama 3.3)** sebagai asisten triase medis, konsultasi rekomendasi gizi kantin pesantren, dan deteksi klaster wabah.

---

## 3. Profil Pengguna & Peran (User Persona & RBAC)
Sistem mengakomodasi **10 Role Pengguna** dengan batasan hak akses sesuai tugas operasionalnya:
1. **Super Admin**: Mengelola master data, akun pengguna, hak akses role, serta audit trail log aktivitas.
2. **Petugas Medis (Dokter/Perawat)**: Melakukan anamnesis, input pemeriksaan fisik, diagnosa penyakit, resep obat, dan rekomendasi rujukan/izin.
3. **Pengasuhan Santri**: Menerbitkan izin kepulangan santri sakit dan memantau santri yang sedang isolasi di asrama.
4. **Petugas Keamanan (Satpam)**: Melakukan pemindaian QR Code e-surat izin santri di pintu gerbang utama untuk verifikasi keluar-masuk.
5. **Petugas Piket / Admin Santri**: Mengelola registrasi antrean poliklinik dan skrining awal keluhan santri.
6. **Petugas Gizi & Dapur**: Memantau daftar santri sakit yang membutuhkan menu makanan/diet khusus.
7. **Bendahara Poskestren**: Mengelola billing pengobatan dan rekapitulasi keuangan poskestren.
8. **Pimpinan / Kyai**: Memantau laporan eksekutif tren kesehatan, statistik santri sakit, dan kesiapan fasilitas poskestren.
9. **Santri**: Mengakses edukasi kesehatan mandiri dan memeriksa status perizinan sakitnya.
10. **Wali Santri**: Menerima informasi resmi kondisi kesehatan anak dan riwayat pemeriksaan dari poskestren.

---

## 4. Kebutuhan Fungsional (Functional Requirements)

| Kode Kebutuhan | Nama Modul | Deskripsi Kebutuhan |
|---|---|---|
| **FR-01** | Otentikasi & Autorisasi | Login aman, rate-limiting (proteksi brute force 5x gagal), session management, dan pembatasan halaman berbasis 57 permission. |
| **FR-02** | Master Data Santri & Asrama | Pendataan santri lengkap dengan data kamar, blok, asrama, golongan darah, dan riwayat alergi obat. |
| **FR-03** | Rekam Medis & Pemeriksaan | Pencatatan tanda vital (tensi, nadi, suhu), keluhan, diagnosa ICD-10 ringkas, tindakan medis, dan pemakaian obat. |
| **FR-04** | E-Surat Izin & QR-Code | Penerbitan surat izin istirahat/pulang dengan QR-Code unik ber-enkripsi verifikasi publik. |
| **FR-05** | Contact Tracing Wabah | Modul identifikasi kontak erat santri sekamar dan seblok asrama dari kasus penyakit menular aktif. |
| **FR-06** | Inventaris & Resep Obat | Pengurangan otomatis stok obat saat resep diterbitkan, peringatan stok menipis, dan deteksi obat kedaluwarsa. |
| **FR-07** | Asisten AI Kesehatan | Konsultasi triase klinis berbantu AI, analisis potensi outbreak klaster asrama, dan rekomendasi menu gizi pemulihan. |
| **FR-08** | Laporan & Audit Log | Ekspor cetak rekapitulasi bulanan, laporan morbiditas penyakit, serta pencatatan log aktivitas sistem (audit trail). |

---

## 5. Kebutuhan Non-Fungsional (Non-Functional Requirements)
1. **Keamanan (Security)**:
   - Enkripsi password menggunakan `password_hash()` Bcrypt standar PHP.
   - Perlindungan menyeluruh dari serangan SQL Injection dengan PDO Prepared Statements.
   - Proteksi token CSRF pada seluruh form transaksi POST.
2. **Kinerja (Performance)**:
   - Waktu respons halaman web `< 1.2 detik` pada kondisi jaringan lokal intranet pesantren.
   - Respons inferensi AI `< 3 detik` via API streaming Groq Cloud.
3. **Ketersediaan & Keandalan (Reliability)**:
   - Sistem dapat beroperasi 24/7 di server lokal Apache (XAMPP) intranet pesantren tanpa ketergantungan koneksi internet (fitur AI otomatis fallback jika offline).
4. **Antarmuka Pengguna (Usability)**:
   - Desain responsif berbasis Bootstrap 5.3 yang nyaman diakses melalui laptop, tablet poliklinik, maupun smartphone satpam.
