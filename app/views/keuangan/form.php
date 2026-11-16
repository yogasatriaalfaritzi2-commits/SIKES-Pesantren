<?php
use App\Core\Session;
use App\Models\Keuangan;

 $item   = (isset($item) && is_array($item)) ? $item : [];
 $isEdit = !empty($item['id']);
 $action = $isEdit ? base_url('/keuangan/' . (int) $item['id']) : base_url('/keuangan');
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/keuangan') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Transaksi' : 'Catat Transaksi' ?></h1>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 680px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label" for="jenis">Jenis *</label>
                    <select class="form-select" id="jenis" name="jenis" required>
                        <?php foreach (Keuangan::JENIS as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($item['jenis'] ?? 'masuk') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="kategori">Kategori *</label>
                    <select class="form-select" id="kategori" name="kategori" required>
                        <?php foreach (Keuangan::KATEGORI as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($item['kategori'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="jumlah">Jumlah (Rp) *</label>
                    <input type="number" class="form-control" id="jumlah" name="jumlah" required
                           min="1" step="0.01" value="<?= e((string) ($item['jumlah'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tanggal">Tanggal *</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" required
                           value="<?= e($item['tanggal'] ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="referensi">Referensi</label>
                    <input type="text" class="form-control" id="referensi" name="referensi" maxlength="100"
                           value="<?= e($item['referensi'] ?? '') ?>" placeholder="No. bukti (opsional)">
                </div>
                <div class="col-12">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <input type="text" class="form-control" id="keterangan" name="keterangan" maxlength="255"
                           value="<?= e($item['keterangan'] ?? '') ?>" placeholder="opsional">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/keuangan') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>