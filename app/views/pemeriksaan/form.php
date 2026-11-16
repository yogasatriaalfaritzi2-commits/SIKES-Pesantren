<?php
use App\Core\Session;
use App\Models\Pemeriksaan;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/pemeriksaan') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0">Registrasi Kunjungan</h1>
        <p class="text-muted small mb-0">Catat santri yang datang ke poskestren. Field bertanda * wajib diisi.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 760px">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('/pemeriksaan') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label" for="santri_id">Santri *</label>
                    <select class="form-select" id="santri_id" name="santri_id" required>
                        <option value="">— Pilih santri —</option>
                        <?php foreach ($santriOptions as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === $preSantriId ? 'selected' : '' ?>>
                            <?= e($s['nis']) ?> — <?= e($s['nama']) ?> (<?= e($s['jenis_kelamin']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="jenis_kunjungan">Jenis Kunjungan *</label>
                    <select class="form-select" id="jenis_kunjungan" name="jenis_kunjungan" required>
                        <?php foreach (Pemeriksaan::JENIS_LABEL as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $v === 'rawat_jalan' ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="tanggal_kunjungan">Tanggal &amp; Jam Kunjungan *</label>
                    <input type="datetime-local" class="form-control" id="tanggal_kunjungan" name="tanggal_kunjungan"
                           required value="<?= e(date('Y-m-d\TH:i')) ?>" max="<?= e(date('Y-m-d\TH:i')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="keluhan">Keluhan *</label>
                    <textarea class="form-control" id="keluhan" name="keluhan" rows="2" required
                              placeholder="Keluhan utama santri, cth: demam 3 hari, badan lemas"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="pemeriksaan_awal">Pemeriksaan Awal / Triage</label>
                    <textarea class="form-control" id="pemeriksaan_awal" name="pemeriksaan_awal" rows="2"
                              placeholder="Kondisi awal saat tiba di poskestren (opsional)"></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Registrasi</button>
                <a href="<?= base_url('/pemeriksaan') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>