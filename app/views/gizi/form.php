<?php
use App\Core\Session;
use App\Models\GiziDiet;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/gizi') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0">Input Rekomendasi Diet Khusus</h1>
        <p class="text-muted small mb-0">Diet disampaikan ke dapur untuk tanggal yang dipilih.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="alert alert-light border small text-muted">
    <i class="bi bi-shield-check me-1"></i>
    Rekomendasi diet adalah <strong>isian manual tenaga medis</strong> berdasarkan kondisi santri —
    sistem <strong>tidak menghasilkan rekomendasi diet otomatis</strong>.
</div>

<div class="card border-0 shadow-sm" style="max-width: 720px">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('/gizi') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
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
                <div class="col-md-5">
                    <label class="form-label" for="tanggal">Tanggal Efektif *</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" required
                           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+30 day')) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="jenis_diet">Jenis Diet *</label>
                    <select class="form-select" id="jenis_diet" name="jenis_diet" required>
                        <option value="">— Pilih jenis —</option>
                        <?php foreach (GiziDiet::JENIS as $v => $l): ?>
                        <option value="<?= $v ?>"><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-7" id="jenisLainWrap" style="display:none">
                    <label class="form-label" for="jenis_lain">Sebutkan Jenis Diet Lainnya *</label>
                    <input type="text" class="form-control" id="jenis_lain" name="jenis_lain"
                           maxlength="100" placeholder="cth: Rendah garam, Alami tanpa MSG">
                </div>
                <div class="col-12">
                    <label class="form-label" for="catatan_medis">Catatan Tenaga Medis</label>
                    <textarea class="form-control" id="catatan_medis" name="catatan_medis" rows="3"
                              placeholder="Panduan untuk dapur: tekstur, porsi, bahan yang dihindari... (opsional)"></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan Rekomendasi</button>
                <a href="<?= base_url('/gizi') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const jenis = document.getElementById('jenis_diet');
    const wrap  = document.getElementById('jenisLainWrap');
    function toggle() {
        if (wrap) wrap.style.display = (jenis.value === 'lainnya') ? '' : 'none';
    }
    jenis?.addEventListener('change', toggle);
    toggle();
})();
</script>