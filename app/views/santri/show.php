<?php
use App\Core\Auth;
use App\Core\Session;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/santri') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0"><?= e($item['nama']) ?></h1>
        <p class="text-muted small mb-0">NIS <?= e($item['nis']) ?><?= $item['nisn'] ? ' · NISN ' . e($item['nisn']) : '' ?></p>
    </div>
    <span class="badge bg-<?= $item['status'] === 'aktif' ? 'success' : 'secondary' ?>-subtle
                  text-<?= $item['status'] === 'aktif' ? 'success' : 'secondary' ?>-emphasis">
        <?= e(ucfirst($item['status'])) ?>
    </span>
    <?php if (Auth::hasPermission('pemeriksaan.view')): ?>
        <?php if ($skrining): ?>
        <a href="<?= base_url('/skrining/' . $skrining['id']) ?>" class="btn btn-outline-brand btn-sm">
            <i class="bi bi-heart-pulse me-1"></i> Profil Kesehatan
        </a>
        <?php elseif (Auth::hasPermission('pemeriksaan.create')): ?>
        <a href="<?= base_url('/skrining/create?santri_id=' . $item['id']) ?>" class="btn btn-outline-brand btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Input Skrining
        </a>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (Auth::hasPermission('santri.edit')): ?>
    <a href="<?= base_url('/santri/' . $item['id'] . '/edit') ?>" class="btn btn-brand btn-sm">
        <i class="bi bi-pencil me-1"></i> Edit
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if (Auth::hasPermission('pemeriksaan.view') && $skriningWarnings): ?>
<div class="alert alert-danger d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill mt-1 fs-5"></i>
    <div>
        <strong>Perhatian kondisi khusus:</strong>
        <?php foreach ($skriningWarnings as $i => $w): ?><?= $i > 0 ? '' : '' ?>
            <span class="badge bg-danger-subtle text-danger-emphasis me-1 mt-1"><?= e($w) ?></span>
        <?php endforeach; ?>
        <div class="small mt-2">Sumber: skrining kesehatan awal (isian tenaga medis). Lihat <a href="<?= base_url('/skrining/' . $skrining['id']) ?>">Profil Kesehatan</a> untuk detail.</div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person-vcard me-2"></i>Data Pribadi</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Nama Lengkap</span><span><?= e($item['nama']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Jenis Kelamin</span><span><?= $item['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Tempat, Tgl Lahir</span><span><?= e($item['tempat_lahir'] ?? '-') ?>, <?= $item['tanggal_lahir'] ? e(date('d-m-Y', strtotime($item['tanggal_lahir']))) : '-' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">No. Telepon</span><span><?= e($item['no_telepon'] ?? '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Alamat</span><span class="text-end"><?= e($item['alamat'] ?? '-') ?></span></li>
            </ul>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-building me-2"></i>Penempatan</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Asrama</span><span><?= e($item['asrama_nama'] ?? '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Blok</span><span><?= e($item['blok_nama'] ?? '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Kamar</span><span><?= e($item['kamar_nama'] ?? 'Belum ditempatkan') ?></span></li>
            </ul>
        </div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person-badge me-2"></i>Wali Santri</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Nama</span><span><?= e($item['wali_nama'] ?? '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Telepon</span><span><?= e($item['wali_telepon'] ?? '-') ?></span></li>
            </ul>
        </div>
        <?php if (Auth::hasPermission('pemeriksaan.view') && $skrining): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-heart-pulse me-2"></i>Ringkasan Kesehatan</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">IMT</span>
                    <span><?= $skrining['imt'] !== null ? e((string) $skrining['imt']) . ' (' . e($skrining['kategori_imt'] ?? '-') . ')' : '-' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Gol. Darah</span>
                    <span><?= $skrining['golongan_darah'] !== '-' ? e($skrining['golongan_darah']) . ($skrining['rhesus'] !== '-' ? e($skrining['rhesus'] === 'positif' ? '+' : '-') : '') : '-' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Kondisi Khusus</span>
                    <span>
                        <?php if ($skriningWarnings): ?>
                            <span class="badge bg-danger-subtle text-danger-emphasis"><?= count($skriningWarnings) ?> kondisi</span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success-emphasis">Tidak ada</span>
                        <?php endif; ?>
                    </span>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>