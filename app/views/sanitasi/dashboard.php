<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\InspeksiKamar;

 $qs = function (array $over = []) use ($filters): string {
    $merged = array_filter(array_merge($filters, $over), fn ($v) => $v !== '' && $v !== null && $v !== 0);
    return base_url('/sanitasi?' . http_build_query($merged));
};
 $cards = [
    ['label' => 'Baik',             'count' => $data['stats']['baik'],            'color' => 'success',   'icon' => 'bi-check-circle-fill'],
    ['label' => 'Perlu Perhatian',  'count' => $data['stats']['perlu_perhatian'], 'color' => 'warning',   'icon' => 'bi-exclamation-circle-fill'],
    ['label' => 'Risiko Tinggi',    'count' => $data['stats']['risiko_tinggi'],   'color' => 'danger',    'icon' => 'bi-exclamation-triangle-fill'],
    ['label' => 'Belum Diinspeksi', 'count' => $data['stats']['belum'],           'color' => 'secondary', 'icon' => 'bi-question-circle-fill'],
];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Sanitasi &amp; Lingkungan</h1>
        <p class="text-muted mb-0">Kondisi kebersihan terkini seluruh kamar (<?= count($data['rows']) ?> kamar).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('/sanitasi/inspeksi') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i> Histori Inspeksi
        </a>
        <?php if (Auth::hasPermission('sanitasi.create')): ?>
        <a href="<?= base_url('/sanitasi/inspeksi/create') ?>" class="btn btn-brand">
            <i class="bi bi-clipboard2-check me-1"></i> Input Inspeksi
        </a>
        <?php endif; ?>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if (!empty($kasusAktif)): ?>
<div class="alert alert-danger">
    <div class="fw-semibold mb-2">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Warning Sanitasi: <?= count($kasusAktif) ?> kamar memiliki kasus menular AKTIF
    </div>
    <ul class="mb-0 small">
        <?php foreach ($kasusAktif as $ka): ?>
        <li>
            <strong><?= $ka['kamar_nama'] ? 'Kamar ' . e($ka['kamar_nama']) : 'Tanpa kamar' ?></strong>
            (<?= e($ka['asrama_nama'] ?? '-') ?>) — <?= e($ka['penyakit']) ?>
            (index: <?= e($ka['santri_nama']) ?>, <?= (int) $ka['kontak_count'] ?> kontak potensial) —
            <a href="<?= base_url('/tracing/kasus/' . $ka['kasus_id']) ?>">lihat tracing</a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Rekap status -->
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
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/sanitasi') ?>">
            <div class="col-6 col-md-4">
                <label class="form-label small mb-1">Asrama</label>
                <select name="asrama" id="fAsrama" class="form-select form-select-sm">
                    <option value="">Semua asrama</option>
                    <?php foreach ($asramaOptions as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= $filters['asrama'] === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= e($a['nama']) ?> (<?= e($a['jenis']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-1">Blok</label>
                <select name="blok" id="fBlok" class="form-select form-select-sm">
                    <option value="">Semua blok</option>
                    <?php
                    $grouped = [];
                    foreach ($blokList as $b) { $grouped[$b['asrama_nama']][] = $b; }
                    foreach ($grouped as $asr => $bloks): ?>
                    <optgroup label="<?= e($asr) ?>">
                        <?php foreach ($bloks as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" <?= $filters['blok'] === (int) $b['id'] ? 'selected' : '' ?>><?= e($b['nama']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            <div class="col-6 col-md-2 d-grid">
                <a href="<?= base_url('/sanitasi') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Kamar</th><th>Blok / Asrama</th><th>Penghuni</th>
                <th style="min-width:150px">Kondisi Terakhir</th><th>Tanggal Inspeksi</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada kamar pada filter ini.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td class="fw-semibold">Kamar <?= e($row['nama']) ?></td>
                    <td class="small">
                        <?= e($row['blok_nama']) ?> ·
                        <?= e($row['asrama_nama']) ?>
                        <span class="badge bg-<?= $row['asrama_jenis'] === 'putra' ? 'info' : 'danger' ?>-subtle
                                      text-<?= $row['asrama_jenis'] === 'putra' ? 'info' : 'danger' ?>-emphasis ms-1">
                            <?= e(ucfirst($row['asrama_jenis'])) ?>
                        </span>
                    </td>
                    <td class="small"><?= (int) $row['terisi'] ?>/<?= (int) $row['kapasitas'] ?></td>
                    <td>
                        <?php if ($row['inspeksi_id']): ?>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-<?= InspeksiKamar::STATUS_COLOR[$row['status']] ?> text-white">
                                    <?= InspeksiKamar::STATUS_LABEL[$row['status']] ?>
                                </span>
                                <div class="progress flex-grow-1" style="height:6px; min-width:60px">
                                    <div class="progress-bar bg-<?= InspeksiKamar::STATUS_COLOR[$row['status']] ?>"
                                         style="width:<?= (float) $row['persentase'] ?>%"></div>
                                </div>
                            </div>
                            <small class="text-muted">Skor <?= (int) $row['skor'] ?>/16 (<?= e((string) $row['persentase']) ?>%)</small>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">Belum diinspeksi</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-nowrap">
                        <?= $row['tanggal_inspeksi'] ? e(date('d-m-Y', strtotime($row['tanggal_inspeksi']))) : '-' ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($row['inspeksi_id']): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/sanitasi/inspeksi/' . $row['inspeksi_id']) ?>" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (Auth::hasPermission('sanitasi.create')): ?>
                        <a class="btn btn-sm btn-<?= $row['inspeksi_id'] ? 'outline-brand' : 'brand' ?>"
                           href="<?= base_url('/sanitasi/inspeksi/create?kamar_id=' . $row['id']) ?>"
                           title="<?= $row['inspeksi_id'] ? 'Inspeksi Ulang' : 'Inspeksi Sekarang' ?>">
                            <i class="bi bi-clipboard2-plus"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Filter blok otomatis mengikuti asrama yang dipilih
document.getElementById('fAsrama')?.addEventListener('change', function () {
    const url = '<?= base_url('/master/blok-options') ?>?asrama_id=' + this.value;
    fetch(url).then(r => r.json()).then(res => {
        const sel = document.getElementById('fBlok');
        const current = sel.value;
        sel.innerHTML = '<option value="">Semua blok</option>' +
            res.data.map(b => `<option value="${b.id}">${b.nama}</option>`).join('');
        if ([...sel.options].some(o => o.value === current)) sel.value = current;
    });
});
</script>