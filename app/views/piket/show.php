<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\PiketJadwal;
use App\Models\PiketRiwayat;

/* ============================================================
   NORMALISASI DEFENSIF — mencegah warning "Undefined array key"
   walau data tidak sempat dikirim; dengan controller + model di
   atas, $item akan berisi baris database yang utuh.
   ============================================================ */
 $item   = (isset($item) && is_array($item)) ? $item : [];
 $id     = (int) ($item['id'] ?? 0);
 $status = $item['status'] ?? 'terjadwal';
 $warna  = PiketJadwal::STATUS_COLOR[$status] ?? 'secondary';

 $tanggal      = $item['tanggal'] ?? '';
 $waktuMulai   = substr((string) ($item['waktu_mulai'] ?? ''), 0, 5);
 $waktuSelesai = $item['waktu_selesai'] ?? null;
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <a href="<?= base_url('/piket') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0"><?= e($item['nama_kegiatan'] ?? 'Detail Jadwal Piket') ?></h1>
        <p class="text-muted small mb-0">
            <?= PiketJadwal::JENIS[$item['jenis'] ?? ''] ?? '-' ?> ·
            <?= $tanggal !== '' ? e(date('d-m-Y', strtotime($tanggal))) : '-' ?><?= $waktuMulai !== '' ? ', ' . e($waktuMulai) : '' ?><?= $waktuSelesai ? '–' . e(substr((string) $waktuSelesai, 0, 5)) : '' ?>
        </p>
    </div>
    <span class="badge bg-<?= $warna ?> text-white fs-6 px-3 py-2"><?= PiketJadwal::STATUS_LABEL[$status] ?></span>

    <?php if ($id > 0 && Auth::hasPermission('piket.manage')): ?>
        <?php if ($status === 'terjadwal'): ?>
        <form method="POST" action="<?= base_url('/piket/' . $id . '/mulai') ?>"
              onsubmit="return confirm('Mulai kegiatan ini?')">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
            <button class="btn btn-brand btn-sm"><i class="bi bi-play-fill me-1"></i> Mulai</button>
        </form>
        <?php elseif ($status === 'berlangsung'): ?>
        <form method="POST" action="<?= base_url('/piket/' . $id . '/selesai') ?>"
              onsubmit="return confirm('Selesaikan kegiatan ini?')">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
            <button class="btn btn-outline-success btn-sm"><i class="bi bi-check2-circle me-1"></i> Selesaikan</button>
        </form>
        <?php endif; ?>
    <a href="<?= base_url('/piket/' . $id . '/edit') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-pencil me-1"></i> Edit
    </a>
    <form class="d-inline form-delete" method="POST" action="<?= base_url('/piket/' . $id . '/delete') ?>"
          data-name="jadwal <?= e($item['nama_kegiatan'] ?? '') ?>">
        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
    </form>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if ($id === 0): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Data jadwal tidak tersedia. <a href="<?= base_url('/piket') ?>">Kembali ke daftar jadwal</a>.
</div>
<?php else: ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-info-circle me-2"></i>Informasi Kegiatan</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Jenis</span>
                    <span><?= PiketJadwal::JENIS[$item['jenis'] ?? ''] ?? '-' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Lokasi</span><span><?= e($item['lokasi'] ?? '-') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Tanggal</span>
                    <span><?= $tanggal !== '' ? e(date('l, d-m-Y', strtotime($tanggal))) : '-' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Waktu</span>
                    <span><?= $waktuMulai !== '' ? e($waktuMulai) : '-' ?><?= $waktuSelesai ? ' – ' . e(substr((string) $waktuSelesai, 0, 5)) : ' (sampai selesai)' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Penanggung Jawab</span>
                    <span class="fw-semibold"><?= e($item['penanggung_jawab'] ?? '-') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Dibuat oleh</span>
                    <span><?= e($item['dibuat_nama'] ?? '-') ?></span>
                </li>
            </ul>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-journal-text me-2"></i>Catatan</div>
            <div class="card-body">
                <?php if (!empty($item['catatan'])): ?>
                    <p class="mb-0" style="white-space: pre-wrap"><?= e($item['catatan']) ?></p>
                <?php else: ?>
                    <span class="text-muted small">Tidak ada catatan.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat Jadwal</div>
            <ul class="list-group list-group-flush">
                <?php if (!$riwayatList): ?>
                    <li class="list-group-item text-muted small">Belum ada riwayat.</li>
                <?php endif; ?>
                <?php foreach ($riwayatList as $r): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">
                            <?= PiketRiwayat::AKSI_LABEL[$r['aksi']] ?? e($r['aksi']) ?>
                        </span>
                        <small class="text-muted"><?= e(date('d-m-Y H:i', strtotime($r['created_at']))) ?></small>
                    </div>
                    <div class="small mt-1"><?= e($r['keterangan'] ?? '-') ?></div>
                    <?php if ($r['user_nama']): ?><div class="small text-muted">oleh <?= e($r['user_nama']) ?></div><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>