<?php use App\Models\RujukanRiwayat; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak <?= e($item['nomor_surat']) ?> | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Times New Roman', serif; }
        .paper { background: #fff; max-width: 800px; margin: 24px auto; padding: 48px 56px;
                 box-shadow: 0 4px 24px rgba(0,0,0,.12); border: 1px solid #e5e7eb; }
        .kop { display: flex; align-items: center; gap: 16px; border-bottom: 3px double #1e293b; padding-bottom: 12px; }
        .kop .t1 { font-size: 15px; font-weight: 700; text-transform: uppercase; }
        .kop .t2 { font-size: 20px; font-weight: 800; text-transform: uppercase; color: #0f766e; }
        .kop .t3 { font-size: 12px; color: #475569; }
        .judul { text-align: center; margin: 28px 0 4px; font-weight: 800; font-size: 16px;
                 text-decoration: underline; text-transform: uppercase; }
        .nomor { text-align: center; font-size: 13px; margin-bottom: 24px; }
        .isi { font-size: 14px; line-height: 1.85; text-align: justify; }
        .isi table td { padding: 1px 6px; vertical-align: top; }
        .isi .bagian { margin: 12px 0 2px; font-weight: 700; }
        .ttd { margin-top: 40px; display: flex; justify-content: flex-end; text-align: center; font-size: 13px; }
        .btn-print { position: fixed; top: 16px; right: 16px; z-index: 10; }
        @media print {
            body { background: #fff; }
            .paper { margin: 0; box-shadow: none; border: 0; max-width: none; padding: 20mm; }
            .btn-print, .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="btn-print d-flex gap-2 no-print">
        <a href="<?= base_url('/rujukan/' . $item['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <button class="btn btn-brand" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Cetak / Simpan PDF
        </button>
    </div>

    <div class="paper">
        <div class="kop">
            <i class="bi bi-heart-pulse" style="font-size:56px;color:#0f766e"></i>
            <div>
                <div class="t1">Pondok Pesantren — Pos Kesehatan (Poskestren)</div>
                <div class="t2">Surat Rujukan</div>
                <div class="t3"><?= e(APP_NAME) ?> — dikelola secara digital oleh tenaga kesehatan.</div>
            </div>
        </div>

        <div class="judul">Surat Rujukan Pasien</div>
        <div class="nomor">Nomor: <strong><?= e($item['nomor_surat']) ?></strong></div>

        <div class="isi">
            <p>Yang bertanda tangan di bawah ini, petugas kesehatan pondok pesantren, menerangkan bahwa:</p>
            <table>
                <tr><td style="width:150px">Nama</td><td>:</td><td><strong><?= e($item['santri_nama']) ?></strong></td></tr>
                <tr><td>NIS / NISN</td><td>:</td><td><?= e($item['nis']) ?><?= $item['nisn'] ? ' / ' . e($item['nisn']) : '' ?></td></tr>
                <tr><td>Jenis Kelamin</td><td>:</td><td><?= $item['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                <tr><td>Asrama / Kamar</td><td>:</td><td>
                    <?= e($item['asrama_nama'] ?? '-') ?><?= $item['blok_nama'] ? ' / ' . e($item['blok_nama']) : '' ?><?= $item['kamar_nama'] ? ' — Kamar ' . e($item['kamar_nama']) : '' ?>
                </td></tr>
            </table>

            <p style="margin-top:14px">Berdasarkan pemeriksaan yang telah dilakukan di poskestren, dengan ini kami
            <strong>merujuk pasien tersebut ke <?= e($item['tujuan_faskes']) ?></strong> untuk mendapatkan penanganan lanjutan,
            dengan data sebagai berikut:</p>

            <div class="bagian">1. Keluhan</div>
            <div><?= nl2br(e($item['keluhan'])) ?></div>

            <div class="bagian">2. Hasil Pemeriksaan</div>
            <div><?= nl2br(e($item['hasil_pemeriksaan'])) ?></div>

            <div class="bagian">3. Diagnosis / Keterangan Awal Tenaga Medis</div>
            <div><?= nl2br(e($item['diagnosis_awal'])) ?></div>

            <?php if ($item['tindakan_sementara']): ?>
            <div class="bagian">4. Tindakan Sementara yang Telah Dilakukan</div>
            <div><?= nl2br(e($item['tindakan_sementara'])) ?></div>
            <?php endif; ?>

            <div class="bagian"><?= $item['tindakan_sementara'] ? '5.' : '4.' ?> Rekomendasi</div>
            <div><?= nl2br(e($item['rekomendasi'])) ?></div>

            <p style="margin-top:14px">Demikian surat rujukan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
        </div>

        <div class="ttd">
            <div>
                <?= e(date('d-m-Y')) ?><br>Petugas Kesehatan,<br><br><br><br>
                <strong><u><?= e($item['penerbit_nama'] ?? 'Petugas Kesehatan') ?></u></strong>
            </div>
        </div>
    </div>

    <div class="text-center text-muted small mb-4 no-print">
        <i class="bi bi-info-circle me-1"></i>
        Untuk menyimpan sebagai <strong>PDF</strong>: klik "Cetak / Simpan PDF" lalu pada dialog cetak browser,
        pilih tujuan <em>"Save as PDF"</em>. Pencetakan ini tercatat pada riwayat surat
        (<?= RujukanRiwayat::AKSI_LABEL['dicetak'] ?>).
    </div>
</body>
</html>