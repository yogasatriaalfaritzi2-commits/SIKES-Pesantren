<?php
use App\Core\Session;
use App\Core\Auth;

/* ============================================================
   NAVBAR — Menu akun SELF-CONTAINED:
   - Toggle memakai VANILLA JS murni (inline di partial ini),
     tidak bergantung pada Bootstrap JS / skrip halaman mana pun
     -> dropdown & logout berfungsi di SEMUA halaman, termasuk
        halaman dengan skrip kustom (dashboard, AI, dsb.)
   - Logout: POST + CSRF + konfirmasi -> session dihancurkan -> login
   ============================================================ */
 $user = Auth::user();
?>
<style>
    /* --- Notifikasi Navbar Bell & Dropdown --- */
    #navNotifWrap { position: relative; }
    #navNotifToggle { position: relative; }
    #navNotifBadge {
        position: absolute;
        top: -4px;
        right: -4px;
        font-size: .65rem;
        padding: .2em .45em;
        border-radius: 999px;
        border: 2px solid #fff;
    }
    #navNotifMenu {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        width: 320px;
        max-width: 90vw;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 14px 34px -8px rgba(15, 23, 42, .25);
        z-index: 2000;
        text-align: left;
        overflow: hidden;
    }
    #navNotifMenu.open { display: block; animation: accIn .12s ease; }
    #navNotifListHeader {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: #f8fafc;
        border-bottom: 1px solid #eef2f6;
    }
    #navNotifItems {
        max-height: 320px;
        overflow-y: auto;
    }
    .nav-notif-row {
        display: flex;
        gap: 10px;
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: inherit;
        transition: background .15s ease;
    }
    .nav-notif-row:hover {
        background: #f8fafc;
    }
    .nav-notif-row:last-child {
        border-bottom: 0;
    }

    /* --- Toast Container Real-time --- */
    #sikesToastContainer {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 380px;
        pointer-events: none;
    }
    .sikes-realtime-toast {
        pointer-events: auto;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border-left: 5px solid #0f766e;
        overflow: hidden;
        animation: toastSlideIn .3s cubic-bezier(0.16, 1, 0.3, 1);
        transition: all .25s ease;
    }
    .sikes-realtime-toast.danger {
        border-left-color: #ef4444;
    }
    .sikes-realtime-toast.warning {
        border-left-color: #f59e0b;
    }
    @keyframes toastSlideIn {
        from { transform: translateX(110%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes toastSlideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(110%); opacity: 0; }
    }

    /* --- Menu akun (self-contained) --- */
    #accountMenuWrap { position: relative; }
    #accountMenu {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        min-width: 230px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 14px 34px -8px rgba(15, 23, 42, .25);
        padding: 6px;
        z-index: 2000;            /* di atas sticky navbar & semua konten */
        text-align: left;
    }
    #accountMenu.open { display: block; animation: accIn .12s ease; }
    @keyframes accIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
    #accountMenu .acc-head {
        padding: 8px 10px 6px;
        border-bottom: 1px solid #eef2f6;
        margin-bottom: 4px;
    }
    #accountMenu .acc-name  { font-weight: 800; font-size: .88rem; color: #1e293b; }
    #accountMenu .acc-user  { font-size: .75rem; color: #94a3b8; }
    #accountMenu .acc-role  { margin-top: 4px; }
    #accountMenu .acc-item {
        display: flex; align-items: center; gap: 10px;
        width: 100%; text-align: left;
        padding: 9px 10px; border: 0; background: none;
        border-radius: 8px; font-size: .87rem; font-weight: 600;
        color: #334155; text-decoration: none; cursor: pointer;
    }
    #accountMenu .acc-item:hover { background: #f1f5f9; color: #0f766e; }
    #accountMenu .acc-item.text-danger { color: #dc2626; }
    #accountMenu .acc-item.text-danger:hover { background: #fef2f2; color: #b91c1c; }
</style>

<nav class="navbar navbar-expand bg-white shadow-sm sticky-top px-3 no-print">
    <!-- Tombol buka/tutup sidebar -->
    <button class="btn btn-outline-secondary btn-sm me-3" id="sidebarToggle" type="button"
            aria-label="Buka/tutup sidebar">
        <i class="bi bi-list fs-5"></i>
    </button>

    <!-- Judul halaman -->
    <span class="navbar-text fw-semibold"><?= e($title ?? '') ?></span>

    <div class="ms-auto d-flex align-items-center gap-2">
        <!-- ===== LONCENG NOTIFIKASI REAL-TIME ===== -->
        <div id="navNotifWrap">
            <button type="button" id="navNotifToggle"
                    class="btn btn-light btn-sm rounded-circle p-2 d-flex align-items-center justify-content-center"
                    style="width: 38px; height: 38px;"
                    aria-label="Notifikasi Sistem"
                    aria-haspopup="true" aria-expanded="false" title="Peringatan & Notifikasi Sistem">
                <i class="bi bi-bell-fill fs-5 text-secondary"></i>
                <span class="badge bg-danger d-none" id="navNotifBadge">0</span>
            </button>

            <!-- Dropdown Notifikasi -->
            <div id="navNotifMenu" role="menu">
                <div id="navNotifListHeader">
                    <span class="fw-bold small text-dark"><i class="bi bi-bell me-1 text-primary"></i> Notifikasi Sistem</span>
                    <span class="badge bg-danger-subtle text-danger-emphasis rounded-pill small" id="navNotifHeaderBadge">0 Isu</span>
                </div>
                <div id="navNotifItems">
                    <div class="p-3 text-center text-muted small">Memuat notifikasi...</div>
                </div>
                <div class="p-2 bg-light border-top text-center">
                    <a href="<?= base_url('/ai') ?>" class="btn btn-sm btn-link p-0 text-decoration-none fw-semibold small text-primary">
                        Buka Pusat Notifikasi & AI &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- ===== MENU AKUN (self-contained, selalu berfungsi) ===== -->
        <div id="accountMenuWrap">
            <button type="button" id="accountToggle"
                    class="btn btn-light btn-sm d-flex align-items-center gap-2"
                    aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-person-circle fs-5"></i>
                <span class="d-none d-md-inline fw-semibold"><?= e($user['nama'] ?? 'Pengguna') ?></span>
                <i class="bi bi-chevron-down" style="font-size:.7rem"></i>
            </button>

            <div id="accountMenu" role="menu">
                <div class="acc-head">
                    <div class="acc-name"><?= e($user['nama'] ?? 'Pengguna') ?></div>
                    <div class="acc-user">@<?= e($user['username'] ?? 'pengguna') ?></div>
                    <div class="acc-role">
                        <span class="badge bg-success-subtle text-success-emphasis">
                            <?= e($user['role_name'] ?? 'Pengguna') ?>
                        </span>
                    </div>
                </div>

                <!-- Profil (placeholder modul profil masa depan) -->
                <a class="acc-item" href="#" onclick="return false;">
                    <i class="bi bi-person"></i> Profil
                </a>

                <!-- Logout: POST + CSRF langsung tanpa pop-up dialog browser -->
                <form method="POST" action="<?= base_url('/logout') ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                    <button type="submit" class="acc-item text-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<!-- Container Toast Notifikasi Real-time -->
<div id="sikesToastContainer"></div>

<script>
/* ============================================================
   Toggle menu akun — VANILLA JS MURNI (tanpa dependency):
   - Klik tombol  -> buka/tutup
   - Klik di luar -> tutup
   - Tombol Esc   -> tutup
   Skrip inline di partial ini berjalan di SEMUA halaman,
   tidak terpengaruh error/skrip apa pun di konten halaman.
   ============================================================ */
(function () {
    var btn  = document.getElementById('accountToggle');
    var menu = document.getElementById('accountMenu');
    if (!btn || !menu) {
        return; // navbar tidak dirender (mis. halaman login) — aman
    }

    function openMenu() {
        menu.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
    }
    function closeMenu() {
        menu.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        if (menu.classList.contains('open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    // Klik di luar menu -> tutup
    document.addEventListener('click', function (e) {
        if (menu.classList.contains('open')
            && !menu.contains(e.target)
            && !btn.contains(e.target)) {
            closeMenu();
        }
    });

    // Tombol Esc -> tutup
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMenu();
            closeNotifMenu();
        }
    });

    // ===== Toggle Lonceng Notifikasi Navbar =====
    var notifBtn  = document.getElementById('navNotifToggle');
    var notifMenu = document.getElementById('navNotifMenu');

    function openNotifMenu() {
        if (!notifMenu) return;
        notifMenu.classList.add('open');
        notifBtn.setAttribute('aria-expanded', 'true');
    }
    function closeNotifMenu() {
        if (!notifMenu) return;
        notifMenu.classList.remove('open');
        notifBtn.setAttribute('aria-expanded', 'false');
    }

    if (notifBtn && notifMenu) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            if (notifMenu.classList.contains('open')) {
                closeNotifMenu();
            } else {
                closeMenu(); // tutup menu akun jika sedang terbuka
                openNotifMenu();
            }
        });

        document.addEventListener('click', function(e) {
            if (notifMenu.classList.contains('open')
                && !notifMenu.contains(e.target)
                && !notifBtn.contains(e.target)) {
                closeNotifMenu();
            }
        });
    }
})();
</script>