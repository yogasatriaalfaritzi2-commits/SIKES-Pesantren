<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\SuratIzin;
use App\Models\SuratIzinRiwayat;
use App\Models\Santri;
use App\Models\SkriningKesehatan;

class SuratIzinController extends Controller
{
    private SuratIzin $surat;
    private SuratIzinRiwayat $riwayat;
    private Santri $santri;
    private SkriningKesehatan $skrining;

    public function __construct()
    {
        $this->surat    = new SuratIzin();
        $this->riwayat  = new SuratIzinRiwayat();
        $this->santri   = new Santri();
        $this->skrining = new SkriningKesehatan();
    }

    /* ================= 1. Daftar E-Surat ================= */
    public function index(): void
    {
        $this->surat->expireOverdue();

        $filters = [
            'q'         => trim($_GET['q'] ?? ''),
            'status'    => isset(SuratIzin::STATUS_LABEL[$_GET['status'] ?? '']) ? $_GET['status'] : '',
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('surat/index', [
            'title'   => 'E-Surat Izin Sakit',
            'user'    => Auth::user(),
            'data'    => $this->surat->paginate($page, 10, $filters),
            'filters' => $filters,
        ]);
    }

    /* ================= 2. Terbitkan Surat ================= */
    public function create(): void
    {
        $this->view('surat/form', [
            'title'         => 'Terbitkan E-Surat Izin Sakit',
            'user'          => Auth::user(),
            'santriOptions' => $this->skrining->santriOptions(),
        ]);
    }

    /** Endpoint JSON: pemeriksaan selesai milik satu santri (dropdown form). */
    public function pemeriksaanOptions(): void
    {
        $santriId = (int) ($_GET['santri_id'] ?? 0);
        json_response(['data' => $santriId > 0 ? $this->surat->pemeriksaanOptions($santriId) : []]);
    }

    public function store(): void
    {
        $this->checkCsrf('/surat/create');

        $in = [
            'santri_id'        => (int) ($this->input('santri_id') ?? 0),
            'pemeriksaan_id'   => (int) ($this->input('pemeriksaan_id') ?? 0),
            'tanggal_mulai'    => $this->input('tanggal_mulai') ?? '',
            'tanggal_selesai'  => $this->input('tanggal_selesai') ?? '',
            'keterangan_medis' => $this->input('keterangan_medis') ?? '',
        ];

        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/surat/create');
        }

        $token = SuratIzin::generateToken();
        $id = $this->surat->create([
            'nomor_surat'      => 'PENDING',                       // diisi setelah id diketahui
            'token'            => $token,
            'santri_id'        => $in['santri_id'],
            'pemeriksaan_id'   => $in['pemeriksaan_id'] > 0 ? $in['pemeriksaan_id'] : null,
            'tanggal_mulai'    => $in['tanggal_mulai'],
            'tanggal_selesai'  => $in['tanggal_selesai'],
            'keterangan_medis' => $in['keterangan_medis'],
            'status'           => 'aktif',
            'diterbitkan_id'   => Auth::id(),
        ]);

        $nomor = SuratIzin::generateNomor($id, $in['tanggal_mulai']);
        $this->surat->update($id, ['nomor_surat' => $nomor]);

        $santri = $this->santri->find($in['santri_id']);
        $this->riwayat->add($id, 'terbit', "Surat {$nomor} diterbitkan untuk {$santri['nama']}", Auth::id());
        log_activity('create', 'surat', "Terbit e-surat izin sakit {$nomor} — {$santri['nama']}");
        Session::setFlash('success', "Surat {$nomor} berhasil diterbitkan.");
        $this->redirect('/surat/' . $id);
    }

    /* ================= 3. Detail (internal) ================= */
    public function show($id): void
    {
        $this->surat->expireOverdue();
        $item = $this->surat->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/surat');
        }

        $this->view('surat/show', [
            'title'       => 'Detail E-Surat Izin Sakit',
            'user'        => Auth::user(),
            'item'        => $item,
            'verifyUrl'   => base_url('/verifikasi/' . $item['token']),
            'riwayatList' => $this->riwayat->forSurat((int) $id),
        ]);
    }

    /* ================= 4. Cetak Surat ================= */
    public function cetak($id): void
    {
        $this->surat->expireOverdue();
        $item = $this->surat->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/surat');
        }

        // Render tanpa layout utama (halaman cetak mandiri)
        $this->view('surat/cetak', [
            'item'      => $item,
            'verifyUrl' => base_url('/verifikasi/' . $item['token']),
        ], null);
    }

    /* ================= 5. Batalkan Surat ================= */
    public function batal($id): void
    {
        $this->checkCsrf('/surat');
        $id   = (int) $id;
        $item = $this->surat->find($id);
        if (!$item) {
            Session::setFlash('error', 'Surat tidak ditemukan.');
            $this->redirect('/surat');
        }
        if ($item['status'] !== 'aktif') {
            Session::setFlash('error', 'Hanya surat berstatus "Aktif" yang dapat dibatalkan.');
            $this->redirect('/surat/' . $id);
        }

        $alasan = $this->input('alasan') ?? '';
        if (mb_strlen($alasan) < 5) {
            Session::setFlash('error', 'Alasan pembatalan wajib diisi (min. 5 karakter).');
            $this->redirect('/surat/' . $id);
        }

        $this->surat->update($id, [
            'status'            => 'dibatalkan',
            'alasan_pembatalan' => $alasan,
        ]);
        $this->riwayat->add($id, 'batal', "Dibatalkan: {$alasan}", Auth::id());
        log_activity('update', 'surat', "Membatalkan surat {$item['nomor_surat']}: {$alasan}");
        Session::setFlash('success', 'Surat dibatalkan. QR-nya kini TIDAK VALID bila diverifikasi.');
        $this->redirect('/surat/' . $id);
    }

    /* ================= 6. Verifikasi QR (PUBLIK, tanpa login) ================= */
    public function verify($token): void
    {
        $token = (string) $token;
        $found = false;
        $item  = null;

        // Token harus hex 64 (format kunci kita) — tolak lainnya tanpa query
        if (preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->surat->expireOverdue();
            $item = $this->surat->findByToken($token);
            if ($item) {
                $found = true;
                $this->riwayat->add((int) $item['id'], 'diverifikasi',
                    'Surat diverifikasi via QR (halaman publik)', null);
            }
        }

        // Render tanpa layout utama
        $this->view('surat/verify', [
            'item'  => $found ? $item : null,
            'token' => $token,
        ], null);
    }

    /* ================= Helper ================= */

    /** @return string[] */
    private function validate(array $in): array
    {
        $e = [];

        $santri = $in['santri_id'] > 0 ? $this->santri->find($in['santri_id']) : null;
        if (!$santri) {
            $e[] = 'Santri wajib dipilih.';
        }
        if ($in['pemeriksaan_id'] > 0) {
            $p = $this->surat->pemeriksaanOptions($in['santri_id']);
            $validIds = array_column($p, 'id');
            if (!in_array((string) $in['pemeriksaan_id'], array_map('strval', $validIds), true)) {
                $e[] = 'Pemeriksaan rujukan tidak valid untuk santri tersebut.';
            }
        }

        $ts = strtotime($in['tanggal_mulai']);
        $te = strtotime($in['tanggal_selesai']);
        if ($ts === false) {
            $e[] = 'Tanggal mulai tidak valid.';
        }
        if ($te === false) {
            $e[] = 'Tanggal selesai tidak valid.';
        }
        if ($ts !== false && $te !== false) {
            if ($te < $ts) {
                $e[] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
            }
            $days = (int) floor(($te - $ts) / 86400) + 1;
            if ($days > SuratIzin::MAX_DAYS) {
                $e[] = 'Durasi izin maksimal ' . SuratIzin::MAX_DAYS . ' hari.';
            }
        }

        if (mb_strlen($in['keterangan_medis']) < 5) {
            $e[] = 'Keterangan medis (administratif) wajib diisi (min. 5 karakter).';
        } elseif (mb_strlen($in['keterangan_medis']) > 1000) {
            $e[] = 'Keterangan medis maksimal 1000 karakter.';
        }

        if (!$e && $santri && $this->surat->hasOverlappingActive(
            $in['santri_id'], $in['tanggal_mulai'], $in['tanggal_selesai']
        )) {
            $e[] = 'Santri tersebut masih memiliki surat AKTIF yang rentangnya bertumpang tindih. Batalkan dulu surat lama.';
        }
        return $e;
    }
}