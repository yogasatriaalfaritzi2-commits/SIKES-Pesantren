<?php
use App\Core\Session;

 $pageQs = fn (int $p): string => base_url('/skrining/riwayat?' . http_build_query(array_filter([
    'santri_id' => $filters['santri_id'] ?: null,
    'q'         => $filters['q'] !== '' ? $filters['q'] : null,
    'page'      => $p,
])));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Riwayat Pemeriksaan Skrining</h1>
        <p class="text-muted mb-0">Jejak pembuatan &amp; perubahan data skrining (<?= (int) $data['total'] ?> catatan).</p>
    </div>
    <a href="<?= base_url('/skrining') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-list-ul me-1"></i> Daftar Skrining
    </a>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/skrining/riwayat') ?>">
            <div class="col-md-5">
                <label class="form-label small mb-1">Santri</label>
                <select name="santri_id" class="form-select form-select-sm">
                    <option value="">Semua santri</option>
                    <?php foreach ($santriOptions as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= $filters['santri_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['nis']) ?> — <?= e($s['nama']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="nama / NIS / isi catatan" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Waktu</th><th>Santri</th><th>Aksi</th><th>Ringkasan Perubahan</th><th>Oleh</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat pemeriksaan.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row):
                $snap = json_decode($row['data_sesudah'] ?? '', true);
                $ringkasan = $snap['ringkasan'] ?? '-';
            ?>
                <tr>
                    <td class="small text-nowrap"><?= e(date('d-m-Y H:i:s', strtotime($row['created_at']))) ?></td>
                    <td>
                        <div class="fw-semibold small"><?= e($row['santri_nama']) ?></div>
                        <small class="text-muted">NIS <?= e($row['nis']) ?></small>
                    </td>
                    <td>
                        <span class="badge bg-<?= $row['aksi'] === 'buat' ? 'primary' : 'warning' ?>-subtle
                                      text-<?= $row['aksi'] === 'buat' ? 'primary' : 'warning' ?>-emphasis">
                            <?= $row['aksi'] === 'buat' ? 'Dibuat' : 'Diubah' ?>
                        </span>
                    </td>
                    <td class="small"><?= e($ringkasan) ?></td>
                    <td class="small text-muted"><?= e($row['user_nama'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($data['pages'] > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Halaman <?= $data['page'] ?> dari <?= $data['pages'] ?></small>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $pageQs(max(1, $data['page'] - 1)) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $pageQs(min($data['pages'], $data['page'] + 1)) ?>">&raquo;</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</div>