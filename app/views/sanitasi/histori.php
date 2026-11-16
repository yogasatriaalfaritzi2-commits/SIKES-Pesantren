<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\InspeksiKamar;

 $qs = function (array $over = []) use ($filters): string {
    $merged = array_filter(
        array_merge($filters, ['page' => 1], $over),
        fn ($v) => $v !== '' && $v !== null && $v !== 0
    );
    return base_url('/sanitasi/inspeksi?' . http_build_query($merged));
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Histori Inspeksi</h1>
        <p class="text-muted mb-0">Semua hasil inspeksi kebersihan kamar (<?= (int) $data['total'] ?> catatan).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('/sanitasi') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard Kondisi
        </a>
        <?php if (Auth::hasPermission('sanitasi.create')): ?>
        <a href="<?= base_url('/sanitasi/inspeksi/create') ?>" class="btn btn-brand">
            <i class="bi bi-plus-circle me-1"></i> Input Inspeksi
        </a>
        <?php endif; ?>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/sanitasi/inspeksi') ?>">
            <div class="col-md-3">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="kamar / blok / catatan" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Asrama</label>
                <select name="asrama" id="hAsrama" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach ($asramaOptions as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= $filters['asrama'] === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Blok</label>
                <select name="blok" id="hBlok" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach ($blokList as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= $filters['blok'] === (int) $b['id'] ? 'selected' : '' ?>><?= e($b['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (InspeksiKamar::STATUS_LABEL as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-3 col-md-1">
                <label class="form-label small mb-1">Dari</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-3 col-md-1">
                <label class="form-label small mb-1">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Tanggal</th><th>Kamar</th><th>Lokasi</th><th style="min-width:140px">Skor</th>
                <th>Status</th><th>Petugas</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada histori inspeksi.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td class="small text-nowrap"><?= e(date('d-m-Y', strtotime($row['tanggal_inspeksi']))) ?></td>
                    <td class="fw-semibold">Kamar <?= e($row['kamar_nama']) ?> <small class="text-muted fw-normal">(<?= (int) $row['terisi'] ?>/<?= (int) $row['kapasitas'] ?>)</small></td>
                    <td class="small"><?= e($row['blok_nama']) ?> · <?= e($row['asrama_nama']) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:6px">
                                <div class="progress-bar bg-<?= InspeksiKamar::STATUS_COLOR[$row['status']] ?>"
                                     style="width:<?= (float) $row['persentase'] ?>%"></div>
                            </div>
                            <small class="text-nowrap"><?= (int) $row['skor'] ?>/16</small>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-<?= InspeksiKamar::STATUS_COLOR[$row['status']] ?> text-white">
                            <?= InspeksiKamar::STATUS_LABEL[$row['status']] ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= e($row['petugas_nama'] ?? '-') ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/sanitasi/inspeksi/' . $row['id']) ?>"><i class="bi bi-eye"></i></a>
                        <?php if (Auth::hasPermission('sanitasi.edit')): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/sanitasi/inspeksi/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline form-delete" method="POST"
                              action="<?= base_url('/sanitasi/inspeksi/' . $row['id'] . '/delete') ?>"
                              data-name="inspeksi Kamar <?= e($row['kamar_nama']) ?> tgl <?= e(date('d-m-Y', strtotime($row['tanggal_inspeksi']))) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
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

<script>
document.getElementById('hAsrama')?.addEventListener('change', function () {
    fetch('<?= base_url('/master/blok-options') ?>?asrama_id=' + this.value)
        .then(r => r.json())
        .then(res => {
            const sel = document.getElementById('hBlok');
            sel.innerHTML = '<option value="">Semua</option>' +
                res.data.map(b => `<option value="${b.id}">${b.nama}</option>`).join('');
        });
});
</script>