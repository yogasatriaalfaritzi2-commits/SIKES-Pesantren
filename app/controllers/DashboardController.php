<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Santri;
use App\Models\WaliSantri;
use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\SkriningKesehatan;
use App\Models\Pemeriksaan;
use App\Models\KasusMenular;
use App\Models\InspeksiKamar;
use App\Models\PiketJadwal;
use App\Models\SuratIzin;
use App\Models\Kepulangan;
use App\Models\BukuTamu;
use App\Models\GiziDiet;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $user = Auth::user();

        $cfg = $this->roleConfig($user);

        $quick = array_values(array_filter(
            $cfg['quick'],
            fn (array $l) => $l['perm'] === null || Auth::hasPermission($l['perm'])
        ));

        $reminders = Auth::hasPermission('piket.view')
            ? (new PiketJadwal())->reminders()
            : null;

        $this->view('dashboard/index', [
            'title'     => 'Dashboard',
            'user'      => $user,
            'subtitle'  => $cfg['subtitle'],
            'stats'     => $cfg['stats'],
            'quick'     => $quick,
            'reminders' => $reminders,
        ]);
    }

    private function stat(string $label, callable $compute, string $icon, string $color): array
    {
        try {
            $value = (string) $compute();
        } catch (\Throwable $e) {
            $value = '0';
        }
        return ['label' => $label, 'value' => $value, 'icon' => $icon, 'color' => $color];
    }

    private function roleConfig(array $user): array
    {
        switch ($user['role_slug'] ?? '') {

            case 'super-admin':
                return [
                    'subtitle' => 'Ringkasan sistem, pengguna, dan aktivitas.',
                    'stats' => [
                        $this->stat('Total Pengguna', fn () => (new User())->count(),       'bi-people',             'primary'),
                        $this->stat('Total Santri',   fn () => (new Santri())->count(),     'bi-mortarboard',        'success'),
                        $this->stat('Asrama',         fn () => (new Asrama())->count(),     'bi-building',           'info'),
                        $this->stat('Login Hari Ini', fn () => (new ActivityLog())->countToday('login'), 'bi-box-arrow-in-right', 'warning'),
                    ],
                    'quick' => [
                        ['label' => 'Data Santri',     'icon' => 'bi-people',        'url' => '/santri',   'perm' => 'santri.view'],
                        ['label' => 'Asrama',          'icon' => 'bi-building',      'url' => '/asrama',   'perm' => 'asrama.view'],
                        ['label' => 'Kelola Pengguna', 'icon' => 'bi-people-gear',   'url' => '/users',    'perm' => 'users.view'],
                        ['label' => 'Log Aktivitas',   'icon' => 'bi-clock-history', 'url' => '/activity', 'perm' => 'logs.view'],
                    ],
                ];

            case 'tenaga-medis':
                $pm = new Pemeriksaan();
                return [
                    'subtitle' => 'Pantau kunjungan, pemeriksaan, skrining, perizinan, dan rekomendasi diet.',
                    'stats' => [
                        $this->stat('Menunggu Pemeriksaan', fn () => $pm->countWaiting(),                  'bi-hourglass-split',      'warning'),
                        $this->stat('Kunjungan Hari Ini',   fn () => $pm->countToday(),                    'bi-clipboard2-heart',     'primary'),
                        $this->stat('Kasus Menular Aktif',  fn () => (new KasusMenular())->countActive(),  'bi-diagram-3',            'danger'),
                        $this->stat('Surat Izin Aktif',     fn () => (new SuratIzin())->countActive(),     'bi-file-earmark-medical', 'success'),
                    ],
                    'quick' => [
                        ['label' => 'Daftar Kunjungan',      'icon' => 'bi-clipboard2-heart',     'url' => '/pemeriksaan',        'perm' => 'pemeriksaan.view'],
                        ['label' => 'Registrasi Kunjungan',  'icon' => 'bi-person-plus',          'url' => '/pemeriksaan/create', 'perm' => 'pemeriksaan.create'],
                        ['label' => 'Rekomendasi Kepulangan','icon' => 'bi-box-arrow-right',      'url' => '/kepulangan/create',  'perm' => 'kepulangan.manage'],
                        ['label' => 'Rekomendasi Diet',      'icon' => 'bi-egg-fried',            'url' => '/gizi/create',        'perm' => 'gizi.rekomendasi'],
                        ['label' => 'Contact Tracing',       'icon' => 'bi-diagram-3',            'url' => '/tracing',            'perm' => 'pemeriksaan.view'],
                        ['label' => 'Skrining Kesehatan',    'icon' => 'bi-clipboard2-pulse',     'url' => '/skrining',           'perm' => 'pemeriksaan.view'],
                        ['label' => 'Surat Izin Sakit',      'icon' => 'bi-file-earmark-medical', 'url' => '/surat',              'perm' => 'surat.view'],
                        ['label' => 'Surat Rujukan',        'icon' => 'bi-envelope-paper',       'url' => '/rujukan',            'perm' => 'rujukan.view'],
                    ],
                ];

            case 'pengasuhan':
                $insp = new InspeksiKamar();
                return [
                    'subtitle' => 'Pembinaan, penempatan santri, kebersihan asrama, dan approval kepulangan.',
                    'stats' => [
                        $this->stat('Perlu Approval',      fn () => (new Kepulangan())->countPendingPengasuhanStage(),   'bi-box-arrow-right',          'warning'),
                        $this->stat('Kamar Baik',          fn () => $insp->kamarCondition()['stats']['baik'],            'bi-check-circle-fill',        'success'),
                        $this->stat('Kamar Risiko Tinggi', fn () => $insp->kamarCondition()['stats']['risiko_tinggi'],   'bi-exclamation-triangle-fill','danger'),
                        $this->stat('Piket Belum Selesai', fn () => (new PiketJadwal())->countUnfinished(),              'bi-calendar-week',            'primary'),
                    ],
                    'quick' => [
                        ['label' => 'Kepulangan Sakit',      'icon' => 'bi-box-arrow-right','url' => '/kepulangan','perm' => 'kepulangan.view'],
                        ['label' => 'Jadwal Piket',          'icon' => 'bi-calendar-week',  'url' => '/piket',     'perm' => 'piket.view'],
                        ['label' => 'Sanitasi & Lingkungan', 'icon' => 'bi-house-gear',     'url' => '/sanitasi',  'perm' => 'sanitasi.view'],
                        ['label' => 'Data Asrama',           'icon' => 'bi-building',       'url' => '/asrama',    'perm' => 'asrama.view'],
                    ],
                ];

            case 'keamanan':
                return [
                    'subtitle' => 'Approval kepulangan, gerbang, dan buku tamu penjenguk.',
                    'stats' => [
                        $this->stat('Perlu Approval',      fn () => (new Kepulangan())->countPendingKeamanan(), 'bi-clipboard2-check',  'warning'),
                        $this->stat('Siap Keluar Gerbang', fn () => (new Kepulangan())->countReadyGate(),       'bi-door-open',         'info'),
                        $this->stat('Tamu Masih di Dalam', fn () => (new BukuTamu())->countInside(),            'bi-people-fill',       'primary'),
                        $this->stat('Tamu Hari Ini',       fn () => (new BukuTamu())->countToday(),             'bi-journal-check',     'success'),
                    ],
                    'quick' => [
                        ['label' => 'Kepulangan Sakit', 'icon' => 'bi-box-arrow-right', 'url' => '/kepulangan',       'perm' => 'kepulangan.view'],
                        ['label' => 'Buku Tamu',        'icon' => 'bi-journal-check',   'url' => '/buku-tamu',        'perm' => 'buku_tamu.view'],
                        ['label' => 'Catat Tamu Baru',  'icon' => 'bi-person-plus',     'url' => '/buku-tamu/create', 'perm' => 'buku_tamu.manage'],
                    ],
                ];

            case 'admin-santri':
                return [
                    'subtitle' => 'Pengelolaan data santri, wali, dan penempatan asrama.',
                    'stats' => [
                        $this->stat('Total Santri', fn () => (new Santri())->count(),        'bi-mortarboard', 'primary'),
                        $this->stat('Santri Aktif', fn () => (new Santri())->countActive(),  'bi-person-check','success'),
                        $this->stat('Wali Terdata', fn () => (new WaliSantri())->count(),    'bi-person-vcard','info'),
                        $this->stat('Kamar Terisi', fn () => (new Kamar())->countOccupied(), 'bi-door-open',   'warning'),
                    ],
                    'quick' => [
                        ['label' => 'Data Santri', 'icon' => 'bi-people',       'url' => '/santri', 'perm' => 'santri.view'],
                        ['label' => 'Wali Santri', 'icon' => 'bi-person-badge', 'url' => '/wali',   'perm' => 'wali.view'],
                        ['label' => 'Kamar',       'icon' => 'bi-door-open',    'url' => '/kamar',  'perm' => 'asrama.view'],
                    ],
                ];

            case 'dapur-gizi':
                return [
                    'subtitle' => 'Dashboard dapur: diet khusus hari ini dan status distribusinya.',
                    'stats' => [
                        $this->stat('Santri Diet Khusus',     fn () => (new GiziDiet())->statsFor(date('Y-m-d'))['santri'],  'bi-egg-fried',          'warning'),
                        $this->stat('Label Belum Diproses',   fn () => (new GiziDiet())->statsFor(date('Y-m-d'))['belum'],   'bi-hourglass-split',    'secondary'),
                        $this->stat('Siap Diambil',           fn () => (new GiziDiet())->statsFor(date('Y-m-d'))['siap'],    'bi-bag-check',          'info'),
                        $this->stat('Selesai Didistribusikan',fn () => (new GiziDiet())->statsFor(date('Y-m-d'))['selesai'], 'bi-check-circle-fill',  'success'),
                    ],
                    'quick' => [
                        ['label' => 'Daftar Diet Hari Ini', 'icon' => 'bi-list-ul', 'url' => '/gizi',                'perm' => 'gizi.view'],
                        ['label' => 'Cetak Label Hari Ini', 'icon' => 'bi-tags',    'url' => '/gizi/label-hari-ini', 'perm' => 'gizi.view'],
                    ],
                ];

            case 'bendahara':
                return [
                    'subtitle' => 'Keuangan pondok: pemasukan, pengeluaran, dan tagihan.',
                    'stats' => [
                        ['label' => 'Saldo Kas',             'value' => '-', 'icon' => 'bi-wallet2',           'color' => 'success'],
                        ['label' => 'Pemasukan Bulan Ini',   'value' => '-', 'icon' => 'bi-arrow-down-circle', 'color' => 'primary'],
                        ['label' => 'Pengeluaran Bulan Ini', 'value' => '-', 'icon' => 'bi-arrow-up-circle',   'color' => 'danger'],
                        ['label' => 'Tagihan Belum Lunas',   'value' => '-', 'icon' => 'bi-receipt',           'color' => 'warning'],
                    ],
                    'quick' => [
                        ['label' => 'Keuangan', 'icon' => 'bi-cash-coin', 'url' => '#', 'perm' => 'keuangan.view', 'soon' => true],
                    ],
                ];

            case 'kyai':
                return [
                    'subtitle' => 'Pemantauan ringkas seluruh kegiatan pondok.',
                    'stats' => [
                        $this->stat('Total Santri', fn () => (new Santri())->count(),                'bi-mortarboard',    'primary'),
                        $this->stat('Siap Keluar',  fn () => (new Kepulangan())->countReadyGate(),   'bi-door-open',      'warning'),
                        $this->stat('Kasus Aktif',  fn () => (new KasusMenular())->countActive(),    'bi-diagram-3',      'danger'),
                        $this->stat('Piket Aktif',  fn () => (new PiketJadwal())->countUnfinished(), 'bi-calendar-week',  'info'),
                    ],
                    'quick' => [
                        ['label' => 'Data Santri',      'icon' => 'bi-people',        'url' => '/santri',     'perm' => 'santri.view'],
                        ['label' => 'Kepulangan Sakit', 'icon' => 'bi-box-arrow-right','url' => '/kepulangan', 'perm' => 'kepulangan.view'],
                        ['label' => 'Dapur & Gizi',     'icon' => 'bi-egg-fried',     'url' => '/gizi',       'perm' => 'gizi.view'],
                        ['label' => 'Laporan', 'icon' => 'bi-file-earmark-bar-graph', 'url' => '#', 'perm' => 'laporan.view', 'soon' => true],
                        ['label' => 'Dashboard Eksekutif', 'icon' => 'bi-speedometer', 'url' => '/eksekutif', 'perm' => 'laporan.view'],
                    ],
                ];

            case 'santri':
                return [
                    'subtitle' => 'Ringkasan data pribadi dan kegiatan Anda.',
                    'stats' => [
                        ['label' => 'Kesehatan Terakhir', 'value' => '-', 'icon' => 'bi-heart-pulse',    'color' => 'danger'],
                        ['label' => 'Perizinan',          'value' => '-', 'icon' => 'bi-calendar-check', 'color' => 'info'],
                        ['label' => 'Tagihan',            'value' => '-', 'icon' => 'bi-receipt',        'color' => 'warning'],
                        ['label' => 'Poin Pembinaan',     'value' => '-', 'icon' => 'bi-star',           'color' => 'primary'],
                    ],
                    'quick' => [
                        ['label' => 'Profil Saya', 'icon' => 'bi-person', 'url' => '#', 'perm' => null, 'soon' => true],
                    ],
                ];

            case 'wali-santri':
                return [
                    'subtitle' => 'Pantau kondisi ananda di pondok.',
                    'stats' => [
                        ['label' => 'Ananda Terdaftar', 'value' => '-', 'icon' => 'bi-people',         'color' => 'primary'],
                        ['label' => 'Kesehatan',        'value' => '-', 'icon' => 'bi-heart-pulse',    'color' => 'danger'],
                        ['label' => 'Kehadiran',        'value' => '-', 'icon' => 'bi-calendar-check', 'color' => 'info'],
                        ['label' => 'Tagihan',          'value' => '-', 'icon' => 'bi-receipt',        'color' => 'warning'],
                    ],
                    'quick' => [
                        ['label' => 'Data Ananda', 'icon' => 'bi-person-heart', 'url' => '#', 'perm' => null, 'soon' => true],
                    ],
                ];

            default:
                return ['subtitle' => 'Selamat datang di sistem.', 'stats' => [], 'quick' => []];
        }
    }
}