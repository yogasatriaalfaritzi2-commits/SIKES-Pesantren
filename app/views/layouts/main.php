<?php
/**
 * Layout utama aplikasi (halaman setelah login).
 */
?>
<!DOCTYPE html>
<html lang="id">
<?php require __DIR__ . '/header.php'; ?>
<body class="bg-light">

    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php require __DIR__ . '/navbar.php'; ?>

        <main class="container-fluid p-3 p-lg-4 flex-grow-1">
            <?php require __DIR__ . '/../partials/breadcrumb.php'; ?>
            <?php require __DIR__ . '/../partials/flash.php'; ?>
            <?php require $contentFile; ?>
        </main>

        <?php require __DIR__ . '/footer.php'; ?>
    </div>

    <!-- AI Health Assistant (Floating WA-style widget & modal) -->
    <?php require __DIR__ . '/../partials/ai_widget.php'; ?>

    <!-- Modal konfirmasi hapus (dipakai semua modul lewat form.form-delete) -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Yakin ingin menghapus <strong id="deleteEntityName">data ini</strong>?
                    Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="deleteConfirmBtn">
                        <i class="bi bi-trash me-1"></i>Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('js/app.js') ?>"></script>
</body>
</html>