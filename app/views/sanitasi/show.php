<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\InspeksiKamar;

 $nilaiLabel = ['baik' => 'Baik', 'cukup' => 'Cukup', 'buruk' => 'Buruk'];
 $warna      = InspeksiKamar::STATUS_COLOR[$item['status']];
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <a href="<?= base_url('/sanitasi/inspeksi') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1">
        <h1 class="h4 fw-bold mb-0">Inspeksi Kamar <?= e($item['kamar_nama']) ?></h1>
        <p class="text-muted small mb-0">
            <?= e(date('d-m-Y', strtotime($item['tanggal_inspeksi']))) ?> ·
            <?= e($item['blok_nama']) ?> · <?= e($item['asrama_nama']) ?> (<?= e(ucfirst($item['asrama_jenis'])) ?>)
            · Penghuni <?= (int) $item['terisi'] ?>/<?= (int) $item['kapasitas'] ?>
        </p>
    </div>
    <span class="badge bg-<?= $warna ?> text-white fs-6 px-3 py-2"><?= InspeksiKamar::STATUS_LABEL[$item['status']] ?></span>
    <a href="<?= base_url('/sanitasi') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
    <?php if (Auth::hasPermission('sanitasi.edit')): ?>
    <a href="<?= base_url('/sanitasi/inspeksi/' . $item['id'] . '/edit') ?>" class="btn btn-brand btn-sm">
        <i class="bi bi-pencil me-1"></i> Edit
    </a>
    <form class="d-inline form-delete" method="POST" action="<?= base_url('/sanitasi/inspeksi/' . $item['id'] . '/delete') ?>"
          data-name="inspeksi Kamar <?= e($item['kamar_nama']) ?> tgl <?= e(date('d-m-Y', strtotime($item['tanggal_inspeksi']))) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
    </form>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clipboard2-check me-2"></i>Hasil per Parameter</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Parameter</th><th class="text-center">Nilai</th><th class="text-center">Poin</th></tr></thead>
                    <tbody>
                        <?php foreach (InspeksiKamar::PARAMS as $field => $label):
                            $val  = $item[$field];
                            $poin = InspeksiKamar::NILAI[$val];
                            $vc   = $val === 'baik' ? 'success' : ($val === 'cukup' ? 'warning' : 'danger');
                        ?>
                        <tr>
                            <td class="fw-semibold small"><?= e($label) ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $vc ?>-subtle text-<?= $vc ?>-emphasis"><?= $nilaiLabel[$val] ?></span>
                            </td>
                            <td class="text-center fw-bold"><?= $poin ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light">
                            <td class="fw-bold">TOTAL</td>
                            <td></td>
                            <td class="text-center fw-bold"><?= (int) $item['skor'] ?>/16</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-journal-text me-2"></i>Catatan Petugas</div>
            <div class="card-body">
                <?php if ($item['catatan_petugas']): ?>
                    <p class="mb-0" style="white-space: pre-wrap"><?= e($item['catatan_petugas']) ?></p>
                <?php else: ?>
                    <span class="text-muted small">Tidak ada catatan.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-speedometer2 me-2"></i>Skor Kebersihan</div>
            <div class="card-body text-center">
                <div class="display-4 fw-bold text-<?= $warna ?>"><?= e((string) $item['persentase']) ?>%</div>
                <span class="badge bg-<?= $warna ?> text-white mb-3"><?= InspeksiKamar::STATUS_LABEL[$item['status']] ?></span>
                <div class="progress" style="height:10px">
                    <div class="progress-bar bg-<?= $warna ?>" style="width:<?= (float) $item['persentase'] ?>%"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted mt-2">
                    <span>Baik &ge; 75%</span><span>Perhatian &ge; 50%</span><span>Risiko &lt; 50%</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-info-circle me-2"></i>Informasi</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Petugas</span><span><?= e($item['petugas_nama'] ?? '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Tanggal</span><span><?= e(date('d-m-Y', strtotime($item['tanggal_inspeksi']))) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Terakhir diubah</span><span><?= e(date('d-m-Y H:i', strtotime($item['updated_at']))) ?></span></li>
            </ul>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Jejak Inspeksi</div>
            <ul class="list-group list-group-flush">
                <?php if (!$riwayatList): ?>
                    <li class="list-group-item text-muted small">Belum ada jejak.</li>
                <?php endif; ?>
                <?php foreach ($riwayatList as $r):
                    $snap = json_decode($r['data_sesudah'] ?? '', true);
                ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?= $r['aksi'] === 'buat' ? 'primary' : 'warning' ?>-subtle
                                      text-<?= $r['aksi'] === 'buat' ? 'primary' : 'warning' ?>-emphasis">
                            <?= $r['aksi'] === 'buat' ? 'Dibuat' : 'Diubah' ?>
                        </span>
                        <small class="text-muted"><?= e(date('d-m-Y H:i', strtotime($r['created_at']))) ?></small>
                    </div>
                    <div class="small mt-1"><?= e($snap['ringkasan'] ?? '-') ?></div>
                    <?php if ($r['user_nama']): ?><div class="small text-muted">oleh <?= e($r['user_nama']) ?></div><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>