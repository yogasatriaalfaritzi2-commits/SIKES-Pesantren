<?php
use App\Models\SkriningKesehatan;
use App\Models\Pemeriksaan;

 $santriId = (int) ($santri['id'] ?? 0);
 $umur     = (!empty($santri['tanggal_lahir']) && strtotime((string) $santri['tanggal_lahir']) !== false)
    ? (new DateTime((string) $santri['tanggal_lahir']))->diff(new DateTime())->y
    : null;
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <a href="<?= base_url('/rekam-medis') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0">Rekam Medis — <?= e($santri['nama'] ?? '-') ?></h1>
        <p class="text-muted small mb-0">
            NIS <?= e($santri['nis'] ?? '-') ?><?= $santri['nisn'] ? ' / ' . e($santri['nisn']) : '' ?> ·
            <?= $umur !== null ? $umur . ' tahun' : '-' ?> ·
            <?= e($santri['asrama_nama'] ?? '-') ?><?= $santri['kamar_nama'] ? ' · Kam. ' . e($santri['kamar_nama']) : '' ?>
        </p>
    </div>
    <a href="<?= base_url('/santri/' . $santriId) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-person me-1"></i> Data Santri
    </a>
    <?php if ($skrining): ?>
    <a href="<?= base_url('/skrining/' . $skrining['id']) ?>" class="btn btn-outline-brand btn-sm">
        <i class="bi bi-clipboard2-pulse me-1"></i> Profil Skrining
    </a>
    <?php endif; ?>
    <a href="<?= base_url('/pemeriksaan/riwayat/' . $santriId) ?>" class="btn btn-outline-brand btn-sm">
        <i class="bi bi-clock-history me-1"></i> Riwayat Kunjungan
    </a>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if ($warnings): ?>
<div class="alert alert-danger d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill mt-1 fs-5"></i>
    <div>
        <strong>Kondisi khusus (skrining awal):</strong>
        <?php foreach ($warnings as $w): ?>
            <span class="badge bg-danger-subtle text-danger-emphasis me-1 mt-1"><?= e($w) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <!-- Ringkasan Skrining -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clipboard2-pulse me-2"></i>Skrining Awal</div>
            <?php if ($skrining): ?>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Tanggal</span>
                    <span><?= e(date('d-m-Y', strtotime($skrining['tanggal_skrining']))) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">IMT</span>
                    <span><?= $skrining['imt'] !== null ? e((string) $skrining['imt']) . ' (' . e($skrining['kategori_imt'] ?? '-') . ')' : '-' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Gol. Darah</span>
                    <span><?= $skrining['golongan_darah'] !== '-' ? e($skrining['golongan_darah']) . ($skrining['rhesus'] !== '-' ? e($skrining['rhesus'] === 'positif' ? '+' : '-') : '') : '-' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Kondisi Khusus</span>
                    <?php if ($warnings): ?>
                        <span class="badge bg-danger-subtle text-danger-emphasis"><?= count($warnings) ?> kondisi</span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success-emphasis">Tidak ada</span>
                    <?php endif; ?>
                </li>
            </ul>
            <?php else: ?>
            <div class="card-body"><span class="text-muted small">Santri belum diskrining.</span></div>
            <?php endif; ?>
        </div>

        <!-- Kasus Menular -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-virus me-2"></i>Kasus Penyakit Menular (<?= count($kasus) ?>)</div>
            <ul class="list-group list-group-flush">
                <?php if (!$kasus): ?>
                    <li class="list-group-item text-muted small">Tidak ada riwayat kasus menular.</li>
                <?php endif; ?>
                <?php foreach ($kasus as $k): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold small"><?= e($k['penyakit']) ?></span>
                        <span class="badge bg-<?= $k['status_kasus'] === 'aktif' ? 'danger' : 'success' ?>-subtle
                                      text-<?= $k['status_kasus'] === 'aktif' ? 'danger' : 'success' ?>-emphasis">
                            <?= $k['status_kasus'] === 'aktif' ? 'AKTIF' : 'Selesai' ?>
                        </span>
                    </div>
                    <small class="text-muted"><?= e(date('d-m-Y', strtotime($k['tanggal_mulai']))) ?>
                        <?= $k['tanggal_selesai'] ? '– ' . e(date('d-m-Y', strtotime($k['tanggal_selesai']))) : '– sekarang' ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Pemeriksaan -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clipboard2-heart me-2"></i>Histori Pemeriksaan (<?= count($pemeriksaan) ?>)</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Tanggal</th><th>Jenis</th><th>Keluhan</th><th>Diagnosis</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (!$pemeriksaan): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pemeriksaan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($pemeriksaan as $p): ?>
                        <tr>
                            <td class="small text-nowrap"><?= e(date('d-m-y H:i', strtotime($p['tanggal_kunjungan']))) ?></td>
                            <td class="small"><?= Pemeriksaan::JENIS_LABEL[$p['jenis_kunjungan']] ?? '-' ?></td>
                            <td class="small" style="max-width:160px"><?= e(mb_strimwidth($p['keluhan'], 0, 45, '…')) ?></td>
                            <td class="small" style="max-width:160px">
                                <?= $p['diagnosis'] ? e(mb_strimwidth($p['diagnosis'], 0, 45, '…')) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $p['status'] === 'selesai' ? 'success' : ($p['status'] === 'menunggu' ? 'warning' : 'secondary') ?>-subtle
                                              text-<?= $p['status'] === 'selesai' ? 'success' : ($p['status'] === 'menunggu' ? 'warning' : 'secondary') ?>-emphasis">
                                    <?= Pemeriksaan::STATUS_LABEL[$p['status']] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3">
            <!-- Surat Izin -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold"><i class="bi bi-file-earmark-medical me-2"></i>Surat Izin Sakit (<?= count($izin) ?>)</div>
                    <ul class="list-group list-group-flush">
                        <?