<?php
use App\Core\Session;

/* ============================================================
   NORMALISASI DEFENSIF — mencegah warning "Undefined array key"
   walau $item tidak utuh; dengan controller + model final,
   $item berisi baris database lengkap.
   ============================================================ */
 $item = (isset($item) && is_array($item)) ? $item : [];

 $id           = (int) ($item['id'] ?? 0);
 $santriNama   = $item['santri_nama'] ?? 'Santri';
 $nis          = $item['nis'] ?? '';
 $jenisKelamin = $item['jenis_kelamin'] ?? '';
 $tanggalLahir = $item['tanggal_lahir'] ?? null;
 $asramaNama   = $item['asrama_nama'] ?? null;
 $kamarNama    = $item['kamar_nama'] ?? null;

 $umur = ($tanggalLahir !== null && $tanggalLahir !== '' && strtotime($tanggalLahir) !== false)
    ? (new DateTime($tanggalLahir))->diff(new DateTime())->y
    : null;

 $action = $isEdit ?? false
    ? base_url('/pemeriksaan/' . $id)
    : base_url('/pemeriksaan/' . $id . '/periksa');

 $valTglKunjungan = !empty($item['tanggal_kunjungan'])
    ? date('Y-m-d\TH:i', strtotime((string) $item['tanggal_kunjungan']))
    : date('Y-m-d\TH:i');
 $valJenis        = $item['jenis_kunjungan'] ?? 'rawat_jalan';
 $valKeluhan      = $item['keluhan'] ?? '';
 $valPemeriksaanAwal = $item['pemeriksaan_awal'] ?? '';
 $valSuhu         = $item['suhu_c'] ?? '';
 $valSistol       = $item['td_sistol'] ?? '';
 $valDiastol      = $item['td_diastol'] ?? '';
 $valNadi         = $item['nadi'] ?? '';
 $valNapas        = $item['pernapasan'] ?? '';
 $valHasil        = $item['hasil_pemeriksaan'] ?? '';
 $valDiagnosis    = $item['diagnosis'] ?? '';
 $valTindakan     = $item['tindakan'] ?? '';
 $valResep        = $item['resep_obat'] ?? '';
 $valLanjutan     = $item['catatan_lanjutan'] ?? '';
 $valKontrol      = $item['tanggal_kontrol'] ?? '';

 $jenisOptions = [
    'rawat_jalan' => 'Rawat Jalan',
    'darurat'     => 'Darurat',
    'kontrol'     => 'Kontrol',
    'preventif'   => 'Preventif (Tracing)',
];
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/pemeriksaan/' . $id) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0"><?= ($isEdit ?? false) ? 'Edit Pemeriksaan' : 'Lakukan Pemeriksaan' ?></h1>
        <p class="text-muted small mb-0">Seluruh diagnosis &amp; keterangan merupakan isian manual tenaga medis.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if (!empty($warnings)): ?>
<div class="alert alert-danger d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill mt-1 fs-5"></i>
    <div>
        <strong>Kondisi khusus santri (dari skrining awal):</strong>
        <?php foreach ($warnings as $w): ?>
            <span class="badge bg-danger-subtle text-danger-emphasis me-1 mt-1"><?= e($w) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 small">
            <div class="col-md-3"><span class="text-muted">Santri:</span> <strong><?= e($santriNama) ?></strong></div>
            <div class="col-md-2"><span class="text-muted">NIS:</span> <?= e($nis) ?></div>
            <div class="col-md-2"><span class="text-muted">L/P:</span> <?= $jenisKelamin === 'L' ? 'Laki-laki' : ($jenisKelamin === 'P' ? 'Perempuan' : '-') ?></div>
            <div class="col-md-2"><span class="text-muted">Umur:</span> <?= $umur !== null ? $umur . ' th' : '-' ?></div>
            <div class="col-md-3"><span class="text-muted">Penempatan:</span> <?= e($asramaNama ?? '-') ?><?= $kamarNama ? ' · Kam. ' . e($kamarNama) : '' ?></div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="max-width: 960px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <?php if ($isEdit ?? false): ?>
            <!-- Edit: data registrasi ikut dapat dikoreksi -->
            <div class="fw-semibold text-brand text-uppercase small mb-3"><i class="bi bi-calendar me-1"></i> Data Kunjungan</div>
            <div class="row g-3 mb-2">
                <div class="col-md-4">
                    <label class="form-label" for="tanggal_kunjungan">Tanggal &amp; Jam *</label>
                    <input type="datetime-local" class="form-control" id="tanggal_kunjungan" name="tanggal_kunjungan" required
                           value="<?= e($valTglKunjungan) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="jenis_kunjungan">Jenis Kunjungan *</label>
                    <select class="form-select" id="jenis_kunjungan" name="jenis_kunjungan" required>
                        <?php foreach ($jenisOptions as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $valJenis === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mb-2">
                <div class="col-12">
                    <label class="form-label" for="keluhan">Keluhan *</label>
                    <textarea class="form-control" id="keluhan" name="keluhan" rows="2" required><?= e($valKeluhan) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="pemeriksaan_awal">Pemeriksaan Awal / Triage</label>
                    <textarea class="form-control" id="pemeriksaan_awal" name="pemeriksaan_awal" rows="2"><?= e($valPemeriksaanAwal) ?></textarea>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== Tanda Vital ===== -->
            <div class="fw-semibold text-brand text-uppercase small mt-3 mb-3">
                <i class="bi bi-activity me-1"></i> Tanda Vital
            </div>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label class="form-label" for="suhu_c">Suhu (°C)</label>
                    <input type="number" step="0.1" min="33" max="45" class="form-control" id="suhu_c" name="suhu_c"
                           value="<?= e((string) $valSuhu) ?>" placeholder="cth: 36.8">
                </div>
                <div class="col-3 col-md-2">
                    <label class="form-label" for="td_sistol">TD Sistolic</label>
                    <input type="number" min="50" max="300" class="form-control" id="td_sistol" name="td_sistol"
                           value="<?= e((string) $valSistol) ?>" placeholder="120">
                </div>
                <div class="col-3 col-md-2">
                    <label class="form-label" for="td_diastol">TD Diastolic</label>
                    <input type="number" min="20" max="200" class="form-control" id="td_diastol" name="td_diastol"
                           value="<?= e((string) $valDiastol) ?>" placeholder="80">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="nadi">Nadi (/menit)</label>
                    <input type="number" min="20" max="250" class="form-control" id="nadi" name="nadi"
                           value="<?= e((string) $valNadi) ?>" placeholder="80">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="pernapasan">Pernapasan (/menit)</label>
                    <input type="number" min="5" max="80" class="form-control" id="pernapasan" name="pernapasan"
                           value="<?= e((string) $valNapas) ?>" placeholder="20">
                </div>
            </div>

            <!-- ===== Hasil, Diagnosis, Tindakan ===== -->
            <div class="fw-semibold text-brand text-uppercase small mt-4 mb-3">
                <i class="bi bi-clipboard2-check me-1"></i> Hasil &amp; Kesimpulan Tenaga Medis
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="hasil_pemeriksaan">Hasil Pemeriksaan *</label>
                    <textarea class="form-control" id="hasil_pemeriksaan" name="hasil_pemeriksaan" rows="3"
                              placeholder="Pemeriksaan fisik: inspeksi, palpasi, auskultasi, dll."><?= e($valHasil) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="diagnosis">Diagnosis / Keterangan Tenaga Medis *</label>
                    <input type="text" class="form-control" id="diagnosis" name="diagnosis" required
                           value="<?= e($valDiagnosis) ?>" placeholder="Kesimpulan klinis oleh tenaga medis">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tindakan">Tindakan</label>
                    <textarea class="form-control" id="tindakan" name="tindakan" rows="2"
                              placeholder="cth: istirahat, kompres, rujukan"><?= e($valTindakan) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="resep_obat">Resep / Catatan Obat</label>
                    <textarea class="form-control" id="resep_obat" name="resep_obat" rows="2"
                              placeholder="cth: Paracetamol 500mg 3x1 PRN"><?= e($valResep) ?></textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="catatan_lanjutan">Catatan Lanjutan</label>
                    <textarea class="form-control" id="catatan_lanjutan" name="catatan_lanjutan" rows="2"
                              placeholder="Anjuran tindak lanjut, hal yang perlu dipantau"><?= e($valLanjutan) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tanggal_kontrol">Tanggal Kontrol</label>
                    <input type="date" class="form-control" id="tanggal_kontrol" name="tanggal_kontrol"
                           value="<?= e($valKontrol) ?>">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4">
                    <i class="bi bi-check-lg me-1"></i> <?= ($isEdit ?? false) ? 'Simpan Perubahan' : 'Selesaikan Pemeriksaan' ?>
                </button>
                <a href="<?= base_url('/pemeriksaan/' . $id) ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>