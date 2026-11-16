<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\RujukanRiwayat;
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <a href="<?= base_url('/rujukan') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0"><?= e($item['nomor_surat']) ?></h1>
        <p class="text-muted small mb-0">Surat Rujukan — <?= e($item['santri_nama']) ?> (NIS <?= e($item['nis']) ?>) · <?= e(date('d-m-Y', strtotime($item['tanggal_rujukan']))) ?></p>
    </div>
    <a href="<?= base_url('/rujukan/' . $item['id'] . '/cetak') ?>" class="btn btn-brand btn-sm">
        <i class="bi bi-printer me-1"></i> Cetak / PDF
    </a>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-envelope-paper me-2"></i>Isi Surat (Preview)</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Santri</span>
                    <span><?= e($item['santri_nama']) ?> · NIS <?= e($item['nis']) ?><?= $item['nisn'] ? ' / ' . e($item['nisn']) : '' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Asrama / Kamar</span>
                    <span><?= e($item['asrama_nama'] ?? '-') ?><?= $item['kamar_nama'] ? ' · Kam. ' . e($item['kamar_nama']) : '' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Tujuan Faskes</span>
                    <span class="fw-semibold"><?= e($item['tujuan_faskes']) ?></span></li>
                <li class="list-group-item"><div class="text-muted small mb-1">Keluhan</div>
                    <div style="white-space: pre-wrap"><?= e($item['keluhan']) ?></div></li>
                <li class="list-group-item"><div class="text-muted small mb-1">Hasil Pemeriksaan</div>
                    <div style="white-space: pre-wrap"><?= e($item['hasil_pemeriksaan']) ?></div></li>
                <li class="list-group-item"><div class="text-muted small mb-1">Diagnosis / Keterangan Awal Tenaga Medis</div>
                    <div class="fw-semibold text-brand"><?= e($item['diagnosis_awal']) ?></div></li>
                <li class="list-group-item"><div class="text-muted small mb-1">Tindakan Sementara</div>
                    <div style="white-space: pre-wrap"><?= $item['tindakan_sementara'] ? e($item['tindakan_sementara']) : '<span class="text-muted small">-</span>' ?></div></li>
                <li class="list-group-item"><div class="text-muted small mb-1">Rekomendasi</div>
                    <div style="white-space: pre-wrap"><?= e($item['rekomendasi']) ?></div></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Penerbit</span>
                    <span><?= e($item['penerbit_nama'] ?? '-') ?> · <?= e(date('d-m-Y', strtotime($item['tanggal_rujukan']))) ?></span></li>
            </ul>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat Surat</div>
            <ul class="list-group list-group-flush">
                <?php if (!$riwayatList): ?>
                    <li class="list-group-item text-muted small">Belum ada riwayat.</li>
                <?php endif; ?>
                <?php foreach ($riwayatList as $r): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?= $r['aksi'] === 'terbit' ? 'primary' : 'secondary' ?>-subtle
                                      text-<?= $r['aksi'] === 'terbit' ? 'primary' : 'secondary' ?>-emphasis">
                            <?= RujukanRiwayat::AKSI_LABEL[$r['aksi']] ?? e($r['aksi']) ?>
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