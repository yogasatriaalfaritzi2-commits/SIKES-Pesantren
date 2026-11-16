<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Kepulangan;
use App\Models\KepulanganRiwayat;
use App\Models\Santri;
use App\Models\SkriningKesehatan;

class KepulanganController extends Controller
{
    private const ROLES_MEDIS      = ['tenaga-medis'];
    private const ROLES_KEAMANAN   = ['keamanan'];
    private const ROLES_PENGASUHAN = ['pengasuhan', 'kyai'];

    private Kepulangan $kepulangan;
    private KepulanganRiwayat $riwayat;
    private Santri $santri;
    private SkriningKesehatan $skrining;

    public function __construct()
    {
        $this->kepulangan = new Kepulangan();
        $this->riwayat    = new KepulanganRiwayat();
        $this->santri     = new Santri();
        $this->skrining   = new SkriningKesehatan();
    }

    /** Gate per tahap: hanya role berkenaan (Super Admin bypass). */
    private function requireRole(array $slugs): void
    {
        if (Auth::isSuper()) return;
        if (!in_array(Auth::roleSlug(), $slugs, true)) {
            http_response_code(403);
            require ROOT_PATH . '/app/views/errors/403.php';
            exit;
        }
    }

    /* ================= Daftar ================= */
    public function index(): void
    {
        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'status' => isset(Kepulangan::STATUS_LABEL[$_GET['status'] ?? '']) ? $_GET['status'] : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('kepulangan/index', [
            'title'   => 'Kepulangan Santri Sakit',
            'user'    => Auth::user(),
            'data'    => $this->kepulangan->paginate($page, 10, $filters),
            'filters' => $filters,
        ]);
    }

    /* ================= Tahap 1: Rekomendasi Medis ================= */
    public function create(): void
    {
        $this->view('kepulangan/form', [
            'title'         => 'Rekomendasi Medis Kepulangan',
            'user'          => Auth::user(),
            'santriOptions' => $this->skrining->santriOptions(),
        ]);
    }

    /** Endpoint JSON: pemeriksaan selesai milik satu santri (dropdown). */
    public function pemeriksaanOptions(): void
    {
        $santriId = (int) ($_GET['santri_id'] ?? 0);
        json_response(['data' => $santriId > 0 ? $this->kepulangan->pemeriksaanOptions($santriId) : []]);
    }

    public function store(): void
    {
        $this->checkCsrf('/kepulangan/create');
        $this->requireRole(self::ROLES_MEDIS);

        $in = [
            'santri_id'        => (int) ($this->input('santri_id') ?? 0),
            'pemeriksaan_id'   => (int) ($this->input('pemeriksaan_id') ?? 0),
            'alasan_medis'     => $this->input('alasan_medis') ?? '',
            'alamat_tujuan'    => $this->input('alamat_tujuan') ?? '',
            'tanggal_pengajuan'=> $this->input('tanggal_pengajuan') ?? '',
            'medis_catatan'    => $this->input('medis_catatan') ?? '',
        ];

        if ($errors = $this->validateStore($in)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/kepulangan/create');
        }

        $id = $this->kepulangan->create([
            'santri_id'         => $in['santri_id'],
            'pemeriksaan_id'    => $in['pemeriksaan_id'] > 0 ? $in['pemeriksaan_id'] : null,
            'alasan_medis'      => $in['alasan_medis'],
            'alamat_tujuan'     => $in['alamat_tujuan'],
            'tanggal_pengajuan' => $in['tanggal_pengajuan'],
            'status'            => 'menunggu',
            'medis_id'          => Auth::id(),
            'medis_catatan'     => $in['medis_catatan'] !== '' ? $in['medis_catatan'] : null,
        ]);

        $santri = $this->santri->find($in['santri_id']);
        $this->riwayat->add($id, 'rekomendasi',
            "Rekomendasi medis diterbitkan untuk {$santri['nama']} (NIS {$santri['nis']})", Auth::id());
        log_activity('create', 'kepulangan', "Rekomendasi kepulangan santri: {$santri['nama']}");
        Session::setFlash('success', 'Rekomendasi medis tersimpan. Lanjut ke Approval Keamanan.');
        $this->redirect('/kepulangan/' . $id);
    }

    /* ================= Detail + Histori ================= */
    public function show($id): void
    {
        $item = $this->kepulangan->findDetail((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data kepulangan tidak ditemukan.');
            $this->redirect('/kepulangan');
        }

        $this->view('kepulangan/show', [
            'title'       => 'Detail Kepulangan Santri',
            'user'        => Auth::user(),
            'item'        => $item,
            'riwayatList' => $this->riwayat->forKepulangan((int) $id),
        ]);
    }

    /* ================= Tahap 2: Approval Keamanan ================= */
    public function keamanan($id): void
    {
        $this->checkCsrf('/kepulangan');
        $this->requireRole(self::ROLES_KEAMANAN);
        $this->processApproval((int) $id, 'keamanan', $this->input('keputusan') ?? '', $this->input('catatan') ?? '');
    }

    /* ================= Tahap 3: Approval Pengasuhan/Kyai ================= */
    public function pengasuhan($id): void
    {
        $this->checkCsrf('/kepulangan');
        $this->requireRole(self::ROLES_PENGASUHAN);
        $this->processApproval((int) $id, 'pengasuhan', $this->input('keputusan') ?? '', $this->input('catatan') ?? '');
    }

    /* ================= Tahap 4: Izin Keluar Gerbang ================= */
    public function gerbang($id): void
    {
        $this->checkCsrf('/kepulangan');
        $this->requireRole(self::ROLES_KEAMANAN);

        $item = $this->kepulangan->find((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Data kepulangan tidak ditemukan.');
            $this->redirect('/kepulangan');
        }
        if (Kepulangan::currentStage($item) !== 'gerbang') {
            Session::setFlash('error', 'Izin gerbang hanya dapat diberikan pada tahap akhir (status Disetujui).');
            $this->redirect('/kepulangan/' . $item['id']);
        }

        $this->kepulangan->update((int) $item['id'], [
            'status'     => 'selesai',
            'gerbang_id' => Auth::id(),
            'gerbang_at' => date('Y-m-d H:i:s'),
        ]);
        $this->riwayat->add((int) $item['id'], 'gerbang', 'Santri keluar gerbang — workflow selesai.', Auth::id());
        log_activity('update', 'kepulangan', "Izin keluar gerbang #{$item['id']}");
        Session::setFlash('success', 'Izin keluar gerbang tercatat. Workflow selesai.');
        $this->redirect('/kepulangan/' . $item['id']);
    }

    /* ================= Helper ================= */

    /** Eksekusi tahap approval 2/3: validasi tahap, keputusan, catatan, simpan. */
    private function processApproval(int $id, string $stage, string $keputusan, string $catatan): void
    {
        $item = $this->kepulangan->find($id);
        if (!$item) {
            Session::setFlash('error', 'Data kepulangan tidak ditemukan.');
            $this->redirect('/kepulangan');
        }
        if (Kepulangan::currentStage($item) !== $stage) {
            Session::setFlash('error', 'Approval ini bukan tahap aktif saat ini.');
            $this->redirect('/kepulangan/' . $id);
        }

        $keputusan = in_array($keputusan, ['disetujui', 'ditolak'], true) ? $keputusan : '';
        if ($keputusan === '') {
            Session::setFlash('error', 'Keputusan wajib dipilih (Disetujui/Ditolak).');
            $this->redirect('/kepulangan/' . $id);
        }
        if ($keputusan === 'ditolak' && mb_strlen(trim($catatan)) < 5) {
            Session::setFlash('error', 'Catatan wajib diisi (min. 5 karakter) jika menolak.');
            $this->redirect('/kepulangan/' . $id);
        }

        $payload = [
            "{$stage}_id"      => Auth::id(),
            "{$stage}_status"  => $keputusan,
            "{$stage}_catatan" => $catatan !== '' ? trim($catatan) : null,
            "{$stage}_at"      => date('Y-m-d H:i:s'),
        ];
        if ($keputusan === 'ditolak') {
            $payload['status'] = 'ditolak';
        } elseif ($stage === 'pengasuhan') {
            $payload['status'] = 'disetujui'; // tahap 3 lolos -> siap gerbang
        }

        $this->kepulangan->update($id, $payload);

        $label = KepulanganRiwayat::AKSI_LABEL[$stage];
        $ket   = ($keputusan === 'disetujui' ? 'Disetujui' : 'Ditolak') . " ({$label})"
               . ($catatan !== '' ? ': ' . trim($catatan) : '');
        $this->riwayat->add($id, $stage, $ket, Auth::id());
        log_activity('update', 'kepulangan', "Approval {$stage} #{$id}: {$keputusan}");

        Session::setFlash('success', 'Keputusan tersimpan: ' . ucfirst($keputusan) . '.');
        $this->redirect('/kepulangan/' . $id);
    }

    /** @return string[] */
    private function validateStore(array $in): array
    {
        $e = [];

        $santri = $in['santri_id'] > 0 ? $this->santri->find($in['santri_id']) : null;
        if (!$santri) {
            $e[] = 'Santri wajib dipilih.';
        } elseif ($this->kepulangan->hasActiveProcess($in['santri_id'])) {
            $e[] = 'Santri tersebut masih memiliki proses kepulangan AKTIF. Selesaikan/ditolakkan proses sebelumnya dulu.';
        }
        if ($in['pemeriksaan_id'] > 0) {
            $validIds = array_map('strval', array_column($this->kepulangan->pemeriksaanOptions($in['santri_id']), 'id'));
            if (!in_array((string) $in['pemeriksaan_id'], $validIds, true)) {
                $e[] = 'Pemeriksaan rujukan tidak valid untuk santri tersebut.';
            }
        }
        if (mb_strlen($in['alasan_medis']) < 10) {
            $e[] = 'Rekomendasi/alasan medis minimal 10 karakter.';
        }
        if (mb_strlen($in['alamat_tujuan']) < 5) {
            $e[] = 'Alamat tujuan pulang wajib diisi.';
        }
        $ts = strtotime($in['tanggal_pengajuan']);
        if ($ts === false) {
            $e[] = 'Tanggal pengajuan tidak valid.';
        } elseif ($ts > strtotime('+7 day')) {
            $e[] = 'Tanggal pengajuan maksimal 7 hari ke depan.';
        }
        return $e;
    }
}