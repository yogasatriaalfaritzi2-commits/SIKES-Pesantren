<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\KasusMenular;

 $qs = fn (int $p): string => base_url('/tracing?' . http_build_query(array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'status' => $filters['status'] !== '' ? $filters['status'] : null,
    'page' => $p,
])));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Contact Tracing Penularan</h1>
        <p class="text-muted mb-0">Pemetaan kasus penyakit menular &amp; kontak potensial (<?= (int) $data['total'] ?> kasus).</p>
    </div>
    <?php if (Auth::hasPermission('pemeriksaan.create')): ?>
    <a href="<?= base_url('/tracing/kasus/create') ?>" class="btn btn-brand">
        <i class="bi bi-plus-circle me-1"></i> Catat Kasus
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="alert alert-light border small text-muted mb-3">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Istilah "kontak potensial":</strong> santri yang pernah berada di kamar yang sama dengan kasus.
    Masuk dalam daftar ini <strong>tidak berarti pasti tertular</strong> — hanya penanda untuk dilakukan
    pemeriksaan preventif. Penilaian klinis sepenuhnya merupakan kewenangan tenaga medis.
</div>

<!-- ===== WARNING: kamar dengan kasus aktif ===== -->
<?php if ($kasusAktif): ?>
<div class="alert alert-danger">
    <div class="fw-semibold mb-2">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Perhatian: <?= count($kasusAktif) ?> kasus menular AKTIF
    </div>
    <div class="row g-2">
        <?php foreach ($kasusAktif as $ka): ?>
        <div class="col-md-6">
            <div class="border rounded-3 p-2 bg-white small">
                <div class="fw-semibold text-danger">
                    <i class="bi bi-door-open me-1"></i>
                    <?= $ka['kamar_nama'] ? 'Kamar ' . e($ka['kamar_nama']) : 'Belum ditempatkan' ?>
                    <?= $ka['asrama_nama'] ? ' — ' . e($ka['asrama_nama']) . ' / ' . e($ka['blok_nama'] ?? '') : '' ?>
                </div>
                <div>
                    <strong><?= e($ka['santri_nama']) ?></strong> (NIS <?= e($ka['nis']) ?>) ·
                    <?= e($ka['penyakit']) ?> · sejak <?= e(date('d-m-Y', strtotime($ka['tanggal_mulai']))) ?> ·
                    <?= (int) $ka['kontak_count'] ?> kontak potensial
                </div>
                <a href="<?= base_url('/tracing/kasus/' . $ka['kasus_id']) ?>" class="small">Lihat tracing &rarr;</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/tracing') ?>">
            <div class="col-md-5">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="penyakit / nama / NIS" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Status Kasus</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="aktif" <?= $filters['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="selesai" <?= $filters['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Kasus</th><th>Santri (Index)</th><th>Kamar</th><th>Mulai</th>
                <th>Kontak Potensial</th><th>Status</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada kasus tercatat.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($row['penyakit']) ?></div>
                        <small class="text-muted">Dilaporkan: <?= e($row['pelapor_nama'] ?? '-') ?></small>
                    </td>
                    <td>
                        <a class="fw-semibold text-decoration-none" href="<?= base_url('/santri/' . $row['santri_id']) ?>">
                            <?= e($row['santri_nama']) ?>
                        </a>
                        <div><small class="text-muted">NIS <?= e($row['nis']) ?></small></div>
                    </td>
                    <td class="small">
                        <?php if ($row['kamar_nama']): ?>
                            <?php if ($row['status_kasus'] === 'aktif'): ?>
                                <span class="badge bg-danger-subtle text-danger-emphasis mb-1">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Kasus Aktif
                                </span>
                            <?php endif; ?>
                            <div>Kamar <?= e($row['kamar_nama']) ?></div>
                            <div class="text-muted"><?= e($row['asrama_nama']) ?> / <?= e($row['blok_nama']) ?></div>
                        <?php else: ?>
                            <span class="text-muted">Belum ditempatkan</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-nowrap"><?= e(date('d-m-Y', strtotime($row['tanggal_mulai']))) ?></td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= (int) $row['kontak_count'] ?> orang</span>
                        <?php if ((int) $row['kontak_belum_selesai'] > 0): ?>
                            <div><small class="text-warning-emphasis"><?= (int) $row['kontak_belum_selesai'] ?> belum selesai</small></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $row['status_kasus'] === 'aktif' ? 'danger' : 'success' ?>-subtle
                                      text-<?= $row['status_kasus'] === 'aktif' ? 'danger' : 'success' ?>-emphasis">
                            <?= KasusMenular::STATUS_LABEL[$row['status_kasus']] ?>
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/tracing/kasus/' . $row['id']) ?>">
                            <i class="bi bi-eye me-1"></i>Tracing
                        </a>
                        <?php if ($row['status_kasus'] === 'aktif' && Auth::hasPermission('pemeriksaan.edit')): ?>
                        <form class="d-inline" method="POST" action="<?= base_url('/tracing/kasus/' . $row['id'] . '/tutup') ?>"
                              onsubmit="return confirm('Tutup kasus ini? Warning kamar akan hilang.')">
                            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                            <button class="btn btn-sm btn-outline-success" title="Tutup Kasus"><i class="bi bi-check2-circle"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($data['pages'] > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Halaman <?= $data['page'] ?> dari <?= $data['pages'] ?></small>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $qs(max(1, $data['page'] - 1)) ?>">&laquo;</a></li>
            <li class="page-item <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>"><a class="page-link" href="<?= $qs(min($data['pages'], $data['page'] + 1)) ?>">&raquo;</a></li>
        </ul>
    </div>
    <?php endif; ?>
</div>