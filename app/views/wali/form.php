<?php
use App\Core\Session;

 $isEdit = $item !== null;
 $action = $isEdit ? base_url('/wali/' . $item['id']) : base_url('/wali');
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/wali') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Wali Santri' : 'Tambah Wali Santri' ?></h1>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 720px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="nama">Nama Wali *</label>
                    <input type="text" class="form-control" id="nama" name="nama" required
                           value="<?= e($item['nama'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="no_telepon">No. Telepon *</label>
                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" required
                           value="<?= e($item['no_telepon'] ?? '') ?>" placeholder="cth: 081234567890">
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="pekerjaan">Pekerjaan</label>
                    <input type="text" class="form-control" id="pekerjaan" name="pekerjaan"
                           value="<?= e($item['pekerjaan'] ?? '') ?>">
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="alamat">Alamat</label>
                    <input type="text" class="form-control" id="alamat" name="alamat"
                           value="<?= e($item['alamat'] ?? '') ?>">
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/wali') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>