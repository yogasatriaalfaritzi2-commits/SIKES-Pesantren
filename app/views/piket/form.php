<?php
use App\Core\Session;
use App\Models\PiketJadwal;

/* ============================================================
   NORMALISASI DEFENSIF
   - $item bisa null, [], atau baris lengkap (mode edit)
   - Mode edit ditentukan dari id yang ADA, bukan dari !== null
   - Semua akses array memakai ?? default -> nol warning
   ============================================================ */
 $item   = (isset($item) && is_array($item)) ? $item : [];
 $isEdit = !empty($item['id']);
 $action = $isEdit ? base_url('/piket/' . (int) $item['id']) : base_url('/piket');

 $valNama   = $item['nama_kegiatan'] ?? '';
 $valLokasi = $item['lokasi'] ?? '';
 $valJenis  = $item['jenis'] ?? '';
 $valTgl    = $item['tanggal'] ?? date('Y-m-d');
 $valMulai  = substr((string) ($item['waktu_mulai'] ?? '07:00'), 0, 5);
 $valSelesai= $item['waktu_selesai'] ?? null;
 $valPJ     = $item['penanggung_jawab'] ?? '';
 $valCatatan= $item['catatan'] ?? '';
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/piket') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Jadwal Piket' : 'Tambah Jadwal Piket' ?></h1>
        <p class="text-muted small mb-0">Field bertanda * wajib diisi. Status diubah lewat tombol Mulai/Selesai, bukan form ini.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 760px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label" for="jenis">Jenis Kegiatan *</label>
                    <select class="form-select" id="jenis" name="jenis" required>
                        <option value="">— Pilih jenis —</option>
                        <?php foreach (PiketJadwal::JENIS as $v => $l): ?>
                        <option value="<?= e($v) ?>" <?= $valJenis === $v ? 'selected' : '' ?>><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="nama_kegiatan">Nama Kegiatan *</label>
                    <input type="text" class="form-control" id="nama_kegiatan" name="nama_kegiatan" required
                           value="<?= e($valNama) ?>" placeholder="cth: Kerja Bakti Lingkungan Pondok">
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="lokasi">Lokasi *</label>
                    <input type="text" class="form-control" id="lokasi" name="lokasi" required
                           value="<?= e($valLokasi) ?>" placeholder="cth: Asrama Al-Faruq — Blok A">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tanggal">Tanggal *</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" required
                           value="<?= e($valTgl) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="waktu_mulai">Waktu Mulai *</label>
                    <input type="time" class="form-control" id="waktu_mulai" name="waktu_mulai" required
                           value="<?= e($valMulai) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="waktu_selesai">Waktu Selesai</label>
                    <input type="time" class="form-control" id="waktu_selesai" name="waktu_selesai"
                           value="<?= $valSelesai ? e(substr((string) $valSelesai, 0, 5)) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="penanggung_jawab">Penanggung Jawab *</label>
                    <input type="text" class="form-control" id="penanggung_jawab" name="penanggung_jawab" required
                           value="<?= e($valPJ) ?>" placeholder="nama musyrif/petugas">
                </div>
                <div class="col-12">
                    <label class="form-label" for="catatan">Catatan</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="3"
                              placeholder="Instruksi khusus, persiapan, target hasil..."><?= e($valCatatan) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/piket') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>