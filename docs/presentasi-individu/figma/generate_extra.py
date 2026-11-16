#!/usr/bin/env python3
"""Diagram tambahan: aktor & peran, use case notifikasi, alur keamanan request.

Memakai Canvas dan palet dari generate_diagrams.py supaya gayanya identik.
Output: 06-aktor-peran.svg, 07-usecase-notifikasi.svg, 08-alur-keamanan.svg
"""
import os

from generate_diagrams import (
    Canvas, OUT, INK, LINE, SOFT, BLUE, BLUE_D, GREEN, GREEN_D,
    AMBER, AMBER_D, RED, RED_D, PURPLE, PURPLE_D,
)


def mini_actor(c, cx, cy, color=INK):
    c.b.append(f'<circle cx="{cx}" cy="{cy-16}" r="7" fill="none" stroke="{color}" stroke-width="2"/>')
    c.line(cx, cy - 9, cx, cy + 5, color, 2)
    c.line(cx - 10, cy - 3, cx + 10, cy - 3, color, 2)
    c.line(cx, cy + 5, cx - 8, cy + 16, color, 2)
    c.line(cx, cy + 5, cx + 8, cy + 16, color, 2)


# --------------------------------------------------------------------------
# 06 — Aktor & peran
# --------------------------------------------------------------------------
def aktor_peran():
    c = Canvas(2000, 1120, "Aktor & peran")
    c.text(60, 68, "Aktor & peran: siapa boleh melakukan apa", 30, "start", INK, "700")
    c.text(60, 104, "Sepuluh peran internal, 57 permission format modul.aksi, dan penegakan akses di server.",
           15, "start", LINE)

    roles = [
        ("Super Admin", "akses penuh", BLUE_D, BLUE, [
            "is_system = 1, tidak memakai pivot",
            "Kelola pengguna, role, permission",
            "Konfigurasi model AI + audit log",
            "Semua modul, termasuk dashboard eksekutif",
        ]),
        ("Tenaga Medis", "22 izin", GREEN_D, GREEN, [
            "Pemeriksaan, rekam medis, rujukan, BPJS",
            "Obat: stok, pemakaian, kedaluwarsa",
            "Izin sakit dan surat keterangan",
            "Tracing kasus menular + laporan",
        ]),
        ("Pengasuhan", "16 izin", GREEN_D, GREEN, [
            "Data asrama, kamar, dan wali",
            "Piket dan inspeksi sanitasi kamar",
            "Izin kepulangan tahap pengasuhan",
            "Lihat surat dan data santri",
        ]),
        ("Keamanan", "9 izin", AMBER_D, AMBER, [
            "Buku tamu dan pos keamanan",
            "Izin kepulangan tahap gerbang",
            "Lihat piket dan sanitasi",
            "Lihat data santri",
        ]),
        ("Admin Santri", "14 izin", BLUE_D, BLUE, [
            "CRUD santri dan wali santri",
            "BPJS dan penempatan asrama",
            "Ekspor laporan",
            "Lihat surat izin",
        ]),
        ("Dapur / Gizi", "4 izin", AMBER_D, AMBER, [
            "Menu dan jadwal gizi",
            "Distribusi porsi harian",
            "Lihat rekomendasi gizi",
            "Tidak menyentuh data medis",
        ]),
        ("Bendahara", "5 izin", PURPLE_D, PURPLE, [
            "Pemasukan dan pengeluaran",
            "Kelola keuangan pondok",
            "Ekspor laporan keuangan",
            "Lihat stok obat untuk pengadaan",
        ]),
        ("Kyai / Pimpinan", "20 izin", PURPLE_D, PURPLE, [
            "Dashboard eksekutif dan laporan",
            "Lihat semua modul (read-only)",
            "Lihat log aktivitas",
            "Menyetujui izin kepulangan",
        ]),
        ("Santri", "1 izin", LINE, SOFT, [
            "profile.edit (profil sendiri)",
            "Rute lain ditolak 403 di server",
            "Tidak melihat data santri lain",
            "Permintaan izin lewat alur kepulangan",
        ]),
        ("Wali Santri", "1 izin", LINE, SOFT, [
            "profile.edit (profil sendiri)",
            "Rute lain ditolak 403 di server",
            "Tidak melihat rekam medis anak",
            "Verifikasi surat lewat token publik",
        ]),
    ]

    cw, ch, gap = 360, 236, 20
    x0, y0, row_gap = 60, 150, 26
    for i, (name, count, dark, light, actions) in enumerate(roles):
        col, row = i % 5, i // 5
        x = x0 + col * (cw + gap)
        y = y0 + row * (ch + row_gap)
        c.rect(x, y, cw, ch, "#FFFFFF", LINE, 2, 12)
        c.rect(x, y, cw, 46, light, LINE, 2, 12)
        c.rect(x, y + 34, cw, 12, light, light, 0)
        mini_actor(c, x + 30, y + 26, dark)
        c.text(x + 56, y + 24, name, 15, "start", dark, "700")
        c.text(x + 56, y + 40, count, 11, "start", LINE)
        ly = y + 76
        for a in actions:
            c.b.append(f'<circle cx="{x + 16}" cy="{ly - 5}" r="2.5" fill="{dark}"/>')
            c.text(x + 28, ly, a, 12.5, "start", INK)
            ly += 32

    # Penegakan akses
    c.rect(60, 730, 1880, 320, "#FFFFFF", LINE, 2, 12)
    c.text(84, 766, "PENEGAKAN DI SERVER, BUKAN DI TOMBOL UI", 15, "start", BLUE_D, "700")
    c.text(84, 792, "Urutan yang sama dipakai semua rute; tombol yang disembunyikan hanya kosmetik.",
           13, "start", LINE)

    steps = [
        ("Router", "routes.php\nmiddleware per rute:\nauth / permission"),
        ("Controller", "requirePermission()\nmenolak sebelum query\nberjalan → 403"),
        ("Model", "query hanya jalan\nsetelah izin lolos;\nPDO prepared statement"),
        ("Audit", "setiap aksi penting\nmasuk activity_logs\n(76 pemanggilan)"),
        ("Session", "permission dimuat\nulang tiap request;\nrole berubah → segera"),
    ]
    bw, bh, bgap = 344, 108, 22
    for i, (title, body) in enumerate(steps):
        x = 84 + i * (bw + bgap)
        c.rect(x, 820, bw, bh, SOFT, LINE, 2, 8)
        c.text(x + bw / 2, 848, title, 14, "middle", INK, "700")
        for j, ln in enumerate(body.split("\n")):
            c.text(x + bw / 2, 870 + j * 17, ln, 11.5, "middle", LINE)
        if i < len(steps) - 1:
            c.arrow(x + bw + 2, 874, x + bw + bgap - 2, 874, BLUE_D, 2)

    c.text(84, 962, "Bukti kode: app/config/routes.php · app/core/Router.php · app/core/Auth.php · "
                    "app/core/Controller.php · app/core/Database.php",
           11.5, "start", LINE)
    c.save("06-aktor-peran.svg")


# --------------------------------------------------------------------------
# 07 — Use case notifikasi
# --------------------------------------------------------------------------
def usecase_notifikasi():
    c = Canvas(2000, 1080, "Use case notifikasi")
    c.text(60, 68, "Use case: notifikasi dihitung ulang dari data", 30, "start", INK, "700")
    c.text(60, 104, "Enam aturan ambang dijalankan saat widget meminta; tidak ada tabel notifikasi, "
                    "email, SMS, atau WhatsApp.", 15, "start", LINE)

    # Aktor kiri
    actors = ["Tenaga Medis", "Pengasuhan", "Keamanan", "Kyai / Pimpinan"]
    for i, name in enumerate(actors):
        cy = 300 + i * 170
        c.actor(170, cy, name, None, INK, 14)
        c.line(208, cy - 4, 430, cy - 4, LINE, 2)

    # Batas sistem
    c.rect(430, 160, 940, 780, "#FFFFFF", BLUE_D, 2.5, 10)
    c.text(456, 192, "SIKES — panel asisten & notifikasi", 16, "start", BLUE_D, "700")

    c.usecase(700, 280, "Lihat badge\nnotifikasi", 120, 40)
    c.usecase(1070, 280, "Buka panel\nperingatan", 120, 40)
    c.usecase(885, 390, "Ikuti tautan\ntindak lanjut", 120, 38)
    c.arrow(700, 320, 790, 390, LINE, 1.6)
    c.arrow(1010, 320, 950, 390, LINE, 1.6)

    # Paket aturan
    c.package(470, 470, 860, 430, "6 aturan heuristik (dihitung saat diminta)")
    rules = [
        ("Kunjungan menunggu", "pemeriksaan.status = 'menunggu'\nhitung + umur antrean"),
        ("Kontak belum diperiksa", "tracing_kontak.status =\n'belum_diperiksa'"),
        ("Stok obat menipis", "stok di bawah minimum\nper item obat"),
        ("Obat kedaluwarsa", "tanggal_expired < hari ini\nlevel: danger"),
        ("Mendekati kedaluwarsa", "sisa umur <= 90 hari\nprioritas FEFO"),
        ("Sanitasi risiko tinggi", "inspeksi terakhir per kamar\nstatus risiko_tinggi"),
    ]
    bw, bh = 386, 112
    for i, (title, body) in enumerate(rules):
        col, row = i % 2, i // 2
        x = 490 + col * (bw + 24)
        y = 512 + row * (bh + 16)
        c.rect(x, y, bw, bh, SOFT, LINE, 2, 8)
        c.text(x + 16, y + 28, title, 13.5, "start", INK, "700")
        for j, ln in enumerate(body.split("\n")):
            c.text(x + 16, y + 50 + j * 17, ln, 11.5, "start", LINE)

    c.arrow(1010, 326, 990, 466, LINE, 2, dash="6 4")
    c.text(1038, 404, "«include»", 11, "start", LINE)
    c.text(1038, 420, "hitung ulang", 11, "start", LINE)

    # Panel kanan
    px = 1410
    c.rect(px, 170, 520, 186, "#FFFFFF", GREEN_D, 2, 10)
    c.text(px + 20, 202, "CARA KERJA WIDGET", 13, "start", GREEN_D, "700")
    for j, ln in enumerate([
        "GET /ai/notifications dipanggil tiap 8 detik",
        "Selama halaman asisten terbuka",
        "Respons JSON: level, judul, detail, url",
        "Badge = jumlah aturan yang menyala",
    ]):
        c.text(px + 20, 230 + j * 24, "·  " + ln, 12, "start", INK)
    c.text(px + 20, 336, "ai_widget.php:928 dan :985-987", 11, "start", LINE)

    c.rect(px, 380, 520, 214, "#FFFFFF", AMBER_D, 2, 10)
    c.text(px + 20, 412, "POSISI TERHADAP SYARAT AI", 13, "start", AMBER_D, "700")
    for j, ln in enumerate([
        "Notifikasi bukan produk AI: ini aturan ambang",
        "Tidak diklaim sebagai AI pada presentasi",
        "Klaim AI ada di modul asisten (3 lapis)",
        "Tanpa antrean pesan, tanpa tabel notifikasi",
        "Tidak mengirim email, SMS, atau WhatsApp",
    ]):
        c.text(px + 20, 440 + j * 26, "·  " + ln, 12, "start", INK)

    c.rect(px, 618, 520, 250, "#FFFFFF", RED_D, 2, 10)
    c.text(px + 20, 650, "CONTOH DATA HARI INI", 13, "start", RED_D, "700")
    for j, ln in enumerate([
        "5 peringatan aktif di database demo",
        "2 obat stok menipis, 1 kedaluwarsa",
        "1 obat mendekati kedaluwarsa (<=90 hari)",
        "1 kamar sanitasi risiko tinggi",
        "Angka berubah mengikuti data, bukan hardcode",
    ]):
        c.text(px + 20, 678 + j * 26, "·  " + ln, 12, "start", INK)

    c.text(60, 1006, "Bukti kode: AiAssistant::analyze/getSystemWarnings · AiAssistantController::notifications · "
                     "models/Obat.php · models/InspeksiKamar.php", 11.5, "start", LINE)
    c.save("07-usecase-notifikasi.svg")


# --------------------------------------------------------------------------
# 08 — Alur request & kontrol keamanan
# --------------------------------------------------------------------------
def alur_keamanan():
    c = Canvas(2000, 1020, "Alur keamanan request")
    c.text(60, 68, "Coding web: satu request, enam tahap, enam kontrol", 30, "start", INK, "700")
    c.text(60, 104, "Tanpa framework, jadi routing, sesi, CSRF, dan query aman ditulis sendiri di app/core.",
           15, "start", LINE)

    boxes = [
        ("Browser", "form + token CSRF", BLUE, BLUE_D),
        ("public/index.php", "bootstrap config\n+ session start", SOFT, LINE),
        ("Router", "middleware per rute\nauth / permission", GREEN, GREEN_D),
        ("Controller", "requirePermission()\ncheckCsrf() + validasi", GREEN, GREEN_D),
        ("Model + PDO", "prepared statement\nemulasi prepare OFF", PURPLE, PURPLE_D),
        ("MySQL", "32 tabel\nactivity_logs", AMBER, AMBER_D),
    ]
    bw, bh, gap = 286, 150, 26
    y = 330
    for i, (title, body, fill, dark) in enumerate(boxes):
        x = 60 + i * (bw + gap)
        c.rect(x, y, bw, bh, fill, dark, 2, 10)
        c.text(x + bw / 2, y + 34, title, 14.5, "middle", dark, "700")
        for j, ln in enumerate(body.split("\n")):
            c.text(x + bw / 2, y + 66 + j * 22, ln, 12, "middle", INK)
        if i < len(boxes) - 1:
            c.arrow(x + bw + 3, y + bh / 2, x + bw + gap - 4, y + bh / 2, LINE, 2)

    # Sesi di atas
    c.rect(60, 140, 960, 140, "#FFFFFF", BLUE_D, 2, 10)
    c.text(80, 172, "SESI & IDENTITAS", 13, "start", BLUE_D, "700")
    for j, ln in enumerate([
        "Cookie httponly + samesite, ID sesi diperbarui saat login (Session.php:193)",
        "Password bcrypt password_hash(); login dibatasi 5 percobaan / 300 detik (Session.php:13-14)",
    ]):
        c.text(80, 198 + j * 24, "·  " + ln, 12, "start", INK)
    c.arrow(540, 280, 540, 326, BLUE_D, 2, dash="6 4")

    c.rect(1040, 140, 900, 140, "#FFFFFF", GREEN_D, 2, 10)
    c.text(1060, 172, "OTORISASI", 13, "start", GREEN_D, "700")
    for j, ln in enumerate([
        "Permission dicek di Router dan diulang di Controller",
        "Ditolak → 403 sebelum query berjalan; UI hanya pelengkap",
    ]):
        c.text(1060, 198 + j * 24, "·  " + ln, 12, "start", INK)

    # Band kontrol
    c.rect(60, 540, 1880, 300, "#FFFFFF", LINE, 2, 12)
    c.text(84, 576, "KONTROL YANG SUDAH BERJALAN", 14, "start", GREEN_D, "700")
    controls = [
        ("Password", "bcrypt\npassword_hash / verify"),
        ("CSRF", "token per form\nhash_equals()"),
        ("SQL", "PDO prepared\nemulate = false"),
        ("Cookie", "httponly, samesite\nregenerate saat login"),
        ("Rate limit", "5 gagal / 300 detik\nper username"),
        ("Audit", "activity_logs\n76 pemanggilan"),
    ]
    cw2, ch2, g2 = 296, 108, 16
    for i, (title, body) in enumerate(controls):
        x = 84 + i * (cw2 + g2)
        c.rect(x, 596, cw2, ch2, SOFT, LINE, 2, 8)
        c.text(x + cw2 / 2, 624, title, 13, "middle", INK, "700")
        for j, ln in enumerate(body.split("\n")):
            c.text(x + cw2 / 2, 648 + j * 18, ln, 11.5, "middle", LINE)

    c.rect(84, 726, 916, 96, RED, RED_D, 2, 8)
    c.text(104, 754, "BELUM SELESAI — sebutkan, jangan diklaim beres", 12.5, "start", RED_D, "700")
    for j, ln in enumerate([
        "Belum ada rate limit khusus endpoint AI dan belum ada tes otomatis",
        "Kunci API masih punya nilai default di config; cabut dan baca dari .env sebelum repo dibagikan",
    ]):
        c.text(104, 778 + j * 20, "·  " + ln, 11.5, "start", INK)

    c.rect(1024, 726, 916, 96, AMBER, AMBER_D, 2, 8)
    c.text(1044, 754, "YANG BISA DITUNJUKKAN SAAT DEMO", 12.5, "start", AMBER_D, "700")
    for j, ln in enumerate([
        "Login sebagai santri lalu buka /tracing → halaman 403, bukan tombol tersembunyi",
        "Setiap login dan aksi penting muncul di activity_logs beserta pengguna dan waktunya",
    ]):
        c.text(1044, 778 + j * 20, "·  " + ln, 11.5, "start", INK)

    c.text(60, 966, "Bukti kode: app/core/Router.php · Auth.php · Database.php:26 · Session.php · helpers.php · "
                    "app/config/routes.php", 11.5, "start", LINE)
    c.save("08-alur-keamanan.svg")


if __name__ == "__main__":
    print("Generating diagram tambahan ke:", OUT)
    aktor_peran()
    usecase_notifikasi()
    alur_keamanan()
    print("Selesai. Semua file lolos validasi XML well-formed.")
