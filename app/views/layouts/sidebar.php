<?php
use App\Core\Auth;

 $current = current_path();
 $dash    = ($current === '/' || $current === '/dashboard') ? 'active' : '';
 $is      = fn (string $p) => str_starts_with($current, $p) ? 'active' : '';
?>
<aside class="sidebar text-white d-flex flex-column" id="sidebar">
    <div class="sidebar-brand d-flex align-items-center gap-2 px-3 py-3">
        <img src="<?= base_url('favicon.svg') ?>" alt="SIKES Logo" width="32" height="32" class="rounded">
        <div class="lh-sm">
            <div class="fw-bold">SIKES</div>
            <small class="text-white-50">Pondok Pesantren</small>
        </div>
    </div>

    <hr class="border-secondary my-0">

    <nav class="nav flex-column p-2 gap-1">
        <!-- ==================== DASHBOARD ==================== -->
        <a href="<?= base_url('/dashboard') ?>" class="nav-link <?= $dash ?>">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>

        <!-- ==================== DASHBOARD EKSEKUTIF (Kyai/Pimpinan) ==================== -->
        <?php if (Auth::isSuper() || Auth::hasRole('kyai')): ?>
        <a href="<?= base_url('/eksekutif') ?>" class="nav-link <?= $is('/eksekutif') ?>">
            <i class="bi bi-speedometer me-2"></i>Dashboard Eksekutif
        </a>
        <?php endif; ?>



        <!-- ==================== MASTER DATA ==================== -->
        <?php if (Auth::hasAnyPermission(['santri.view', 'wali.view', 'asrama.view'])): ?>
        <div class="sidebar-heading px-3 mt-3 mb-1">Master Data</div>
        <?php if (Auth::hasPermission('santri.view')): ?>
        <a href="<?= base_url('/santri') ?>" class="nav-link <?= $is('/santri') ?>">
            <i class="bi bi-people me-2"></i>Data Santri
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('wali.view')): ?>
        <a href="<?= base_url('/wali') ?>" class="nav-link <?= $is('/wali') ?>">
            <i class="bi bi-person-badge me-2"></i>Wali Santri
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('asrama.view')): ?>
        <a href="<?= base_url('/asrama') ?>" class="nav-link <?= $is('/asrama') ?>">
            <i class="bi bi-building me-2"></i>Asrama
        </a>
        <a href="<?= base_url('/blok') ?>" class="nav-link <?= $is('/blok') ?>">
            <i class="bi bi-grid-3x3-gap me-2"></i>Blok
        </a>
        <a href="<?= base_url('/kamar') ?>" class="nav-link <?= $is('/kamar') ?>">
            <i class="bi bi-door-open me-2"></i>Kamar
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- ==================== KESEHATAN (diperbarui Tahap 16: Rekam Medis aktif) ==================== -->
        <?php if (Auth::hasAnyPermission(['pemeriksaan.view', 'rekam_medis.view', 'surat.view', 'bpjs.view', 'rujukan.view', 'obat.view'])): ?>
        <div class="sidebar-heading px-3 mt-3 mb-1">Kesehatan</div>
        <?php if (Auth::hasPermission('pemeriksaan.view')): ?>
        <a href="<?= base_url('/pemeriksaan') ?>" class="nav-link <?= $is('/pemeriksaan') ?>">
            <i class="bi bi-clipboard2-heart me-2"></i>Pemeriksaan
        </a>
        <a href="<?= base_url('/tracing') ?>" class="nav-link <?= $is('/tracing') ?>">
            <i class="bi bi-diagram-3 me-2"></i>Contact Tracing
        </a>
        <a href="<?= base_url('/skrining') ?>" class="nav-link <?= $is('/skrining') ?>">
            <i class="bi bi-clipboard2-pulse me-2"></i>Skrining Kesehatan
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('surat.view')): ?>
        <a href="<?= base_url('/surat') ?>" class="nav-link <?= $is('/surat') ?>">
            <i class="bi bi-file-earmark-medical me-2"></i>Surat Izin Sakit
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('rujukan.view')): ?>
        <a href="<?= base_url('/rujukan') ?>" class="nav-link <?= $is('/rujukan') ?>">
            <i class="bi bi-envelope-paper me-2"></i>Surat Rujukan
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('bpjs.view')): ?>
        <a href="<?= base_url('/bpjs') ?>" class="nav-link <?= $is('/bpjs') ?>">
            <i class="bi bi-hospital me-2"></i>Asuransi BPJS
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('obat.view')): ?>
        <a href="<?= base_url('/obat') ?>" class="nav-link <?= $is('/obat') ?>">
            <i class="bi bi-capsule me-2"></i>Manajemen Obat
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('rekam_medis.view')): ?>
        <a href="<?= base_url('/rekam-medis') ?>" class="nav-link <?= $is('/rekam-medis') ?>">
            <i class="bi bi-journal-medical me-2"></i>Rekam Medis
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- ==================== MODUL PONDOK (diperbarui Tahap 16: Pengasuhan, Keuangan, Laporan aktif) ==================== -->
        <?php if (Auth::hasAnyPermission(['pengasuhan.view', 'gizi.view', 'keuangan.view', 'laporan.view', 'sanitasi.view', 'piket.view', 'kepulangan.view', 'buku_tamu.view'])): ?>
        <div class="sidebar-heading px-3 mt-3 mb-1">Modul Pondok</div>
        <?php if (Auth::hasPermission('piket.view')): ?>
        <a href="<?= base_url('/piket') ?>" class="nav-link <?= $is('/piket') ?>">
            <i class="bi bi-calendar-week me-2"></i>Piket Kebersihan
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('sanitasi.view')): ?>
        <a href="<?= base_url('/sanitasi') ?>" class="nav-link <?= $is('/sanitasi') ?>">
            <i class="bi bi-house-gear me-2"></i>Sanitasi &amp; Lingkungan
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('kepulangan.view')): ?>
        <a href="<?= base_url('/kepulangan') ?>" class="nav-link <?= $is('/kepulangan') ?>">
            <i class="bi bi-box-arrow-right me-2"></i>Kepulangan Sakit
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('buku_tamu.view')): ?>
        <a href="<?= base_url('/buku-tamu') ?>" class="nav-link <?= $is('/buku-tamu') ?>">
            <i class="bi bi-journal-check me-2"></i>Buku Tamu
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('pengasuhan.view')): ?>
        <a href="<?= base_url('/pengasuhan') ?>" class="nav-link <?= $is('/pengasuhan') ?>">
            <i class="bi bi-house-heart me-2"></i>Pengasuhan
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('gizi.view')): ?>
        <a href="<?= base_url('/gizi') ?>" class="nav-link <?= $is('/gizi') ?>">
            <i class="bi bi-egg-fried me-2"></i>Dapur &amp; Gizi
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('keuangan.view')): ?>
        <a href="<?= base_url('/keuangan') ?>" class="nav-link <?= $is('/keuangan') ?>">
            <i class="bi bi-cash-coin me-2"></i>Keuangan
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('laporan.view')): ?>
        <a href="<?= base_url('/laporan') ?>" class="nav-link <?= $is('/laporan') ?>">
            <i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- ==================== MANAJEMEN SISTEM ==================== -->
        <?php if (Auth::hasAnyPermission(['users.view', 'roles.view', 'logs.view'])): ?>
        <div class="sidebar-heading px-3 mt-3 mb-1">Manajemen Sistem</div>
        <?php if (Auth::hasPermission('users.view')): ?>
        <a href="<?= base_url('/users') ?>" class="nav-link <?= $is('/users') ?>">
            <i class="bi bi-people-gear me-2"></i>Pengguna
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('roles.view')): ?>
        <a href="<?= base_url('/roles') ?>" class="nav-link <?= $is('/roles') ?>">
            <i class="bi bi-shield-lock me-2"></i>Role &amp; Hak Akses
        </a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('logs.view')): ?>
        <a href="<?= base_url('/activity') ?>" class="nav-link <?= $is('/activity') ?>">
            <i class="bi bi-clock-history me-2"></i>Log Aktivitas
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- ==================== AKUN ==================== -->
        <div class="sidebar-heading px-3 mt-3 mb-1">Akun</div>
        <a href="<?= base_url('/logout') ?>" class="nav-link"><i class="bi bi-box-arrow-left me-2"></i>Logout</a>
    </nav>

    <div class="mt-auto p-3 small text-white-50 border-top border-secondary">
        v<?= APP_VERSION ?>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>