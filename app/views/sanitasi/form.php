<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\InspeksiKamar;

/* ============================================================
   NORMALISASI DEFENSIF
   - $item bisa null, [], atau baris lengkap (mode edit)
   - Mode edit ditentukan dari id yang ADA, bukan dari !== null
   - Semua akses array memakai ?? default -> nol warning
   ============================================================ */
 $item   = (isset($item) && is_array($item)) ? $item : [];
 $isEdit = !empty($item['id']);
 $action = $isEdit ? base_url('/sanitasi/inspeksi/' . (int) $item['id']) : base_url('/sanitasi/inspeksi');

 $valKamar   = (int) ($item['kamar_id'] ?? 0);
 $preKamar   = (int) ($preKamarId ?? 0);
 $selectedKamar = $isEdit ? $valKamar : $preKamar;

 $valTanggal = ($item['tanggal_inspeksi'] ?? '') !== ''
    ? $item['tanggal_inspeksi']
    : date('Y-m-d');
 $valCatatan = $item['catatan_petugas'] ?? '';
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/sanitasi/inspeksi') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Inspeksi Kamar' : 'Input Inspeksi Kamar' ?></h1>
        <p class="text-muted small mb-0">Nilai tiap parameter: Baik = 2 poin, Cukup = 1 poin, Buruk = 0 poin. Skor maksimal 16.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 860px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>" id="inspeksiForm">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <label class="form-label" for="kamar_id">Kamar *</label>
                    <select class="form-select" id="kamar_id" name="kamar_id" required
                            <?= $isEdit ? 'data-current="' . $valKamar . '"' : '' ?>>
                        <option value="">— Pilih kamar —</option>
                        <?php
                        $grouped = [];
                        foreach ($kamarList as $k) {
                            $grouped[$k['asrama_nama'] . ' (' . $k['asrama_jenis'] . ') — ' . $k['blok_nama']][] = $k;
                        }
                        foreach ($grouped as $label => $kamars): ?>
                        <optgroup label="<?= e($label) ?>">
                            <?php foreach ($kamars as $k): ?>
                            <option value="<?= (int) $k['id'] ?>"
                                <?= $selectedKamar === (int) $k['id'] ? 'selected' : '' ?>>
                                Kamar <?= e($k['kamar_nama']) ?> (kapasitas <?= (int) $k['kapasitas'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="tanggal_inspeksi">Tanggal Inspeksi *</label>
                    <input type="date" class="form-control" id="tanggal_inspeksi" name="tanggal_inspeksi" required
                           value="<?= e($valTanggal) ?>" max="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <!-- 8 parameter -->
            <div class="table-responsive border rounded-3 mb-3">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="table-light">
                            <th style="width:45%">Parameter</th>
                            <th class="text-center">Baik (2)</th>
                            <th class="text-center">Cukup (1)</th>
                            <th class="text-center">Buruk (0)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (InspeksiKamar::PARAMS as $field => $label): ?>
                        <tr>
                            <td class="fw-semibold small"><?= e($label) ?></td>
                            <?php foreach (['baik', 'cukup', 'buruk'] as $valOpt): ?>
                            <td class="text-center">
                                <div class="form-check d-inline-block">
                                    <input class="form-check-input param-radio" type="radio"
                                           name="<?= e($field) ?>" value="<?= $valOpt ?>"
                                           id="<?= e($field) ?>_<?= $valOpt ?>"
                                           <?= ($item[$field] ?? 'baik') === $valOpt ? 'checked' : '' ?>>
                                    <label class="form-check-label visually-hidden"
                                           for="<?= e($field) ?>_<?= $valOpt ?>"><?= $valOpt ?></label>
                                </div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info py-2 small" id="skorPreview">Skor: -</div>

            <div class="mb-3">
                <label class="form-label" for="catatan_petugas">Catatan Petugas</label>
                <textarea class="form-control" id="catatan_petugas" name="catatan_petugas" rows="3"
                          placeholder="Temuan, anjuran perbaikan, tindak lanjut..."><?= e($valCatatan) ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan Inspeksi</button>
                <a href="<?= base_url('/sanitasi/inspeksi') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const out  = document.getElementById('skorPreview');
    const form = document.getElementById('inspeksiForm');

    function label(persen) {
        if (persen >= 75) return 'Baik';
        if (persen >= 50) return 'Perlu Perhatian';
        return 'Risiko Tinggi';
    }
    function calc() {
        let skor = 0;
        document.querySelectorAll('.param-radio:checked').forEach(r => {
            skor += ({ baik: 2, cukup: 1, buruk: 0 })[r.value] ?? 0;
        });
        const persen = (skor / 16 * 100).toFixed(2);
        out.textContent = 'Skor: ' + skor + '/16 (' + persen + '%) — Status: ' + label(parseFloat(persen));
    }
    form?.addEventListener('change', e => { if (e.target.classList.contains('param-radio')) calc(); });
    calc();
})();
</script>