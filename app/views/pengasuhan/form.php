<?php
use App\Core\Session;
use App\Models\Pengasuhan;

 $item   = (isset($item) && is_array($item)) ? $item : [];
 $isEdit = !empty($item['id']);
 $action = $isEdit ? base_url('/pengasuhan/' . (int) $item['id']) : base_url('/pengasuhan');
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/pengasuhan') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Catatan Pengasuhan' : 'Catat Perizinan / Pelanggaran' ?></h1>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 720px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <?php if ($isEdit): ?>
                <div class="col-md-7">
                    <label class="form-label">Santri</label>
                    <input type="text" class="form-control" disabled value="<?= e($item['santri_nama'] ?? '') ?>">
                </div>
                <?php else: ?>
                <div class="col-md-7">
                    <label class="form-label" for="santri_id">Santri *</label>
                    <select class="form-select" id="santri_id" name="santri_id" required>
                        <option value="">— Pilih santri —</option>
                        <?php foreach ($santriOptions as $s): ?>
                        <option value="<?= (int) $s['id'] ?>">
                            <?= e($s['nis']) ?> — <?= e($s['nama']) ?> (<?= e($s['jenis_kelamin']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-5">
                    <label class="form-label" for="jenis">Jenis *</label>
                    <select class="form-select" id="jenis" name="jenis" required>
                        <?php foreach (Pengasuhan::JENIS as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($item['jenis'] ?? '') === $v ? 'selected' : '' ?>><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tanggal">Tanggal *</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" required
                           value="<?= e($item['tanggal'] ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="keterangan">Keterangan *</label>
                    <input type="text" class="form-control" id="keterangan" name="keterangan" required
                           value="<?= e($item['keterangan'] ?? '') ?>"
                           placeholder="cth: Izin pulang 3 hari / Keluar malam tanpa izin">
                </div>
                <div class="col-12">
                    <label class="form-label" for="tindakan">Tindakan / Tindak Lanjut</label>
                    <input type="text" class="form-control" id="tindakan" name="tindakan" maxlength="255"
                           value="<?= e($item['tindakan'] ?? '') ?>" placeholder="opsional — pembinaan, sanksi, dsb.">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/pengasuhan') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>