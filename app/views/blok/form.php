<?php
use App\Core\Session;

/* ============================================================
   NORMALISASI DEFENSIF
   - $item bisa null, [], atau baris lengkap (mode edit)
   - Mode edit ditentukan dari id yang ADA, bukan dari !== null
   - Semua akses array memakai ?? default -> nol warning
   ============================================================ */
 $item   = (isset($item) && is_array($item)) ? $item : [];
 $isEdit = !empty($item['id']);
 $action = $isEdit ? base_url('/blok/' . (int) $item['id']) : base_url('/blok');

 $valAsramaId  = (int) ($item['asrama_id'] ?? 0);
 $valNama      = $item['nama'] ?? '';
 $valKeterangan= $item['keterangan'] ?? '';

 $asramaOptions = (isset($asramaOptions) && is_array($asramaOptions)) ? $asramaOptions : [];
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/blok') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Blok' : 'Tambah Blok' ?></h1>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 640px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label" for="asrama_id">Asrama *</label>
                    <select class="form-select" id="asrama_id" name="asrama_id" required>
                        <option value="">— Pilih asrama —</option>
                        <?php foreach ($asramaOptions as $a): ?>
                        <option value="<?= (int) $a['id'] ?>" <?= $valAsramaId === (int) $a['id'] ? 'selected' : '' ?>>
                            <?= e($a['nama']) ?> (<?= e($a['jenis']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="nama">Nama Blok *</label>
                    <input type="text" class="form-control" id="nama" name="nama" required
                           value="<?= e($valNama) ?>" placeholder="cth: Blok A">
                </div>
                <div class="col-12">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <input type="text" class="form-control" id="keterangan" name="keterangan"
                           value="<?= e($valKeterangan) ?>">
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/blok') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>