<?php
use App\Core\Session;
use App\Models\BukuTamu;
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/buku-tamu') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h4 fw-bold mb-0">Catat Tamu Penjenguk</h1>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 760px">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('/buku-tamu') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="nama_penjenguk">Nama Penjenguk *</label>
                    <input type="text" class="form-control" id="nama_penjenguk" name="nama_penjenguk" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="hubungan">Hubungan *</label>
                    <select class="form-select" id="hubungan" name="hubungan" required>
                        <option value="">— Pilih —</option>
                        <?php foreach (BukuTamu::HUBUNGAN as $h): ?>
                        <option value="<?= e($h) ?>"><?= e($h) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="no_telepon">No. Telepon</label>
                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" placeholder="opsional">
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="santri_id">Santri yang Dijenguk *</label>
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
                    <label class="form-label" for="tanggal">Tanggal *</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" required
                           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="waktu_datang">Waktu Datang *</label>
                    <input type="time" class="form-control" id="waktu_datang" name="waktu_datang" required
                           value="<?= date('H:i') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="tujuan">Tujuan *</label>
                    <input type="text" class="form-control" id="tujuan" name="tujuan" required
                           placeholder="cth: Menjenguk ananda yang sakit">
                </div>
                <div class="col-12">
                    <label class="form-label" for="catatan">Catatan</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="2"
                              placeholder="Barang dibawa, keterangan tambahan... (opsional)"></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/buku-tamu') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>