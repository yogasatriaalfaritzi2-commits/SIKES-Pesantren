<?php
use App\Core\Auth;
use App\Core\Session;

 $pageQs = fn (int $p): string => base_url('/users?' . http_build_query(['q' => $q !== '' ? $q : null, 'page' => $p]));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Manajemen Pengguna</h1>
        <p class="text-muted mb-0">Kelola akun pengguna sistem dan role-nya.</p>
    </div>
    <?php if (Auth::hasPermission('users.create')): ?>
    <a href="<?= base_url('/users/create') ?>" class="btn btn-brand">
        <i class="bi bi-person-plus me-1"></i> Tambah Pengguna
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-people me-2"></i>Daftar Pengguna (<?= (int) $data['total'] ?>)</span>
        <form class="d-flex gap-2" method="GET" action="<?= base_url('/users') ?>">
            <input type="text" name="q" class="form-control form-control-sm" style="width:230px"
                   placeholder="Cari nama / username / email" value="<?= e($q) ?>">
            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Pengguna</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm"><?= e(strtoupper(mb_substr($row['nama'], 0, 1))) ?></div>
                            <div class="lh-sm">
                                <div class="fw-semibold"><?= e($row['nama']) ?></div>
                                <small class="text-muted">@<?= e($row['username']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= e($row['role_name']) ?></span></td>
                    <td>
                        <?php if ((int) $row['is_active'] === 1): ?>
                            <span class="badge bg-success-subtle text-success-emphasis">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger-emphasis">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= $row['last_login'] ? e(date('d-m-Y H:i', strtotime($row['last_login']))) : 'Belum pernah' ?></td>
                    <td class="text-end">
                        <?php if (Auth::hasPermission('users.edit')): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/users/' . $row['id'] . '/edit') ?>">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (Auth::hasPermission('users.delete') && (int) $row['id'] !== Auth::id() && (int) $row['role_is_system'] !== 1): ?>
                        <form class="d-inline" method="POST" action="<?= base_url('/users/' . $row['id'] . '/delete') ?>"
                              onsubmit="return confirm('Hapus pengguna <?= e($row['username']) ?>?')">
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
            <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $pageQs(max(1, $data['page'] - 1)) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $pageQs(min($data['pages'], $data['page'] + 1)) ?>">&raquo;</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</div>