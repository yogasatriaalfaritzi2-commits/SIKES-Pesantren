<?php
 $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Pusat Laporan</h1>
        <p class="text-muted mb-0">Rekap lintas modul — santri, kesehatan, dan biaya obat (12 bulan).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('/laporan/export') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-filetype-csv me-1"></i> Ekspor CSV
        </a>
        <a href="<?= base_url('/laporan/cetak') ?>" target="_blank" class="btn btn-brand">
            <i class="bi bi-printer me-1"></i> Cetak
        </a>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="row g-3 mb-4">
    <!-- A. Santri per asrama -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-building me-2"></i>Santri Aktif per Asrama</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Asrama</th><th>Blok</th><th>Kamar</th><th class="text-end">Santri Aktif</th></tr></thead>
                    <tbody>
                    <?php foreach ($asrama as $a): ?>
                        <tr>
                            <td class="small fw-semibold"><?= e($a['asrama']) ?> <small class="text-muted">(<?= e($a['jenis']) ?>)</small></td>
                            <td class="small"><?= (int) $a['blok'] ?></td>
                            <td class="small"><?= (int) $a['kamar'] ?></td>
                            <td class="text-end fw-bold"><?= (int) $a['santri_aktif'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- B. Tren 12 bulan -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-graph-up me-2"></i>Tren Kesehatan 12 Bulan</div>
            <div class="card-body"><canvas id="chartTren" height="150"></canvas></div>
        </div>
    </div>
</div>

<!-- C. Biaya obat -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-capsule me-2"></i>Biaya Obat 12 Bulan</div>
    <div class="card-body"><canvas id="chartObat" height="80"></canvas></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead><tr><th>Bulan</th><th class="text-end">Item Digunakan</th><th class="text-end">Biaya</th></tr></thead>
            <tbody>
            <?php foreach ($tren['labels'] as $i => $label): if ($obat['item'][$i] === 0 && $obat['biaya'][$i] == 0) continue; ?>
                <tr>
                    <td class="small"><?= e($label) ?></td>
                    <td class="text-end small"><?= (int) $obat['item'][$i] ?></td>
                    <td class="text-end small"><?= $rupiah($obat['biaya'][$i]) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="table-light">
                <td class="fw-bold">TOTAL</td>
                <td class="text-end fw-bold"><?= array_sum($obat['item']) ?></td>
                <td class="text-end fw-bold"><?= $rupiah($obat['grand']) ?></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const tren = <?= json_encode($tren, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    const obat = <?= json_encode(['labels' => $tren['labels'], 'biaya' => $obat['biaya']], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

    new Chart(document.getElementById('chartTren'), {
        type: 'line',
        data: { labels: tren.labels,
                datasets: [
                    { label: 'Kasus',      data: tren.kasus,   borderColor: '#14b8a6', backgroundColor: 'rgba(20,184,166,.1)', tension: .3, fill: true },
                    { label: 'Izin Sakit', data: tren.izin,    borderColor: '#e2b04a', backgroundColor: 'rgba(226,176,74,.1)', tension: .3, fill: true },
                    { label: 'Rujukan',    data: tren.rujukan, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.08)', tension: .3, fill: true }
                ] },
        options: { plugins: { legend: { position: 'bottom' } },
                   scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartObat'), {
        type: 'bar',
        data: { labels: obat.labels,
                datasets: [{ label: 'Biaya Obat (Rp)', data: obat.biaya, backgroundColor: '#0f766e' }] },
        options: { plugins: { legend: { display: false } },
                   scales: { y: { beginAtZero: true } } }
    });
})();
</script>