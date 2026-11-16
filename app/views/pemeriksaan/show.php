<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\Pemeriksaan;

/* ============================================================
   NORMALISASI DEFENSIF — mencegah warning "Undefined array key"
   walau data tidak lengkap; dengan controller + model di atas,
   $item akan berisi baris database yang utuh.
   ============================================================ */
 $item        = (isset($item) && is_array($item)) ? $item : [];
 $warnings    = (isset($warnings) && is_array($warnings)) ? $warnings : [];
 $riwayatList = (isset($riwayatList) && is_array($riwayatList)) ? $riwayatList : [];
 $skrining    = (isset($skrining) && is_array($skrining)) ? $skrining : null;

 $id          = (int) ($item['id'] ?? 0);
 $santriId    = (int) ($item['santri_id'] ?? 0);
 $status      = $item['status'] ?? 'menunggu';

 $statusMap = ['menunggu' => 'warning', 'selesai' => 'success', 'batal' => 'secondary'];
 $aksiMap   = ['registrasi' => 'primary', 'periksa' => 'success', 'ubah' => 'warning', 'batal' => 'secondary'];
 $aksiLabel = ['registrasi' => 'Teregistrasi', 'periksa' => 'Diperiksa', 'ubah' => 'Diubah', 'batal' => 'Dibatalkan'];

 $tglKunjungan = $item['tanggal_kunjungan'] ?? null;
 $umur         = (!empty($item['tanggal_lahir']) && strtotime((string) $item['tanggal_lahir']) !== false)
    ? (new DateTime((string) $item['tanggal_lahir']))->diff(new DateTime())->y
    : null;

 $hasVitals = ($item['suhu_c'] ?? null) !== null
    || ($item['td_sistol'] ?? null) !== null
    || ($item['nadi'] ?? null) !== null
    || ($item['pernapasan'] ?? null) !== null;
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <a href="<?= base_url('/pemeriksaan') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0">Kunjungan — <?= e($item['santri_nama'] ?? 'Detail Pemeriksaan') ?></h1>
        <p class="text-muted small mb-0">
            <?= $tglKunjungan ? e(date('d-m-Y H:i', strtotime((string) $tglKunjungan))) : '-' ?> ·
            <?= Pemeriksaan::JENIS_LABEL[$item['jenis_kunjungan'] ?? ''] ?? '-' ?> ·
            <?= $item['nis'] ? 'NIS ' . e($item['nis']) : '' ?>
        </p>
    </div>
    <span class="badge bg-<?= $statusMap[$status] ?>-subtle text-<?= $statusMap[$status] ?>-emphasis">
        <?= Pemeriksaan::STATUS_LABEL[$status] ?>
    </span>
    <?php if ($id > 0): ?>
    <a href="<?= base_url('/pemeriksaan/riwayat/' . $santriId) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-clock-history me-1"></i> Riwayat Kesehatan
    </a>
    <?php endif; ?>
    <?php if ($status === 'menunggu' && $id > 0 && Auth::hasPermission('pemeriksaan.edit')): ?>
    <a href="<?= base_url('/pemeriksaan/' . $id . '/periksa') ?>" class="btn btn-brand btn-sm">
        <i class="bi bi-clipboard2-heart me-1"></i> Lakukan Pemeriksaan
    </a>
    <form method="POST" action="<?= base_url('/pemeriksaan/' . $id . '/batal') ?>"
          onsubmit="return confirm('Batalkan kunjungan ini?')">
        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i> Batalkan</button>
    </form>
    <?php elseif ($status === 'selesai' && $id > 0 && Auth::hasPermission('pemeriksaan.edit')): ?>
    <a href="<?= base_url('/pemeriksaan/' . $id . '/edit') ?>" class="btn btn-brand btn-sm">
        <i class="bi bi-pencil me-1"></i> Edit
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if ($id === 0): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Data pemeriksaan tidak tersedia. <a href="<?= base_url('/pemeriksaan') ?>">Kembali ke daftar kunjungan</a>.
</div>
<?php else: ?>

<?php if ($warnings): ?>
<div class="alert alert-danger d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill mt-1 fs-5"></i>
    <div>
        <strong>Kondisi khusus santri:</strong>
        <?php foreach ($warnings as $w): ?>
            <span class="badge bg-danger-subtle text-danger-emphasis me-1 mt-1"><?= e($w) ?></span>
        <?php endforeach; ?>
        <div class="small mt-2">Sumber: skrining kesehatan awal. Wajib diperhatikan saat menentukan tindakan &amp; obat.</div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <!-- Tanda Vital -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-activity me-2"></i>Tanda Vital</div>
            <div class="card-body">
                <?php if (!$hasVitals): ?>
                    <span class="text-muted small">Belum diperiksa.</span>
                <?php else: ?>
                <div class="row g-2 text-center">
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-2">
                            <i class="bi bi-thermometer-half text-danger d-block fs-5"></i>
                            <div class="fw-bold"><?= $item['suhu_c'] !== null ? e((string) $item['suhu_c']) . '°C' : '-' ?></div>
                            <small class="text-muted">Suhu</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-2">
                            <i class="bi bi-heart-pulse text-danger d-block fs-5"></i>
                            <div class="fw-bold"><?= $item['td_sistol'] !== null ? e($item['td_sistol'] . '/' . $item['td_diastol']) : '-' ?></div>
                            <small class="text-muted">Tek. Darah (mmHg)</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-2">
                            <i class="bi bi-activity text-danger d-block fs-5"></i>
                            <div class="fw-bold"><?= $item['nadi'] !== null ? e((string) $item['nadi']) : '-' ?></div>
                            <small class="text-muted">Nadi (/menit)</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-2">
                            <i class="bi bi-wind text-danger d-block fs-5"></i>
                            <div class="fw-bold"><?= $item['pernapasan'] !== null ? e((string) $item['pernapasan']) : '-' ?></div>
                            <small class="text-muted">Pernapasan (/menit)</small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Keluhan & Hasil -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-chat-left-text me-2"></i>Keluhan &amp; Hasil Pemeriksaan</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <div class="fw-semibold small text-muted mb-1">Keluhan</div>
                    <div style="white-space: pre-wrap"><?= e($item['keluhan'] ?? '-') ?></div>
                </li>
                <?php if (!empty($item['pemeriksaan_awal'])): ?>
                <li class="list-group-item">
                    <div class="fw-semibold small text-muted mb-1">Pemeriksaan Awal / Triage</div>
                    <div style="white-space: pre-wrap"><?= e($item['pemeriksaan_awal']) ?></div>
                </li>
                <?php endif; ?>
                <li class="list-group-item">
                    <div class="fw-semibold small text-muted mb-1">Hasil Pemeriksaan</div>
                    <?php if (!empty($item['hasil_pemeriksaan'])): ?>
                        <div style="white-space: pre-wrap"><?= e($item['hasil_pemeriksaan']) ?></div>
                    <?php else: ?>
                        <span class="text-muted small">Belum ada.</span>
                    <?php endif; ?>
                </li>
            </ul>
        </div>

        <!-- Diagnosis, Tindakan, Resep -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-clipboard2-pulse me-2"></i>Diagnosis &amp; Tindakan
                <span class="text-muted fw-normal small">(isian tenaga medis)</span>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <div class="fw-semibold small text-muted mb-1">Diagnosis / Keterangan</div>
                    <?php if (!empty($item['diagnosis'])): ?>
                        <div class="fw-semibold text-brand"><?= e($item['diagnosis']) ?></div>
                    <?php else: ?>
                        <span class="text-muted small">Belum ada.</span>
                    <?php endif; ?>
                </li>
                <li class="list-group-item">
                    <div class="fw-semibold small text-muted mb-1">Tindakan</div>
                    <div style="white-space: pre-wrap"><?= !empty($item['tindakan']) ? e($item['tindakan']) : '<span class="text-muted small">-</span>' ?></div>
                </li>
                <li class="list-group-item">
                    <div class="fw-semibold small text-muted mb-1">Resep / Catatan Obat</div>
                    <div style="white-space: pre-wrap"><?= !empty($item['resep_obat']) ? e($item['resep_obat']) : '<span class="text-muted small">-</span>' ?></div>
                </li>
                <li class="list-group-item">
                    <div class="fw-semibold small text-muted mb-1">Catatan Lanjutan</div>
                    <div style="white-space: pre-wrap"><?= !empty($item['catatan_lanjutan']) ? e($item['catatan_lanjutan']) : '<span class="text-muted small">-</span>' ?></div>
                    <?php if (!empty($item['tanggal_kontrol'])): ?>
                        <span class="badge bg-info-subtle text-info-emphasis mt-2">
                            <i class="bi bi-calendar-check me-1"></i>Jadwal Kontrol: <?= e(date('d-m-Y', strtotime((string) $item['tanggal_kontrol']))) ?>
                        </span>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Info -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-info-circle me-2"></i>Informasi Kunjungan</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Waktu</span>
                    <span><?= $tglKunjungan ? e(date('d-m-Y H:i', strtotime((string) $tglKunjungan))) : '-' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Jenis</span>
                    <span><?= Pemeriksaan::JENIS_LABEL[$item['jenis_kunjungan'] ?? ''] ?? '-' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Umur</span>
                    <span><?= $umur !== null ? $umur . ' tahun' : '-' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Petugas Registrasi</span>
                    <span><?= e($item['petugas_nama'] ?? '-') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Pemeriksa</span>
                    <span><?= e($item['pemeriksa_nama'] ?? 'Belum diperiksa') ?></span>
                </li>
            </ul>
        </div>

        <!-- Jejak audit -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Jejak Kunjungan</div>
            <ul class="list-group list-group-flush">
                <?php if (!$riwayatList): ?>
                    <li class="list-group-item text-muted small">Belum ada jejak.</li>
                <?php endif; ?>
                <?php foreach ($riwayatList as $r):
                    $snap = json_decode($r['data_sesudah'] ?? '', true);
                    $ringkasan = is_array($snap) ? ($snap['ringkasan'] ?? '-') : '-';
                ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?= $aksiMap[$r['aksi']] ?? 'secondary' ?>-subtle
                                      text-<?= $aksiMap[$r['aksi']] ?? 'secondary' ?>-emphasis">
                            <?= $aksiLabel[$r['aksi']] ?? e($r['aksi']) ?>
                        </span>
                        <small class="text-muted"><?= e(date('d-m-Y H:i', strtotime($r['created_at']))) ?></small>
                    </div>
                    <div class="small mt-1"><?= e((string) $ringkasan) ?></div>
                    <?php if ($r['user_nama']): ?><div class="small text-muted">oleh <?= e($r['user_nama']) ?></div><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>