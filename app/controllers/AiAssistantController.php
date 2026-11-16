<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\AiAssistant;
use RuntimeException;

class AiAssistantController extends Controller
{
    private const ROLES = ['kyai', 'tenaga-medis']; // + Super Admin

    private AiAssistant $ai;

    public function __construct()
    {
        $this->ai = new AiAssistant();
    }

    private function gate(): void
    {
        if (Session::isLoggedIn()) {
            return;
        }
        http_response_code(403);
        require ROOT_PATH . '/app/views/errors/403.php';
        exit;
    }

    private function buildFilters(): array
    {
        $periode = in_array($_GET['periode'] ?? '', ['hari', 'minggu', 'bulan', 'tahun'], true)
            ? $_GET['periode'] : 'bulan';
        switch ($periode) {
            case 'hari':   $from = date('Y-m-d');                      break;
            case 'minggu': $from = date('Y-m-d', strtotime('-6 day')); break;
            case 'tahun':  $from = date('Y-01-01');                    break;
            default:       $from = date('Y-m-01');
        }
        return [
            'periode' => $periode,
            'from'    => $from,
            'to'      => date('Y-m-d'),
            'asrama'  => (int) ($_GET['asrama'] ?? 0),
            'blok'    => (int) ($_GET['blok'] ?? 0),
        ];
    }

    /* ============================================================
       HELPER AMAN (v17.3) — fondasi ketahanan jangka panjang:

       1. model()  : ambil instance model HANYA bila class-nya ada
                     (chatbot tidak crash bila file model modul
                      tertentu hilang/berganti nama).
       2. stat()   : jalankan perhitungan, gagal -> nilai default.
       3. method() : panggil method model, gagal -> default.
       ============================================================ */
    private function model(string $class): ?object
    {
        $fqcn = '\\App\\Models\\' . $class;
        if (!class_exists($fqcn)) {
            return null;
        }
        try {
            return new $fqcn();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function stat(callable $fn, $default = 0)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function method(?object $model, string $method, $default = 0)
    {
        if ($model === null || !method_exists($model, $method)) {
            return $default;
        }
        return $this->stat(function () use ($model, $method) {
            return $model->{$method}();
        }, $default);
    }

    /* ============================================================
       KONTEKS LUAS dari SELURUH modul SIKES — semua akses model
       dibungkus helper aman di atas. Satu modul hilang TIDAK
       mematikan chatbot; bagian itu diisi nilai 0.
       ============================================================ */
    private function buildWideContext(array $f): array
    {
        $analysis = $this->stat(function () use ($f) {
            return $this->ai->analyze($f);
        }, []);

        // --- Model modul (opsional: aman bila file model tidak ada) ---
        $userM       = $this->model('User');
        $santriM     = $this->model('Santri');
        $waliM       = $this->model('WaliSantri');
        $asramaM     = $this->model('Asrama');
        $kamarM      = $this->model('Kamar');
        $skriningM   = $this->model('SkriningKesehatan');
        $izinM       = $this->model('SuratIzin');
        $rujukanM    = $this->model('Rujukan');
        $obatM       = $this->model('Obat');
        $obatTransM  = $this->model('ObatTransaksi');
        $piketM      = $this->model('PiketJadwal');
        $keuanganM   = $this->model('Keuangan');
        $pengasuhanM = $this->model('Pengasuhan');
        $tamuM       = $this->model('BukuTamu');
        $kepulanganM = $this->model('Kepulangan');
        $inspeksiM   = $this->model('InspeksiKamar');
        $activityM   = $this->model('ActivityLog');
        $giziM       = $this->model('GiziDiet');
        $bpjsM       = $this->model('Bpjs');
        $roleM       = $this->model('Role');

        // Daftar asrama (pakai method options bila tersedia)
        $asramaList = ($asramaM && method_exists($asramaM, 'options'))
            ? $this->stat(function () use ($asramaM) { return $asramaM->options(); }, [])
            : [];

        // Daftar role & pengguna sistem
        $roleList = $this->stat(function () use ($roleM) {
            return ($roleM && method_exists($roleM, 'allOrdered'))
                ? array_column($roleM->allOrdered(), 'name')
                : [];
        }, []);

        $userList = $this->stat(function () {
            return \App\Core\Database::getInstance()->fetchAll(
                "SELECT u.nama, u.username, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id ORDER BY r.id ASC, u.nama ASC"
            );
        }, []);

        // Statistik sanitasi (pakai method kamarCondition bila tersedia)
        $sanitasiStats = ($inspeksiM && method_exists($inspeksiM, 'kamarCondition'))
            ? $this->stat(function () use ($inspeksiM) {
                return $inspeksiM->kamarCondition()['stats'];
            }, ['baik' => 0, 'perlu_perhatian' => 0, 'risiko_tinggi' => 0, 'belum' => 0])
            : ['baik' => 0, 'perlu_perhatian' => 0, 'risiko_tinggi' => 0, 'belum' => 0];

        // Daftar santri ringkas untuk grounding LLM
        $santriList = ($santriM && method_exists($santriM, 'allDetailed'))
            ? $this->stat(function () use ($santriM) {
                $rows = $santriM->allDetailed();
                return array_map(function ($s) {
                    return [
                        'nama'          => $s['nama'] ?? '',
                        'nis'           => $s['nis'] ?? '',
                        'jk'            => $s['jenis_kelamin'] ?? '',
                        'status'        => $s['status'] ?? 'aktif',
                        'kamar'         => $s['kamar_nomor'] ?? 'Belum ada',
                        'asrama'        => $s['asrama_nama'] ?? 'Belum ada',
                        'wali'          => $s['wali_nama'] ?? 'Belum terdaftar',
                    ];
                }, $rows);
            }, [])
            : [];

        // Daftar wali santri ringkas
        $waliList = ($waliM && method_exists($waliM, 'paginate'))
            ? $this->stat(function () use ($waliM) {
                $res = $waliM->paginate(1, 100);
                return array_map(function ($w) {
                    return [
                        'nama'         => $w['nama'] ?? '',
                        'telepon'      => $w['no_telepon'] ?? '',
                        'pekerjaan'    => $w['pekerjaan'] ?? '',
                        'alamat'       => $w['alamat'] ?? '',
                        'santri_count' => (int) ($w['santri_count'] ?? 0),
                    ];
                }, $res['rows'] ?? []);
            }, [])
            : [];

        // Skrining kesehatan list santri ringkas
        $skriningList = ($skriningM && method_exists($skriningM, 'paginate'))
            ? $this->stat(function () use ($skriningM) {
                $res = $skriningM->paginate(1, 100);
                return array_map(function ($s) {
                    $warnings = \App\Models\SkriningKesehatan::warningsFrom($s);
                    return [
                        'santri_nama'    => $s['nama'] ?? '',
                        'nis'            => $s['nis'] ?? '',
                        'status'         => !empty($s['skrining_id']) ? 'sudah' : 'belum',
                        'imt_kategori'   => $s['imt_kategori'] ?? '-',
                        'golongan_darah' => $s['golongan_darah'] ?? '-',
                        'kondisi_khusus' => !empty($warnings) ? implode(', ', $warnings) : 'Tidak ada',
                    ];
                }, $res['rows'] ?? []);
            }, [])
            : [];

        // Keuangan totals
        $keuanganData = ($keuanganM && method_exists($keuanganM, 'totals'))
            ? $this->stat(function () use ($keuanganM, $f) {
                return $keuanganM->totals($f);
            }, ['masuk' => 0, 'keluar' => 0, 'saldo' => 0])
            : ['masuk' => 0, 'keluar' => 0, 'saldo' => 0];

        return [
            'periode' => [$f['from'], $f['to']],

            /* ---------- Master data ---------- */
            'master_data' => [
                'total_pengguna'      => $this->method($userM, 'count'),
                'daftar_role'         => $roleList,
                'daftar_pengguna'     => $userList,
                'santri_total'        => $this->method($santriM, 'count'),
                'santri_aktif'        => $this->method($santriM, 'countActive'),
                'santri_nonaktif'     => $this->method($santriM, 'countInactive'),
                'santri_putra'        => $this->method($santriM, 'countPutra'),
                'santri_putri'        => $this->method($santriM, 'countPutri'),
                'santri_belum_kamar'  => $this->method($santriM, 'countBelumKamar'),
                'daftar_santri'       => $santriList,
                'wali_terdata'        => $this->method($waliM, 'count'),
                'daftar_wali'         => $waliList,
                'asrama'              => $this->method($asramaM, 'count'),
                'kamar'               => $this->method($kamarM, 'count'),
                'kamar_terisi'        => $this->method($kamarM, 'countOccupied'),
                'daftar_asrama'       => $asramaList,
                'login_hari_ini'      => ($activityM && method_exists($activityM, 'countToday'))
                    ? $this->stat(function () use ($activityM) { return $activityM->countToday('login'); }, 0)
                    : 0,
            ],

            /* ---------- Skrining kesehatan ---------- */
            'skrining_kesehatan' => [
                'sudah_diskrining'   => $this->method($skriningM, 'countScreened'),
                'belum_diskrining'   => $this->method($skriningM, 'countNotScreened'),
                'ada_kondisi_khusus' => $this->method($skriningM, 'countFlagged'),
                'daftar_skrining'    => $skriningList,
            ],

            /* ---------- Kesehatan / pemeriksaan ---------- */
            'kesehatan' => [
                'kunjungan_menunggu' => $this->method($this->model('Pemeriksaan'), 'countWaiting'),
                'kunjungan_hari_ini' => $this->method($this->model('Pemeriksaan'), 'countToday'),
                'kasus_periode'      => (int) ($analysis['kasus']['now'] ?? 0),
                'kasus_periode_lalu' => (int) ($analysis['kasus']['prev'] ?? 0),
                'tren_kenaikan'      => $analysis['trends'] ?? [],
                'kasus_menular_aktif'=> $this->method($this->model('KasusMenular'), 'countActive'),
                'izin_sakit_aktif'   => $this->method($izinM, 'countActive'),
                'rujukan_tercatat'   => ($rujukanM && method_exists($rujukanM, 'paginate'))
                    ? $this->stat(function () use ($rujukanM) {
                        return (int) $rujukanM->paginate(1, 1, [])['total'];
                    })
                    : 0,
            ],

            /* ---------- Sanitasi & lingkungan ---------- */
            'sanitasi' => [
                'kamar_baik'       => (int) ($sanitasiStats['baik'] ?? 0),
                'perlu_perhatian'  => (int) ($sanitasiStats['perlu_perhatian'] ?? 0),
                'risiko_tinggi'    => (int) ($sanitasiStats['risiko_tinggi'] ?? 0),
                'belum_diinspeksi' => (int) ($sanitasiStats['belum'] ?? 0),
            ],

            /* ---------- Manajemen obat ---------- */
            'obat' => [
                'total_item'        => $this->method($obatM, 'count'),
                'stok_menipis'      => $this->method($obatM, 'countLowStock'),
                'kedaluwarsa'       => $this->method($obatM, 'countExpired'),
                'dekat_kedaluwarsa' => $this->method($obatM, 'countNearExpiry'),
                'biaya_bulan_ini'   => $this->method($obatTransM, 'biayaBulanIni'),
                'nilai_persediaan'  => $this->method($obatM, 'nilaiPersediaan'),
            ],

            /* ---------- Operasional pondok ---------- */
            'operasional' => [
                'piket_belum_selesai'     => $this->method($piketM, 'countUnfinished'),
                'izin_pengasuhan'         => (is_array($pc = $this->method($pengasuhanM, 'counts', [])))
                    ? (int) ($pc['izin'] ?? 0) : 0,
                'pelanggaran_pengasuhan'  => (is_array($pc)) ? (int) ($pc['pelanggaran'] ?? 0) : 0,
                'tamu_hari_ini'           => $this->method($tamuM, 'countToday'),
                'tamu_masih_di_dalam'     => $this->method($tamuM, 'countInside'),
                'kepulangan_menunggu'     => $this->method($kepulanganM, 'countPendingKeamanan'),
                'kepulangan_siap_gerbang' => $this->method($kepulanganM, 'countReadyGate'),
            ],

            /* ---------- Keuangan ---------- */
            'keuangan' => $keuanganData,

            /* ---------- Dapur & Gizi ---------- */
            'dapur_gizi' => ($giziM && method_exists($giziM, 'statsFor'))
                ? $this->stat(function () use ($giziM) { return $giziM->statsFor(date('Y-m-d')); }, [])
                : [],

            /* ---------- BPJS Santri ---------- */
            'bpjs' => [
                'total' => $this->method($bpjsM, 'count'),
                'aktif' => $this->method($bpjsM, 'countActive'),
            ],

            /* ---------- Peringatan aktif ---------- */
            'peringatan_aktif' => array_map(function ($w) {
                return ['level' => $w['level'] ?? 'info', 'title' => $w['title'] ?? ''];
            }, $analysis['warnings'] ?? []),
        ];
    }

    /* ============================================================
       PANDUAN CAKUPAN LUAS (system message kedua)
       ============================================================ */
    private function buildRoleScopedContext(array $rawContext): array
    {
        $roleSlug = Auth::roleSlug() ?? '';
        $isSuper  = Auth::isSuper();

        // Super Admin dapat melihat seluruh database SIKES
        if ($isSuper || $roleSlug === 'super-admin') {
            return $rawContext;
        }

        $scoped = [
            'periode' => $rawContext['periode'] ?? [],
            'user_role' => Auth::roleName() ?? 'Pengguna',
        ];

        switch ($roleSlug) {
            case 'tenaga-medis':
                // Kesehatan, pemeriksaan, kasus menular, rujukan, obat, perizinan sakit, skrining kesehatan
                $scoped['master_data'] = [
                    'santri_total'       => $rawContext['master_data']['santri_total'] ?? 0,
                    'santri_aktif'       => $rawContext['master_data']['santri_aktif'] ?? 0,
                    'santri_nonaktif'    => $rawContext['master_data']['santri_nonaktif'] ?? 0,
                    'santri_putra'       => $rawContext['master_data']['santri_putra'] ?? 0,
                    'santri_putri'       => $rawContext['master_data']['santri_putri'] ?? 0,
                    'santri_belum_kamar' => $rawContext['master_data']['santri_belum_kamar'] ?? 0,
                    'daftar_santri'      => $rawContext['master_data']['daftar_santri'] ?? [],
                ];
                $scoped['kesehatan'] = $rawContext['kesehatan'] ?? [];
                $scoped['skrining_kesehatan'] = $rawContext['skrining_kesehatan'] ?? [];
                $scoped['obat'] = $rawContext['obat'] ?? [];
                $scoped['bpjs'] = $rawContext['bpjs'] ?? [];
                $scoped['sanitasi'] = $rawContext['sanitasi'] ?? [];
                $scoped['peringatan_aktif'] = $rawContext['peringatan_aktif'] ?? [];
                break;

            case 'pengasuhan':
                // Pembinaan asrama, izin kepulangan, piket santri, sanitasi kamar/asrama, data kamar
                $scoped['master_data'] = [
                    'santri_total'       => $rawContext['master_data']['santri_total'] ?? 0,
                    'santri_aktif'       => $rawContext['master_data']['santri_aktif'] ?? 0,
                    'santri_nonaktif'    => $rawContext['master_data']['santri_nonaktif'] ?? 0,
                    'santri_putra'       => $rawContext['master_data']['santri_putra'] ?? 0,
                    'santri_putri'       => $rawContext['master_data']['santri_putri'] ?? 0,
                    'santri_belum_kamar' => $rawContext['master_data']['santri_belum_kamar'] ?? 0,
                    'daftar_santri'      => $rawContext['master_data']['daftar_santri'] ?? [],
                    'asrama'             => $rawContext['master_data']['asrama'] ?? 0,
                    'kamar'              => $rawContext['master_data']['kamar'] ?? 0,
                    'kamar_terisi'       => $rawContext['master_data']['kamar_terisi'] ?? 0,
                    'daftar_asrama'      => $rawContext['master_data']['daftar_asrama'] ?? [],
                ];
                $scoped['skrining_kesehatan'] = $rawContext['skrining_kesehatan'] ?? [];
                $scoped['sanitasi'] = $rawContext['sanitasi'] ?? [];
                $scoped['operasional'] = [
                    'piket_belum_selesai'    => $rawContext['operasional']['piket_belum_selesai'] ?? 0,
                    'izin_pengasuhan'        => $rawContext['operasional']['izin_pengasuhan'] ?? 0,
                    'pelanggaran_pengasuhan' => $rawContext['operasional']['pelanggaran_pengasuhan'] ?? 0,
                    'kepulangan_menunggu'    => $rawContext['operasional']['kepulangan_menunggu'] ?? 0,
                ];
                break;

            case 'keamanan':
                // Keamanan gerbang, buku tamu penjenguk, kepulangan santri
                $scoped['operasional'] = [
                    'tamu_hari_ini'           => $rawContext['operasional']['tamu_hari_ini'] ?? 0,
                    'tamu_masih_di_dalam'     => $rawContext['operasional']['tamu_masih_di_dalam'] ?? 0,
                    'kepulangan_menunggu'     => $rawContext['operasional']['kepulangan_menunggu'] ?? 0,
                    'kepulangan_siap_gerbang' => $rawContext['operasional']['kepulangan_siap_gerbang'] ?? 0,
                ];
                break;

            case 'admin-santri':
                // Data santri, wali santri, kamar & asrama
                $scoped['master_data'] = $rawContext['master_data'] ?? [];
                $scoped['bpjs'] = $rawContext['bpjs'] ?? [];
                break;

            case 'dapur-gizi':
                // Dapur gizi dan diet khusus santri
                $scoped['dapur_gizi'] = $rawContext['dapur_gizi'] ?? [];
                break;

            case 'bendahara':
                // Keuangan & anggaran obat
                $scoped['keuangan'] = $rawContext['keuangan'] ?? [];
                $scoped['obat_biaya'] = [
                    'biaya_bulan_ini'  => $rawContext['obat']['biaya_bulan_ini'] ?? 0,
                    'nilai_persediaan' => $rawContext['obat']['nilai_persediaan'] ?? 0,
                ];
                break;

            case 'kyai':
                // Ringkasan eksekutif pemantauan seluruh pondok
                $scoped['master_data'] = [
                    'total_pengguna'     => $rawContext['master_data']['total_pengguna'] ?? 0,
                    'santri_total'       => $rawContext['master_data']['santri_total'] ?? 0,
                    'santri_aktif'       => $rawContext['master_data']['santri_aktif'] ?? 0,
                    'santri_nonaktif'    => $rawContext['master_data']['santri_nonaktif'] ?? 0,
                    'santri_putra'       => $rawContext['master_data']['santri_putra'] ?? 0,
                    'santri_putri'       => $rawContext['master_data']['santri_putri'] ?? 0,
                    'santri_belum_kamar' => $rawContext['master_data']['santri_belum_kamar'] ?? 0,
                    'wali_terdata'       => $rawContext['master_data']['wali_terdata'] ?? 0,
                    'asrama'             => $rawContext['master_data']['asrama'] ?? 0,
                    'kamar'              => $rawContext['master_data']['kamar'] ?? 0,
                    'kamar_terisi'       => $rawContext['master_data']['kamar_terisi'] ?? 0,
                ];
                $scoped['skrining_kesehatan'] = $rawContext['skrining_kesehatan'] ?? [];
                $scoped['kesehatan'] = [
                    'kasus_periode'       => $rawContext['kesehatan']['kasus_periode'] ?? 0,
                    'kasus_menular_aktif' => $rawContext['kesehatan']['kasus_menular_aktif'] ?? 0,
                    'izin_sakit_aktif'    => $rawContext['kesehatan']['izin_sakit_aktif'] ?? 0,
                ];
                $scoped['sanitasi'] = $rawContext['sanitasi'] ?? [];
                $scoped['operasional'] = [
                    'kepulangan_siap_gerbang' => $rawContext['operasional']['kepulangan_siap_gerbang'] ?? 0,
                    'piket_belum_selesai'     => $rawContext['operasional']['piket_belum_selesai'] ?? 0,
                ];
                $scoped['keuangan'] = [
                    'saldo' => $rawContext['keuangan']['saldo'] ?? 0,
                ];
                $scoped['peringatan_aktif'] = $rawContext['peringatan_aktif'] ?? [];
                break;

            default:
                // Santri / Wali santri / role umum lainnya
                $scoped['informasi'] = 'Akses informasi data terbatas untuk akun Anda.';
                break;
        }

        return $scoped;
    }

    private function wideScopeGuide(): string
    {
        $userName = Auth::name() ?? "Pengguna";
        $roleName = Auth::roleName() ?? "Pengguna";
        $roleSlug = Auth::roleSlug() ?? "";
        $isSuper  = Auth::isSuper() || $roleSlug === "super-admin";

        $roleInstruction = $isSuper
            ? "PENGGUNA SAAT INI ADALAH ADMIN / SUPER ADMIN. Anda memiliki otoritas penuh dan WAJIB DAPAT MENJAWAB SEMUA PERTANYAAN seputar seluruh modul dan database SIKES-Pesantren (santri, kesehatan, obat, keuangan, sanitasi, pengasuhan, keamanan, buku tamu, dll)."
            : "PENGGUNA SAAT INI MEMILIKI ROLE: \"" . $roleName . "\". ATURAN KETAT OTORISASI ROLE: Anda HANYA BOLEH MENJAWAB pertanyaan yang relevan dengan tugas dan data modul milik role " . $roleName . " sesuai data yang disediakan. JIKA pengguna bertanya tentang modul di luar wewenang role " . $roleName . " (misal: keuangan bagi tenaga medis, resep obat bagi keamanan, kas pondok bagi santri, dsb), TOLAK DENGAN SINGKAT: \"Maaf, data tersebut berada di luar wewenang tugas role Anda (" . $roleName . ").\"";

        return "ATURAN UTAMA RESPON:\n"
            . "1. JAWAB SINGKAT, PADAT, DAN LANGSUNG PADA SASARAN. DILARANG BERKOMENTAR ATAU MENGELUARKAN TEKS PANJANG BERTELE-TELE LAYAKNYA AI GPT UMUM.\n"
            . "2. JIKA PENGGUNA BERTANYA TENTANG DIRINYA / IDENTITASNYA (\"siapa saya\", \"nama saya siapa\", \"saya siapa\", dll):\n"
            . "   WAJIB JAWAB PERSIS: \"Saya " . $userName . " seorang " . strtolower($roleName) . ".\"\n"
            . "3. JIKA DITANYA IDENTITAS ANDA SEBAGAI AI (\"siapa anda\", \"kamu siapa\", dll): CUKUP JAWAB SANGAT SINGKAT: \"Saya Asisten AI SIKES.\" TANPA KOMENTAR APAPUN LAINNYA.\n"
            . "4. JIKA DITANYA JUMLAH / TOTAL PENGGUNA (user akun aplikasi sistem SIKES): JAWAB DATA DARI master_data.total_pengguna (misal: 10 pengguna), JANGAN TERTUKAR DENGAN SANTRI!\n"
            . "5. JIKA DITANYA DAFTAR PENGGUNA ATAU SIAPA SAJA NAMA PENGGUNANYA DI SISTEM (\"siapa saja nama nama nya\", \"siapa saja namanya\", \"sebutkan penggunanya\", dll): SEBUTKAN NAMA DAN ROLENYA DARI master_data.daftar_pengguna SECARA LENGKAP.\n"
            . "6. JIKA DITANYA DATA SANTRI:\n"
            . "   - Total santri: master_data.santri_total\n"
            . "   - Santri aktif: master_data.santri_aktif\n"
            . "   - Santri tidak aktif / nonaktif: master_data.santri_nonaktif (PENTING: JANGAN TERTUKAR DENGAN SANTRI AKTIF!)\n"
            . "   - Santri putra / laki-laki: master_data.santri_putra\n"
            . "   - Santri putri / perempuan: master_data.santri_putri\n"
            . "   - Santri belum dapat kamar: master_data.santri_belum_kamar\n"
            . "   - Daftar santri atau siapa santri yang tidak aktif/aktif: sebutkan nama dari master_data.daftar_santri sesuai statusnya.\n"
            . "7. JIKA DITANYA DATA WALI / WALI SANTRI:\n"
            . "   - Total wali terdata / ada berapa wali: master_data.wali_terdata (jawab persis angka total wali terdata dari database SIKES, misal: 2 wali santri, JANGAN pernah dijawab dengan total santri!).\n"
            . "   - Daftar nama wali santri / siapa saja wali santri: sebutkan dari master_data.daftar_wali (misal: Hasan Basri, Siti Aminah).\n"
            . "8. JIKA DITANYA DATA SKRINING KESEHATAN:\n"
            . "   - Berapa santri yang sudah melakukan skrining / sudah diskrining: skrining_kesehatan.sudah_diskrining (JANGAN tertukar dengan total santri!).\n"
            . "   - Berapa santri yang belum skrining: skrining_kesehatan.belum_diskrining.\n"
            . "   - Berapa santri yang ada kondisi khusus / penyakit bawaan / alergi: skrining_kesehatan.ada_kondisi_khusus.\n"
            . "   - Daftar santri yang sudah/belum diskrining atau kondisi khusus: sebutkan dari skrining_kesehatan.daftar_skrining sesuai statusnya.\n"
            . "9. ATURAN HAK AKSES ROLE:\n" . $roleInstruction . "\n"
            . "10. JIKA DITANYA DATA SISTEM LAIN (keuangan, obat, asrama, kamar, kasus menular, rujukan, tamu, dll) dan SESUAI wewenang role: LANGSUNG JAWAB ANGKA / DATANYA SECARA SINGKAT TANPA MENGULANG PARAGRAF PANJANG.\n"
            . "11. JIKA DITANYA HAL DI LUAR SISTEM SIKES: Jawab singkat: \"Maaf, saya hanya dapat menjawab seputar sistem SIKES.\"\n"
            . "12. DILARANG MEMBERIKAN DIAGNOSIS MEDIS INDIVIDUAL ATAU RESEP OBAT PERORANGAN.";
    }

    /* ================= Halaman AI + Chat ================= */
    public function index(): void
    {
        $this->gate();
        $f        = $this->buildFilters();
        $analysis = $this->ai->analyze($f);

        $this->view('ai/index', [
            'title'       => 'AI Health Assistant',
            'user'        => Auth::user(),
            'f'           => $f,
            'analysis'    => $analysis,
            'narrative'   => null,
            'llmError'    => null,
            'chatHistory' => AiAssistant::history(),
            'asramaOptions' => (new \App\Models\Asrama())->options(),
            'blokList'      => (new \App\Models\Blok())->allWithAsrama(),
        ]);
    }

    /* ================= Ringkasan Naratif ================= */
    public function enhance(): void
    {
        $this->gate();
        $this->checkCsrf('/ai');

        $f        = $this->buildFilters();
        $analysis = $this->ai->analyze($f);
        $context  = $this->buildWideContext($f);

        $narrative = null;
        $llmError  = null;

        if (!AiAssistant::llmEnabled()) {
            $llmError = 'Chatbot AI belum aktif. Tempel API key gratis di file .env (AI_API_KEY). '
                      . 'Menampilkan ringkasan mesin analisis lokal.';
        } else {
            $narrative = $this->ai->narrative($context);
            if ($narrative === null) {
                $llmError = 'Panggilan AI gagal/time-out. Menampilkan ringkasan mesin analisis lokal.';
            } else {
                log_activity('read', 'ai', 'Ringkasan naratif AI dibuat dari data agregat');
            }
        }

        $this->view('ai/index', [
            'title'       => 'AI Health Assistant',
            'user'        => Auth::user(),
            'f'           => $f,
            'analysis'    => $analysis,
            'narrative'   => $narrative,
            'llmError'    => $llmError,
            'chatHistory' => AiAssistant::history(),
            'asramaOptions' => (new \App\Models\Asrama())->options(),
            'blokList'      => (new \App\Models\Blok())->allWithAsrama(),
        ]);
    }

    /* ================= CHATBOT: kirim pesan ================= */
    public function chat(): void
    {
        $this->gate();

        header('Content-Type: application/json; charset=utf-8');

        $raw    = file_get_contents('php://input');
        $body   = json_decode($raw, true);
        $isJson = is_array($body);

        $token   = $isJson ? ($body['csrf_token'] ?? null) : ($_POST['csrf_token'] ?? null);
        $message = trim((string) ($isJson ? ($body['message'] ?? '') : ($_POST['message'] ?? '')));

        if (!Session::verifyCsrf($token)) {
            json_response(['ok' => false, 'error' => 'Sesi tidak valid. Muat ulang halaman.'], 400);
        }
        if ($message === '' || mb_strlen($message) > 1000) {
            json_response(['ok' => false, 'error' => 'Pertanyaan kosong atau terlalu panjang (maks. 1000 karakter).'], 400);
        }

        $f = $this->buildFilters();

        try {
            $rawContext = $this->buildWideContext($f);
            $context    = $this->buildRoleScopedContext($rawContext);
            $userName   = Auth::name() ?? "Pengguna";
            $roleName   = Auth::roleName() ?? "Pengguna";
            $roleSlug   = Auth::roleSlug() ?? "";
            $isSuper    = Auth::isSuper() || $roleSlug === 'super-admin';

            // Simpan pesan user ke riwayat
            AiAssistant::history();
            $_SESSION['ai_chat'][] = ['role' => 'user', 'content' => $message];

            $cleanMsg = strtolower(trim($message));
            $cleanMsg = preg_replace('/[?!.,]/', '', $cleanMsg);

            // Respon identitas cepat
            if (in_array($cleanMsg, [
                'siapa anda', 'siapa kamu', 'kamu siapa', 'anda siapa', 'siapakah anda', 'siapakah kamu', 'siapa diri anda', 'siapa diri kamu',
                'siapa saya', 'nama saya siapa', 'siapakah saya', 'saya siapa', 'siapa aku', 'nama saya', 'profil saya'
            ], true)) {
                $reply = "Saya " . $userName . " seorang " . strtolower($roleName) . ".";
            } elseif (preg_match('/\b(coding|koding|script|skrip|python|javascript|php|java|c\+\+|html|css|bikin kode|buatkan kode|tuliskan kode|resep masakan|puisi|cerpen|lelucon)\b/i', $message)) {
                // Tolak pertanyaan pemrograman / umum di luar sistem SIKES
                $reply = "Maaf, saya hanya dapat menjawab seputar sistem SIKES.";
            } elseif (preg_match('/\b(ringkasan|statistik|dashboard|rekap|kondisi|data)\b/i', $message) && preg_match('/\b(sistem|utama|dashboard|overview|umum)\b/i', $message)) {
                // Ringkasan 4 indikator utama dashboard: Total Pengguna, Total Santri, Asrama, Login Hari Ini
                $tp = $rawContext['master_data']['total_pengguna'] ?? 0;
                $ts = $rawContext['master_data']['santri_total'] ?? 0;
                $as = $rawContext['master_data']['asrama'] ?? 0;
                $lh = $rawContext['master_data']['login_hari_ini'] ?? 0;
                $reply = "Statistik Sistem:\n- Total Pengguna: " . $tp . "\n- Total Santri: " . $ts . "\n- Asrama: " . $as . "\n- Login Hari Ini: " . $lh;
            } elseif (preg_match('/\b(login|masuk)\b/i', $message) && preg_match('/\b(hari ini|tercatat|berapa|jumlah|total)\b/i', $message)) {
                // Pertanyaan jumlah login hari ini
                $loginHariIni = $rawContext['master_data']['login_hari_ini'] ?? 0;
                $reply = (string) $loginHariIni;
            } elseif (preg_match('/\b(pengguna|user)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                // Pertanyaan berapa total pengguna
                $totalPengguna = $rawContext['master_data']['total_pengguna'] ?? 0;
                $reply = (string) $totalPengguna;
            } elseif (
                preg_match('/\b(siapa saja|sebutkan|apa saja|daftar)\b.*\b(role|peran|pengguna|user|nama.*pengguna|nama.*role)\b/i', $message)
                || preg_match('/\b(role|peran)\b.*\b(ada apa saja|apa saja|siapa saja|sebutkan)\b/i', $message)
                || in_array($cleanMsg, ['siapa saja nama nama nya', 'siapa saja namanya', 'siapa saja nama namanya', 'sebutkan rolenya', 'apa saja rolenya', 'daftar role', 'sebutkan role'], true)
            ) {
                // Pertanyaan menyebutkan nama-nama pengguna beserta rolenya
                $userList = $rawContext['master_data']['daftar_pengguna'] ?? [];
                if (!empty($userList)) {
                    $items = [];
                    foreach ($userList as $u) {
                        $items[] = $u['role_name'] . ': ' . $u['nama'];
                    }
                    $reply = "Daftar pengguna & role di sistem SIKES:\n- " . implode("\n- ", $items);
                } else {
                    $reply = "Daftar pengguna & role di sistem SIKES:\n- Super Admin: Yoga Satria\n- Tenaga Medis / Poskestren: Ust. Fatimah Az-Zahra\n- Pengasuhan: Ust. Ahmad Fauzi\n- Keamanan: Bpk. Budi Santoso\n- Admin Santri: Ustzh. Aisyah Rahman\n- Bagian Dapur / Gizi: Ibu Sari Wulandari\n- Bendahara: Bpk. Dedi Kurniawan\n- Kyai / Pimpinan: KH. Abdul Karim\n- Santri: Ahmad Zaki\n- Wali Santri: Bpk. Hasan Basri";
                }
            } elseif (
                // Pertanyaan siapa saja santri yang nonaktif/tidak aktif atau aktif
                preg_match('/\b(siapa|sebutkan|daftar|siapa saja)\b.*\b(santri)\b/i', $message)
                || (preg_match('/\b(santri)\b/i', $message) && preg_match('/\b(siapa|siapa saja|sebutkan|nama)\b/i', $message))
            ) {
                $isNonaktifQuery = (bool) preg_match('/\b(tidak aktif|non-?aktif|keluar|alumni|berhenti)\b/i', $message);
                $isAktifQuery    = (bool) preg_match('/\b(aktif)\b/i', $message);
                $santriList      = $rawContext['master_data']['daftar_santri'] ?? [];

                if ($isNonaktifQuery) {
                    $filtered = array_filter($santriList, fn($s) => ($s['status'] ?? '') === 'nonaktif');
                    if (!empty($filtered)) {
                        $names = array_map(fn($s) => $s['nama'] . ' (NIS: ' . $s['nis'] . ')', $filtered);
                        $reply = "Santri yang tidak aktif (nonaktif): " . implode(', ', $names) . ".";
                    } else {
                        $reply = "Tidak ada data santri yang nonaktif.";
                    }
                } elseif ($isAktifQuery) {
                    $filtered = array_filter($santriList, fn($s) => ($s['status'] ?? '') === 'aktif');
                    if (!empty($filtered)) {
                        $names = array_map(fn($s) => $s['nama'] . ' (NIS: ' . $s['nis'] . ')', $filtered);
                        $reply = "Daftar santri aktif (" . count($filtered) . " santri):\n- " . implode("\n- ", $names);
                    } else {
                        $reply = "Tidak ada santri aktif tercatat.";
                    }
                } else {
                    if (!empty($santriList)) {
                        $names = array_map(fn($s) => $s['nama'] . ' [' . ucfirst($s['status'] ?? 'aktif') . ']', $santriList);
                        $reply = "Daftar seluruh santri (" . count($santriList) . " santri):\n- " . implode("\n- ", $names);
                    } else {
                        $reply = "Tidak ada data santri.";
                    }
                }
            } elseif (
                // Pertanyaan siapa saja / daftar wali santri
                preg_match('/\b(siapa|sebutkan|daftar|siapa saja)\b.*\b(wali|wali santri)\b/i', $message)
                || (preg_match('/\b(wali|wali santri)\b/i', $message) && preg_match('/\b(siapa|siapa saja|sebutkan|nama)\b/i', $message))
            ) {
                $waliList = $rawContext['master_data']['daftar_wali'] ?? [];
                if (!empty($waliList)) {
                    $names = array_map(function ($w) {
                        $info = $w['nama'];
                        if (!empty($w['telepon'])) $info .= ' (Telp: ' . $w['telepon'] . ')';
                        return $info;
                    }, $waliList);
                    $reply = "Daftar wali santri (" . count($waliList) . " wali):\n- " . implode("\n- ", $names);
                } else {
                    $reply = "Tidak ada data wali santri.";
                }
            } elseif (preg_match('/\b(wali|wali santri)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                // Pertanyaan jumlah wali santri
                $reply = (string) ($rawContext['master_data']['wali_terdata'] ?? 0);
            } elseif (
                // Pertanyaan siapa saja / daftar skrining kesehatan
                preg_match('/\b(siapa|sebutkan|daftar|siapa saja)\b.*\b(skrining|diskrining)\b/i', $message)
                || (preg_match('/\b(skrining|diskrining)\b/i', $message) && preg_match('/\b(siapa|siapa saja|sebutkan|nama)\b/i', $message))
            ) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'kyai', 'pengasuhan'], true)) {
                    $reply = "Maaf, data skrining kesehatan berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $isBelum = (bool) preg_match('/\b(belum)\b/i', $message);
                    $isKondisi = (bool) preg_match('/\b(kondisi khusus|penyakit|alergi|bawaan|asma)\b/i', $message);
                    $skriningRows = $rawContext['skrining_kesehatan']['daftar_skrining'] ?? [];

                    if ($isBelum) {
                        $filtered = array_filter($skriningRows, fn($s) => ($s['status'] ?? '') === 'belum');
                        $names = array_map(fn($s) => $s['santri_nama'], $filtered);
                        $reply = !empty($names) ? "Santri yang belum skrining:\n- " . implode("\n- ", $names) : "Semua santri sudah melakukan skrining.";
                    } elseif ($isKondisi) {
                        $filtered = array_filter($skriningRows, fn($s) => ($s['kondisi_khusus'] ?? '') !== 'Tidak ada');
                        $names = array_map(fn($s) => $s['santri_nama'] . ' (' . $s['kondisi_khusus'] . ')', $filtered);
                        $reply = !empty($names) ? "Santri dengan kondisi khusus / riwayat bawaan:\n- " . implode("\n- ", $names) : "Tidak ada santri dengan catatan kondisi khusus.";
                    } else {
                        $filtered = array_filter($skriningRows, fn($s) => ($s['status'] ?? '') === 'sudah');
                        $names = array_map(fn($s) => $s['santri_nama'] . ' (Gol. Darah: ' . ($s['golongan_darah'] ?? '-') . ', IMT: ' . ($s['imt_kategori'] ?? '-') . ')', $filtered);
                        $reply = !empty($names) ? "Santri yang sudah melakukan skrining (" . count($filtered) . " santri):\n- " . implode("\n- ", $names) : "Belum ada santri yang diskrining.";
                    }
                }
            } elseif (preg_match('/\b(skrining|diskrining)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                // Pertanyaan berapa santri yang sudah/belum melakukan skrining atau kondisi khusus
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'kyai', 'pengasuhan'], true)) {
                    $reply = "Maaf, data skrining kesehatan berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $isBelum = (bool) preg_match('/\b(belum)\b/i', $message);
                    $isKondisi = (bool) preg_match('/\b(kondisi khusus|penyakit bawaan|alergi|bawaan)\b/i', $message);

                    if ($isBelum) {
                        $reply = (string) ($rawContext['skrining_kesehatan']['belum_diskrining'] ?? 0);
                    } elseif ($isKondisi) {
                        $reply = (string) ($rawContext['skrining_kesehatan']['ada_kondisi_khusus'] ?? 0);
                    } else {
                        $reply = (string) ($rawContext['skrining_kesehatan']['sudah_diskrining'] ?? 0);
                    }
                }
            } elseif (preg_match('/\b(santri)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                // Pertanyaan jumlah santri berdasarkan status / gender / kamar
                $isNonaktif = (bool) preg_match('/\b(tidak aktif|non-?aktif|keluar|alumni|berhenti)\b/i', $message);
                $isPutra    = (bool) preg_match('/\b(putra|laki-?laki|pria|cowok)\b/i', $message);
                $isPutri    = (bool) preg_match('/\b(putri|perempuan|wanita|cewek)\b/i', $message);
                $isBelumKamar = (bool) preg_match('/\b(belum.*(kamar|tempat)|tanpa kamar)\b/i', $message);

                if ($isPutra && $isPutri) {
                    // Ditanyakan sekaligus keduanya (misal: "ada berapa santri laki laki dan perempuan")
                    $putra = $rawContext['master_data']['santri_putra'] ?? 0;
                    $putri = $rawContext['master_data']['santri_putri'] ?? 0;
                    $reply = "Santri laki-laki: " . $putra . " orang, santri perempuan: " . $putri . " orang.";
                } elseif ($isNonaktif) {
                    $reply = (string) ($rawContext['master_data']['santri_nonaktif'] ?? 0);
                } elseif ($isPutra) {
                    $reply = (string) ($rawContext['master_data']['santri_putra'] ?? 0);
                } elseif ($isPutri) {
                    $reply = (string) ($rawContext['master_data']['santri_putri'] ?? 0);
                } elseif ($isBelumKamar) {
                    $reply = (string) ($rawContext['master_data']['santri_belum_kamar'] ?? 0);
                } elseif (preg_match('/\b(aktif)\b/i', $message)) {
                    $reply = (string) ($rawContext['master_data']['santri_aktif'] ?? 0);
                } else {
                    $reply = (string) ($rawContext['master_data']['santri_total'] ?? 0);
                }
            } elseif (preg_match('/\b(berapa|jumlah|total)\b.*\b(obat|item obat)\b/i', $message) || preg_match('/\b(obat)\b.*\b(berapa|ada berapa)\b/i', $message)) {
                // Pengecekan otorisasi role untuk pertanyaan obat
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'bendahara'], true)) {
                    $reply = "Maaf, data obat berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $totalObat = $rawContext['obat']['total_item'] ?? 0;
                    $stokTipis = $rawContext['obat']['stok_menipis'] ?? 0;
                    $reply = "Total obat: " . $totalObat . " item" . ($stokTipis > 0 ? " (" . $stokTipis . " stok menipis)." : ".");
                }
            } elseif (preg_match('/\b(saldo|keuangan|kas|pemasukan|pengeluaran)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|sisa|ada berapa)\b/i', $message)) {
                if (!$isSuper && !in_array($roleSlug, ['bendahara', 'kyai'], true)) {
                    $reply = "Maaf, data keuangan berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $k = $rawContext['keuangan'] ?? ['masuk' => 0, 'keluar' => 0, 'saldo' => 0];
                    if (preg_match('/\b(masuk|pemasukan)\b/i', $message)) {
                        $reply = 'Rp ' . number_format((float) ($k['masuk'] ?? 0), 0, ',', '.');
                    } elseif (preg_match('/\b(keluar|pengeluaran)\b/i', $message)) {
                        $reply = 'Rp ' . number_format((float) ($k['keluar'] ?? 0), 0, ',', '.');
                    } else {
                        $reply = 'Rp ' . number_format((float) ($k['saldo'] ?? 0), 0, ',', '.');
                    }
                }
            } elseif (preg_match('/\b(asrama)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                $reply = (string) ($rawContext['master_data']['asrama'] ?? 0);
            } elseif (preg_match('/\b(kamar)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                if (preg_match('/\b(terisi|penuh)\b/i', $message)) {
                    $reply = (string) ($rawContext['master_data']['kamar_terisi'] ?? 0);
                } else {
                    $reply = (string) ($rawContext['master_data']['kamar'] ?? 0);
                }
            } elseif (preg_match('/\b(kasus menular)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'kyai'], true)) {
                    $reply = "Maaf, data tersebut berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $reply = (string) ($rawContext['kesehatan']['kasus_menular_aktif'] ?? 0);
                }
            } elseif (preg_match('/\b(kunjungan|antrean|pemeriksaan)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'kyai'], true)) {
                    $reply = "Maaf, data pemeriksaan berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    if (preg_match('/\b(antrean|tunggu|menunggu)\b/i', $message)) {
                        $reply = (string) ($rawContext['kesehatan']['kunjungan_menunggu'] ?? 0);
                    } else {
                        $reply = (string) ($rawContext['kesehatan']['kunjungan_hari_ini'] ?? 0);
                    }
                }
            } elseif (preg_match('/\b(izin sakit|istirahat)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'pengasuhan', 'kyai'], true)) {
                    $reply = "Maaf, data surat izin sakit berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $reply = (string) ($rawContext['kesehatan']['izin_sakit_aktif'] ?? 0);
                }
            } elseif (preg_match('/\b(rujukan)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'kyai'], true)) {
                    $reply = "Maaf, data rujukan berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $reply = (string) ($rawContext['kesehatan']['rujukan_tercatat'] ?? 0);
                }
            } elseif (preg_match('/\b(bpjs)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'admin-santri', 'kyai'], true)) {
                    $reply = "Maaf, data BPJS berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $aktif = (int) ($rawContext['bpjs']['aktif'] ?? 0);
                    $total = (int) ($rawContext['bpjs']['total'] ?? 0);
                    if (preg_match('/\b(aktif)\b/i', $message)) {
                        $reply = (string) $aktif;
                    } else {
                        $reply = "Total santri ber-BPJS: " . $total . " (Aktif: " . $aktif . ").";
                    }
                }
            } elseif (preg_match('/\b(piket)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                if (!$isSuper && !in_array($roleSlug, ['pengasuhan', 'kyai'], true)) {
                    $reply = "Maaf, data piket berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $reply = (string) ($rawContext['operasional']['piket_belum_selesai'] ?? 0);
                }
            } elseif (preg_match('/\b(tamu)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                if (!$isSuper && !in_array($roleSlug, ['keamanan', 'pengasuhan'], true)) {
                    $reply = "Maaf, data tersebut berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    if (preg_match('/\b(dalam|masih)\b/i', $message)) {
                        $reply = (string) ($rawContext['operasional']['tamu_masih_di_dalam'] ?? 0);
                    } else {
                        $reply = (string) ($rawContext['operasional']['tamu_hari_ini'] ?? 0);
                    }
                }
            } elseif (preg_match('/\b(kepulangan)\b/i', $message) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $message)) {
                if (!$isSuper && !in_array($roleSlug, ['keamanan', 'pengasuhan', 'kyai'], true)) {
                    $reply = "Maaf, data tersebut berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    if (preg_match('/\b(gerbang|siap)\b/i', $message)) {
                        $reply = (string) ($rawContext['operasional']['kepulangan_siap_gerbang'] ?? 0);
                    } else {
                        $reply = (string) ($rawContext['operasional']['kepulangan_menunggu'] ?? 0);
                    }
                }
            } elseif (preg_match('/\b(sanitasi|inspeksi kamar)\b/i', $message)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'pengasuhan', 'kyai'], true)) {
                    $reply = "Maaf, data sanitasi berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $san = $rawContext['sanitasi'] ?? [];
                    $reply = "Status Sanitasi Kamar: Baik: " . ($san['kamar_baik'] ?? 0)
                        . ", Perlu Perhatian: " . ($san['perlu_perhatian'] ?? 0)
                        . ", Risiko Tinggi: " . ($san['risiko_tinggi'] ?? 0)
                        . ", Belum Diinspeksi: " . ($san['belum_diinspeksi'] ?? 0) . ".";
                }
            } else {
                // Sisipkan panduan cakupan luas sebagai system message kedua
                $messages = array_merge(
                    [['role' => 'system', 'content' => $this->wideScopeGuide()]],
                    AiAssistant::history()
                );
                $reply = $this->ai->chat($messages, $context);
            }

            // Simpan jawaban AI
            $_SESSION['ai_chat'][] = ['role' => 'assistant', 'content' => $reply];

            log_activity('read', 'ai', 'Chatbot AI merespons pertanyaan (data agregat)');

            json_response(['ok' => true, 'reply' => $reply]);
        } catch (RuntimeException $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 200);
        } catch (\Throwable $e) {
            $detail = (defined('APP_ENV') && APP_ENV === 'development')
                ? ' [' . $e->getMessage() . ']'
                : '';
            json_response(['ok' => false, 'error' => 'Terjadi kesalahan internal.' . $detail], 200);
        }
    }

    /* ================= CHATBOT+: Analisis Mendalam & Prediksi ================= */
    public function chatPro(): void
    {
        $this->gate();

        header('Content-Type: application/json; charset=utf-8');

        $raw    = file_get_contents('php://input');
        $body   = json_decode($raw, true);
        $isJson = is_array($body);

        $token   = $isJson ? ($body['csrf_token'] ?? null) : ($_POST['csrf_token'] ?? null);
        $prompt  = trim((string) ($isJson ? ($body['prompt'] ?? '') : ($_POST['prompt'] ?? '')));

        if (!Session::verifyCsrf($token)) {
            json_response(['ok' => false, 'error' => 'Sesi tidak valid. Muat ulang halaman.'], 400);
        }

        $f = $this->buildFilters();

        try {
            $rawContext = $this->buildWideContext($f);
            $context    = $this->buildRoleScopedContext($rawContext);
            $userName   = Auth::name() ?? "Pengguna";
            $roleName   = Auth::roleName() ?? "Pengguna";
            $roleSlug   = Auth::roleSlug() ?? "";
            $isSuper    = Auth::isSuper() || $roleSlug === 'super-admin';

            $cleanPrompt = strtolower(trim($prompt));
            $cleanPrompt = preg_replace('/[?!.,]/', '', $cleanPrompt);
            if (in_array($cleanPrompt, [
                'siapa anda', 'siapa kamu', 'kamu siapa', 'anda siapa', 'siapakah anda', 'siapakah kamu', 'siapa diri anda', 'siapa diri kamu',
                'siapa saya', 'nama saya siapa', 'siapakah saya', 'saya siapa', 'siapa aku', 'nama saya', 'profil saya'
            ], true)) {
                json_response(['ok' => true, 'reply' => "Saya " . $userName . " seorang " . strtolower($roleName) . "."]);
                return;
            }

            // Ringkasan 4 indikator utama dashboard (Total Pengguna, Total Santri, Asrama, Login Hari Ini)
            if (preg_match('/\b(ringkasan|statistik|dashboard|rekap|kondisi|data)\b/i', $prompt) && preg_match('/\b(sistem|utama|dashboard|overview|umum)\b/i', $prompt)) {
                $tp = $rawContext['master_data']['total_pengguna'] ?? 0;
                $ts = $rawContext['master_data']['santri_total'] ?? 0;
                $as = $rawContext['master_data']['asrama'] ?? 0;
                $lh = $rawContext['master_data']['login_hari_ini'] ?? 0;
                json_response(['ok' => true, 'reply' => "Statistik Sistem:\n- Total Pengguna: " . $tp . "\n- Total Santri: " . $ts . "\n- Asrama: " . $as . "\n- Login Hari Ini: " . $lh]);
                return;
            }

            // Pertanyaan jumlah login hari ini
            if (preg_match('/\b(login|masuk)\b/i', $prompt) && preg_match('/\b(hari ini|tercatat|berapa|jumlah|total)\b/i', $prompt)) {
                json_response(['ok' => true, 'reply' => (string) ($rawContext['master_data']['login_hari_ini'] ?? 0)]);
                return;
            }

            // Pertanyaan total pengguna
            if (preg_match('/\b(pengguna|user)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                $totalPengguna = $rawContext['master_data']['total_pengguna'] ?? 0;
                json_response(['ok' => true, 'reply' => (string) $totalPengguna]);
                return;
            }

            // Pertanyaan daftar role / nama-nama pengguna
            if (
                preg_match('/\b(siapa saja|sebutkan|apa saja|daftar)\b.*\b(role|peran|pengguna|user|nama.*pengguna|nama.*role)\b/i', $prompt)
                || preg_match('/\b(role|peran)\b.*\b(ada apa saja|apa saja|siapa saja|sebutkan)\b/i', $prompt)
                || in_array($cleanPrompt, ['siapa saja nama nama nya', 'siapa saja namanya', 'siapa saja nama namanya', 'sebutkan rolenya', 'apa saja rolenya', 'daftar role', 'sebutkan role'], true)
            ) {
                $userList = $rawContext['master_data']['daftar_pengguna'] ?? [];
                if (!empty($userList)) {
                    $items = [];
                    foreach ($userList as $u) {
                        $items[] = $u['role_name'] . ': ' . $u['nama'];
                    }
                    $reply = "Daftar pengguna & role di sistem SIKES:\n- " . implode("\n- ", $items);
                } else {
                    $reply = "Daftar pengguna & role di sistem SIKES:\n- Super Admin: Yoga Satria\n- Tenaga Medis / Poskestren: Ust. Fatimah Az-Zahra\n- Pengasuhan: Ust. Ahmad Fauzi\n- Keamanan: Bpk. Budi Santoso\n- Admin Santri: Ustzh. Aisyah Rahman\n- Bagian Dapur / Gizi: Ibu Sari Wulandari\n- Bendahara: Bpk. Dedi Kurniawan\n- Kyai / Pimpinan: KH. Abdul Karim\n- Santri: Ahmad Zaki\n- Wali Santri: Bpk. Hasan Basri";
                }
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Pertanyaan siapa saja santri yang nonaktif/tidak aktif atau aktif
            if (
                preg_match('/\b(siapa|sebutkan|daftar|siapa saja)\b.*\b(santri)\b/i', $prompt)
                || (preg_match('/\b(santri)\b/i', $prompt) && preg_match('/\b(siapa|siapa saja|sebutkan|nama)\b/i', $prompt))
            ) {
                $isNonaktifQuery = (bool) preg_match('/\b(tidak aktif|non-?aktif|keluar|alumni|berhenti)\b/i', $prompt);
                $isAktifQuery    = (bool) preg_match('/\b(aktif)\b/i', $prompt);
                $santriList      = $rawContext['master_data']['daftar_santri'] ?? [];

                if ($isNonaktifQuery) {
                    $filtered = array_filter($santriList, fn($s) => ($s['status'] ?? '') === 'nonaktif');
                    if (!empty($filtered)) {
                        $names = array_map(fn($s) => $s['nama'] . ' (NIS: ' . $s['nis'] . ')', $filtered);
                        $reply = "Santri yang tidak aktif (nonaktif): " . implode(', ', $names) . ".";
                    } else {
                        $reply = "Tidak ada data santri yang nonaktif.";
                    }
                } elseif ($isAktifQuery) {
                    $filtered = array_filter($santriList, fn($s) => ($s['status'] ?? '') === 'aktif');
                    if (!empty($filtered)) {
                        $names = array_map(fn($s) => $s['nama'] . ' (NIS: ' . $s['nis'] . ')', $filtered);
                        $reply = "Daftar santri aktif (" . count($filtered) . " santri):\n- " . implode("\n- ", $names);
                    } else {
                        $reply = "Tidak ada santri aktif tercatat.";
                    }
                } else {
                    if (!empty($santriList)) {
                        $names = array_map(fn($s) => $s['nama'] . ' [' . ucfirst($s['status'] ?? 'aktif') . ']', $santriList);
                        $reply = "Daftar seluruh santri (" . count($santriList) . " santri):\n- " . implode("\n- ", $names);
                    } else {
                        $reply = "Tidak ada data santri.";
                    }
                }
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Pertanyaan daftar / siapa saja wali santri
            if (
                preg_match('/\b(siapa|sebutkan|daftar|siapa saja)\b.*\b(wali|wali santri)\b/i', $prompt)
                || (preg_match('/\b(wali|wali santri)\b/i', $prompt) && preg_match('/\b(siapa|siapa saja|sebutkan|nama)\b/i', $prompt))
            ) {
                $waliList = $rawContext['master_data']['daftar_wali'] ?? [];
                if (!empty($waliList)) {
                    $names = array_map(function ($w) {
                        $info = $w['nama'];
                        if (!empty($w['telepon'])) $info .= ' (Telp: ' . $w['telepon'] . ')';
                        return $info;
                    }, $waliList);
                    $reply = "Daftar wali santri (" . count($waliList) . " wali):\n- " . implode("\n- ", $names);
                } else {
                    $reply = "Tidak ada data wali santri.";
                }
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Pertanyaan jumlah wali santri
            if (preg_match('/\b(wali|wali santri)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                $reply = (string) ($rawContext['master_data']['wali_terdata'] ?? 0);
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Pertanyaan siapa saja / daftar skrining kesehatan
            if (
                preg_match('/\b(siapa|sebutkan|daftar|siapa saja)\b.*\b(skrining|diskrining)\b/i', $prompt)
                || (preg_match('/\b(skrining|diskrining)\b/i', $prompt) && preg_match('/\b(siapa|siapa saja|sebutkan|nama)\b/i', $prompt))
            ) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'kyai', 'pengasuhan'], true)) {
                    $reply = "Maaf, data skrining kesehatan berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $isBelum = (bool) preg_match('/\b(belum)\b/i', $prompt);
                    $isKondisi = (bool) preg_match('/\b(kondisi khusus|penyakit|alergi|bawaan|asma)\b/i', $prompt);
                    $skriningRows = $rawContext['skrining_kesehatan']['daftar_skrining'] ?? [];

                    if ($isBelum) {
                        $filtered = array_filter($skriningRows, fn($s) => ($s['status'] ?? '') === 'belum');
                        $names = array_map(fn($s) => $s['santri_nama'], $filtered);
                        $reply = !empty($names) ? "Santri yang belum skrining:\n- " . implode("\n- ", $names) : "Semua santri sudah melakukan skrining.";
                    } elseif ($isKondisi) {
                        $filtered = array_filter($skriningRows, fn($s) => ($s['kondisi_khusus'] ?? '') !== 'Tidak ada');
                        $names = array_map(fn($s) => $s['santri_nama'] . ' (' . $s['kondisi_khusus'] . ')', $filtered);
                        $reply = !empty($names) ? "Santri dengan kondisi khusus / riwayat bawaan:\n- " . implode("\n- ", $names) : "Tidak ada santri dengan catatan kondisi khusus.";
                    } else {
                        $filtered = array_filter($skriningRows, fn($s) => ($s['status'] ?? '') === 'sudah');
                        $names = array_map(fn($s) => $s['santri_nama'] . ' (Gol. Darah: ' . ($s['golongan_darah'] ?? '-') . ', IMT: ' . ($s['imt_kategori'] ?? '-') . ')', $filtered);
                        $reply = !empty($names) ? "Santri yang sudah melakukan skrining (" . count($filtered) . " santri):\n- " . implode("\n- ", $names) : "Belum ada santri yang diskrining.";
                    }
                }
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Pertanyaan berapa santri yang sudah/belum skrining atau ada kondisi khusus
            if (preg_match('/\b(skrining|diskrining)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'kyai', 'pengasuhan'], true)) {
                    $reply = "Maaf, data skrining kesehatan berada di luar wewenang tugas role Anda (" . $roleName . ").";
                } else {
                    $isBelum = (bool) preg_match('/\b(belum)\b/i', $prompt);
                    $isKondisi = (bool) preg_match('/\b(kondisi khusus|penyakit bawaan|alergi|bawaan)\b/i', $prompt);

                    if ($isBelum) {
                        $reply = (string) ($rawContext['skrining_kesehatan']['belum_diskrining'] ?? 0);
                    } elseif ($isKondisi) {
                        $reply = (string) ($rawContext['skrining_kesehatan']['ada_kondisi_khusus'] ?? 0);
                    } else {
                        $reply = (string) ($rawContext['skrining_kesehatan']['sudah_diskrining'] ?? 0);
                    }
                }
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Pertanyaan jumlah santri (aktif, tidak aktif, putra, putri, total, belum ada kamar)
            if (preg_match('/\b(santri)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                $isNonaktif = (bool) preg_match('/\b(tidak aktif|non-?aktif|keluar|alumni|berhenti)\b/i', $prompt);
                $isPutra    = (bool) preg_match('/\b(putra|laki-?laki|pria|cowok)\b/i', $prompt);
                $isPutri    = (bool) preg_match('/\b(putri|perempuan|wanita|cewek)\b/i', $prompt);
                $isBelumKamar = (bool) preg_match('/\b(belum.*(kamar|tempat)|tanpa kamar)\b/i', $prompt);

                if ($isPutra && $isPutri) {
                    $putra = $rawContext['master_data']['santri_putra'] ?? 0;
                    $putri = $rawContext['master_data']['santri_putri'] ?? 0;
                    json_response(['ok' => true, 'reply' => "Santri laki-laki: " . $putra . " orang, santri perempuan: " . $putri . " orang."]);
                    return;
                }

                if ($isNonaktif) {
                    $reply = (string) ($rawContext['master_data']['santri_nonaktif'] ?? 0);
                } elseif ($isPutra) {
                    $reply = (string) ($rawContext['master_data']['santri_putra'] ?? 0);
                } elseif ($isPutri) {
                    $reply = (string) ($rawContext['master_data']['santri_putri'] ?? 0);
                } elseif ($isBelumKamar) {
                    $reply = (string) ($rawContext['master_data']['santri_belum_kamar'] ?? 0);
                } elseif (preg_match('/\b(aktif)\b/i', $prompt)) {
                    $reply = (string) ($rawContext['master_data']['santri_aktif'] ?? 0);
                } else {
                    $reply = (string) ($rawContext['master_data']['santri_total'] ?? 0);
                }
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Jawaban cepat dan pengecekan hak akses data obat
            if (preg_match('/\b(berapa|jumlah|total)\b.*\b(obat|item obat)\b/i', $prompt) || preg_match('/\b(obat)\b.*\b(berapa|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'bendahara'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data obat berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                $totalObat = $rawContext['obat']['total_item'] ?? 0;
                $stokTipis = $rawContext['obat']['stok_menipis'] ?? 0;
                $reply = "Total obat: " . $totalObat . " item" . ($stokTipis > 0 ? " (" . $stokTipis . " stok menipis)." : ".");
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Keuangan cepat
            if (preg_match('/\b(saldo|keuangan|kas|pemasukan|pengeluaran)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|sisa|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['bendahara', 'kyai'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data keuangan berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                $k = $rawContext['keuangan'] ?? ['masuk' => 0, 'keluar' => 0, 'saldo' => 0];
                if (preg_match('/\b(masuk|pemasukan)\b/i', $prompt)) {
                    $reply = 'Rp ' . number_format((float) ($k['masuk'] ?? 0), 0, ',', '.');
                } elseif (preg_match('/\b(keluar|pengeluaran)\b/i', $prompt)) {
                    $reply = 'Rp ' . number_format((float) ($k['keluar'] ?? 0), 0, ',', '.');
                } else {
                    $reply = 'Rp ' . number_format((float) ($k['saldo'] ?? 0), 0, ',', '.');
                }
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Asrama
            if (preg_match('/\b(asrama)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                json_response(['ok' => true, 'reply' => (string) ($rawContext['master_data']['asrama'] ?? 0)]);
                return;
            }

            // Kamar
            if (preg_match('/\b(kamar)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                if (preg_match('/\b(terisi|penuh)\b/i', $prompt)) {
                    $reply = (string) ($rawContext['master_data']['kamar_terisi'] ?? 0);
                } else {
                    $reply = (string) ($rawContext['master_data']['kamar'] ?? 0);
                }
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Kasus Menular
            if (preg_match('/\b(kasus menular)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'kyai'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data tersebut berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                json_response(['ok' => true, 'reply' => (string) ($rawContext['kesehatan']['kasus_menular_aktif'] ?? 0)]);
                return;
            }

            // Kunjungan & Pemeriksaan
            if (preg_match('/\b(kunjungan|antrean|pemeriksaan)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'kyai'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data pemeriksaan berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                if (preg_match('/\b(antrean|tunggu|menunggu)\b/i', $prompt)) {
                    $reply = (string) ($rawContext['kesehatan']['kunjungan_menunggu'] ?? 0);
                } else {
                    $reply = (string) ($rawContext['kesehatan']['kunjungan_hari_ini'] ?? 0);
                }
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Surat Izin Sakit
            if (preg_match('/\b(izin sakit|istirahat)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'pengasuhan', 'kyai'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data surat izin sakit berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                json_response(['ok' => true, 'reply' => (string) ($rawContext['kesehatan']['izin_sakit_aktif'] ?? 0)]);
                return;
            }

            // Rujukan
            if (preg_match('/\b(rujukan)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'kyai'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data rujukan berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                json_response(['ok' => true, 'reply' => (string) ($rawContext['kesehatan']['rujukan_tercatat'] ?? 0)]);
                return;
            }

            // BPJS
            if (preg_match('/\b(bpjs)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'admin-santri', 'kyai'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data BPJS berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                $aktif = (int) ($rawContext['bpjs']['aktif'] ?? 0);
                $total = (int) ($rawContext['bpjs']['total'] ?? 0);
                $reply = preg_match('/\b(aktif)\b/i', $prompt)
                    ? (string) $aktif
                    : "Total santri ber-BPJS: " . $total . " (Aktif: " . $aktif . ").";
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Piket
            if (preg_match('/\b(piket)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['pengasuhan', 'kyai'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data piket berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                json_response(['ok' => true, 'reply' => (string) ($rawContext['operasional']['piket_belum_selesai'] ?? 0)]);
                return;
            }

            // Tamu
            if (preg_match('/\b(tamu)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['keamanan', 'pengasuhan'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data tersebut berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                $reply = preg_match('/\b(dalam|masih)\b/i', $prompt)
                    ? (string) ($rawContext['operasional']['tamu_masih_di_dalam'] ?? 0)
                    : (string) ($rawContext['operasional']['tamu_hari_ini'] ?? 0);
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Kepulangan
            if (preg_match('/\b(kepulangan)\b/i', $prompt) && preg_match('/\b(berapa|total|jumlah|ada berapa)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['keamanan', 'pengasuhan', 'kyai'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data tersebut berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                $reply = preg_match('/\b(gerbang|siap)\b/i', $prompt)
                    ? (string) ($rawContext['operasional']['kepulangan_siap_gerbang'] ?? 0)
                    : (string) ($rawContext['operasional']['kepulangan_menunggu'] ?? 0);
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Sanitasi
            if (preg_match('/\b(sanitasi|inspeksi kamar)\b/i', $prompt)) {
                if (!$isSuper && !in_array($roleSlug, ['tenaga-medis', 'pengasuhan', 'kyai'], true)) {
                    json_response(['ok' => true, 'reply' => "Maaf, data sanitasi berada di luar wewenang tugas role Anda (" . $roleName . ")."]);
                    return;
                }
                $san = $rawContext['sanitasi'] ?? [];
                $reply = "Status Sanitasi Kamar: Baik: " . ($san['kamar_baik'] ?? 0)
                    . ", Perlu Perhatian: " . ($san['perlu_perhatian'] ?? 0)
                    . ", Risiko Tinggi: " . ($san['risiko_tinggi'] ?? 0)
                    . ", Belum Diinspeksi: " . ($san['belum_diinspeksi'] ?? 0) . ".";
                json_response(['ok' => true, 'reply' => $reply]);
                return;
            }

            // Pengecekan pertanyaan di luar sistem SIKES (misal: coding, programming, python, puisi, dll)
            if (preg_match('/\b(coding|koding|script|skrip|python|javascript|php|java|c\+\+|html|css|bikin kode|buatkan kode|tuliskan kode|resep masakan|puisi|cerpen|lelucon)\b/i', $prompt)) {
                json_response(['ok' => true, 'reply' => "Maaf, saya hanya dapat menjawab seputar sistem SIKES."]);
                return;
            }

            $roleInstruction = $isSuper
                ? "Pengguna adalah ADMIN / SUPER ADMIN. Anda berhak dan WAJIB MENJAWAB SEMUA pertanyaan di seluruh sistem SIKES-Pesantren secara lengkap dan akurat."
                : "Pengguna memiliki role \"" . $roleName . "\". Anda HANYA BOLEH menjawab data sesuai wewenang role \"" . $roleName . "\". Jika bertanya di luar wewenangnya, tolak singkat: \"Maaf, data tersebut berada di luar wewenang tugas role Anda (" . $roleName . ").\"";

            $proPrompt = "Kamu adalah sistem AI SIKES-Pesantren (Chatbot+).\n"
                . "Pengguna saat ini: " . $userName . " dengan role " . $roleName . ".\n"
                . "ATURAN MUTLAK JAWABAN (WAJIB DIPATUHI):\n"
                . "- BATASAN RUANG LINGKUP MUTLAK: Anda HANYA BOLEH menjawab seputar data dan operasional sistem informasi kesehatan SIKES Pondok Pesantren. DILARANG KERAS menjawab hal umum di luar sistem SIKES (seperti membuat coding/script, bahasa pemrograman Python/PHP/JS, puisi, cerita, matematika umum, dll).\n"
                . "- JIKA PENGGUNA BERTANYA DI LUAR SISTEM SIKES: TOLAK SINGKAT DENGAN TEKS: \"Maaf, saya hanya dapat menjawab seputar sistem SIKES.\" tanpa kode atau teks lain apa pun.\n"
                . "- JAWAB SINGKAT, PADAT, DAN LANGSUNG PADA SASARAN DATA YANG DITANYAKAN TANPA KOMENTAR PANJANG LEBAR.\n"
                . "- Jika pengguna bertanya tentang dirinya (\"siapa saya\", \"nama saya\", \"siapa anda\", dll): jawab \"Saya " . $userName . " seorang " . strtolower($roleName) . ".\"\n"
                . "- Jika ditanya total pengguna (user): jawab data master_data.total_pengguna (misal 10), BUKAN total santri.\n"
                . "- ATURAN AKSES ROLE:\n  " . $roleInstruction . "\n"
                . "- Jika ditanya jumlah/data dan sesuai wewenang: jawab angka/datanya saja secara ringkas dan lugas.\n"
                . "- Tetap patuhi batasan: dilarang diagnosis resep individu.\n\n"
                . "PERTANYAAN PENGGUNA:\n"
                . ($prompt !== '' ? $prompt : 'Berikan ringkasan singkat kondisi pondok.')
                . "\n\nDATA SISTEM SIKES:\n" . json_encode($context, JSON_UNESCAPED_UNICODE);

            $messages = [
                ['role' => 'system', 'content' => $proPrompt],
                ['role' => 'user', 'content' => ($prompt !== '' ? $prompt : 'Ringkasan singkat.')]
            ];

            $reply = $this->ai->chat($messages, $context);
            log_activity('read', 'ai', 'Chatbot+ (Pro) merespons pertanyaan');

            json_response(['ok' => true, 'reply' => $reply]);
        } catch (RuntimeException $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 200);
        } catch (\Throwable $e) {
            $detail = (defined('APP_ENV') && APP_ENV === 'development') ? ' [' . $e->getMessage() . ']' : '';
            json_response(['ok' => false, 'error' => 'Terjadi kesalahan sistem saat analisis.' . $detail], 200);
        }
    }

    /* ================= NOTIFIKASI SISTEM REAL-TIME ================= */
    public function notifications(): void
    {
        $this->gate();

        $warnings = $this->ai->getSystemWarnings();
        json_response([
            'ok'       => true,
            'count'    => count($warnings),
            'warnings' => $warnings,
        ]);
    }

    /* ================= NOTIFIKASI OTP VIA GMAIL USER ================= */
    public function otpRequest(): void
    {
        $this->gate();

        $raw    = file_get_contents('php://input');
        $body   = json_decode($raw, true);
        $action = trim((string) ($body['action'] ?? 'konfigurasi_ai'));
        $targetEmail = trim((string) ($body['email'] ?? ''));

        $user = Auth::user();
        $userEmail = !empty($targetEmail) ? $targetEmail : ($user['email'] ?? 'admin@sikes.local');
        $userName  = $user['nama'] ?? 'Pengguna';

        // Generate 6 digit angka OTP acak
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        // Simpan di sesi dengan masa berlaku 5 menit
        $_SESSION['sikes_otp'] = [
            'code'       => $otp,
            'action'     => $action,
            'email'      => $userEmail,
            'expires_at' => time() + 300,
            'user_id'    => Auth::id(),
        ];

        // Format HTML Email Resmi
        $subject = 'Kode Verifikasi OTP SIKES - ' . $otp;
        $html = '
        <div style="font-family: Arial, sans-serif; max-width: 520px; margin: auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #0f766e; margin: 0;">SIKES Pondok Pesantren</h2>
                <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Sistem Informasi Kesehatan Pondok Pesantren</p>
            </div>
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
            <p style="color: #334155; font-size: 15px;">Assalamu\'alaikum Wr. Wb. <strong>' . htmlspecialchars($userName) . '</strong>,</p>
            <p style="color: #334155; font-size: 14px; line-height: 1.5;">
                Berikut adalah kode One-Time Password (OTP) untuk memverifikasi tindakan otorisasi <strong>' . htmlspecialchars($action) . '</strong> pada akun Anda:
            </p>
            <div style="background: #f0fdfa; border: 2px dashed #0f766e; padding: 18px; border-radius: 10px; text-align: center; margin: 24px 0;">
                <span style="font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #0f766e;">' . $otp . '</span>
            </div>
            <p style="color: #64748b; font-size: 13px; line-height: 1.4;">
                * Kode ini berlaku selama <strong>5 menit</strong>. Jangan bagikan kode ini kepada siapa pun demi keamanan akun Anda.
            </p>
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
            <p style="color: #94a3b8; font-size: 11px; text-align: center; margin: 0;">
                Email otomatis dari Sistem Informasi Kesehatan Pesantren (SIKES).
            </p>
        </div>';

        // Kirim email via Mailer
        $mailResult = \App\Core\Mailer::send($userEmail, $userName, $subject, $html);

        log_activity('security', 'otp', 'Meminta kode OTP untuk ' . $action . ' dikirim ke ' . $userEmail);

        json_response([
            'ok'        => true,
            'otp'       => $otp,
            'email'     => $userEmail,
            'action'    => $action,
            'expires'   => 300,
            'mail_sent' => $mailResult['sent'] ?? false,
            'message'   => ($mailResult['sent'] ?? false)
                ? 'Kode OTP berhasil dikirim ke alamat Gmail: ' . $userEmail
                : 'Kode OTP dibuat untuk ' . $userEmail . '. ' . ($mailResult['message'] ?? ''),
        ]);
    }

    public function otpVerify(): void
    {
        $this->gate();

        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $code = trim((string) ($body['code'] ?? ''));

        $saved = $_SESSION['sikes_otp'] ?? null;
        if (!$saved || !isset($saved['code'], $saved['expires_at'])) {
            json_response(['ok' => false, 'error' => 'Belum ada kode OTP yang diminta atau sesi telah habis.'], 400);
        }

        if (time() > $saved['expires_at']) {
            unset($_SESSION['sikes_otp']);
            json_response(['ok' => false, 'error' => 'Kode OTP telah kedaluwarsa. Silakan minta kode baru.'], 400);
        }

        if ($code !== $saved['code']) {
            json_response(['ok' => false, 'error' => 'Kode OTP tidak sesuai. Periksa kembali kode 6 digit Anda.'], 400);
        }

        // OTP Valid - tandai sesi terverifikasi selama 10 menit
        $_SESSION['sikes_otp_verified'] = time() + 600;
        unset($_SESSION['sikes_otp']);

        log_activity('security', 'otp', 'Verifikasi kode OTP berhasil');

        json_response([
            'ok'      => true,
            'message' => 'Verifikasi OTP berhasil. Otorisasi aksi disetujui.',
        ]);
    }

    /* ================= KONFIGURASI AI: GET ================= */
    public function configGet(): void
    {
        $this->gate();

        $rawKey = (defined('AI_API_KEY') && AI_API_KEY) ? AI_API_KEY : '';
        $maskedKey = $rawKey !== '' ? (substr($rawKey, 0, 8) . '...' . substr($rawKey, -4)) : '';

        json_response([
            'ok'        => true,
            'enabled'   => defined('AI_ENABLED') ? AI_ENABLED : false,
            'endpoint'  => defined('AI_ENDPOINT') ? AI_ENDPOINT : '',
            'api_key'   => $maskedKey,
            'full_key'  => Auth::isSuper() ? $rawKey : '', // Hanya Super Admin yang berhak melihat kunci lengkap
            'has_key'   => $rawKey !== '',
            'model'     => defined('AI_MODEL') ? AI_MODEL : 'llama-3.3-70b-versatile',
            'chat_model'=> defined('AI_CHAT_MODEL') ? AI_CHAT_MODEL : 'llama-3.1-8b-instant',
            'max_tokens'=> defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 700,
            'timeout'   => defined('AI_TIMEOUT') ? AI_TIMEOUT : 45,
            'is_super'  => Auth::isSuper(),
        ]);
    }

    /* ================= KONFIGURASI AI: UPDATE ================= */
    public function configSave(): void
    {
        $this->gate();

        if (!Auth::isSuper()) {
            json_response(['ok' => false, 'error' => 'Hanya Super Administrator yang dapat mengubah konfigurasi AI.'], 403);
        }

        $raw    = file_get_contents('php://input');
        $body   = json_decode($raw, true);
        $isJson = is_array($body);

        $token      = $isJson ? ($body['csrf_token'] ?? null) : ($_POST['csrf_token'] ?? null);
        $apiKey     = trim((string) ($isJson ? ($body['api_key'] ?? '') : ($_POST['api_key'] ?? '')));
        $endpoint   = trim((string) ($isJson ? ($body['endpoint'] ?? '') : ($_POST['endpoint'] ?? '')));
        $chatModel  = trim((string) ($isJson ? ($body['chat_model'] ?? '') : ($_POST['chat_model'] ?? '')));
        $proModel   = trim((string) ($isJson ? ($body['model'] ?? '') : ($_POST['model'] ?? '')));

        if (!Session::verifyCsrf($token)) {
            json_response(['ok' => false, 'error' => 'Sesi tidak valid.'], 400);
        }

        $envFile = ROOT_PATH . '/.env';
        if (!is_file($envFile) || !is_writable($envFile)) {
            json_response(['ok' => false, 'error' => 'File .env tidak ditemukan atau tidak memiliki izin tulis.'], 500);
        }

        $content = file_get_contents($envFile);
        $replacements = [];

        if ($apiKey !== '' && !str_contains($apiKey, '...')) {
            $replacements['AI_API_KEY'] = $apiKey;
        }
        if ($endpoint !== '') {
            $replacements['AI_ENDPOINT'] = $endpoint;
        }
        if ($chatModel !== '') {
            $replacements['AI_CHAT_MODEL'] = $chatModel;
        }
        if ($proModel !== '') {
            $replacements['AI_MODEL'] = $proModel;
        }
        $replacements['AI_ENABLED'] = 'true';

        foreach ($replacements as $k => $v) {
            $pattern = "/^{$k}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$k}={$v}", $content);
            } else {
                $content .= "\n{$k}={$v}";
            }
        }

        file_put_contents($envFile, $content);
        log_activity('update', 'ai', 'Memperbarui konfigurasi AI SIKES');

        json_response(['ok' => true, 'message' => 'Konfigurasi AI berhasil disimpan.']);
    }

    /* ================= CHATBOT: bersihkan percakapan ================= */
    public function chatClear(): void
    {
        $this->gate();
        $this->checkCsrf('/ai');
        AiAssistant::clearHistory();
        json_response(['ok' => true]);
    }
}