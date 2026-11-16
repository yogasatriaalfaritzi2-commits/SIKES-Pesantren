<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\GiziDiet;

 $qs = function (array $over = []) use ($filters): string {
    $merged = array_filter(array_merge($filters, ['page' => 1], $over), fn ($v) => $v !== '' && $v !== null && $v !== 0);
    return base_url('/gizi?' . http_build_query($merged));
};
 $labelQs = base_url('/gizi/label-hari-ini?' . http_build_query(array_filter([
    'tanggal' => $filters['tanggal'], 'asrama' => $filters['asrama'] ?: null, 'kamar' => $filters['kamar'] ?: null,
])));
 $canRekomendasi = Auth::hasPermission('gizi.rekomendasi');
 $canDistribusi  = Auth::hasPermission('gizi.distribusi');

 $cards = [
    ['label' => 'Santri Diet Khusus',    'count' => $stats['santri'],  'color' => 'warning',   'icon' => 'bi-egg-fried'],
    ['label' => 'Belum Diproses',        'count' => $stats['belum'],   'color' => 'secondary', 'icon' => 'bi-hourglass-split'],
    ['label' => 'Siap Diambil',          'count' => $stats['siap'],    'color' => 'info',      'icon' => 'bi-bag-check'],
    ['label' => 'Selesai Didistribusikan','count' => $stats['selesai'],'color' => 'success',   'icon' => 'bi-check-circle-fill'],
];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Dapur &amp; Gizi — Diet Khusus</h1>
        <p class="text-muted mb-0">
            Daftar diet <?= e(date('d-m-Y', strtotime($filters['tanggal']))) ?> —
            <?= (int) $stats['total'] ?> label untuk <?= (int) $stats['santri'] ?> santri.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $labelQs ?>" class="btn btn-outline-secondary">
            <i class="bi bi-tags me-1"></i> Cetak Label
        </a>
        <?php if ($canRekomendasi): ?>
        <a href="<?= base_url('/gizi/create') ?>" class="btn btn-brand">
            <i class="bi bi-plus-circle me-1"></i> Input Diet
        </a>
        <?php endif; ?>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<!-- Rekap -->
<div class="row g-3 mb-4">
    <?php foreach ($cards as $c): ?>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-<?= $c['color'] ?>"><i class="bi <?= $c['icon'] ?>"></i></div>
                <div>
                    <div class="fw-bold fs-4 lh-1"><?= (int) $c['count'] ?></div>
                    <small class="text-muted"><?= $c['label'] ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/gizi') ?>">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= e($filters['tanggal']) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Asrama</label>
                <select name="asrama" class="form-select form-select-sm">
                    <option value="">Semua asrama</option>
                    <?php foreach ($asramaOptions as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= $filters['asrama'] === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= e($a['nama']) ?> (<?= e($a['jenis']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Kamar</label>
                <select name="kamar" class="form-select form-select-sm">
                    <option value="">Semua kamar</option>
                    <?php
                    $grouped = [];
                    foreach ($kamarList as $k) { $grouped[$k['asrama_nama'] . ' (' . $k['asrama_jenis'] . ')'][] = $k; }
                    foreach ($grouped as $label => $ks): ?>
                    <optgroup label="<?= e($label) ?>">
                        <?php foreach ($ks as $k): ?>
                        <option value="<?= (int) $k['id'] ?>" <?= $filters['kamar'] === (int) $k['id'] ? 'selected' : '' ?>>
                            <?= e($k['blok_nama']) ?> · Kam. <?= e($k['kamar_nama']) ?>
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Status Distribusi</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (GiziDiet::STATUS_LABEL as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="nama / NIS" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-12 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Terapkan Filter</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Santri</th><th>Kamar / Asrama</th><th>Jenis Diet</th><th>Tanggal</th>
                <th>Status Distribusi</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">
                    Tidak ada diet khusus pada filter ini.
                    <?php if ($canRekomendasi): ?><br><a href="<?= base_url('/gizi/create') ?>">Input rekomendasi diet</a><?php endif; ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): $jenis = GiziDiet::displayJenis($row); ?>
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
                    <td class="small">
                        <?php if ($row['kamar_nama']): ?>
                            Kam. <?= e($row['kamar_nama']) ?> · <?= e($row['blok_nama']) ?>
                            <div class="text-muted"><?= e($row['asrama_nama']) ?> (<?= e($row['asrama_jenis']) ?>)</div>
                        <?php else: ?>
                            <span class="text-muted">Tanpa kamar</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="fw-semibold text-brand"><?= e($jenis) ?></span>
                        <?php if ($row['catatan_medis']): ?>
                            <div class="small text-muted"><?= e(mb_strimwidth($row['catatan_medis'], 0, 60, '…')) ?></div>
                        <?php endif; ?>
                        <div class="small text-muted">Rekomendasi: <?= e($row['rekomendasi_nama'] ?? '-') ?></div>
                    </td>
                    <td class="small text-nowrap"><?= e(date('d-m-Y', strtotime($row['tanggal']))) ?></td>
                    <td>
                        <span class="badge bg-<?= GiziDiet::STATUS_COLOR[$row['status_distribusi']] ?> text-white">
                            <?= GiziDiet::STATUS_LABEL[$row['status_distribusi']] ?>
                        </span>
                        <?php if ($row['distribusi_at']): ?>
                            <div class="small text-muted"><?= e(date('d-m-Y H:i', strtotime($row['distribusi_at']))) ?> · <?= e($row['distribusi_nama'] ?? '-') ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-brand" href="<?= base_url('/gizi/' . $row['id'] . '/label') ?>" title="Cetak Label">
                            <i class="bi bi-tags"></i>
                        </a>
                        <?php if ($canDistribusi): ?>
                        <form class="d-inline-flex gap-1 align-items-center" method="POST"
                              action="<?= base_url('/gizi/' . $row['id'] . '/distribusi') ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                            <select name="status" class="form-select form-select-sm" style="width:130px">
                                <?php foreach (GiziDiet::STATUS_LABEL as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $row['status_distribusi'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-outline-secondary" title="Simpan status"><i class="bi bi-check2"></i></button>
                        </form>
                        <?php endif; ?>
                        <?php if ($canRekomendasi): ?>
                        <form class="d-inline form-delete" method="POST" action="<?= base_url('/gizi/' . $row['id'] . '/delete') ?>"
                              data-name="rekomendasi diet <?= e($jenis) ?> untuk <?= e($row['santri_nama']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
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