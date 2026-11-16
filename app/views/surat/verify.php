<?php
use App\Models\SuratIzin;

// Tentukan tampilan status verifikasi
if (!$item) {
    $ok = false; $cls = 'danger'; $icon = 'bi-x-octagon-fill';
    $title = 'Surat Tidak Ditemukan';
    $desc  = 'QR Code tidak dikenali. Pastikan Anda memindai QR resmi dari surat izin sakit pondok.';
} elseif ($item['status'] === 'dibatalkan') {
    $ok = false; $cls = 'danger'; $icon = 'bi-x-octagon-fill';
    $title = 'TIDAK VALID — Surat Dibatalkan';
    $desc  = 'Surat ini telah dibatalkan oleh pihak pondok dan tidak dapat digunakan.';
} elseif ($item['status'] === 'kadaluarsa') {
    $ok = false; $cls = 'secondary'; $icon = 'bi-hourglass-bottom';
    $title = 'Kadaluarsa';
    $desc  = 'Periode izin pada surat ini telah berakhir.';
} else {
    $today = date('Y-m-d');
    if ($today < $item['tanggal_mulai']) {
        $ok = false; $cls = 'info'; $icon = 'bi-hourglass-split';
        $title = 'Belum Berlaku';
        $desc  = 'Periode izin surat ini belum dimulai.';
    } elseif ($today > $item['tanggal_selesai']) {
        $ok = false; $cls = 'secondary'; $icon = 'bi-hourglass-bottom';
        $title = 'Kadaluarsa';
        $desc  = 'Periode izin surat ini telah berakhir.';
    } else {
        $ok = true; $cls = 'success'; $icon = 'bi-patch-check-fill';
        $title = 'VALID — Surat Berlaku';
        $desc  = 'Surat ini terdaftar resmi dan berada dalam periode berlaku.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Surat Izin | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(160deg, #0d2b1e, #0f766e); min-height: 100vh;
               display: flex; align-items: center; justify-content: center; padding: 16px; }
        .card-verify { max-width: 480px; width: 100%; border: 0; border-radius: 20px; overflow: hidden;
                       box-shadow: 0 25px 60px -12px rgba(0,0,0,.45); }
        .result-icon { font-size: 64px; }
    </style>
</head>
<body>
    <div class="card card-verify">
        <div class="card-body p-4 p-md-5 text-center">
            <i class="bi bi-heart-pulse text-brand fs-2"></i>
            <div class="fw-bold mt-1 mb-4"><?= e(APP_NAME) ?><br>
                <small class="text-muted fw-normal">Verifikasi Keaslian Surat Izin Sakit</small>
            </div>

            <i class="bi <?= $icon ?> result-icon text-<?= $cls ?>"></i>
            <h1 class="h4 fw-bold mt-2 mb-1"><?= $title ?></h1>
            <p class="text-muted small mb-4"><?= $desc ?></p>

            <?php if ($item): ?>
            <div class="border rounded-3 p-3 text-start small">
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span class="text-muted">Nomor Surat</span><span class="fw-semibold"><?= e($item['nomor_surat']) ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span class="text-muted">Nama</span><span><?= e($item['santri_nama']) ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span class="text-muted">NIS</span><span><?= e($item['nis']) ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span class="text-muted">Asrama</span><span><?= e($item['asrama_nama'] ?? '-') ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span class="text-muted">Periode Izin</span>
                    <span><?= e(date('d-m-Y', strtotime($item['tanggal_mulai']))) ?> s.d. <?= e(date('d-m-Y', strtotime($item['tanggal_selesai']))) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Diterbitkan</span>
                    <span><?= e($item['penerbit_nama'] ?? '-') ?> · <?= e(date('d-m-Y', strtotime($item['created_at']))) ?></span>
                </div>
            </div>

            <div class="alert alert-light border small text-muted mt-3 mb-0">
                <i class="bi bi-shield-lock me-1"></i>
                Untuk menjaga privasi, <strong>detail kondisi kesehatan tidak ditampilkan</strong> pada halaman publik.
                Keperluan: surat keterangan izin sakit untuk administrasi pondok.
            </div>
            <?php endif; ?>

            <div class="text-center text-white-50 small mt-4">
                Diverifikasi: <?= e(date('d-m-Y H:i:s')) ?>
            </div>
        </div>
    </div>
</body>
</html>