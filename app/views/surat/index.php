<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\SuratIzin;

 $qs = function (array $over = []) use ($filters): string {
    $merged = array_filter(array_merge($filters, ['page' => 1], $over), fn ($v) => $v !== '' && $v !== null);
    return base_url('/surat?' . http_build_query($merged));
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">E-Surat Izin Sakit</h1>
        <p class="text-muted mb-0">Surat keterangan izin sakit digital dengan QR verifikasi (<?= (int) $data['total'] ?> surat).</p>
    </div>
    <?php if (Auth::hasPermission('surat.create')): ?>
    <a href="<?= base_url('/surat/create') ?>" class="btn btn-brand">
        <i class="bi bi-file-earmark-plus me-1"></i> Terbitkan Surat
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/surat') ?>">
            <div class="col-md-4">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="nomor surat / nama / NIS" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (SuratIzin::STATUS_LABEL as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-3 col-md-2">
                <label class="form-label small mb-1">Dari</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-3 col-md-2">
                <label class="form-label small mb-1">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Nomor Surat</th><th>Santri</th><th>Periode Izin</th><th>Status</th><th>Diterbitkan</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada surat.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td class="small fw-semibold text-nowrap"><?= e($row['nomor_surat']) ?></td>
                    <td>
                        <div class="fw-semibold"><a class="text-decoration-none" href="<?= base_url('/santri/' . $row['santri_id']) ?>"><?= e($row['santri_nama']) ?></a></div>
                        <small class="text-muted">NIS <?= e($row['nis']) ?><?= $row['kamar_nama'] ? ' · Kam. ' . e($row['kamar_nama']) : '' ?></small>
                    </td>
                    <td class="small text-nowrap"><?= e(date('d-m-Y', strtotime($row['tanggal_mulai']))) ?> s.d. <?= e(date('d-m-Y', strtotime($row['tanggal_selesai']))) ?></td>
                    <td>
                        <span class="badge bg-<?= SuratIzin::STATUS_COLOR[$row['status']] ?> text-white">
                            <?= SuratIzin::STATUS_LABEL[$row['status']] ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= e($row['penerbit_nama'] ?? '-') ?><div><?= e(date('d-m-Y', strtotime($row['created_at']))) ?></div></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/surat/' . $row['id']) ?>" title="Detail"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-brand" href="<?= base_url('/surat/' . $row['id'] . '/cetak') ?>" title="Cetak"><i class="bi bi-printer"></i></a>
                        <?php if ($row['status'] === 'aktif' && Auth::hasPermission('surat.edit')): ?>
                        <form class="d-inline" method="POST" action="<?= base_url('/surat/' . $row['id'] . '/batal') ?>"
                              onsubmit="var a = prompt('Alasan pembatalan (min. 5 karakter):'); if (!a || a.trim().length < 5) return false; this.querySelector('[name=alasan]').value = a.trim();">
                            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                            <input type="hidden" name="alasan" value="">
                            <button class="btn btn-sm btn-outline-danger" title="Batalkan"><i class="bi bi-x-circle"></i></button>
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
            <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $qs(['page' => max(1, $data['page'] - 1)]) ?>">&laquo;</a></li>
            <li class="page-item <?= $data['page'] >= $data['pages'] ? 'disabled' : '' ?>"><a class="page-link" href="<?= $qs(['page' => min($data['pages'], $data['page'] + 1)]) ?>">&raquo;</a></li>
        </ul>
    </div>
    <?php endif; ?>
</div>