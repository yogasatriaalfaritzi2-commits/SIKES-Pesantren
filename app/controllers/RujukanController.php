<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Rujukan;
use App\Models\RujukanRiwayat;
use App\Models\Santri;
use App\Models\SkriningKesehatan;

class RujukanController extends Controller
{
    private Rujukan $rujukan;
    private RujukanRiwayat $riwayat;
    private Santri $santri;
    private SkriningKesehatan $skrining;

    public function __construct()
    {
        $this->rujukan   = new Rujukan();
        $this->riwayat   = new RujukanRiwayat();
        $this->santri    = new Santri();
        $this->skrining  = new SkriningKesehatan();
    }

    /* ================= Histori Rujukan ================= */
    public function index(): void
    {
        $filters = [
            'q'         => trim($_GET['q'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
            'santri_id' => (int) ($_GET['santri_id'] ?? 0),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('rujukan/index', [
            'title'   => 'Surat Rujukan Eksternal',
            'user'    => Auth::user(),
            'data'    => $this->rujukan->paginate($page, 10, $filters),
            'filters' => $filters,
        ]);
    }

    /* ================= Terbitkan ================= */
    public function create(): void
    {
        $this->view('rujukan/form', [
            'title'         => 'Terbitkan Surat Rujukan',
            'user'          => Auth::user(),
            'santriOptions' => $this->skrining->santriOptions(),
        ]);
    }

    /** Endpoint JSON: pemeriksaan selesai milik satu santri (dropdown). */
    public function pemeriksaanOptions(): void
    {
        $santriId = (int) ($_GET['santri_id'] ?? 0);
        json_response(['data' => $santriId > 0 ? $this->rujukan->pemeriksaanOptions($santriId) : []]);
    }

    public function store(): void
    {
        $this->checkCsrf('/rujukan/create');
        $in = $this->gather();

        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/rujukan/create');
        }

        $id = $this->rujukan->create([
            'nomor_surat'       => 'PENDING', // diisi setelah id diketahui
            'santri_id'         => $in['santri_id'],
            'pemeriksaan_id'    => $in['pemeriksaan_id'] > 0 ? $in['pemeriksaan_id'] : null,
            'tujuan_faskes'     => $in['tujuan_faskes'],
            'keluhan'           => $in['keluhan'],
            'hasil_pemeriksaan' => $in['hasil_pemeriksaan'],
            'diagnosis_awal'    => $in['diagnosis_awal'],
            'tindakan_sementara'=> $in['tindakan_sementara'] !== '' ? $in['tindakan_sementara'] : null,
            'rekomendasi'       => $in['rekomendasi'],
            'tanggal_rujukan'   => $in['tanggal_rujukan'],
            'penerbit_id'       => Auth::id(),
        ]);

        $nomor = Rujukan::generateNomor($id, $in['tanggal_rujukan']);
        $this->rujukan->update($id, ['nomor_surat' => $nomor]);

        $santri = $this->santri->find($in['santri_id']);
        $this->riwayat->add($id, 'terbit', "Surat {$nomor} diterbitkan untuk {$santri['nama']}", Auth::id());
        log_activity('create', 'rujukan', "Terbit surat rujukan {$nomor} — {$santri['nama']} ke {$in['tujuan_faskes']}");
        Session::setFlash('success', "Surat rujukan {$nomor} berhasil diterbitkan.");
        $this->redirect('/rujukan/' . $id);
    }

    /* ================= Preview (internal) ================= */
    public function show($id): void
    {
        $item = $this->rujukan->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Surat rujukan tidak ditemukan.');
            $this->redirect('/rujukan');
        }

        $this->view('rujukan/show', [
            'title'       => 'Preview Surat Rujukan',
            'user'        => Auth::user(),
            'item'        => $item,
            'riwayatList' => $this->riwayat->forRujukan((int) $id),
        ]);
    }

    /* ================= Cetak / PDF ================= */
    public function cetak($id): void
    {
        $item = $this->rujukan->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Surat rujukan tidak ditemukan.');
            $this->redirect('/rujukan');
        }

        $this->riwayat->add((int) $item['id'], 'dicetak',
            'Surat dicetak / disimpan sebagai PDF', Auth::id());
        log_activity('update', 'rujukan', "Mencetak surat rujukan {$item['nomor_surat']}");

        $this->view('rujukan/cetak', ['item' => $item], null);
    }

    /* ================= Helper ================= */

    private function gather(): array
    {
        return [
            'santri_id'          => (int) ($this->input('santri_id') ?? 0),
            'pemeriksaan_id'     => (int) ($this->input('pemeriksaan_id') ?? 0),
            'tujuan_faskes'      => $this->input('tujuan_faskes') ?? '',
            'keluhan'            => $this->input('keluhan') ?? '',
            'hasil_pemeriksaan'  => $this->input('hasil_pemeriksaan') ?? '',
            'diagnosis_awal'     => $this->input('diagnosis_awal') ?? '',
            'tindakan_sementara' => $this->input('tindakan_sementara') ?? '',
            'rekomendasi'        => $this->input('rekomendasi') ?? '',
            'tanggal_rujukan'    => $this->input('tanggal_rujukan') ?? '',
        ];
    }

    /** @return string[] */
    private function validate(array $in): array
    {
        $e = [];

        $santri = $in['santri_id'] > 0 ? $this->santri->find($in['santri_id']) : null;
        if (!$santri) {
            $e[] = 'Santri wajib dipilih.';
        }
        if ($in['pemeriksaan_id'] > 0) {
            $validIds = array_map('strval', array_column($this->rujukan->pemeriksaanOptions($in['santri_id']), 'id'));
            if (!in_array((string) $in['pemeriksaan_id'], $validIds, true)) {
                $e[] = 'Pemeriksaan rujukan tidak valid untuk santri tersebut.';
            }
        }
        if (mb_strlen($in['tujuan_faskes']) < 3) {
            $e[] = 'Tujuan fasilitas kesehatan wajib diisi (min. 3 karakter).';
        }
        foreach ([
            'keluhan'            => 'Keluhan',
            'hasil_pemeriksaan'  => 'Hasil pemeriksaan',
            'diagnosis_awal'     => 'Diagnosis/keterangan awal',
            'rekomendasi'        => 'Rekomendasi',
        ] as $f => $label) {
            if (mb_strlen($in[$f]) < 5) {
                $e[] = "{$label} wajib diisi (min. 5 karakter).";
            }
        }

        $ts = strtotime($in['tanggal_rujukan']);
        if ($ts === false) {
            $e[] = 'Tanggal rujukan tidak valid.';
        } elseif ($ts > strtotime(date('Y-m-d'))) {
            $e[] = 'Tanggal rujukan tidak boleh di masa depan.';
        }
        return $e;
    }
}