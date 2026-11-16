<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\RekamMedis;
use App\Models\Santri;
use App\Models\SkriningKesehatan;

class RekamMedisController extends Controller
{
    private RekamMedis $rekam;

    public function __construct()
    {
        $this->rekam = new RekamMedis();
    }

    /** Daftar santri + rekap kesehatan (konsolidasi read-only). */
    public function index(): void
    {
        $q    = trim($_GET['q'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('rekam_medis/index', [
            'title' => 'Rekam Medis Santri',
            'user'  => Auth::user(),
            'data'  => $this->rekam->ringkasan($page, 10, $q),
            'q'     => $q,
        ]);
    }

    /** Konsolidasi lengkap satu santri. */
    public function show($id): void
    {
        $santri = (new Santri())->findDetail((int) $id);
        if (!$santri) {
            $this->redirect('/rekam-medis');
        }

        $skrining = $this->rekam->skrining((int) $id);

        $this->view('rekam_medis/show', [
            'title'      => 'Rekam Medis — ' . $santri['nama'],
            'user'       => Auth::user(),
            'santri'     => $santri,
            'skrining'   => $skrining,
            'warnings'   => SkriningKesehatan::warningsFrom($skrining),
            'pemeriksaan'=> $this->rekam->pemeriksaan((int) $id),
            'kasus'      => $this->rekam->kasus((int) $id),
            'izin'       => $this->rekam->izin((int) $id),
            'rujukan'    => $this->rekam->rujukan((int) $id),
        ]);
    }
}