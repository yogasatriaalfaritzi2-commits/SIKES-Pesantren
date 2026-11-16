<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\Pemeriksaan;

 $statusMap = ['menunggu' => 'warning', 'selesai' => 'success', 'batal' => 'secondary'];
 $umur      = $santri['tanggal_lahir'] ? (new DateTime($santri['tanggal_lahir']))->diff(new DateTime())->y : null;
 $selesai   = array_filter($list, fn ($r) => $r['status'] === 'selesai');
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <a href="<?= base_url('/pemeriksaan') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0">Riwayat Kesehatan — <?= e($santri['nama']) ?></h1>
        <p class="text-muted small mb-0">NIS <?= e($santri['nis']) ?> · Rekap kunjungan &amp; skrining (<?= count($selesai) ?> pemeriksaan selesai).</p>
    </div>
    <a href="<?= base_url('/santri/' . $santri['id']) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-person me-1"></i> Data Santri
    </a>
    <?php if ($skrining && Auth::hasPermission('pemeriksaan.view')): ?>
    <a href="<?= base_url('/skrining/' . $skrining['id']) ?>" class="btn btn-outline-brand btn-sm">
        <i class="bi bi-clipboard2-pulse me-1"></i> Profil Skrining
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<?php if ($warnings): ?>
<div class="alert alert-danger d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill mt-1 fs-5"></i>
    <div>
        <strong>Kondisi khusus (skrining awal):</strong>
        <?php foreach ($warnings as $w): ?>
            <span class="badge bg-danger-subtle text-danger-emphasis me-1 mt-1"><?= e($w) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <!-- Ringkasan -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person-vcard me-2"></i>Identitas</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Nama</span><span><?= e($santri['nama']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">NIS / NISN</span><span><?= e($santri['nis']) ?><?= $santri['nisn'] ? ' / ' . e($santri['nisn']) : '' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Umur</span><span><?= $umur !== null ? $umur . ' tahun' : '-' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Penempatan</span>
                    <span class="text-end"><?= e($santri['asrama_nama'] ?? '-') ?><?= $santri['kamar_nama'] ? ' · Kam. ' . e($santri['kamar_nama']) : '' ?></span>
                </li>
            </ul>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-heart-pulse me-2"></i>Ringkasan Skrining Awal</div>
            <?php if ($skrining): ?>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Tanggal Skrining</span><span><?= e(date('d-m-Y', strtotime($skrining['tanggal_skrining']))) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">IMT</span>
                    <span><?= $skrining['imt'] !== null ? e((string) $skrining['imt']) . ' (' . e($skrining['kategori_imt'] ?? '-') . ')' : '-' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Gol. Darah</span>
                    <span><?= $skrining['golongan_darah'] !== '-' ? e($skrining['golongan_darah']) . ($skrining['rhesus'] !== '-' ? e($skrining['rhesus'] === 'positif' ? '+' : '-') : '') : '-' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Kondisi Khusus</span>
                    <?php if ($warnings): ?>
                        <span class="badge bg-danger-subtle text-danger-emphasis"><?= count($warnings) ?> kondisi</span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success-emphasis">Tidak ada</span>
                    <?php endif; ?>
                </li>
            </ul>
            <?php else: ?>
            <div class="card-body">
                <span class="text-muted small">Santri belum diskrining.</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel kunjungan -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-clock-history me-2"></i>Histori Kunjungan (<?= count($list) ?>)
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th>Tanggal</th><th>Jenis</th><th>Keluhan</th><th>Diagnosis</th><th>Status</th><th class="text-end">Aksi</th>
                    </tr></thead>
                    <tbody>
                    <?php if (!$list): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat kunjungan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($list as $row): ?>
                        <tr>
                            <td class="small text-nowrap"><?= e(date('d-m-Y H:i', strtotime($row['tanggal_kunjungan']))) ?></td>
                            <td class="small"><?= Pemeriksaan::JENIS_LABEL[$row['jenis_kunjungan']] ?></td>
                            <td class="small" style="max-width:180px"><?= e(mb_strimwidth($row['keluhan'], 0, 50, '…')) ?></td>
                            <td class="small" style="max-width:180px">
                                <?= $row['diagnosis'] ? e(mb_strimwidth($row['diagnosis'], 0, 50, '…')) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $statusMap[$row['status']] ?>-subtle text-<?= $statusMap[$row['status']] ?>-emphasis">
                                    <?= Pemeriksaan::STATUS_LABEL[$row['status']] ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/pemeriksaan/' . $row['id']) ?>"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>