<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\BukuTamu;
use App\Models\Santri;
use App\Models\SkriningKesehatan;

class BukuTamuController extends Controller
{
    private BukuTamu $tamu;
    private Santri $santri;
    private SkriningKesehatan $skrining;

    public function __construct()
    {
        $this->tamu     = new BukuTamu();
        $this->santri   = new Santri();
        $this->skrining = new SkriningKesehatan();
    }

    public function index(): void
    {
        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'date'   => trim($_GET['date'] ?? ''),
            'inside' => ($_GET['inside'] ?? '') === '1',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('buku_tamu/index', [
            'title'   => 'Buku Tamu Penjenguk',
            'user'    => Auth::user(),
            'data'    => $this->tamu->paginate($page, 10, $filters),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->view('buku_tamu/form', [
            'title'         => 'Catat Tamu Penjenguk',
            'user'          => Auth::user(),
            'santriOptions' => $this->skrining->santriOptions(),
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/buku-tamu/create');

        $in = [
            'nama_penjenguk' => $this->input('nama_penjenguk') ?? '',
            'hubungan'       => $this->input('hubungan') ?? '',
            'santri_id'      => (int) ($this->input('santri_id') ?? 0),
            'no_telepon'     => $this->input('no_telepon') ?? '',
            'tanggal'        => $this->input('tanggal') ?? '',
            'waktu_datang'   => substr($this->input('waktu_datang') ?? '', 0, 5),
            'tujuan'         => $this->input('tujuan') ?? '',
            'catatan'        => $this->input('catatan') ?? '',
        ];

        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/buku-tamu/create');
        }

        $this->tamu->create([
            'nama_penjenguk' => $in['nama_penjenguk'],
            'hubungan'       => $in['hubungan'],
            'santri_id'      => $in['santri_id'],
            'no_telepon'     => $in['no_telepon'] !== '' ? $in['no_telepon'] : null,
            'tanggal'        => $in['tanggal'],
            'waktu_datang'   => $in['waktu_datang'] . ':00',
            'waktu_pulang'   => null,
            'tujuan'         => $in['tujuan'],
            'catatan'        => $in['catatan'] !== '' ? $in['catatan'] : null,
            'petugas_id'     => Auth::id(),
        ]);

        log_activity('create', 'buku_tamu', "Tamu masuk: {$in['nama_penjenguk']} ({$in['hubungan']})");
        Session::setFlash('success', 'Kedatangan tamu tercatat.');
        $this->redirect('/buku-tamu');
    }

    /** Catat waktu pulang tamu (menutup entri). */
    public function pulang($id): void
    {
        $this->checkCsrf('/buku-tamu');
        $item = $this->tamu->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data tamu tidak ditemukan.');
            $this->redirect('/buku-tamu');
        }
        if ($item['waktu_pulang'] !== null) {
            Session::setFlash('error', 'Tamu ini sudah tercatat pulang.');
            $this->redirect('/buku-tamu');
        }

        $waktu = substr($this->input('waktu_pulang') ?? '', 0, 5);
        if ($waktu === '') {
            $waktu = date('H:i');
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $waktu)) {
            Session::setFlash('error', 'Format waktu pulang tidak valid.');
            $this->redirect('/buku-tamu');
        }
        if ($item['tanggal'] === date('Y-m-d') && $waktu <= substr($item['waktu_datang'], 0, 5)) {
            Session::setFlash('error', 'Waktu pulang harus lebih besar dari waktu datang.');
            $this->redirect('/buku-tamu');
        }

        $this->tamu->update((int) $id, ['waktu_pulang' => $waktu . ':00']);
        log_activity('update', 'buku_tamu', "Tamu pulang: {$item['nama_penjenguk']} ({$waktu})");
        Session::setFlash('success', 'Waktu pulang tamu tercatat.');
        $this->redirect('/buku-tamu');
    }

    public function destroy($id): void
    {
        $this->checkCsrf('/buku-tamu');
        $item = $this->tamu->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data tamu tidak ditemukan.');
            $this->redirect('/buku-tamu');
        }

        log_activity('delete', 'buku_tamu', "Menghapus entri tamu: {$item['nama_penjenguk']}");
        $this->tamu->delete((int) $id);
        Session::setFlash('success', 'Entri buku tamu dihapus.');
        $this->redirect('/buku-tamu');
    }

    /** @return string[] */
    private function validate(array $in): array
    {
        $e = [];
        if (mb_strlen($in['nama_penjenguk']) < 3) {
            $e[] = 'Nama penjenguk minimal 3 karakter.';
        }
        if (!in_array($in['hubungan'], BukuTamu::HUBUNGAN, true)) {
            $e[] = 'Hubungan wajib dipilih.';
        }
        $santri = $in['santri_id'] > 0 ? $this->santri->find($in['santri_id']) : null;
        if (!$santri) {
            $e[] = 'Santri yang dijenguk wajib dipilih.';
        }
        if ($in['no_telepon'] !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $in['no_telepon'])) {
            $e[] = 'Format nomor telepon tidak valid.';
        }
        $ts = strtotime($in['tanggal']);
        if ($ts === false) {
            $e[] = 'Tanggal tidak valid.';
        } elseif ($ts > strtotime(date('Y-m-d'))) {
            $e[] = 'Tanggal tidak boleh di masa depan.';
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $in['waktu_datang'])) {
            $e[] = 'Waktu datang tidak valid.';
        }
        if (mb_strlen($in['tujuan']) < 3) {
            $e[] = 'Tujuan wajib diisi (min. 3 karakter).';
        }
        return $e;
    }
}