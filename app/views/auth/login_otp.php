<?php
use App\Core\Session;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Verifikasi OTP Login') ?> | <?= APP_NAME ?></title>
    <!-- Favicon Website -->
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="alternate icon" href="<?= base_url('favicon.ico') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <style>
        .otp-input {
            letter-spacing: 8px;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
        }
    </style>
</head>
<body class="login-page d-flex align-items-center justify-content-center">

    <div class="card login-card shadow-lg border-0 w-100" style="max-width: 440px;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <img src="<?= base_url('favicon.svg') ?>" alt="SIKES Logo" width="60" height="60" class="rounded-4 shadow-sm mb-3">
                <h1 class="h5 fw-bold mb-1">Verifikasi OTP Login</h1>
                <p class="text-muted small mb-0">Keamanan Akses Dua Langkah (2FA Gmail)</p>
            </div>

            <?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

            <!-- STEP 1: Jika belum kirim OTP atau ingin ubah email tujuan -->
            <form method="POST" action="<?= base_url('/login/otp-send') ?>" class="mb-3">
                <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

                <label class="form-label small fw-semibold text-secondary">Kirim Kode OTP ke Alamat Gmail Anda:</label>
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" name="email" 
                           value="<?= e($sentEmail ?? ($user['email'] ?? '')) ?>" 
                           placeholder="namaanda@gmail.com" required>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-send-fill me-1"></i> <?= !empty($otpSent) ? 'Kirim Ulang' : 'Kirim OTP' ?>
                    </button>
                </div>
                <div class="text-muted small" style="font-size:.72rem;">
                    * Masukkan Gmail aktif Anda. Kode 6 digit OTP akan langsung dikirimkan ke email ini.
                </div>
            </form>

            <hr class="my-3 text-muted">

            <!-- STEP 2: Input Verifikasi OTP 6 Digit -->
            <form method="POST" action="<?= base_url('/login/otp-verify') ?>">
                <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

                <div class="mb-3">
                    <label for="otp_code" class="form-label small fw-semibold text-dark">Masukkan 6 Digit Kode OTP:</label>
                    <input type="text" maxlength="6" class="form-control otp-input" id="otp_code" name="otp_code"
                           placeholder="••••••" required autofocus autocomplete="one-time-code">
                    <div class="text-muted small mt-1 text-center" style="font-size:.72rem;">
                        Kode berlaku 5 menit. Cek tab Kotak Masuk atau folder Spam pada Gmail Anda.
                    </div>
                </div>

                <button type="submit" class="btn btn-brand w-100 py-2">
                    <i class="bi bi-shield-check me-1"></i> Verifikasi & Masuk SIKES
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="<?= base_url('/login/cancel') ?>" class="text-muted small text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Batal & Gunakan Akun Lain
                </a>
            </div>
        </div>
        <div class="card-footer text-center text-muted small py-3 bg-white border-top-0">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
