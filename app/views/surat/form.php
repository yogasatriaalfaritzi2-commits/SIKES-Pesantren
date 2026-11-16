<?php
use App\Core\Session;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/surat') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0">Terbitkan E-Surat Izin Sakit</h1>
        <p class="text-muted small mb-0">Nomor surat &amp; token QR dibuat otomatis. Field bertanda * wajib diisi.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 760px">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('/surat') ?>">
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
                <div class="col-md-3">
                    <label class="form-label" for="tanggal_mulai">Tanggal Mulai *</label>
                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required
                           value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="tanggal_selesai">Tanggal Selesai *</label>
                    <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required
                           value="<?= date('Y-m-d', strtotime('+2 day')) ?>">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="alert alert-info py-2 small mb-0 w-100">Durasi maksimal 30 hari. Tidak boleh bertumpang tindih dengan surat aktif lain milik santri yang sama.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="keterangan_medis">Keterangan Medis (Administratif) *</label>
                    <textarea class="form-control" id="keterangan_medis" name="keterangan_medis" rows="3" required
                              placeholder="Ringkasan untuk keperluan administrasi, cth: Demam/ISPA — dianjurkan istirahat di rumah 3 hari."></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Terbitkan Surat</button>
                <a href="<?= base_url('/surat') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('santri_id')?.addEventListener('change', function () {
    const sel = document.getElementById('pemeriksaan_id');
    sel.innerHTML = '<option value="">Tanpa rujukan pemeriksaan</option>';
    if (!this.value) return;
    fetch('<?= base_url('/surat/pemeriksaan-options') ?>?santri_id=' + this.value)
        .then(r => r.json())
        .then(res => {
            res.data.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = `#${p.id} — ${p.tanggal_kunjungan.slice(0,10)} — ${(p.diagnosis || '-').slice(0,60)}`;
                sel.appendChild(opt);
            });
        });
});
</script>