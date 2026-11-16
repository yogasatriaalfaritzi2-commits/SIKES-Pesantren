<?php
use App\Core\Auth;
use App\Core\Session;

 $qs = function (array $over = []) use ($filters): string {
    $merged = array_filter(array_merge($filters, ['page' => 1], $over), fn ($v) => $v !== '' && $v !== null && $v !== 0);
    return base_url('/kamar?' . http_build_query($merged));
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Data Kamar</h1>
        <p class="text-muted mb-0">Kamar per blok beserta okupansi (<?= (int) $data['total'] ?> data).</p>
    </div>
    <?php if (Auth::hasPermission('asrama.manage')): ?>
    <a href="<?= base_url('/kamar/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i> Tambah Kamar</a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-door-open me-2"></i>Daftar Kamar</span>
        <form class="d-flex gap-2" method="GET" action="<?= base_url('/kamar') ?>">
            <input type="text" name="q" class="form-control form-control-sm" style="width:200px"
                   placeholder="Cari kamar / blok / asrama" value="<?= e($filters['q']) ?>">
            <select name="asrama" class="form-select form-select-sm" style="width:190px" onchange="this.form.submit()">
                <option value="">Semua asrama</option>
                <?php foreach ($asramaOptions as $a): ?>
                <option value="<?= (int) $a['id'] ?>" <?= $filters['asrama'] === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['nama']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Kamar</th><th>Blok</th><th>Asrama</th><th style="min-width:160px">Okupansi</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <?php
                $pct = min(100, (int) round(((int) $row['terisi'] / max(1, (int) $row['kapasitas'])) * 100));
                $bar = (int) $row['terisi'] >= (int) $row['kapasitas'] ? 'danger' : ($pct >= 75 ? 'warning' : 'success');
                ?>
                <tr>
                    <td class="fw-semibold"><?= e($row['nama']) ?></td>
                    <td class="small"><?= e($row['blok_nama']) ?></td>
                    <td class="small">
                        <?= e($row['asrama_nama']) ?>
                        <span class="badge bg-<?= $row['asrama_jenis'] === 'putra' ? 'info' : 'danger' ?>-subtle
                                      text-<?= $row['asrama_jenis'] === 'putra' ? 'info' : 'danger' ?>-emphasis ms-1">
                            <?= e(ucfirst($row['asrama_jenis'])) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:6px">
                                <div class="progress-bar bg-<?= $bar ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                            <small class="text-nowrap text-muted"><?= (int) $row['terisi'] ?>/<?= (int) $row['kapasitas'] ?></small>
                        </div>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/kamar/' . $row['id']) ?>" title="Penghuni">
                            <i class="bi bi-people"></i>
                        </a>
                        <?php if (Auth::hasPermission('asrama.manage')): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/kamar/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline form-delete" method="POST" action="<?= base_url('/kamar/' . $row['id'] . '/delete') ?>"
                              data-name="kamar <?= e($row['nama']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($data['pages'] > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Halaman <?= $data['page'] ?> dari <?= $data['pages'] ?></small>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $qs(['page' => max(1, $data['page'] - 1)]) ?>">&laquo;</a></li>
            <li class="page-item <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>"><a class="page-link" href="<?= $qs(['page' => min($data['pages'], $data['page'] + 1)]) ?>">&raquo;</a></li>
        </ul>
    </div>
    <?php endif; ?>
</div>