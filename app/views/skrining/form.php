<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\SkriningKesehatan;

 $isEdit = $item !== null;
 $action = $isEdit ? base_url('/skrining/' . $item['id']) : base_url('/skrining');
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/skrining') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Hasil Skrining' : 'Input Skrining Kesehatan Awal' ?></h1>
        <p class="text-muted small mb-0">Field bertanda * wajib diisi. IMT dihitung otomatis.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 960px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <!-- ===== Santri & Tanggal ===== -->
            <div class="fw-semibold text-brand text-uppercase small mb-3">
                <i class="bi bi-person me-1"></i> Santri &amp; Waktu Skrining
            </div>
            <div class="row g-3 mb-2">
                <?php if ($isEdit): ?>
                <div class="col-md-7">
                    <label class="form-label">Santri</label>
                    <input type="text" class="form-control" disabled
                           value="<?= e($item['santri_nama']) ?> (NIS <?= e($item['nis']) ?>)">
                    <input type="hidden" name="santri_id" value="<?= (int) $item['santri_id'] ?>">
                </div>
                <?php else: ?>
                <div class="col-md-7">
                    <label class="form-label" for="santri_id">Santri *</label>
                    <select class="form-select" id="santri_id" name="santri_id" required>
                        <option value="">— Pilih santri —</option>
                        <?php foreach ($santriOptions as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === (int) $selectedSantriId ? 'selected' : '' ?>>
                            <?= e($s['nis']) ?> — <?= e($s['nama']) ?> (<?= e($s['jenis_kelamin']) ?><?= $s['status'] === 'nonaktif' ? ', nonaktif' : '' ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-5">
                    <label class="form-label" for="tanggal_skrining">Tanggal Skrining *</label>
                    <input type="date" class="form-control" id="tanggal_skrining" name="tanggal_skrining" required
                           value="<?= e($item['tanggal_skrining'] ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <!-- ===== Kondisi Khusus ===== -->
            <div class="fw-semibold text-brand text-uppercase small mt-4 mb-3">
                <i class="bi bi-clipboard2-plus me-1"></i> Riwayat &amp; Kondisi Khusus
            </div>
            <div class="row g-3">
                <?php foreach (SkriningKesehatan::KONDISI as $field => $label):
                    $val = $item[$field] ?? 'tidak';
                    $ket = $item[$field . '_ket'] ?? '';
                ?>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <label class="form-label fw-semibold mb-2"><?= e($label) ?> *</label>
                        <div class="d-flex gap-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input kondisi-toggle" type="radio"
                                       name="<?= e($field) ?>" value="tidak" id="<?= e($field) ?>_t"
                                       data-target="ket-<?= e($field) ?>" <?= $val !== 'ya' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= e($field) ?>_t">Tidak</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input kondisi-toggle" type="radio"
                                       name="<?= e($field) ?>" value="ya" id="<?= e($field) ?>_y"
                                       data-target="ket-<?= e($field) ?>" <?= $val === 'ya' ? 'checked' : '' ?>>
                                <label class="form-check-label text-danger fw-semibold" for="<?= e($field) ?>_y">Ya</label>
                            </div>
                        </div>
                        <input type="text" class="form-control form-control-sm kondisi-ket"
                               id="ket-<?= e($field) ?>" name="<?= e($field) ?>_ket"
                               value="<?= e($ket) ?>" placeholder="Jelaskan kondisinya (wajib jika Ya)"
                               style="display: <?= $val === 'ya' ? 'block' : 'none' ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ===== Antropometri ===== -->
            <div class="fw-semibold text-brand text-uppercase small mt-4 mb-3">
                <i class="bi bi-rulers me-1"></i> Antropometri &amp; Golongan Darah
            </div>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label class="form-label" for="tinggi_cm">Tinggi Badan (cm)</label>
                    <input type="number" class="form-control" id="tinggi_cm" name="tinggi_cm"
                           step="0.1" min="30" max="250" value="<?= e((string) ($item['tinggi_cm'] ?? '')) ?>" placeholder="cth: 165">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="berat_kg">Berat Badan (kg)</label>
                    <input type="number" class="form-control" id="berat_kg" name="berat_kg"
                           step="0.1" min="2" max="300" value="<?= e((string) ($item['berat_kg'] ?? '')) ?>" placeholder="cth: 55">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="golongan_darah">Golongan Darah</label>
                    <select class="form-select" id="golongan_darah" name="golongan_darah">
                        <?php foreach (['-', 'A', 'B', 'AB', 'O'] as $gd): ?>
                        <option value="<?= $gd ?>" <?= ($item['golongan_darah'] ?? '-') === $gd ? 'selected' : '' ?>>
                            <?= $gd === '-' ? 'Belum diketahui' : $gd ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="rhesus">Rhesus</label>
                    <select class="form-select" id="rhesus" name="rhesus">
                        <?php foreach (['-', 'positif', 'negatif'] as $rh): ?>
                        <option value="<?= $rh ?>" <?= ($item['rhesus'] ?? '-') === $rh ? 'selected' : '' ?>>
                            <?= $rh === '-' ? 'Belum diketahui' : ucfirst($rh) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <div class="alert alert-info py-2 mb-0 small" id="imtPreview">IMT: - (terisi otomatis setelah tinggi &amp; berat diisi)</div>
                </div>
            </div>

            <!-- ===== Pemeriksaan ===== -->
            <div class="fw-semibold text-brand text-uppercase small mt-4 mb-3">
                <i class="bi bi-clipboard2-check me-1"></i> Hasil Pemeriksaan
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="buta_warna">Pemeriksaan Buta Warna</label>
                    <select class="form-select" id="buta_warna" name="buta_warna">
                        <?php foreach (['belum' => 'Belum diperiksa', 'normal' => 'Normal', 'kelainan' => 'Ada Kelainan'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($item['buta_warna'] ?? 'belum') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="form-control form-control-sm mt-2" name="buta_warna_ket"
                           value="<?= e($item['buta_warna_ket'] ?? '') ?>" placeholder="Keterangan (wajib jika kelainan)">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="gigi_status">Pemeriksaan Gigi</label>
                    <select class="form-select" id="gigi_status" name="gigi_status">
                        <?php foreach (['belum' => 'Belum diperiksa', 'baik' => 'Baik', 'perlu_perawatan' => 'Perlu Perawatan', 'perlu_rujukan' => 'Perlu Rujukan'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($item['gigi_status'] ?? 'belum') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="form-control form-control-sm mt-2" name="gigi_ket"
                           value="<?= e($item['gigi_ket'] ?? '') ?>" placeholder="Keterangan gigi">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="fisik_status">Pemeriksaan Fisik Umum</label>
                    <select class="form-select" id="fisik_status" name="fisik_status">
                        <?php foreach (['belum' => 'Belum diperiksa', 'sehat' => 'Sehat', 'ada_keluhan' => 'Ada Keluhan', 'perlu_rujukan' => 'Perlu Rujukan'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($item['fisik_status'] ?? 'belum') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="form-control form-control-sm mt-2" name="fisik_ket"
                           value="<?= e($item['fisik_ket'] ?? '') ?>" placeholder="Keterangan kondisi fisik">
                </div>
                <div class="col-12">
                    <label class="form-label" for="catatan_medis">Catatan Tenaga Medis</label>
                    <textarea class="form-control" id="catatan_medis" name="catatan_medis" rows="3"
                              placeholder="Catatan, anjuran tindak lanjut, dll."><?= e($item['catatan_medis'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/skrining') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // Toggle keterangan kondisi: tampil hanya jika radio "Ya" dipilih
    document.querySelectorAll('.kondisi-toggle').forEach(function (r) {
        r.addEventListener('change', function () {
            const target = document.getElementById(r.dataset.target);
            if (target) target.style.display = (r.value === 'ya') ? 'block' : 'none';
        });
    });

    // Preview IMT di sisi client (nilai tersimpan tetap dihitung ulang oleh server)
    const t = document.getElementById('tinggi_cm');
    const b = document.getElementById('berat_kg');
    const out = document.getElementById('imtPreview');
    function calc() {
        const tv = parseFloat(t.value), bv = parseFloat(b.value);
        if (tv > 0 && bv > 0) {
            const imt = bv / Math.pow(tv / 100, 2);
            let kat = 'Normal';
            if (imt < 18.5) kat = 'Berat Badan Kurang';
            else if (imt >= 27.0) kat = 'Obesitas';
            else if (imt >= 25.0) kat = 'Berat Badan Berlebih';
            out.textContent = 'IMT: ' + imt.toFixed(2) + ' (' + kat + ') — kategori skrining, bukan diagnosis';
        } else {
            out.textContent = 'IMT: - (terisi otomatis setelah tinggi & berat diisi)';
        }
    }
    if (t && b && out) { t.addEventListener('input', calc); b.addEventListener('input', calc); calc(); }
})();
</script>