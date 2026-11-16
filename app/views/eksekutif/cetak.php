<?php $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Eksekutif | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', serif; background: #fff; }
        .paper { max-width: 800px; margin: 24px auto; padding: 40px 48px; }
        .kop { text-align: center; border-bottom: 3px double #1e293b; padding-bottom: 10px; margin-bottom: 24px; }
        .kop .t1 { font-weight: 800; font-size: 18px; text-transform: uppercase; color: #0f766e; }
        .kop .t2 { font-size: 12px; color: #475569; }
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
            <div class="t2">Laporan Eksekutif Kesehatan Pondok — <?= e($periodeLabel) ?>
                (<?= e(date('d-m-Y', strtotime($f['from']))) ?> s.d. <?= e(date('d-m-Y', strtotime($f['to']))) ?>)</div>
            <div class="t2">Filter Asrama: <?= $f['asrama'] ? '#' . (int) $f['asrama'] : 'Semua' ?> ·
                Blok: <?= $f['blok'] ? '#' . (int) $f['blok'] : 'Semua' ?> ·
                Dicetak: <?= e(date('d-m-Y H:i')) ?></div>
        </div>

        <h5>1. Metrik Utama</h5>
        <table class="table table-sm table-bordered mb-3">
            <tbody>
                <tr><td style="width:55%">Total Santri Aktif</td><td><?= (int) $metrics['santriTotal'] ?></td></tr>
                <tr><td>Santri Sedang Sakit</td><td><?= (int) $metrics['santriSakit'] ?></td></tr>
                <tr><td>Kasus Kesehatan (kunjungan selesai)</td><td><?= (int) $metrics['kasus'] ?></td></tr>
                <tr><td>Santri Isolasi / Rawat Inap (kasus aktif)</td><td><?= (int) $metrics['rawatInap'] ?></td></tr>
                <tr><td>Jumlah Surat Rujukan</td><td><?= (int) $metrics['rujukan'] ?></td></tr>
                <tr><td>Jumlah Surat Izin Sakit</td><td><?= (int) $metrics['izin'] ?></td></tr>
                <tr><td>Sanitasi Kamar: Rata-rata Skor</td><td><?= e((string) $metrics['sanitasi']['rata2']) ?>%
                    (Baik <?= (int) $metrics['sanitasi']['baik'] ?> · Perhatian <?= (int) $metrics['sanitasi']['perhatian'] ?> · Risiko <?= (int) $metrics['sanitasi']['risiko'] ?>)</td></tr>
                <tr><td>Pemakaian Obat</td><td><?= (int) $metrics['obat']['jumlah'] ?> item — <?= $rupiah($metrics['obat']['biaya']) ?></td></tr>
            </tbody>
        </table>

        <h5>2. Penyakit Terbanyak (Keterangan Awal Tenaga Medis)</h5>
        <table class="table table-sm table-bordered mb-3">
            <thead><tr><th style="width:70%">Penyakit</th><th>Jumlah Kasus</th></tr></thead>
            <tbody>
            <?php foreach ($penyakit as $p): ?>
                <tr><td><?= e($p['penyakit']) ?></td><td><?= (int) $p['total'] ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$penyakit): ?><tr><td colspan="2" class="text-center">-</td></tr><?php endif; ?>
            </tbody>
        </table>

        <h5>3. Pemakaian Obat Terbanyak</h5>
        <table class="table table-sm table-bordered mb-3">
            <thead><tr><th style="width:70%">Obat</th><th>Jumlah Digunakan</th></tr></thead>
            <tbody>
            <?php foreach ($obatTop as $o): ?>
                <tr><td><?= e($o['nama']) ?></td><td><?= (int) $o['jumlah'] ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$obatTop): ?><tr><td colspan="2" class="text-center">-</td></tr><?php endif; ?>
            </tbody>
        </table>

        <h5>4. Heatmap Asrama / Blok</h5>
        <table class="table table-sm table-bordered mb-3">
            <thead><tr>
                <th>Asrama</th><th>Blok</th><th>Kasus Periode</th><th>Kasus Aktif</th><th>Sanitasi Risiko</th><th>Skor</th>
            </tr></thead>
            <tbody>
            <?php foreach ($heatmap as $h): $skor = (int) $h['kasus'] + (int) $h['aktif'] * 2 + (int) $h['risiko'] * 2; ?>
                <tr><td><?= e($h['asrama']) ?> (<?= e($h['jenis']) ?>)</td><td><?= e($h['blok']) ?></td>
                    <td><?= (int) $h['kasus'] ?></td><td><?= (int) $h['aktif'] ?></td>
                    <td><?= (int) $h['risiko'] ?></td><td class="fw-bold"><?= $skor ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$heatmap): ?><tr><td colspan="6" class="text-center">-</td></tr><?php endif; ?>
            </tbody>
        </table>

        <p class="small text-muted mt-4 mb-0">
            Laporan dihasilkan otomatis oleh <?= e(APP_NAME) ?> sebagai rekap data administratif —
            penilaian akhir tetap merupakan kewenangan pimpinan bersama tenaga kesehatan.
        </p>
    </div>
</body>
</html>