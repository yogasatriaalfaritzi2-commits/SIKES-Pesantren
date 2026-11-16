<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Keuangan;

class KeuanganController extends Controller
{
    private Keuangan $keuangan;

    public function __construct()
    {
        $this->keuangan = new Keuangan();
    }

    public function index(): void
    {
        $filters = [
            'q'         => trim($_GET['q'] ?? ''),
            'jenis'     => isset(Keuangan::JENIS[$_GET['jenis'] ?? '']) ? $_GET['jenis'] : '',
            'kategori'  => isset(Keuangan::KATEGORI[$_GET['kategori'] ?? '']) ? $_GET['kategori'] : '',
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('keuangan/index', [
            'title'   => 'Keuangan Pondok',
            'user'    => Auth::user(),
            'data'    => $this->keuangan->paginate($page, 10, $filters),
            'totals'  => $this->keuangan->totals($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->view('keuangan/form', ['title' => 'Catat Transaksi', 'user' => Auth::user(), 'item' => null]);
    }

    public function store(): void
    {
        $this->checkCsrf('/keuangan/create');
        $in = $this->gather();

        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/keuangan/create');
        }

        $this->keuangan->create($this->payload($in));
        log_activity('create', 'keuangan', $in['jenis'] . ' ' . $in['jumlah'] . ' (' . $in['kategori'] . ')');
        Session::setFlash('success', 'Transaksi tersimpan.');
        $this->redirect('/keuangan');
    }

    public function edit($id): void
    {
        $item = $this->keuangan->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('/keuangan');
        }

        $this->view('keuangan/form', ['title' => 'Edit Transaksi', 'user' => Auth::user(), 'item' => $item]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/keuangan');
        $id   = (int) $id;
        $item = $this->keuangan->find($id);
        if (!$item) {
            Session::setFlash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('/keuangan');
        }

        $in = $this->gather();
        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/keuangan/' . $id . '/edit');
        }

        $payload = $this->payload($in);
        unset($payload['user_id']); // pencatat asli tetap terjaga
        $this->keuangan->update($id, $payload);
        log_activity('update', 'keuangan', 'Mengubah transaksi ID ' . $id);
        Session::setFlash('success', 'Transaksi diperbarui.');
        $this->redirect('/keuangan');
    }

    public function destroy($id): void
    {
        $this->checkCsrf('/keuangan');
        $item = $this->keuangan->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('/keuangan');
        }

        $this->keuangan->delete((int) $id);
        log_activity('delete', 'keuangan', 'Menghapus transaksi ID ' . $id);
        Session::setFlash('success', 'Transaksi dihapus.');
        $this->redirect('/keuangan');
    }

    /* ================= Helper ================= */

    private function gather(): array
    {
        return [
            'jenis'      => $this->input('jenis') ?? '',
            'kategori'   => $this->input('kategori') ?? '',
            'jumlah'     => (float) ($this->input('jumlah') ?? 0),
            'tanggal'    => $this->input('tanggal') ?? '',
            'keterangan' => $this->input('keterangan') ?? '',
            'referensi'  => $this->input('referensi') ?? '',
        ];
    }

    private function payload(array $in): array
    {
        return [
            'jenis'      => $in['jenis'],
            'kategori'   => $in['kategori'],
            'jumlah'     => $in['jumlah'],
            'tanggal'    => $in['tanggal'],
            'keterangan' => $in['keterangan'] !== '' ? $in['keterangan'] : null,
            'referensi'  => $in['referensi'] !== '' ? $in['referensi'] : null,
            'user_id'    => Auth::id(),
        ];
    }

    /** @return string[] */
    private function validate(array $in): array
    {
        $e = [];
        if (!isset(Keuangan::JENIS[$in['jenis']])) {
            $e[] = 'Jenis transaksi wajib dipilih.';
        }
        if (!isset(Keuangan::KATEGORI[$in['kategori']])) {
            $e[] = 'Kategori wajib dipilih.';
        }
        if ($in['jumlah'] <= 0 || $in['jumlah'] > 99999999999) {
            $e[] = 'Jumlah harus lebih besar dari 0.';
        }
        $ts = strtotime($in['tanggal']);
        if ($ts === false) {
            $e[] = 'Tanggal tidak valid.';
        } elseif ($ts > strtotime(date('Y-m-d'))) {
            $e[] = 'Tanggal tidak boleh di masa depan.';
        }
        if (mb_strlen($in['keterangan']) > 255) {
            $e[] = 'Keterangan maksimal 255 karakter.';
        }
        if (mb_strlen($in['referensi']) > 100) {
            $e[] = 'Referensi maksimal 100 karakter.';
        }
        return $e;
    }
}