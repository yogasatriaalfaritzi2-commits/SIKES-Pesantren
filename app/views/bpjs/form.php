<?php
use App\Core\Session;
use App\Models\Bpjs;

/* ============================================================
   NORMALISASI DEFENSIF + SELF-HEALING
   - $item bisa null, [], atau baris lengkap (mode edit)
   - Bila edit tanpa data, ID diambil dari URL /bpjs/{id}/edit
     dan record di-fetch langsung dari model (satu file, tanpa
     mengubah controller/model lain).
   - Semua akses array memakai ?? default -> nol warning.
   ============================================================ */
 $item = (isset($item) && is_array($item)) ? $item : [];

 $editId = (int) ($item['id'] ?? 0);

if ($editId === 0 && preg_match('#^/bpjs/(\d+)/edit$#', current_path(), $m)) {
    $editId = (int) $m[1];
    try {
        $fetched = (new Bpjs())->findDetail($editId);
        if ($fetched) {
            $item = $fetched;
        }
    } catch (\Throwable $e) {
        // biarkan kosong — guard di bawah yang menangani
    }
}

 $isEdit = $editId > 0;

 $santriId      = (int) ($item['santri_id'] ?? 0);
 $santriNama    = $item['santri_nama'] ?? ($item['nama'] ?? '');
 $nomorBpjs     = $item['nomor_bpjs'] ?? '';
 $status        = $item['status'] ?? 'aktif';
 $faskesTk1     = $item['faskes_tk1'] ?? '';
 $tglPembaruan  = ($item['tanggal_pembaruan'] ?? '') !== ''
    ? $item['tanggal_pembaruan']
    : date('Y-m-d');
 $catatan       = $item['catatan'] ?? '';

 $santriOptions = (isset($santriOptions) && is_array($santriOptions)) ? $santriOptions : [];

 $action = $isEdit ? base_url('/bpjs/' . $editId) : base_url('/bpjs');
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/bpjs') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Data BPJS' : 'Tambah Data BPJS' ?></h1>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if ($isEdit && $nomorBpjs === ''): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Data BPJS tidak ditemukan. <a href="<?= base_url('/bpjs') ?>">Kembali ke daftar BPJS</a>.
</div>
<?php else: ?>

<div class="card border-0 shadow-sm" style="max-width: 720px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <?php if ($isEdit): ?>
                <div class="col-md-7">
                    <label class="form-label" for="santri_display">Santri *</label>
                    <input type="text" class="form-control" id="santri_display" disabled
                           value="<?= e($santriNama !== '' ? $santriNama : 'Santri #' . $santriId) ?>">
                    <input type="hidden" name="santri_id" value="<?= $santriId ?>">
                    <small class="text-muted">Santri tidak dapat diubah pada mode edit.</small>
                </div>
                <?php else: ?>
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
                <?php endif; ?>
                <div class="col-md-5">
                    <label class="form-label" for="nomor_bpjs">Nomor BPJS *</label>
                    <input type="text" class="form-control" id="nomor_bpjs" name="nomor_bpjs" required
                           inputmode="numeric" value="<?= e($nomorBpjs) ?>"
                           placeholder="13 digit angka">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="status">Status *</label>
                    <select class="form-select" id="status" name="status" required>
                        <?php foreach (Bpjs::STATUS_LABEL as $v => $l): ?>
                        <option value="<?= e($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="faskes_tk1">Faskes Tingkat 1 *</label>
                    <input type="text" class="form-control" id="faskes_tk1" name="faskes_tk1" required
                           value="<?= e($faskesTk1) ?>" placeholder="cth: Puskesmas Cimahi Utara">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tanggal_pembaruan">Tanggal Pembaruan *</label>
                    <input type="date" class="form-control" id="tanggal_pembaruan" name="tanggal_pembaruan" required
                           value="<?= e($tglPembaruan) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="catatan">Catatan</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="2" maxlength="255"
                              placeholder="opsional — kelas, tunggakan, keterangan keluarga..."><?= e($catatan) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/bpjs') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>