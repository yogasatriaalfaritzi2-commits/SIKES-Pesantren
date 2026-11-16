<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\SkriningKesehatan;

 $bwMap    = ['belum' => ['Belum diperiksa', 'secondary'], 'normal' => ['Normal', 'success'], 'kelainan' => ['Kelainan', 'danger']];
 $gigiMap  = ['belum' => ['Belum diperiksa', 'secondary'], 'baik' => ['Baik', 'success'], 'perlu_perawatan' => ['Perlu Perawatan', 'warning'], 'perlu_rujukan' => ['Perlu Rujukan', 'danger']];
 $fisikMap = ['belum' => ['Belum diperiksa', 'secondary'], 'sehat' => ['Sehat', 'success'], 'ada_keluhan' => ['Ada Keluhan', 'warning'], 'perlu_rujukan' => ['Perlu Rujukan', 'danger']];
 $imtColor = match ($item['kategori_imt'] ?? '') {
    'Normal' => 'success',
    'Berat Badan Kurang', 'Berat Badan Berlebih' => 'warning',
    'Obesitas' => 'danger',
    default => 'secondary',
};
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <a href="<?= base_url('/skrining') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0">Profil Kesehatan — <?= e($item['santri_nama']) ?></h1>
        <p class="text-muted small mb-0">
            NIS <?= e($item['nis']) ?> · Skrining <?= e(date('d-m-Y', strtotime($item['tanggal_skrining']))) ?>
            <?= $item['petugas_nama'] ? ' · Petugas: ' . e($item['petugas_nama']) : '' ?>
        </p>
    </div>
    <a href="<?= base_url('/santri/' . $item['santri_id']) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-person me-1"></i> Data Santri
    </a>
    <a href="<?= base_url('/skrining/riwayat?santri_id=' . $item['santri_id']) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-clock-history me-1"></i> Riwayat
    </a>
    <?php if (Auth::hasPermission('pemeriksaan.edit')): ?>
    <a href="<?= base_url('/skrining/' . $item['id'] . '/edit') ?>" class="btn btn-brand btn-sm">
        <i class="bi bi-pencil me-1"></i> Edit
    </a>
    <form class="d-inline form-delete" method="POST" action="<?= base_url('/skrining/' . $item['id'] . '/delete') ?>"
          data-name="skrining atas nama <?= e($item['santri_nama']) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
    </form>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if ($warnings): ?>
<div class="alert alert-danger d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill mt-1 fs-5"></i>
    <div>
        <strong>Perhatian — kondisi khusus tercatat:</strong><br>
        <?php foreach ($warnings as $w): ?>
            <span class="badge bg-danger-subtle text-danger-emphasis me-1 mt-1"><?= e($w) ?></span>
        <?php endforeach; ?>
        <div class="small mt-2">Berdasarkan isian tenaga medis pada skrining awal. Wajib diperhatikan dalam pembinaan, menu makanan, dan aktivitas fisik santri.</div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-success py-2 small">
    <i class="bi bi-check-circle me-1"></i> Tidak ada alergi / kondisi khusus yang tercatat pada skrining awal.
</div>
<?php endif; ?>

<div class="row g-3">
    <!-- Kiri: kondisi + pemeriksaan -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-clipboard2-plus me-2"></i>Riwayat &amp; Kondisi Khusus
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach (SkriningKesehatan::KONDISI as $field => $label):
                    $ya  = ($item[$field] ?? 'tidak') === 'ya';
                    $ket = $item[$field . '_ket'] ?? null;
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold"><?= e($label) ?></div>
                        <?php if ($ya && $ket): ?>
                            <small class="text-muted"><?= e($ket) ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if ($ya): ?>
                        <span class="badge bg-danger-subtle text-danger-emphasis flex-shrink-0">Ya</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis flex-shrink-0">Tidak</span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-clipboard2-check me-2"></i>Hasil Pemeriksaan
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div><div class="fw-semibold">Buta Warna</div>
                        <?php if ($item['buta_warna'] === 'kelainan' && $item['buta_warna_ket']): ?><small class="text-muted"><?= e($item['buta_warna_ket']) ?></small><?php endif; ?>
                    </div>
                    <span class="badge bg-<?= $bwMap[$item['buta_warna']][1] ?>-subtle text-<?= $bwMap[$item['buta_warna']][1] ?>-emphasis">
                        <?= $bwMap[$item['buta_warna']][0] ?>
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div><div class="fw-semibold">Gigi</div>
                        <?php if ($item['gigi_ket']): ?><small class="text-muted"><?= e($item['gigi_ket']) ?></small><?php endif; ?>
                    </div>
                    <span class="badge bg-<?= $gigiMap[$item['gigi_status']][1] ?>-subtle text-<?= $gigiMap[$item['gigi_status']][1] ?>-emphasis">
                        <?= $gigiMap[$item['gigi_status']][0] ?>
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div><div class="fw-semibold">Fisik Umum</div>
                        <?php if ($item['fisik_ket']): ?><small class="text-muted"><?= e($item['fisik_ket']) ?></small><?php endif; ?>
                    </div>
                    <span class="badge bg-<?= $fisikMap[$item['fisik_status']][1] ?>-subtle text-<?= $fisikMap[$item['fisik_status']][1] ?>-emphasis">
                        <?= $fisikMap[$item['fisik_status']][0] ?>
                    </span>
                </li>
            </ul>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-journal-text me-2"></i>Catatan Tenaga Medis</div>
            <div class="card-body">
                <?php if ($item['catatan_medis']): ?>
                    <p class="mb-0" style="white-space: pre-wrap"><?= e($item['catatan_medis']) ?></p>
                <?php else: ?>
                    <span class="text-muted small">Belum ada catatan.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Kanan: antropometri + identitas + riwayat ringkas -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-rulers me-2"></i>Antropometri</div>
            <div class="card-body text-center">
                <?php if ($item['imt'] !== null): ?>
                    <div class="display-5 fw-bold text-brand"><?= e((string) $item['imt']) ?></div>
                    <span class="badge bg-<?= $imtColor ?>-subtle text-<?= $imtColor ?>-emphasis mb-3">
                        <?= e($item['kategori_imt']) ?>
                    </span>
                <?php else: ?>
                    <div class="display-6 fw-bold text-secondary">-</div>
                    <p class="text-muted small mb-3">Tinggi/berat belum diukur</p>
                <?php endif; ?>
                <div class="row text-start mt-2">
                    <div class="col-6 d-flex justify-content-between border-top pt-2">
                        <span class="text-muted small">Tinggi</span>
                        <span class="small"><?= $item['tinggi_cm'] ? e((string) $item['tinggi_cm']) . ' cm' : '-' ?></span>
                    </div>
                    <div class="col-6 d-flex justify-content-between border-top pt-2">
                        <span class="text-muted small">Berat</span>
                        <span class="small"><?= $item['berat_kg'] ? e((string) $item['berat_kg']) . ' kg' : '-' ?></span>
                    </div>
                    <div class="col-6 d-flex justify-content-between border-top pt-2">
                        <span class="text-muted small">Gol. Darah</span>
                        <span class="small fw-semibold">
                            <?php if ($item['golongan_darah'] !== '-'): ?>
                                <?= e($item['golongan_darah']) ?><?= $item['rhesus'] !== '-' ? e($item['rhesus'] === 'positif' ? '+' : '-') : '' ?>
                            <?php else: ?>-<?php endif; ?>
                        </span>
                    </div>
                    <div class="col-6 d-flex justify-content-between border-top pt-2">
                        <span class="text-muted small">Rhesus</span>
                        <span class="small"><?= $item['rhesus'] !== '-' ? e(ucfirst($item['rhesus'])) : '-' ?></span>
                    </div>
                </div>
                <div class="alert alert-light border py-2 small mt-3 mb-0 text-muted">
                    <i class="bi bi-info-circle me-1"></i>IMT dihitung otomatis sebagai data skrining awal — <strong>bukan diagnosis</strong>. Penilaian akhir merupakan kewenangan tenaga medis.
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person me-2"></i>Identitas Santri</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Nama</span><span><?= e($item['santri_nama']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">NIS / NISN</span><span><?= e($item['nis']) ?><?= $item['nisn'] ? ' / ' . e($item['nisn']) : '' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Jenis Kelamin</span><span><?= $item['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Penempatan</span>
                    <span class="text-end"><?= e($item['asrama_nama'] ?? '-') ?><?= $item['kamar_nama'] ? ' · Kam. ' . e($item['kamar_nama']) : '' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Wali</span><span><?= e($item['wali_nama'] ?? '-') ?></span></li>
            </ul>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat Terakhir</div>
            <ul class="list-group list-group-flush">
                <?php if (!$riwayatList): ?>
                    <li class="list-group-item text-muted small">Belum ada riwayat.</li>
                <?php endif; ?>
                <?php foreach ($riwayatList as $r):
                    $snap = json_decode($r['data_sesudah'] ?? '', true);
                    $ringkasan = $snap['ringkasan'] ?? '-';
                ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-<?= $r['aksi'] === 'buat' ? 'primary' : 'warning' ?>-subtle
                                      text-<?= $r['aksi'] === 'buat' ? 'primary' : 'warning' ?>-emphasis">
                            <?= $r['aksi'] === 'buat' ? 'Dibuat' : 'Diubah' ?>
                        </span>
                        <small class="text-muted"><?= e(date('d-m-Y H:i', strtotime($r['created_at']))) ?></small>
                    </div>
                    <div class="small mt-1"><?= e($ringkasan) ?></div>
                    <?php if ($r['user_nama']): ?><div class="small text-muted">oleh <?= e($r['user_nama']) ?></div><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>