<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Asrama;

class AsramaController extends Controller
{
    private Asrama $asrama;

    public function __construct()
    {
        $this->asrama = new Asrama();
    }

    public function index(): void
    {
        $q     = trim($_GET['q'] ?? '');
        $jenis = in_array($_GET['jenis'] ?? '', ['putra', 'putri'], true) ? $_GET['jenis'] : '';
        $page  = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('asrama/index', [
            'title'   => 'Data Asrama',
            'user'    => Auth::user(),
            'data'    => $this->asrama->paginate($page, 10, $q, $jenis),
            'filters' => ['q' => $q, 'jenis' => $jenis],
        ]);
    }

    public function create(): void
    {
        $this->view('asrama/form', ['title'=>'Tambah Asrama','user'=>Auth::user(),'item'=>null]);
    }

    public function store(): void
    {
        $this->checkCsrf('/asrama/create');
        $in = $this->gather();

        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/asrama/create');
        }

        $this->asrama->create($in);
        log_activity('create', 'asrama', "Menambah asrama: {$in['nama']} ({$in['jenis']})");
        Session::setFlash('success', "Asrama {$in['nama']} berhasil ditambahkan.");
        $this->redirect('/asrama');
    }

    public function edit($id): void
    {
        $item = $this->asrama->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Asrama tidak ditemukan.');
            $this->redirect('/asrama');
        }
        $this->view('asrama/form', ['title'=>'Edit Asrama','user'=>Auth::user(),'item'=>$item]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/asrama');
        $id   = (int) $id;
        $item = $this->asrama->find($id);
        if (!$item) {
            Session::setFlash('error', 'Asrama tidak ditemukan.');
            $this->redirect('/asrama');
        }

        $in = $this->gather();
        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect("/asrama/{$id}/edit");
        }

        $this->asrama->update($id, $in);
        log_activity('update', 'asrama', "Mengubah asrama: {$in['nama']} ({$in['jenis']})");
        Session::setFlash('success', "Asrama {$in['nama']} berhasil diperbarui.");
        $this->redirect('/asrama');
    }

    public function destroy($id): void
    {
        $this->checkCsrf('/asrama');
        $item = $this->asrama->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Asrama tidak ditemukan.');
            $this->redirect('/asrama');
        }

        $count = $this->asrama->blokCount((int) $id);
        if ($count > 0) {
            Session::setFlash('error', "Asrama masih memiliki {$count} blok. Hapus blok terlebih dahulu.");
            $this->redirect('/asrama');
        }

        $this->asrama->delete((int) $id);
        log_activity('delete', 'asrama', "Menghapus asrama: {$item['nama']}");
        Session::setFlash('success', "Asrama {$item['nama']} berhasil dihapus.");
        $this->redirect('/asrama');
    }

    private function gather(): array
    {
        return [
            'nama'       => $this->input('nama') ?? '',
            'jenis'      => $this->input('jenis') ?? '',
            'lokasi'     => $this->input('lokasi'),
            'keterangan' => $this->input('keterangan'),
        ];
    }

    /** @return string[] */
    private function validate(array $in): array
    {
        $e = [];
        if (mb_strlen($in['nama']) < 3) {
            $e[] = 'Nama asrama minimal 3 karakter.';
        }
        if (!in_array($in['jenis'], ['putra', 'putri'], true)) {
            $e[] = 'Jenis asrama wajib dipilih (putra/putri).';
        }
        return $e;
    }
}