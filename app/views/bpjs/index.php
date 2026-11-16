<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\Bpjs;

 $qs = fn (int $p): string => base_url('/bpjs?' . http_build_query(array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'status' => $filters['status'] !== '' ? $filters['status'] : null, 'page' => $p,
])));
 $canManage = Auth::hasPermission('bpjs.manage');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Asuransi BPJS Santri</h1>
        <p class="text-muted mb-0">Data kepesertaan BPJS Kesehatan (<?= (int) $data['total'] ?> santri terdata).</p>
    </div>
    <?php if ($canManage): ?>
    <a href="<?= base_url('/bpjs/create') ?>" class="btn btn-brand">
        <i class="bi bi-plus-circle me-1"></i> Tambah Data BPJS
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/bpjs') ?>">
            <div class="col-md-6">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="nama / NIS / nomor BPJS" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (Bpjs::STATUS_LABEL as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
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
                <th>Santri</th><th>Nomor BPJS</th><th>Status</th><th>Faskes TK1</th>
                <th>Pembaruan</th><th>Catatan</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data BPJS.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm"><?= e(strtoupper(mb_substr($row['santri_nama'], 0, 1))) ?></div>
                            <div class="lh-sm">
                                <div class="fw-semibold"><?= e($row['santri_nama']) ?></div>
                                <small class="text-muted">NIS <?= e($row['nis']) ?><?= $row['kamar_nama'] ? ' · Kam. ' . e($row['kamar_nama']) : '' ?></small>
                            </div>
                        </div>
                    </td>
                    <td class="small fw-semibold text-nowrap"><?= e($row['nomor_bpjs']) ?></td>
                    <td>
                        <span class="badge bg-<?= Bpjs::STATUS_COLOR[$row['status']] ?> text-white">
                            <?= Bpjs::STATUS_LABEL[$row['status']] ?>
                        </span>
                    </td>
                    <td class="small"><?= e($row['faskes_tk1']) ?></td>
                    <td class="small text-nowrap"><?= e(date('d-m-Y', strtotime($row['tanggal_pembaruan']))) ?></td>
                    <td class="small text-muted" style="max-width:180px"><?= e($row['catatan'] ?? '-') ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ($canManage): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/bpjs/' . $row['id'] . '/edit') ?>" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form class="d-inline form-delete" method="POST" action="<?= base_url('/bpjs/' . $row['id'] . '/delete') ?>"
                              data-name="data BPJS <?= e($row['santri_nama']) ?>">
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