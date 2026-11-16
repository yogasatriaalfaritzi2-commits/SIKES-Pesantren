<?php
use App\Core\Session;

 $badgeMap = ['login' => 'success', 'logout' => 'secondary', 'create' => 'primary', 'update' => 'warning', 'delete' => 'danger'];
 $pageQs = fn (int $p): string => base_url('/activity?' . http_build_query([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'action' => $filters['action'] !== '' ? $filters['action'] : null,
    'date' => $filters['date'] !== '' ? $filters['date'] : null,
    'page' => $p,
]));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Log Aktivitas</h1>
        <p class="text-muted mb-0">Jejak aktivitas pengguna dalam sistem (<?= (int) $data['total'] ?> catatan).</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="deskripsi / nama / username" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Aksi</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach ($actions as $a): ?>
                    <option value="<?= $a ?>" <?= $filters['action'] === $a ? 'selected' : '' ?>><?= ucfirst($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Tanggal</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= e($filters['date']) ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Modul</th><th>Deskripsi</th><th>IP</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td class="small text-nowrap"><?= e(date('d-m-Y H:i:s', strtotime($row['created_at']))) ?></td>
                    <td>
                        <?php if ($row['user_nama']): ?>
                            <div class="fw-semibold small"><?= e($row['user_nama']) ?></div>
                            <small class="text-muted">@<?= e($row['username']) ?></small>
                        <?php else: ?>
                            <span class="text-muted small">Anonim</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $badgeMap[$row['action']] ?? 'secondary' ?>-subtle
                                      text-<?= $badgeMap[$row['action']] ?? 'secondary' ?>-emphasis">
                            <?= e($row['action']) ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= e($row['module']) ?></td>
                    <td class="small"><?= e($row['description'] ?? '-') ?></td>
                    <td class="small text-muted"><?= e($row['ip_address'] ?? '-') ?></td>
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