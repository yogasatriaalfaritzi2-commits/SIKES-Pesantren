<?php $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan SIKES | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', serif; background: #fff; }
        .paper { max-width: 800px; margin: 24px auto; padding: 40px 48px; }
        .kop { text-align: center; border-bottom: 3px double #1e293b; padding-bottom: 10px; margin-bottom: 24px; }
        .kop .t1 { font-weight: 800; font-size: 18px; text-transform: uppercase; color: #0f766e; }
        h5 { font-size: 14px; font-weight: 800; text-transform: uppercase; margin-top: 20px; }
        table { font-size: 13px; }
        .btn-print { position: fixed; top: 16px; right: 16px; }
        @media print { .btn-print, .no-print { display: none !important; }
                       .paper { margin: 0; padding: 10mm; max-width: none; } }
    </style>
</head>
<body>
    <button class="btn btn-brand btn-print" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Cetak
    </button>

    <div class="paper">
        <div class="kop">
            <div class="t1"><?= e(APP_NAME) ?></div>
            <div class="t2">Laporan Pusat — Rekap Santri, Kesehatan &amp; Biaya Obat (12 Bulan)</div>
            <div class="t2">Dicetak: <?= e(date('d-m-Y H:i')) ?></div>
        </div>

        <h5>1. Santri Aktif per Asrama</h5>
        <table class="table table-sm table-bordered mb-3">
            <thead><tr><th>Asrama</th><th>Jenis</th><th>Blok</th><th>Kamar</th><th>Santri Aktif</th></tr></thead>
            <tbody>
            <?php foreach ($asrama as $a): ?>
                <tr><td><?= e($a['asrama']) ?></td><td><?= e($a['jenis']) ?></td>
                    <td><?= (int) $a['blok'] ?></td><td><?= (int) $a['kamar'] ?></td>
                    <td><?= (int) $a['santri_aktif'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h5>2. Tren Kesehatan 12 Bulan</h5>
        <table class="table table-sm table-bordered mb-3">
            <thead><tr><th>Bulan</th><th>Kasus</th><th>Izin Sakit</th><th>Rujukan</th></tr></thead>
            <tbody>
            <?php foreach ($tren['labels'] as $i => $label): ?>
                <tr><td><?= e($label) ?></td><td><?= (int) $tren['kasus'][$i] ?></td>
                    <td><?= (int) $tren['izin'][$i] ?></td><td><?= (int) $tren['rujukan'][$i] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h5>3. Biaya Obat 12 Bulan</h5>
        <table class="table table-sm table-bordered mb-3">
            <thead><tr><th>Bulan</th><th>Item Digunakan</th><th>Biaya</th></tr></thead>
            <tbody>
            <?php foreach ($tren['labels'] as $i => $label): ?>
                <tr><td><?= e($label) ?></td><td><?= (int) $obat['item'][$i] ?></td>
                    <td><?= $rupiah($obat['biaya'][$i]) ?></td></tr>
            <?php endforeach; ?>
            <tr class="fw-bold"><td>TOTAL</td><td><?= array_sum($obat['item']) ?></td>
                <td><?= $rupiah($obat['grand']) ?></td></tr>
            </tbody>
        </table>

        <p class="small text-muted mb-0">Laporan dihasilkan otomatis oleh <?= e(APP_NAME) ?> —
            rekap data administratif, bukan diagnosis medis.</p>
    </div>
</body>
</html>