<?php
use App\Core\Session;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/obat') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h4 fw-bold mb-0">Catat Stok Masuk</h1>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 640px">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('/obat/masuk') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="obat_id">Obat *</label>
                    <select class="form-select" id="obat_id" name="obat_id" required>
                        <option value="">— Pilih obat —</option>
                        <?php foreach ($obatList as $o): ?>
                        <option value="<?= (int) $o['id'] ?>">
                            <?= e($o['nama']) ?> (stok: <?= (int) $o['stok'] ?> <?= e($o['satuan']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="jumlah">Jumlah Masuk *</label>
                    <input type="number" class="form-control" id="jumlah" name="jumlah" required min="1" max="100000">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="harga_satuan">Harga Satuan (Rp) *</label>
                    <input type="number" class="form-control" id="harga_satuan" name="harga_satuan" required
                           min="0" step="0.01" placeholder="harga beli saat ini">
                </div>
                <div class="col-12">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <input type="text" class="form-control" id="keterangan" name="keterangan" maxlength="255"
                           placeholder="cth: Pengadaan bulan ini, No. Faktur #123 (opsional)">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-box-arrow-in-down me-1"></i> Simpan Stok Masuk</button>
                <a href="<?= base_url('/obat') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>