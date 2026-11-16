<?php
use App\Core\Auth;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/kamar') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0">Kamar <?= e($item['nama']) ?></h1>
        <p class="text-muted small mb-0"><?= e($item['asrama_nama']) ?> · <?= e($item['blok_nama']) ?> · <?= e(ucfirst($item['asrama_jenis'])) ?></p>
    </div>
    <?php if (Auth::hasPermission('asrama.manage')): ?>
    <a href="<?= base_url('/kamar/' . $item['id'] . '/edit') ?>" class="btn btn-brand btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-door-open me-2"></i>Informasi Kamar</div>
            <div class="card-body text-center">
                <div class="display-6 fw-bold text-brand"><?= $terisi ?>/<?= (int) $item['kapasitas'] ?></div>
                <p class="text-muted small mb-2">penghuni aktif / kapasitas</p>
                <?php $pct = min(100, (int) round($terisi / max(1, (int) $item['kapasitas']) * 100)); ?>
                <div class="progress" style="height:8px">
                    <div class="progress-bar bg-<?= $terisi >= (int) $item['kapasitas'] ? 'danger' : 'success' ?>"
                         style="width:<?= $pct ?>%"></div>
                </div>
                <?php if ($terisi >= (int) $item['kapasitas']): ?>
                <div class="alert alert-warning py-2 small mt-3 mb-0">Kamar penuh — santri baru tidak bisa ditempatkan di sini.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-people me-2"></i>Daftar Penghuni (<?= count($occupants) ?>)
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Santri</th><th>NIS</th><th>L/P</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    <?php if (!$occupants): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada penghuni. Tempatkan santri lewat menu <a href="<?= base_url('/santri') ?>">Data Santri</a>.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($occupants as $o): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($o['nama']) ?></td>
                            <td class="small text-muted"><?= e($o['nis']) ?></td>
                            <td><span class="badge bg-<?= $o['jenis_kelamin'] === 'L' ? 'info' : 'danger' ?>-subtle
                                          text-<?= $o['jenis_kelamin'] === 'L' ? 'info' : 'danger' ?>-emphasis"><?= e($o['jenis_kelamin']) ?></span></td>
                            <td><span class="badge bg-<?= $o['status'] === 'aktif' ? 'success' : 'secondary' ?>-subtle
                                          text-<?= $o['status'] === 'aktif' ? 'success' : 'secondary' ?>-emphasis"><?= e(ucfirst($o['status'])) ?></span></td>
                            <td class="text-end">
                                <?php if (Auth::hasPermission('santri.view')): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/santri/' . $o['id']) ?>"><i class="bi bi-eye"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>