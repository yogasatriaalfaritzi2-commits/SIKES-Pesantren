<?php
use App\Core\Auth;

 $qs = fn (int $p): string => base_url('/rujukan?' . http_build_query(array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'date_from' => $filters['date_from'] !== '' ? $filters['date_from'] : null,
    'date_to' => $filters['date_to'] !== '' ? $filters['date_to'] : null,
    'santri_id' => $filters['santri_id'] ?: null, 'page' => $p,
])));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Surat Rujukan Eksternal</h1>
        <p class="text-muted mb-0">Histori surat rujukan ke fasilitas kesehatan (<?= (int) $data['total'] ?> surat).</p>
    </div>
    <?php if (Auth::hasPermission('rujukan.create')): ?>
    <a href="<?= base_url('/rujukan/create') ?>" class="btn btn-brand">
        <i class="bi bi-envelope-plus me-1"></i> Terbitkan Rujukan
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/rujukan') ?>">
            <div class="col-md-4">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="nomor / nama / NIS / tujuan" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Dari</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Santri</label>
                <input type="number" name="santri_id" class="form-control form-control-sm" min="0"
                       placeholder="ID santri (opsional)" value="<?= $filters['santri_id'] ?: '' ?>">
            </div>
            <div class="col-6 col-md-1 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Nomor Surat</th><th>Tanggal</th><th>Santri</th><th>Tujuan Faskes</th>
                <th>Keterangan Awal</th><th>Penerbit</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada surat rujukan.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td class="small fw-semibold text-nowrap"><?= e($row['nomor_surat']) ?></td>
                    <td class="small text-nowrap"><?= e(date('d-m-Y', strtotime($row['tanggal_rujukan']))) ?></td>
                    <td>
                        <div class="fw-semibold"><?= e($row['santri_nama']) ?></div>
                        <small class="text-muted">NIS <?= e($row['nis']) ?></small>
                    </td>
                    <td class="small"><?= e($row['tujuan_faskes']) ?></td>
                    <td class="small text-muted" style="max-width:200px"><?= e(mb_strimwidth($row['diagnosis_awal'], 0, 60, '…')) ?></td>
                    <td class="small text-muted"><?= e($row['penerbit_nama'] ?? '-') ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/rujukan/' . $row['id']) ?>" title="Preview">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a class="btn btn-sm btn-outline-brand" href="<?= base_url('/rujukan/' . $row['id'] . '/cetak') ?>" title="Cetak / PDF">
                            <i class="bi bi-printer"></i>
                        </a>
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