<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Halaman Tidak Ditemukan | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center p-4">
        <div class="display-1 fw-bold text-secondary">404</div>
        <p class="fs-5 mb-4">Halaman yang Anda cari tidak ditemukan.</p>
        <a href="<?= base_url('/dashboard') ?>" class="btn btn-brand">
            <i class="bi bi-house me-1"></i> Kembali ke Beranda
        </a>
    </div>
</body>
</html>