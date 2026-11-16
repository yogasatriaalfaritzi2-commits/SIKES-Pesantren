<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\SuratIzin;
use App\Models\SuratIzinRiwayat;
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <a href="<?= base_url('/surat') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0"><?= e($item['nomor_surat']) ?></h1>
        <p class="text-muted small mb-0">E-Surat Izin Sakit — <?= e($item['santri_nama']) ?> (NIS <?= e($item['nis']) ?>)</p>
    </div>
    <span class="badge bg-<?= SuratIzin::STATUS_COLOR[$item['status']] ?> text-white fs-6 px-3 py-2">
        <?= SuratIzin::STATUS_LABEL[$item['status']] ?>
    </span>
    <a href="<?= base_url('/surat/' . $item['id'] . '/cetak') ?>" class="btn btn-brand btn-sm">
        <i class="bi bi-printer me-1"></i> Cetak
    </a>
    <?php if ($item['status'] === 'aktif' && Auth::hasPermission('surat.edit')): ?>
    <form method="POST" action="<?= base_url('/surat/' . $item['id'] . '/batal') ?>"
          onsubmit="var a = prompt('Alasan pembatalan (min. 5 karakter):'); if (!a || a.trim().length < 5) return false; this.querySelector('[name=alasan]').value = a.trim();">
        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
        <input type="hidden" name="alasan" value="">
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i> Batalkan</button>
    </form>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if ($item['status'] === 'dibatalkan'): ?>
<div class="alert alert-danger">
    <i class="bi bi-x-octagon-fill me-2"></i><strong>Surat ini TIDAK VALID.</strong>
    Telah dibatalkan oleh penerbit<?= $item['alasan_pembatalan'] ? ': "' . e($item['alasan_pembatalan']) . '"' : '.' ?>
    Verifikasi QR akan selalu menampilkan status tidak valid.
</div>
<?php elseif ($item['status'] === 'kadaluarsa'): ?>
<div class="alert alert-secondary">
    <i class="bi bi-hourglass-bottom me-2"></i>Surat ini sudah <strong>KADALUARSA</strong> (periode izin telah berakhir).
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-file-earmark-text me-2"></i>Isi Surat</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Nomor</span><span class="fw-semibold"><?= e($item['nomor_surat']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Santri</span><span><?= e($item['santri_nama']) ?> (<?= e($item['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan') ?>)</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">NIS / NISN</span><span><?= e($item['nis']) ?><?= $item['nisn'] ? ' / ' . e($item['nisn']) : '' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Penempatan</span><span><?= e($item['asrama_nama'] ?? '-') ?><?= $item['kamar_nama'] ? ' · Kam. ' . e($item['kamar_nama']) : '' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Periode Izin</span>
                    <span><?= e(date('d-m-Y', strtotime($item['tanggal_mulai']))) ?> s.d. <?= e(date('d-m-Y', strtotime($item['tanggal_selesai']))) ?></span>
                </li>
                <li class="list-group-item">
                    <div class="text-muted small mb-1">Keterangan Medis (Administratif)</div>
                    <div style="white-space: pre-wrap"><?= e($item['keterangan_medis']) ?></div>
                </li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Rujukan Pemeriksaan</span>
                    <span><?= $item['pemeriksaan_id'] ? '#' . (int) $item['pemeriksaan_id'] . ' — ' . e((string) $item['pemeriksaan_diagnosis']) : '-' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Diterbitkan oleh</span><span><?= e($item['penerbit_nama'] ?? '-') ?></span></li>
            </ul>
        </div>

        <?php if ($item['status'] === 'dibatalkan' && $item['alasan_pembatalan']): ?>
        <div class="card border-0 shadow-sm mb-3 border-danger">
            <div class="card-header bg-white fw-semibold text-danger"><i class="bi bi-x-octagon me-2"></i>Alasan Pembatalan</div>
            <div class="card-body"><?= e($item['alasan_pembatalan']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-qr-code me-2"></i>QR Verifikasi</div>
            <div class="card-body text-center">
                <div id="qrcode" class="d-inline-block p-2 bg-white border rounded-3"></div>
                <div class="small text-muted mt-2 text-break" style="font-family:monospace"><?= e(substr($item['token'], 0, 32)) ?>…</div>
                <div class="small text-muted">Token 64 karakter acak — tidak dapat ditebak.</div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat Surat</div>
            <ul class="list-group list-group-flush">
                <?php if (!$riwayatList): ?>
                    <li class="list-group-item text-muted small">Belum ada riwayat.</li>
                <?php endif; ?>
                <?php foreach ($riwayatList as $r): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?= $r['aksi'] === 'terbit' ? 'primary' : ($r['aksi'] === 'batal' ? 'danger' : 'secondary') ?>-subtle
                                      text-<?= $r['aksi'] === 'terbit' ? 'primary' : ($r['aksi'] === 'batal' ? 'danger' : 'secondary') ?>-emphasis">
                            <?= SuratIzinRiwayat::AKSI_LABEL[$r['aksi']] ?? e($r['aksi']) ?>
                        </span>
                        <small class="text-muted"><?= e(date('d-m-Y H:i', strtotime($r['created_at']))) ?></small>
                    </div>
                    <div class="small mt-1"><?= e($r['keterangan'] ?? '-') ?></div>
                    <div class="small text-muted"><?= $r['user_nama'] ? 'oleh ' . e($r['user_nama']) : 'verifikasi publik' ?></div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qrcode'), {
    text: '<?= e($verifyUrl) ?>',
    width: 160, height: 160,
    correctLevel: QRCode.CorrectLevel.M
});
</script>