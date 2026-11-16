<?php
use App\Core\Auth;
use App\Core\Session;

 $isEdit = $item !== null;
 $isSelf = $isEdit && (int) $item['id'] === Auth::id();
 $action = $isEdit ? base_url('/users/' . $item['id']) : base_url('/users');
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/users') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' ?></h1>
        <p class="text-muted small mb-0">Field bertanda * wajib diisi.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 760px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="nama">Nama Lengkap *</label>
                    <input type="text" class="form-control" id="nama" name="nama" required
                           value="<?= e($item['nama'] ?? '') ?>" placeholder="cth: Ust. Ahmad Fauzi">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="username">Username *</label>
                    <input type="text" class="form-control" id="username" name="username" required
                           pattern="[a-zA-Z0-9_.]{3,50}" value="<?= e($item['username'] ?? '') ?>"
                           placeholder="huruf, angka, titik, underscore">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($item['email'] ?? '') ?>" placeholder="opsional">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="role_id">Role *</label>
                    <select class="form-select" id="role_id" name="role_id" required <?= $isSelf ? 'disabled' : '' ?>>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= (int) $r['id'] ?>"
                            <?= (int) ($item['role_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>>
                            <?= e($r['name']) ?><?= $r['is_system'] ? ' (Sistem)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isSelf): ?>
                        <input type="hidden" name="role_id" value="<?= (int) $item['role_id'] ?>">
                        <small class="text-muted">Role tidak dapat diubah pada akun sendiri.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password">
                        Password <?= $isEdit ? '(isi hanya jika ingin diganti)' : '*' ?>
                    </label>
                    <input type="password" class="form-control" id="password" name="password"
                           minlength="8" <?= $isEdit ? '' : 'required' ?> placeholder="minimal 8 karakter"
                           autocomplete="new-password">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                               <?= ($isEdit && (int) $item['is_active'] === 1) || !$isEdit ? 'checked' : '' ?>
                               <?= $isSelf ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="is_active">Akun aktif</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4">
                    <i class="bi bi-check-lg me-1"></i> Simpan
                </button>
                <a href="<?= base_url('/users') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>