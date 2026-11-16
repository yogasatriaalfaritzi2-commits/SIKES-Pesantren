<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\EksekutifModel;

class EksekutifController extends Controller
{
    private EksekutifModel $dash;

    public function __construct()
    {
        $this->dash = new EksekutifModel();
    }

    /** Dashboard eksekutif hanya untuk Kyai/Pimpinan (Super Admin bypass). */
    private function gate(): void
    {
        if (Auth::isSuper() || Auth::hasRole('kyai')) {
            return;
        }
        http_response_code(403);
        require ROOT_PATH . '/app/views/errors/403.php';
        exit;
    }

    /** Susun filter dari query string: periode (hari/minggu/bulan/tahun) + asrama/blok. */
    private function buildFilters(): array
    {
        $periode = in_array($_GET['periode'] ?? '', ['hari', 'minggu', 'bulan', 'tahun'], true)
            ? $_GET['periode'] : 'bulan';
        $asrama  = (int) ($_GET['asrama'] ?? 0);
        $blok    = (int) ($_GET['blok'] ?? 0);

        switch ($periode) {
            case 'hari':    $from = date('Y-m-d');                       break;
            case 'minggu':  $from = date('Y-m-d', strtotime('-6 day'));  break;
            case 'tahun':   $from = date('Y-01-01');                     break;
            default:        $from = date('Y-m-01');
        }
        return [
            'periode' => $periode,
            'from'    => $from,
            'to'      => date('Y-m-d'),
            'asrama'  => $asrama,
            'blok'    => $blok,
        ];
    }

    /* ================= Dashboard ================= */
    public function index(): void
    {
        $this->gate();
        $f = $this->buildFilters();

        $this->view('eksekutif/index', [
            'title'   => 'Dashboard Eksekutif',
            'user'    => Auth::user(),
            'f'       => $f,
            'metrics' => [
                'santriTotal'   => $this->dash->santriTotal($f),
                'santriSakit'   => $this->dash->santriSakit($f),
                'kasus'         => $this->dash->kasusPeriode($f),
                'rawatInap'     => $this->dash->rawatInap($f),
                'rujukan'       => $this->dash->rujukanCount($f),
                'izin'          => $this->dash->izinCount($f),
                'sanitasi'      => $this->dash->sanitasi($f),
                'obat'          => $this->dash->pemakaianObat($f),
            ],
            'penyakit'   => $this->dash->penyakitTerbanyak($f),
            'tren'       => $this->dash->trenBulanan($f),
            'obatTop'    => $this->dash->obatTop($f),
            'heatmap'    => $this->dash->heatmap($f),
            'asramaOptions' => (new \App\Models\Asrama())->options(),
            'blokList'      => (new \App\Models\Blok())->allWithAsrama(),
        ]);
    }

    /* ================= Ekspor CSV ================= */
    public function export(): void
    {
        $this->gate();
        $f = $this->buildFilters();

        $penyakit = $this->dash->penyakitTerbanyak($f, 20);
        $obatTop  = $this->dash->obatTop($f, 20);
        $laporan  = $this->dash->pemakaianObat($f);
        $sanitasi = $this->dash->sanitasi($f);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="laporan-eksekutif-' . date('Ymd') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

        fputcsv($out, ['LAPORAN EKSEKUTIF SIKES']);
        fputcsv($out, ['Periode', $f['from'] . ' s.d. ' . $f['to']]);
        fputcsv($out, ['Filter Asrama', $f['asrama'] ?: 'Semua']);
        fputcsv($out, ['Filter Blok',   $f['blok']   ?: 'Semua']);
        fputcsv($out, []);

        fputcsv($out, ['METRIK UTAMA']);
        fputcsv($out, ['Total Santri Aktif',          $this->dash->santriTotal($f)]);
        fputcsv($out, ['Santri Sedang Sakit',         $this->dash->santriSakit($f)]);
        fputcsv($out, ['Kasus Kesehatan Periode',     $this->dash->kasusPeriode($f)]);
        fputcsv($out, ['Santri Isolasi/Rawat Inap',   $this->dash->rawatInap($f)]);
        fputcsv($out, ['Jumlah Rujukan',              $this->dash->rujukanCount($f)]);
        fputcsv($out, ['Jumlah Izin Sakit',           $this->dash->izinCount($f)]);
        fputcsv($out, ['Sanitasi: Kamar Dibersihkan', $sanitasi['total']]);
        fputcsv($out, ['Sanitasi: Rata-rata Skor (%)',$sanitasi['rata2']]);
        fputcsv($out, ['Sanitasi: Risiko Tinggi',     $sanitasi['risiko']]);
        fputcsv($out, ['Pemakaian Obat (satuan)',     $laporan['jumlah']]);
        fputcsv($out, ['Biaya Obat (Rp)',             $laporan['biaya']]);
        fputcsv($out, []);

        fputcsv($out, ['PENYAKIT TERBANYAK']);
        fputcsv($out, ['Penyakit (Keterangan Awal)', 'Jumlah Kasus']);
        foreach ($penyakit as $p) {
            fputcsv($out, [$p['penyakit'], $p['total']]);
        }
        fputcsv($out, []);

        fputcsv($out, ['PEMAKAIAN OBAT TERBANYAK']);
        fputcsv($out, ['Obat', 'Jumlah Digunakan']);
        foreach ($obatTop as $o) {
            fputcsv($out, [$o['nama'], $o['jumlah']]);
        }
        fputcsv($out, []);

        fputcsv($out, ['HEATMAP ASRAMA/BLOK']);
        fputcsv($out, ['Asrama', 'Blok', 'Kasus Periode', 'Kasus Aktif', 'Sanitasi Risiko']);
        foreach ($this->dash->heatmap($f) as $h) {
            fputcsv($out, [$h['asrama'], $h['blok'], $h['kasus'], $h['aktif'], $h['risiko']]);
        }

        fclose($out);
        exit;
    }

    /* ================= Cetak ================= */
    public function cetak(): void
    {
        $this->gate();
        $f = $this->buildFilters();

        $this->view('eksekutif/cetak', [
            'f'        => $f,
            'metrics'  => [
                'santriTotal' => $this->dash->santriTotal($f),
                'santriSakit' => $this->dash->santriSakit($f),
                'kasus'       => $this->dash->kasusPeriode($f),
                'rawatInap'   => $this->dash->rawatInap($f),
                'rujukan'     => $this->dash->rujukanCount($f),
                'izin'        => $this->dash->izinCount($f),
                'sanitasi'    => $this->dash->sanitasi($f),
                'obat'        => $this->dash->pemakaianObat($f),
            ],
            'penyakit' => $this->dash->penyakitTerbanyak($f, 10),
            'obatTop'  => $this->dash->obatTop($f, 10),
            'heatmap'  => $this->dash->heatmap($f),
            'periodeLabel' => ['hari' => 'Hari Ini', 'minggu' => '7 Hari Terakhir',
                               'bulan' => 'Bulan Ini', 'tahun' => 'Tahun Ini'][$f['periode']],
        ], null);
    }
}