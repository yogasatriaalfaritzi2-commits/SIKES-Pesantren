<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanRiwayat;
use App\Models\Santri;
use App\Models\SkriningKesehatan;

class PemeriksaanController extends Controller
{
    private Pemeriksaan $pemeriksaan;
    private PemeriksaanRiwayat $riwayat;
    private Santri $santri;
    private SkriningKesehatan $skrining;

    public function __construct()
    {
        $this->pemeriksaan = new Pemeriksaan();
        $this->riwayat     = new PemeriksaanRiwayat();
        $this->santri      = new Santri();
        $this->skrining    = new SkriningKesehatan();
    }

    /* ================= 1. Daftar Kunjungan ================= */
    public function index(): void
    {
        $filters = [
            'q'         => trim($_GET['q'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
            'status'    => in_array($_GET['status'] ?? '', ['menunggu', 'selesai', 'batal'], true) ? $_GET['status'] : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('pemeriksaan/index', [
            'title'   => 'Pemeriksaan Kesehatan',
            'user'    => Auth::user(),
            'data'    => $this->pemeriksaan->paginate($page, 10, $filters),
            'filters' => $filters,
        ]);
    }

    /* ================= 2. Registrasi Kunjungan ================= */
    public function create(): void
    {
        $this->view('pemeriksaan/form', [
            'title'         => 'Registrasi Kunjungan',
            'user'          => Auth::user(),
            'item'          => null,
            'santriOptions' => $this->skrining->santriOptions(),
            'preSantriId'   => (int) ($_GET['santri_id'] ?? 0),
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/pemeriksaan/create');
        $in = $this->gatherRegistration();

        $errors = $this->validateRegistration($in, false);
        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/pemeriksaan/create' . ($in['santri_id'] > 0 ? '?santri_id=' . $in['santri_id'] : ''));
        }

        $payload = [
            'santri_id'         => $in['santri_id'],
            'tanggal_kunjungan' => $in['tanggal_kunjungan'],
            'jenis_kunjungan'   => $in['jenis_kunjungan'],
            'keluhan'           => $in['keluhan'],
            'pemeriksaan_awal'  => $in['pemeriksaan_awal'] !== '' ? $in['pemeriksaan_awal'] : null,
            'status'            => 'menunggu',
            'petugas_id'        => Auth::id(),
        ];
        $id = $this->pemeriksaan->create($payload);

        $this->writeHistory($id, 'registrasi', $payload, 'Kunjungan teregistrasi, menunggu pemeriksaan');

        $santri = $this->santri->find($in['santri_id']);
        log_activity('create', 'pemeriksaan', 'Registrasi kunjungan: ' . $santri['nama'] . ' (NIS ' . $santri['nis'] . ')');
        Session::setFlash('success', 'Kunjungan berhasil teregistrasi. Santri menunggu pemeriksaan.');
        $this->redirect('/pemeriksaan/' . $id);
    }

    /* ================= 3. Detail Pemeriksaan ================= */
    public function show($id): void
    {
        $item = $this->pemeriksaan->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data pemeriksaan tidak ditemukan.');
            $this->redirect('/pemeriksaan');
        }

        $skrining = $this->skrining->findBySantri((int) $item['santri_id']);

        // Kirim baris database UTUH ke view
        $this->view('pemeriksaan/show', [
            'title'       => 'Detail Pemeriksaan',
            'user'        => Auth::user(),
            'item'        => $item,
            'skrining'    => $skrining,
            'warnings'    => SkriningKesehatan::warningsFrom($skrining),
            'riwayatList' => $this->riwayat->forPemeriksaan((int) $id),
        ]);
    }

    /* ================= Lakukan Pemeriksaan ================= */
    public function formPeriksa($id): void
    {
        $item = $this->pemeriksaan->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data pemeriksaan tidak ditemukan.');
            $this->redirect('/pemeriksaan');
        }
        if ($item['status'] !== 'menunggu') {
            Session::setFlash('warning', 'Kunjungan ini sudah berstatus "' . Pemeriksaan::STATUS_LABEL[$item['status']] . '".');
            $this->redirect('/pemeriksaan/' . $item['id']);
        }

        $this->renderFormPeriksa($item, false);
    }

    public function periksa($id): void
    {
        $this->checkCsrf('/pemeriksaan');
        $id   = (int) $id;
        $item = $this->pemeriksaan->find($id);
        if (!$item) {
            Session::setFlash('error', 'Data pemeriksaan tidak ditemukan.');
            $this->redirect('/pemeriksaan');
        }
        if ($item['status'] !== 'menunggu') {
            Session::setFlash('error', 'Kunjungan ini sudah diproses sebelumnya.');
            $this->redirect('/pemeriksaan/' . $id);
        }

        $in     = $this->gatherExamination();
        $errors = $this->validateExamination($in, $item['tanggal_kunjungan']);
        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/pemeriksaan/' . $id . '/periksa');
        }

        $payload = array_merge($this->examinationPayload($in), [
            'status'       => 'selesai',
            'pemeriksa_id' => Auth::id(),
        ]);
        $this->pemeriksaan->update($id, $payload);

        $this->writeHistory($id, 'periksa', $payload, 'Pemeriksaan diselesaikan. Diagnosis: ' . ($payload['diagnosis'] ?? '-'));
        log_activity('update', 'pemeriksaan', 'Menyelesaikan pemeriksaan ID ' . $id . ', diagnosis: ' . ($payload['diagnosis'] ?? '-'));
        Session::setFlash('success', 'Hasil pemeriksaan berhasil disimpan.');
        $this->redirect('/pemeriksaan/' . $id);
    }

    /* ================= Edit (koreksi data) ================= */
    public function edit($id): void
    {
        $item = $this->pemeriksaan->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data pemeriksaan tidak ditemukan.');
            $this->redirect('/pemeriksaan');
        }
        if ($item['status'] === 'batal') {
            Session::setFlash('error', 'Kunjungan yang dibatalkan tidak dapat diubah.');
            $this->redirect('/pemeriksaan/' . $item['id']);
        }

        $this->renderFormPeriksa($item, true);
    }

    public function update($id): void
    {
        $this->checkCsrf('/pemeriksaan');
        $id  = (int) $id;
        $old = $this->pemeriksaan->find($id);
        if (!$old) {
            Session::setFlash('error', 'Data pemeriksaan tidak ditemukan.');
            $this->redirect('/pemeriksaan');
        }
        if ($old['status'] === 'batal') {
            Session::setFlash('error', 'Kunjungan yang dibatalkan tidak dapat diubah.');
            $this->redirect('/pemeriksaan/' . $id);
        }

        $in         = $this->gatherAll();
        $isExamined = $old['status'] === 'selesai';

        $errors = $this->validateRegistration($in, true);
        if ($isExamined) {
            $errors = array_merge($errors, $this->validateExamination($in, $in['tanggal_kunjungan']));
        }
        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/pemeriksaan/' . $id . '/edit');
        }

        $payload = [
            'tanggal_kunjungan' => $in['tanggal_kunjungan'],
            'jenis_kunjungan'   => $in['jenis_kunjungan'],
            'keluhan'           => $in['keluhan'],
            'pemeriksaan_awal'  => $in['pemeriksaan_awal'] !== '' ? $in['pemeriksaan_awal'] : null,
        ];
        if ($isExamined) {
            $payload = array_merge($payload, $this->examinationPayload($in));
        }

        $this->pemeriksaan->update($id, $payload);
        $this->writeHistory($id, 'ubah', $payload, 'Perubahan/koreksi data pemeriksaan');
        log_activity('update', 'pemeriksaan', 'Mengubah data pemeriksaan ID ' . $id);
        Session::setFlash('success', 'Data pemeriksaan berhasil diperbarui.');
        $this->redirect('/pemeriksaan/' . $id);
    }

    /* ================= Batalkan (bukan hapus) ================= */
    public function batal($id): void
    {
        $this->checkCsrf('/pemeriksaan');
        $item = $this->pemeriksaan->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data pemeriksaan tidak ditemukan.');
            $this->redirect('/pemeriksaan');
        }
        if ($item['status'] !== 'menunggu') {
            Session::setFlash('error', 'Hanya kunjungan berstatus "menunggu" yang dapat dibatalkan.');
            $this->redirect('/pemeriksaan/' . $item['id']);
        }

        $this->pemeriksaan->update((int) $id, ['status' => 'batal']);
        $this->writeHistory((int) $id, 'batal', ['status' => 'batal'], 'Kunjungan dibatalkan');
        log_activity('update', 'pemeriksaan', 'Membatalkan kunjungan ID ' . $id);
        Session::setFlash('success', 'Kunjungan berhasil dibatalkan.');
        $this->redirect('/pemeriksaan');
    }

    /* ================= 4. Riwayat Kesehatan Santri ================= */
    public function riwayat($santriId): void
    {
        $santriId = (int) $santriId;
        $santri   = $this->santri->findDetail($santriId);
        if (!$santri) {
            Session::setFlash('error', 'Santri tidak ditemukan.');
            $this->redirect('/pemeriksaan');
        }

        $skrining = $this->skrining->findBySantri($santriId);

        $this->view('pemeriksaan/riwayat', [
            'title'    => 'Riwayat Kesehatan Santri',
            'user'     => Auth::user(),
            'santri'   => $santri,
            'skrining' => $skrining,
            'warnings' => SkriningKesehatan::warningsFrom($skrining),
            'list'     => $this->pemeriksaan->allForSantri($santriId),
        ]);
    }

    /* ================= Helper ================= */

    private function renderFormPeriksa(array $item, bool $isEdit): void
    {
        $skrining = $this->skrining->findBySantri((int) $item['santri_id']);

        $this->view('pemeriksaan/form_periksa', [
            'title'      => $isEdit ? 'Edit Pemeriksaan' : 'Pemeriksaan — ' . $item['santri_nama'],
            'user'       => Auth::user(),
            'item'       => $item,
            'warnings'   => SkriningKesehatan::warningsFrom($skrining),
            'isEdit'     => $isEdit,
            'isExamined' => $item['status'] === 'selesai',
        ]);
    }

    private function normalizeDateTime(string $v): string
    {
        $v = str_replace('T', ' ', trim($v));
        if (strlen($v) === 16) {
            $v .= ':00';
        }
        return $v;
    }

    private function gatherRegistration(): array
    {
        return [
            'santri_id'         => (int) ($this->input('santri_id') ?? 0),
            'tanggal_kunjungan' => $this->normalizeDateTime($this->input('tanggal_kunjungan') ?? ''),
            'jenis_kunjungan'   => $this->input('jenis_kunjungan') ?? 'rawat_jalan',
            'keluhan'           => $this->input('keluhan') ?? '',
            'pemeriksaan_awal'  => $this->input('pemeriksaan_awal') ?? '',
        ];
    }

    private function gatherExamination(): array
    {
        return [
            'suhu_c'            => $this->input('suhu_c') ?? '',
            'td_sistol'         => $this->input('td_sistol') ?? '',
            'td_diastol'        => $this->input('td_diastol') ?? '',
            'nadi'              => $this->input('nadi') ?? '',
            'pernapasan'        => $this->input('pernapasan') ?? '',
            'hasil_pemeriksaan' => $this->input('hasil_pemeriksaan') ?? '',
            'diagnosis'         => $this->input('diagnosis') ?? '',
            'tindakan'          => $this->input('tindakan') ?? '',
            'resep_obat'        => $this->input('resep_obat') ?? '',
            'catatan_lanjutan'  => $this->input('catatan_lanjutan') ?? '',
            'tanggal_kontrol'   => $this->input('tanggal_kontrol') ?? '',
        ];
    }

    private function gatherAll(): array
    {
        return array_merge($this->gatherRegistration(), $this->gatherExamination());
    }

    /** @return string[] */
    private function validateRegistration(array $in, bool $skipSantri): array
    {
        $e = [];
        if (!$skipSantri) {
            if ($in['santri_id'] <= 0) {
                $e[] = 'Santri wajib dipilih.';
            } elseif (!$this->santri->find($in['santri_id'])) {
                $e[] = 'Santri tidak ditemukan.';
            }
        }
        $ts = strtotime($in['tanggal_kunjungan']);
        if ($in['tanggal_kunjungan'] === '' || $ts === false) {
            $e[] = 'Tanggal & jam kunjungan tidak valid.';
        } elseif ($ts > time() + 300) {
            $e[] = 'Tanggal & jam kunjungan tidak boleh di masa depan.';
        }
        if (!in_array($in['jenis_kunjungan'], ['rawat_jalan', 'darurat', 'kontrol', 'preventif'], true)) {
            $e[] = 'Jenis kunjungan tidak valid.';
        }
        if (mb_strlen($in['keluhan']) < 3) {
            $e[] = 'Keluhan wajib diisi (min. 3 karakter).';
        }
        return $e;
    }

    /** @return string[] */
    private function validateExamination(array $in, ?string $baseDate = null): array
    {
        $e = [];

        if ($in['suhu_c'] !== '' && (!is_numeric($in['suhu_c']) || (float) $in['suhu_c'] < 33 || (float) $in['suhu_c'] > 45)) {
            $e[] = 'Suhu tubuh harus antara 33-45 °C.';
        }
        foreach ([
            'td_sistol'  => [50, 300, 'Tekanan darah sistolik'],
            'td_diastol' => [20, 200, 'Tekanan darah diastolik'],
            'nadi'       => [20, 250, 'Nadi'],
            'pernapasan' => [5, 80, 'Laju pernapasan'],
        ] as $field => [$min, $max, $label]) {
            $v = $in[$field];
            if ($v !== '' && (!ctype_digit($v) || (int) $v < $min || (int) $v > $max)) {
                $e[] = "{$label} harus angka antara {$min}-{$max}.";
            }
        }
        if ($in['td_sistol'] !== '' && $in['td_diastol'] !== '' && (int) $in['td_sistol'] <= (int) $in['td_diastol']) {
            $e[] = 'Tekanan darah sistolik harus lebih besar dari diastolik.';
        }

        if (mb_strlen($in['hasil_pemeriksaan']) < 3) {
            $e[] = 'Hasil pemeriksaan wajib diisi.';
        }
        if (mb_strlen($in['diagnosis']) < 3) {
            $e[] = 'Diagnosis/keterangan tenaga medis wajib diisi.';
        }

        if ($in['tanggal_kontrol'] !== '') {
            $tk = strtotime($in['tanggal_kontrol']);
            if ($tk === false) {
                $e[] = 'Tanggal kontrol tidak valid.';
            } elseif ($baseDate && $tk < strtotime(date('Y-m-d', strtotime($baseDate)))) {
                $e[] = 'Tanggal kontrol tidak boleh sebelum tanggal kunjungan.';
            }
        }
        return $e;
    }

    private function examinationPayload(array $in): array
    {
        $num = fn (string $v) => $v !== '' ? $v : null;
        return [
            'suhu_c'            => $num($in['suhu_c']),
            'td_sistol'         => $num($in['td_sistol']),
            'td_diastol'        => $num($in['td_diastol']),
            'nadi'              => $num($in['nadi']),
            'pernapasan'        => $num($in['pernapasan']),
            'hasil_pemeriksaan' => $in['hasil_pemeriksaan'] !== '' ? $in['hasil_pemeriksaan'] : null,
            'diagnosis'         => $in['diagnosis'] !== '' ? $in['diagnosis'] : null,
            'tindakan'          => $in['tindakan'] !== '' ? $in['tindakan'] : null,
            'resep_obat'        => $in['resep_obat'] !== '' ? $in['resep_obat'] : null,
            'catatan_lanjutan'  => $in['catatan_lanjutan'] !== '' ? $in['catatan_lanjutan'] : null,
            'tanggal_kontrol'   => $in['tanggal_kontrol'] !== '' ? $in['tanggal_kontrol'] : null,
        ];
    }

    private function writeHistory(int $pemeriksaanId, string $aksi, array $payload, string $ringkasan): void
    {
        try {
            $snap = $payload;
            $snap['ringkasan'] = $ringkasan;
            $this->riwayat->create([
                'pemeriksaan_id' => $pemeriksaanId,
                'aksi'           => $aksi,
                'data_sesudah'   => json_encode($snap, JSON_UNESCAPED_UNICODE),
                'user_id'        => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            // riwayat pelengkap — jangan ganggu proses utama
        }
    }
}