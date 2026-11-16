<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\Kepulangan;
use App\Models\KepulanganRiwayat;

 $stage        = Kepulangan::currentStage($item);
 $isKeamanan   = Auth::isSuper() || Auth::hasRole('keamanan');
 $isPengasuhan = Auth::isSuper() || Auth::hasRole('pengasuhan') || Auth::hasRole('kyai');

 $stages = [
    ['no' => 1, 'label' => 'Rekomendasi Medis', 'icon' => 'bi-clipboard2-pulse',
     'petugas' => $item['medis_nama'], 'at' => $item['created_at'], 'status' => 'disetujui', 'catatan' => $item['medis_catatan']],
    ['no' => 2, 'label' => 'Approval Keamanan', 'icon' => 'bi-shield-check',
     'petugas' => $item['keamanan_nama'], 'at' => $item['keamanan_at'],
     'status' => $item['keamanan_status'], 'catatan' => $item['keamanan_catatan']],
    ['no' => 3, 'label' => 'Approval Pengasuhan / Kyai', 'icon' => 'bi-house-heart',
     'petugas' => $item['pengasuhan_nama'], 'at' => $item['pengasuhan_at'],
     'status' => $item['pengasuhan_status'], 'catatan' => $item['pengasuhan_catatan']],
    ['no' => 4, 'label' => 'Izin Keluar Gerbang', 'icon' => 'bi-door-open',
     'petugas' => $item['gerbang_nama'], 'at' => $item['gerbang_at'],
     'status' => $item['status'] === 'selesai' ? 'selesai' : 'menunggu', 'catatan' => null],
];
 $stLabel = ['disetujui' => 'Disetujui', 'menunggu' => 'Menunggu', 'ditolak' => 'Ditolak', 'selesai' => 'Selesai'];
 $stColor = ['disetujui' => 'success', 'menunggu' => 'warning', 'ditolak' => 'danger', 'selesai' => 'success'];
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <a href="<?= base_url('/kepulangan') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0">Kepulangan — <?= e($item['santri_nama']) ?></h1>
        <p class="text-muted small mb-0">NIS <?= e($item['nis']) ?> · <?= e($item['asrama_nama'] ?? '-') ?><?= $item['kamar_nama'] ? ' · Kam. ' . e($item['kamar_nama']) : '' ?> · Diajukan <?= e(date('d-m-Y', strtotime($item['tanggal_pengajuan']))) ?></p>
    </div>
    <span class="badge bg-<?= Kepulangan::STATUS_COLOR[$item['status']] ?> text-white fs-6 px-3 py-2">
        <?= Kepulangan::STATUS_LABEL[$item['status']] ?>
    </span>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if ($item['status'] === 'ditolak'): ?>
<div class="alert alert-danger py-2 small">
    <i class="bi bi-x-octagon-fill me-1"></i>Proses ini <strong>DITOLAK</strong> dan berhenti di tahap
    "<?= KepulanganRiwayat::AKSI_LABEL[$item['keamanan_status'] === 'ditolak' ? 'keamanan' : 'pengasuhan'] ?>".
</div>
<?php elseif ($item['status'] === 'selesai'): ?>
<div class="alert alert-success py-2 small">
    <i class="bi bi-check-circle-fill me-1"></i>Santri telah keluar gerbang pada
    <?= e(date('d-m-Y H:i', strtotime((string) $item['gerbang_at']))) ?>. Workflow selesai.
</div>
<?php endif; ?>

<!-- ===== ALUR 4 TAHAP ===== -->
<div class="row g-3 mb-4">
    <?php foreach ($stages as $st): ?>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 <?= $stage === ['keamanan','pengasuhan','gerbang'][$st['no']-2] ?? '' ? '' : '' ?>">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="small fw-bold"><?= $st['no'] ?>. <?= $st['label'] ?></div>
                        <i class="bi <?= $st['icon'] ?> text-muted"></i>
                    </div>
                    <span class="badge bg-<?= $stColor[$st['status']] ?> text-white"><?= $stLabel[$st['status']] ?></span>
                </div>
                <div class="small">
                    <div class="text-muted">Petugas: <strong><?= e($st['petugas'] ?? '-') ?></strong></div>
                    <div class="text-muted">Waktu: <?= $st['at'] ? e(date('d-m-Y H:i', strtotime((string) $st['at']))) : '-' ?></div>
                    <?php if ($st['catatan']): ?>
                        <div class="mt-1 border-top pt-1"><?= e($st['catatan']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <!-- ===== AKSI SESUAI TAHAP & ROLE ===== -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-gear me-2"></i>Tindakan</div>
            <div class="card-body">
                <?php if ($stage === 'keamanan' && $isKeamanan): ?>
                    <form method="POST" action="<?= base_url('/kepulangan/' . $item['id'] . '/keamanan') ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                        <label class="form-label fw-semibold">Approval Keamanan *</label>
                        <div class="d-flex gap-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="keputusan" value="disetujui" id="k-set" checked>
                                <label class="form-check-label" for="k-set">Disetujui</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="keputusan" value="ditolak" id="k-tol">
                                <label class="form-check-label text-danger" for="k-tol">Ditolak</label>
                            </div>
                        </div>
                        <textarea class="form-control mb-2" name="catatan" rows="2"
                                  placeholder="Catatan (wajib jika menolak)"></textarea>
                        <button class="btn btn-brand"><i class="bi bi-check2-square me-1"></i> Simpan Keputusan</button>
                    </form>
                <?php elseif ($stage === 'pengasuhan' && $isPengasuhan): ?>
                    <form method="POST" action="<?= base_url('/kepulangan/' . $item['id'] . '/pengasuhan') ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                        <label class="form-label fw-semibold">Approval Pengasuhan / Kyai *</label>
                        <div class="d-flex gap-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="keputusan" value="disetujui" id="p-set" checked>
                                <label class="form-check-label" for="p-set">Disetujui</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="keputusan" value="ditolak" id="p-tol">
                                <label class="form-check-label text-danger" for="p-tol">Ditolak</label>
                            </div>
                        </div>
                        <textarea class="form-control mb-2" name="catatan" rows="2"
                                  placeholder="Catatan (wajib jika menolak)"></textarea>
                        <button class="btn btn-brand"><i class="bi bi-check2-square me-1"></i> Simpan Keputusan</button>
                    </form>
                <?php elseif ($stage === 'gerbang' && $isKeamanan): ?>
                    <p class="small text-muted mb-2">Semua approval lolos. Catat saat santri benar-benar keluar gerbang.</p>
                    <form method="POST" action="<?= base_url('/kepulangan/' . $item['id'] . '/gerbang') ?>"
                          onsubmit="return confirm('Catat santri KELUAR GERBANG sekarang?')">
                        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                        <button class="btn btn-success"><i class="bi bi-door-open me-1"></i> Izinkan Keluar Gerbang</button>
                    </form>
                <?php elseif ($stage === 'ditolak' || $stage === 'selesai'): ?>
                    <span class="text-muted small">Tidak ada tindakan — proses telah berakhir.</span>
                <?php else: ?>
                    <span class="text-muted small">
                        <i class="bi bi-hourglass-split me-1"></i>
                        Menunggu tahap <strong><?= Kepulangan::STAGE_LABEL[$stage] ?></strong>
                        oleh unit berwenangan (bukan kewenangan Anda).
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detail -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-file-earmark-medical me-2"></i>Detail Pengajuan</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Rekomendasi Medis</span>
                    <span class="text-end" style="max-width:60%"><?= e($item['alasan_medis']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Alamat Tujuan</span>
                    <span class="text-end"><?= e($item['alamat_tujuan']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Rujukan Pemeriksaan</span>
                    <span><?= $item['pemeriksaan_id'] ? '#' . (int) $item['pemeriksaan_id'] . ' — ' . e((string) $item['pemeriksaan_diagnosis']) : '-' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Petugas Medis</span><span><?= e($item['medis_nama'] ?? '-') ?></span></li>
                <li class="list-group-item"><a class="small" href="<?= base_url('/santri/' . $item['santri_id']) ?>">Lihat data santri &rarr;</a></li>
            </ul>
        </div>
    </div>

    <!-- ===== HISTORI ===== -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Histori Proses</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($riwayatList as $r): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">
                            <?= KepulanganRiwayat::AKSI_LABEL[$r['aksi']] ?? e($r['aksi']) ?>
                        </span>
                        <small class="text-muted"><?= e(date('d-m-Y H:i', strtotime($r['created_at']))) ?></small>
                    </div>
                    <div class="small mt-1"><?= e($r['keterangan'] ?? '-') ?></div>
                    <?php if ($r['user_nama']): ?><div class="small text-muted">oleh <?= e($r['user_nama']) ?></div><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>