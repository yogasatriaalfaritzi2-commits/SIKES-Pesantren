<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\WaliSantri;

class WaliSantriController extends Controller
{
    private WaliSantri $wali;

    public function __construct()
    {
        $this->wali = new WaliSantri();
    }

    public function index(): void
    {
        $q    = trim($_GET['q'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('wali/index', [
            'title' => 'Wali Santri',
            'user'  => Auth::user(),
            'data'  => $this->wali->paginate($page, 10, $q),
            'q'     => $q,
        ]);
    }

    public function create(): void
    {
        $this->view('wali/form', ['title'=>'Tambah Wali Santri','user'=>Auth::user(),'item'=>null]);
    }

    public function store(): void
    {
        $this->checkCsrf('/wali/create');
        $in = $this->gather();

        if ($errors = $this->validate($in, 0)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/wali/create');
        }

        $this->wali->create($in);
        log_activity('create', 'wali', "Menambah wali santri: {$in['nama']}");
        Session::setFlash('success', "Wali santri {$in['nama']} berhasil ditambahkan.");
        $this->redirect('/wali');
    }

    public function edit($id): void
    {
        $item = $this->wali->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Wali santri tidak ditemukan.');
            $this->redirect('/wali');
        }
        $this->view('wali/form', ['title'=>'Edit Wali Santri','user'=>Auth::user(),'item'=>$item]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/wali');
        $id   = (int) $id;
        $item = $this->wali->find($id);
        if (!$item) {
            Session::setFlash('error', 'Wali santri tidak ditemukan.');
            $this->redirect('/wali');
        }

        $in = $this->gather();
        if ($errors = $this->validate($in, $id)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect("/wali/{$id}/edit");
        }

        $this->wali->update($id, $in);
        log_activity('update', 'wali', "Mengubah wali santri: {$in['nama']}");
        Session::setFlash('success', "Wali santri {$in['nama']} berhasil diperbarui.");
        $this->redirect('/wali');
    }

    public function destroy($id): void
    {
        $this->checkCsrf('/wali');
        $item = $this->wali->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Wali santri tidak ditemukan.');
            $this->redirect('/wali');
        }

        $count = $this->wali->santriCount((int) $id);
        if ($count > 0) {
            Session::setFlash('error', "Wali masih terhubung dengan {$count} santri. Ubah dulu wali pada santri terkait.");
            $this->redirect('/wali');
        }

        $this->wali->delete((int) $id);
        log_activity('delete', 'wali', "Menghapus wali santri: {$item['nama']}");
        Session::setFlash('success', "Wali santri {$item['nama']} berhasil dihapus.");
        $this->redirect('/wali');
    }

    private function gather(): array
    {
        $telepon = $this->input('no_telepon') ?? '';
        return [
            'nama'       => $this->input('nama') ?? '',
            'no_telepon' => preg_replace('/\s+/', ' ', $telepon),
            'pekerjaan'  => $this->input('pekerjaan'),
            'alamat'     => $this->input('alamat'),
        ];
    }

    /** @return string[] */
    private function validate(array $in, int $id): array
    {
        $e = [];
        if (mb_strlen($in['nama']) < 3) {
            $e[] = 'Nama wali minimal 3 karakter.';
        }
        if (!preg_match('/^[0-9+\-\s]{8,20}$/', $in['no_telepon'])) {
            $e[] = 'Nomor telepon wajib 8-20 karakter (angka, +, -).';
        }
        return $e;
    }
}