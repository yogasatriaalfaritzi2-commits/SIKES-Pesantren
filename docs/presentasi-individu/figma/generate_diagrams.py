#!/usr/bin/env python3
"""Generator diagram UML (use case + activity) SIKES Pondok Pesantren.

Output: .svg siap import ke Figma (drag & drop).
Jalankan:  python generate_diagrams.py

Semua bentuk primitif (rect/ellipse/polygon/text), tanpa <marker>/<style>
karena Figma mengabaikannya saat impor SVG.
"""
import math
import os
import xml.etree.ElementTree as ET

OUT = os.path.dirname(os.path.abspath(__file__))
FONT = "Inter, Arial, Helvetica, sans-serif"

INK = "#1F2933"
LINE = "#52606D"
SOFT = "#F5F7FA"
BLUE = "#E4EAF2"
BLUE_D = "#3E5C76"
GREEN = "#E3F0E6"
GREEN_D = "#2F6B45"
AMBER = "#FBF0DC"
AMBER_D = "#8A5B13"
RED = "#F7E4E4"
RED_D = "#8C2F2F"
PURPLE = "#EDE7F6"
PURPLE_D = "#4B3A72"


def esc(s):
    return (str(s).replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;"))


class Canvas:
    def __init__(self, w, h, title):
        self.w, self.h, self.title = w, h, title
        self.b = []

    def rect(self, x, y, w, h, fill=SOFT, stroke=LINE, sw=2, rx=0, dash=None):
        d = f' stroke-dasharray="{dash}"' if dash else ""
        self.b.append(
            f'<rect x="{x:.1f}" y="{y:.1f}" width="{w:.1f}" height="{h:.1f}" rx="{rx}" '
            f'fill="{fill}" stroke="{stroke}" stroke-width="{sw}"{d}/>')

    def ellipse(self, cx, cy, rx, ry, fill=BLUE, stroke=BLUE_D, sw=2):
        self.b.append(
            f'<ellipse cx="{cx:.1f}" cy="{cy:.1f}" rx="{rx:.1f}" ry="{ry:.1f}" '
            f'fill="{fill}" stroke="{stroke}" stroke-width="{sw}"/>')

    def diamond(self, cx, cy, w, h, fill=AMBER, stroke=AMBER_D, sw=2):
        pts = f"{cx},{cy-h/2} {cx+w/2},{cy} {cx},{cy+h/2} {cx-w/2},{cy}"
        self.b.append(f'<polygon points="{pts}" fill="{fill}" stroke="{stroke}" stroke-width="{sw}"/>')

    def text(self, x, y, s, size=17, anchor="middle", fill=INK, weight="normal"):
        self.b.append(
            f'<text x="{x:.1f}" y="{y:.1f}" font-family="{FONT}" font-size="{size}" '
            f'font-weight="{weight}" fill="{fill}" text-anchor="{anchor}">{esc(s)}</text>')

    def line(self, x1, y1, x2, y2, stroke=LINE, sw=2, dash=None):
        d = f' stroke-dasharray="{dash}"' if dash else ""
        self.b.append(
            f'<line x1="{x1:.1f}" y1="{y1:.1f}" x2="{x2:.1f}" y2="{y2:.1f}" '
            f'stroke="{stroke}" stroke-width="{sw}"{d}/>')

    def arrow(self, x1, y1, x2, y2, stroke=LINE, sw=2, dash=None, label=None,
              label_dx=0, label_dy=-9, label_size=13, label_fill=LINE):
        self.line(x1, y1, x2, y2, stroke, sw, dash)
        ang = math.atan2(y2 - y1, x2 - x1)
        L, W = 12, 7
        pts = (f"{x2:.1f},{y2:.1f} "
               f"{x2 - L*math.cos(ang) + W*math.sin(ang):.1f},{y2 - L*math.sin(ang) - W*math.cos(ang):.1f} "
               f"{x2 - L*math.cos(ang) - W*math.sin(ang):.1f},{y2 - L*math.sin(ang) + W*math.cos(ang):.1f}")
        self.b.append(f'<polygon points="{pts}" fill="{stroke}" stroke="none"/>')
        if label:
            self.text((x1 + x2) / 2 + label_dx, (y1 + y2) / 2 + label_dy, label,
                      label_size, "middle", label_fill)

    def polyline(self, pts, stroke=LINE, sw=2, dash=None, label=None, label_at=None):
        for i in range(len(pts) - 1):
            self.line(pts[i][0], pts[i][1], pts[i + 1][0], pts[i + 1][1], stroke, sw, dash)
        x1, y1 = pts[-2]
        x2, y2 = pts[-1]
        ang = math.atan2(y2 - y1, x2 - x1)
        L, W = 12, 7
        p = (f"{x2:.1f},{y2:.1f} "
             f"{x2 - L*math.cos(ang) + W*math.sin(ang):.1f},{y2 - L*math.sin(ang) - W*math.cos(ang):.1f} "
             f"{x2 - L*math.cos(ang) - W*math.sin(ang):.1f},{y2 - L*math.sin(ang) + W*math.cos(ang):.1f}")
        self.b.append(f'<polygon points="{p}" fill="{stroke}" stroke="none"/>')
        if label and label_at:
            self.text(label_at[0], label_at[1], label, 13, "middle", stroke)

    def actor(self, cx, cy, label, sub=None, stroke=INK, size=15):
        self.b.append(f'<circle cx="{cx}" cy="{cy-30:.1f}" r="11" fill="none" '
                      f'stroke="{stroke}" stroke-width="2.5"/>')
        self.line(cx, cy - 19, cx, cy + 6, stroke, 2.5)
        self.line(cx - 14, cy - 10, cx + 14, cy - 10, stroke, 2.5)
        self.line(cx, cy + 6, cx - 12, cy + 24, stroke, 2.5)
        self.line(cx, cy + 6, cx + 12, cy + 24, stroke, 2.5)
        self.text(cx, cy + 42, label, size, "middle", stroke, "600")
        if sub:
            self.text(cx, cy + 60, sub, 12, "middle", LINE)

    def dot(self, cx, cy, final=False):
        self.b.append(f'<circle cx="{cx}" cy="{cy}" r="13" fill="{INK}"/>')
        if final:
            self.b.append(f'<circle cx="{cx}" cy="{cy}" r="19" fill="none" stroke="{INK}" stroke-width="3"/>')

    def package(self, x, y, w, h, label, stroke=BLUE_D, fill="#FFFFFF"):
        tab_w = min(w - 8, 34 + len(label) * 8.4)
        self.rect(x, y, tab_w, 26, fill, stroke, 2)
        self.text(x + tab_w / 2, y + 18, label, 14, "middle", stroke, "600")
        self.rect(x, y + 26, w, h - 26, fill, stroke, 2)

    def usecase(self, cx, cy, label, rx=104, ry=28, fill=BLUE, stroke=BLUE_D, size=14):
        self.ellipse(cx, cy, rx, ry, fill, stroke, 2)
        lines = label.split("\n")
        for i, ln in enumerate(lines):
            self.text(cx, cy + 5 + (i - (len(lines) - 1) / 2) * 16, ln, size, "middle", INK, "500")

    def action(self, x, y, w, h, label, fill=SOFT, stroke=LINE, size=15, rx=14):
        self.rect(x, y, w, h, fill, stroke, 2, rx)
        lines = label.split("\n")
        for i, ln in enumerate(lines):
            self.text(x + w / 2, y + h / 2 + 5.5 + (i - (len(lines) - 1) / 2) * 18, ln,
                      size, "middle", INK, "500")

    def note(self, x, y, w, h, label, fill=AMBER, stroke=AMBER_D, size=13):
        self.rect(x, y, w, h, fill, stroke, 1.5, 4)
        for i, ln in enumerate(label.split("\n")):
            self.text(x + 12, y + 22 + i * 18, ln, size, "start", stroke)

    def lane(self, x, y, w, h, name, stroke=LINE, fill="#FFFFFF"):
        self.rect(x, y, w, h, fill, stroke, 2)
        self.rect(x, y, 150, h, "#E9EDF1", stroke, 2)
        lines = name.split("\n")
        for i, ln in enumerate(lines):
            self.text(x + 75, y + h / 2 + (i - (len(lines) - 1) / 2) * 20 + 5, ln,
                      14, "middle", stroke, "700")

    def save(self, name):
        head = (f'<svg xmlns="http://www.w3.org/2000/svg" width="{self.w}" height="{self.h}" '
                f'viewBox="0 0 {self.w} {self.h}">')
        xml = (f'{head}\n<rect x="0" y="0" width="{self.w}" height="{self.h}" fill="#FFFFFF"/>\n'
               f'{"\n".join(self.b)}\n</svg>\n')
        ET.fromstring(xml)
        with open(os.path.join(OUT, name), "w", encoding="utf-8") as f:
            f.write(xml)
        print(f"  {name:34s} {self.w}x{self.h}  {len(self.b):4d} elemen")


# ============================================================================
# 01 — USE CASE OVERVIEW
# ============================================================================
def usecase_overview():
    c = Canvas(2000, 1500, "usecase")
    c.text(1000, 44, "SIKES PONDOK PESANTREN — USE CASE DIAGRAM (OVERVIEW)", 30, "middle", BLUE_D, "700")
    c.text(1000, 74, "10 aktor + 3 aktor pendukung · 5 paket modul · 176 rute · 57 permission · 32 tabel",
           17, "middle", LINE)

    c.rect(430, 100, 1140, 1120, "#FFFFFF", BLUE_D, 2.5)
    c.text(1000, 128, "System: SIKES Pondok Pesantren (PHP 8 MVC, tanpa framework)", 18, "middle", BLUE_D, "700")

    pk = {
        "p1": (460, 150, 1080, 150, "Paket 1: Master Data"),
        "p2": (460, 320, 1080, 230, "Paket 2: Kesehatan (Poskestren)"),
        "p3": (460, 570, 1080, 230, "Paket 3: Pondok & Operasional"),
        "p4": (460, 820, 1080, 150, "Paket 4: Keuangan & Laporan"),
        "p5": (460, 990, 1080, 210, "Paket 5: Sistem, RBAC & Asisten AI"),
    }
    for x, y, w, h, t in pk.values():
        c.package(x, y, w, h, t)

    rows = {
        "p1": [(600, 250, "Kelola Data\nSantri"), (870, 250, "Kelola Data\nWali Santri"),
               (1150, 250, "Kelola Asrama /\nBlok / Kamar"), (1420, 250, "Kelola Pengguna\n& Peran")],
        "p2": [(600, 420, "Skrining\nKesehatan"), (870, 420, "Pemeriksaan\nSantri Sakit"),
               (1150, 420, "Rekam Medis\n(read-only)"), (1420, 420, "Tracing Kontak\nMenular")],
        "p3": [(600, 670, "Distribusi\nGizi / Diet"), (870, 670, "Izin Kepulangan\n4 Tahap"),
               (1150, 670, "Buku Tamu &\nJadwal Piket"), (1420, 670, "Inspeksi\nSanitasi")],
        "p4": [(700, 920, "Catat & Verifikasi\nTransaksi"), (1050, 920, "Cetak Laporan\nRekap"),
               (1390, 920, "Dashboard Eksekutif\n+ Heatmap")],
        "p5": [(600, 1090, "Login\n(throttle 5x)"), (870, 1090, "Kelola Role &\nPermission"),
               (1150, 1090, "Audit Trail\nAktivitas"), (1420, 1090, "Tanya Asisten AI\n& Notifikasi")],
    }
    for items in rows.values():
        for x, y, t in items:
            c.usecase(x, y, t, rx=110, ry=32)
    for x, y, t in [(760, 500, "Rujukan & BPJS"), (1080, 500, "E-Surat Izin + QR"),
                    (1390, 500, "Kelola Obat & Stok")]:
        c.usecase(x, y, t, rx=120, ry=28)
    for x, y, t in [(760, 750, "Catatan Pengasuhan"), (1080, 750, "Verifikasi Gerbang"),
                    (1390, 750, "Kepulangan Riwayat")]:
        c.usecase(x, y, t, rx=120, ry=28)

    for x, y, n in [(170, 220, "Admin Santri"), (170, 430, "Tenaga Medis"), (170, 640, "Pengasuhan"),
                    (170, 830, "Keamanan"), (170, 1010, "Dapur / Gizi"), (170, 1180, "Super Admin")]:
        c.actor(x, y, n)
    for item in [(1830, 220, "Bendahara"), (1830, 430, "Kyai / Pimpinan"), (1830, 640, "Santri"),
                 (1830, 830, "Wali Santri"), (1830, 1020, "Groq LLM API", True),
                 (1830, 1180, "MySQL", True)]:
        c.actor(item[0], item[1], item[2], "«system»" if len(item) > 3 else None)

    for ax, ay, targets in [(170, 220, ["p1", "p5"]), (170, 430, ["p2", "p3", "p5"]),
                            (170, 640, ["p2", "p3"]), (170, 830, ["p3", "p5"]),
                            (170, 1010, ["p3"]), (170, 1180, ["p1", "p5"])]:
        for t in targets:
            x, y, w, h, _ = pk[t]
            ty = ay if y + 40 < ay < y + h else (y + h / 2)
            c.line(ax + 22, ay, x, ty)
    for ax, ay, targets in [(1830, 220, ["p4"]), (1830, 430, ["p4", "p5"]),
                            (1830, 640, ["p2", "p3"]), (1830, 830, ["p2", "p5"])]:
        for t in targets:
            x, y, w, h, _ = pk[t]
            ty = ay if y + 40 < ay < y + h else (y + h / 2)
            c.line(ax - 22, ay, x + w, ty)
    for ax, ay in [(1830, 1020), (1830, 1180)]:
        x, y, w, h, _ = pk["p5"]
        c.line(ax - 22, ay, x + w, y + h / 2, dash="7 5")
    c.text(1690, 1000, "«invoke» LLM", 13, "middle", PURPLE_D)
    c.text(1690, 1160, "«query»", 13, "middle", PURPLE_D)

    c.note(40, 1300, 900, 150,
           "CARA BACA\n"
           "·  Garis lurus = asosiasi aktor ke paket (diperpendek agar terbaca; rinciannya di 01b-zoom)\n"
           "·  Paket 5 = scope kontribusi individu (asisten AI, notifikasi, RBAC, audit)\n"
           "·  Groq LLM API & MySQL = aktor pendukung (secondary actor), bukan pengguna manusia\n"
           "·  Wali/Santri memakai sistem lewat surat & gerbang, bukan lewat menu admin")
    c.note(980, 1300, 900, 150,
           "PETA AKTOR -> PERMISSION (contoh)\n"
           "·  tenaga-medis  : periksa.*, skrining.view, tracing.manage, obat.view\n"
           "·  keamanan      : tamu.manage, piket.manage, kepulangan.gerbang\n"
           "·  pengasuhan    : pengasuhan.*, kepulangan.pengasuhan, sanitasi.manage\n"
           "·  bendahara     : keuangan.manage (verifikasi transaksi)\n"
           "·  kyai          : laporan.view, eksekutif.view  ·  super-admin : bypass semua")
    c.save("01-usecase-overview.svg")


# ============================================================================
# 01b — ZOOM PAKET 5 (scope individu)
# ============================================================================
def usecase_zoom():
    c = Canvas(2000, 1200, "usecase-zoom")
    c.text(1000, 44, "USE CASE ZOOM — PAKET 5: SISTEM, RBAC & ASISTEN AI", 30, "middle", BLUE_D, "700")
    c.text(1000, 74, "Paket yang saya kerjakan sendiri — lengkap dengan include/extend dan aktor teknis",
           17, "middle", LINE)

    c.rect(430, 110, 1140, 780, "#FFFFFF", BLUE_D, 2.5)
    c.text(1000, 140, "System: SIKES — Modul Asisten AI (3.323 LOC)", 18, "middle", BLUE_D, "700")

    ucs = {
        "login": (600, 230, "Login\n(throttle 5x/300s)"),
        "rbac": (900, 230, "Kelola Role &\nPermission"),
        "audit": (1200, 230, "Baca Audit\nTrail"),
        "cfg": (1450, 230, "Konfigurasi\nModel AI"),
        "ai": (600, 430, "Tanya Asisten AI\n(mode chat)"),
        "aipro": (900, 430, "Tanya Asisten AI\n(mode chatPro)"),
        "notif": (1200, 430, "Terima Notifikasi\nOperasional"),
        "dash": (1450, 430, "Pantau Dashboard\nEksekutif"),
        "clear": (760, 620, "Bersihkan\nRiwayat Chat"),
        "scope": (1080, 620, "Potong Konteks\nsesuai Role"),
        "warn": (1380, 620, "Hitung Ulang\n6 Aturan"),
        "surat": (600, 790, "Verifikasi QR\nSurat (Publik)"),
        "laporan": (950, 790, "Buka Halaman\n/ai"),
        "export": (1320, 790, "Cetak Laporan\nEksekutif"),
    }
    for x, y, t in ucs.values():
        c.usecase(x, y, t, rx=112, ry=32)

    for x, y, n in [(170, 230, "Super Admin"), (170, 430, "Tenaga Medis"),
                    (170, 620, "Pengasuhan"), (170, 790, "Admin Santri")]:
        c.actor(x, y, n)
    for item in [(1830, 230, "Kyai / Pimpinan"), (1830, 430, "Bendahara"),
                 (1830, 620, "Groq LLM API", "«system»"), (1830, 790, "Browser", "polling 8 detik")]:
        c.actor(item[0], item[1], item[2], item[3] if len(item) > 3 else None)

    for ax, ay, targets in [(170, 230, ["login", "rbac", "cfg", "audit"]),
                            (170, 430, ["login", "ai", "notif", "dash"]),
                            (170, 620, ["login", "ai", "notif"]),
                            (170, 790, ["login", "surat", "rbac"])]:
        for t in targets:
            x, y, _ = ucs[t]
            c.line(ax + 22, ay, x - 112, y)
    for ax, ay, targets in [(1830, 230, ["dash"]),
                            (1830, 430, ["export"])]:
        for t in targets:
            x, y, _ = ucs[t]
            c.line(ax - 22, ay, x + 112, y)

    c.arrow(600, 462, 760, 588, dash="7 5", label="«include»", label_dx=-58, label_fill=PURPLE_D)
    c.arrow(900, 462, 1080, 588, dash="7 5", label="«include» role-scoping",
            label_dx=90, label_fill=PURPLE_D)
    c.arrow(1200, 462, 1380, 588, dash="7 5", label="«include»", label_dx=58, label_fill=PURPLE_D)
    c.arrow(1450, 398, 1200, 398, dash="7 5", label="«extend» pantau",
            label_dy=-12, label_fill=PURPLE_D)
    c.arrow(1450, 262, 1450, 398, dash="7 5", label="«include» hanya Super Admin",
            label_dx=180, label_fill=PURPLE_D)
    c.arrow(900, 398, 600, 398, dash="7 5", label="«extend» chatPro", label_dy=-12, label_fill=PURPLE_D)
    c.arrow(1808, 620, 1012, 430, dash="7 5", label="«invoke»", label_dx=-150, label_fill=PURPLE_D)
    c.arrow(1808, 790, 1312, 430, dash="7 5", label="«poll» 8s", label_dx=-30, label_fill=PURPLE_D)

    c.note(40, 950, 900, 200,
           "CATATAN USE CASE INI\n"
           "·  'Tanya Asisten AI' punya 2 mode: chat (intent lokal, hemat kuota) dan chatPro (analitik luas)\n"
           "·  'Potong Konteks sesuai Role' = use case wajib (include) sebelum data masuk prompt\n"
           "·  'Terima Notifikasi' bukan push — dihitung ulang tiap polling, jadi tidak ada tabel notifikasi\n"
           "·  'Verifikasi QR Surat' satu-satunya use case publik (tanpa login, permission null)\n"
           "·  'Konfigurasi Model AI' sengaja dikunci ke satu aktor (Super Admin) di controller, bukan di UI")
    c.note(980, 950, 900, 200,
           "ALTERNATE FLOW (yang sering ditanya penguji)\n"
           "·  AI_ENABLED=false → use case 'Tanya Asisten AI' tetap jalan via intent lokal\n"
           "·  API 401/429 → rantai failover 5 model → habis → pesan ramah + ringkasan mesin lokal\n"
           "·  Role tidak punya hak → konteks kosong + instruksi tolak di prompt (bukan filter tombol)\n"
           "·  CSRF token salah → 403 sebelum query apa pun jalan\n"
           "·  Riwayat chat per session: logout = riwayat ikut hilang (belum ada persistensi DB)")
    c.save("01b-usecase-zoom-ai.svg")


# ============================================================================
# 02 — ACTIVITY: IZIN KEPULANGAN 4 TAHAP
# ============================================================================
def activity_kepulangan():
    c = Canvas(2000, 1240, "kepulangan")
    c.text(1000, 44, "ACTIVITY DIAGRAM — IZIN KEPULANGAN SANTRI (4 TAHAP APPROVAL)", 28, "middle", BLUE_D, "700")
    c.text(1000, 72,
           "KepulanganController.php — rekomendasi medis :77 → keamanan :133 → pengasuhan :141 → gerbang :149 · mesin tunggal processApproval() :178",
           15, "middle", LINE)

    for name, y, fill, stroke in [("Santri /\nWali", 100, GREEN, GREEN_D), ("Tenaga\nMedis", 280, BLUE, BLUE_D),
                                  ("Keamanan", 460, AMBER, AMBER_D), ("Pengasuhan /\nKyai", 640, PURPLE, PURPLE_D),
                                  ("Gerbang", 820, RED, RED_D)]:
        c.rect(100, y, 1800, 180, fill, stroke, 2)
        c.rect(100, y, 150, 180, "#E9EDF1", stroke, 2)
        lines = name.split("\n")
        for i, ln in enumerate(lines):
            c.text(175, y + 90 + (i - (len(lines) - 1) / 2) * 20 + 5, ln, 14, "middle", stroke, "700")

    c.dot(320, 190)
    c.action(380, 162, 220, 56, "Ajukan izin\npulang", GREEN, GREEN_D)
    c.action(380, 342, 240, 56, "Periksa kondisi\n& kelayakan", BLUE, BLUE_D)
    c.diamond(760, 370, 170, 88)
    c.text(760, 375, "Layak?", 14, "middle", AMBER_D, "700")
    c.action(920, 522, 240, 56, "Approval\nKeamanan", AMBER, AMBER_D)
    c.diamond(1340, 550, 170, 88)
    c.text(1340, 555, "Setuju?", 14, "middle", AMBER_D, "700")
    c.action(920, 702, 240, 56, "Approval Pengasuhan\n/ Kyai", PURPLE, PURPLE_D)
    c.diamond(1340, 730, 170, 88)
    c.text(1340, 735, "Setuju?", 14, "middle", AMBER_D, "700")
    c.action(920, 882, 240, 56, "Izin Gerbang:\nsantri keluar fisik", RED, RED_D)
    c.action(1240, 882, 220, 56, "Status SELESAI\n+ riwayat", SOFT, LINE)
    c.action(1500, 342, 220, 56, "Status DITOLAK\n(catatan wajib)", RED, RED_D)
    c.action(1600, 702, 220, 56, "Belum boleh\nkeluar gerbang", RED, RED_D)

    c.arrow(333, 190, 380, 190)
    c.arrow(490, 218, 490, 342)
    c.arrow(620, 370, 675, 370)
    c.arrow(845, 370, 1500, 370, label="Tidak", label_dx=-40)
    c.arrow(760, 414, 1040, 522, label="Ya", label_dx=26)
    c.arrow(1160, 550, 1255, 550)
    c.polyline([(1425, 550), (1610, 550), (1610, 398)], label="Tidak", label_at=(1470, 542))
    c.arrow(1340, 594, 1040, 702, label="Ya", label_dx=-26, label_dy=18)
    c.arrow(1160, 730, 1255, 730)
    c.arrow(1425, 730, 1600, 730, label="Tidak", label_dx=-30)
    c.arrow(1340, 774, 1040, 882, label="Ya", label_dx=-20, label_dy=18)
    c.arrow(1160, 910, 1240, 910)

    c.note(100, 1030, 880, 170,
           "CATATAN TEKNIS\n"
           "·  Satu mesin approval untuk semua tahap: processApproval(id, stage, keputusan, catatan)\n"
           "·  Status: menunggu / disetujui / ditolak / selesai — tiap keputusan masuk tabel riwayat\n"
           "·  Tahap berikutnya baru terbuka setelah tahap sebelumnya disetujui (berjenjang per role)\n"
           "·  Penolakan di tahap mana pun = akhir alur; santri tidak bisa melewati gerbang")
    c.note(1020, 1030, 880, 170,
           "AKTOR & PERMISSION\n"
           "·  tenaga-medis → kepulangan.rekomendasi   ·  keamanan → kepulangan.keamanan\n"
           "·  pengasuhan / kyai → kepulangan.pengasuhan   ·  keamanan → kepulangan.gerbang\n"
           "·  Santri/Wali hanya mengajukan & melihat status, tidak bisa menyetujui\n"
           "·  Gate berbasis slug role, bukan hanya permission — KepulanganController.php:15-17")
    c.save("02-activity-kepulangan.svg")


# ============================================================================
# 03 — ACTIVITY: ASISTEN AI HYBRID
# ============================================================================
def activity_ai():
    c = Canvas(2000, 1420, "ai")
    c.text(1000, 44, "ACTIVITY DIAGRAM — ASISTEN AI HYBRID (3 LAPIS KECERDASAN)", 28, "middle", BLUE_D, "700")
    c.text(1000, 72,
           "Lapis 1 intent lokal (tanpa API) · Lapis 2 heuristik/analitik · Lapis 3 LLM Groq + failover — jawaban atas syarat 'bukan sekadar CRUD/notifikasi'",
           15, "middle", LINE)

    c.lane(100, 100, 1800, 150, "Pengguna\n(role apa pun)", GREEN_D)
    c.lane(100, 250, 1800, 190, "Lapis 1\nIntent router\n+ RBAC", BLUE_D)
    c.lane(100, 440, 1800, 190, "Lapis 2\nMesin analitik\n& heuristik", AMBER_D)
    c.lane(100, 630, 1800, 300, "Lapis 3\nLLM Groq\n+ failover", PURPLE_D)
    c.lane(100, 930, 1800, 170, "Keluaran\n& jejak", LINE)

    c.dot(300, 175)
    c.action(360, 148, 230, 54, "Ketik pertanyaan\n(maks 1000 karakter)", GREEN, GREEN_D)
    c.action(650, 148, 210, 54, "Widget POST\n/ai/chat", GREEN, GREEN_D)
    c.arrow(313, 175, 360, 175)
    c.arrow(590, 175, 650, 175)

    c.action(300, 320, 220, 54, "gate(): login\n+ cek CSRF", BLUE, BLUE_D)
    c.diamond(680, 347, 190, 92)
    c.text(680, 352, "Intent cocok?", 13, "middle", AMBER_D, "700")
    c.action(900, 320, 260, 54, "buildRoleScopedContext\npotong data per role", BLUE, BLUE_D)
    c.action(1200, 320, 230, 54, "Query DB lokal\n(±20 topik)", BLUE, BLUE_D)
    c.action(1470, 320, 250, 54, "Jawaban terstruktur\n(tanpa panggilan API)", BLUE, BLUE_D)
    c.polyline([(755, 202), (755, 240), (410, 240), (410, 320)])
    c.arrow(520, 347, 585, 347)
    c.arrow(775, 347, 900, 347, label="Ya", label_dy=-10)
    c.arrow(1160, 347, 1200, 347)
    c.arrow(1430, 347, 1470, 347)

    c.action(300, 505, 240, 58, "analyze(): agregasi\npemeriksaan & tren", AMBER, AMBER_D)
    c.action(620, 505, 240, 58, "Ambang: stok obat,\nED ≤90 hari, antrean", AMBER, AMBER_D)
    c.action(940, 505, 240, 58, "Skor risiko sanitasi,\nIMT, heatmap blok", AMBER, AMBER_D)
    c.action(1260, 505, 240, 58, "getSystemWarnings()\n→ 6 aturan", AMBER, AMBER_D)
    c.arrow(540, 534, 620, 534)
    c.arrow(860, 534, 940, 534)
    c.arrow(1180, 534, 1260, 534)
    c.arrow(1500, 520, 1560, 374, dash="7 5", label="dipakai jalur intent",
            label_dx=90, label_fill=AMBER_D)
    c.arrow(680, 393, 420, 505, label="Tidak: butuh nalar", label_dx=-70, label_dy=-14, label_fill=AMBER_D)

    c.diamond(420, 720, 200, 96)
    c.text(420, 725, "llmEnabled()\n& API hidup?", 12, "middle", AMBER_D, "700")
    c.action(560, 690, 280, 60, "buildWideContext()\nJSON konteks ke prompt", PURPLE, PURPLE_D)
    c.action(890, 690, 250, 60, "cURL → Groq\nllama-3.1-8b-instant", PURPLE, PURPLE_D)
    c.diamond(1250, 720, 180, 90)
    c.text(1250, 725, "HTTP 200?", 12, "middle", AMBER_D, "700")
    c.action(1400, 690, 260, 60, "Failover model\nberikutnya (rantai 5)", PURPLE, PURPLE_D)
    c.action(1400, 810, 260, 56, "Pesan ramah 401 /\n429 / kuota habis", RED, RED_D)
    c.action(560, 810, 280, 56, "Fallback: ringkasan\nmesin lokal", AMBER, AMBER_D)
    c.arrow(420, 563, 420, 672)
    c.arrow(520, 720, 560, 720, label="Ya", label_dy=-10)
    c.arrow(840, 720, 890, 720)
    c.arrow(1140, 720, 1160, 720)
    c.arrow(1340, 720, 1400, 720, label="Gagal", label_dy=-10)
    c.polyline([(1530, 690), (1530, 655), (1015, 655), (1015, 690)], label="coba model lain",
               label_at=(1270, 645))
    c.arrow(1530, 750, 1530, 810)
    c.arrow(420, 768, 420, 838, label="Tidak", label_dx=32)
    c.polyline([(420, 838), (420, 890), (600, 890), (600, 866)])

    c.action(1400, 985, 260, 54, "Respons JSON\n(satu-shot)", SOFT, LINE)
    c.action(1060, 985, 260, 54, "Riwayat → session\n$_SESSION['ai_chat']")
    c.action(720, 985, 260, 54, "log_activity()\n'read','ai'")
    c.action(360, 985, 260, 54, "Badge notifikasi\nrefresh tiap 8 detik", AMBER, AMBER_D)
    c.dot(240, 1012, final=True)
    c.arrow(1250, 765, 1470, 985, label="Ya", label_dx=-40, label_dy=20)
    c.arrow(1530, 866, 1530, 985)
    c.polyline([(700, 866), (700, 958), (1500, 958), (1500, 985)])
    c.polyline([(1720, 347), (1720, 1012), (1660, 1012)])
    c.arrow(1400, 1012, 1320, 1012)
    c.arrow(1060, 1012, 980, 1012)
    c.arrow(720, 1012, 620, 1012)
    c.arrow(360, 1012, 259, 1012)

    c.note(100, 1130, 880, 240,
           "KECERDASAN NON-LLM (bukti bukan 'cuma tempel ChatGPT')\n"
           "·  Router intent regex: ±20 topik dijawab dari DB tanpa API — AiAssistantController.php:581-836\n"
           "·  Tren period-over-period + delta % + top-3 penyakit — AiAssistant.php:137-155\n"
           "·  Umur antrean kunjungan dalam hari — AiAssistant.php:168-173\n"
           "·  Ambang obat: stok < minimum_stok, ED, near-expiry ≤ 90 hari — models/Obat.php:143-159\n"
           "·  Skor risiko sanitasi 8 parameter berbobot (maks 16) — models/InspeksiKamar.php:44-60\n"
           "·  IMT + kategori Kemenkes — models/SkriningKesehatan.php:43-61\n"
           "·  Heatmap risiko: skor = kasus + aktif×2 + risiko×2 — views/eksekutif/index.php:132-146")
    c.note(1020, 1130, 880, 240,
           "MITIGASI RISIKO YANG SUDAH DITERAPKAN\n"
           "·  Role-scoping konteks: data di luar hak akses dipotong sebelum masuk prompt — controller :313\n"
           "·  Instruksi penolakan + larangan diagnosis medis di system prompt — AiAssistant.php:364\n"
           "·  Batas biaya: max_tokens 700, timeout 45 detik — config/ai.php:29-30\n"
           "·  Fallback 5 model + mode offline → sistem tetap berfungsi saat API mati\n"
           "·  Konfigurasi model hanya untuk Super Admin — controller :1298 (cek server-side, bukan disembunyikan)\n"
           "·  Input dibatasi 1000 karakter; CSRF divalidasi sebelum eksekusi\n"
           "·  Yang BELUM ada: rate limiting, cap riwayat, logging isi prompt, evaluasi akurasi")
    c.save("03-activity-asisten-ai.svg")


# ============================================================================
# 04 — ACTIVITY: TRACING KONTAK
# ============================================================================
def activity_tracing():
    c = Canvas(2000, 940, "tracing")
    c.text(1000, 44, "ACTIVITY DIAGRAM — TRACING KONTAK PENYAKIT MENULAR", 28, "middle", BLUE_D, "700")
    c.text(1000, 72,
           "TracingController.php — kasus → generate kontak otomatis → periksa → catat hasil → tutup kasus",
           15, "middle", LINE)

    c.dot(260, 190)
    c.action(320, 162, 240, 56, "Input kasus menular\n(form_kasus)", BLUE, BLUE_D)
    c.action(620, 162, 280, 56, "Auto-generate kontak:\npenghuni kamar sama", AMBER, AMBER_D)
    c.action(960, 162, 260, 56, "Buat pemeriksaan\njenis 'preventif'", BLUE, BLUE_D)
    c.action(1280, 162, 240, 56, "Panggil santri\n& periksa", BLUE, BLUE_D)
    c.action(1580, 162, 240, 56, "Catat hasil\n(catatHasil)", BLUE, BLUE_D)

    c.diamond(900, 330, 220, 96)
    c.text(900, 335, "Ada gejala?", 13, "middle", AMBER_D, "700")
    c.action(1180, 302, 260, 56, "Isolasi / rujuk\n+ karantina kamar", RED, RED_D)
    c.action(420, 302, 260, 56, "Lanjut pantau\nkontak berikutnya", AMBER, AMBER_D)
    c.diamond(1560, 330, 220, 96)
    c.text(1560, 335, "Semua kontak\nselesai?", 13, "middle", AMBER_D, "700")
    c.action(1420, 470, 280, 56, "TUTUP KASUS\n(histori tersimpan)", GREEN, GREEN_D)
    c.action(1000, 470, 300, 56, "Status kontak 4 tingkat:\nbelum → dipantau → selesai → lepas")

    c.arrow(273, 190, 320, 190)
    c.arrow(560, 190, 620, 190)
    c.arrow(900, 190, 960, 190)
    c.arrow(1220, 190, 1280, 190)
    c.arrow(1520, 190, 1580, 190)
    c.polyline([(1700, 218), (1700, 260), (900, 282)], label="periksa tiap kontak",
               label_at=(1360, 250))
    c.arrow(1010, 330, 1180, 330, label="Ya", label_dy=-10)
    c.arrow(790, 330, 680, 330, label="Tidak", label_dx=-24, label_dy=-12)
    c.polyline([(550, 358), (550, 400), (1150, 400), (1150, 470)])
    c.arrow(1440, 330, 1450, 330)
    c.arrow(1560, 378, 1560, 470, label="Ya", label_dx=28)
    c.polyline([(1670, 330), (1860, 330), (1860, 140), (1400, 140), (1400, 162)],
               label="Belum", label_at=(1830, 235))

    c.note(100, 600, 880, 200,
           "ATURAN OTOMATIS (kecerdasan di luar LLM)\n"
           "·  Kontak potensial dibuat otomatis dari santri sekamar — TracingController.php:114-132\n"
           "·  Pemeriksaan preventif dibuat otomatis per kontak — :219-234\n"
           "·  Kontak 'belum diperiksa' = aturan notifikasi no.2 di panel Notifikasi\n"
           "·  Kasus aktif ≥3 di satu blok menaikkan skor heatmap risiko eksekutif\n"
           "·  resyncKontak() mengisi kontak yang tertinggal setelah kasus dibuat")
    c.note(1020, 600, 880, 200,
           "AKTOR\n"
           "·  Tenaga Medis: input kasus, periksa, catat hasil, tutup kasus\n"
           "·  Pengasuhan: menghubungi wali santri yang dikarantina\n"
           "·  Kyai / Pimpinan: memantau kasus aktif lewat dashboard eksekutif\n"
           "·  Permission: tracing.view, tracing.manage, rekam-medis.view\n"
           "·  Riwayat kontak per kasus → bisa dicetak untuk laporan")
    c.save("04-activity-tracing.svg")


# ============================================================================
# 05 — ACTIVITY: E-SURAT + VERIFIKASI QR
# ============================================================================
def activity_surat():
    c = Canvas(2000, 900, "esurat")
    c.text(1000, 44, "ACTIVITY DIAGRAM — E-SURAT IZIN & VERIFIKASI QR PUBLIK", 28, "middle", BLUE_D, "700")
    c.text(1000, 72,
           "SuratIzinController.php — satu-satunya rute publik tanpa login: GET /verifikasi/{token} (routes.php:17)",
           15, "middle", LINE)

    c.dot(240, 200)
    c.action(300, 172, 240, 56, "Petugas terbitkan\nsurat izin", BLUE, BLUE_D)
    c.action(600, 172, 240, 56, "Simpan + token\nunik per surat", BLUE, BLUE_D)
    c.action(900, 172, 240, 56, "Cetak PDF + QR\ncetak.php", GREEN, GREEN_D)
    c.action(1200, 172, 240, 56, "Santri / wali bawa\nsurat ke gerbang", AMBER, AMBER_D)
    c.action(1500, 172, 260, 56, "Petugas scan QR\n→ /verifikasi/{token}", PURPLE, PURPLE_D)

    c.diamond(1630, 340, 240, 100)
    c.text(1630, 345, "Token valid &\nbelum dibatalkan?", 12, "middle", AMBER_D, "700")
    c.action(1180, 312, 260, 56, "Halaman publik:\nSAH + identitas", GREEN, GREEN_D)
    c.action(760, 312, 260, 56, "Halaman publik:\nTIDAK VALID", RED, RED_D)
    c.action(1180, 452, 260, 56, "Catat diverifikasi\n(waktu, IP, petugas)", BLUE, BLUE_D)
    c.action(760, 452, 260, 56, "Batal surat\n(batal())", RED, RED_D)
    c.dot(400, 480, final=True)

    c.arrow(253, 200, 300, 200)
    c.arrow(540, 200, 600, 200)
    c.arrow(840, 200, 900, 200)
    c.arrow(1140, 200, 1200, 200)
    c.arrow(1440, 200, 1500, 200)
    c.polyline([(1630, 228), (1630, 290)])
    c.arrow(1510, 340, 1440, 340, label="Ya", label_dy=-10)
    c.arrow(1310, 368, 1310, 452)
    c.arrow(1440, 480, 1520, 480)
    c.dot(1545, 480, final=True)
    c.arrow(760, 480, 419, 480)
    c.arrow(890, 452, 890, 368, dash="7 5", label="batal → QR mati",
            label_dx=96, label_dy=0)
    c.polyline([(1630, 390), (1630, 560), (1100, 560), (1100, 340), (1020, 340)],
               label="Tidak", label_at=(1672, 480))

    c.note(100, 600, 880, 240,
           "MENGAPA INI BAGUS UNTUK PRESENTASI\n"
           "·  Aktor EKSTERNAL tanpa akun (petugas gerbang / wali) lewat rute publik ber-token\n"
           "·  Verifikasi server-side: token dicek ke DB, bukan klaim di kertas → tidak bisa dipalsukan lewat UI\n"
           "·  Pembatalan surat langsung membuat QR mati → ada alur balik (batal → tidak valid)\n"
           "·  Kolom 'diverifikasi' tercatat di DB (sikes_db.sql:503) sebagai jejak audit\n"
           "·  Menunjukkan batas keamanan: rute publik permission null, tapi tetap butuh token acak")
    c.note(1020, 600, 880, 240,
           "AKTOR & PERMISSION\n"
           "·  Admin Santri / Pengasuhan → surat.create, surat.cetak, surat.batal\n"
           "·  Petugas gerbang / publik → GET /verifikasi/{token} (tanpa login)\n"
           "·  Super Admin → audit: siapa menerbitkan, siapa memverifikasi, dari IP mana\n"
           "·  Alur ini TIDAK memakai LLM — cocok untuk menegaskan bahwa AI hanya di modul asisten\n"
           "·  QR = tautan, bukan penyimpanan data medis: hanya status sah/tidak + identitas singkat")
    c.save("05-activity-esurat-qr.svg")


if __name__ == "__main__":
    print("Generating SVG diagrams ke:", OUT)
    usecase_overview()
    usecase_zoom()
    activity_kepulangan()
    activity_ai()
    activity_tracing()
    activity_surat()
    print("Selesai. Semua file lolos validasi XML well-formed.")
