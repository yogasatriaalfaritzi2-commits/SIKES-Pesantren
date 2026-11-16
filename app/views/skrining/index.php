<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\SkriningKesehatan;

 $qs = function (array $over = []) use ($filters): string {
    $merged = array_filter(
        array_merge($filters, ['page' => 1], $over),
        fn ($v) => $v !== '' && $v !== null && $v !== 0
    );
    return base_url('/skrining?' . http_build_query($merged));
};
 $imtColor = function (?string $kat): string {
    return match ($kat) {
        'Normal' => 'success',
        'Berat Badan Kurang', 'Berat Badan Berlebih' => 'warning',
        'Obesitas' => 'danger',
        default => 'secondary',
    };
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Skrining Kesehatan Awal</h1>
        <p class="text-muted mb-0">Pemetaan skrining seluruh santri (<?= (int) $data['total'] ?> data).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('/skrining/riwayat') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i> Riwayat Pemeriksaan
        </a>
        <?php if (Auth::hasPermission('pemeriksaan.create')): ?>
        <a href="<?= base_url('/skrining/create') ?>" class="btn btn-brand">
            <i class="bi bi-plus-circle me-1"></i> Input Skrining
        </a>
        <?php endif; ?>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/skrining') ?>">
            <div class="col-md-3">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="nama / NIS" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Jenis Kelamin</label>
                <select name="jk" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="L" <?= $filters['jk'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="P" <?= $filters['jk'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Asrama</label>
                <select name="asrama" class="form-select form-select-sm">
                    <option value="">Semua asrama</option>
                    <?php foreach ($asramaOptions as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= $filters['asrama'] === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= e($a['nama']) ?> (<?= e($a['jenis']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Status Skrining</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="sudah" <?= $filters['status'] === 'sudah' ? 'selected' : '' ?>>Sudah diskrining</option>
                    <option value="belum" <?= $filters['status'] === 'belum' ? 'selected' : '' ?>>Belum diskrining</option>
                </select>
            </div>
            <div class="col-6 col-md-1 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Santri</th><th>L/P</th><th>Penempatan</th><th>Skrining</th>
                <th>IMT</th><th>Gol. Darah</th><th>Peringatan</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row):
                $w = SkriningKesehatan::warningsFrom($row); ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm"><?= e(strtoupper(mb_substr($row['nama'], 0, 1))) ?></div>
                            <div class="lh-sm">
                                <div class="fw-semibold">
                                    <a class="text-decoration-none" href="<?= base_url('/santri/' . $row['santri_id']) ?>"><?= e($row['nama']) ?></a>
                                </div>
                                <small class="text-muted">NIS <?= e($row['nis']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-<?= $row['jenis_kelamin'] === 'L' ? 'info' : 'danger' ?>-subtle
                                      text-<?= $row['jenis_kelamin'] === 'L' ? 'info' : 'danger' ?>-emphasis">
                            <?= e($row['jenis_kelamin']) ?>
                        </span>
                    </td>
                    <td class="small">
                        <?php if ($row['asrama_nama']): ?>
                            <div><?= e($row['asrama_nama']) ?></div>
                            <div class="text-muted"><?= e($row['blok_nama']) ?> · Kam. <?= e($row['kamar_nama']) ?></div>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['skrining_id']): ?>
                            <span class="badge bg-success-subtle text-success-emphasis">Sudah</span>
                            <div class="small text-muted"><?= e(date('d-m-Y', strtotime($row['tanggal_skrining']))) ?></div>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis">Belum</span>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if ($row['imt'] !== null): ?>
                            <span class="badge bg-<?= $imtColor($row['kategori_imt']) ?>-subtle
                                          text-<?= $imtColor($row['kategori_imt']) ?>-emphasis">
                                <?= e((string) $row['imt']) ?>
                            </span>
                            <div class="text-muted"><?= e($row['kategori_imt'] ?? '') ?></div>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if ($row['golongan_darah'] !== '-'): ?>
                            <strong><?= e($row['golongan_darah']) ?></strong><?= $row['rhesus'] !== '-' ? e($row['rhesus'] === 'positif' ? '+' : '-') : '' ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($w): ?>
                            <?php foreach ($w as $i => $warn): ?>
                                <span class="badge bg-danger-subtle text-danger-emphasis mb-1"><?= e($warn) ?></span><?= $i < count($w) - 1 ? ' ' : '' ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($row['skrining_id']): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/skrining/' . $row['skrining_id']) ?>" title="Profil Kesehatan">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (Auth::hasPermission('pemeriksaan.edit')): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/skrining/' . $row['skrining_id'] . '/edit') ?>" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form class="d-inline form-delete" method="POST"
                                  action="<?= base_url('/skrining/' . $row['skrining_id'] . '/delete') ?>"
                                  data-name="skrining atas nama <?= e($row['nama']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        <?php elseif (Auth::hasPermission('pemeriksaan.create')): ?>
                            <a class="btn btn-sm btn-brand" href="<?= base_url('/skrining/create?santri_id=' . $row['santri_id']) ?>">
                                <i class="bi bi-plus-circle me-1"></i>Skrining
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
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
            <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $qs(['page' => max(1, $data['page'] - 1)]) ?>">&laquo;</a>
            </li>
            <li class="page-item <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $qs(['page' => min($data['pages'], $data['page'] + 1)]) ?>">&raquo;</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</div>