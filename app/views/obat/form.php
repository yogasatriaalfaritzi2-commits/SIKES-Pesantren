<?php
use App\Core\Session;
use App\Models\Obat;

 $isEdit = $item !== null;
 $action = $isEdit ? base_url('/obat/' . $item['id']) : base_url('/obat');
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/obat') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Obat' : 'Tambah Obat' ?></h1>
        <p class="text-muted small mb-0">Stok awal selalu 0 — isi lewat menu <strong>Stok Masuk</strong> agar tercatat di histori.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 720px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="nama">Nama Obat *</label>
                    <input type="text" class="form-control" id="nama" name="nama" required
                           value="<?= e($item['nama'] ?? '') ?>" placeholder="cth: Paracetamol 500mg">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="kategori">Kategori *</label>
                    <select class="form-select" id="kategori" name="kategori" required>
                        <?php foreach (Obat::KATEGORI as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($item['kategori'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="satuan">Satuan *</label>
                    <select class="form-select" id="satuan" name="satuan" required>
                        <?php foreach (Obat::SATUAN as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($item['satuan'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="minimum_stok">Batas Stok Minimum *</label>
                    <input type="number" class="form-control" id="minimum_stok" name="minimum_stok" required
                           min="0" max="100000" value="<?= e((string) ($item['minimum_stok'] ?? 10)) ?>">
                    <small class="text-muted">Peringatan stok menipis aktif saat stok ≤ batas ini.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="harga">Harga per Satuan (Rp) *</label>
                    <input type="number" class="form-control" id="harga" name="harga" required
                           min="0" step="0.01" value="<?= e((string) ($item['harga'] ?? 0)) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tanggal_kedaluwarsa">Tanggal Kedaluwarsa *</label>
                    <input type="date" class="form-control" id="tanggal_kedaluwarsa" name="tanggal_kedaluwarsa" required
                           value="<?= e($item['tanggal_kedaluwarsa'] ?? date('Y-m-d', strtotime('+1 year'))) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="catatan">Catatan</label>
                    <input type="text" class="form-control" id="catatan" name="catatan" maxlength="255"
                           value="<?= e($item['catatan'] ?? '') ?>" placeholder="opsional">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/obat') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>