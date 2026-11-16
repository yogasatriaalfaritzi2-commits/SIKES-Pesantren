<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Kamar;
use App\Models\Blok;
use App\Models\Asrama;

class KamarController extends Controller
{
    private Kamar $kamar;
    private Blok $blok;
    private Asrama $asrama;

    public function __construct()
    {
        $this->kamar  = new Kamar();
        $this->blok   = new Blok();
        $this->asrama = new Asrama();
    }

    /* ================= Daftar Kamar ================= */
    public function index(): void
    {
        $q        = trim($_GET['q'] ?? '');
        $asramaId = (int) ($_GET['asrama'] ?? 0);
        $page     = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('kamar/index', [
            'title'         => 'Data Kamar',
            'user'          => Auth::user(),
            'data'          => $this->kamar->paginate($page, 10, $q, $asramaId),
            'filters'       => ['q' => $q, 'asrama' => $asramaId],
            'asramaOptions' => $this->asrama->options(),
        ]);
    }

    /** Relasi penghuni kamar: detail kamar + daftar penghuninya. */
    public function detail($id): void
    {
        $item = $this->kamar->findWithLocation((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Kamar tidak ditemukan.');
            $this->redirect('/kamar');
        }

        $this->view('kamar/detail', [
            'title'    => 'Detail Kamar',
            'user'     => Auth::user(),
            'item'     => $item,
            'occupants'=> $this->kamar->occupants((int) $item['id']),
            'terisi'   => $this->kamar->countOccupants((int) $item['id']),
        ]);
    }

    /* ================= Tambah ================= */
    public function create(): void
    {
        $this->view('kamar/form', [
            'title'    => 'Tambah Kamar',
            'user'     => Auth::user(),
            'item'     => null,
            'blokList' => $this->blok->allWithAsrama(),
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/kamar/create');
        $in = $this->gather();

        if ($errors = $this->validate($in, 0)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/kamar/create');
        }

        $this->kamar->create($in);
        log_activity('create', 'kamar', 'Menambah kamar: ' . $in['nama']);
        Session::setFlash('success', 'Kamar ' . $in['nama'] . ' berhasil ditambahkan.');
        $this->redirect('/kamar');
    }

    /* ================= Edit ================= */
    public function edit($id): void
    {
        $item = $this->kamar->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Kamar tidak ditemukan.');
            $this->redirect('/kamar');
        }
        $this->view('kamar/form', [
            'title'    => 'Edit Kamar',
            'user'     => Auth::user(),
            'item'     => $item,
            'blokList' => $this->blok->allWithAsrama(),
        ]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/kamar');
        $id   = (int) $id;
        $item = $this->kamar->find($id);
        if (!$item) {
            Session::setFlash('error', 'Kamar tidak ditemukan.');
            $this->redirect('/kamar');
        }

        $in = $this->gather();
        if ($errors = $this->validate($in, $id)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/kamar/' . $id . '/edit');
        }

        $this->kamar->update($id, $in);
        log_activity('update', 'kamar', 'Mengubah kamar: ' . $in['nama']);
        Session::setFlash('success', 'Kamar ' . $in['nama'] . ' berhasil diperbarui.');
        $this->redirect('/kamar');
    }

    /* ================= Hapus ================= */
    public function destroy($id): void
    {
        $this->checkCsrf('/kamar');
        $item = $this->kamar->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Kamar tidak ditemukan.');
            $this->redirect('/kamar');
        }

        $count = $this->kamar->countSantri((int) $id);
        if ($count > 0) {
            Session::setFlash('error', 'Kamar masih menampung ' . $count . ' santri. Pindahkan santri terlebih dahulu.');
            $this->redirect('/kamar');
        }

        $this->kamar->delete((int) $id);
        log_activity('delete', 'kamar', 'Menghapus kamar: ' . $item['nama']);
        Session::setFlash('success', 'Kamar ' . $item['nama'] . ' berhasil dihapus.');
        $this->redirect('/kamar');
    }

    /** Endpoint JSON untuk cascading dropdown form santri. */
    public function options(): void
    {
        $rows = $this->kamar->optionsByBlok((int) ($_GET['blok_id'] ?? 0));
        foreach ($rows as &$r) {
            $r['label'] = $r['nama'] . ' (' . $r['terisi'] . '/' . $r['kapasitas'] . ')';
        }
        json_response(['data' => $rows]);
    }

    /* ================= Helper ================= */

    private function gather(): array
    {
        return [
            'blok_id'   => (int) ($this->input('blok_id') ?? 0),
            'nama'      => $this->input('nama') ?? '',
            'kapasitas' => (int) ($this->input('kapasitas') ?? 0),
        ];
    }

    /** @return string[] */
    private function validate(array $in, int $id): array
    {
        $e = [];

        if ($in['blok_id'] <= 0 || !$this->blok->find($in['blok_id'])) {
            $e[] = 'Blok wajib dipilih.';
        }
        if ($in['nama'] === '') {
            $e[] = 'Nama/nomor kamar wajib diisi.';
        }
        if ($in['kapasitas'] < 1 || $in['kapasitas'] > 50) {
            $e[] = 'Kapasitas harus antara 1-50.';
        }

        // Cek unik via method publik model — TANPA akses $db dari controller
        if ($in['blok_id'] > 0 && $in['nama'] !== ''
            && $this->kamar->existsInBlok($in['nama'], $in['blok_id'], $id > 0 ? $id : null)) {
            $e[] = 'Kamar "' . $in['nama'] . '" sudah ada di blok tersebut.';
        }

        // Kapasitas tidak boleh dikurangi di bawah jumlah penghuni aktif saat ini
        if ($id > 0 && $in['kapasitas'] >= 1) {
            $occupied = $this->kamar->countOccupants($id);
            if ($in['kapasitas'] < $occupied) {
                $e[] = 'Kapasitas tidak boleh lebih kecil dari penghuni aktif saat ini (' . $occupied . ').';
            }
        }
        return $e;
    }
}