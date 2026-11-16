<?php
use App\Core\Session;

 $isEdit = $item !== null;
 $locked = $isEdit && (int) $item['is_system'] === 1;
 $action = $isEdit ? base_url('/roles/' . $item['id']) : base_url('/roles');
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/roles') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Role' : 'Tambah Role' ?></h1>
        <p class="text-muted small mb-0">Centang permission yang dimiliki role ini.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if ($locked): ?>
    <div class="alert alert-warning"><i class="bi bi-lock me-2"></i>
        Role sistem <strong><?= e($item['name']) ?></strong> dilindungi dan tidak dapat diubah.
        Role ini otomatis memiliki akses ke semua fitur.
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label" for="name">Nama Role *</label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?= e($item['name'] ?? '') ?>" <?= $locked ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="slug">Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug"
                           value="<?= e($item['slug'] ?? '') ?>" <?= $locked ? 'disabled' : '' ?>
                           placeholder="otomatis dari nama">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="description">Deskripsi</label>
                    <input type="text" class="form-control" id="description" name="description"
                           value="<?= e($item['description'] ?? '') ?>" <?= $locked ? 'disabled' : '' ?>>
                </div>
            </div>

            <div class="row g-3">
                <?php foreach ($permissionsGrouped as $module => $perms): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-uppercase small text-brand"><?= e($module) ?></strong>
                            <?php if (!$locked): ?>
                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none select-group"
                                    data-target="g-<?= e($module) ?>">Pilih semua</button>
                            <?php endif; ?>
                        </div>
                        <?php foreach ($perms as $p): ?>
                        <div class="form-check">
                            <input class="form-check-input group-member" type="checkbox"
                                   data-group="g-<?= e($module) ?>"
                                   name="permissions[]" value="<?= (int) $p['id'] ?>"
                                   id="p<?= (int) $p['id'] ?>"
                                   <?= in_array((int) $p['id'], $selectedPermissions, true) ? 'checked' : '' ?>
                                   <?= $locked ? 'disabled' : '' ?>>
                            <label class="form-check-label small" for="p<?= (int) $p['id'] ?>">
                                <?= e($p['name']) ?>
                                <br><code class="text-muted" style="font-size:.7rem"><?= e($p['slug']) ?></code>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$locked): ?>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/roles') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
// Auto-isi slug dari nama (hanya saat tambah & slug belum disentuh)
const nameEl = document.getElementById('name');
const slugEl = document.getElementById('slug');
nameEl?.addEventListener('input', () => {
    if (slugEl?.dataset.touched) return;
    if (slugEl) slugEl.value = nameEl.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
});
slugEl?.addEventListener('input', () => slugEl.dataset.touched = '1');

// Pilih / batal pilih semua checkbox dalam satu modul
document.querySelectorAll('.select-group').forEach(btn => {
    btn.addEventListener('click', () => {
        const boxes = document.querySelectorAll(`.group-member[data-group="${btn.dataset.target}"]`);
        const allChecked = [...boxes].every(b => b.checked);
        boxes.forEach(b => b.checked = !allChecked);
    });
});
</script>