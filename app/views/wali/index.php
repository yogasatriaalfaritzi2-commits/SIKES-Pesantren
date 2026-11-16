<?php
use App\Core\Auth;
use App\Core\Session;

 $pageQs = fn (int $p): string => base_url('/wali?' . http_build_query(['q' => $q !== '' ? $q : null, 'page' => $p]));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Wali Santri</h1>
        <p class="text-muted mb-0">Data wali/pendamping santri (<?= (int) $data['total'] ?> data).</p>
    </div>
    <?php if (Auth::hasPermission('wali.create')): ?>
    <a href="<?= base_url('/wali/create') ?>" class="btn btn-brand"><i class="bi bi-person-plus me-1"></i> Tambah Wali</a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-person-badge me-2"></i>Daftar Wali</span>
        <form class="d-flex gap-2" method="GET" action="<?= base_url('/wali') ?>">
            <input type="text" name="q" class="form-control form-control-sm" style="width:230px"
                   placeholder="Cari nama / telepon" value="<?= e($q) ?>">
            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Nama Wali</th><th>Telepon</th><th>Pekerjaan</th><th>Alamat</th><th>Santri</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td class="fw-semibold"><?= e($row['nama']) ?></td>
                    <td class="small"><?= e($row['no_telepon']) ?></td>
                    <td class="small text-muted"><?= e($row['pekerjaan'] ?? '-') ?></td>
                    <td class="small text-muted"><?= e($row['alamat'] ?? '-') ?></td>
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= (int) $row['santri_count'] ?> santri</span></td>
                    <td class="text-end">
                        <?php if (Auth::hasPermission('wali.edit')): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/wali/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if (Auth::hasPermission('wali.delete')): ?>
                        <form class="d-inline form-delete" method="POST" action="<?= base_url('/wali/' . $row['id'] . '/delete') ?>"
                              data-name="wali <?= e($row['nama']) ?>">
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
            <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $pageQs(max(1, $data['page'] - 1)) ?>">&laquo;</a></li>
            <li class="page-item <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>"><a class="page-link" href="<?= $pageQs(min($data['pages'], $data['page'] + 1)) ?>">&raquo;</a></li>
        </ul>
    </div>
    <?php endif; ?>
</div>