<?php use App\Core\Session; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center p-4">
        <div class="display-1 fw-bold text-secondary"><i class="bi bi-shield-lock"></i></div>
        <p class="fs-4 fw-bold mb-1">403 — Akses Ditolak</p>
        <p class="text-muted mb-4">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
        <a href="<?= base_url(Session::isLoggedIn() ? '/dashboard' : '/login') ?>" class="btn btn-brand">
            <i class="bi bi-house me-1"></i> Kembali
        </a>
    </div>
</body>
</html>