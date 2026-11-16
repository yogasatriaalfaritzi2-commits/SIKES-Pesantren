# Naskah Video Demo Final — 12 menit

SIKES Pondok Pesantren · presentasi individu · versi 12 November 2026

Semua angka di naskah ini sudah dicek ke kode dan ke database `sikes_pondok` yang berjalan di
`http://localhost/SIKES-Pesantren/public`.

---

## 1. Persiapan sebelum merekam

| Hal | Nilai |
|---|---|
| URL aplikasi | `http://localhost/SIKES-Pesantren/public` |
| Akun utama | `admin` / `admin123` (Super Admin) |
| Akun uji RBAC | `santri` / `admin123` (hanya `profile.edit`) |
| Akun pendukung | `medis`, `kyai`, `keamanan` — semuanya `admin123` |
| Data demo | 5 santri, 5 pemeriksaan, 2 kasus menular, 3 surat, 8 obat, 3 inspeksi kamar |
| Peringatan aktif | 5 (2 stok menipis, 1 kedaluwarsa, 1 mendekati kedaluwarsa, 1 sanitasi) |

Siapkan tab berikut supaya tidak ada waktu terbuang:

1. `01-scope-individu.md` atau slide PPTX (untuk segmen pembuka).
2. `figma/preview/01-usecase-overview.png` dan `figma/preview/06-aktor-peran.png`.
3. Aplikasi: `/dashboard` (login `admin`).
4. Aplikasi: `/ai` (asisten + widget notifikasi).
5. Aplikasi: `/tracing` (untuk demo 403 setelah login `santri`).
6. Editor kode: `app/config/routes.php`, `app/core/Auth.php`, `app/core/Database.php`, `app/core/Session.php`.
7. `figma/preview/07-usecase-notifikasi.png` dan `figma/preview/08-alur-keamanan.png`.

Rekam tanpa musik latar, zoom browser 100–110 % agar teks terbaca, dan tampilkan kursor saat mengklik.

---

## 2. Peta waktu 12 menit

| Waktu | Durasi | Segmen | Yang tampil |
|---|---|---|---|
| 0:00–0:40 | 40 s | Pembuka | Slide judul |
| 0:40–2:10 | 90 s | Masalah, solusi, ukuran sistem | Slide angka |
| 2:10–4:10 | 120 s | Use case & aktor/peran | `01` + `06` |
| 4:10–5:40 | 90 s | Demo RBAC di server | Login `santri` → 403 |
| 5:40–8:10 | 150 s | Asisten AI tiga lapis | `/ai` + pertanyaan |
| 8:10–9:30 | 80 s | Notifikasi dihitung ulang | Widget `/ai` + `07` |
| 9:30–11:10 | 100 s | Coding web & keamanan | `08` + potongan kode |
| 11:10–12:00 | 50 s | Penutup & batasan | Slide penutup |

---

## 3. Naskah per segmen

### 0:00–0:40 · Pembuka

**Tampilkan:** slide judul "SIKES Pondok Pesantren — Asisten AI hybrid, RBAC, dan alur operasional".

**Narasi:**

> Assalamualaikum. Saya akan mendemonstrasikan SIKES, sistem informasi kesehatan Pondok
> Pesantren yang saya bangun sendiri dengan PHP 8 tanpa framework. Dalam dua belas menit saya
> akan menunjukkan empat hal: use case dan peran pengguna, asisten AI tiga lapis, notifikasi yang
> dihitung dari data, dan kontrol keamanan yang saya tulis di sisi server.

### 0:40–2:10 · Masalah, solusi, ukuran sistem

**Tampilkan:** slide "Ukuran sistem". Sorot angka satu per satu.

**Narasi:**

> Masalahnya sederhana: data kesehatan santri tersebar di buku dan grup WhatsApp, izin
> kepulangan perlu persetujuan berlapis, dan pimpinan butuh ringkasan tanpa membuka semua menu.
>
> SIKES menjawabnya dengan satu aplikasi: 22.178 baris PHP, 176 rute, 28 controller, 36 model,
> dan 84 view. Akses diatur 10 peran dengan 57 permission, dan 76 pemanggilan log aktivitas di 23
> controller mencatat aksi penting ke tabel `activity_logs`. Datanya ada di 32 tabel pada skema SQL.
>
> Saya sengaja tidak memakai framework. Artinya routing, session, CSRF, dan query aman harus saya
> tulis sendiri di folder `app/core`. Itu justru bagian yang ingin saya pertanggungjawabkan hari ini.

### 2:10–4:10 · Use case dan aktor/peran

**Tampilkan:** `preview/01-usecase-overview.png`, lalu `preview/06-aktor-peran.png`.

**Narasi:**

> Diagram pertama adalah use case overview. Ada sepuluh aktor manusia dan tiga aktor pendukung:
> Groq sebagai penyedia model, MySQL, dan waktu sebagai pemicu. Aktor tidak saya hubungkan ke
> setiap use case, tetapi ke lima paket modul supaya diagramnya masih terbaca.
>
> Peran dan aksinya begini. Super Admin memegang akses penuh karena ditandai `is_system = 1`.
> Tenaga Medis punya 22 izin: pemeriksaan, rekam medis, obat, rujukan, BPJS, sampai tracing.
> Pengasuhan 16 izin: asrama, piket, sanitasi, dan tahap persetujuan kepulangan. Keamanan 9 izin:
> buku tamu dan gerbang. Admin Santri 14 izin: data santri dan wali. Dapur 4 izin, Bendahara 5 izin,
> Kyai 20 izin pemantauan. Santri dan wali masing-masing hanya satu izin, yaitu mengubah profilnya
> sendiri.
>
> Yang penting: izin ini tidak dicek di tampilan. Router memeriksa middleware per rute, lalu
> controller mengulang pemeriksaan dengan `requirePermission`. Kalau ditolak, server mengembalikan
> 403 sebelum query berjalan. Menyembunyikan tombol hanya kosmetik.

### 4:10–5:40 · Demo RBAC di server

**Tampilkan:** logout, login sebagai `santri` / `admin123`, lalu buka `/tracing`.

**Narasi:**

> Saya masuk sebagai santri. Menu yang terlihat memang sedikit, tetapi bukti sebenarnya ada di
> server: saya ketik langsung alamat `/tracing`. Hasilnya halaman 403 Akses Ditolak, bukan halaman
> yang kosong. Saya coba `/pemeriksaan` dan `/ai`, juga 403. Artinya membuka URL secara manual
> tidak menembus hak akses.
>
> Sekarang saya login kembali sebagai admin. Halaman yang sama terbuka normal. Perbedaan ini yang
> saya sebut kontrol di sisi server, bukan di tombol.

**Jika ditanya "apa buktinya permission dicek dua kali?":** buka `app/core/Router.php` dan
`app/core/Controller.php`, tunjukkan `hasPermission` dan `requirePermission`.

### 5:40–8:10 · Asisten AI tiga lapis

**Tampilkan:** `/ai`, lalu ajukan pertanyaan berurutan.

**Narasi:**

> Asisten AI ini bukan chatbot yang semua pertanyaannya dikirim ke model. Ada tiga lapis, dan
> pertanyaan dijawab lapis termurah yang mampu menjawab.
>
> Lapis satu, intent lokal. Saya tanya "berapa jumlah santri". Jawabannya lima, langsung dari
> database, tanpa panggilan API. Saya tanya "berapa obat". Jawabannya delapan item, dua stok
> menipis. "Antrean pemeriksaan" dijawab dua. "Berapa kasus menular" dijawab satu. Semua ini
> pencocokan pola dan query, jadi cepat dan tidak memakai kuota.
>
> Lapis dua, mesin heuristik. Untuk pertanyaan yang butuh hitungan, sistem menghitung sendiri:
> tren periode dibanding periode sebelumnya, umur antrean, stok dan kedaluwarsa obat, skor sanitasi
> delapan parameter, IMT, dan heatmap blok. Hasilnya dipakai untuk ringkasan dan peringatan.
>
> Lapis tiga, model bahasa. Hanya pertanyaan terbuka yang dikirim ke Groq. Konteksnya dipotong
> sesuai peran, lalu model dicoba berurutan: model chat cepat, model analisis, lalu model cadangan.
> Kalau satu gagal, lanjut ke berikutnya. Kalau semuanya gagal, ringkasan mesin lokal tetap tampil.
> Batasnya 700 token keluaran dan 45 detik per panggilan.
>
> Dua lapis pertama tetap bekerja saat API mati. Itu pembeda dari aplikasi yang hanya menempelkan
> satu panggilan model.

**Pertanyaan cadangan yang sudah terbukti di data demo:**

- "siapa saja role pengguna" → daftar sepuluh peran beserta nama pengguna.
- "siapa saya" → identitas dan peran pengguna yang sedang login.
- "siapa saja santri" → daftar nama santri.

**Opsional (bukti kuat):** buka DevTools → Network, filter `groq`. Pertanyaan angka tidak
menghasilkan request; pertanyaan terbuka menghasilkan satu request.

### 8:10–9:30 · Notifikasi dihitung ulang

**Tampilkan:** widget notifikasi di `/ai` dan `preview/07-usecase-notifikasi.png`.

**Narasi:**

> Notifikasi di sini bukan pesan yang dikirim, tetapi hasil hitung. Widget memanggil
> `GET /ai/notifications` setiap delapan detik selama halaman asisten terbuka. Setiap panggilan
> menjalankan enam aturan pada data terkini: kunjungan yang masih menunggu, kontak tracing yang
> belum diperiksa, stok obat menipis, obat kedaluwarsa, obat mendekati kedaluwarsa sembilan puluh
> hari, dan kamar dengan sanitasi risiko tinggi.
>
> Hari ini ada lima peringatan: dua stok menipis, satu kedaluwarsa, satu mendekati kedaluwarsa,
> dan satu kamar risiko tinggi. Angka ini berubah kalau datanya berubah, karena tidak ada yang
> di-hardcode.
>
> Saya tegaskan: notifikasi bukan klaim AI. Ini aturan ambang dari data operasional. Tidak ada
> tabel notifikasi, tidak ada email, SMS, atau WhatsApp. Klaim AI tetap di modul asisten tadi.

**Demo klik:** klik peringatan "obat stok menipis" → halaman obat dengan filter terkait.

### 9:30–11:10 · Coding web dan keamanan

**Tampilkan:** `preview/08-alur-keamanan.png`, lalu potongan kode.

**Narasi:**

> Satu request melewati enam tahap: browser, `public/index.php`, Router, Controller, Model dengan
> PDO, lalu MySQL. Di setiap tahap ada kontrol yang saya tulis sendiri.
>
> Password disimpan dengan `password_hash`, bukan teks biasa. Form membawa token CSRF dan
> dibandingkan dengan `hash_equals`. Query memakai prepared statement dan emulasi prepare dimatikan
> di `Database.php` baris dua puluh enam. Cookie session httponly dan samesite, dan ID sesi
> diperbarui saat login. Login dibatasi lima percobaan per tiga ratus detik.
>
> Otorisasinya berlapis: rute mendeklarasikan middleware `auth` atau permission, lalu controller
> mengulang pemeriksaan. Permission dimuat ulang setiap request, jadi perubahan peran berlaku tanpa
> login ulang. Aksi penting masuk `activity_logs` beserta pengguna dan waktunya.
>
> Saya juga jujur soal yang belum selesai: belum ada rate limit khusus endpoint AI, belum ada tes
> otomatis, dan kunci API masih punya nilai default di konfigurasi. Sebelum repo dibagikan, kunci
> itu harus dicabut dan dibaca hanya dari `.env`.

**Tunjukkan singkat:** satu baris rute di `routes.php`, `requirePermission` di controller,
`ATTR_EMULATE_PREPARES => false`, dan isi tabel `activity_logs` (login terakhir).

### 11:10–12:00 · Penutup dan batasan

**Tampilkan:** slide penutup.

**Narasi:**

> Kesimpulannya: SIKES menggabungkan alur operasional pondok, kontrol akses sepuluh peran, dan
> asisten AI hybrid dalam satu aplikasi PHP yang saya tulis tanpa framework. Bukti angkanya bisa
> dibuka di repository: 176 rute, 57 permission, dan 32 tabel.
>
> Batasannya juga saya sebut: belum ada tinjauan visual hasil render di mesin ini, belum ada tes
> otomatis, dan kunci API harus dicabut sebelum repo dibagikan. Terima kasih, saya siap menjawab
> pertanyaan.

---

## 4. Jawaban cadangan untuk tanya jawab

| Pertanyaan | Jawaban singkat |
|---|---|
| "Kenapa tidak pakai framework?" | Untuk menunjukkan pemahaman routing, session, CSRF, dan query aman; semuanya ada di `app/core` dan bisa ditunjuk satu per satu. |
| "Apakah AI-nya benar-benar AI?" | Ya, di modul asisten: intent lokal, heuristik, lalu model bahasa dengan failover. Notifikasi sengaja tidak diklaim AI. |
| "Bagaimana kalau API mati?" | Dua lapis pertama tetap jalan; jawaban lokal dan ringkasan heuristik tetap tampil. |
| "Apakah data medis bocor ke model?" | Konteks dipotong sesuai peran sebelum masuk prompt. Ini batas privasi yang masih perlu dipersempit; saya sebut sebagai pekerjaan lanjutan. |
| "Berapa biayanya?" | Model gratis Groq dengan batas kuota; batas 700 token dan timeout 45 detik menahan biaya. |
| "Apa yang belum selesai?" | Rate limit endpoint AI, tes otomatis, dan pencabutan kunci API default. |
| "Di mana bukti 403-nya?" | Demo di 4:10; kodenya `Router` + `requirePermission`; halaman 403 asli dari server. |

---

## 5. Yang tidak boleh diklaim di video

- Jangan bilang "semua sudah dites otomatis" — belum ada suite tes.
- Jangan bilang "aman sepenuhnya" — sebut kontrol yang ada dan yang belum.
- Jangan menampilkan nilai kunci API atau isi `.env` di layar.
- Jangan menyebut notifikasi sebagai produk AI.
- Jangan mengklaim render/visual PPTX sudah ditinjau; di mesin ini belum bisa.
