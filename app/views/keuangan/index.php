<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\Keuangan;

 $qs = fn (int $p): string => base_url('/keuangan?' . http_build_query(array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'jenis' => $filters['jenis'] !== '' ? $filters['jenis'] : null,
    'kategori' => $filters['kategori'] !== '' ? $filters['kategori'] : null,
    'date_from' => $filters['date_from'] !== '' ? $filters['date_from'] : null,
    'date_to' => $filters['date_to'] !== '' ? $filters['date_to'] : null, 'page' => $p,
])));
 $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
 $canManage = Auth::hasPermission('keuangan.create') || Auth::hasPermission('keuangan.manage');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Keuangan Pondok</h1>
        <p class="text-muted mb-0">Pencatatan pemasukan &amp; pengeluaran (<?= (int) $data['total'] ?> transaksi).</p>
    </div>
    <?php if (Auth::hasPermission('keuangan.create')): ?>
    <a href="<?= base_url('/keuangan/create') ?>" class="btn btn-brand">
        <i class="bi bi-plus-circle me-1"></i> Catat Transaksi
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total Pemasukan', $totals['masuk'], 'bi-arrow-down-circle', 'success'],
        ['Total Pengeluaran', $totals['keluar'], 'bi-arrow-up-circle', 'danger'],
        ['Saldo', $totals['saldo'], 'bi-wallet2', 'primary'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-12 col-md-4">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-<?= $c[3] ?>"><i class="bi <?= $c[2] ?>"></i></div>
                <div>
                    <div class="fw-bold fs-5 lh-1"><?= $rupiah($c[1]) ?></div>
                    <small class="text-muted"><?= $c[0] ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/keuangan') ?>">
            <div class="col-md-3">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="keterangan / referensi" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Jenis</label>
                <select name="jenis" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (Keuangan::JENIS as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['jenis'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Kategori</label>
                <select name="kategori" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (Keuangan::KATEGORI as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['kategori'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-3 col-md-2">
                <label class="form-label small mb-1">Dari</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-3 col-md-2">
                <label class="form-label small mb-1">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Tanggal</th><th>Jenis</th><th>Kategori</th><th>Keterangan</th><th>Referensi</th><th class="text-end">Jumlah</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td class="small text-nowrap"><?= e(date('d-m-Y', strtotime($row['tanggal']))) ?></td>
                    <td>
                        <span class="badge bg-<?= $row['jenis'] === 'masuk' ? 'success' : 'danger' ?>-subtle
                                      text-<?= $row['jenis'] === 'masuk' ? 'success' : 'danger' ?>-emphasis">
                            <?= Keuangan::JENIS[$row['jenis']] ?>
                        </span>
                    </td>
                    <td class="small"><?= Keuangan::KATEGORI[$row['kategori']] ?? e($row['kategori']) ?></td>
                    <td class="small"><?= e($row['keterangan'] ?? '-') ?></td>
                    <td class="small text-muted"><?= e($row['referensi'] ?? '-') ?></td>
                    <td class="text-end fw-semibold text-nowrap
                               <?= $row['jenis'] === 'masuk' ? 'text-success' : 'text-danger' ?>">
                        <?= $row['jenis'] === 'masuk' ? '+' : '−' ?> <?= $rupiah($row['jumlah']) ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if (Auth::hasPermission('keuangan.manage')): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/keuangan/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline form-delete" method="POST" action="<?= base_url('/keuangan/' . $row['id'] . '/delete') ?>"
                              data-name="transaksi <?= e($row['tanggal']) ?> (<?= e(Keuangan::KATEGORI[$row['kategori']] ?? '') ?>)">
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