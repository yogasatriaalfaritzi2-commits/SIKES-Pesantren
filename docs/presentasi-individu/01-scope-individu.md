# 01 — Klaim Scope Individu (SIKES Pondok Pesantren)

> Panduan Proyek Akhir v4: *"Proyek boleh berupa proyek team atau individu. Yang dinilai adalah individu.
> Masing-masing individu harus punya repositori project atau modul berbeda."*
>
> Dokumen ini dipakai untuk menjawab satu pertanyaan penguji: **"Bagian mana yang kamu kerjakan sendiri?"**

---

## 0. Status bukti (baca dulu sebelum mengklaim)

Project ini **bukan repository git** (`git rev-parse` → `fatal: not a git repository`).
Karena itu **tidak ada riwayat authorship yang bisa diverifikasi**. Satu-satunya bukti yang tersedia adalah
**timestamp modifikasi file** (`mtime`) dan **konsistensi gaya kode**.

| Bukti | Kuat/lemah | Catatan |
|---|---|---|
| `git log` / `git blame` | ❌ tidak ada | Tidak bisa dipakai |
| `mtime` file | 🟠 inferensi | Menunjukkan *urutan* kerja, bukan *siapa* |
| Konsistensi penamaan/komentar | 🟡 kualitatif | Bisa menunjukkan satu penulis |
| Kontribusi yang Anda ingat sendiri | ✅ paling kuat | Isi bagian 3 |

**Rekomendasi:** inisialisasi git sekarang (`git init` + commit awal) agar ada jejak untuk commit berikutnya.
Ini juga memenuhi kata "repositori" di panduan. **Jangan commit sebelum bagian 5 beres** (ada API key di source).

---

## 1. Skala project (angka terverifikasi)

| Metrik | Nilai | Cara verifikasi |
|---|---|---|
| Total baris kode (php/sql/js/css) | **24.106** | `find app public database -type f … \| xargs wc -l` |
| Controller | 28 | `ls app/controllers` |
| Model | 41 | `ls app/models` |
| View | 88 | `ls app/views` |
| Route | **176** | grep `$router->get(` + `$router->post(` di `app/config/routes.php` |
| Tabel DB | 32 | `grep -c "CREATE TABLE" database/sikes_db.sql` |
| Role / Permission | 10 / 57 | seed `database/sikes_db.sql:740-750`, `:752` |
| LOC per lapisan | controllers 6.856 · models 3.383 · views 10.113 · core 1.442 · config 384 · public 580 · database 1.348 | |

---

## 2. Gelombang kerja menurut `mtime`

Ini yang bisa saya buktikan dari filesystem. Urut dari yang paling baru.

### Gelombang A — 2026-10-31 · Asisten AI & Notifikasi (3.323 LOC ≈ 13,8% project)

| File | LOC | Isi |
|---|---|---|
| `app/controllers/AiAssistantController.php` | 1.360 | Intent router regex ±20 topik, `chat()`, `chatPro()`, `notifications()`, `buildWideContext()`, `buildRoleScopedContext()`, `configGet/configSave` |
| `app/views/partials/ai_widget.php` | 1.105 | Widget mengambang: 4 tab (Chatbot / Chatbot+ / Notifikasi / Konfigurasi), badge, polling 8 detik |
| `app/core/AiAssistant.php` | 544 | Klien Groq via cURL, `analyze()`, rantai failover 5 model, `llmEnabled()`, prompt system |
| `app/views/ai/index.php` | 314 | Halaman `/ai` penuh |

Ciri khas satu penulis: komentar blok `/* ============ … ============ */` dan penamaan `camelCase` konsisten di keempat file ini.

### Gelombang B — 2026-10-24 s.d. 10-29 · Dashboard & Auth
`app/views/dashboard/index.php`, `app/core/Auth.php`, `app/models/Santri.php`

### Gelombang C — 2026-10-17 · Laporan Eksekutif & kerangka layout
`app/controllers/EksekutifController.php`, `app/config/routes.php`, `app/layouts/sidebar.php`,
`app/layouts/main.php`, `app/partials/breadcrumb.php`

### Gelombang D — 2026-10-14 · Fondasi framework-buatan-sendiri & konfigurasi
`public/index.php`, `app/core/{App,Session,Env}.php`, `app/config/{config,ai}.php`,
`app/controllers/AuthController.php`, `app/views/layouts/navbar.php`

### Gelombang E — 2026-09-22 · Modul obat/kamar/blok/asrama + migrasi
`app/controllers/{Obat,Kamar,Blok}Controller.php`, `app/models/{Obat,Kamar,Blok}.php`,
form views, `database/migration_v16_modul_segera.sql`, `database/REPAIR_1932.sql`

### Gelombang F — 2026-09-12 · Scaffold awal (sebagian besar view & skema)
`database/sikes_db.sql`, `public/css/style.css`, `public/js/app.js`, ±60 view modul kesehatan/pondok,
`app/core/{Router,Controller,Model,Database,helpers}.php`

---

## 3. Klaim scope individu — DRAF (Anda yang mengoreksi)

Isian ini saya **kosongkan sebagian** karena hanya Anda yang tahu. Yang bertanda ✅ punya bukti teknis kuat
di §2; yang bertanda ❓ perlu keputusan Anda.

| # | Fitur yang saya kerjakan sendiri | Bukti | Status |
|---|---|---|---|
| 1 | Chatbot AI dua mode (`chat` + `chatPro`) dengan router intent lokal | Gelombang A, 1.360 LOC | ✅ |
| 2 | Panel Notifikasi realtime (6 aturan, badge, polling 8 detik) | `AiAssistant.php:498`, `ai_widget.php:547` | ✅ |
| 3 | Grounding prompt ke DB + pemotongan konteks per role (role-scoping) | `AiAssistantController.php:97`, `:313` | ✅ |
| 4 | Failover 5 model + fallback non-LLM saat API mati | `AiAssistant.php:448-459`, `:489` | ✅ |
| 5 | UI konfigurasi provider/model/API key (khusus Super Admin) | `AiAssistantController.php:1294-1298` | ✅ |
| 6 | Mesin heuristik: tren period-over-period, umur antrean, ambang obat | `AiAssistant.php:158-236` | ✅ |
| 7 | Heatmap risiko blok & kartu statistik per role di dashboard | `eksekutif/index.php:132-146`, `DashboardController.php:61-232` | ❓ |
| 8 | Skor risiko sanitasi kamar (8 parameter berbobot) | `InspeksiKamar.php:44-60` | ❓ |
| 9 | IMT + kategori Kemenkes + flag kondisi khusus | `SkriningKesehatan.php:43-61` | ❓ |
| 10 | Alur approval kepulangan 4 tahap | `KepulanganController.php:178` | ❓ |
| 11 | Verifikasi QR e-surat publik | `SuratIzinController.php:174` | ❓ |
| 12 | RBAC 10 role / 57 permission | `Auth.php:46-63`, `Controller.php:50` | ❓ |
| 13 | Router/MVC custom tanpa framework | `app/core/Router.php` | ❓ |

> **Cara pakai:** hapus baris ❓ yang bukan karya Anda. Jangan klaim 13 item sekaligus kalau project ini tim —
> penguji biasanya menguji dengan meminta Anda membuka file dan menjelaskan barisnya secara acak.

### Kalimat pembuka presentasi (versi 6 item ✅)

> "Sistem SIKES dibangun tim dan punya 28 modul. Kontribusi individu saya adalah **lapisan kecerdasan**:
> asisten AI di `app/controllers/AiAssistantController.php` dan `app/core/AiAssistant.php` — 3.323 baris kode,
> sekitar 14% project. Isinya bukan sekadar memanggil LLM: ada router intent yang menjawab ±20 pertanyaan
> langsung dari database tanpa API, enam aturan notifikasi operasional, grounding konteks yang dipotong
> sesuai role pengguna, failover lima model, dan fallback saat kuota API habis."

---

## 4. Peta scope → 4 wajib panduan

| Wajib | Dijawab oleh scope individu |
|---|---|
| Use Case / Activity Diagram | Use case "Bertanya ke Asisten AI", "Menerima notifikasi operasional", "Mengonfigurasi model AI" + activity diagram alur hybrid (intent lokal → DB → LLM → fallback) |
| Actor / Role | 10 role; asisten AI punya 2 aktor tambahan: *Groq LLM API* dan *polling browser* |
| Produk AI + MLOps | Gelombang A sepenuhnya, plus risiko & mitigasi (§5) dan roadmap MLOps |
| Coding | Backend PHP 8 murni (tanpa framework, tanpa `composer.json`), domain **Fullstack** |

---

## 5. WAJIB dibereskan sebelum file/repo dibagikan

| Prioritas | Temuan | Lokasi | Aksi |
|---|---|---|---|
| 🔴 1 | **API key Groq asli ter-hardcode** sebagai nilai default | `app/config/ai.php:22` | Kosongkan jadi `env('AI_API_KEY', '')`, lalu **revoke key di console.groq.com** |
| 🔴 2 | Komentar klaim "hanya data AGREGAT, tanpa identitas santri" **tidak sesuai fakta** — konteks yang dikirim ke API memuat nama, NIS, nomor telepon, dan alamat wali | `app/config/ai.php:11-12` vs `AiAssistantController.php:137-180` | Buang kolom identitas dari konteks, atau ubah kalimatnya. Ini isu privasi data kesehatan |
| 🟠 3 | `AI_CHAT_HISTORY_MAX` (12) didefinisikan tapi tidak pernah dibaca → riwayat chat tumbuh tanpa batas | `ai.php:33`, grep: 1 kemunculan | Tambah `array_slice()` saat menyimpan riwayat |
| 🟠 4 | Tidak ada repository git, tidak ada file test, `README.md` root isinya skrip generator hash | root | `git init`, tulis README, buat `.gitignore` untuk `.env` |
| 🟡 5 | Isi request/response AI tidak di-log (hanya event `read/ai` generik) | `helpers.php:51-66` | Tambah log metadata (tokens, latency, model) untuk evaluasi |

---

## 6. Yang tidak ada di project (jawab jujur bila ditanya)

- ❌ Sistem notifikasi berbasis tabel/queue — tidak ada tabel `notifications`; panel notifikasi **dihitung ulang** tiap request
- ❌ Gateway email / SMS / WhatsApp — satu-satunya `curl_init` adalah ke endpoint AI; label "WhatsApp-style" hanya CSS
- ❌ Streaming respons LLM — satu-shot JSON
- ❌ Dataset evaluasi, akurasi, feedback rating, monitoring token/biaya, CI/CD, deteksi drift
- ❌ Rate limiting dan pembatasan biaya
- ❌ `composer.json` / `vendor/`
