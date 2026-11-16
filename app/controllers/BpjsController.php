<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Bpjs;
use App\Models\Santri;
use App\Models\SkriningKesehatan;

class BpjsController extends Controller
{
    private Bpjs $bpjs;
    private Santri $santri;
    private SkriningKesehatan $skrining;

    public function __construct()
    {
        $this->bpjs     = new Bpjs();
        $this->santri   = new Santri();
        $this->skrining = new SkriningKesehatan();
    }

    /* ================= Daftar ================= */
    public function index(): void
    {
        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'status' => isset(Bpjs::STATUS_LABEL[$_GET['status'] ?? '']) ? $_GET['status'] : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('bpjs/index', [
            'title'   => 'Asuransi BPJS Santri',
            'user'    => Auth::user(),
            'data'    => $this->bpjs->paginate($page, 10, $filters),
            'filters' => $filters,
        ]);
    }

    /* ================= Tambah ================= */
    public function create(): void
    {
        $this->view('bpjs/form', [
            'title'         => 'Tambah Data BPJS',
            'user'          => Auth::user(),
            'item'          => null,
            'santriOptions' => $this->skrining->santriOptions(),
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/bpjs/create');
        $in = $this->gather();

        if ($errors = $this->validate($in, null)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/bpjs/create');
        }

        $this->bpjs->create([
            'santri_id'         => $in['santri_id'],
            'nomor_bpjs'        => $in['nomor_bpjs'],
            'status'            => $in['status'],
            'faskes_tk1'        => $in['faskes_tk1'],
            'catatan'           => $in['catatan'] !== '' ? $in['catatan'] : null,
            'tanggal_pembaruan' => $in['tanggal_pembaruan'],
            'petugas_id'        => Auth::id(),
        ]);

        $santri = $this->santri->find($in['santri_id']);
        log_activity('create', 'asuransi', "Data BPJS {$in['nomor_bpjs']} untuk {$santri['nama']}");
        Session::setFlash('success', "Data BPJS untuk {$santri['nama']} tersimpan.");
        $this->redirect('/bpjs');
    }

    /* ================= Edit ================= */
    public function edit($id): void
    {
        $item = $this->bpjs->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data BPJS tidak ditemukan.');
            $this->redirect('/bpjs');
        }

        $this->view('bpjs/form', [
            'title'         => 'Edit Data BPJS',
            'user'          => Auth::user(),
            'item'          => $item,
            'santriOptions' => [],
        ]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/bpjs');
        $id   = (int) $id;
        $item = $this->bpjs->find($id);
        if (!$item) {
            Session::setFlash('error', 'Data BPJS tidak ditemukan.');
            $this->redirect('/bpjs');
        }

        $in = $this->gather();
        // Santri tidak berubah saat edit
        $in['santri_id'] = (int) $item['santri_id'];

        if ($errors = $this->validate($in, $id)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect("/bpjs/{$id}/edit");
        }

        $this->bpjs->update($id, [
            'nomor_bpjs'        => $in['nomor_bpjs'],
            'status'            => $in['status'],
            'faskes_tk1'        => $in['faskes_tk1'],
            'catatan'           => $in['catatan'] !== '' ? $in['catatan'] : null,
            'tanggal_pembaruan' => $in['tanggal_pembaruan'],
            'petugas_id'        => Auth::id(),
        ]);

        log_activity('update', 'asuransi', "Memperbarui BPJS {$in['nomor_bpjs']} (ID {$id})");
        Session::setFlash('success', 'Data BPJS berhasil diperbarui.');
        $this->redirect('/bpjs');
    }

    /* ================= Hapus ================= */
    public function destroy($id): void
    {
        $this->checkCsrf('/bpjs');
        $item = $this->bpjs->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data BPJS tidak ditemukan.');
            $this->redirect('/bpjs');
        }

        $this->bpjs->delete((int) $id);
        log_activity('delete', 'asuransi', "Menghapus BPJS {$item['nomor_bpjs']} (ID {$id})");
        Session::setFlash('success', 'Data BPJS dihapus.');
        $this->redirect('/bpjs');
    }

    /* ================= Helper ================= */

    private function gather(): array
    {
        return [
            'santri_id'         => (int) ($this->input('santri_id') ?? 0),
            'nomor_bpjs'        => preg_replace('/\D/', '', $this->input('nomor_bpjs') ?? ''),
            'status'            => $this->input('status') ?? 'aktif',
            'faskes_tk1'        => $this->input('faskes_tk1') ?? '',
            'catatan'           => $this->input('catatan') ?? '',
            'tanggal_pembaruan' => $this->input('tanggal_pembaruan') ?? '',
        ];
    }

    /** @return string[] */
    private function validate(array $in, ?int $exceptId): array
    {
        $e = [];

        if ($in['santri_id'] <= 0 || !$this->santri->find($in['santri_id'])) {
            $e[] = 'Santri wajib dipilih.';
        } elseif ($exceptId === null && $this->bpjs->findForSantri($in['santri_id'])) {
            $e[] = 'Santri tersebut sudah memiliki data BPJS. Gunakan menu Edit.';
        }

        if (!preg_match('/^[0-9]{10,20}$/', $in['nomor_bpjs'])) {
            $e[] = 'Nomor BPJS harus 10-20 digit angka (umumnya 13 digit).';
        } elseif ($this->bpjs->nomorExists($in['nomor_bpjs'], $exceptId)) {
            $e[] = 'Nomor BPJS sudah terdaftar untuk santri lain.';
        }

        if (!isset(Bpjs::STATUS_LABEL[$in['status']])) {
            $e[] = 'Status BPJS tidak valid.';
        }
        if (mb_strlen($in['faskes_tk1']) < 3) {
            $e[] = 'Faskes Tingkat 1 wajib diisi (min. 3 karakter).';
        }

        $ts = strtotime($in['tanggal_pembaruan']);
        if ($ts === false) {
            $e[] = 'Tanggal pembaruan tidak valid.';
        } elseif ($ts > strtotime(date('Y-m-d'))) {
            $e[] = 'Tanggal pembaruan tidak boleh di masa depan.';
        }

        if (mb_strlen($in['catatan']) > 255) {
            $e[] = 'Catatan maksimal 255 karakter.';
        }
        return $e;
    }
}