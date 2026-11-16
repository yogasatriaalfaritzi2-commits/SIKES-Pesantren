<?php
use App\Core\AiAssistant;
use App\Core\Session;

 $periodeLabel = ['hari' => 'Hari Ini', 'minggu' => '7 Hari Terakhir',
                 'bulan' => 'Bulan Ini', 'tahun' => 'Tahun Ini'][$f['periode']] ?? '';
 $chatHistory  = (isset($chatHistory) && is_array($chatHistory)) ? $chatHistory : [];

/**
 * Renderer teks chat (aman XSS + rapi):
 * 1. Escape dulu (htmlspecialchars)
 * 2. **tebal**  -> <strong>
 * 3. "- " di awal baris -> "• "
 * 4. Baris baru -> <br>
 */
if (!function_exists('renderChatText')) {
    function renderChatText(string $text): string
    {
        $safe = e($text);
        $safe = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $safe);
        $safe = preg_replace('/(^|\n)[-–] /', '$1• ', $safe);
        return nl2br($safe);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asisten AI | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(160deg, #0d2b1e, #0f766e);
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
        }
        .chat-wrap {
            max-width: 780px;
            margin: 24px auto;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 48px);
        }
        .chat-head {
            background: rgba(255,255,255,.96);
            border-radius: 18px 18px 0 0;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .chat-head .avatar {
            width: 42px; height: 42px; border-radius: 12px;
            background: linear-gradient(135deg, #14b8a6, #0f766e);
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; flex-shrink: 0;
        }
        .chat-head h6 { margin: 0; font-weight: 800; }
        .chat-head small { color: #10b981; font-weight: 600; }
        .chat-head small .dot {
            display: inline-block; width: 8px; height: 8px; border-radius: 50%;
            background: #10b981; margin-right: 4px;
        }
        .chat-head small.err { color: #dc2626; }
        .chat-body {
            flex: 1;
            overflow-y: auto;
            background: #f8fafc;
            padding: 18px 16px;
        }
        .msg { display: flex; margin-bottom: 12px; }
        .msg.user { justify-content: flex-end; }
        .msg .bubble {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 16px;
            font-size: .92rem;
            line-height: 1.6;
            word-break: break-word;
        }
        .msg.ai .bubble {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .msg.user .bubble {
            background: linear-gradient(135deg, #14b8a6, #0f766e);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .msg.err .bubble { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .chips { display: flex; flex-wrap: wrap; gap: 6px; padding: 0 16px 10px; background: #f8fafc; }
        .chip {
            border: 1px solid #cbd5e1; background: #fff; color: #0f766e;
            border-radius: 999px; padding: 5px 12px; font-size: .78rem;
            cursor: pointer; transition: all .15s;
        }
        .chip:hover { background: #0f766e; color: #fff; border-color: #0f766e; }
        .chat-foot {
            background: rgba(255,255,255,.96);
            border-radius: 0 0 18px 18px;
            padding: 12px;
            border-top: 1px solid #e2e8f0;
        }
        .chat-foot .row-in { display: flex; gap: 8px; }
        .chat-foot input {
            border-radius: 999px; padding: 10px 18px; border: 1px solid #cbd5e1;
        }
        .chat-foot input:focus {
            border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,.15);
        }
        .chat-foot button {
            border-radius: 50%; width: 44px; height: 44px; flex-shrink: 0;
            background: linear-gradient(135deg, #14b8a6, #0f766e); border: 0; color: #fff;
        }
        .typing span {
            display: inline-block; width: 7px; height: 7px; border-radius: 50%;
            background: #94a3b8; margin-right: 3px;
            animation: blink 1.2s infinite;
        }
        .typing span:nth-child(2) { animation-delay: .2s; }
        .typing span:nth-child(3) { animation-delay: .4s; }
        @keyframes blink { 0%,100% { opacity: .25; } 50% { opacity: 1; } }
        .collapse-box { background: #fff; border-radius: 12px; margin: 0 16px 10px; font-size: .82rem; }
        .collapse-box .card-header { cursor: pointer; font-weight: 700; background: #fff; }
        @media (max-width: 575.98px) {
            .chat-wrap { margin: 10px; height: calc(100vh - 20px); }
            .msg .bubble { max-width: 90%; }
        }
    </style>
</head>
<body>

<div class="chat-wrap">
    <!-- ===== HEADER CHAT ===== -->
    <div class="chat-head">
        <div class="avatar"><i class="bi bi-robot"></i></div>
        <div class="flex-grow-1">
            <h6>Asisten Kesehatan SIKES</h6>
            <small class="<?= AiAssistant::llmEnabled() ? '' : 'err' ?>">
                <span class="dot" style="background: <?= AiAssistant::llmEnabled() ? '#10b981' : '#dc2626' ?>;"></span>
                <?= AiAssistant::llmEnabled() ? 'Online — siap membantu' : 'Perlu API key (app/config/ai.php)' ?>
            </small>
        </div>
        <form method="POST" action="<?= base_url('/ai/chat/clear') ?>" id="clearForm" class="me-2">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
            <button class="btn btn-sm btn-outline-secondary" title="Bersihkan percakapan" type="submit">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
        </form>
        <a href="<?= base_url('/dashboard') ?>" class="btn btn-sm btn-outline-secondary" title="Kembali">
            <i class="bi bi-x-lg"></i>
        </a>
    </div>

    <!-- ===== ISI CHAT ===== -->
    <div class="chat-body" id="chatBody">
        <?php if (!$chatHistory): ?>
        <div class="msg ai"><div class="bubble">
            Assalamu'alaikum! 👋 Saya <strong>Asisten Kesehatan SIKES</strong>.<br>
            Tanyakan statistik kesehatan pondok, kondisi asrama, stok obat,
            atau cara pakai sistem.<br>
            <small class="text-muted">Catatan: saya tidak memberikan diagnosis medis.</small>
        </div></div>
        <?php else: ?>
            <?php foreach ($chatHistory as $m): ?>
                <?php if ($m['role'] === 'user'): ?>
                <div class="msg user"><div class="bubble"><?= e($m['content']) ?></div></div>
                <?php else: ?>
                <div class="msg ai"><div class="bubble"><?= renderChatText($m['content']) ?></div></div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="msg ai d-none" id="typing">
            <div class="bubble typing">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>

    <!-- ===== TOMBOL PERTANYAAN CEPAT ===== -->
    <div class="chips" id="chips">
        <button class="chip">Bagaimana kondisi kesehatan pondok saat ini?</button>
        <button class="chip">Penyakit apa yang paling banyak?</button>
        <button class="chip">Ada obat yang perlu segera ditangani?</button>
        <button class="chip">Asrama mana yang perlu perhatian?</button>
    </div>

    <!-- ===== INPUT ===== -->
    <div class="chat-foot">
        <form id="chatForm" class="row-in" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e(Session::csrfToken()) ?>">
            <input type="text" class="form-control" id="chatInput" maxlength="1000"
                   placeholder="Tulis pertanyaan Anda…" required>
            <button type="submit" id="sendBtn" title="Kirim"><i class="bi bi-send-fill"></i></button>
        </form>
        <div class="text-center mt-2">
            <small class="text-muted" style="font-size:.7rem">
                AI hanya asisten analisis data — bukan diagnosis medis.
                <a href="#" data-bs-toggle="collapse" data-bs-target="#analysisBox" class="text-decoration-none">
                    Lihat analisis detail</a>
            </small>
        </div>

        <!-- Analisis detail (dilipat, tidak mengganggu chat) -->
        <div class="collapse collapse-box mt-2" id="analysisBox">
            <div class="card border-0">
                <div class="card-header bg-white small fw-bold">
                    <i class="bi bi-cpu me-1"></i> Analisis Mesin Lokal — <?= $periodeLabel ?>
                </div>
                <div class="card-body py-2">
                    <p class="mb-2" style="white-space:pre-wrap"><?= e($analysis['summary'] ?? '') ?></p>
                    <?php foreach (($analysis['warnings'] ?? []) as $w): ?>
                        <div class="small mb-1">
                            <i class="bi <?= $w['icon'] ?> me-1"></i><strong><?= e($w['title']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const body   = document.getElementById('chatBody');
    const form   = document.getElementById('chatForm');
    const input  = document.getElementById('chatInput');
    const btn    = document.getElementById('sendBtn');
    const typing = document.getElementById('typing');

    const down = () => { body.scrollTop = body.scrollHeight; };
    const esc  = t => { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; };

    /* Rapikan format Markdown-lite dari AI (aman: escape dijalankan dulu):
       **tebal** -> <strong>, "- " -> bullet, baris baru -> <br>           */
    const pretty = (safeHtml) => safeHtml
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/(^|\n)[-–] /g, '$1• ')
        .replace(/\n/g, '<br>');

    const add = (safeHtml, who, isErr) => {
        const m = document.createElement('div');
        m.className = 'msg ' + who + (isErr ? ' err' : '');
        const b = document.createElement('div');
        b.className = 'bubble';
        b.innerHTML = safeHtml;
        m.appendChild(b);
        body.insertBefore(m, typing);
        down();
        return b;
    };

    // Pertanyaan cepat -> isi input
    document.querySelectorAll('.chip').forEach(function (c) {
        c.addEventListener('click', function () {
            input.value = c.textContent.trim();
            input.focus();
        });
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        add(esc(text), 'user');
        input.value = '';
        btn.disabled = true;
        typing.classList.remove('d-none');
        down();

        try {
            const fd = new FormData();
            fd.append('csrf_token', form.querySelector('[name=csrf_token]').value);
            fd.append('message', text);

            const res  = await fetch('<?= base_url('/ai/chat') ?>', { method: 'POST', body: fd });
            const json = await res.json();

            typing.classList.add('d-none');

            if (json.ok) {
                add(pretty(esc(json.reply)), 'ai');
            } else {
                add(esc(json.error || 'Terjadi kesalahan.'), 'ai', true);
            }
        } catch (err) {
            typing.classList.add('d-none');
            add(esc('Koneksi gagal. Periksa server lalu coba lagi.'), 'ai', true);
        } finally {
            btn.disabled = false;
            input.focus();
        }
    });

    document.getElementById('clearForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        await fetch('<?= base_url('/ai/chat/clear') ?>', { method: 'POST', body: fd });
        location.reload();
    });

    down();
})();
</script>
</body>
</html>