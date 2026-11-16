<?php
use App\Core\Session;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/rujukan') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0">Terbitkan Surat Rujukan</h1>
        <p class="text-muted small mb-0">Nomor surat dibuat otomatis. Field bertanda * wajib diisi.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="alert alert-light border small text-muted">
    <i class="bi bi-shield-check me-1"></i>
    Seluruh isi klinis surat adalah <strong>isian manual tenaga medis</strong> —
    sistem <strong>tidak menghasilkan diagnosis atau rekomendasi otomatis</strong>.
</div>

<div class="card border-0 shadow-sm" style="max-width: 860px">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('/rujukan') ?>">
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
                    <label class="form-label" for="pemeriksaan_id">Rujukan Pemeriksaan</label>
                    <select class="form-select" id="pemeriksaan_id" name="pemeriksaan_id">
                        <option value="">— Pilih santri dulu —</option>
                    </select>
                    <small class="text-muted">Opsional: dasar pemeriksaan klinik.</small>
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="tujuan_faskes">Tujuan Fasilitas Kesehatan *</label>
                    <input type="text" class="form-control" id="tujuan_faskes" name="tujuan_faskes" required
                           placeholder="cth: RSUD Kota Bandung — Poli Paru">
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="tanggal_rujukan">Tanggal Surat *</label>
                    <input type="date" class="form-control" id="tanggal_rujukan" name="tanggal_rujukan" required
                           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="keluhan">Keluhan *</label>
                    <textarea class="form-control" id="keluhan" name="keluhan" rows="2" required
                              placeholder="Keluhan utama santri"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="hasil_pemeriksaan">Hasil Pemeriksaan *</label>
                    <textarea class="form-control" id="hasil_pemeriksaan" name="hasil_pemeriksaan" rows="3" required
                              placeholder="Temuan pemeriksaan fisik/vital di poskestren"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="diagnosis_awal">Diagnosis / Keterangan Awal Tenaga Medis *</label>
                    <input type="text" class="form-control" id="diagnosis_awal" name="diagnosis_awal" required
                           placeholder="Kesimpulan awal oleh tenaga medis">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tindakan_sementara">Tindakan Sementara</label>
                    <textarea class="form-control" id="tindakan_sementara" name="tindakan_sementara" rows="2"
                              placeholder="Pertolongan awal yang telah diberikan (opsional)"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="rekomendasi">Rekomendasi *</label>
                    <textarea class="form-control" id="rekomendasi" name="rekomendasi" rows="2" required
                              placeholder="Rekomendasi tindak lanjut di faskes tujuan"></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Terbitkan Surat</button>
                <a href="<?= base_url('/rujukan') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('santri_id')?.addEventListener('change', function () {
    const sel = document.getElementById('pemeriksaan_id');
    sel.innerHTML = '<option value="">Tanpa rujukan pemeriksaan</option>';
    if (!this.value) return;
    fetch('<?= base_url('/rujukan/pemeriksaan-options') ?>?santri_id=' + this.value)
        .then(r => r.json())
        .then(res => res.data.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `#${p.id} — ${p.tanggal_kunjungan.slice(0,10)} — ${(p.diagnosis || '-').slice(0,60)}`;
            sel.appendChild(opt);
        }));
});
</script>