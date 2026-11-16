<?php
 $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Laporan Pemakaian Obat</h1>
        <p class="text-muted mb-0">Periode <?= e(date('d-m-Y', strtotime($dateFrom))) ?> s.d. <?= e(date('d-m-Y', strtotime($dateTo))) ?> — untuk Bendahara &amp; Poskestren.</p>
    </div>
    <a href="<?= base_url('/obat') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Master Obat</a>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/obat/laporan') ?>">
            <div class="col-6 col-md-4">
                <label class="form-label small mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-md-4 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Nama Obat</th><th>Satuan</th><th>Jumlah Digunakan</th><th>Harga</th><th class="text-end">Total Biaya</th>
            </tr></thead>
            <tbody>
            <?php if (!$laporan['rows']): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">
                    Tidak ada pemakaian obat pada periode ini.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($laporan['rows'] as $row): ?>
                <tr>
                    <td class="fw-semibold"><?= e($row['nama']) ?></td>
                    <td class="small"><?= e($row['satuan']) ?></td>
                    <td class="fw-semibold"><?= (int) $row['jumlah'] ?> <?= e($row['satuan']) ?></td>
                    <td class="small text-nowrap"><?= $rupiah($row['harga']) ?></td>
                    <td class="text-end fw-semibold text-nowrap"><?= $rupiah($row['total_biaya']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($laporan['rows']): ?>
            <tr class="table-light">
                <td colspan="4" class="fw-bold">TOTAL BIAYA PERIODE</td>
                <td class="text-end fw-bold fs-5 text-brand text-nowrap"><?= $rupiah($laporan['grand_total']) ?></td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white small text-muted">
        <i class="bi bi-info-circle me-1"></i>
        Harga pada laporan adalah <strong>harga snapshot saat transaksi</strong> — tidak berubah meski harga master diperbarui.
    </div>
</div>