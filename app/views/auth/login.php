<?php
use App\Core\Session;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Login') ?> | <?= APP_NAME ?></title>
    <!-- Favicon Website -->
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="alternate icon" href="<?= base_url('favicon.ico') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
</head>
<body class="login-page d-flex align-items-center justify-content-center">

    <div class="card login-card shadow-lg border-0 w-100">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <img src="<?= base_url('favicon.svg') ?>" alt="SIKES Logo" width="68" height="68" class="rounded-4 shadow-sm mb-3">
                <h1 class="h4 fw-bold mb-1"><?= APP_NAME ?></h1>
                <p class="text-muted mb-0">Sistem Informasi Kesehatan Pondok Pesantren</p>
            </div>

            <?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

            <form method="POST" action="<?= base_url('/login') ?>">
                <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Masukkan username" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Masukkan password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-brand w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </form>
        </div>
        <div class="card-footer text-center text-muted small py-3 bg-white border-top-0">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tampilkan/sembunyikan password
        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon  = this.querySelector('i');
            const show  = input.type === 'password';
            input.type  = show ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !show);
            icon.classList.toggle('bi-eye-slash', show);
        });
    </script>
</body>
</html>