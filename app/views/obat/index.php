<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\Obat;

 $qs = fn (int $p): string => base_url('/obat?' . http_build_query(array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'kategori' => $filters['kategori'] !== '' ? $filters['kategori'] : null,
    'flag' => $filters['flag'] !== '' ? $filters['flag'] : null, 'page' => $p,
])));
 $canManage = Auth::hasPermission('obat.manage');
 $canPakai  = Auth::hasPermission('obat.pemakaian');
 $rupiah    = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Manajemen Obat</h1>
        <p class="text-muted mb-0">Master obat, stok, dan pemakaian poskestren (<?= (int) $data['total'] ?> item).</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canManage): ?>
        <a href="<?= base_url('/obat/masuk') ?>" class="btn btn-outline-brand"><i class="bi bi-box-arrow-in-down me-1"></i> Stok Masuk</a>
        <a href="<?= base_url('/obat/create') ?>" class="btn btn-outline-secondary"><i class="bi bi-plus-circle me-1"></i> Tambah Obat</a>
        <?php endif; ?>
        <?php if ($canPakai): ?>
        <a href="<?= base_url('/obat/pemakaian') ?>" class="btn btn-brand"><i class="bi bi-capsule me-1"></i> Catat Pemakaian</a>
        <?php endif; ?>
        <a href="<?= base_url('/obat/transaksi') ?>" class="btn btn-outline-secondary"><i class="bi bi-clock-history me-1"></i> Histori</a>
        <a href="<?= base_url('/obat/laporan') ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan</a>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<!-- ===== Dashboard anggaran & peringatan (Bendahara + Poskestren) ===== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-2 p-3">
                <div class="stat-icon text-primary"><i class="bi bi-capsule"></i></div>
                <div><div class="fw-bold fs-5 lh-1"><?= (int) $stats['total'] ?></div><small class="text-muted">Item Obat</small></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-2 p-3">
                <div class="stat-icon text-warning"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div><div class="fw-bold fs-5 lh-1"><?= (int) $stats['menipis'] ?></div><small class="text-muted">Stok Menipis</small></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-2 p-3">
                <div class="stat-icon text-info"><i class="bi bi-hourglass-split"></i></div>
                <div><div class="fw-bold fs-5 lh-1"><?= (int) $stats['mendekati'] ?></div><small class="text-muted">Dekat Kedaluwarsa</small></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-2 p-3">
                <div class="stat-icon text-danger"><i class="bi bi-x-octagon-fill"></i></div>
                <div><div class="fw-bold fs-5 lh-1"><?= (int) $stats['kadaluarsa'] ?></div><small class="text-muted">Kedaluwarsa</small></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="fw-bold fs-6 lh-1 text-brand"><?= $rupiah($stats['biayaBulan']) ?></div>
                <small class="text-muted">Biaya Obat Bulan Ini</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="fw-bold fs-6 lh-1 text-brand"><?= $rupiah($stats['nilai']) ?></div>
                <small class="text-muted">Nilai Persediaan</small>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/obat') ?>">
            <div class="col-md-4">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="nama obat" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Kategori</label>
                <select name="kategori" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (Obat::KATEGORI as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['kategori'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Peringatan</label>
                <select name="flag" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="menipis" <?= $filters['flag'] === 'menipis' ? 'selected' : '' ?>>Stok Menipis</option>
                    <option value="mendekati" <?= $filters['flag'] === 'mendekati' ? 'selected' : '' ?>>Dekat Kedaluwarsa</option>
                    <option value="kedaluwarsa" <?= $filters['flag'] === 'kedaluwarsa' ? 'selected' : '' ?>>Kedaluwarsa</option>
                    <option value="aman" <?= $filters['flag'] === 'aman' ? 'selected' : '' ?>>Aman</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Obat</th><th>Kategori / Satuan</th><th style="min-width:140px">Stok</th>
                <th>Harga</th><th>Kedaluwarsa</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada obat pada filter ini.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row):
                $low      = $this === null ? false : true; // placeholder agar IDE tenang
                $low      = (int) $row['stok'] <= (int) $row['minimum_stok'];
                $expired  = strtotime((string) $row['tanggal_kedaluwarsa']) < strtotime(date('Y-m-d'));
                $near     = !$expired && ((strtotime((string) $row['tanggal_kedaluwarsa']) - strtotime(date('Y-m-d'))) / 86400) <= Obat::EXP_WARNING_DAYS;
            ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($row['nama']) ?></div>
                        <?php if ($row['catatan']): ?><small class="text-muted"><?= e(mb_strimwidth($row['catatan'], 0, 45, '…')) ?></small><?php endif; ?>
                    </td>
                    <td class="small">
                        <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= Obat::KATEGORI[$row['kategori']] ?></span>
                        <div class="text-muted"><?= Obat::SATUAN[$row['satuan']] ?></div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:6px">
                                <div class="progress-bar bg-<?= $low ? 'danger' : 'success' ?>"
                                     style="width:<?= min(100, (int) round(((int) $row['stok'] / max(1, (int) $row['minimum_stok'] * 3)) * 100)) ?>%"></div>
                            </div>
                            <small class="text-nowrap"><?= (int) $row['stok'] ?> / min <?= (int) $row['minimum_stok'] ?></small>
                        </div>
                        <?php if ($low): ?>
                            <span class="badge bg-danger-subtle text-danger-emphasis mt-1">Stok Menipis</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-nowrap"><?= $rupiah($row['harga']) ?></td>
                    <td class="small text-nowrap">
                        <?= e(date('d-m-Y', strtotime($row['tanggal_kedaluwarsa']))) ?>
                        <?php if ($expired): ?>
                            <div><span class="badge bg-danger text-white">Kedaluwarsa</span></div>
                        <?php elseif ($near): ?>
                            <div><span class="badge bg-warning text-dark">Dekat Kedaluwarsa</span></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($canManage): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/obat/' . $row['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline form-delete" method="POST" action="<?= base_url('/obat/' . $row['id'] . '/delete') ?>"
                              data-name="obat <?= e($row['nama']) ?>">
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