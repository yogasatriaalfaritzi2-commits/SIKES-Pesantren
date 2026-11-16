<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\Pengasuhan;

 $qs = fn (int $p): string => base_url('/pengasuhan?' . http_build_query(array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'jenis' => $filters['jenis'] !== '' ? $filters['jenis'] : null,
    'status' => $filters['status'] !== '' ? $filters['status'] : null, 'page' => $p,
])));
 $canManage = Auth::hasPermission('pengasuhan.create') || Auth::hasPermission('pengasuhan.edit');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Pengasuhan — Perizinan &amp; Pelanggaran</h1>
        <p class="text-muted mb-0">Catatan pembinaan santri (<?= (int) $data['total'] ?> catatan).</p>
    </div>
    <?php if (Auth::hasPermission('pengasuhan.create')): ?>
    <a href="<?= base_url('/pengasuhan/create') ?>" class="btn btn-brand">
        <i class="bi bi-plus-circle me-1"></i> Catat Baru
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Izin Berjalan',        $counts['izin'],        'bi-calendar-check', 'info'],
        ['Pelanggaran Berjalan', $counts['pelanggaran'], 'bi-exclamation-triangle-fill', 'warning'],
        ['Selesai',              $counts['selesai'],     'bi-check-circle-fill', 'success'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-12 col-md-4">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-<?= $c[3] ?>"><i class="bi <?= $c[2] ?>"></i></div>
                <div>
                    <div class="fw-bold fs-4 lh-1"><?= (int) $c[1] ?></div>
                    <small class="text-muted"><?= $c[0] ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/pengasuhan') ?>">
            <div class="col-md-5">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="santri / keterangan" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Jenis</label>
                <select name="jenis" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (Pengasuhan::JENIS as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['jenis'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (Pengasuhan::STATUS_LABEL as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Tanggal</th><th>Santri</th><th>Jenis</th><th>Keterangan</th><th>Tindakan</th><th>Status</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada catatan.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td class="small text-nowrap"><?= e(date('d-m-Y', strtotime($row['tanggal']))) ?></td>
                    <td>
                        <div class="fw-semibold small"><?= e($row['santri_nama']) ?></div>
                        <small class="text-muted">NIS <?= e($row['nis']) ?><?= $row['kamar_nama'] ? ' · Kam. ' . e($row['kamar_nama']) : '' ?></small>
                    </td>
                    <td>
                        <span class="badge bg-<?= $row['jenis'] === 'izin' ? 'info' : 'danger' ?>-subtle
                                      text-<?= $row['jenis'] === 'izin' ? 'info' : 'danger' ?>-emphasis">
                            <?= Pengasuhan::JENIS[$row['jenis']] ?>
                        </span>
                    </td>
                    <td class="small" style="max-width:220px"><?= e(mb_strimwidth($row['keterangan'], 0, 60, '…')) ?></td>
                    <td class="small text-muted" style="max-width:160px"><?= e($row['tindakan'] ?? '-') ?></td>
                    <td>
                        <span class="badge bg-<?= Pengasuhan::STATUS_COLOR[$row['status']] ?> text-white">
                            <?= Pengasuhan::STATUS_LABEL[$row['status']] ?>
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if (Auth::hasPermission('pengasuhan.edit')): ?>
                            <?php if ($row['status'] === 'berjalan'): ?>
                            <form class="d-inline" method="POST" action="<?= base_url('/pengasuhan/' . $row['id'] . '/selesai') ?>"
                                  onsubmit="return confirm('Tandai selesai?')">
                                <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                                <button class="btn btn-sm btn-outline-success" title="Tandai Selesai"><i class="bi bi-check2-circle"></i></button>
                            </form>
                            <?php endif; ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/pengasuhan/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline form-delete" method="POST" action="<?= base_url('/pengasuhan/' . $row['id'] . '/delete') ?>"
                              data-name="catatan <?= e($row['jenis']) ?> <?= e($row['santri_nama']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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