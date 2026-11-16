<?php
use App\Core\Auth;
use App\Core\Session;

 $qs = function (array $over = []) use ($filters): string {
    $merged = array_filter(
        array_merge($filters, ['page' => 1], $over),
        fn ($v) => $v !== '' && $v !== null && $v !== 0
    );
    return base_url('/santri?' . http_build_query($merged));
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Data Santri</h1>
        <p class="text-muted mb-0">Kelola data santri dan penempatan asrama (<?= (int) $data['total'] ?> data).</p>
    </div>
    <?php if (Auth::hasPermission('santri.create')): ?>
    <a href="<?= base_url('/santri/create') ?>" class="btn btn-brand">
        <i class="bi bi-person-plus me-1"></i> Tambah Santri
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/santri') ?>">
            <div class="col-md-4">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="nama / NIS / NISN" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Jenis Kelamin</label>
                <select name="jk" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="L" <?= $filters['jk'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="P" <?= $filters['jk'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Asrama</label>
                <select name="asrama" class="form-select form-select-sm">
                    <option value="">Semua asrama</option>
                    <?php foreach ($asramaOptions as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= $filters['asrama'] === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= e($a['nama']) ?> (<?= e($a['jenis']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="aktif" <?= $filters['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $filters['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-6 col-md-1 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Santri</th><th>L/P</th><th>Penempatan</th><th>Wali</th><th>Status</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm"><?= e(strtoupper(mb_substr($row['nama'], 0, 1))) ?></div>
                            <div class="lh-sm">
                                <div class="fw-semibold">
                                    <a href="<?= base_url('/santri/' . $row['id']) ?>" class="text-decoration-none"><?= e($row['nama']) ?></a>
                                </div>
                                <small class="text-muted">NIS <?= e($row['nis']) ?><?= $row['nisn'] ? ' · NISN ' . e($row['nisn']) : '' ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-<?= $row['jenis_kelamin'] === 'L' ? 'info' : 'danger' ?>-subtle
                                      text-<?= $row['jenis_kelamin'] === 'L' ? 'info' : 'danger' ?>-emphasis">
                            <?= e($row['jenis_kelamin']) ?>
                        </span>
                    </td>
                    <td class="small">
                        <?php if ($row['kamar_nama']): ?>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= e($row['asrama_nama']) ?></span>
                            <div class="text-muted"><?= e($row['blok_nama']) ?> · Kamar <?= e($row['kamar_nama']) ?></div>
                        <?php else: ?>
                            <span class="text-muted">Belum ditempatkan</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= e($row['wali_nama'] ?? '-') ?></td>
                    <td>
                        <span class="badge bg-<?= $row['status'] === 'aktif' ? 'success' : 'secondary' ?>-subtle
                                      text-<?= $row['status'] === 'aktif' ? 'success' : 'secondary' ?>-emphasis">
                            <?= e(ucfirst($row['status'])) ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/santri/' . $row['id']) ?>" title="Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if (Auth::hasPermission('santri.edit')): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/santri/' . $row['id'] . '/edit') ?>" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (Auth::hasPermission('santri.delete')): ?>
                        <form class="d-inline form-delete" method="POST"
                              action="<?= base_url('/santri/' . $row['id'] . '/delete') ?>"
                              data-name="santri <?= e($row['nama']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
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
            <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $qs(['page' => max(1, $data['page'] - 1)]) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $qs(['page' => min($data['pages'], $data['page'] + 1)]) ?>">&raquo;</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</div>