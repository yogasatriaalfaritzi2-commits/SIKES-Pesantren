<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\PiketJadwal;

 $qs = function (array $over = []) use ($filters): string {
    $merged = array_filter(
        array_merge($filters, ['page' => 1], $over),
        fn ($v) => $v !== '' && $v !== null
    );
    return base_url('/piket?' . http_build_query($merged));
};
 $bulanID = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-1">Jadwal Piket Kebersihan Massal</h1>
        <p class="text-muted mb-0">Jemur kasur, kerja bakti, pembersihan kamar, sanitasi kamar mandi, pemeriksaan lingkungan (<?= (int) $data['total'] ?> jadwal).</p>
    </div>
    <?php if (Auth::hasPermission('piket.manage')): ?>
    <a href="<?= base_url('/piket/create') ?>" class="btn btn-brand">
        <i class="bi bi-plus-circle me-1"></i> Tambah Jadwal
    </a>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<!-- ===== KALENDER ===== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <a class="btn btn-sm btn-outline-secondary"
           href="<?= base_url('/piket?cy=' . $calPrev['y'] . '&cm=' . $calPrev['m']) ?>">
            <i class="bi bi-chevron-left"></i>
        </a>
        <span class="fw-semibold">
            <i class="bi bi-calendar-week me-2 text-brand"></i><?= $bulanID[$calMonth] ?> <?= $calYear ?>
        </span>
        <a class="btn btn-sm btn-outline-secondary"
           href="<?= base_url('/piket?cy=' . $calNext['y'] . '&cm=' . $calNext['m']) ?>">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>
    <div class="card-body">
        <div id="calendarGrid" class="cal-grid"></div>
        <div id="calDetail" class="mt-3"></div>
    </div>
</div>

<!-- ===== DAFTAR ===== -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end" method="GET" action="<?= base_url('/piket') ?>">
            <input type="hidden" name="cy" value="<?= $calYear ?>"><input type="hidden" name="cm" value="<?= $calMonth ?>">
            <div class="col-md-3">
                <label class="form-label small mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="kegiatan / lokasi / PJ" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Jenis</label>
                <select name="jenis" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (PiketJadwal::JENIS as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $filters['jenis'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (PiketJadwal::STATUS_LABEL as $v => $l): ?>
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
            <div class="col-md-1 d-grid">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Kegiatan</th><th>Lokasi</th><th>Tanggal &amp; Waktu</th><th>Penanggung Jawab</th><th>Status</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$data['rows']): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada jadwal piket.</td></tr>
            <?php endif; ?>
            <?php foreach ($data['rows'] as $row): ?>
                <tr>
                    <td>
                        <div class="fw-semibold">
                            <a class="text-decoration-none" href="<?= base_url('/piket/' . $row['id']) ?>"><?= e($row['nama_kegiatan']) ?></a>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= PiketJadwal::JENIS[$row['jenis']] ?></span>
                        <?php if ($row['catatan']): ?><div class="small text-muted mt-1"><?= e(mb_strimwidth($row['catatan'], 0, 50, '…')) ?></div><?php endif; ?>
                    </td>
                    <td class="small"><?= e($row['lokasi']) ?></td>
                    <td class="small text-nowrap">
                        <i class="bi bi-calendar3 me-1 text-muted"></i><?= e(date('d-m-Y', strtotime($row['tanggal']))) ?><br>
                        <i class="bi bi-clock me-1 text-muted"></i><?= e(substr($row['waktu_mulai'], 0, 5)) ?>
                        <?= $row['waktu_selesai'] ? '– ' . e(substr($row['waktu_selesai'], 0, 5)) : '' ?>
                    </td>
                    <td class="small"><?= e($row['penanggung_jawab']) ?></td>
                    <td>
                        <span class="badge bg-<?= PiketJadwal::STATUS_COLOR[$row['status']] ?> text-white">
                            <?= PiketJadwal::STATUS_LABEL[$row['status']] ?>
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if (Auth::hasPermission('piket.manage')): ?>
                            <?php if ($row['status'] === 'terjadwal'): ?>
                            <form class="d-inline" method="POST" action="<?= base_url('/piket/' . $row['id'] . '/mulai') ?>"
                                  onsubmit="return confirm('Mulai kegiatan <?= e($row['nama_kegiatan']) ?>?')">
                                <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                                <button class="btn btn-sm btn-brand" title="Mulai"><i class="bi bi-play-fill"></i></button>
                            </form>
                            <?php elseif ($row['status'] === 'berlangsung'): ?>
                            <form class="d-inline" method="POST" action="<?= base_url('/piket/' . $row['id'] . '/selesai') ?>"
                                  onsubmit="return confirm('Selesaikan kegiatan <?= e($row['nama_kegiatan']) ?>?')">
                                <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                                <button class="btn btn-sm btn-outline-success" title="Selesaikan"><i class="bi bi-check2-circle"></i></button>
                            </form>
                            <?php endif; ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/piket/' . $row['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline form-delete" method="POST" action="<?= base_url('/piket/' . $row['id'] . '/delete') ?>"
                              data-name="jadwal <?= e($row['nama_kegiatan']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php else: ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('/piket/' . $row['id']) ?>" title="Detail"><i class="bi bi-eye"></i></a>
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

<script>
(function () {
    const EVENTS = <?= json_encode($eventsByDate, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    const YEAR = <?= $calYear ?>, MONTH = <?= $calMonth ?>;
    const STATUS_COLOR = { terjadwal: '#0dcaf0', berlangsung: '#ffc107', selesai: '#198754' };
    const STATUS_LABEL = { terjadwal: 'Terjadwal', berlangsung: 'Berlangsung', selesai: 'Selesai' };
    const JENIS = <?= json_encode(PiketJadwal::JENIS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    const TODAY = '<?= date('Y-m-d') ?>';
    const BASE  = '<?= base_url('/piket') ?>';

    const grid   = document.getElementById('calendarGrid');
    const detail = document.getElementById('calDetail');
    const pad    = n => String(n).padStart(2, '0');

    // Grid dimulai hari Senin; offset = sel kosong di awal
    let startDow = new Date(YEAR, MONTH - 1, 1).getDay();
    startDow = (startDow + 6) % 7;
    const daysInMonth = new Date(YEAR, MONTH, 0).getDate();

    let html = ['Sen','Sel','Rab','Kam','Jum','Sab','Aha'].map(d => `<div class="cal-head">${d}</div>`).join('');
    for (let i = 0; i < startDow; i++) html += '<div class="cal-cell cal-empty"></div>';
    for (let d = 1; d <= daysInMonth; d++) {
        const ds   = `${YEAR}-${pad(MONTH)}-${pad(d)}`;
        const evs  = EVENTS[ds] || [];
        const today = ds === TODAY;
        const dots = evs.slice(0, 3).map(ev =>
            `<span class="cal-dot" style="background:${STATUS_COLOR[ev.status]}" title="${ev.nama_kegiatan}"></span>`).join('');
        html += `<div class="cal-cell cal-day ${today ? 'cal-today' : ''} ${evs.length ? 'cal-has-event' : ''}"
                      data-date="${ds}" role="button" tabindex="0">
                    <div class="cal-num">${d} ${dots}</div>
                    ${evs.length ? `<div class="cal-ev text-truncate">${evs[0].nama_kegiatan}</div>` : ''}
                 </div>`;
    }
    grid.innerHTML = html;

    function showDetail(ds) {
        const evs = EVENTS[ds] || [];
        if (!evs.length) { detail.innerHTML = ''; return; }
        detail.innerHTML = `<div class="card border">
            <div class="card-header bg-white small fw-semibold py-2">
                <i class="bi bi-calendar-event me-1"></i>Kegiatan ${ds} (${evs.length})
            </div>
            <ul class="list-group list-group-flush">
                ${evs.map(ev => `<li class="list-group-item small d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge" style="background:${STATUS_COLOR[ev.status]}">${STATUS_LABEL[ev.status]}</span>
                        <strong class="ms-1">${ev.nama_kegiatan}</strong>
                        <div class="text-muted">${JENIS[ev.jenis]} · ${ev.lokasi} · ${ev.waktu_mulai.slice(0,5)}${ev.waktu_selesai ? '–' + ev.waktu_selesai.slice(0,5) : ''}</div>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary" href="${BASE}/${ev.id}"><i class="bi bi-eye"></i></a>
                </li>`).join('')}
            </ul></div>`;
    }

    grid.addEventListener('click', function (e) {
        const cell = e.target.closest('.cal-day');
        if (cell) showDetail(cell.dataset.date);
    });
})();
</script>