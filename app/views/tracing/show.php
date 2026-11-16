<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\TracingKontak;
use App\Models\TracingRiwayat;

 $canEdit = Auth::hasPermission('pemeriksaan.edit');
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <a href="<?= base_url('/tracing') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0">Tracing — <?= e($kasus['penyakit']) ?></h1>
        <p class="text-muted small mb-0">
            <?= e($kasus['santri_nama']) ?> (NIS <?= e($kasus['nis']) ?>) ·
            <?= $kasus['kamar_nama'] ? 'Kamar ' . e($kasus['kamar_nama']) . ' — ' . e($kasus['asrama_nama']) : 'Belum ditempatkan' ?> ·
            sejak <?= e(date('d-m-Y', strtotime($kasus['tanggal_mulai']))) ?>
        </p>
    </div>
    <span class="badge bg-<?= $kasus['status_kasus'] === 'aktif' ? 'danger' : 'success' ?>-subtle
                  text-<?= $kasus['status_kasus'] === 'aktif' ? 'danger' : 'success' ?>-emphasis fs-6 px-3 py-2">
        <?= \App\Models\KasusMenular::STATUS_LABEL[$kasus['status_kasus']] ?>
    </span>
    <?php if ($kasus['status_kasus'] === 'aktif' && $canEdit): ?>
    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#resyncModal">
        <i class="bi bi-arrow-repeat me-1"></i> Sinkronkan Kontak
    </button>
    <form method="POST" action="<?= base_url('/tracing/kasus/' . $kasus['id'] . '/tutup') ?>"
          onsubmit="return confirm('Tutup kasus ini? Warning kamar akan hilang.')">
        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
        <button class="btn btn-outline-success btn-sm"><i class="bi bi-check2-circle me-1"></i> Tutup Kasus</button>
    </form>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="alert alert-light border small text-muted">
    <i class="bi bi-info-circle me-1"></i>
    Daftar di bawah adalah <strong>kontak potensial</strong> — penghuni kamar yang sama dengan kasus.
    Status kontak <strong>tidak merupakan kesimpulan medis</strong>; penilaian akhir oleh tenaga medis
    melalui pemeriksaan preventif.
</div>

<?php if ($kasus['status_kasus'] === 'aktif' && $kasus['kamar_nama']): ?>
<div class="alert alert-danger py-2 small">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    <strong>Kamar <?= e($kasus['kamar_nama']) ?> (<?= e($kasus['asrama_nama']) ?> / <?= e($kasus['blok_nama']) ?>)
    memiliki kasus menular aktif.</strong> Pertimbangkan pembatasan kunjungan &amp; penguatan kebersihan.
</div>
<?php endif; ?>

<!-- Rekap status kontak -->
<div class="row g-2 mb-4">
    <?php foreach (TracingKontak::STATUS_LABEL as $st => $label): ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 d-flex justify-content-between align-items-center">
                <span class="badge bg-<?= TracingKontak::STATUS_COLOR[$st] ?> text-white"><?= $label ?></span>
                <span class="fw-bold fs-5"><?= (int) $counts[$st] ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <!-- Daftar kontak potensial -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-diagram-3 me-2"></i>Kontak Potensial (<?= count($kontaks) ?> orang)
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th>Santri</th><th>Status</th><th>Hasil</th><th class="text-end">Tindakan</th>
                    </tr></thead>
                    <tbody>
                    <?php if (!$kontaks): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">
                            Tidak ada kontak potensial (santri index tidak memiliki kamar / tidak ada penghuni lain).
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($kontaks as $k): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-sm"><?= e(strtoupper(mb_substr($k['santri_nama'], 0, 1))) ?></div>
                                    <div class="lh-sm">
                                        <a class="fw-semibold text-decoration-none" href="<?= base_url('/santri/' . $k['santri_id']) ?>">
                                            <?= e($k['santri_nama']) ?>
                                        </a>
                                        <div><small class="text-muted">NIS <?= e($k['nis']) ?> · <?= e($k['jenis_kelamin']) ?></small></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= TracingKontak::STATUS_COLOR[$k['status']] ?> text-white">
                                    <?= TracingKontak::STATUS_LABEL[$k['status']] ?>
                                </span>
                                <?php if ($k['examined_at']): ?>
                                    <div><small class="text-muted"><?= e(date('d-m-Y H:i', strtotime($k['examined_at']))) ?></small></div>
                                <?php endif; ?>
                            </td>
                            <td class="small" style="max-width:260px">
                                <?= $k['hasil_pemeriksaan'] ? e($k['hasil_pemeriksaan']) : '<span class="text-muted">-</span>' ?>
                                <?php if ($k['pemeriksaan_id']): ?>
                                    <div class="mt-1">
                                        <a class="small text-decoration-none" href="<?= base_url('/pemeriksaan/' . $k['pemeriksaan_id']) ?>">
                                            <i class="bi bi-clipboard2-heart me-1"></i>Pemeriksaan preventif #<?= (int) $k['pemeriksaan_id'] ?>
                                            <?= $k['pemeriksaan_status'] === 'menunggu' ? '(menunggu diperiksa)' : '' ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($canEdit && $k['status'] === 'belum_diperiksa'): ?>
                                    <form method="POST" action="<?= base_url('/tracing/kontak/' . $k['id'] . '/periksa') ?>"
                                          onsubmit="return confirm('Buat pemeriksaan preventif untuk <?= e($k['santri_nama']) ?>?')">
                                        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                                        <button class="btn btn-sm btn-brand">
                                            <i class="bi bi-clipboard2-plus me-1"></i>Pemeriksaan Preventif
                                        </button>
                                    </form>
                                <?php elseif ($canEdit && in_array($k['status'], ['dalam_pemeriksaan', 'perlu_tindak_lanjut'], true)): ?>
                                    <form method="POST" action="<?= base_url('/tracing/kontak/' . $k['id'] . '/hasil') ?>"
                                          onsubmit="return confirm('Catat hasil pemeriksaan untuk <?= e($k['santri_nama']) ?>?')">
                                        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <select name="hasil" class="form-select form-select-sm" style="max-width:190px" required>
                                                <option value="">— Pilih hasil —</option>
                                                <option value="sudah_diperiksa">Tidak ada keluhan — selesai</option>
                                                <option value="perlu_tindak_lanjut">Ada keluhan — perlu tindak lanjut</option>
                                            </select>
                                            <button class="btn btn-sm btn-outline-brand">Catat</button>
                                        </div>
                                        <input type="text" name="catatan" class="form-control form-control-sm mt-1"
                                               placeholder="Catatan (wajib jika perlu tindak lanjut)">
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info kasus -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-journal-text me-2"></i>Catatan Kasus</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Penyakit</span><span><?= e($kasus['penyakit']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Tanggal Mulai</span><span><?= e(date('d-m-Y', strtotime($kasus['tanggal_mulai']))) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Tanggal Selesai</span><span><?= $kasus['tanggal_selesai'] ? e(date('d-m-Y', strtotime($kasus['tanggal_selesai']))) : '-' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Pelapor</span><span><?= e($kasus['pelapor_nama'] ?? '-') ?></span></li>
                <li class="list-group-item">
                    <div class="text-muted small mb-1">Catatan Tenaga Medis</div>
                    <div style="white-space: pre-wrap"><?= $kasus['catatan'] ? e($kasus['catatan']) : '<span class="text-muted small">-</span>' ?></div>
                </li>
            </ul>
        </div>
    </div>

    <!-- Histori tracing -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Histori Tracing</div>
            <ul class="list-group list-group-flush">
                <?php if (!$riwayat): ?>
                    <li class="list-group-item text-muted small">Belum ada histori.</li>
                <?php endif; ?>
                <?php foreach ($riwayat as $r): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">
                            <?= TracingRiwayat::AKSI_LABEL[$r['aksi']] ?? e($r['aksi']) ?>
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

<!-- Modal sinkronisasi kontak -->
<div class="modal fade" id="resyncModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sinkronkan Kontak Potensial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body small">
                Sistem akan memeriksa ulang penghuni kamar saat ini dan menambahkan kontak potensial
                yang belum terdaftar (mis. penghuni baru). Kontak yang sudah ada <strong>tidak diubah</strong>.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="<?= base_url('/tracing/kasus/' . $kasus['id'] . '/resync') ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                    <button class="btn btn-brand"><i class="bi bi-arrow-repeat me-1"></i>Sinkronkan</button>
                </form>
            </div>
        </div>
    </div>
</div>