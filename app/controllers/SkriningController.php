<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Santri;
use App\Models\Asrama;
use App\Models\SkriningKesehatan;
use App\Models\SkriningRiwayat;

class SkriningController extends Controller
{
    private Santri $santri;
    private Asrama $asrama;
    private SkriningKesehatan $skrining;
    private SkriningRiwayat $riwayat;

    public function __construct()
    {
        $this->santri   = new Santri();
        $this->asrama   = new Asrama();
        $this->skrining = new SkriningKesehatan();
        $this->riwayat  = new SkriningRiwayat();
    }

    /* ================= 1. Daftar Skrining ================= */
    public function index(): void
    {
        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'jk'     => in_array($_GET['jk'] ?? '', ['L', 'P'], true) ? $_GET['jk'] : '',
            'asrama' => (int) ($_GET['asrama'] ?? 0),
            'status' => in_array($_GET['status'] ?? '', ['sudah', 'belum'], true) ? $_GET['status'] : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('skrining/index', [
            'title'         => 'Skrining Kesehatan Awal',
            'user'          => Auth::user(),
            'data'          => $this->skrining->paginate($page, 10, $filters),
            'filters'       => $filters,
            'asramaOptions' => $this->asrama->options(),
        ]);
    }

    /* ================= 2. Input Skrining ================= */
    public function create(): void
    {
        $selectedSantriId = (int) ($_GET['santri_id'] ?? 0);

        if ($selectedSantriId > 0) {
            $existing = $this->skrining->findBySantri($selectedSantriId);
            if ($existing) {
                Session::setFlash('warning', 'Santri tersebut sudah memiliki data skrining. Berikut detailnya.');
                $this->redirect('/skrining/' . $existing['id']);
            }
        }

        $this->view('skrining/form', [
            'title'            => 'Input Skrining Kesehatan',
            'user'             => Auth::user(),
            'item'             => null,
            'santriOptions'    => $this->skrining->santriOptions(),
            'selectedSantriId' => $selectedSantriId,
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/skrining/create');
        $in = $this->gather();

        $errors = $this->validate($in, false);
        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/skrining/create' . ($in['santri_id'] > 0 ? '?santri_id=' . $in['santri_id'] : ''));
        }

        $payload = $this->payload($in);
        $id      = $this->skrining->create($payload);

        $this->writeHistory($id, 'buat', $payload, 'Skrining awal dibuat');

        log_activity('create', 'skrining', "Menambah skrining kesehatan untuk santri ID {$in['santri_id']}");
        Session::setFlash('success', 'Data skrining berhasil disimpan.');
        $this->redirect('/skrining/' . $id);
    }

    /* ================= 3. Detail Profil Kesehatan ================= */
    public function show($id): void
    {
        $item = $this->skrining->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data skrining tidak ditemukan.');
            $this->redirect('/skrining');
        }

        $this->view('skrining/show', [
            'title'       => 'Profil Kesehatan Santri',
            'user'        => Auth::user(),
            'item'        => $item,
            'warnings'    => SkriningKesehatan::warningsFrom($item),
            'riwayatList' => $this->riwayat->forSkrining((int) $id),
        ]);
    }

    /* ================= 4. Edit Hasil Skrining ================= */
    public function edit($id): void
    {
        $item = $this->skrining->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data skrining tidak ditemukan.');
            $this->redirect('/skrining');
        }

        $this->view('skrining/form', [
            'title'            => 'Edit Hasil Skrining',
            'user'             => Auth::user(),
            'item'             => $item,
            'santriOptions'    => [],
            'selectedSantriId' => 0,
        ]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/skrining');
        $id  = (int) $id;
        $old = $this->skrining->find($id);
        if (!$old) {
            Session::setFlash('error', 'Data skrining tidak ditemukan.');
            $this->redirect('/skrining');
        }

        $in = $this->gather();
        $errors = $this->validate($in, true);
        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect("/skrining/{$id}/edit");
        }

        $payload = $this->payload($in);
        $payload['santri_id'] = (int) $old['santri_id']; // santri tidak boleh diganti saat edit
        $this->skrining->update($id, $payload);

        $this->writeHistory($id, 'ubah', $payload, $this->diffRingkasan($old, $payload));

        log_activity('update', 'skrining', "Mengubah skrining kesehatan ID {$id}");
        Session::setFlash('success', 'Hasil skrining berhasil diperbarui.');
        $this->redirect('/skrining/' . $id);
    }

    public function destroy($id): void
    {
        $this->checkCsrf('/skrining');
        $item = $this->skrining->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data skrining tidak ditemukan.');
            $this->redirect('/skrining');
        }

        $this->skrining->delete((int) $id);
        log_activity('delete', 'skrining', "Menghapus skrining kesehatan ID {$id}");
        Session::setFlash('success', 'Data skrining berhasil dihapus.');
        $this->redirect('/skrining');
    }

    /* ================= 5. Riwayat Pemeriksaan ================= */
    public function riwayat(): void
    {
        $filters = [
            'santri_id' => (int) ($_GET['santri_id'] ?? 0),
            'q'         => trim($_GET['q'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('skrining/riwayat', [
            'title'         => 'Riwayat Pemeriksaan Skrining',
            'user'          => Auth::user(),
            'data'          => $this->riwayat->paginate($page, 15, $filters),
            'filters'       => $filters,
            'santriOptions' => $this->skrining->santriOptions(),
        ]);
    }

    /* ================= Helper ================= */

    private function gather(): array
    {
        $g = fn (string $k): string => $this->input($k) ?? '';

        $in = [
            'santri_id'        => (int) ($this->input('santri_id') ?? 0),
            'tanggal_skrining' => $g('tanggal_skrining'),
            'tinggi_cm'        => $g('tinggi_cm'),
            'berat_kg'         => $g('berat_kg'),
            'golongan_darah'   => $g('golongan_darah') ?: '-',
            'rhesus'           => $g('rhesus') ?: '-',
            'buta_warna'       => $g('buta_warna') ?: 'belum',
            'buta_warna_ket'   => $g('buta_warna_ket'),
            'gigi_status'      => $g('gigi_status') ?: 'belum',
            'gigi_ket'         => $g('gigi_ket'),
            'fisik_status'     => $g('fisik_status') ?: 'belum',
            'fisik_ket'        => $g('fisik_ket'),
            'catatan_medis'    => $g('catatan_medis'),
        ];
        foreach (array_keys(SkriningKesehatan::KONDISI) as $field) {
            $in[$field]          = $g($field) ?: 'tidak';
            $in[$field . '_ket'] = $g($field . '_ket');
        }
        return $in;
    }

    /** Konversi input -> kolom DB; IMT dihitung di sini (satu sumber kebenaran). */
    private function payload(array $in): array
    {
        $imt = SkriningKesehatan::hitungImt(
            $in['berat_kg'] !== '' ? (float) $in['berat_kg'] : null,
            $in['tinggi_cm'] !== '' ? (float) $in['tinggi_cm'] : null
        );

        $p = [
            'santri_id'        => $in['santri_id'],
            'tanggal_skrining' => $in['tanggal_skrining'],
        ];

        foreach (SkriningKesehatan::KONDISI as $field => $label) {
            $ya = ($in[$field] ?? 'tidak') === 'ya';
            $p[$field]          = $ya ? 'ya' : 'tidak';
            $p[$field . '_ket'] = $ya ? ($in[$field . '_ket'] !== '' ? $in[$field . '_ket'] : null) : null;
        }

        $p['tinggi_cm']       = $in['tinggi_cm'] !== '' ? $in['tinggi_cm'] : null;
        $p['berat_kg']        = $in['berat_kg'] !== '' ? $in['berat_kg'] : null;
        $p['imt']             = $imt['imt'] ?? null;
        $p['kategori_imt']    = $imt['kategori'] ?? null;
        $p['golongan_darah']  = $in['golongan_darah'];
        $p['rhesus']          = $in['rhesus'];
        $p['buta_warna']      = $in['buta_warna'];
        $p['buta_warna_ket']  = $in['buta_warna_ket'] !== '' ? $in['buta_warna_ket'] : null;
        $p['gigi_status']     = $in['gigi_status'];
        $p['gigi_ket']        = $in['gigi_ket'] !== '' ? $in['gigi_ket'] : null;
        $p['fisik_status']    = $in['fisik_status'];
        $p['fisik_ket']       = $in['fisik_ket'] !== '' ? $in['fisik_ket'] : null;
        $p['catatan_medis']   = $in['catatan_medis'] !== '' ? $in['catatan_medis'] : null;
        $p['petugas_id']      = Auth::id();

        return $p;
    }

    /** @return string[] */
    private function validate(array $in, bool $isEdit): array
    {
        $e = [];

        if (!$isEdit) {
            if ($in['santri_id'] <= 0) {
                $e[] = 'Santri wajib dipilih.';
            } elseif (!$this->santri->find($in['santri_id'])) {
                $e[] = 'Santri tidak ditemukan.';
            } elseif ($this->skrining->findBySantri($in['santri_id'])) {
                $e[] = 'Santri tersebut sudah memiliki data skrining. Gunakan menu Edit.';
            }
        }

        if ($in['tanggal_skrining'] === '' || !strtotime($in['tanggal_skrining'])) {
            $e[] = 'Tanggal skrining tidak valid.';
        } elseif (strtotime($in['tanggal_skrining']) > time()) {
            $e[] = 'Tanggal skrining tidak boleh di masa depan.';
        }

        foreach (SkriningKesehatan::KONDISI as $field => $label) {
            $val = $in[$field] ?? '';
            if (!in_array($val, ['tidak', 'ya'], true)) {
                $e[] = "Nilai \"{$label}\" tidak valid.";
                continue;
            }
            if ($val === 'ya' && mb_strlen(trim($in[$field . '_ket'] ?? '')) < 3) {
                $e[] = "Keterangan \"{$label}\" wajib diisi (min. 3 karakter) karena berstatus Ya.";
            }
        }

        if ($in['tinggi_cm'] !== '') {
            if (!is_numeric($in['tinggi_cm']) || (float) $in['tinggi_cm'] < 30 || (float) $in['tinggi_cm'] > 250) {
                $e[] = 'Tinggi badan harus antara 30-250 cm.';
            }
        }
        if ($in['berat_kg'] !== '') {
            if (!is_numeric($in['berat_kg']) || (float) $in['berat_kg'] < 2 || (float) $in['berat_kg'] > 300) {
                $e[] = 'Berat badan harus antara 2-300 kg.';
            }
        }
        if (($in['tinggi_cm'] === '') !== ($in['berat_kg'] === '')) {
            $e[] = 'Tinggi dan berat badan harus diisi keduanya, atau keduanya dikosongkan.';
        }

        if (!in_array($in['golongan_darah'], ['A', 'B', 'AB', 'O', '-'], true)) {
            $e[] = 'Golongan darah tidak valid.';
        }
        if (!in_array($in['rhesus'], ['positif', 'negatif', '-'], true)) {
            $e[] = 'Rhesus tidak valid.';
        }

        if (!in_array($in['buta_warna'], ['belum', 'normal', 'kelainan'], true)) {
            $e[] = 'Hasil pemeriksaan buta warna tidak valid.';
        } elseif ($in['buta_warna'] === 'kelainan' && mb_strlen(trim($in['buta_warna_ket'] ?? '')) < 3) {
            $e[] = 'Keterangan kelainan buta warna wajib diisi.';
        }

        if (!in_array($in['gigi_status'], ['belum', 'baik', 'perlu_perawatan', 'perlu_rujukan'], true)) {
            $e[] = 'Status pemeriksaan gigi tidak valid.';
        } elseif ($in['gigi_status'] === 'perlu_rujukan' && mb_strlen(trim($in['gigi_ket'] ?? '')) < 3) {
            $e[] = 'Keterangan rujukan gigi wajib diisi.';
        }

        if (!in_array($in['fisik_status'], ['belum', 'sehat', 'ada_keluhan', 'perlu_rujukan'], true)) {
            $e[] = 'Status fisik umum tidak valid.';
        } elseif (in_array($in['fisik_status'], ['ada_keluhan', 'perlu_rujukan'], true)
                  && mb_strlen(trim($in['fisik_ket'] ?? '')) < 3) {
            $e[] = 'Keterangan kondisi fisik wajib diisi.';
        }

        return $e;
    }

    /** Simpan snapshot riwayat (gagal mencatat riwayat tidak membatalkan penyimpanan). */
    private function writeHistory(int $skriningId, string $aksi, array $payload, string $ringkasan): void
    {
        try {
            $snap = $payload;
            $snap['ringkasan'] = $ringkasan;
            $this->riwayat->create([
                'skrining_id'  => $skriningId,
                'aksi'         => $aksi,
                'data_sesudah' => json_encode($snap, JSON_UNESCAPED_UNICODE),
                'user_id'      => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            // diamkan: riwayat bersifat pelengkap
        }
    }

    /** Bandingkan data lama vs baru -> ringkasan "Label: lama → baru". */
    private function diffRingkasan(array $old, array $new): string
    {
        $labels = [
            'tanggal_skrining' => 'Tanggal',  'tinggi_cm' => 'Tinggi',
            'berat_kg' => 'Berat',            'imt' => 'IMT',
            'kategori_imt' => 'Kategori IMT', 'golongan_darah' => 'Gol. darah',
            'rhesus' => 'Rhesus',             'buta_warna' => 'Buta warna',
            'buta_warna_ket' => 'Ket. buta warna', 'gigi_status' => 'Gigi',
            'gigi_ket' => 'Ket. gigi',        'fisik_status' => 'Fisik umum',
            'fisik_ket' => 'Ket. fisik',      'catatan_medis' => 'Catatan medis',
        ];
        foreach (SkriningKesehatan::KONDISI as $f => $l) {
            $labels[$f]          = $l;
            $labels[$f . '_ket'] = 'Ket. ' . mb_strtolower($l);
        }

        $changes = [];
        foreach ($labels as $f => $label) {
            $o = (string) ($old[$f] ?? '');
            $n = (string) ($new[$f] ?? '');
            if ($o !== $n) {
                $changes[] = $label . ': ' . ($o !== '' ? $o : '-') . ' → ' . ($n !== '' ? $n : '-');
            }
        }
        return $changes ? implode('; ', $changes) : 'Tidak ada perubahan nilai.';
    }
}