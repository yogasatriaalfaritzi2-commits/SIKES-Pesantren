<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\InspeksiKamar;
use App\Models\InspeksiRiwayat;
use App\Models\Asrama;
use App\Models\Blok;
use App\Models\Kamar;
use App\Models\KasusMenular;

class SanitasiController extends Controller
{
    private InspeksiKamar $inspeksi;
    private InspeksiRiwayat $riwayat;
    private Asrama $asrama;
    private Blok $blok;
    private Kamar $kamar;

    public function __construct()
    {
        $this->inspeksi = new InspeksiKamar();
        $this->riwayat  = new InspeksiRiwayat();
        $this->asrama   = new Asrama();
        $this->blok     = new Blok();
        $this->kamar    = new Kamar();
    }

    /* ================= Dashboard Kondisi Kamar ================= */
    public function dashboard(): void
    {
        $filters = [
            'asrama' => (int) ($_GET['asrama'] ?? 0),
            'blok'   => (int) ($_GET['blok'] ?? 0),
        ];

        $this->view('sanitasi/dashboard', [
            'title'         => 'Sanitasi & Lingkungan',
            'user'          => Auth::user(),
            'data'          => $this->inspeksi->kamarCondition($filters),
            'filters'       => $filters,
            'asramaOptions' => $this->asrama->options(),
            'blokList'      => $this->blok->allWithAsrama(),
            'kasusAktif'    => (new KasusMenular())->roomsWithActiveCases(),
        ]);
    }

    /* ================= Histori Inspeksi ================= */
    public function histori(): void
    {
        $filters = [
            'q'         => trim($_GET['q'] ?? ''),
            'asrama'    => (int) ($_GET['asrama'] ?? 0),
            'blok'      => (int) ($_GET['blok'] ?? 0),
            'status'    => in_array($_GET['status'] ?? '', ['baik', 'perlu_perhatian', 'risiko_tinggi'], true) ? $_GET['status'] : '',
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('sanitasi/histori', [
            'title'         => 'Histori Inspeksi Sanitasi',
            'user'          => Auth::user(),
            'data'          => $this->inspeksi->paginate($page, 10, $filters),
            'filters'       => $filters,
            'asramaOptions' => $this->asrama->options(),
            'blokList'      => $this->blok->allWithAsrama(),
        ]);
    }

    /* ================= Form Inspeksi (buat) ================= */
    public function create(): void
    {
        $this->view('sanitasi/form', [
            'title'      => 'Input Inspeksi Kamar',
            'user'       => Auth::user(),
            'item'       => null,
            'kamarList'  => $this->inspeksi->kamarOptions(),
            'preKamarId' => (int) ($_GET['kamar_id'] ?? 0),
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/sanitasi/inspeksi/create');
        $in = $this->gather();

        $errors = $this->validate($in, null);
        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/sanitasi/inspeksi/create' . ($in['kamar_id'] > 0 ? '?kamar_id=' . $in['kamar_id'] : ''));
        }

        $hasil   = InspeksiKamar::hitung($in);
        $payload = $this->payload($in, $hasil);
        $id = $this->inspeksi->create($payload);

        $this->writeHistory($id, 'buat', $payload,
            'Inspeksi kamar dibuat. Skor ' . $hasil['skor'] . '/' . $hasil['maks']
            . ' (' . $hasil['persentase'] . '%) - ' . InspeksiKamar::STATUS_LABEL[$hasil['status']]);

        $kamar = $this->kamar->find($in['kamar_id']);
        log_activity('create', 'sanitasi', 'Inspeksi kamar ' . $kamar['nama'] . ': skor '
            . $hasil['skor'] . '/' . $hasil['maks'] . ' (' . $hasil['status'] . ')');
        Session::setFlash('success', 'Inspeksi berhasil disimpan. Status: '
            . InspeksiKamar::STATUS_LABEL[$hasil['status']] . '.');
        $this->redirect('/sanitasi/inspeksi/' . $id);
    }

    /* ================= Detail Inspeksi ================= */
    public function show($id): void
    {
        $item = $this->inspeksi->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data inspeksi tidak ditemukan.');
            $this->redirect('/sanitasi/inspeksi');
        }

        $this->view('sanitasi/show', [
            'title'       => 'Detail Inspeksi Kamar',
            'user'        => Auth::user(),
            'item'        => $item,
            'riwayatList' => $this->riwayat->forInspeksi((int) $id),
        ]);
    }

    /* ================= Edit Inspeksi ================= */
    public function edit($id): void
    {
        $item = $this->inspeksi->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data inspeksi tidak ditemukan.');
            $this->redirect('/sanitasi/inspeksi');
        }

        $this->view('sanitasi/form', [
            'title'      => 'Edit Inspeksi Kamar',
            'user'       => Auth::user(),
            'item'       => $item,
            'kamarList'  => $this->inspeksi->kamarOptions(),
            'preKamarId' => 0,
        ]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/sanitasi/inspeksi');
        $id  = (int) $id;
        $old = $this->inspeksi->find($id);
        if (!$old) {
            Session::setFlash('error', 'Data inspeksi tidak ditemukan.');
            $this->redirect('/sanitasi/inspeksi');
        }

        $in = $this->gather();
        $errors = $this->validate($in, $id);
        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/sanitasi/inspeksi/' . $id . '/edit');
        }

        $hasil   = InspeksiKamar::hitung($in);
        $payload = $this->payload($in, $hasil);
        $this->inspeksi->update($id, $payload);

        // ============================================================
        // FIX (parse error baris 172): tanda +/- dihitung ke variabel
        // terpisah, lalu semua penggabungan memakai konkatenasi '.' —
        // tidak ada lagi ekspresi ternary di dalam interpolasi string.
        // ============================================================
        $delta     = (int) $hasil['skor'] - (int) $old['skor'];
        $deltaSign = $delta >= 0 ? '+' : '';

        $ringkasan = 'Inspeksi diperbarui. Skor ' . $old['skor'] . ' -> '
                   . $hasil['skor'] . '/' . $hasil['maks']
                   . ' (' . $hasil['persentase'] . '%) - '
                   . InspeksiKamar::STATUS_LABEL[$hasil['status']];
        $this->writeHistory($id, 'ubah', $payload, $ringkasan);

        log_activity('update', 'sanitasi', 'Mengubah inspeksi ID ' . $id
            . ': skor ' . $deltaSign . $delta . ' -> ' . $hasil['skor'] . '/' . $hasil['maks']);
        Session::setFlash('success', 'Inspeksi berhasil diperbarui. Status: '
            . InspeksiKamar::STATUS_LABEL[$hasil['status']] . '.');
        $this->redirect('/sanitasi/inspeksi/' . $id);
    }

    /* ================= Hapus ================= */
    public function destroy($id): void
    {
        $this->checkCsrf('/sanitasi/inspeksi');
        $item = $this->inspeksi->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data inspeksi tidak ditemukan.');
            $this->redirect('/sanitasi/inspeksi');
        }

        log_activity('delete', 'sanitasi', 'Menghapus inspeksi kamar ID ' . $id
            . ' (tanggal ' . $item['tanggal_inspeksi'] . ')');
        $this->inspeksi->delete((int) $id);
        Session::setFlash('success', 'Data inspeksi berhasil dihapus.');
        $this->redirect('/sanitasi/inspeksi');
    }

    /* ================= Helper ================= */

    private function gather(): array
    {
        $in = [
            'kamar_id'         => (int) ($this->input('kamar_id') ?? 0),
            'tanggal_inspeksi' => $this->input('tanggal_inspeksi') ?? '',
            'catatan_petugas'  => $this->input('catatan_petugas') ?? '',
        ];
        foreach (array_keys(InspeksiKamar::PARAMS) as $f) {
            $in[$f] = $this->input($f) ?? '';
        }
        return $in;
    }

    private function payload(array $in, array $hasil): array
    {
        $p = [
            'kamar_id'         => $in['kamar_id'],
            'tanggal_inspeksi' => $in['tanggal_inspeksi'],
            'skor'             => $hasil['skor'],
            'persentase'       => $hasil['persentase'],
            'status'           => $hasil['status'],
            'catatan_petugas'  => $in['catatan_petugas'] !== '' ? $in['catatan_petugas'] : null,
            'petugas_id'       => Auth::id(),
        ];
        foreach (array_keys(InspeksiKamar::PARAMS) as $f) {
            $p[$f] = $in[$f];
        }
        return $p;
    }

    /** @return string[] */
    private function validate(array $in, ?int $exceptId): array
    {
        $e = [];

        if ($in['kamar_id'] <= 0) {
            $e[] = 'Kamar wajib dipilih.';
        } elseif (!$this->kamar->find($in['kamar_id'])) {
            $e[] = 'Kamar tidak ditemukan.';
        }

        $ts = strtotime($in['tanggal_inspeksi']);
        if ($in['tanggal_inspeksi'] === '' || $ts === false) {
            $e[] = 'Tanggal inspeksi tidak valid.';
        } elseif ($ts > strtotime(date('Y-m-d'))) {
            $e[] = 'Tanggal inspeksi tidak boleh di masa depan.';
        } elseif ($in['kamar_id'] > 0 && $this->inspeksi->existsFor($in['kamar_id'], $in['tanggal_inspeksi'], $exceptId)) {
            $e[] = 'Kamar tersebut sudah diinspeksi pada tanggal yang sama. Gunakan menu Edit.';
        }

        foreach (InspeksiKamar::PARAMS as $f => $label) {
            if (!in_array($in[$f], ['baik', 'cukup', 'buruk'], true)) {
                $e[] = 'Nilai parameter "' . $label . '" tidak valid.';
            }
        }

        if (mb_strlen($in['catatan_petugas']) > 1000) {
            $e[] = 'Catatan petugas maksimal 1000 karakter.';
        }
        return $e;
    }

    private function writeHistory(int $inspeksiId, string $aksi, array $payload, string $ringkasan): void
    {
        try {
            $snap = $payload;
            $snap['ringkasan'] = $ringkasan;
            $this->riwayat->create([
                'inspeksi_id'  => $inspeksiId,
                'aksi'         => $aksi,
                'data_sesudah' => json_encode($snap, JSON_UNESCAPED_UNICODE),
                'user_id'      => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            // riwayat pelengkap — jangan ganggu proses utama
        }
    }
}