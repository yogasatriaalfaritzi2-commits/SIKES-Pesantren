<?php
use App\Core\Auth;
use App\Models\Kepulangan;

 $qs = fn (int $p): string => base_url('/kepulangan?' . http_build_query(array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'status' => $filters['status'] !== '' ? $filters['status'] : null, 'page' => $p,
])));
 $isMedis = Auth::isSuper() || Auth::hasRole('tenaga-medis');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Kepulangan Santri Sakit</h1>
        <p class="text-muted mb-0">Alur: Rekomendasi Medis &rarr; Approval Keamanan &rarr; Approval Pengasuhan/Kyai &rarr; Izin Keluar Gerbang (<?= (int) $data['total'] ?> proses).</p>
    </div>
    <?php if ($isMedis): ?>
    <a href="<?= base_url('/kepulangan/create') ?>" class="btn btn-brand">
        <i class="bi bi-file-earmark-medical me-1"></i> Rekomendasi Medis
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/kepulangan') ?>">
            <div class="col-md-6">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="nama / NIS / alamat tujuan" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (Kepulangan::STATUS_LABEL as $v => $l): ?>
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
                <th>Santri</th><th>Rekomendasi Medis</th><th>Tujuan</th><th>Diajukan</th>
                <th>Tahap Saat Ini</th><th>Status</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada proses kepulangan.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): $stage = Kepulangan::currentStage($row); ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm"><?= e(strtoupper(mb_substr($row['santri_nama'], 0, 1))) ?></div>
                            <div class="lh-sm">
                                <div class="fw-semibold"><?= e($row['santri_nama']) ?></div>
                                <small class="text-muted">NIS <?= e($row['nis']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td class="small" style="max-width:220px"><?= e(mb_strimwidth($row['alasan_medis'], 0, 70, '…')) ?></td>
                    <td class="small text-muted" style="max-width:150px"><?= e(mb_strimwidth($row['alamat_tujuan'], 0, 40, '…')) ?></td>
                    <td class="small text-nowrap"><?= e(date('d-m-Y', strtotime($row['tanggal_pengajuan']))) ?></td>
                    <td class="small"><?= Kepulangan::STAGE_LABEL[$stage] ?></td>
                    <td>
                        <span class="badge bg-<?= Kepulangan::STATUS_COLOR[$row['status']] ?> text-white">
                            <?= Kepulangan::STATUS_LABEL[$row['status']] ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/kepulangan/' . $row['id']) ?>">
                            <i class="bi bi-eye me-1"></i>Proses
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