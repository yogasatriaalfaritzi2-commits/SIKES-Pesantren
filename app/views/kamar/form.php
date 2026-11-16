<?php
use App\Core\Session;

/* ============================================================
   NORMALISASI DEFENSIF
   - $item bisa null, [], atau baris lengkap (mode edit)
   - Mode edit ditentukan dari id yang ADA, bukan dari !== null
   - Semua akses array memakai ?? default -> nol warning
   ============================================================ */
 $item    = (isset($item) && is_array($item)) ? $item : [];
 $isEdit  = !empty($item['id']);
 $action  = $isEdit ? base_url('/kamar/' . (int) $item['id']) : base_url('/kamar');

 $valBlokId    = (int) ($item['blok_id'] ?? 0);
 $valNama      = $item['nama'] ?? '';
 $valKapasitas = (int) ($item['kapasitas'] ?? 8);

 $blokList = (isset($blokList) && is_array($blokList)) ? $blokList : [];

/* Kelompokkan blok per asrama untuk optgroup */
 $grouped = [];
foreach ($blokList as $b) {
    $grouped[$b['asrama_nama'] . ' (' . $b['asrama_jenis'] . ')'][] = $b;
}
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/kamar') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Kamar' : 'Tambah Kamar' ?></h1>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 640px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="blok_id">Blok (Asrama) *</label>
                    <select class="form-select" id="blok_id" name="blok_id" required>
                        <option value="">— Pilih blok —</option>
                        <?php foreach ($grouped as $label => $bloks): ?>
                        <optgroup label="<?= e($label) ?>">
                            <?php foreach ($bloks as $b): ?>
                            <option value="<?= (int) $b['id'] ?>" <?= $valBlokId === (int) $b['id'] ? 'selected' : '' ?>>
                                <?= e($b['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="kapasitas">Kapasitas *</label>
                    <input type="number" class="form-control" id="kapasitas" name="kapasitas"
                           min="1" max="50" required value="<?= $valKapasitas > 0 ? $valKapasitas : 8 ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="nama">Nama / No. Kamar *</label>
                    <input type="text" class="form-control" id="nama" name="nama" required
                           value="<?= e($valNama) ?>" placeholder="cth: 101">
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/kamar') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>