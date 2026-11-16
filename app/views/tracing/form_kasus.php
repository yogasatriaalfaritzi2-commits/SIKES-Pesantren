<?php
use App\Core\Session;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/tracing') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0">Catat Kasus Penyakit Menular</h1>
        <p class="text-muted small mb-0">Setelah disimpan, sistem otomatis menggenerate daftar kontak potensial dari penghuni kamar santri.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="alert alert-light border small text-muted">
    <i class="bi bi-info-circle me-1"></i>
    Kontak potensial = penghuni aktif kamar yang sama dengan santri index pada saat kasus dicatat.
    Ini <strong>tidak berarti mereka pasti tertular</strong>.
</div>

<div class="card border-0 shadow-sm" style="max-width: 720px">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('/tracing/kasus') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="santri_id">Santri (Kasus Index) *</label>
                    <select class="form-select" id="santri_id" name="santri_id" required>
                        <option value="">— Pilih santri —</option>
                        <?php foreach ($santriOptions as $s): ?>
                        <option value="<?= (int) $s['id'] ?>">
                            <?= e($s['nis']) ?> — <?= e($s['nama']) ?> (<?= e($s['jenis_kelamin']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tanggal_mulai">Tanggal Mulai Sakit *</label>
                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required
                           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="penyakit">Nama Penyakit *</label>
                    <input type="text" class="form-control" id="penyakit" name="penyakit" required
                           placeholder="cth: Varicella (Cacar Air), Scabies, ISPA">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tanggal_selesai">Tanggal Selesai</label>
                    <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai"
                           placeholder="kosongkan jika masih aktif">
                </div>
                <div class="col-12">
                    <label class="form-label" for="catatan">Catatan Tenaga Medis</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="3"
                              placeholder="Kondisi, langkah isolasi, anjuran..."></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4">
                    <i class="bi bi-check-lg me-1"></i> Simpan &amp; Generate Kontak
                </button>
                <a href="<?= base_url('/tracing') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>