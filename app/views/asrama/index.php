<?php
use App\Core\Auth;
use App\Core\Session;

 $qs = function (array $over = []) use ($filters): string {
    $merged = array_filter(array_merge($filters, ['page' => 1], $over), fn ($v) => $v !== '' && $v !== null);
    return base_url('/asrama?' . http_build_query($merged));
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Data Asrama</h1>
        <p class="text-muted mb-0">Gedung asrama putra &amp; putri (<?= (int) $data['total'] ?> data).</p>
    </div>
    <?php if (Auth::hasPermission('asrama.manage')): ?>
    <a href="<?= base_url('/asrama/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i> Tambah Asrama</a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-building me-2"></i>Daftar Asrama</span>
        <form class="d-flex gap-2" method="GET" action="<?= base_url('/asrama') ?>">
            <input type="text" name="q" class="form-control form-control-sm" style="width:200px"
                   placeholder="Cari nama / lokasi" value="<?= e($filters['q']) ?>">
            <select name="jenis" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                <option value="">Semua jenis</option>
                <option value="putra" <?= $filters['jenis'] === 'putra' ? 'selected' : '' ?>>Putra</option>
                <option value="putri" <?= $filters['jenis'] === 'putri' ? 'selected' : '' ?>>Putri</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Asrama</th><th>Lokasi</th><th>Blok</th><th>Kamar</th><th>Penghuni Aktif</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($row['nama']) ?></div>
                        <span class="badge bg-<?= $row['jenis'] === 'putra' ? 'info' : 'danger' ?>-subtle
                                      text-<?= $row['jenis'] === 'putra' ? 'info' : 'danger' ?>-emphasis">
                            <?= e(ucfirst($row['jenis'])) ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= e($row['lokasi'] ?? '-') ?></td>
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= (int) $row['blok_count'] ?></span></td>
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= (int) $row['kamar_count'] ?></span></td>
                    <td><span class="badge bg-success-subtle text-success-emphasis"><?= (int) $row['penghuni_count'] ?></span></td>
                    <td class="text-end">
                        <?php if (Auth::hasPermission('asrama.manage')): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/asrama/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline form-delete" method="POST" action="<?= base_url('/asrama/' . $row['id'] . '/delete') ?>"
                              data-name="asrama <?= e($row['nama']) ?>">
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