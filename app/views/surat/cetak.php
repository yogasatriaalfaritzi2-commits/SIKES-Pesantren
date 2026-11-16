<?php
use App\Models\SuratIzin;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak <?= e($item['nomor_surat']) ?> | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Times New Roman', serif; }
        .paper { background: #fff; max-width: 800px; margin: 24px auto; padding: 48px 56px;
                 box-shadow: 0 4px 24px rgba(0,0,0,.12); border: 1px solid #e5e7eb; }
        .kop { display: flex; align-items: center; gap: 16px; border-bottom: 3px double #1e293b; padding-bottom: 12px; }
        .kop img { height: 72px; }
        .kop .t1 { font-size: 15px; font-weight: 700; text-transform: uppercase; }
        .kop .t2 { font-size: 20px; font-weight: 800; text-transform: uppercase; color: #0f766e; }
        .kop .t3 { font-size: 12px; color: #475569; }
        .judul { text-align: center; margin: 28px 0 4px; font-weight: 800; font-size: 16px;
                 text-decoration: underline; text-transform: uppercase; }
        .nomor { text-align: center; font-size: 13px; margin-bottom: 24px; }
        .isi { font-size: 14px; line-height: 1.85; text-align: justify; }
        .isi table td { padding: 1px 6px; vertical-align: top; }
        .ttd { margin-top: 36px; display: flex; justify-content: space-between; align-items: flex-start; }
        .qr-box { text-align: center; font-size: 10px; color: #475569; }
        .badge-status { font-size: 12px; }
        .btn-print { position: fixed; top: 16px; right: 16px; z-index: 10; }
        @media print {
            body { background: #fff; }
            .paper { margin: 0; box-shadow: none; border: 0; max-width: none; padding: 20mm; }
            .btn-print, .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <button class="btn btn-brand btn-print" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Cetak
    </button>

    <div class="paper">
        <!-- Kop surat -->
        <div class="kop">
            <i class="bi bi-heart-pulse" style="font-size:56px;color:#0f766e"></i>
            <div>
                <div class="t1">Pondok Pesantren</div>
                <div class="t2">Pos Kesehatan (Poskestren)</div>
                <div class="t3"><?= e(APP_NAME) ?> — Sistem dikelola secara digital, keaslian diverifikasi via QR Code.</div>
            </div>
        </div>

        <div class="judul">Surat Keterangan Izin Sakit</div>
        <div class="nomor">Nomor: <strong><?= e($item['nomor_surat']) ?></strong></div>

        <div class="isi">
            <p>Yang bertanda tangan di bawah ini, petugas kesehatan pondok pesantren, menerangkan bahwa:</p>
            <table>
                <tr><td>Nama</td><td>:</td><td><strong><?= e($item['santri_nama']) ?></strong></td></tr>
                <tr><td>NIS / NISN</td><td>:</td><td><?= e($item['nis']) ?><?= $item['nisn'] ? ' / ' . e($item['nisn']) : '' ?></td></tr>
                <tr><td>Jenis Kelamin</td><td>:</td><td><?= $item['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                <tr><td>Asrama / Kamar</td><td>:</td><td>
                    <?= e($item['asrama_nama'] ?? '-') ?><?= $item['blok_nama'] ? ' / ' . e($item['blok_nama']) : '' ?><?= $item['kamar_nama'] ? ' — Kamar ' . e($item['kamar_nama']) : '' ?>
                </td></tr>
            </table>

            <p style="margin-top:14px">
                Berdasarkan pemeriksaan kesehatan<?= $item['pemeriksaan_diagnosis'] ? ' (diagnosa: ' . e((string) $item['pemeriksaan_diagnosis']) . ')' : '' ?>,
                santri tersebut <strong>tidak dalam kondisi mengikuti kegiatan pondok</strong> dan diberikan izin tidak mengikuti
                seluruh kegiatan untuk jangka waktu:
            </p>
            <table>
                <tr><td>Mulai</td><td>:</td><td><strong><?= e(date('d-m-Y', strtotime($item['tanggal_mulai']))) ?></strong></td></tr>
                <tr><td>Selesai</td><td>:</td><td><strong><?= e(date('d-m-Y', strtotime($item['tanggal_selesai']))) ?></strong></td></tr>
            </table>

            <p style="margin-top:14px"><strong>Keterangan:</strong><br><?= nl2br(e($item['keterangan_medis'])) ?></p>

            <p>Demikian surat keterangan ini diterbitkan untuk keperluan administrasi pondok pesantren.
            Keaslian surat dapat diverifikasi dengan memindai QR Code di samping.</p>
        </div>

        <div class="ttd">
            <div style="font-size:12px;color:#64748b">
                Status surat:<br>
                <span class="badge badge-status bg-<?= SuratIzin::STATUS_COLOR[$item['status']] ?>">
                    <?= SuratIzin::STATUS_LABEL[$item['status']] ?>
                </span>
                <?php if ($item['status'] === 'dibatalkan'): ?>
                    <br><span style="color:#b91c1c">Surat ini tidak sah.</span>
                <?php endif; ?>
            </div>
            <div class="qr-box">
                <div id="qrcode"></div>
                <div style="max-width:150px;word-break:break-all;margin:0 auto">
                    Verifikasi: <?= e(substr($item['token'], 0, 24)) ?>…
                </div>
                <div style="margin-top:4px"><?= e(date('d-m-Y')) ?><br>
                    <strong><?= e($item['penerbit_nama'] ?? 'Petugas Kesehatan') ?></strong><br>
                    Petugas Kesehatan
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
    new QRCode(document.getElementById('qrcode'), {
        text: '<?= e($verifyUrl) ?>',
        width: 110, height: 110,
        correctLevel: QRCode.CorrectLevel.M
    });
    </script>
</body>
</html>