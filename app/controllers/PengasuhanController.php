<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Pengasuhan;
use App\Models\Santri;
use App\Models\SkriningKesehatan;

class PengasuhanController extends Controller
{
    private Pengasuhan $pengasuhan;
    private Santri $santri;
    private SkriningKesehatan $skrining;

    public function __construct()
    {
        $this->pengasuhan = new Pengasuhan();
        $this->santri     = new Santri();
        $this->skrining   = new SkriningKesehatan();
    }

    public function index(): void
    {
        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'jenis'  => isset(Pengasuhan::JENIS[$_GET['jenis'] ?? '']) ? $_GET['jenis'] : '',
            'status' => isset(Pengasuhan::STATUS_LABEL[$_GET['status'] ?? '']) ? $_GET['status'] : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('pengasuhan/index', [
            'title'   => 'Pengasuhan — Perizinan & Pelanggaran',
            'user'    => Auth::user(),
            'data'    => $this->pengasuhan->paginate($page, 10, $filters),
            'counts'  => $this->pengasuhan->counts(),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->view('pengasuhan/form', [
            'title'         => 'Catat Perizinan / Pelanggaran',
            'user'          => Auth::user(),
            'item'          => null,
            'santriOptions' => $this->skrining->santriOptions(),
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/pengasuhan/create');
        $in = $this->gather();

        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/pengasuhan/create');
        }

        $this->pengasuhan->create($this->payload($in));
        log_activity('create', 'pengasuhan', 'Catat ' . $in['jenis'] . ': ' . $in['keterangan']);
        Session::setFlash('success', 'Catatan tersimpan.');
        $this->redirect('/pengasuhan');
    }

    public function edit($id): void
    {
        $item = $this->pengasuhan->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Catatan tidak ditemukan.');
            $this->redirect('/pengasuhan');
        }

        $this->view('pengasuhan/form', [
            'title'         => 'Edit Catatan Pengasuhan',
            'user'          => Auth::user(),
            'item'          => $item,
            'santriOptions' => $this->skrining->santriOptions(),
        ]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/pengasuhan');
        $id   = (int) $id;
        $item = $this->pengasuhan->find($id);
        if (!$item) {
            Session::setFlash('error', 'Catatan tidak ditemukan.');
            $this->redirect('/pengasuhan');
        }

        $in = $this->gather();
        // santri tidak berubah saat edit
        $in['santri_id'] = (int) $item['santri_id'];

        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/pengasuhan/' . $id . '/edit');
        }

        $this->pengasuhan->update($id, $this->payload($in));
        log_activity('update', 'pengasuhan', 'Mengubah catatan ID ' . $id);
        Session::setFlash('success', 'Catatan berhasil diperbarui.');
        $this->redirect('/pengasuhan');
    }

    /** Tandai catatan selesai (tindak lanjut selesai). */
    public function selesai($id): void
    {
        $this->checkCsrf('/pengasuhan');
        $item = $this->pengasuhan->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Catatan tidak ditemukan.');
            $this->redirect('/pengasuhan');
        }
        if ($item['status'] !== 'berjalan') {
            Session::setFlash('error', 'Catatan ini sudah selesai.');
            $this->redirect('/pengasuhan');
        }

        $this->pengasuhan->update((int) $id, ['status' => 'selesai']);
        log_activity('update', 'pengasuhan', 'Menandai selesai catatan ID ' . $id);
        Session::setFlash('success', 'Catatan ditandai selesai.');
        $this->redirect('/pengasuhan');
    }

    public function destroy($id): void
    {
        $this->checkCsrf('/pengasuhan');
        $item = $this->pengasuhan->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Catatan tidak ditemukan.');
            $this->redirect('/pengasuhan');
        }

        $this->pengasuhan->delete((int) $id);
        log_activity('delete', 'pengasuhan', 'Menghapus catatan ID ' . $id);
        Session::setFlash('success', 'Catatan dihapus.');
        $this->redirect('/pengasuhan');
    }

    /* ================= Helper ================= */

    private function gather(): array
    {
        return [
            'santri_id'  => (int) ($this->input('santri_id') ?? 0),
            'jenis'      => $this->input('jenis') ?? '',
            'tanggal'    => $this->input('tanggal') ?? '',
            'keterangan' => $this->input('keterangan') ?? '',
            'tindakan'   => $this->input('tindakan') ?? '',
        ];
    }

    private function payload(array $in): array
    {
        return [
            'santri_id'  => $in['santri_id'],
            'jenis'      => $in['jenis'],
            'tanggal'    => $in['tanggal'],
            'keterangan' => $in['keterangan'],
            'tindakan'   => $in['tindakan'] !== '' ? $in['tindakan'] : null,
            'petugas_id' => Auth::id(),
        ];
    }

    /** @return string[] */
    private function validate(array $in): array
    {
        $e = [];
        if ($in['santri_id'] <= 0 || !$this->santri->find($in['santri_id'])) {
            $e[] = 'Santri wajib dipilih.';
        }
        if (!isset(Pengasuhan::JENIS[$in['jenis']])) {
            $e[] = 'Jenis wajib dipilih (Perizinan/Pelanggaran).';
        }
        if ($in['tanggal'] === '' || strtotime($in['tanggal']) === false) {
            $e[] = 'Tanggal tidak valid.';
        }
        if (mb_strlen($in['keterangan']) < 3) {
            $e[] = 'Keterangan minimal 3 karakter.';
        }
        return $e;
    }
}