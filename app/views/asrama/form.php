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
 $action = $isEdit ? base_url('/asrama/' . (int) $item['id']) : base_url('/asrama');

 $valNama  = $item['nama'] ?? '';
 $valJenis = $item['jenis'] ?? '';
 $valLokasi = $item['lokasi'] ?? '';
 $valKeterangan = $item['keterangan'] ?? '';
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/asrama') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Asrama' : 'Tambah Asrama' ?></h1>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 720px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="nama">Nama Asrama *</label>
                    <input type="text" class="form-control" id="nama" name="nama" required
                           value="<?= e($valNama) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="jenis">Jenis *</label>
                    <select class="form-select" id="jenis" name="jenis" required>
                        <option value="">— Pilih —</option>
                        <option value="putra" <?= $valJenis === 'putra' ? 'selected' : '' ?>>Putra</option>
                        <option value="putri" <?= $valJenis === 'putri' ? 'selected' : '' ?>>Putri</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="lokasi">Lokasi</label>
                    <input type="text" class="form-control" id="lokasi" name="lokasi"
                           value="<?= e($valLokasi) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="2"><?= e($valKeterangan) ?></textarea>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/asrama') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>