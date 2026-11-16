<?php
use App\Core\Session;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/obat') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h4 fw-bold mb-0">Catat Pemakaian Obat</h1>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="alert alert-light border small text-muted">
    <i class="bi bi-shield-check me-1"></i>
    Pemakaian mengurangi stok secara <strong>atomik (database transaction)</strong>:
    stok tidak mencukupi atau obat kedaluwarsa akan otomatis ditolak.
</div>

<div class="card border-0 shadow-sm" style="max-width: 720px">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('/obat/pemakaian') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="obat_id">Obat *</label>
                    <select class="form-select" id="obat_id" name="obat_id" required>
                        <option value="">— Pilih obat —</option>
                        <?php foreach ($obatList as $o): ?>
                        <option value="<?= (int) $o['id'] ?>">
                            <?= e($o['nama']) ?> (stok: <?= (int) $o['stok'] ?> <?= e($o['satuan']) ?>
                            <?= strtotime((string) $o['tanggal_kedaluwarsa']) < strtotime(date('Y-m-d')) ? ' — KEDALUWARSA' : '' ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="jumlah">Jumlah Digunakan *</label>
                    <input type="number" class="form-control" id="jumlah" name="jumlah" required min="1" max="100000">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="pemeriksaan_id">Terkait Pemeriksaan (ID)</label>
                    <input type="number" class="form-control" id="pemeriksaan_id" name="pemeriksaan_id" min="0"
                           placeholder="opsional: ID pemeriksaan terkait">
                </div>
                <div class="col-12">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <input type="text" class="form-control" id="keterangan" name="keterangan" maxlength="255"
                           placeholder="cth: pemberian ke santri NIS 2401001 (opsional)">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-capsule me-1"></i> Simpan Pemakaian</button>
                <a href="<?= base_url('/obat') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>