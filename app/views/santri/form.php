<?php
use App\Core\Auth;
use App\Core\Session;

/* ============================================================
   NORMALISASI DEFENSIF
   - $item bisa null, [], atau baris lengkap (mode edit)
   - Mode edit ditentukan dari id yang ADA, bukan dari !== null
   - Semua akses array memakai ?? default -> nol warning
   ============================================================ */
 $item   = (isset($item) && is_array($item)) ? $item : [];
 $isEdit = !empty($item['id']);
 $action = $isEdit ? base_url('/santri/' . (int) $item['id']) : base_url('/santri');

/* ---------- Nilai form (aman untuk kedua mode) ---------- */
 $valNis       = $item['nis'] ?? '';
 $valNisn      = $item['nisn'] ?? '';
 $valNama      = $item['nama'] ?? '';
 $valJk        = $item['jenis_kelamin'] ?? '';
 $valTempat    = $item['tempat_lahir'] ?? '';
 $valTglLahir  = $item['tanggal_lahir'] ?? '';
 $valStatus    = $item['status'] ?? 'aktif';
 $valAlamat    = $item['alamat'] ?? '';
 $valTelp      = $item['no_telepon'] ?? '';
 $valWaliId    = (int) ($item['wali_id'] ?? 0);

/* ---------- Data cascading utk pemulihan saat edit ---------- */
 $editAsramaId = (int) ($item['asrama_id'] ?? 0);
 $editBlokId   = (int) ($item['blok_id'] ?? 0);
 $editKamarId  = (int) ($item['kamar_id'] ?? 0);

 $waliOptions   = (isset($waliOptions) && is_array($waliOptions)) ? $waliOptions : [];
 $asramaOptions = (isset($asramaOptions) && is_array($asramaOptions)) ? $asramaOptions : [];
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('/santri') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h4 fw-bold mb-0"><?= $isEdit ? 'Edit Santri' : 'Tambah Santri' ?></h1>
        <p class="text-muted small mb-0">Field bertanda * wajib diisi.</p>
    </div>
</div>

<?php require ROOT_PATH . '/app/views/partials/flash.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 900px">
    <div class="card-body p-4">
        <form method="POST" action="<?= $action ?>" id="santriForm"
              data-asrama="<?= $editAsramaId ?>"
              data-blok="<?= $editBlokId ?>">

            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="fw-semibold text-brand text-uppercase small mb-3">
                <i class="bi bi-person-vcard me-1"></i> Identitas
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="nis">NIS *</label>
                    <input type="text" class="form-control" id="nis" name="nis" required
                           value="<?= e($valNis) ?>" placeholder="cth: 2401001">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="nisn">NISN</label>
                    <input type="text" class="form-control" id="nisn" name="nisn"
                           value="<?= e($valNisn) ?>" placeholder="10 digit (opsional)" maxlength="10" inputmode="numeric">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="nama">Nama Lengkap *</label>
                    <input type="text" class="form-control" id="nama" name="nama" required
                           value="<?= e($valNama) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="jenis_kelamin">Jenis Kelamin *</label>
                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                        <option value="">— Pilih —</option>
                        <option value="L" <?= $valJk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $valJk === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tempat_lahir">Tempat Lahir *</label>
                    <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" required
                           value="<?= e($valTempat) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="tanggal_lahir">Tanggal Lahir *</label>
                    <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required
                           value="<?= e($valTglLahir) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">Status *</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="aktif" <?= $valStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $valStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="fw-semibold text-brand text-uppercase small mt-4 mb-3">
                <i class="bi bi-geo-alt me-1"></i> Kontak & Alamat
            </div>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="alamat">Alamat *</label>
                    <input type="text" class="form-control" id="alamat" name="alamat" required
                           value="<?= e($valAlamat) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="no_telepon">No. Telepon</label>
                    <input type="text" class="form-control" id="no_telepon" name="no_telepon"
                           value="<?= e($valTelp) ?>" placeholder="opsional">
                </div>
            </div>

            <div class="fw-semibold text-brand text-uppercase small mt-4 mb-3">
                <i class="bi bi-building me-1"></i> Wali & Penempatan
            </div>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label" for="wali_id">Wali Santri *</label>
                    <select class="form-select" id="wali_id" name="wali_id" required>
                        <option value="">— Pilih wali —</option>
                        <?php foreach ($waliOptions as $w): ?>
                        <option value="<?= (int) $w['id'] ?>" <?= $valWaliId === (int) $w['id'] ? 'selected' : '' ?>>
                            <?= e($w['nama']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- 3 dropdown ini hanya untuk cascading; yang tersimpan hanya kamar_id -->
                <div class="col-md-7">
                    <label class="form-label d-block">Penempatan Asrama (asrama → blok → kamar)</label>
                    <div class="row g-2">
                        <div class="col-4">
                            <select class="form-select" id="asramaSelect">
                                <option value="">Asrama</option>
                                <?php foreach ($asramaOptions as $a): ?>
                                <option value="<?= (int) $a['id'] ?>"><?= e($a['nama']) ?> (<?= e($a['jenis']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <select class="form-select" id="blokSelect">
                                <option value="">Blok</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <select class="form-select" id="kamarSelect" name="kamar_id"
                                    data-current="<?= $editKamarId ?>">
                                <option value="">Kamar</option>
                            </select>
                        </div>
                    </div>
                    <small class="text-muted">Kosongkan kamar = santri belum ditempatkan. Kapasitas &amp; kecocokan asrama divalidasi otomatis.</small>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="<?= base_url('/santri') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const form      = document.getElementById('santriForm');
    const asramaSel = document.getElementById('asramaSelect');
    const blokSel   = document.getElementById('blokSelect');
    const kamarSel  = document.getElementById('kamarSelect');
    const URL_BLOK  = '<?= base_url('/master/blok-options') ?>';
    const URL_KAMAR = '<?= base_url('/master/kamar-options') ?>';

    const fill = (sel, rows, selected = 0) => {
        sel.innerHTML = '<option value="">— Pilih —</option>' + rows.map(r =>
            `<option value="${r.id}" ${Number(r.id) === Number(selected) ? 'selected' : ''}>${r.label ?? r.nama}</option>`
        ).join('');
    };
    const get = async (url) => (await (await fetch(url)).json()).data;

    asramaSel.addEventListener('change', async () => {
        fill(blokSel, await get(URL_BLOK + '?asrama_id=' + asramaSel.value));
        fill(kamarSel, []);
    });
    blokSel.addEventListener('change', async () => {
        fill(kamarSel, await get(URL_KAMAR + '?blok_id=' + blokSel.value));
    });

    // Saat edit: pulihkan pilihan cascading dari data penempatan tersimpan
    (async () => {
        const a = Number(form.dataset.asrama || 0);
        if (!a) return;
        asramaSel.value = a;
        fill(blokSel, await get(URL_BLOK + '?asrama_id=' + a), Number(form.dataset.blok || 0));
        if (form.dataset.blok) {
            fill(kamarSel, await get(URL_KAMAR + '?blok_id=' + form.dataset.blok),
                 Number(kamarSel.dataset.current || 0));
        }
    })();
})();
</script>