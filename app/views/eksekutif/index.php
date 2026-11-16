<?php
use App\Core\Auth;

 $qs = fn (array $over = []): string => base_url('/eksekutif?' . http_build_query(
    array_filter(array_merge([
        'periode' => $f['periode'], 'asrama' => $f['asrama'] ?: null, 'blok' => $f['blok'] ?: null,
    ], $over), fn ($v) => $v !== null && $v !== '')
));
 $periodeLabel = ['hari' => 'Hari Ini', 'minggu' => '7 Hari Terakhir', 'bulan' => 'Bulan Ini', 'tahun' => 'Tahun Ini'][$f['periode']];
 $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Dashboard Eksekutif</h1>
        <p class="text-muted mb-0">Ringkasan strategis — <?= $periodeLabel ?> (<?= e(date('d-m-Y', strtotime($f['from']))) ?> s.d. <?= e(date('d-m-Y', strtotime($f['to']))) ?>)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('/eksekutif/export?' . http_build_query(array_filter([
            'periode' => $f['periode'], 'asrama' => $f['asrama'] ?: null, 'blok' => $f['blok'] ?: null]))) ?>"
           class="btn btn-outline-secondary">
            <i class="bi bi-filetype-csv me-1"></i> Ekspor CSV
        </a>
        <a href="<?= base_url('/eksekutif/cetak?' . http_build_query(array_filter([
            'periode' => $f['periode'], 'asrama' => $f['asrama'] ?: null, 'blok' => $f['blok'] ?: null]))) ?>"
           class="btn btn-outline-secondary" target="_blank">
            <i class="bi bi-printer me-1"></i> Cetak
        </a>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<!-- ===== FILTER ===== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/eksekutif') ?>">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Periode</label>
                <select name="periode" class="form-select form-select-sm">
                    <?php foreach (['hari' => 'Hari Ini', 'minggu' => '7 Hari Terakhir',
                                    'bulan' => 'Bulan Ini', 'tahun' => 'Tahun Ini'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $f['periode'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Asrama</label>
                <select name="asrama" id="eAsrama" class="form-select form-select-sm">
                    <option value="">Semua asrama</option>
                    <?php foreach ($asramaOptions as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= $f['asrama'] === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= e($a['nama']) ?> (<?= e($a['jenis']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Blok</label>
                <select name="blok" id="eBlok" class="form-select form-select-sm">
                    <option value="">Semua blok</option>
                    <?php foreach ($blokList as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= $f['blok'] === (int) $b['id'] ? 'selected' : '' ?>>
                        <?= e($b['asrama_nama']) ?> · <?= e($b['nama']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== METRIK UTAMA (9 kartu) ===== -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total Santri',        $metrics['santriTotal'],              'bi-mortarboard',        'primary'],
        ['Sedang Sakit',        $metrics['santriSakit'],              'bi-thermometer-half',   'warning'],
        ['Kasus Periode',       $metrics['kasus'],                    'bi-clipboard2-heart',   'info'],
        ['Isolasi / Inap',      $metrics['rawatInap'],                'bi-person-lines-fill',  'danger'],
        ['Rujukan Periode',     $metrics['rujukan'],                  'bi-envelope-paper',     'secondary'],
        ['Izin Sakit Periode',  $metrics['izin'],                     'bi-file-earmark-medical','success'],
        ['Sanitasi Rata-rata',  $metrics['sanitasi']['rata2'] . '%',  'bi-house-gear',         $metrics['sanitasi']['rata2'] >= 75 ? 'success' : 'danger'],
        ['Pemakaian Obat',      $metrics['obat']['jumlah'] . ' item', 'bi-capsule',            'primary'],
        ['Biaya Obat Periode',  $rupiah($metrics['obat']['biaya']),   'bi-cash-stack',         'warning'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-6 col-md-4 col-xl-4">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon text-<?= $c[3] ?>"><i class="bi <?= $c[2] ?>"></i></div>
                <div>
                    <div class="fw-bold fs-4 lh-1"><?= e((string) $c[1]) ?></div>
                    <small class="text-muted"><?= $c[0] ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ===== GRAFIK: PENYAKIT + PEMAKAIAN OBAT ===== -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-bar-chart me-2"></i>Penyakit Terbanyak</div>
            <div class="card-body"><canvas id="chartPenyakit" height="180"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-capsule me-2"></i>Pemakaian Obat Terbanyak</div>
            <div class="card-body"><canvas id="chartObat" height="180"></canvas></div>
        </div>
    </div>
</div>

<!-- ===== GRAFIK TREN BULANAN ===== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-graph-up me-2"></i>Tren 12 Bulan Terakhir — Kasus · Izin Sakit · Rujukan
    </div>
    <div class="card-body"><canvas id="chartTren" height="90"></canvas></div>
</div>

<!-- ===== HEATMAP ASRAMA/BLOK ===== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-grid-3x3-gap-fill me-2"></i>Heatmap Kondisi per Asrama/Blok
        <small class="text-muted fw-normal">(skor = kasus periode + kasus aktif + sanitasi risiko; makin merah makin perlu perhatian)</small>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <?php foreach ($heatmap as $h):
                $skor = (int) $h['kasus'] + (int) $h['aktif'] * 2 + (int) $h['risiko'] * 2;
                // Warna: 0 hijau, 1-4 kuning muda, 5-9 oranye, >=10 merah
                $bg = $skor === 0 ? '#dcfce7' : ($skor <= 4 ? '#fef9c3' : ($skor <= 9 ? '#fed7aa' : '#fecaca'));
                $tx = $skor <= 4 ? '#334155' : '#7f1d1d';
            ?>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="border rounded-3 p-2 text-center h-100" style="background:<?= $bg ?>;color:<?= $tx ?>">
                    <div class="small fw-bold"><?= e($h['blok']) ?></div>
                    <div style="font-size:.7rem" class="opacity-75"><?= e($h['asrama']) ?> (<?= e($h['jenis']) ?>)</div>
                    <div class="fs-4 fw-bold lh-1 my-1"><?= $skor ?></div>
                    <div style="font-size:.68rem">Kasus <?= (int) $h['kasus'] ?> · Aktif <?= (int) $h['aktif'] ?> · Risiko <?= (int) $h['risiko'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (!$heatmap): ?>
            <div class="col-12 text-center text-muted py-4">Tidak ada blok pada filter ini.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const penyakit   = <?= json_encode($penyakit, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    const obatTop    = <?= json_encode($obatTop,  JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    const tren       = <?= json_encode($tren,     JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    const brand      = '#0f766e', brandL = '#14b8a6', gold = '#e2b04a', red = '#ef4444';

    new Chart(document.getElementById('chartPenyakit'), {
        type: 'bar',
        data: { labels: penyakit.map(p => p.penyakit),
                datasets: [{ label: 'Jumlah Kasus', data: penyakit.map(p => p.total),
                             backgroundColor: brand }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } },
                   scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartObat'), {
        type: 'bar',
        data: { labels: obatTop.map(o => o.nama),
                datasets: [{ label: 'Jumlah Digunakan', data: obatTop.map(o => o.jumlah),
                             backgroundColor: gold }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } },
                   scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartTren'), {
        type: 'line',
        data: { labels: tren.labels,
                datasets: [
                    { label: 'Kasus',        data: tren.kasus,   borderColor: brandL, backgroundColor: 'rgba(20,184,166,.1)', tension: .3, fill: true },
                    { label: 'Izin Sakit',   data: tren.izin,    borderColor: gold,   backgroundColor: 'rgba(226,176,74,.1)', tension: .3, fill: true },
                    { label: 'Rujukan',      data: tren.rujukan, borderColor: red,    backgroundColor: 'rgba(239,68,68,.08)', tension: .3, fill: true }
                ] },
        options: { plugins: { legend: { position: 'bottom' } },
                   scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>