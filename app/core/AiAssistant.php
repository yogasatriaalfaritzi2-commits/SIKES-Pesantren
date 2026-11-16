<?php

namespace App\Core;

use RuntimeException;

/**
 * AI SIKES — asisten analisis data + CHATBOT AI ASLI (API gratis Groq).
 *
 * FIX v17.2:
 *  - "Jawaban kosong": kini pesan error ASLI dari API diekstrak &
 *    ditampilkan (mis. model dipensiunkan, kuota, dsb).
 *  - FALLBACK MODEL: jika model utama gagal/kosong, otomatis mencoba
 *    model cadangan yang umum tersedia di Groq.
 *  - Struktur & fitur lain identik (analyze, riwayat session, naratif).
 *
 * BATAS TEGAS: asisten analisis data — TIDAK memberi diagnosis medis.
 */
class AiAssistant
{
    private Database $db;

    private const JOIN_CHAIN = "
        LEFT JOIN kamar k  ON k.id  = s.kamar_id
        LEFT JOIN blok b   ON b.id  = k.blok_id
        LEFT JOIN asrama a ON a.id  = b.asrama_id";

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ================= Wrapper aman ================= */
    private function safe(callable $fn, $default)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /* ================= Scope filter ================= */
    private function scopeSql(array $f): array
    {
        $w = [];
        $params = [];
        if (!empty($f['asrama'])) {
            $w[] = 'b.asrama_id = ?';
            $params[] = (int) $f['asrama'];
        }
        if (!empty($f['blok'])) {
            $w[] = 'b.id = ?';
            $params[] = (int) $f['blok'];
        }
        return [$w, $params];
    }

    private function scopeWhere(array $f): array
    {
        [$w, $params] = $this->scopeSql($f);
        if ($w) {
            return [' AND ' . implode(' AND ', $w), $params];
        }
        return ['', $params];
    }

    /* ================= Query dasar ================= */

    private function kasusCount(string $from, string $to, array $f): int
    {
        [$scope, $params] = $this->scopeWhere($f);
        $sql = "SELECT COUNT(*) AS total
             FROM pemeriksaan p
             JOIN santri s ON s.id = p.santri_id" . self::JOIN_CHAIN . "
             WHERE p.status = 'selesai'
               AND DATE(p.tanggal_kunjungan) BETWEEN ? AND ?" . $scope;
        $row = $this->db->fetch($sql, array_merge([$from, $to], $params));
        return (int) ($row['total'] ?? 0);
    }

    private function penyakitBreakdown(string $from, string $to, array $f): array
    {
        [$scope, $params] = $this->scopeWhere($f);
        $sql = "SELECT TRIM(p.diagnosis) AS penyakit, COUNT(*) AS total
             FROM pemeriksaan p
             JOIN santri s ON s.id = p.santri_id" . self::JOIN_CHAIN . "
             WHERE p.status = 'selesai'
               AND DATE(p.tanggal_kunjungan) BETWEEN ? AND ?
               AND p.diagnosis IS NOT NULL AND TRIM(p.diagnosis) <> ''" . $scope . "
             GROUP BY TRIM(p.diagnosis)";
        return $this->db->fetchAll($sql, array_merge([$from, $to], $params));
    }

    private function blokPenyakit(string $penyakit, string $from, string $to, array $f): ?array
    {
        [$scope, $params] = $this->scopeWhere($f);
        $sql = "SELECT b.nama AS blok, a.nama AS asrama, COUNT(*) AS total
             FROM pemeriksaan p
             JOIN santri s ON s.id = p.santri_id" . self::JOIN_CHAIN . "
             WHERE p.status = 'selesai'
               AND DATE(p.tanggal_kunjungan) BETWEEN ? AND ?
               AND TRIM(p.diagnosis) = ?" . $scope . "
             GROUP BY b.id, b.nama, a.nama
             ORDER BY total DESC
             LIMIT 1";
        return $this->db->fetch($sql, array_merge([$from, $to, $penyakit], $params));
    }

    /* ================= Analisis utama ================= */

    public function analyze(array $f): array
    {
        $days     = max(1, (int) ((strtotime($f['to']) - strtotime($f['from'])) / 86400) + 1);
        $prevTo   = date('Y-m-d', strtotime($f['from'] . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($days - 1) . ' day'));

        $kasusNow  = $this->safe(function () use ($f) {
            return $this->kasusCount($f['from'], $f['to'], $f);
        }, 0);
        $kasusPrev = $this->safe(function () use ($f, $prevFrom, $prevTo) {
            return $this->kasusCount($prevFrom, $prevTo, $f);
        }, 0);

        $trends = $this->safe(function () use ($f, $prevFrom, $prevTo) {
            $now  = $this->penyakitBreakdown($f['from'], $f['to'], $f);
            $prev = [];
            $rowsPrev = $this->penyakitBreakdown($prevFrom, $prevTo, $f);
            foreach ($rowsPrev as $r) {
                $prev[$r['penyakit']] = (int) $r['total'];
            }

            $out = [];
            foreach ($now as $r) {
                $disease = $r['penyakit'];
                $p       = $prev[$disease] ?? 0;
                $delta   = (int) $r['total'] - $p;
                if ($delta > 0) {
                    $loc    = $this->blokPenyakit($disease, $f['from'], $f['to'], $f);
                    $persen = ($p > 0) ? (int) round($delta / $p * 100) : null;
                    $out[] = [
                        'penyakit' => $disease,
                        'now'      => (int) $r['total'],
                        'prev'     => $p,
                        'delta'    => $delta,
                        'persen'   => $persen,
                        'blok'     => $loc['blok'] ?? null,
                        'asrama'   => $loc['asrama'] ?? null,
                    ];
                }
            }
            usort($out, function ($a, $b) {
                return $b['delta'] <=> $a['delta'];
            });
            return array_slice($out, 0, 3);
        }, []);

        $warnings = $this->safe(function () {
            $w = [];

            $menunggu = $this->db->fetch(
                "SELECT COUNT(*) AS total, MIN(tanggal_kunjungan) AS oldest
                 FROM pemeriksaan WHERE status = 'menunggu'"
            );
            $menunggu = is_array($menunggu) ? $menunggu : [];
            if ((int) ($menunggu['total'] ?? 0) > 0) {
                $hari = 0;
                if (!empty($menunggu['oldest'])) {
                    $hari = (int) floor((time() - strtotime((string) $menunggu['oldest'])) / 86400);
                }
                $w[] = ['level' => 'warning', 'icon' => 'bi-hourglass-split',
                    'title' => (int) $menunggu['total'] . ' kunjungan menunggu pemeriksaan',
                    'detail' => 'Kunjungan tertua sudah menunggu ±' . $hari . ' hari. Segera lakukan pemeriksaan sesuai prosedur poskestren.',
                    'timestamp' => !empty($menunggu['oldest']) ? date('c', strtotime((string) $menunggu['oldest'])) : date('c'),
                    'url' => base_url('/pemeriksaan')];
            }

            $kontak = $this->db->fetch(
                "SELECT COUNT(*) AS total FROM tracing_kontak WHERE status = 'belum_diperiksa'"
            );
            $kontak = is_array($kontak) ? $kontak : [];
            if ((int) ($kontak['total'] ?? 0) > 0) {
                $w[] = ['level' => 'warning', 'icon' => 'bi-diagram-3',
                    'title' => (int) $kontak['total'] . ' kontak potensial belum diperiksa',
                    'detail' => 'Jadwalkan pemeriksaan preventif bagi kontak potensial sesuai alur Contact Tracing.',
                    'timestamp' => date('c'),
                    'url' => base_url('/tracing')];
            }

            $obat = new \App\Models\Obat();
            $n = $obat->countLowStock();
            if ($n > 0) {
                $w[] = ['level' => 'danger', 'icon' => 'bi-exclamation-circle-fill',
                    'title' => $n . ' obat stok menipis',
                    'detail' => 'Ajukan pengadaan ke bendahara sebelum stok habis.',
                    'timestamp' => date('c'),
                    'url' => base_url('/obat')];
            }
            $n = $obat->countExpired();
            if ($n > 0) {
                $w[] = ['level' => 'danger', 'icon' => 'bi-x-octagon-fill',
                    'title' => $n . ' obat sudah kedaluwarsa',
                    'detail' => 'Keluar obat kedaluwarsa dari rak dan proses pemusnahan sesuai prosedur.',
                    'timestamp' => date('c'),
                    'url' => base_url('/obat?flag=kedaluwarsa')];
            }
            $n = $obat->countNearExpiry();
            if ($n > 0) {
                $w[] = ['level' => 'warning', 'icon' => 'bi-hourglass-split',
                    'title' => $n . ' obat mendekati kedaluwarsa (<=90 hari)',
                    'detail' => 'Prioritaskan penggunaan (FEFO) dan pantau tanggal kedaluwarsa.',
                    'timestamp' => date('c'),
                    'url' => base_url('/obat?flag=mendekati')];
            }

            $sanitasi = $this->db->fetch(
                "SELECT COUNT(*) AS total FROM inspeksi_kamar ik
                 WHERE ik.status = 'risiko_tinggi'
                   AND ik.id = (SELECT MAX(x.id) FROM inspeksi_kamar x WHERE x.kamar_id = ik.kamar_id)"
            );
            $sanitasi = is_array($sanitasi) ? $sanitasi : [];
            if ((int) ($sanitasi['total'] ?? 0) > 0) {
                $w[] = ['level' => 'danger', 'icon' => 'bi-house-gear',
                    'title' => (int) $sanitasi['total'] . ' kamar berstatus sanitasi RISIKO TINGGI',
                    'detail' => 'Lakukan inspeksi ulang dan tindak lanjut kebersihan bersama pengasuhan.',
                    'timestamp' => date('c'),
                    'url' => base_url('/sanitasi')];
            }

            usort($w, function ($a, $b) {
                $rankA = ($a['level'] === 'danger') ? 0 : 1;
                $rankB = ($b['level'] === 'danger') ? 0 : 1;
                return $rankA <=> $rankB;
            });
            return $w;
        }, []);

        $perhatian = $this->safe(function () use ($f) {
            [$scope, $scopeParams] = $this->scopeWhere($f);
            $sql = "SELECT a.nama AS asrama, b.nama AS blok,
                        (SELECT COUNT(*) FROM pemeriksaan p
                           JOIN santri sx ON sx.id = p.santri_id
                          WHERE sx.kamar_id IN (SELECT k2.id FROM kamar k2 WHERE k2.blok_id = b.id)
                            AND p.status = 'selesai'
                            AND DATE(p.tanggal_kunjungan) BETWEEN ? AND ?) AS kasus,
                        (SELECT COUNT(*) FROM kasus_menular km
                           JOIN santri sx ON sx.id = km.santri_id
                          WHERE sx.kamar_id IN (SELECT k2.id FROM kamar k2 WHERE k2.blok_id = b.id)
                            AND km.status_kasus = 'aktif') AS aktif,
                        (SELECT COUNT(*) FROM inspeksi_kamar x
                          WHERE x.kamar_id IN (SELECT k2.id FROM kamar k2 WHERE k2.blok_id = b.id)
                            AND x.id = (SELECT MAX(x2.id) FROM inspeksi_kamar x2 WHERE x2.kamar_id = x.kamar_id)
                            AND x.status = 'risiko_tinggi') AS risiko
                 FROM blok b
                 JOIN asrama a ON a.id = b.asrama_id
                 WHERE 1=1" . $scope . "
                 ORDER BY a.nama ASC, b.nama ASC";
            return $this->db->fetchAll($sql, array_merge([$f['from'], $f['to']], $scopeParams));
        }, []);

        $perhatian = array_values(array_filter($perhatian, function ($r) {
            return (int) $r['aktif'] > 0 || (int) $r['risiko'] > 0 || (int) $r['kasus'] >= 3;
        }));

        $deltaKasus = $kasusNow - $kasusPrev;
        $arah = 'stabil';
        if ($deltaKasus > 0) {
            $arah = 'meningkat';
        } elseif ($deltaKasus < 0) {
            $arah = 'menurun';
        }

        $summary = 'Selama periode ' . $f['from'] . ' s.d. ' . $f['to']
                 . ' tercatat ' . $kasusNow . ' kunjungan kesehatan ('
                 . $arah . ' ' . abs($deltaKasus)
                 . ' kasus dibanding periode sebelumnya: ' . $kasusPrev . ').';

        if ($trends) {
            $t = $trends[0];
            $persenTxt = ($t['persen'] !== null) ? ' (sekitar ' . $t['persen'] . '%)' : '';
            $summary .= "\nTren meningkat paling menonjol: " . $t['penyakit']
                      . ' (+' . $t['delta'] . ' kasus' . $persenTxt . ')';
            if ($t['blok']) {
                $summary .= ', terkonsentrasi di ' . $t['blok'] . ' (' . $t['asrama'] . ')';
            }
            $summary .= '. Petugas disarankan melakukan pemeriksaan sanitasi dan pemeriksaan preventif '
                      . 'pada area terkait sesuai prosedur.';
        }

        $summary .= "\nTerdapat " . count($warnings) . ' hal operasional yang memerlukan perhatian.'
                  . "\nCATATAN: Analisis ini adalah ringkasan data administratif — bukan diagnosis medis. "
                  . 'Keputusan klinis tetap merupakan kewenangan tenaga kesehatan.';

        return [
            'kasus'     => ['now' => $kasusNow, 'prev' => $kasusPrev, 'delta' => $deltaKasus],
            'trends'    => $trends,
            'warnings'  => $warnings,
            'perhatian' => $perhatian,
            'summary'   => $summary,
            'periode'   => [
                'from'      => $f['from'],
                'to'        => $f['to'],
                'prev_from' => $prevFrom,
                'prev_to'   => $prevTo,
            ],
        ];
    }

    /* ============================================================
       CHATBOT — riwayat di session
       ============================================================ */

    public static function history(): array
    {
        if (!isset($_SESSION['ai_chat']) || !is_array($_SESSION['ai_chat'])) {
            $_SESSION['ai_chat'] = [];
        }
        return $_SESSION['ai_chat'];
    }

    public static function clearHistory(): void
    {
        $_SESSION['ai_chat'] = [];
    }

    /**
     * System prompt chatbot — larang diagnosis medis & aturan identitas ringkas.
     */
    private function chatSystemPrompt(array $context): string
    {
        $userName = \App\Core\Auth::name() ?? "Pengguna";
        $roleName = \App\Core\Auth::roleName() ?? "Pengguna";
        $roleSlug = \App\Core\Auth::roleSlug() ?? "";
        $isSuper  = \App\Core\Auth::isSuper() || $roleSlug === "super-admin";

        $roleInstruction = $isSuper
            ? "PENGGUNA SAAT INI ADALAH ADMIN / SUPER ADMIN. Anda berhak dan WAJIB DAPAT MENJAWAB SEMUA pertanyaan di seluruh sistem dan database SIKES-Pesantren secara lengkap, akurat, dan ringkas."
            : "PENGGUNA SAAT INI MEMILIKI ROLE: \"" . $roleName . "\". ATURAN AKSES TUGAS ROLE: Anda HANYA BOLEH MENJAWAB data dan pertanyaan yang berada dalam wewenang tugas role " . $roleName . " sesuai data agregat konteks yang diberikan. JIKA pengguna menanyakan data di luar wewenang role " . $roleName . " (misalnya menanyakan data keuangan, resep obat, atau modul lain yang bukan bagiannya), TOLAK DENGAN SINGKAT: \"Maaf, data tersebut berada di luar wewenang tugas role Anda (" . $roleName . ").\"";

        return 'Kamu adalah Asisten SIKES, asisten resmi Sistem Informasi Kesehatan Pondok Pesantren.' . "\n"
            . 'ATURAN MUTLAK GAYA JAWABAN (WAJIB DIPATUHI TANPA PENGECUALIAN):'. "\n"
            . '1. BATASAN RUANG LINGKUP MUTLAK: Anda HANYA BOLEH menjawab seputar data dan operasional sistem informasi kesehatan SIKES Pondok Pesantren. DILARANG KERAS menjawab hal umum di luar sistem SIKES (misalnya: membuat coding/script pemrograman seperti Python/PHP/JS, puisi, cerita, matematika umum, resep masakan, pengetahuan umum, tugas sekolah, dll).' . "\n"
            . '   JIKA pengguna bertanya hal apa pun di luar data sistem SIKES (termasuk permintaan coding/pemrograman): WAJIB TOLAK SINGKAT DENGAN TEKS: "Maaf, saya hanya dapat menjawab seputar sistem SIKES." (tanpa komentar tambahan atau kode apa pun).' . "\n"
            . '2. JAWAB SINGKAT, PADAT, DAN LANGSUNG PADA INTI JAWABAN TANPA KOMENTAR ATAU PENJELASAN PANJANG LEBAR (JANGAN BERKOMENTAR PANJANG LAYAKNYA AI GPT BIASA).' . "\n"
            . '3. JIKA PENGGUNA BERTANYA IDENTITAS DIRI (misalnya "siapa anda", "kamu siapa", "siapa saya", "nama saya siapa", dll):' . "\n"
            . '   Jawab persis: "Saya ' . $userName . ' seorang ' . strtolower($roleName) . '." tanpa komentar tambahan apa pun.' . "\n"
            . '4. JIKA DITANYA TOTAL PENGGUNA (user akun sistem): jawab data master_data.total_pengguna (misal 10 pengguna, jangan tertukar dengan santri).' . "\n"
            . '5. JIKA DITANYA LOGIN HARI INI: jawab data master_data.login_hari_ini (misal 1 login hari ini).' . "\n"
            . '6. JIKA DITANYA STATISTIK / RINGKASAN UTAMA SISTEM / DASHBOARD: sebutkan 4 indikator utama: Total Pengguna (master_data.total_pengguna), Total Santri (master_data.santri_total), Asrama (master_data.asrama), dan Login Hari Ini (master_data.login_hari_ini).' . "\n"
            . '7. JIKA DITANYA DAFTAR PENGGUNA ATAU SIAPA SAJA NAMA PENGGUNANYA DI SISTEM ("siapa saja nama nama nya", "siapa saja namanya", "sebutkan penggunanya", dll):' . "\n"
            . '   Sebutkan nama pengguna beserta rolenya dari master_data.daftar_pengguna secara lengkap.' . "\n"
            . '8. JIKA DITANYA DATA SANTRI:' . "\n"
            . '   - Total santri: master_data.santri_total' . "\n"
            . '   - Santri aktif: master_data.santri_aktif' . "\n"
            . '   - Santri tidak aktif / nonaktif: master_data.santri_nonaktif (JANGAN TERTUKAR DENGAN SANTRI AKTIF!)' . "\n"
            . '   - Santri putra / laki-laki: master_data.santri_putra' . "\n"
            . '   - Santri putri / perempuan: master_data.santri_putri' . "\n"
            . '   - Santri belum dapat kamar: master_data.santri_belum_kamar' . "\n"
            . '   - Daftar santri / siapa santri nonaktif atau aktif: sebutkan nama dari master_data.daftar_santri sesuai statusnya.' . "\n"
            . '9. BATASAN AKSES ROLE:' . "\n"
            . '   ' . $roleInstruction . "\n"
            . '10. JIKA DITANYA DATA ATAU ANGKA LAIN (keuangan, obat, asrama, kamar, kasus menular, rujukan, tamu, dll) dan SESUAI wewenang role:' . "\n"
            . '   Jawab langsung to-the-point dengan data/angkanya secara singkat dan jelas berdasarkan data sistem yang valid tanpa komentar panjang.' . "\n"
            . '11. DILARANG diagnosa klinis medis individual atau resep obat perorangan.' . "\n"
            . "\nDATA SISTEM SIKES:\n" . json_encode($context, JSON_UNESCAPED_UNICODE);
    }

    private function callLlm(array $messages, string $model, float $temperature, int $maxTokens): array
    {
        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'max_tokens'  => $maxTokens,
            'temperature' => $temperature,
        ];

        $ch = curl_init(AI_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => defined('AI_TIMEOUT') ? AI_TIMEOUT : 45,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . AI_API_KEY,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);
        $res  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false || $err !== '') {
            return [false, 'Koneksi ke server AI gagal (' . $err . '). Coba lagi sebentar.', $code];
        }

        $json = json_decode((string) $res, true);
        if (!is_array($json)) {
            return [false, 'Respons server AI tidak dapat dibaca. Coba lagi.', $code];
        }

        // Pesan error ASLI dari API (mis. model dipensiunkan, kuota, dsb.)
        if (isset($json['error'])) {
            $apiMsg = '';
            if (is_array($json['error']) && isset($json['error']['message'])) {
                $apiMsg = (string) $json['error']['message'];
            } elseif (is_string($json['error'])) {
                $apiMsg = $json['error'];
            }
            if ($apiMsg !== '') {
                return [false, 'Server AI: ' . $apiMsg, $code];
            }
            return [false, 'Server AI menolak permintaan (HTTP ' . $code . ').', $code];
        }

        // Ambil teks jawaban
        $text = $json['choices'][0]['message']['content'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            return [false, 'Server AI memberi jawaban kosong untuk model "' . $model . '".', $code];
        }

        return [true, trim($text), $code];
    }

    /**
     * CHATBOT: kirim percakapan dengan FALLBACK MODEL otomatis.
     * Urutan: AI_CHAT_MODEL -> AI_MODEL -> daftar model Groq umum.
     * Melempar RuntimeException dengan pesan ramah bila semua gagal.
     */
    public function chat(array $messages, array $context): string
    {
        if (!self::llmEnabled()) {
            throw new RuntimeException(
                'Chatbot AI belum aktif. Tempel API key gratis (https://console.groq.com) '
                . 'ke AI_API_KEY di file .env.'
            );
        }

        $maxTokens = defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 700;
        $system    = $this->chatSystemPrompt($context);

        $fullMessages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $messages
        );

        // Rantai model: utama -> naratif -> cadangan umum di Groq
        $chain = [];
        if (defined('AI_CHAT_MODEL') && AI_CHAT_MODEL !== '') {
            $chain[] = AI_CHAT_MODEL;
        }
        if (defined('AI_MODEL') && AI_MODEL !== '' && !in_array(AI_MODEL, $chain, true)) {
            $chain[] = AI_MODEL;
        }
        foreach (['llama-3.1-8b-instant', 'llama-3.3-70b-versatile', 'openai/gpt-oss-20b'] as $fallback) {
            if (!in_array($fallback, $chain, true)) {
                $chain[] = $fallback;
            }
        }

        $lastError = 'Server AI tidak dapat dihubungi.';
        foreach ($chain as $model) {
            [$ok, $result, $code] = $this->callLlm($fullMessages, $model, 0.5, $maxTokens);

            if ($ok) {
                return $result;
            }

            // Error fatal yang TIDAK perlu dicoba model lain:
            if ($code === 401) {
                throw new RuntimeException('API key tidak valid. Periksa AI_API_KEY di file .env.');
            }
            if ($code === 429) {
                throw new RuntimeException('Kuota gratis sedang penuh. Tunggu ±1 menit lalu kirim ulang.');
            }

            $lastError = $result . ' [model: ' . $model . ']';
        }

        // Semua model gagal — tampilkan error terakhir (dipotong agar rapi)
        $short = mb_strlen($lastError) > 220 ? mb_substr($lastError, 0, 220) . '…' : $lastError;
        throw new RuntimeException($short);
    }

    /* ============================================================
       LLM: status & naratif (fitur lama)
       ============================================================ */

    public static function llmEnabled(): bool
    {
        return defined('AI_ENABLED') && AI_ENABLED
            && defined('AI_API_KEY') && AI_API_KEY !== '';
    }

    /**
     * Ambil peringatan sistem aktif (untuk panel notifikasi & badge).
     */
    public function getSystemWarnings(): array
    {
        $f = [
            'periode' => 'bulan',
            'from'    => date('Y-m-01'),
            'to'      => date('Y-m-d'),
            'asrama'  => 0,
            'blok'    => 0,
        ];
        $analysis = $this->analyze($f);
        return $analysis['warnings'] ?? [];
    }

    public function narrative(array $context): ?string
    {
        if (!self::llmEnabled()) {
            return null;
        }

        $system = 'Anda adalah asisten analisis data kesehatan Pondok Pesantren. '
            . 'TUGAS: merangkum statistik agregat dan memberi saran TINDAKAN OPERASIONAL '
            . '(pemeriksaan sanitasi, pemeriksaan preventif, pengadaan obat, koordinasi antarunit) '
            . 'sesuai prosedur pondok. '
            . 'DILARANG KERAS: memberikan diagnosis medis, resep, dosis, atau kesimpulan '
            . 'klinis per individu; menyebut ini sebagai diagnosis. '
            . 'Gunakan Bahasa Indonesia formal, 2-3 paragraf, untuk ringkasan pimpinan.';

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' =>
                'Berdasarkan data agregat berikut (JSON), buat ringkasan eksekutif '
                . 'beserta rekomendasi operasional:' . "\n"
                . json_encode($context, JSON_UNESCAPED_UNICODE)],
        ];

        try {
            [$ok, $result] = $this->callLlm(
                $messages,
                defined('AI_MODEL') ? AI_MODEL : 'llama-3.3-70b-versatile',
                0.4,
                defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 700
            );
            return $ok ? $result : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}