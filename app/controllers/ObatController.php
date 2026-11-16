<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Obat;
use App\Models\ObatTransaksi;
use App\Models\Pemeriksaan;
use RuntimeException;

class ObatController extends Controller
{
    private Obat $obat;
    private ObatTransaksi $transaksi;

    public function __construct()
    {
        $this->obat      = new Obat();
        $this->transaksi = new ObatTransaksi();
    }

    /* ================= Dashboard + Master Obat ================= */
    public function index(): void
    {
        $filters = [
            'q'        => trim($_GET['q'] ?? ''),
            'kategori' => isset(Obat::KATEGORI[$_GET['kategori'] ?? '']) ? $_GET['kategori'] : '',
            'flag'     => in_array($_GET['flag'] ?? '', ['menipis', 'kedaluwarsa', 'mendekati', 'aman'], true) ? $_GET['flag'] : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('obat/index', [
            'title'   => 'Manajemen Obat',
            'user'    => Auth::user(),
            'data'    => $this->obat->paginate($page, 10, $filters),
            'filters' => $filters,
            'stats'   => [
                'total'      => $this->obat->count(),
                'menipis'    => $this->obat->countLowStock(),
                'mendekati'  => $this->obat->countNearExpiry(),
                'kadaluarsa' => $this->obat->countExpired(),
                'nilai'      => $this->obat->nilaiPersediaan(),
                'biayaBulan' => $this->transaksi->biayaBulanIni(),
            ],
        ]);
    }

    /* ================= Master: Tambah / Edit ================= */
    public function create(): void
    {
        $this->view('obat/form', ['title' => 'Tambah Obat', 'user' => Auth::user(), 'item' => null]);
    }

    public function store(): void
    {
        $this->checkCsrf('/obat/create');
        $in = $this->gather();

        if ($errors = $this->validate($in, null)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/obat/create');
        }

        $this->obat->create($this->payload($in));
        log_activity('create', 'obat', 'Menambah obat: ' . $in['nama']);
        Session::setFlash('success', 'Obat ' . $in['nama'] . ' tersimpan.');
        $this->redirect('/obat');
    }

    public function edit($id): void
    {
        $item = $this->obat->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Obat tidak ditemukan.');
            $this->redirect('/obat');
        }

        $this->view('obat/form', ['title' => 'Edit Obat', 'user' => Auth::user(), 'item' => $item]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/obat');
        $id   = (int) $id;
        $item = $this->obat->find($id);
        if (!$item) {
            Session::setFlash('error', 'Obat tidak ditemukan.');
            $this->redirect('/obat');
        }

        $in = $this->gather();
        if ($errors = $this->validate($in, $id)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/obat/' . $id . '/edit');
        }

        // stok TIDAK diubah lewat form master — hanya lewat transaksi masuk/keluar
        $this->obat->update($id, [
            'nama'                => $in['nama'],
            'kategori'            => $in['kategori'],
            'satuan'              => $in['satuan'],
            'minimum_stok'        => $in['minimum_stok'],
            'harga'               => $in['harga'],
            'tanggal_kedaluwarsa' => $in['tanggal_kedaluwarsa'],
            'catatan'             => $in['catatan'] !== '' ? $in['catatan'] : null,
        ]);
        log_activity('update', 'obat', 'Mengubah master obat: ' . $in['nama'] . ' (ID ' . $id . ')');
        Session::setFlash('success', 'Data obat berhasil diperbarui.');
        $this->redirect('/obat');
    }

    /* ================= Hapus (FIX v16.1) ================= */
    public function destroy($id): void
    {
        $this->checkCsrf('/obat');
        $id   = (int) $id;
        $item = $this->obat->find($id);
        if (!$item) {
            Session::setFlash('error', 'Obat tidak ditemukan.');
            $this->redirect('/obat');
        }

        // FIX v16.1: via method publik model — TANPA akses $db dari controller
        if ($this->obat->hasTransactions($id)) {
            Session::setFlash('error', 'Obat memiliki riwayat transaksi dan tidak dapat dihapus (integritas histori).');
            $this->redirect('/obat');
        }

        $this->obat->delete($id);
        log_activity('delete', 'obat', 'Menghapus obat: ' . $item['nama'] . ' (ID ' . $id . ')');
        Session::setFlash('success', 'Obat dihapus.');
        $this->redirect('/obat');
    }

    /* ================= Stok Masuk ================= */
    public function masukForm(): void
    {
        $this->view('obat/form_masuk', [
            'title'    => 'Catat Stok Masuk',
            'user'     => Auth::user(),
            'obatList' => $this->obat->options(),
        ]);
    }

    public function masukStore(): void
    {
        $this->checkCsrf('/obat/masuk');
        $in = $this->gatherTransaksi();

        if ($errors = $this->validateTransaksi($in, 'masuk')) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/obat/masuk');
        }

        try {
            $this->transaksi->masukStok(
                $in['obat_id'],
                $in['jumlah'],
                $in['harga_satuan'],
                $in['keterangan'],
                Auth::id()
            );
        } catch (RuntimeException $e) {
            Session::setFlash('error', e($e->getMessage()));
            $this->redirect('/obat/masuk');
        }

        $obat = $this->obat->find($in['obat_id']);
        log_activity('create', 'obat', 'Stok masuk ' . $in['jumlah'] . ' ' . $obat['satuan'] . ' — ' . $obat['nama']);
        Session::setFlash('success', 'Stok masuk tercatat untuk ' . $obat['nama'] . '.');
        $this->redirect('/obat');
    }

    /* ================= Pemakaian (Stok Keluar) ================= */
    public function pemakaianForm(): void
    {
        $pemeriksaanModel = new Pemeriksaan();
        $this->view('obat/form_pemakaian', [
            'title'              => 'Catat Pemakaian Obat',
            'user'               => Auth::user(),
            'obatList'           => $this->obat->options(),
            'pemeriksaanOptions' => $pemeriksaanModel->paginate(1, 200, ['status' => ''])['rows'],
        ]);
    }

    public function pemakaianStore(): void
    {
        $this->checkCsrf('/obat/pemakaian');
        $in = $this->gatherTransaksi();

        if ($errors = $this->validateTransaksi($in, 'keluar')) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/obat/pemakaian');
        }

        try {
            $this->transaksi->keluarStok(
                $in['obat_id'],
                $in['jumlah'],
                $in['pemeriksaan_id'],
                $in['keterangan'],
                Auth::id()
            );
        } catch (RuntimeException $e) {
            Session::setFlash('error', e($e->getMessage()));
            $this->redirect('/obat/pemakaian');
        }

        $obat = $this->obat->find($in['obat_id']);
        log_activity('create', 'obat', 'Pemakaian ' . $in['jumlah'] . ' ' . $obat['satuan'] . ' — ' . $obat['nama']);
        Session::setFlash('success', 'Pemakaian ' . $obat['nama'] . ' tercatat; stok diperbarui.');
        $this->redirect('/obat');
    }

    /* ================= Histori Transaksi ================= */
    public function transaksi(): void
    {
        $filters = [
            'q'         => trim($_GET['q'] ?? ''),
            'jenis'     => isset(ObatTransaksi::JENIS_LABEL[$_GET['jenis'] ?? '']) ? $_GET['jenis'] : '',
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('obat/transaksi', [
            'title'   => 'Histori Transaksi Obat',
            'user'    => Auth::user(),
            'data'    => $this->transaksi->paginate($page, 15, $filters),
            'filters' => $filters,
        ]);
    }

    /* ================= Laporan Pemakaian ================= */
    public function laporan(): void
    {
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to'] ?? '');
        if ($dateFrom === '' || strtotime($dateFrom) === false) {
            $dateFrom = date('Y-m-01');
        }
        if ($dateTo === '' || strtotime($dateTo) === false) {
            $dateTo = date('Y-m-d');
        }
        if (strtotime($dateTo) < strtotime($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $this->view('obat/laporan', [
            'title'    => 'Laporan Pemakaian Obat',
            'user'     => Auth::user(),
            'laporan'  => $this->transaksi->laporanPemakaian($dateFrom, $dateTo),
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }

    /* ================= Helper ================= */

    private function gather(): array
    {
        return [
            'nama'                => $this->input('nama') ?? '',
            'kategori'            => $this->input('kategori') ?? '',
            'satuan'              => $this->input('satuan') ?? '',
            'minimum_stok'        => (int) ($this->input('minimum_stok') ?? 0),
            'harga'               => (float) ($this->input('harga') ?? 0),
            'tanggal_kedaluwarsa' => $this->input('tanggal_kedaluwarsa') ?? '',
            'catatan'             => $this->input('catatan') ?? '',
        ];
    }

    private function payload(array $in): array
    {
        return [
            'nama'                => $in['nama'],
            'kategori'            => $in['kategori'],
            'satuan'              => $in['satuan'],
            'stok'                => 0, // stok awal 0; diisi lewat Stok Masuk
            'minimum_stok'        => $in['minimum_stok'],
            'harga'               => $in['harga'],
            'tanggal_kedaluwarsa' => $in['tanggal_kedaluwarsa'],
            'catatan'             => $in['catatan'] !== '' ? $in['catatan'] : null,
        ];
    }

    /** @return string[] */
    private function validate(array $in, ?int $exceptId): array
    {
        $e = [];
        if (mb_strlen($in['nama']) < 3) {
            $e[] = 'Nama obat minimal 3 karakter.';
        } elseif ($this->obat->namaExists($in['nama'], $exceptId)) {
            $e[] = 'Nama obat sudah terdaftar.';
        }
        if (!isset(Obat::KATEGORI[$in['kategori']])) {
            $e[] = 'Kategori obat wajib dipilih.';
        }
        if (!isset(Obat::SATUAN[$in['satuan']])) {
            $e[] = 'Satuan wajib dipilih.';
        }
        if ($in['minimum_stok'] < 0 || $in['minimum_stok'] > 100000) {
            $e[] = 'Batas minimum stok tidak valid.';
        }
        if ($in['harga'] < 0 || $in['harga'] > 100000000) {
            $e[] = 'Harga tidak valid.';
        }
        $ts = strtotime($in['tanggal_kedaluwarsa']);
        if ($ts === false) {
            $e[] = 'Tanggal kedaluwarsa tidak valid.';
        }
        if (mb_strlen($in['catatan']) > 255) {
            $e[] = 'Catatan maksimal 255 karakter.';
        }
        return $e;
    }

    private function gatherTransaksi(): array
    {
        return [
            'obat_id'        => (int) ($this->input('obat_id') ?? 0),
            'jumlah'         => (int) ($this->input('jumlah') ?? 0),
            'harga_satuan'   => (float) ($this->input('harga_satuan') ?? 0),
            'pemeriksaan_id' => (int) ($this->input('pemeriksaan_id') ?? 0),
            'keterangan'     => $this->input('keterangan') ?? '',
        ];
    }

    /** @return string[] */
    private function validateTransaksi(array $in, string $jenis): array
    {
        $e = [];
        $obat = $in['obat_id'] > 0 ? $this->obat->find($in['obat_id']) : null;
        if (!$obat) {
            $e[] = 'Obat wajib dipilih.';
        }
        if ($in['jumlah'] < 1 || $in['jumlah'] > 100000) {
            $e[] = 'Jumlah harus antara 1-100000.';
        }
        if ($jenis === 'masuk' && ($in['harga_satuan'] < 0 || $in['harga_satuan'] > 100000000)) {
            $e[] = 'Harga satuan tidak valid.';
        }
        if ($jenis === 'keluar' && $in['pemeriksaan_id'] > 0) {
            $pemeriksaanModel = new Pemeriksaan();
            $p = $pemeriksaanModel->find($in['pemeriksaan_id']);
            if (!$p) {
                $e[] = 'Pemeriksaan terkait tidak ditemukan.';
            }
        }
        if (mb_strlen($in['keterangan']) > 255) {
            $e[] = 'Keterangan maksimal 255 karakter.';
        }
        return $e;
    }
}