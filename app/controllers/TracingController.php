<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\KasusMenular;
use App\Models\TracingKontak;
use App\Models\TracingRiwayat;
use App\Models\Santri;
use App\Models\Pemeriksaan;
use App\Models\SkriningKesehatan;

class TracingController extends Controller
{
    private KasusMenular $kasus;
    private TracingKontak $kontak;
    private TracingRiwayat $riwayat;
    private Santri $santri;
    private Pemeriksaan $pemeriksaan;
    private SkriningKesehatan $skrining;

    public function __construct()
    {
        $this->kasus       = new KasusMenular();
        $this->kontak      = new TracingKontak();
        $this->riwayat     = new TracingRiwayat();
        $this->santri      = new Santri();
        $this->pemeriksaan = new Pemeriksaan();
        $this->skrining    = new SkriningKesehatan();
    }

    /* ================= 1. Daftar Kasus + Warning Kamar Aktif ================= */
    public function index(): void
    {
        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'status' => in_array($_GET['status'] ?? '', ['aktif', 'selesai'], true) ? $_GET['status'] : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('tracing/index', [
            'title'      => 'Contact Tracing Penularan',
            'user'       => Auth::user(),
            'data'       => $this->kasus->paginate($page, 10, $filters),
            'filters'    => $filters,
            'kasusAktif' => $this->kasus->roomsWithActiveCases(),
        ]);
    }

    /* ================= 2. Catat Kasus Baru ================= */
    public function createKasus(): void
    {
        $this->view('tracing/form_kasus', [
            'title'         => 'Catat Kasus Penyakit Menular',
            'user'          => Auth::user(),
            'santriOptions' => $this->skrining->santriOptions(),
        ]);
    }

    public function storeKasus(): void
    {
        $this->checkCsrf('/tracing/kasus/create');

        $santriId       = (int) ($this->input('santri_id') ?? 0);
        $penyakit       = $this->input('penyakit') ?? '';
        $tanggalMulai   = $this->input('tanggal_mulai') ?? '';
        $tanggalSelesai = $this->input('tanggal_selesai') ?? '';
        $catatan        = $this->input('catatan') ?? '';

        $errors = [];
        $santri = $santriId > 0 ? $this->santri->find($santriId) : null;
        if (!$santri) {
            $errors[] = 'Santri wajib dipilih.';
        } elseif ($this->kasus->hasActiveCase($santriId)) {
            $errors[] = 'Santri tersebut masih memiliki kasus AKTIF. Tutup kasus sebelumnya terlebih dahulu, atau gunakan kasus yang ada.';
        }
        if (mb_strlen($penyakit) < 3) {
            $errors[] = 'Nama penyakit wajib diisi (min. 3 karakter).';
        }
        $ts = strtotime($tanggalMulai);
        if ($tanggalMulai === '' || $ts === false) {
            $errors[] = 'Tanggal mulai tidak valid.';
        } elseif ($ts > strtotime(date('Y-m-d'))) {
            $errors[] = 'Tanggal mulai tidak boleh di masa depan.';
        }
        if ($tanggalSelesai !== '') {
            $tse = strtotime($tanggalSelesai);
            if ($tse === false) {
                $errors[] = 'Tanggal selesai tidak valid.';
            } elseif ($ts !== false && $tse < $ts) {
                $errors[] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
            } elseif ($tse > strtotime(date('Y-m-d'))) {
                $errors[] = 'Tanggal selesai tidak boleh di masa depan.';
            }
        }

        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/tracing/kasus/create');
        }

        $kasusId = $this->kasus->create([
            'santri_id'       => $santriId,
            'penyakit'        => $penyakit,
            'tanggal_mulai'   => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai !== '' ? $tanggalSelesai : null,
            'status_kasus'    => 'aktif',
            'catatan'         => $catatan !== '' ? $catatan : null,
            'dilaporkan_id'   => Auth::id(),
        ]);

        // ===== Auto-generate kontak potensial dari penghuni kamar =====
        $generated = 0;
        foreach ($this->kasus->potentialContacts($santriId) as $c) {
            if ($this->kontak->addIfMissing($kasusId, (int) $c['id'])) {
                $generated++;
            }
        }

        $this->riwayat->add($kasusId, null, 'kasus_dibuat',
            "Kasus {$penyakit} dicatat untuk {$santri['nama']}", Auth::id());
        $this->riwayat->add($kasusId, null, 'kontak_dibuat',
            $generated > 0
                ? "{$generated} kontak potensial digenerate dari penghuni kamar"
                : 'Tidak ada kontak potensial (santri belum ditempatkan / tidak ada penghuni lain)',
            Auth::id());

        log_activity('create', 'tracing', "Kasus menular: {$penyakit} — {$santri['nama']} ({$generated} kontak potensial)");
        Session::setFlash('success',
            "Kasus tercatat. {$generated} kontak potensial digenerate dari penghuni kamar yang sama.");
        $this->redirect('/tracing/kasus/' . $kasusId);
    }

    /* ================= 3. Detail Kasus + Daftar Kontak Potensial ================= */
    public function show($id): void
    {
        $kasus = $this->kasus->findDetail((int) $id);
        if (!$kasus) {
            Session::setFlash('error', 'Kasus tidak ditemukan.');
            $this->redirect('/tracing');
        }

        $this->view('tracing/show', [
            'title'    => 'Detail Contact Tracing',
            'user'     => Auth::user(),
            'kasus'    => $kasus,
            'kontaks'  => $this->kontak->forKasus((int) $kasus['id']),
            'counts'   => $this->kontak->countByStatus((int) $kasus['id']),
            'riwayat'  => $this->riwayat->forKasus((int) $kasus['id']),
        ]);
    }

    /* ================= Tutup Kasus ================= */
    public function tutupKasus($id): void
    {
        $this->checkCsrf('/tracing');
        $kasus = $this->kasus->find((int) $id);
        if (!$kasus) {
            Session::setFlash('error', 'Kasus tidak ditemukan.');
            $this->redirect('/tracing');
        }
        if ($kasus['status_kasus'] !== 'aktif') {
            Session::setFlash('error', 'Kasus sudah ditutup sebelumnya.');
            $this->redirect('/tracing/kasus/' . $kasus['id']);
        }

        $this->kasus->update((int) $kasus['id'], [
            'status_kasus'    => 'selesai',
            'tanggal_selesai' => date('Y-m-d'),
        ]);
        $this->riwayat->add((int) $kasus['id'], null, 'kasus_ditutup', 'Kasus ditutup oleh petugas', Auth::id());
        log_activity('update', 'tracing', "Menutup kasus menular #{$kasus['id']} ({$kasus['penyakit']})");
        Session::setFlash('success', 'Kasus berhasil ditutup. Warning kamar akan hilang.');
        $this->redirect('/tracing/kasus/' . $kasus['id']);
    }

    /* ================= Sinkronkan ulang kontak (penghuni baru) ================= */
    public function resyncKontak($id): void
    {
        $this->checkCsrf('/tracing');
        $kasus = $this->kasus->find((int) $id);
        if (!$kasus) {
            Session::setFlash('error', 'Kasus tidak ditemukan.');
            $this->redirect('/tracing');
        }

        $added = 0;
        foreach ($this->kasus->potentialContacts((int) $kasus['santri_id']) as $c) {
            if ($this->kontak->addIfMissing((int) $kasus['id'], (int) $c['id'])) {
                $added++;
            }
        }

        $this->riwayat->add((int) $kasus['id'], null, 'kontak_disinkron',
            "Sinkronisasi kontak: {$added} kontak potensial baru ditambahkan", Auth::id());
        log_activity('update', 'tracing', "Sinkronisasi kontak kasus #{$kasus['id']}: +{$added}");
        Session::setFlash('success', "{$added} kontak potensial baru ditambahkan.");
        $this->redirect('/tracing/kasus/' . $kasus['id']);
    }

    /* ================= 5. Buat Pemeriksaan Preventif ================= */
    public function mulaiPeriksa($kontakId): void
    {
        $this->checkCsrf('/tracing');
        $k = $this->kontak->findWithSantri((int) $kontakId);
        if (!$k) {
            Session::setFlash('error', 'Data kontak tidak ditemukan.');
            $this->redirect('/tracing');
        }
        if ($k['status'] !== 'belum_diperiksa') {
            Session::setFlash('error', 'Kontak ini sudah diproses sebelumnya.');
            $this->redirect('/tracing/kasus/' . $k['kasus_id']);
        }
        $kasus = $this->kasus->find((int) $k['kasus_id']);

        // Buat pemeriksaan PREVENTIF (modul Pemeriksaan yang sudah ada)
        $pid = $this->pemeriksaan->create([
            'santri_id'         => (int) $k['santri_id'],
            'tanggal_kunjungan' => date('Y-m-d H:i:s'),
            'jenis_kunjungan'   => 'preventif',
            'keluhan'           => "Pemeriksaan preventif — Contact Tracing kasus #{$k['kasus_id']} "
                                 . "({$kasus['penyakit']}). Santri merupakan KONTAK POTENSIAL, "
                                 . 'belum tentu tertular. Periksa skrining preventif.',
            'status'            => 'menunggu',
            'petugas_id'        => Auth::id(),
        ]);

        $this->kontak->update((int) $k['id'], [
            'status'         => 'dalam_pemeriksaan',
            'pemeriksaan_id' => $pid,
            'ditangani_id'   => Auth::id(),
        ]);
        $this->riwayat->add((int) $k['kasus_id'], (int) $k['id'], 'pemeriksaan_dimulai',
            "Pemeriksaan preventif dibuat untuk {$k['santri_nama']}", Auth::id());
        log_activity('create', 'tracing', "Pemeriksaan preventif tracing untuk {$k['santri_nama']} (kasus #{$k['kasus_id']})");

        // Lanjutkan ke form pemeriksaan yang sudah ada
        Session::setFlash('info', 'Pemeriksaan preventif dibuat. Silakan lengkapi hasilnya pada form pemeriksaan.');
        $this->redirect('/pemeriksaan/' . $pid . '/periksa');
    }

    /* ================= 6. Catat Hasil Pemeriksaan ================= */
    public function catatHasil($kontakId): void
    {
        $this->checkCsrf('/tracing');
        $k = $this->kontak->findWithSantri((int) $kontakId);
        if (!$k) {
            Session::setFlash('error', 'Data kontak tidak ditemukan.');
            $this->redirect('/tracing');
        }
        if (!in_array($k['status'], ['dalam_pemeriksaan', 'perlu_tindak_lanjut'], true)) {
            Session::setFlash('error', 'Hasil hanya dapat dicatat untuk kontak berstatus "Dalam Pemeriksaan" atau "Perlu Tindak Lanjut".');
            $this->redirect('/tracing/kasus/' . $k['kasus_id']);
        }

        $hasil   = $this->input('hasil') ?? '';
        $catatan = $this->input('catatan') ?? '';

        if (!in_array($hasil, ['sudah_diperiksa', 'perlu_tindak_lanjut'], true)) {
            Session::setFlash('error', 'Pilihan hasil tidak valid.');
            $this->redirect('/tracing/kasus/' . $k['kasus_id']);
        }
        if ($hasil === 'perlu_tindak_lanjut' && mb_strlen($catatan) < 3) {
            Session::setFlash('error', 'Catatan wajib diisi jika kontak ditandai "Perlu Tindak Lanjut".');
            $this->redirect('/tracing/kasus/' . $k['kasus_id']);
        }

        $hasilText = $hasil === 'sudah_diperiksa'
            ? 'Tidak ditemukan keluhan pada pemeriksaan preventif.'
            : 'Ditemukan keluhan — perlu tindak lanjut tenaga medis.';
        if ($catatan !== '') {
            $hasilText .= ' Catatan: ' . $catatan;
        }

        $this->kontak->update((int) $k['id'], [
            'status'            => $hasil,
            'hasil_pemeriksaan' => $hasilText,
            'catatan'           => $catatan !== '' ? $catatan : null,
            'ditangani_id'      => Auth::id(),
            'examined_at'       => date('Y-m-d H:i:s'),
        ]);
        $this->riwayat->add((int) $k['kasus_id'], (int) $k['id'], 'hasil_dicatat',
            "{$k['santri_nama']}: " . TracingKontak::STATUS_LABEL[$hasil], Auth::id());
        log_activity('update', 'tracing', "Hasil tracing {$k['santri_nama']}: " . TracingKontak::STATUS_LABEL[$hasil]);

        Session::setFlash('success', 'Hasil pemeriksaan berhasil dicatat: ' . TracingKontak::STATUS_LABEL[$hasil] . '.');
        $this->redirect('/tracing/kasus/' . $k['kasus_id']);
    }
}