<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\PiketJadwal;
use App\Models\PiketRiwayat;

class PiketController extends Controller
{
    private PiketJadwal $jadwal;
    private PiketRiwayat $riwayat;

    public function __construct()
    {
        $this->jadwal  = new PiketJadwal();
        $this->riwayat = new PiketRiwayat();
    }

    /* ================= Daftar + Kalender ================= */
    public function index(): void
    {
        $filters = [
            'q'         => trim($_GET['q'] ?? ''),
            'jenis'     => isset(PiketJadwal::JENIS[$_GET['jenis'] ?? '']) ? $_GET['jenis'] : '',
            'status'    => isset(PiketJadwal::STATUS_LABEL[$_GET['status'] ?? '']) ? $_GET['status'] : '',
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        // Kalender: bulan aktif dari URL (nav prev/next), default bulan ini
        $cy = (int) ($_GET['cy'] ?? date('Y'));
        $cm = (int) ($_GET['cm'] ?? date('m'));
        if ($cy < 2000 || $cy > 2100) { $cy = (int) date('Y'); }
        if ($cm < 1 || $cm > 12)      { $cm = (int) date('m'); }

        $calPrev = $cm === 1  ? ['y' => $cy - 1, 'm' => 12] : ['y' => $cy, 'm' => $cm - 1];
        $calNext = $cm === 12 ? ['y' => $cy + 1, 'm' => 1]  : ['y' => $cy, 'm' => $cm + 1];

        $events = $this->jadwal->calendarEvents($cy, $cm);
        $eventsByDate = [];
        foreach ($events as $ev) {
            $eventsByDate[$ev['tanggal']][] = $ev;
        }

        $this->view('piket/index', [
            'title'        => 'Jadwal Piket Kebersihan',
            'user'         => Auth::user(),
            'data'         => $this->jadwal->paginate($page, 10, $filters),
            'filters'      => $filters,
            'calYear'      => $cy,
            'calMonth'     => $cm,
            'calPrev'      => $calPrev,
            'calNext'      => $calNext,
            'eventsByDate' => $eventsByDate,
        ]);
    }

    /* ================= Buat Jadwal ================= */
    public function create(): void
    {
        $this->view('piket/form', [
            'title' => 'Tambah Jadwal Piket',
            'user'  => Auth::user(),
            'item'  => null,
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/piket/create');
        $in = $this->gather();

        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/piket/create');
        }

        $payload = $this->payload($in);
        $id = $this->jadwal->create($payload);

        $this->riwayat->add($id, 'buat', 'Jadwal dibuat', Auth::id());
        log_activity('create', 'piket', 'Jadwal piket: ' . $in['nama_kegiatan'] . ' (' . $in['tanggal'] . ')');
        Session::setFlash('success', 'Jadwal piket berhasil dibuat.');
        $this->redirect('/piket/' . $id);
    }

    /* ================= Detail ================= */
    public function show($id): void
    {
        $item = $this->jadwal->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Jadwal piket tidak ditemukan.');
            $this->redirect('/piket');
        }

        // Pastikan yang dikirim ke view adalah BARIS DATABASE utuh
        $this->view('piket/show', [
            'title'       => 'Detail Jadwal Piket',
            'user'        => Auth::user(),
            'item'        => $item,
            'riwayatList' => $this->riwayat->forJadwal((int) $id),
        ]);
    }

    /* ================= Edit ================= */
    public function edit($id): void
    {
        $item = $this->jadwal->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Jadwal piket tidak ditemukan.');
            $this->redirect('/piket');
        }

        $this->view('piket/form', [
            'title' => 'Edit Jadwal Piket',
            'user'  => Auth::user(),
            'item'  => $item,
        ]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/piket');
        $id   = (int) $id;
        $item = $this->jadwal->find($id);
        if (!$item) {
            Session::setFlash('error', 'Jadwal piket tidak ditemukan.');
            $this->redirect('/piket');
        }

        $in = $this->gather();
        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/piket/' . $id . '/edit');
        }

        // status TIDAK lewat form — hanya lewat tombol Mulai/Selesai
        $this->jadwal->update($id, $this->payload($in));
        $this->riwayat->add($id, 'ubah', 'Jadwal diperbarui', Auth::id());
        log_activity('update', 'piket', 'Mengubah jadwal piket ID ' . $id);
        Session::setFlash('success', 'Jadwal piket berhasil diperbarui.');
        $this->redirect('/piket/' . $id);
    }

    /* ================= Transisi Status ================= */
    public function mulai($id): void
    {
        $this->checkCsrf('/piket');
        $item = $this->jadwal->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Jadwal piket tidak ditemukan.');
            $this->redirect('/piket');
        }
        if ($item['status'] !== 'terjadwal') {
            Session::setFlash('error', 'Hanya jadwal berstatus "Terjadwal" yang dapat dimulai.');
            $this->redirect('/piket/' . $item['id']);
        }

        $this->jadwal->update((int) $item['id'], ['status' => 'berlangsung']);
        $this->riwayat->add((int) $item['id'], 'mulai', 'Kegiatan dimulai', Auth::id());
        log_activity('update', 'piket', 'Memulai kegiatan piket #' . $item['id'] . ': ' . $item['nama_kegiatan']);
        Session::setFlash('success', 'Kegiatan dimulai. Status: Berlangsung.');
        $this->redirect('/piket/' . $item['id']);
    }

    public function selesai($id): void
    {
        $this->checkCsrf('/piket');
        $item = $this->jadwal->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Jadwal piket tidak ditemukan.');
            $this->redirect('/piket');
        }
        if ($item['status'] !== 'berlangsung') {
            Session::setFlash('error', 'Hanya kegiatan berstatus "Berlangsung" yang dapat diselesaikan.');
            $this->redirect('/piket/' . $item['id']);
        }

        $this->jadwal->update((int) $item['id'], ['status' => 'selesai']);
        $this->riwayat->add((int) $item['id'], 'selesai', 'Kegiatan diselesaikan', Auth::id());
        log_activity('update', 'piket', 'Menyelesaikan kegiatan piket #' . $item['id'] . ': ' . $item['nama_kegiatan']);
        Session::setFlash('success', 'Kegiatan selesai. Reminder akan hilang dari dashboard.');
        $this->redirect('/piket/' . $item['id']);
    }

    /* ================= Hapus ================= */
    public function destroy($id): void
    {
        $this->checkCsrf('/piket');
        $item = $this->jadwal->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Jadwal piket tidak ditemukan.');
            $this->redirect('/piket');
        }

        log_activity('delete', 'piket', 'Menghapus jadwal piket ID ' . $id . ': ' . $item['nama_kegiatan']);
        $this->jadwal->delete((int) $id);
        Session::setFlash('success', 'Jadwal piket berhasil dihapus.');
        $this->redirect('/piket');
    }

    /* ================= Helper ================= */

    private function gather(): array
    {
        return [
            'jenis'            => $this->input('jenis') ?? '',
            'nama_kegiatan'    => $this->input('nama_kegiatan') ?? '',
            'lokasi'           => $this->input('lokasi') ?? '',
            'tanggal'          => $this->input('tanggal') ?? '',
            'waktu_mulai'      => $this->input('waktu_mulai') ?? '',
            'waktu_selesai'    => $this->input('waktu_selesai') ?? '',
            'penanggung_jawab' => $this->input('penanggung_jawab') ?? '',
            'catatan'          => $this->input('catatan') ?? '',
        ];
    }

    private function payload(array $in): array
    {
        return [
            'jenis'            => $in['jenis'],
            'nama_kegiatan'    => $in['nama_kegiatan'],
            'lokasi'           => $in['lokasi'],
            'tanggal'          => $in['tanggal'],
            'waktu_mulai'      => $in['waktu_mulai'],
            'waktu_selesai'    => $in['waktu_selesai'] !== '' ? $in['waktu_selesai'] : null,
            'penanggung_jawab' => $in['penanggung_jawab'],
            'catatan'          => $in['catatan'] !== '' ? $in['catatan'] : null,
            'dibuat_id'        => Auth::id(),
        ];
    }

    /** @return string[] */
    private function validate(array $in): array
    {
        $e = [];

        if (!isset(PiketJadwal::JENIS[$in['jenis']])) {
            $e[] = 'Jenis kegiatan wajib dipilih.';
        }
        if (mb_strlen($in['nama_kegiatan']) < 3) {
            $e[] = 'Nama kegiatan minimal 3 karakter.';
        }
        if (mb_strlen($in['lokasi']) < 3) {
            $e[] = 'Lokasi minimal 3 karakter.';
        }
        if ($in['tanggal'] === '' || strtotime($in['tanggal']) === false) {
            $e[] = 'Tanggal tidak valid.';
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', substr($in['waktu_mulai'], 0, 5))) {
            $e[] = 'Waktu mulai tidak valid (format HH:MM).';
        }
        if ($in['waktu_selesai'] !== '') {
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', substr($in['waktu_selesai'], 0, 5))) {
                $e[] = 'Waktu selesai tidak valid (format HH:MM).';
            } elseif ($in['waktu_selesai'] <= $in['waktu_mulai']) {
                $e[] = 'Waktu selesai harus lebih besar dari waktu mulai.';
            }
        }
        if (mb_strlen($in['penanggung_jawab']) < 3) {
            $e[] = 'Penanggung jawab wajib diisi (min. 3 karakter).';
        }
        if (mb_strlen($in['catatan']) > 1000) {
            $e[] = 'Catatan maksimal 1000 karakter.';
        }
        return $e;
    }
}