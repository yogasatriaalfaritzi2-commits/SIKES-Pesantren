<?php
 $pageQs = fn (int $p): string => base_url('/rekam-medis?' . http_build_query(['q' => $q !== '' ? $q : null, 'page' => $p]));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Rekam Medis Santri</h1>
        <p class="text-muted mb-0">Konsolidasi seluruh riwayat kesehatan per santri (<?= (int) $data['total'] ?> santri).</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-journal-medical me-2"></i>Daftar Santri</span>
        <form class="d-flex gap-2" method="GET" action="<?= base_url('/rekam-medis') ?>">
            <input type="text" name="q" class="form-control form-control-sm" style="width:230px"
                   placeholder="Cari nama / NIS" value="<?= e($q) ?>">
            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Santri</th><th>Penempatan</th><th>Kunjungan</th>
                <th>Kasus Aktif</th><th>Izin</th><th>Rujukan</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm"><?= e(strtoupper(mb_substr($row['nama'], 0, 1))) ?></div>
                            <div class="lh-sm">
                                <div class="fw-semibold"><?= e($row['nama']) ?></div>
                                <small class="text-muted">NIS <?= e($row['nis']) ?> · <?= e($row['jenis_kelamin']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td class="small">
                        <?= e($row['asrama_nama'] ?? '-') ?><?= $row['kamar_nama'] ? ' · Kam. ' . e($row['kamar_nama']) : '' ?>
                        <?php if ($row['status'] === 'nonaktif'): ?>
                            <div><span class="badge bg-secondary-subtle text-secondary-emphasis">Nonaktif</span></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="fw-semibold"><?= (int) $row['kunjungan'] ?></span>
                        <div class="small text-muted">
                            <?= $row['kunjungan_terakhir'] ? e(date('d-m-y', strtotime($row['kunjungan_terakhir']))) : '-' ?>
                        </div>
                    </td>
                    <td>
                        <?php if ((int) $row['kasus_aktif'] > 0): ?>
                            <span class="badge bg-danger text-white"><?= (int) $row['kasus_aktif'] ?> aktif</span>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $row['izin'] ?></td>
                    <td><?= (int) $row['rujukan'] ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-brand" href="<?= base_url('/rekam-medis/' . $row['id']) ?>">
                            <i class="bi bi-journal-text me-1"></i> Rekam Medis
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($data['pages'] > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Halaman <?= $data['page'] ?> dari <?= $data['pages'] ?></small>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $pageQs(max(1, $data['page'] - 1)) ?>">&laquo;</a></li>
            <li class="page-item <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>"><a class="page-link" href="<?= $pageQs(min($data['pages'], $data['page'] + 1)) ?>">&raquo;</a></li>
        </ul>
    </div>
    <?php endif; ?>
</div>