<?php
use App\Models\ObatTransaksi;

 $qs = fn (int $p): string => base_url('/obat/transaksi?' . http_build_query(array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'jenis' => $filters['jenis'] !== '' ? $filters['jenis'] : null,
    'date_from' => $filters['date_from'] !== '' ? $filters['date_from'] : null,
    'date_to' => $filters['date_to'] !== '' ? $filters['date_to'] : null, 'page' => $p,
])));
 $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Histori Transaksi Obat</h1>
        <p class="text-muted mb-0">Seluruh stok masuk &amp; keluar/pemakaian (<?= (int) $data['total'] ?> transaksi).</p>
    </div>
    <a href="<?= base_url('/obat') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Master Obat</a>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/obat/transaksi') ?>">
            <div class="col-md-3">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="obat / keterangan" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Jenis</label>
                <select name="jenis" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (ObatTransaksi::JENIS_LABEL as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['jenis'] === $v ? 'selected' : '' ?>><?= $l ?></option>
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
            <div class="col-md-3 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Waktu</th><th>Obat</th><th>Jenis</th><th>Jumlah</th><th>Harga Satuan</th><th>Total</th><th>Keterangan</th><th>Oleh</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td class="small text-nowrap"><?= e(date('d-m-Y H:i', strtotime($row['created_at']))) ?></td>
                    <td class="fw-semibold small"><?= e($row['obat_nama']) ?></td>
                    <td>
                        <span class="badge bg-<?= $row['jenis'] === 'masuk' ? 'success' : 'warning' ?> text-white">
                            <?= $row['jenis'] === 'masuk' ? 'Masuk' : 'Keluar' ?>
                        </span>
                        <?php if ($row['pemeriksaan_id']): ?>
                            <div><small class="text-muted">Pemeriksaan #<?= (int) $row['pemeriksaan_id'] ?></small></div>
                        <?php endif; ?>
                    </td>
                    <td class="small fw-semibold"><?= (int) $row['jumlah'] ?> <?= e($row['satuan']) ?></td>
                    <td class="small text-nowrap"><?= $rupiah($row['harga_satuan']) ?></td>
                    <td class="small text-nowrap fw-semibold"><?= $rupiah((int) $row['jumlah'] * (float) $row['harga_satuan']) ?></td>
                    <td class="small text-muted"><?= e($row['keterangan'] ?? '-') ?></td>
                    <td class="small text-muted"><?= e($row['user_nama'] ?? '-') ?></td>
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