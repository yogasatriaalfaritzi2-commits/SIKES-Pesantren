# Diagram UML untuk Figma — SIKES Pondok Pesantren

Folder ini berisi 9 diagram UML (SVG) yang siap diimpor ke Figma, plus generator Python-nya.

## Daftar file

| File | Isi | Ukuran kanvas |
|---|---|---|
| `01-usecase-overview.svg` | Use case overview: 10 aktor + 3 aktor pendukung, 5 paket modul | 2000×1500 |
| `01b-usecase-zoom-ai.svg` | Use case zoom: modul Sistem, RBAC & Asisten AI (include/extend) | 2000×1200 |
| `02-activity-kepulangan.svg` | Activity swimlane: izin kepulangan 4 tahap approval | 2000×1240 |
| `03-activity-asisten-ai.svg` | Activity swimlane: Asisten AI hybrid 3 lapis | 2000×1420 |
| `04-activity-tracing.svg` | Activity swimlane: tracing kontak penyakit menular | 2000×940 |
| `05-activity-esurat-qr.svg` | Activity swimlane: terbit e-surat izin + verifikasi QR | 2000×900 |
| `06-aktor-peran.svg` | Kartu 10 aktor: izin, aksi utama, dan penegakan akses di server | 2000×1120 |
| `07-usecase-notifikasi.svg` | Use case notifikasi: 6 aturan heuristik, polling 8 detik, bukan AI | 2000×1080 |
| `08-alur-keamanan.svg` | Alur satu request: 6 tahap + 6 kontrol keamanan yang berjalan | 2000×1020 |

## Cara impor ke Figma

1. Buka Figma → **File → New design file**.
2. Seret (drag & drop) file `.svg` dari Explorer/Finder ke kanvas Figma.
   - Alternatif: menu **Main menu → File → Place image** (SVG ikut dikenali).
3. Setelah masuk, klik diagram → **klik kanan → Ungroup** (Ctrl/Cmd + Shift + G beberapa kali)
   agar tiap kotak/teks menjadi layer terpisah yang bisa diedit.
4. Teks menjadi layer teks Figma sungguhan (font fallback: Segoe UI / Inter).

### Catatan teknis
- SVG dibuat hanya dengan primitif (`rect`, `ellipse`, `polygon`, `path`, `line`, `text`).
  **Tidak ada** `<marker>`, `<style>`, atau filter — dua hal yang paling sering gagal saat impor SVG ke Figma.
- Panah digambar sebagai `path` + `polygon` (kepala panah), bukan marker, sehingga ujung panah ikut terbawa saat ungroup.
- Font di-set per elemen (bukan CSS class), jadi Figma tidak kehilangan styling.

## Regenerasi diagram

```bash
cd docs/presentasi-individu/figma
python generate_diagrams.py   # 01–05
python generate_extra.py      # 06–08 (memakai Canvas dari generate_diagrams.py)
```

Tanpa dependency eksternal (hanya Python 3 standar library). Script memvalidasi XML tiap file sebelum ditulis dan mencetak jumlah elemen.

Folder `preview/` berisi PNG hasil render tiap SVG (untuk cek cepat tanpa Figma); bukan bagian yang perlu diimpor.

## Kalau ingin mengubah isi

Semua konten ada di `generate_diagrams.py`, satu fungsi per diagram:

- `usecase_overview()` → aktor & paket modul
- `usecase_zoom()` → use case detail + relasi include/extend
- `activity_kepulangan()` / `activity_ai()` / `activity_tracing()` / `activity_surat()` → swimlane

Warna didefinisikan di bagian atas file (`INK`, `LINE`, `BLUE`, `GREEN`, `AMBER`, `RED`, `PURPLE`).
