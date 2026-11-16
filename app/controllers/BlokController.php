<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Blok;
use App\Models\Asrama;

class BlokController extends Controller
{
    private Blok $blok;
    private Asrama $asrama;

    public function __construct()
    {
        $this->blok   = new Blok();
        $this->asrama = new Asrama();
    }

    /* ================= Daftar Blok ================= */
    public function index(): void
    {
        $q        = trim($_GET['q'] ?? '');
        $asramaId = (int) ($_GET['asrama'] ?? 0);
        $page     = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('blok/index', [
            'title'         => 'Data Blok',
            'user'          => Auth::user(),
            'data'          => $this->blok->paginate($page, 10, $q, $asramaId),
            'filters'       => ['q' => $q, 'asrama' => $asramaId],
            'asramaOptions' => $this->asrama->options(),
        ]);
    }

    /* ================= Tambah ================= */
    public function create(): void
    {
        $this->view('blok/form', [
            'title'         => 'Tambah Blok',
            'user'          => Auth::user(),
            'item'          => null,
            'asramaOptions' => $this->asrama->options(),
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/blok/create');
        $in = $this->gather();

        if ($errors = $this->validate($in, 0)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/blok/create');
        }

        $this->blok->create($in);
        log_activity('create', 'blok', 'Menambah blok: ' . $in['nama']);
        Session::setFlash('success', 'Blok ' . $in['nama'] . ' berhasil ditambahkan.');
        $this->redirect('/blok');
    }

    /* ================= Edit ================= */
    public function edit($id): void
    {
        $item = $this->blok->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Blok tidak ditemukan.');
            $this->redirect('/blok');
        }
        $this->view('blok/form', [
            'title'         => 'Edit Blok',
            'user'          => Auth::user(),
            'item'          => $item,
            'asramaOptions' => $this->asrama->options(),
        ]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/blok');
        $id   = (int) $id;
        $item = $this->blok->find($id);
        if (!$item) {
            Session::setFlash('error', 'Blok tidak ditemukan.');
            $this->redirect('/blok');
        }

        $in = $this->gather();
        if ($errors = $this->validate($in, $id)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/blok/' . $id . '/edit');
        }

        $this->blok->update($id, $in);
        log_activity('update', 'blok', 'Mengubah blok: ' . $in['nama']);
        Session::setFlash('success', 'Blok ' . $in['nama'] . ' berhasil diperbarui.');
        $this->redirect('/blok');
    }

    /* ================= Hapus ================= */
    public function destroy($id): void
    {
        $this->checkCsrf('/blok');
        $item = $this->blok->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Blok tidak ditemukan.');
            $this->redirect('/blok');
        }

        $count = $this->blok->kamarCount((int) $id);
        if ($count > 0) {
            Session::setFlash('error', 'Blok masih memiliki ' . $count . ' kamar. Hapus kamar terlebih dahulu.');
            $this->redirect('/blok');
        }

        $this->blok->delete((int) $id);
        log_activity('delete', 'blok', 'Menghapus blok: ' . $item['nama']);
        Session::setFlash('success', 'Blok ' . $item['nama'] . ' berhasil dihapus.');
        $this->redirect('/blok');
    }

    /** Endpoint JSON untuk cascading dropdown form santri. */
    public function options(): void
    {
        json_response(['data' => $this->blok->optionsByAsrama((int) ($_GET['asrama_id'] ?? 0))]);
    }

    /* ================= Helper ================= */

    private function gather(): array
    {
        return [
            'asrama_id'  => (int) ($this->input('asrama_id') ?? 0),
            'nama'       => $this->input('nama') ?? '',
            'keterangan' => $this->input('keterangan') ?? '',
        ];
    }

    /** @return string[] */
    private function validate(array $in, int $id): array
    {
        $e = [];

        if ($in['asrama_id'] <= 0 || !$this->asrama->find($in['asrama_id'])) {
            $e[] = 'Asrama wajib dipilih.';
        }
        if ($in['nama'] === '') {
            $e[] = 'Nama blok wajib diisi.';
        }

        // Cek unik via method publik model — TANPA akses $db dari controller
        if ($in['asrama_id'] > 0 && $in['nama'] !== ''
            && $this->blok->existsInAsrama($in['nama'], $in['asrama_id'], $id > 0 ? $id : null)) {
            $e[] = 'Blok "' . $in['nama'] . '" sudah ada di asrama tersebut.';
        }
        return $e;
    }
}