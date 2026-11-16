<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Laporan;

class LaporanController extends Controller
{
    private Laporan $laporan;

    public function __construct()
    {
        $this->laporan = new Laporan();
    }

    /** Pusat laporan: ringkasan lintas modul + grafik. */
    public function index(): void
    {
        $this->view('laporan/index', [
            'title'  => 'Pusat Laporan',
            'user'   => Auth::user(),
            'asrama' => $this->laporan->santriPerAsrama(),
            'tren'   => $this->laporan->trenBulanan(),
            'obat'   => $this->laporan->biayaObatBulanan(),
        ]);
    }

    /** Ekspor CSV seluruh bagian laporan. */
    public function export(): void
    {
        $asrama  = $this->laporan->santriPerAsrama();
        $tren    = $this->laporan->trenBulanan();
        $obat    = $this->laporan->biayaObatBulanan();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="laporan-sikes-' . date('Ymd') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 untuk Excel

        fputcsv($out, ['LAPORAN PUSAT SIKES']);
        fputcsv($out, ['Dicetak', date('d-m-Y H:i')]);
        fputcsv($out, []);

        fputcsv($out, ['SANTRI AKTIF PER ASRAMA']);
        fputcsv($out, ['Asrama', 'Jenis', 'Blok', 'Kamar', 'Santri Aktif']);
        foreach ($asrama as $a) {
            fputcsv($out, [$a['asrama'], $a['jenis'], $a['blok'], $a['kamar'], $a['santri_aktif']]);
        }
        fputcsv($out, []);

        fputcsv($out, ['TREN 12 BULAN']);
        fputcsv($out, ['Bulan', 'Kasus', 'Izin Sakit', 'Rujukan']);
        foreach ($tren['labels'] as $i => $label) {
            fputcsv($out, [$label, $tren['kasus'][$i], $tren['izin'][$i], $tren['rujukan'][$i]]);
        }
        fputcsv($out, []);

        fputcsv($out, ['BIAYA OBAT 12 BULAN']);
        fputcsv($out, ['Bulan', 'Item Digunakan', 'Biaya (Rp)']);
        foreach ($tren['labels'] as $i => $label) {
            fputcsv($out, [$label, $obat['item'][$i], $obat['biaya'][$i]]);
        }
        fputcsv($out, ['TOTAL', '', $obat['grand']]);

        fclose($out);
        exit;
    }

    /** Laporan cetak (formal, print-friendly). */
    public function cetak(): void
    {
        $this->view('laporan/cetak', [
            'asrama' => $this->laporan->santriPerAsrama(),
            'tren'   => $this->laporan->trenBulanan(),
            'obat'   => $this->laporan->biayaObatBulanan(),
        ], null);
    }
}