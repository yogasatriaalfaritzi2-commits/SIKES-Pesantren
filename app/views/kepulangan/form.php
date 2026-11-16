<?php
use App\Core\Session;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/kepulangan') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0">Rekomendasi Medis Kepulangan</h1>
        <p class="text-muted small mb-0">Tahap 1 dari 4. Selanjutnya: Approval Keamanan &rarr; Pengasuhan/Kyai &rarr; Gerbang.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 760px">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('/kepulangan') ?>">
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
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tanggal_pengajuan">Tanggal Pengajuan *</label>
                    <input type="date" class="form-control" id="tanggal_pengajuan" name="tanggal_pengajuan" required
                           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+7 day')) ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="alamat_tujuan">Alamat Tujuan Pulang *</label>
                    <input type="text" class="form-control" id="alamat_tujuan" name="alamat_tujuan" required
                           placeholder="cth: Jl. Merdeka No. 5, Bandung (rumah wali)">
                </div>
                <div class="col-12">
                    <label class="form-label" for="alasan_medis">Rekomendasi / Alasan Medis *</label>
                    <textarea class="form-control" id="alasan_medis" name="alasan_medis" rows="3" required
                              placeholder="Diagnosis/ringkasan kondisi dan alasan santri perlu dipulangkan (min. 10 karakter)"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="medis_catatan">Catatan Tambahan Petugas Medis</label>
                    <textarea class="form-control" id="medis_catatan" name="medis_catatan" rows="2"
                              placeholder="Opsional: anjuran perawatan di rumah, obat dibawa, dll."></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Terbitkan Rekomendasi</button>
                <a href="<?= base_url('/kepulangan') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('santri_id')?.addEventListener('change', function () {
    const sel = document.getElementById('pemeriksaan_id');
    sel.innerHTML = '<option value="">Tanpa rujukan pemeriksaan</option>';
    if (!this.value) return;
    fetch('<?= base_url('/kepulangan/pemeriksaan-options') ?>?santri_id=' + this.value)
        .then(r => r.json())
        .then(res => res.data.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `#${p.id} — ${p.tanggal_kunjungan.slice(0,10)} — ${(p.diagnosis || '-').slice(0,60)}`;
            sel.appendChild(opt);
        }));
});
</script>