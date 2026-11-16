<?php use App\Core\Auth;
use App\Models\PiketJadwal; ?>

<?php
// ===== REMINDER PIKET (baru v8) =====
 $r          = $reminders ?? null;
 $rTerlewat  = $r['terlewat'] ?? [];
 $rHariIni   = $r['hari_ini'] ?? [];
 $rMendatang = $r['mendatang'] ?? [];
?>
<?php if ($r && ($rTerlewat || $rHariIni || $rMendatang)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-bell me-2 text-warning"></i>Reminder Piket Kebersihan
        <a href="<?= base_url('/piket') ?>" class="small ms-2">lihat semua &rarr;</a>
    </div>
    <div class="card-body py-3">
        <?php if ($rTerlewat): ?>
        <div class="alert alert-danger py-2 mb-2 small">
            <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Terlewat (belum selesai, tanggal sudah lewat):</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($rTerlewat as $j): ?>
                <li>
                    <a href="<?= base_url('/piket/' . $j['id']) ?>"><?= e($j['nama_kegiatan']) ?></a>
                    — <?= e(date('d-m-Y', strtotime($j['tanggal']))) ?>, <?= e(substr($j['waktu_mulai'], 0, 5)) ?>
                    · <?= e($j['lokasi']) ?> · PJ: <?= e($j['penanggung_jawab']) ?>
                    <span class="badge bg-<?= PiketJadwal::STATUS_COLOR[$j['status']] ?? 'secondary' ?> text-white ms-1">
                        <?= PiketJadwal::STATUS_LABEL[$j['status']] ?? '' ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($rHariIni): ?>
        <div class="alert alert-warning py-2 mb-2 small mb-0">
            <strong><i class="bi bi-sun-fill me-1"></i>Hari ini:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($rHariIni as $j): ?>
                <li>
                    <a href="<?= base_url('/piket/' . $j['id']) ?>"><?= e($j['nama_kegiatan']) ?></a>
                    — <?= e(substr($j['waktu_mulai'], 0, 5)) ?><?= $j['waktu_selesai'] ? '–' . e(substr($j['waktu_selesai'], 0, 5)) : '' ?>
                    · <?= e($j['lokasi']) ?> · PJ: <?= e($j['penanggung_jawab']) ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($rMendatang): ?>
        <div class="small text-muted">
            <strong><i class="bi bi-calendar-check me-1"></i>Agenda 7 hari ke depan (<?= count($rMendatang) ?>):</strong>
            <?php foreach (array_slice($rMendatang, 0, 3) as $j): ?>
                <span class="badge bg-info-subtle text-info-emphasis me-1 mt-1">
                    <?= e(date('d-m', strtotime($j['tanggal']))) ?> · <?= e(mb_strimwidth($j['nama_kegiatan'], 0, 30, '…')) ?>
                </span>
            <?php endforeach; ?>
            <?php if (count($rMendatang) > 3): ?><span class="small">+<?= count($rMendatang) - 3 ?> lainnya</span><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Assalamu'alaikum, <?= e($user['nama']) ?></h1>
        <p class="text-muted mb-0"><?= e($subtitle) ?></p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-brand-soft text-brand fs-6 px-3 py-2">
            <i class="bi bi-shield-check me-1"></i><?= e($user['role_name'] ?? '-') ?>
        </span>
        <span class="badge bg-success-subtle text-success-emphasis fs-6 px-3 py-2">
            <i class="bi bi-calendar-day me-1"></i><?= e(date('d-m-Y')) ?>
        </span>
    </div>
</div>

<?php if ($stats): ?>
<div class="row g-3 mb-4">
    <?php foreach ($stats as $s): ?>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-<?= $s['color'] ?>"><i class="bi <?= $s['icon'] ?>"></i></div>
                <div>
                    <div class="fw-bold fs-4 lh-1"><?= e($s['value']) ?></div>
                    <small class="text-muted"><?= $s['label'] ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($quick): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-lightning-charge me-2"></i>Aksi Cepat</div>
    <div class="card-body d-flex flex-wrap gap-2">
        <?php foreach ($quick as $l): ?>
            <?php if (!empty($l['soon'])): ?>
                <span class="btn btn-outline-secondary btn-sm disabled">
                    <i class="bi <?= $l['icon'] ?> me-1"></i><?= e($l['label']) ?>
                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">Segera</span>
                </span>
            <?php else: ?>
                <a href="<?= base_url($l['url']) ?>" class="btn btn-outline-brand btn-sm">
                    <i class="bi <?= $l['icon'] ?> me-1"></i><?= e($l['label']) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person me-2"></i>Akun Anda</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Nama</span><span><?= e($user['nama']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Username</span><span><?= e($user['username']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Role</span><span><?= e($user['role_name'] ?? '-') ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>