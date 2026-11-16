<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Santri;
use App\Models\WaliSantri;
use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\SkriningKesehatan;

class SantriController extends Controller
{
    private Santri $santri;
    private WaliSantri $wali;
    private Asrama $asrama;
    private Kamar $kamar;
    private SkriningKesehatan $skrining;

    public function __construct()
    {
        $this->santri   = new Santri();
        $this->wali     = new WaliSantri();
        $this->asrama   = new Asrama();
        $this->kamar    = new Kamar();
        $this->skrining = new SkriningKesehatan();
    }

    /* ---------- List + filter + pagination ---------- */
    public function index(): void
    {
        $filters = [
            'q'       => trim($_GET['q'] ?? ''),
            'jk'      => in_array($_GET['jk'] ?? '', ['L', 'P'], true) ? $_GET['jk'] : '',
            'asrama'  => (int) ($_GET['asrama'] ?? 0),
            'status'  => in_array($_GET['status'] ?? '', ['aktif', 'nonaktif'], true) ? $_GET['status'] : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('santri/index', [
            'title'         => 'Data Santri',
            'user'          => Auth::user(),
            'data'          => $this->santri->paginate($page, 10, $filters),
            'filters'       => $filters,
            'asramaOptions' => $this->asrama->options(),
        ]);
    }

    public function create(): void
    {
        $this->view('santri/form', [
            'title'         => 'Tambah Santri',
            'user'          => Auth::user(),
            'item'          => null,
            'waliOptions'   => $this->wali->options(),
            'asramaOptions' => $this->asrama->options(),
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/santri/create');
        $in = $this->gather();

        $errors = $this->validate($in, 0);
        $err    = $this->placementError($in['kamar_id'], $in['jenis_kelamin'], 0);
        if ($err !== null) { $errors[] = $err; }

        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/santri/create');
        }

        $this->santri->create($this->payload($in));
        log_activity('create', 'santri', "Menambah santri: {$in['nama']} (NIS {$in['nis']})");
        Session::setFlash('success', "Santri {$in['nama']} berhasil ditambahkan.");
        $this->redirect('/santri');
    }

    /* ---------- Detail ---------- */
    public function show($id): void
    {
        $item = $this->santri->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data santri tidak ditemukan.');
            $this->redirect('/santri');
        }

        $skrining = $this->skrining->findBySantri((int) $id);

        $this->view('santri/show', [
            'title'            => 'Detail Santri',
            'user'             => Auth::user(),
            'item'             => $item,
            'skrining'         => $skrining,
            'skriningWarnings' => SkriningKesehatan::warningsFrom($skrining),
        ]);
    }

    public function edit($id): void
    {
        $item = $this->santri->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data santri tidak ditemukan.');
            $this->redirect('/santri');
        }

        $this->view('santri/form', [
            'title'         => 'Edit Santri',
            'user'          => Auth::user(),
            'item'          => $item,
            'waliOptions'   => $this->wali->options(),
            'asramaOptions' => $this->asrama->options(),
        ]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/santri');
        $id   = (int) $id;
        $item = $this->santri->find($id);
        if (!$item) {
            Session::setFlash('error', 'Data santri tidak ditemukan.');
            $this->redirect('/santri');
        }

        $in = $this->gather();
        $errors = $this->validate($in, $id);
        $err    = $this->placementError($in['kamar_id'], $in['jenis_kelamin'], $id);
        if ($err !== null) { $errors[] = $err; }

        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect("/santri/{$id}/edit");
        }

        $this->santri->update($id, $this->payload($in));
        log_activity('update', 'santri', "Mengubah santri: {$in['nama']} (NIS {$in['nis']})");
        Session::setFlash('success', "Data santri {$in['nama']} berhasil diperbarui.");
        $this->redirect('/santri');
    }

    public function destroy($id): void
    {
        $this->checkCsrf('/santri');
        $item = $this->santri->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data santri tidak ditemukan.');
            $this->redirect('/santri');
        }

        $this->santri->delete((int) $id);
        log_activity('delete', 'santri', "Menghapus santri: {$item['nama']} (NIS {$item['nis']})");
        Session::setFlash('success', "Santri {$item['nama']} berhasil dihapus.");
        $this->redirect('/santri');
    }

    /* ---------- Helper ---------- */

    private function gather(): array
    {
        return [
            'nis'           => strtoupper($this->input('nis') ?? ''),
            'nisn'          => preg_replace('/\D/', '', $this->input('nisn') ?? ''),
            'nama'          => $this->input('nama') ?? '',
            'jenis_kelamin' => $this->input('jenis_kelamin') ?? '',
            'tempat_lahir'  => $this->input('tempat_lahir') ?? '',
            'tanggal_lahir' => $this->input('tanggal_lahir') ?? '',
            'alamat'        => $this->input('alamat') ?? '',
            'no_telepon'    => $this->input('no_telepon') ?? '',
            'wali_id'       => (int) ($this->input('wali_id') ?? 0),
            'kamar_id'      => (int) ($this->input('kamar_id') ?? 0),
            'status'        => $this->input('status') ?? 'aktif',
        ];
    }

    private function payload(array $in): array
    {
        return [
            'nis'           => $in['nis'],
            'nisn'          => $in['nisn'] !== '' ? $in['nisn'] : null,
            'nama'          => $in['nama'],
            'jenis_kelamin' => $in['jenis_kelamin'],
            'tempat_lahir'  => $in['tempat_lahir'] !== '' ? $in['tempat_lahir'] : null,
            'tanggal_lahir' => $in['tanggal_lahir'] !== '' ? $in['tanggal_lahir'] : null,
            'alamat'        => $in['alamat'] !== '' ? $in['alamat'] : null,
            'no_telepon'    => $in['no_telepon'] !== '' ? $in['no_telepon'] : null,
            'wali_id'       => $in['wali_id'] > 0 ? $in['wali_id'] : null,
            'kamar_id'      => $in['kamar_id'] > 0 ? $in['kamar_id'] : null,
            'status'        => $in['status'],
        ];
    }

    /** @return string[] */
    private function validate(array $in, int $id): array
    {
        $e = [];
        if (!preg_match('/^[A-Z0-9\/\-]{3,20}$/', $in['nis'])) {
            $e[] = 'NIS wajib 3-20 karakter (huruf besar, angka, /, -).';
        } elseif ($this->santri->nisExists($in['nis'], $id ?: null)) {
            $e[] = 'NIS sudah terdaftar.';
        }
        if ($in['nisn'] !== '' && strlen($in['nisn']) !== 10) {
            $e[] = 'NISN harus tepat 10 digit angka.';
        } elseif ($in['nisn'] !== '' && $this->santri->nisnExists($in['nisn'], $id ?: null)) {
            $e[] = 'NISN sudah terdaftar.';
        }
        if (mb_strlen($in['nama']) < 3) {
            $e[] = 'Nama lengkap minimal 3 karakter.';
        }
        if (!in_array($in['jenis_kelamin'], ['L', 'P'], true)) {
            $e[] = 'Jenis kelamin wajib dipilih.';
        }
        if ($in['tempat_lahir'] === '') {
            $e[] = 'Tempat lahir wajib diisi.';
        }
        if ($in['tanggal_lahir'] === '' || !strtotime($in['tanggal_lahir'])) {
            $e[] = 'Tanggal lahir tidak valid.';
        } elseif (strtotime($in['tanggal_lahir']) > time()) {
            $e[] = 'Tanggal lahir tidak boleh di masa depan.';
        }
        if ($in['alamat'] === '') {
            $e[] = 'Alamat wajib diisi.';
        }
        if ($in['no_telepon'] !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $in['no_telepon'])) {
            $e[] = 'Format nomor telepon tidak valid.';
        }
        if ($in['wali_id'] <= 0 || !$this->wali->find($in['wali_id'])) {
            $e[] = 'Wali santri wajib dipilih.';
        }
        if (!in_array($in['status'], ['aktif', 'nonaktif'], true)) {
            $e[] = 'Status tidak valid.';
        }
        return $e;
    }

    private function placementError(int $kamarId, string $jk, int $excludeSantriId): ?string
    {
        if ($kamarId <= 0) {
            return null;
        }
        $kamar = $this->kamar->findWithLocation($kamarId);
        if (!$kamar) {
            return 'Kamar yang dipilih tidak ditemukan.';
        }
        $expected = $jk === 'L' ? 'putra' : 'putri';
        if ($kamar['asrama_jenis'] !== $expected) {
            return "Santri " . ($jk === 'L' ? 'laki-laki' : 'perempuan')
                 . " hanya dapat ditempatkan di asrama {$expected} (Asrama {$kamar['asrama_nama']}).";
        }
        $occupied = $this->kamar->countOccupants($kamarId, $excludeSantriId ?: null);
        if ($occupied >= (int) $kamar['kapasitas']) {
            return "Kamar {$kamar['nama']} sudah penuh ({$occupied}/{$kamar['kapasitas']}).";
        }
        return null;
    }
}