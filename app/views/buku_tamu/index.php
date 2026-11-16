<?php
use App\Core\Auth;
use App\Core\Session;

 $qs = fn (int $p): string => base_url('/buku-tamu?' . http_build_query(array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'date' => $filters['date'] !== '' ? $filters['date'] : null,
    'inside' => $filters['inside'] ? '1' : null, 'page' => $p,
])));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Buku Tamu Penjenguk</h1>
        <p class="text-muted mb-0">Registrasi tamu yang menjenguk santri (<?= (int) $data['total'] ?> entri).</p>
    </div>
    <?php if (Auth::hasPermission('buku_tamu.manage')): ?>
    <a href="<?= base_url('/buku-tamu/create') ?>" class="btn btn-brand">
        <i class="bi bi-person-plus me-1"></i> Catat Tamu Masuk
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/buku-tamu') ?>">
            <div class="col-md-4">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="nama penjenguk / santri / NIS / telp" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Tanggal</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= e($filters['date']) ?>">
            </div>
            <div class="col-6 col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="inside" value="1" id="inside"
                           <?= $filters['inside'] ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="inside">Hanya yang masih di dalam</label>
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Penjenguk</th><th>Santri Dijenguk</th><th>Tanggal</th><th>Datang</th><th>Pulang</th>
                <th>Status</th><th>Tujuan</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data tamu.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): $inside = $row['waktu_pulang'] === null; ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($row['nama_penjenguk']) ?></div>
                        <small class="text-muted"><?= e($row['hubungan']) ?><?= $row['no_telepon'] ? ' · ' . e($row['no_telepon']) : '' ?></small>
                    </td>
                    <td class="small">
                        <div class="fw-semibold"><?= e($row['santri_nama']) ?></div>
                        <small class="text-muted">NIS <?= e($row['nis']) ?><?= $row['kamar_nama'] ? ' · Kam. ' . e($row['kamar_nama']) : '' ?></small>
                    </td>
                    <td class="small text-nowrap"><?= e(date('d-m-Y', strtotime($row['tanggal']))) ?></td>
                    <td class="small"><?= e(substr($row['waktu_datang'], 0, 5)) ?></td>
                    <td class="small"><?= $row['waktu_pulang'] ? e(substr($row['waktu_pulang'], 0, 5)) : '-' ?></td>
                    <td>
                        <span class="badge bg-<?= $inside ? 'warning' : 'success' ?> text-white">
                            <?= $inside ? 'Di Dalam' : 'Sudah Pulang' ?>
                        </span>
                    </td>
                    <td class="small" style="max-width:200px">
                        <?= e(mb_strimwidth($row['tujuan'], 0, 50, '…')) ?>
                        <?php if ($row['catatan']): ?><div class="text-muted"><?= e(mb_strimwidth($row['catatan'], 0, 40, '…')) ?></div><?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if (Auth::hasPermission('buku_tamu.manage')): ?>
                            <?php if ($inside): ?>
                            <form class="d-inline-flex gap-1 align-items-center" method="POST"
                                  action="<?= base_url('/buku-tamu/' . $row['id'] . '/pulang') ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                                <input type="time" name="waktu_pulang" class="form-control form-control-sm" style="width:100px"
                                       value="<?= date('H:i') ?>" required>
                                <button class="btn btn-sm btn-outline-success" title="Catat Pulang"><i class="bi bi-box-arrow-right"></i></button>
                            </form>
                            <?php endif; ?>
                            <form class="d-inline form-delete" method="POST" action="<?= base_url('/buku-tamu/' . $row['id'] . '/delete') ?>"
                                  data-name="entri tamu <?= e($row['nama_penjenguk']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
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