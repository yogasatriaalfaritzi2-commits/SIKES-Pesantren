<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\GiziDiet;
use App\Models\Santri;
use App\Models\SkriningKesehatan;

class GiziController extends Controller
{
    private const ROLES_MEDIS = ['tenaga-medis'];
    private const ROLES_DAPUR = ['dapur-gizi'];

    private GiziDiet $diet;
    private Santri $santri;
    private SkriningKesehatan $skrining;

    public function __construct()
    {
        $this->diet     = new GiziDiet();
        $this->santri   = new Santri();
        $this->skrining = new SkriningKesehatan();
    }

    /** Gate per aksi: rekomendasi = medis, distribusi = dapur (Super Admin bypass). */
    private function requireRole(array $slugs): void
    {
        if (Auth::isSuper()) return;
        if (!in_array(Auth::roleSlug(), $slugs, true)) {
            http_response_code(403);
            require ROOT_PATH . '/app/views/errors/403.php';
            exit;
        }
    }

    /* ================= Daftar Diet + Dashboard Dapur ================= */
    public function index(): void
    {
        $tanggal = trim($_GET['tanggal'] ?? '');
        if ($tanggal === '' || strtotime($tanggal) === false) {
            $tanggal = date('Y-m-d');
        }

        $filters = [
            'q'       => trim($_GET['q'] ?? ''),
            'tanggal' => $tanggal,
            'asrama'  => (int) ($_GET['asrama'] ?? 0),
            'kamar'   => (int) ($_GET['kamar'] ?? 0),
            'status'  => isset(GiziDiet::STATUS_LABEL[$_GET['status'] ?? '']) ? $_GET['status'] : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('gizi/index', [
            'title'         => 'Dapur & Gizi — Diet Khusus',
            'user'          => Auth::user(),
            'data'          => $this->diet->paginate($page, 10, $filters),
            'stats'         => $this->diet->statsFor($tanggal),
            'filters'       => $filters,
            'asramaOptions' => (new \App\Models\Asrama())->options(),
            'kamarList'     => $this->diet->kamarOptions(),
        ]);
    }

    /* ================= Input Diet (Tenaga Medis) ================= */
    public function create(): void
    {
        $this->view('gizi/form', [
            'title'         => 'Input Rekomendasi Diet Khusus',
            'user'          => Auth::user(),
            'santriOptions' => $this->skrining->santriOptions(),
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/gizi/create');
        $this->requireRole(self::ROLES_MEDIS);

        $in = [
            'santri_id'      => (int) ($this->input('santri_id') ?? 0),
            'tanggal'        => $this->input('tanggal') ?? '',
            'jenis_diet'     => $this->input('jenis_diet') ?? '',
            'jenis_lain'     => trim($this->input('jenis_lain') ?? ''),
            'catatan_medis'  => $this->input('catatan_medis') ?? '',
        ];

        if ($errors = $this->validate($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/gizi/create');
        }

        $this->diet->create([
            'santri_id'     => $in['santri_id'],
            'tanggal'       => $in['tanggal'],
            'jenis_diet'    => $in['jenis_diet'],
            'jenis_lain'    => $in['jenis_diet'] === 'lainnya' ? $in['jenis_lain'] : null,
            'catatan_medis' => $in['catatan_medis'] !== '' ? $in['catatan_medis'] : null,
            'status_distribusi' => 'belum',
            'rekomendasi_id'    => Auth::id(),
        ]);

        $santri = $this->santri->find($in['santri_id']);
        log_activity('create', 'gizi', "Rekomendasi diet {$in['jenis_diet']} untuk {$santri['nama']} ({$in['tanggal']})");
        Session::setFlash('success', 'Rekomendasi diet tersimpan dan tampil di daftar dapur.');
        $this->redirect('/gizi?tanggal=' . $in['tanggal']);
    }

    /* ================= Status Distribusi (Bagian Dapur) ================= */
    public function distribusi($id): void
    {
        $this->checkCsrf('/gizi');
        $this->requireRole(self::ROLES_DAPUR);

        $status = $this->input('status') ?? '';
        if (!in_array($status, ['belum', 'siap', 'selesai'], true)) {
            Session::setFlash('error', 'Status distribusi tidak valid.');
            $this->redirect('/gizi');
        }

        $item = $this->diet->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data diet tidak ditemukan.');
            $this->redirect('/gizi');
        }

        $this->diet->update((int) $item['id'], [
            'status_distribusi'     => $status,
            'distribusi_at'         => date('Y-m-d H:i:s'),
            'distribusi_petugas_id' => Auth::id(),
        ]);

        $jenis = GiziDiet::displayJenis($item);
        log_activity('update', 'gizi', "Distribusi diet {$jenis} — {$item['tanggal']}: " . GiziDiet::STATUS_LABEL[$status]);
        Session::setFlash('success', 'Status distribusi diperbarui: ' . GiziDiet::STATUS_LABEL[$status] . '.');
        $this->redirect('/gizi?tanggal=' . $item['tanggal']);
    }

    /* ================= Cetak Label ================= */

    /** Label tunggal untuk satu baris diet. */
    public function label($id): void
    {
        $item = $this->diet->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data diet tidak ditemukan.');
            $this->redirect('/gizi');
        }

        $this->view('gizi/label', [
            'rows'     => [$item],
            'subtitle' => 'Label: ' . GiziDiet::displayJenis($item) . ' — ' . $item['santri_nama'],
        ], null);
    }

    /** Label massal satu tanggal (opsional difilter asrama/kamar). */
    public function labelHariIni(): void
    {
        $tanggal  = trim($_GET['tanggal'] ?? '');
        if ($tanggal === '' || strtotime($tanggal) === false) {
            $tanggal = date('Y-m-d');
        }
        $asramaId = (int) ($_GET['asrama'] ?? 0);
        $kamarId  = (int) ($_GET['kamar'] ?? 0);

        $rows = $this->diet->listForDate($tanggal, $asramaId, $kamarId);

        $this->view('gizi/label', [
            'rows'     => $rows,
            'subtitle' => 'Label Diet ' . date('d-m-Y', strtotime($tanggal)) . ' (' . count($rows) . ' label)',
        ], null);
    }

    /* ================= Hapus (Tenaga Medis) ================= */
    public function destroy($id): void
    {
        $this->checkCsrf('/gizi');
        $this->requireRole(self::ROLES_MEDIS);

        $item = $this->diet->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data diet tidak ditemukan.');
            $this->redirect('/gizi');
        }

        $this->diet->delete((int) $id);
        log_activity('delete', 'gizi', "Menghapus rekomendasi diet ({$item['tanggal']}) ID {$id}");
        Session::setFlash('success', 'Rekomendasi diet dihapus.');
        $this->redirect('/gizi?tanggal=' . $item['tanggal']);
    }

    /* ================= Helper ================= */

    /** @return string[] */
    private function validate(array $in): array
    {
        $e = [];

        $santri = $in['santri_id'] > 0 ? $this->santri->find($in['santri_id']) : null;
        if (!$santri) {
            $e[] = 'Santri wajib dipilih.';
        }

        $ts = strtotime($in['tanggal']);
        if ($ts === false) {
            $e[] = 'Tanggal tidak valid.';
        } elseif ($ts > strtotime('+30 day')) {
            $e[] = 'Tanggal diet maksimal 30 hari ke depan.';
        }

        if (!isset(GiziDiet::JENIS[$in['jenis_diet']])) {
            $e[] = 'Jenis diet wajib dipilih.';
        } elseif ($in['jenis_diet'] === 'lainnya' && mb_strlen($in['jenis_lain']) < 3) {
            $e[] = 'Sebutkan jenis diet lainnya (min. 3 karakter).';
        } elseif ($in['jenis_diet'] !== 'lainnya') {
            $in['jenis_lain'] = '';
        }

        if (!$e && $santri && $in['jenis_diet'] !== '' &&
            $this->diet->existsFor($in['santri_id'], $in['tanggal'], $in['jenis_diet'])) {
            $e[] = 'Santri tersebut sudah memiliki jenis diet ini pada tanggal yang sama.';
        }

        if (mb_strlen($in['catatan_medis']) > 1000) {
            $e[] = 'Catatan medis maksimal 1000 karakter.';
        }
        return $e;
    }
}