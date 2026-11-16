<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\Pemeriksaan;

 $qs = function (array $over = []) use ($filters): string {
    $merged = array_filter(
        array_merge($filters, ['page' => 1], $over),
        fn ($v) => $v !== '' && $v !== null
    );
    return base_url('/pemeriksaan?' . http_build_query($merged));
};
 $statusMap = ['menunggu' => 'warning', 'selesai' => 'success', 'batal' => 'secondary'];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Pemeriksaan Kesehatan</h1>
        <p class="text-muted mb-0">Registrasi &amp; pemeriksaan kunjungan poskestren (<?= (int) $data['total'] ?> kunjungan).</p>
    </div>
    <?php if (Auth::hasPermission('pemeriksaan.create')): ?>
    <a href="<?= base_url('/pemeriksaan/create') ?>" class="btn btn-brand">
        <i class="bi bi-person-plus me-1"></i> Registrasi Kunjungan
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/pemeriksaan') ?>">
            <div class="col-md-4">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="nama / NIS santri" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (Pemeriksaan::STATUS_LABEL as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-1 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Waktu</th><th>Santri</th><th>Keluhan</th><th>Jenis</th><th>Status</th>
                <th>Tanda Vital</th><th>Pemeriksa</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada kunjungan.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td class="small text-nowrap"><?= e(date('d-m-Y H:i', strtotime($row['tanggal_kunjungan']))) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm"><?= e(strtoupper(mb_substr($row['santri_nama'], 0, 1))) ?></div>
                            <div class="lh-sm">
                                <div class="fw-semibold"><?= e($row['santri_nama']) ?></div>
                                <small class="text-muted">NIS <?= e($row['nis']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td class="small" style="max-width:220px"><?= e(mb_strimwidth($row['keluhan'], 0, 70, '…')) ?></td>
                    <td class="small"><?= Pemeriksaan::JENIS_LABEL[$row['jenis_kunjungan']] ?? e($row['jenis_kunjungan']) ?></td>
                    <td>
                        <span class="badge bg-<?= $statusMap[$row['status']] ?>-subtle text-<?= $statusMap[$row['status']] ?>-emphasis">
                            <?= Pemeriksaan::STATUS_LABEL[$row['status']] ?>
                        </span>
                    </td>
                    <td class="small text-nowrap">
                        <?php if ($row['suhu_c'] !== null || $row['td_sistol'] !== null): ?>
                            <?= $row['td_sistol'] !== null ? e($row['td_sistol'] . '/' . $row['td_diastol']) . ' mmHg' : '' ?>
                            <?= $row['suhu_c'] !== null ? ($row['td_sistol'] !== null ? ' · ' : '') . e((string) $row['suhu_c']) . '°C' : '' ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted">
                        <?= $row['pemeriksa_nama'] ? e($row['pemeriksa_nama']) : '<span class="text-muted">-</span>' ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/pemeriksaan/' . $row['id']) ?>" title="Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if ($row['status'] === 'menunggu' && Auth::hasPermission('pemeriksaan.edit')): ?>
                        <a class="btn btn-sm btn-brand" href="<?= base_url('/pemeriksaan/' . $row['id'] . '/periksa') ?>" title="Lakukan Pemeriksaan">
                            <i class="bi bi-clipboard2-heart"></i>
                        </a>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/pemeriksaan/riwayat/' . $row['santri_id']) ?>" title="Riwayat Kesehatan">
                            <i class="bi bi-clock-history"></i>
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
            <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $qs(['page' => max(1, $data['page'] - 1)]) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $qs(['page' => min($data['pages'], $data['page'] + 1)]) ?>">&raquo;</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</div>