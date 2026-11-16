<?php
use App\Core\Auth;
use App\Core\Session;
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Role &amp; Hak Akses</h1>
        <p class="text-muted mb-0">Atur role beserta permission yang dimilikinya.</p>
    </div>
    <?php if (Auth::hasPermission('roles.manage')): ?>
    <a href="<?= base_url('/roles/create') ?>" class="btn btn-brand">
        <i class="bi bi-plus-lg me-1"></i> Tambah Role
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Role</th><th>Deskripsi</th><th>Pengguna</th><th>Permission</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php foreach ($roles as $r): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($r['name']) ?>
                            <?php if ((int) $r['is_system'] === 1): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis ms-1">Sistem</span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted"><?= e($r['slug']) ?></small>
                    </td>
                    <td class="text-muted small"><?= e($r['description'] ?? '-') ?></td>
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= (int) $r['users_count'] ?></span></td>
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= (int) $r['permissions_count'] ?></span></td>
                    <td class="text-end">
                        <?php if (Auth::hasPermission('roles.manage')): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/roles/' . $r['id'] . '/edit') ?>">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php if ((int) $r['is_system'] !== 1 && (int) $r['users_count'] === 0): ?>
                        <form class="d-inline" method="POST" action="<?= base_url('/roles/' . $r['id'] . '/delete') ?>"
                              onsubmit="return confirm('Hapus role <?= e($r['name']) ?>?')">
                            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>