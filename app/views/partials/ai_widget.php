<?php
use App\Core\Auth;
use App\Core\Session;
use App\Core\AiAssistant;

// Widget untuk semua pengguna yang sudah login
if (!Session::isLoggedIn()) {
    return;
}

$csrfToken = Session::csrfToken();
$chatHistory = AiAssistant::history();
?>

<!-- ============================================================== -->
<!-- SIKES FLOATING AI WIDGET (WHATSAPP-STYLE)                     -->
<!-- ============================================================== -->
<style>
/* Tombol Bulat Floating WhatsApp-style di Kanan Bawah */
.sikes-ai-fab {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #25D366, #128C7E);
    box-shadow: 0 8px 24px rgba(18, 140, 126, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 28px;
    cursor: pointer;
    z-index: 1060;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.sikes-ai-fab:hover {
    transform: scale(1.08) translateY(-3px);
    box-shadow: 0 12px 28px rgba(18, 140, 126, 0.55);
    color: #ffffff;
}

.sikes-ai-fab .fab-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #e11d48;
    color: white;
    font-size: 11px;
    font-weight: 700;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.sikes-ai-fab .pulse-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 2px solid #25D366;
    animation: sikesFabPulse 2s infinite;
    pointer-events: none;
}

@keyframes sikesFabPulse {
    0% { transform: scale(1); opacity: 0.8; }
    100% { transform: scale(1.4); opacity: 0; }
}

/* Modal Popup AI Assistant Window */
.sikes-ai-card {
    position: fixed;
    bottom: 95px;
    right: 24px;
    width: 440px;
    max-width: calc(100vw - 32px);
    height: 620px;
    max-height: calc(100vh - 120px);
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.25);
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 1060;
    border: 1px solid #e2e8f0;
    animation: sikesSlideUp 0.25s ease-out;
}

.sikes-ai-card.open {
    display: flex;
}

@keyframes sikesSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Header Pop-up (WhatsApp Top Bar Style) */
.sikes-ai-header {
    background: linear-gradient(135deg, #075E54, #128C7E);
    color: #ffffff;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.sikes-ai-header .avatar-box {
    position: relative;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #ffffff;
    color: #075E54;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.sikes-ai-header .online-dot {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 11px;
    height: 11px;
    background: #25D366;
    border: 2px solid #ffffff;
    border-radius: 50%;
}

.sikes-ai-header .header-info {
    flex: 1;
    min-width: 0;
}

.sikes-ai-header .title {
    font-weight: 700;
    font-size: 0.95rem;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sikes-ai-header .subtitle {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.85);
    margin: 0;
}

.sikes-ai-header .action-btns {
    display: flex;
    gap: 6px;
}

.sikes-ai-header .header-btn {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.85);
    font-size: 18px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s;
}

.sikes-ai-header .header-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}

/* Navigasi Tab Menu (Chatbot, Chatbot+, Notifikasi, Konfigurasi) */
.sikes-ai-nav {
    display: flex;
    background: #f1f5f9;
    padding: 6px 8px;
    border-bottom: 1px solid #e2e8f0;
    gap: 4px;
}

.sikes-ai-nav .nav-item-btn {
    flex: 1;
    padding: 6px 4px;
    font-size: 0.75rem;
    font-weight: 700;
    text-align: center;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #475569;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: all 0.15s;
    white-space: nowrap;
}

.sikes-ai-nav .nav-item-btn.active {
    background: #ffffff;
    color: #0f766e;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

.sikes-ai-nav .badge-count {
    background: #ef4444;
    color: #ffffff;
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 8px;
}

/* Area Konten Tab */
.sikes-ai-content {
    flex: 1;
    overflow: hidden;
    position: relative;
    background: #efeae2; /* Khas background WA chat */
}

.sikes-tab-pane {
    display: none;
    height: 100%;
    flex-direction: column;
}

.sikes-tab-pane.active {
    display: flex;
}

/* Area Chat (Chatbot & Chatbot+) */
.sikes-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background-image: radial-gradient(#d1d7db 1px, transparent 1px);
    background-size: 20px 20px;
}

.sikes-bubble-wrap {
    display: flex;
    width: 100%;
}

.sikes-bubble-wrap.user {
    justify-content: flex-end;
}

.sikes-bubble-wrap.ai {
    justify-content: flex-start;
}

.sikes-bubble {
    max-width: 82%;
    padding: 9px 13px;
    font-size: 0.88rem;
    line-height: 1.5;
    word-break: break-word;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    position: relative;
}

.sikes-bubble-wrap.user .sikes-bubble {
    background: #d9fdd3; /* Warna bubble hijau WA user */
    color: #111b21;
    border-radius: 14px 14px 2px 14px;
}

.sikes-bubble-wrap.ai .sikes-bubble {
    background: #ffffff;
    color: #111b21;
    border-radius: 14px 14px 14px 2px;
}

.sikes-bubble.pro-style {
    border-left: 3px solid #14b8a6;
    background: #f8fafc;
}

.sikes-bubble.err-style {
    background: #fee2e2;
    color: #991b1b;
    border-left: 3px solid #ef4444;
}

.sikes-bubble .time-stamp {
    font-size: 0.68rem;
    color: #667781;
    text-align: right;
    margin-top: 4px;
    display: block;
}

/* Typing animation */
.sikes-typing-bubble {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 10px 14px;
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    width: fit-content;
}

.sikes-typing-bubble span {
    width: 6px;
    height: 6px;
    background: #8696a0;
    border-radius: 50%;
    animation: sikesDotBounce 1.4s infinite;
}

.sikes-typing-bubble span:nth-child(2) { animation-delay: 0.2s; }
.sikes-typing-bubble span:nth-child(3) { animation-delay: 0.4s; }

@keyframes sikesDotBounce {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-5px); }
}

/* Chips Pertanyaan Cepat */
.sikes-quick-chips {
    display: flex;
    overflow-x: auto;
    padding: 6px 10px;
    gap: 6px;
    background: rgba(255, 255, 255, 0.85);
    border-top: 1px solid #e2e8f0;
}

.sikes-quick-chips::-webkit-scrollbar {
    height: 3px;
}

.sikes-quick-chips::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.sikes-chip-btn {
    white-space: nowrap;
    font-size: 0.74rem;
    padding: 4px 10px;
    border-radius: 14px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #0f766e;
    cursor: pointer;
    font-weight: 500;
}

.sikes-chip-btn:hover {
    background: #0f766e;
    color: #ffffff;
    border-color: #0f766e;
}

/* Input Form Footer */
.sikes-chat-input-bar {
    background: #f0f2f5;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-top: 1px solid #e2e8f0;
}

.sikes-chat-input-bar input {
    flex: 1;
    border-radius: 20px;
    border: 1px solid #d1d7db;
    padding: 8px 16px;
    font-size: 0.88rem;
    outline: none;
    background: #ffffff;
}

.sikes-chat-input-bar input:focus {
    border-color: #00a884;
}

.sikes-send-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #00a884;
    color: #ffffff;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s;
    flex-shrink: 0;
}

.sikes-send-btn:hover {
    background: #008f6f;
}

.sikes-send-btn:disabled {
    background: #94a3b8;
    cursor: not-allowed;
}

/* Panel Tab Notifikasi */
.sikes-notif-pane {
    background: #f8fafc;
    height: 100%;
    overflow-y: auto;
    padding: 12px;
}

.sikes-notif-item {
    background: #ffffff;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 10px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    display: flex;
    gap: 12px;
    transition: transform 0.15s;
}

.sikes-notif-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.06);
}

.sikes-notif-item.danger {
    border-left: 4px solid #ef4444;
}

.sikes-notif-item.warning {
    border-left: 4px solid #f59e0b;
}

.sikes-notif-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.sikes-notif-item.danger .sikes-notif-icon {
    background: #fee2e2;
    color: #ef4444;
}

.sikes-notif-item.warning .sikes-notif-icon {
    background: #fef3c7;
    color: #f59e0b;
}

/* Panel Tab Konfigurasi */
.sikes-config-pane {
    background: #f8fafc;
    height: 100%;
    overflow-y: auto;
    padding: 14px;
}

.sikes-config-box {
    background: #ffffff;
    border-radius: 12px;
    padding: 14px;
    border: 1px solid #e2e8f0;
    margin-bottom: 12px;
}

.sikes-config-box h6 {
    font-size: 0.85rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}
</style>

<!-- Floating WA Trigger Button -->
<div class="sikes-ai-fab" id="sikesAiFab" title="Asisten AI SIKES" onclick="sikesAiToggle()">
    <div class="pulse-ring"></div>
    <i class="bi bi-robot" id="sikesFabIcon"></i>
    <span class="fab-badge d-none" id="sikesNotifBadge">0</span>
</div>

<!-- Floating Assistant Modal Card -->
<div class="sikes-ai-card" id="sikesAiCard">
    <!-- Header -->
    <div class="sikes-ai-header">
        <div class="avatar-box">
            <i class="bi bi-robot"></i>
            <span class="online-dot" id="sikesOnlineDot" title="AI Siap"></span>
        </div>
        <div class="header-info">
            <div class="title">SIKES AI Assistant</div>
            <div class="subtitle" id="sikesAiSubtitle">Online • Asisten Kesehatan Pondok</div>
        </div>
        <div class="action-btns">
            <button class="header-btn" title="Bersihkan Chat" onclick="sikesAiClearChat()">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
            <button class="header-btn" title="Tutup" onclick="sikesAiToggle()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="sikes-ai-nav">
        <button class="nav-item-btn active" id="tabBtnChat" onclick="sikesAiSwitchTab('chat')">
            <i class="bi bi-chat-dots-fill"></i> Chatbot
        </button>
        <button class="nav-item-btn" id="tabBtnPro" onclick="sikesAiSwitchTab('pro')">
            <i class="bi bi-lightning-charge-fill"></i> Chatbot+
        </button>
        <button class="nav-item-btn" id="tabBtnNotif" onclick="sikesAiSwitchTab('notif')">
            <i class="bi bi-bell-fill"></i> Notifikasi <span class="badge-count d-none" id="sikesTabNotifBadge">0</span>
        </button>
        <button class="nav-item-btn" id="tabBtnConfig" onclick="sikesAiSwitchTab('config')">
            <i class="bi bi-gear-fill"></i> Konfigurasi
        </button>
    </div>

    <!-- Content Area -->
    <div class="sikes-ai-content">
        <!-- 1. TAB CHATBOT (REGULER) -->
        <div class="sikes-tab-pane active" id="paneChat">
            <div class="sikes-chat-messages" id="sikesChatList">
                <div class="sikes-bubble-wrap ai">
                    <div class="sikes-bubble">
                        Assalamu'alaikum! 👋 Saya <strong>Asisten AI SIKES</strong>.<br>
                        Tanyakan kondisi kesehatan santri, ketersediaan obat, atau SOP pondok.
                        <span class="time-stamp">Hari ini</span>
                    </div>
                </div>
            </div>

            <!-- Chips Rekomendasi Pertanyaan -->
            <div class="sikes-quick-chips">
                <button class="sikes-chip-btn" onclick="sikesAiAskChip(this.innerText)">Bagaimana kondisi kesehatan pondok?</button>
                <button class="sikes-chip-btn" onclick="sikesAiAskChip(this.innerText)">Penyakit apa yang paling banyak?</button>
                <button class="sikes-chip-btn" onclick="sikesAiAskChip(this.innerText)">Ada obat yang stoknya menipis?</button>
                <button class="sikes-chip-btn" onclick="sikesAiAskChip(this.innerText)">Asrama mana yang perlu perhatian?</button>
            </div>

            <!-- Typing indicator -->
            <div class="px-3 py-1 d-none" id="sikesTyping">
                <div class="sikes-typing-bubble">
                    <span></span><span></span><span></span>
                </div>
            </div>

            <!-- Input Bar -->
            <form class="sikes-chat-input-bar" id="sikesChatForm" onsubmit="sikesAiSendMessage(event)">
                <input type="text" id="sikesChatInput" placeholder="Ketik pesan konsultasi..." autocomplete="off" required>
                <button type="submit" class="sikes-send-btn" id="sikesSendBtn" title="Kirim">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>

        <!-- 2. TAB CHATBOT+ (PRO ANALYTICS & PREDICTION) -->
        <div class="sikes-tab-pane" id="panePro">
            <div class="p-3 bg-white border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold text-teal" style="color:#0f766e; font-size:.88rem">
                            <i class="bi bi-cpu-fill me-1"></i> Mode Chatbot+ (Pro Epidemiologi)
                        </div>
                        <small class="text-muted" style="font-size:.72rem">
                            Analisis mendalam, deteksi dini risiko wabah, & rekomendasi aksi taktis.
                        </small>
                    </div>
                    <button class="btn btn-sm btn-outline-success py-1 px-2" style="font-size:.75rem" onclick="sikesAiRunProAnalysis()">
                        <i class="bi bi-play-circle me-1"></i> Analisis Otomatis
                    </button>
                </div>
            </div>

            <div class="sikes-chat-messages" id="sikesProChatList">
                <div class="sikes-bubble-wrap ai">
                    <div class="sikes-bubble pro-style">
                        <strong>Chatbot+ SIKES</strong><br>
                        Siap menjawab pertanyaan seputar data kesehatan & operasional pondok secara cepat dan to-the-point.
                    </div>
                </div>
            </div>

            <!-- Pro Typing indicator -->
            <div class="px-3 py-1 d-none" id="sikesProTyping">
                <div class="sikes-typing-bubble">
                    <span style="background:#0f766e"></span>
                    <span style="background:#0f766e"></span>
                    <span style="background:#0f766e"></span>
                </div>
            </div>

            <!-- Chips Rekomendasi Pertanyaan Pro -->
            <div class="sikes-quick-chips">
                <button type="button" class="sikes-chip-btn" onclick="sikesAiAskProChip(this.innerText)">siapa anda</button>
                <button type="button" class="sikes-chip-btn" onclick="sikesAiAskProChip(this.innerText)">ada berapa obat</button>
                <button type="button" class="sikes-chip-btn" onclick="sikesAiAskProChip(this.innerText)">Berapa santri aktif?</button>
                <button type="button" class="sikes-chip-btn" onclick="sikesAiAskProChip(this.innerText)">Ada kasus sakit aktif?</button>
            </div>

            <form class="sikes-chat-input-bar" id="sikesProChatForm" onsubmit="sikesAiSendProMessage(event)">
                <input type="text" id="sikesProChatInput" placeholder="Instruksi analisis mendalam (mis: prediksi risiko asrama A)..." autocomplete="off">
                <button type="submit" class="sikes-send-btn" id="sikesProSendBtn" style="background:#0f766e" title="Jalankan Analisis">
                    <i class="bi bi-lightning-charge-fill"></i>
                </button>
            </form>
        </div>

        <!-- 3. TAB NOTIFIKASI SISTEM -->
        <div class="sikes-tab-pane" id="paneNotif">
            <div class="p-2 bg-light border-bottom d-flex align-items-center justify-content-between">
                <span class="small fw-bold text-dark"><i class="bi bi-bell-fill text-teal me-1"></i> Peringatan Real-Time</span>
                <span class="badge bg-teal-subtle text-teal rounded-pill" id="sikesNotifPulseStatus" style="font-size: .65rem;">
                    <span class="spinner-grow spinner-grow-sm text-success me-1" style="width:.4rem;height:.4rem;"></span> Aktif
                </span>
            </div>
            <div class="sikes-notif-pane" id="sikesNotifList">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-secondary mb-2" role="status"></div>
                    <div>Memeriksa status sistem kesehatan...</div>
                </div>
            </div>
            <div class="p-2 bg-white border-top text-center">
                <button class="btn btn-sm btn-light border text-muted w-100" style="font-size:.75rem" onclick="sikesAiFetchNotifications()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Segarkan Peringatan Sistem
                </button>
            </div>
        </div>

        <!-- 4. TAB KONFIGURASI AI -->
        <div class="sikes-tab-pane" id="paneConfig">
            <div class="sikes-config-pane">
                <div class="sikes-config-box">
                    <h6><i class="bi bi-shield-lock-fill text-success"></i> Status Layanan AI</h6>
                    <div class="small text-muted mb-2">
                        Status: <span id="sikesCfgStatusBadge" class="badge bg-secondary">Memuat...</span>
                    </div>
                    <div class="small text-muted">
                        Provider Default: <strong>Groq Cloud / Gemini (OpenAI Compatible)</strong>
                    </div>
                </div>

                <form id="sikesConfigForm" onsubmit="sikesAiSaveConfig(event)">
                    <div class="sikes-config-box">
                        <h6><i class="bi bi-sliders text-teal"></i> Pengaturan Parameter Model</h6>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1" style="font-size:.76rem">Penyedia AI (Provider)</label>
                            <select class="form-select form-select-sm" id="sikesCfgProvider" onchange="sikesAiOnProviderChange()">
                                <option value="groq">Groq Cloud (Sangat Cepat - Default)</option>
                                <option value="gemini">Google Gemini (OpenAI Compat)</option>
                                <option value="openai">OpenAI (GPT-4o / GPT-4o-mini)</option>
                                <option value="ollama">Local Server (Ollama / vLLM)</option>
                                <option value="custom">Kustom (Manual Endpoint URL)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1" style="font-size:.76rem">Model Chatbot Cepat</label>
                            <input type="text" class="form-control form-control-sm" id="sikesCfgChatModel" placeholder="llama-3.1-8b-instant" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1" style="font-size:.76rem">Model Chatbot+ (Pro)</label>
                            <input type="text" class="form-control form-control-sm" id="sikesCfgProModel" placeholder="llama-3.3-70b-versatile" required>
                        </div>
                        <div class="mb-2" id="sikesCfgEndpointGroup" style="display: none;">
                            <label class="form-label small fw-semibold mb-1" style="font-size:.76rem">Endpoint API URL</label>
                            <input type="text" class="form-control form-control-sm" id="sikesCfgEndpoint" placeholder="https://api.groq.com/openai/v1/chat/completions" required>
                            <div class="text-muted" style="font-size:.68rem; margin-top:2px;">URL API dikelola otomatis sesuai provider yang dipilih.</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1" style="font-size:.76rem">API Key</label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control" id="sikesCfgApiKey" placeholder="Biarkan kosong jika tidak diubah">
                                <button class="btn btn-outline-secondary" type="button" id="sikesToggleApiKeyBtn" onclick="sikesToggleApiKeyVisibility()" title="Lihat/Sembunyikan API Key">
                                    <i class="bi bi-eye" id="sikesApiKeyEyeIcon"></i>
                                </button>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-1 text-muted" style="font-size:.68rem;">
                                <span>Kunci saat ini: <code id="sikesCfgCurrentKey">Belum disetel</code></span>
                                <a href="javascript:void(0)" id="sikesFillCurrentKeyLink" class="text-decoration-none text-teal d-none fw-semibold" onclick="sikesFillExistingKey()">Gunakan Kunci Saat Ini</a>
                            </div>
                        </div>

                        <div id="sikesCfgNotice" class="d-none small alert alert-info py-1 px-2 mt-2 mb-0" style="font-size:.72rem"></div>

                        <?php if (Auth::isSuper()): ?>
                        <button type="submit" class="btn btn-sm btn-success w-100 mt-3" id="sikesCfgSaveBtn" style="font-size:.78rem">
                            <i class="bi bi-save me-1"></i> Simpan Konfigurasi
                        </button>
                        <?php else: ?>
                        <div class="alert alert-warning py-1 px-2 mt-2 mb-0" style="font-size:.72rem">
                            <i class="bi bi-info-circle me-1"></i> Hanya Super Admin yang dapat mengubah file konfigurasi.
                        </div>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="sikes-config-box">
                    <h6><i class="bi bi-shield-check text-primary"></i> Minta Kode OTP ke Gmail Anda</h6>
                    <p class="text-muted small mb-2" style="font-size:.74rem">
                        Masukkan alamat Gmail Anda. Kode verifikasi 6 digit OTP akan langsung dikirimkan ke kotak masuk Gmail yang Anda ketik.
                    </p>

                    <!-- Input Alamat Gmail Tujuan Pengiriman OTP yang Diisi User -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold small mb-1" style="font-size:.76rem;">Alamat Gmail Penerima:</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="sikesOtpCustomEmail" 
                                   value="" 
                                   placeholder="namaanda@gmail.com" required>
                        </div>
                        <div class="text-muted" style="font-size:.68rem; margin-top:3px;">
                            Contoh: <code>contoh.akun@gmail.com</code>
                        </div>
                    </div>

                    <div id="sikesOtpStatusBox" class="p-2 rounded bg-light border mb-2" style="font-size:.74rem;">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Status Otorisasi:</span> 
                            <strong id="sikesOtpStatusLabel" class="text-secondary">Belum Terverifikasi</strong>
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-primary w-100" id="sikesOtpRequestBtn" style="font-size:.76rem" onclick="sikesRequestOtp()">
                        <i class="bi bi-send-check me-1"></i> Kirim Kode OTP Sekarang
                    </button>

                    <!-- Form Input OTP (muncul setelah minta OTP) -->
                    <div id="sikesOtpInputArea" class="mt-2 d-none">
                        <label class="form-label fw-semibold small mb-1" style="font-size:.74rem;">Masukkan 6 Digit OTP dari Gmail:</label>
                        <div class="input-group input-group-sm">
                            <input type="text" maxlength="6" class="form-control text-center fw-bold letter-spacing-2" id="sikesOtpCode" placeholder="Contoh: 582190">
                            <button class="btn btn-success" type="button" onclick="sikesVerifyOtp()">Verifikasi OTP</button>
                        </div>
                        <div class="small text-muted mt-1" style="font-size:.68rem;" id="sikesOtpNoticeText">
                            Periksa kotak masuk (Inbox/Spam) Gmail Anda untuk melihat kode 6 digit.
                        </div>
                    </div>
                </div>

                <div class="sikes-config-box">
                    <h6><i class="bi bi-trash3-fill text-danger"></i> Reset Percakapan</h6>
                    <p class="text-muted small mb-2" style="font-size:.75rem">
                        Hapus riwayat pertanyaan dan jawaban yang tersimpan di sesi browser.
                    </p>
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" style="font-size:.75rem" onclick="sikesAiClearChat()">
                        <i class="bi bi-eraser-fill me-1"></i> Bersihkan Semua Riwayat
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- JAVASCRIPT LOGIC ASISTEN AI                                    -->
<!-- ============================================================== -->
<script>
(function() {
    const CSRF_TOKEN = '<?= e($csrfToken) ?>';
    const BASE_URL   = '<?= rtrim(base_url(), '/') ?>';

    // State
    let isOpen = false;
    let notifLoaded = false;
    let configLoaded = false;

    // Toggle Modal Card
    window.sikesAiToggle = function() {
        const card = document.getElementById('sikesAiCard');
        const fab = document.getElementById('sikesAiFab');
        const fabIcon = document.getElementById('sikesFabIcon');

        isOpen = !isOpen;
        if (isOpen) {
            card.classList.add('open');
            fabIcon.className = 'bi bi-x-lg';
            if (!notifLoaded) sikesAiFetchNotifications();
            setTimeout(() => document.getElementById('sikesChatInput').focus(), 150);
        } else {
            card.classList.remove('open');
            fabIcon.className = 'bi bi-robot';
        }
    };

    // Tab Switcher
    window.sikesAiSwitchTab = function(tabName) {
        ['chat', 'pro', 'notif', 'config'].forEach(t => {
            const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
            const pane = document.getElementById('pane' + t.charAt(0).toUpperCase() + t.slice(1));
            if (t === tabName) {
                btn.classList.add('active');
                pane.classList.add('active');
            } else {
                btn.classList.remove('active');
                pane.classList.remove('active');
            }
        });

        if (tabName === 'notif' && !notifLoaded) sikesAiFetchNotifications();
        if (tabName === 'config' && !configLoaded) sikesAiFetchConfig();
    };

    // Helper format Markdown aman
    function renderSafeMarkdown(text) {
        const div = document.createElement('div');
        div.textContent = text;
        let safe = div.innerHTML;

        return safe
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/(^|\n)[-–•] /g, '$1• ')
            .replace(/(^|\n)### (.+?)(?=\n|$)/g, '$1<h6 class="fw-bold mt-2 mb-1" style="color:#0f766e">$2</h6>')
            .replace(/(^|\n)## (.+?)(?=\n|$)/g, '$1<h5 class="fw-bold mt-2 mb-1" style="color:#0f766e">$2</h5>')
            .replace(/\n/g, '<br>');
    }

    // Scroll Down helper
    function scrollToBottom(containerId) {
        const el = document.getElementById(containerId);
        if (el) el.scrollTop = el.scrollHeight;
    }

    // ==============================================================
    // 1. CHATBOT REGULER
    // ==============================================================
    window.sikesAiSendMessage = async function(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('sikesChatInput');
        const text = input.value.trim();
        if (!text) return;

        appendMessage('chat', text, 'user');
        input.value = '';

        const typing = document.getElementById('sikesTyping');
        const btn = document.getElementById('sikesSendBtn');
        typing.classList.remove('d-none');
        btn.disabled = true;
        scrollToBottom('sikesChatList');

        try {
            const res = await fetch(BASE_URL + '/ai/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: CSRF_TOKEN, message: text })
            });
            const data = await res.json();
            typing.classList.add('d-none');

            if (data.ok) {
                appendMessage('chat', data.reply, 'ai');
            } else {
                appendMessage('chat', data.error || 'Terjadi kesalahan sistem.', 'ai', true);
            }
        } catch (err) {
            typing.classList.add('d-none');
            appendMessage('chat', 'Gagal tersambung ke server AI. Cek koneksi Anda.', 'ai', true);
        } finally {
            btn.disabled = false;
            input.focus();
            scrollToBottom('sikesChatList');
        }
    };

    window.sikesAiAskChip = function(query) {
        document.getElementById('sikesChatInput').value = query;
        sikesAiSendMessage();
    };

    window.sikesAiAskProChip = function(query) {
        document.getElementById('sikesProChatInput').value = query;
        sikesAiSendProMessage();
    };

    // ==============================================================
    // 2. CHATBOT+ (PRO)
    // ==============================================================
    window.sikesAiRunProAnalysis = function() {
        sikesAiSendProMessage(null, 'Lakukan analisis komprehensif data kesehatan terkini, tren penyakit, dan rekomendasi operasional pondok.');
    };

    window.sikesAiSendProMessage = async function(e, customPrompt) {
        if (e) e.preventDefault();
        const input = document.getElementById('sikesProChatInput');
        const prompt = customPrompt || input.value.trim();
        if (!prompt && !customPrompt) return;

        appendMessage('pro', prompt, 'user');
        if (!customPrompt) input.value = '';

        const typing = document.getElementById('sikesProTyping');
        const btn = document.getElementById('sikesProSendBtn');
        typing.classList.remove('d-none');
        btn.disabled = true;
        scrollToBottom('sikesProChatList');

        try {
            const res = await fetch(BASE_URL + '/ai/chat-pro', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: CSRF_TOKEN, prompt: prompt })
            });
            const data = await res.json();
            typing.classList.add('d-none');

            if (data.ok) {
                appendMessage('pro', data.reply, 'ai', false, true);
            } else {
                appendMessage('pro', data.error || 'Terjadi kesalahan analisis.', 'ai', true);
            }
        } catch (err) {
            typing.classList.add('d-none');
            appendMessage('pro', 'Koneksi gagal menghubungi server analisis.', 'ai', true);
        } finally {
            btn.disabled = false;
            scrollToBottom('sikesProChatList');
        }
    };

    function appendMessage(target, text, sender, isError = false, isPro = false) {
        const container = document.getElementById(target === 'pro' ? 'sikesProChatList' : 'sikesChatList');
        const wrap = document.createElement('div');
        wrap.className = 'sikes-bubble-wrap ' + sender;

        const bubble = document.createElement('div');
        bubble.className = 'sikes-bubble' + (isError ? ' err-style' : '') + (isPro ? ' pro-style' : '');

        if (sender === 'user') {
            const div = document.createElement('div');
            div.textContent = text;
            bubble.innerHTML = div.innerHTML;
        } else {
            bubble.innerHTML = renderSafeMarkdown(text);
        }

        const time = document.createElement('span');
        time.className = 'time-stamp';
        const now = new Date();
        time.textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        bubble.appendChild(time);

        wrap.appendChild(bubble);
        container.appendChild(wrap);
        scrollToBottom(target === 'pro' ? 'sikesProChatList' : 'sikesChatList');
    }

    // ==============================================================
    // 3. NOTIFIKASI SISTEM (REALTIME POLLING & AUTO-REFRESH)
    // ==============================================================
    let lastNotifCount = null;
    let notifPollInterval = null;
    let seenWarnings = new Set();
    let initialLoadDone = false;

    // Fungsi menampilkan Toast Pop-up Real-time
    function showRealtimeToast(warning) {
        const container = document.getElementById('sikesToastContainer');
        if (!container) return;

        const isDanger = warning.level === 'danger';
        const toast = document.createElement('div');
        toast.className = `sikes-realtime-toast ${isDanger ? 'danger' : 'warning'}`;
        toast.innerHTML = `
            <div class="d-flex align-items-start p-3 gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" 
                     style="width: 38px; height: 38px; background: ${isDanger ? '#ef4444' : '#f59e0b'};">
                    <i class="bi ${warning.icon || (isDanger ? 'bi-exclamation-triangle-fill' : 'bi-bell-fill')} fs-5"></i>
                </div>
                <div class="flex-grow-1" style="min-width: 0;">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="badge ${isDanger ? 'bg-danger' : 'bg-warning text-dark'} small" style="font-size: .65rem;">
                            ${isDanger ? 'PERINGATAN KRITIS' : 'PERHATIAN'}
                        </span>
                        <small class="text-muted" style="font-size: .7rem;">Baru Saja</small>
                    </div>
                    <div class="fw-bold text-dark text-truncate mb-1" style="font-size: .85rem;">${warning.title}</div>
                    <div class="text-muted small mb-2" style="font-size: .75rem; line-height: 1.3;">${warning.detail || ''}</div>
                    <div class="d-flex gap-2">
                        ${warning.url ? `<a href="${warning.url}" class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold" style="font-size: .72rem;">Buka Menu</a>` : ''}
                        <button type="button" class="btn btn-sm btn-light border py-0 px-2 text-muted" style="font-size: .72rem;" onclick="this.closest('.sikes-realtime-toast').remove()">Tutup</button>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-sm flex-shrink-0" style="font-size: .65rem;" onclick="this.closest('.sikes-realtime-toast').remove()"></button>
            </div>
        `;

        container.appendChild(toast);

        // Auto remove setelah 7 detik
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.animation = 'toastSlideOut .3s cubic-bezier(0.16, 1, 0.3, 1) forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, 7000);
    }

    window.sikesAiFetchNotifications = async function(isSilent = false) {
        const list = document.getElementById('sikesNotifList');
        const badge = document.getElementById('sikesNotifBadge');
        const tabBadge = document.getElementById('sikesTabNotifBadge');
        const navBadge = document.getElementById('navNotifBadge');
        const navHeaderBadge = document.getElementById('navNotifHeaderBadge');
        const navItems = document.getElementById('navNotifItems');

        try {
            const res = await fetch(BASE_URL + '/ai/notifications?_t=' + Date.now());
            const data = await res.json();
            notifLoaded = true;

            if (data.ok && Array.isArray(data.warnings)) {
                const count = data.warnings.length;

                // Cek item peringatan baru untuk memunculkan Toast Pop-up Real-time
                if (initialLoadDone && isSilent) {
                    data.warnings.forEach(w => {
                        const key = (w.title || '') + '|' + (w.detail || '');
                        if (!seenWarnings.has(key)) {
                            showRealtimeToast(w);
                        }
                    });
                }

                // Perbarui cache set peringatan yang sudah dilihat
                seenWarnings.clear();
                data.warnings.forEach(w => {
                    seenWarnings.add((w.title || '') + '|' + (w.detail || ''));
                });
                initialLoadDone = true;

                // Animasi badge jika ada perubahan notifikasi baru
                if (lastNotifCount !== null && count > lastNotifCount && isSilent) {
                    if (badge) {
                        badge.classList.add('animate__animated', 'animate__heartBeat');
                        setTimeout(() => badge.classList.remove('animate__animated', 'animate__heartBeat'), 1500);
                    }
                    if (navBadge) {
                        navBadge.classList.add('animate__animated', 'animate__heartBeat');
                        setTimeout(() => navBadge.classList.remove('animate__animated', 'animate__heartBeat'), 1500);
                    }
                }
                lastNotifCount = count;

                if (count > 0) {
                    // Update Floating Widget Badge & Tab Badge
                    if (badge) {
                        badge.textContent = count;
                        badge.classList.remove('d-none');
                    }
                    if (tabBadge) {
                        tabBadge.textContent = count;
                        tabBadge.classList.remove('d-none');
                    }

                    // Update Navbar Bell Badge
                    if (navBadge) {
                        navBadge.textContent = count;
                        navBadge.classList.remove('d-none');
                    }
                    if (navHeaderBadge) {
                        navHeaderBadge.textContent = count + ' Isu';
                    }

                    // Render list di panel AI widget
                    let html = '';
                    // Render list di dropdown Navbar
                    let navHtml = '';

                    data.warnings.forEach(w => {
                        const isDanger = w.level === 'danger';
                        const iconClass = w.icon || (isDanger ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');

                        html += `
                        <div class="sikes-notif-item ${isDanger ? 'danger' : 'warning'}">
                            <div class="sikes-notif-icon">
                                <i class="bi ${iconClass}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold" style="font-size:.82rem">${w.title}</div>
                                <div class="text-muted" style="font-size:.74rem">${w.detail || ''}</div>
                                ${w.url ? `<a href="${w.url}" class="btn btn-sm btn-link p-0 text-decoration-none mt-1 fw-semibold" style="font-size:.72rem">Buka Menu Terkait &rarr;</a>` : ''}
                            </div>
                        </div>`;

                        navHtml += `
                        <a href="${w.url || '#'}" class="nav-notif-row">
                            <div class="flex-shrink-0">
                                <span class="badge ${isDanger ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning-emphasis'} p-2 rounded-circle">
                                    <i class="bi ${iconClass}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1" style="min-width:0">
                                <div class="fw-semibold text-dark text-truncate" style="font-size:.8rem">${w.title}</div>
                                <div class="text-muted small text-truncate" style="font-size:.72rem">${w.detail || ''}</div>
                            </div>
                        </a>`;
                    });

                    if (list) list.innerHTML = html;
                    if (navItems) navItems.innerHTML = navHtml;
                } else {
                    if (badge) badge.classList.add('d-none');
                    if (tabBadge) tabBadge.classList.add('d-none');
                    if (navBadge) navBadge.classList.add('d-none');
                    if (navHeaderBadge) navHeaderBadge.textContent = '0 Isu';

                    const emptyState = `
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle-fill text-success fs-2 mb-2 d-block"></i>
                        <div class="fw-bold small">Semua Indikator Normal</div>
                        <small style="font-size:.72rem">Tidak ada peringatan kritis pada stok obat, kamar, atau kasus aktif.</small>
                    </div>`;

                    if (list) list.innerHTML = emptyState;
                    if (navItems) navItems.innerHTML = emptyState;
                }
            }
        } catch (err) {
            if (!isSilent) {
                if (list) list.innerHTML = '<div class="alert alert-danger p-2 small">Gagal memuat notifikasi sistem.</div>';
                if (navItems) navItems.innerHTML = '<div class="p-3 text-center text-danger small">Gagal memuat notifikasi.</div>';
            }
        }
    };

    function startRealtimeNotifPolling() {
        if (notifPollInterval) clearInterval(notifPollInterval);
        // Polling setiap 5 detik untuk notifikasi realtime
        notifPollInterval = setInterval(() => {
            sikesAiFetchNotifications(true);
        }, 5000);
    }

    // ==============================================================
    // 4. KONFIGURASI AI
    // ==============================================================
    const PROVIDER_ENDPOINTS = {
        groq:   'https://api.groq.com/openai/v1/chat/completions',
        gemini: 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
        openai: 'https://api.openai.com/v1/chat/completions',
        ollama: 'http://localhost:11434/v1/chat/completions'
    };

    window.sikesAiOnProviderChange = function() {
        const prov = document.getElementById('sikesCfgProvider').value;
        const endpointInput = document.getElementById('sikesCfgEndpoint');
        const endpointGroup = document.getElementById('sikesCfgEndpointGroup');
        const chatModel = document.getElementById('sikesCfgChatModel');
        const proModel  = document.getElementById('sikesCfgProModel');

        if (prov === 'custom') {
            endpointGroup.style.display = 'block';
        } else {
            endpointGroup.style.display = 'none';
            if (PROVIDER_ENDPOINTS[prov]) {
                endpointInput.value = PROVIDER_ENDPOINTS[prov];
            }
        }

        // Rekomendasi model cerdas per provider jika berganti
        if (prov === 'gemini') {
            if (!chatModel.value.includes('gemini')) chatModel.value = 'gemini-1.5-flash';
            if (!proModel.value.includes('gemini')) proModel.value = 'gemini-1.5-pro';
        } else if (prov === 'openai') {
            if (!chatModel.value.includes('gpt')) chatModel.value = 'gpt-4o-mini';
            if (!proModel.value.includes('gpt')) proModel.value = 'gpt-4o';
        } else if (prov === 'groq') {
            if (!chatModel.value.includes('llama')) chatModel.value = 'llama-3.1-8b-instant';
            if (!proModel.value.includes('llama')) proModel.value = 'llama-3.3-70b-versatile';
        } else if (prov === 'ollama') {
            chatModel.value = 'llama3.1:latest';
            proModel.value  = 'llama3.3:latest';
        }
    };

    // Toggle show/hide API Key
    window.sikesToggleApiKeyVisibility = function() {
        const inp = document.getElementById('sikesCfgApiKey');
        const icon = document.getElementById('sikesApiKeyEyeIcon');
        if (!inp) return;

        if (inp.type === 'password') {
            inp.type = 'text';
            if (icon) {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        } else {
            inp.type = 'password';
            if (icon) {
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye-mute', 'bi-eye');
            }
        }
    };

    let sikesLoadedFullKey = '';
    window.sikesFillExistingKey = function() {
        if (!sikesLoadedFullKey) return;
        const inp = document.getElementById('sikesCfgApiKey');
        if (inp) {
            inp.value = sikesLoadedFullKey;
            inp.type = 'text';
            const icon = document.getElementById('sikesApiKeyEyeIcon');
            if (icon) {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        }
    };

    window.sikesAiFetchConfig = async function() {
        try {
            const res = await fetch(BASE_URL + '/ai/config');
            const data = await res.json();
            configLoaded = true;

            if (data.ok) {
                document.getElementById('sikesCfgChatModel').value = data.chat_model || '';
                document.getElementById('sikesCfgProModel').value = data.model || '';
                document.getElementById('sikesCfgEndpoint').value = data.endpoint || '';
                document.getElementById('sikesCfgCurrentKey').textContent = data.api_key || 'Belum diatur';

                // Simpan kunci lengkap jika user Super Admin & tampilkan tombol pasang
                sikesLoadedFullKey = data.full_key || '';
                const fillLink = document.getElementById('sikesFillCurrentKeyLink');
                if (fillLink) {
                    if (sikesLoadedFullKey) {
                        fillLink.classList.remove('d-none');
                    } else {
                        fillLink.classList.add('d-none');
                    }
                }

                // Tentukan pilihan provider di dropdown berdasarkan URL endpoint
                const ep = (data.endpoint || '').trim().toLowerCase();
                const sel = document.getElementById('sikesCfgProvider');
                const grp = document.getElementById('sikesCfgEndpointGroup');
                if (ep.includes('groq.com')) {
                    sel.value = 'groq';
                    grp.style.display = 'none';
                } else if (ep.includes('googleapis.com')) {
                    sel.value = 'gemini';
                    grp.style.display = 'none';
                } else if (ep.includes('openai.com')) {
                    sel.value = 'openai';
                    grp.style.display = 'none';
                } else if (ep.includes('11434') || ep.includes('ollama')) {
                    sel.value = 'ollama';
                    grp.style.display = 'none';
                } else {
                    sel.value = 'custom';
                    grp.style.display = 'block';
                }

                const badge = document.getElementById('sikesCfgStatusBadge');
                if (data.enabled && data.has_key) {
                    badge.className = 'badge bg-success';
                    badge.textContent = 'Aktif (Terhubung)';
                    document.getElementById('sikesOnlineDot').style.background = '#25D366';
                } else {
                    badge.className = 'badge bg-danger';
                    badge.textContent = 'Perlu API Key';
                    document.getElementById('sikesOnlineDot').style.background = '#ef4444';
                }
            }
        } catch (e) {
            console.error(e);
        }
    };

    // ==============================================================
    // 5. FITUR NOTIFIKASI OTP REAL-TIME KE GMAIL
    // ==============================================================
    window.sikesRequestOtp = async function() {
        const btn = document.getElementById('sikesOtpRequestBtn');
        const inputArea = document.getElementById('sikesOtpInputArea');
        const emailInput = document.getElementById('sikesOtpCustomEmail');
        const targetEmail = emailInput ? emailInput.value.trim() : '';
        const noticeText = document.getElementById('sikesOtpNoticeText');

        if (!targetEmail || !targetEmail.includes('@')) {
            alert('Silakan masukkan alamat Gmail Anda terlebih dahulu (misal: namaanda@gmail.com).');
            if (emailInput) emailInput.focus();
            return;
        }

        if (btn) btn.disabled = true;

        try {
            const res = await fetch(BASE_URL + '/ai/otp/request', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'konfigurasi_ai',
                    email: targetEmail
                })
            });
            const data = await res.json();

            if (data.ok) {
                if (inputArea) inputArea.classList.remove('d-none');
                if (noticeText) {
                    noticeText.textContent = data.mail_sent 
                        ? 'Kode OTP 6 digit telah berhasil dikirim ke ' + data.email + '. Silakan cek kotak masuk/spam Gmail Anda.'
                        : 'Kode OTP untuk ' + data.email + ' telah dibuat. Cek notifikasi toast layar Anda.';
                }

                // Munculkan notifikasi Toast OTP Real-time
                const otpToast = {
                    level: 'warning',
                    icon: 'bi-envelope-check-fill',
                    title: 'OTP DIKIRIM KE GMAIL: ' + data.email,
                    detail: data.mail_sent 
                        ? 'Email OTP telah meluncur ke ' + data.email + '. Masukkan 6 digit kodenya di bawah. Berlaku 5 menit.'
                        : 'Kode OTP: ' + data.otp + ' (Tujuan: ' + data.email + '). Berlaku 5 menit.'
                };
                showRealtimeToast(otpToast);
            } else {
                alert(data.error || 'Gagal meminta OTP.');
            }
        } catch (e) {
            console.error(e);
            alert('Koneksi terputus saat meminta OTP.');
        } finally {
            if (btn) btn.disabled = false;
        }
    };

    window.sikesVerifyOtp = async function() {
        const codeInput = document.getElementById('sikesOtpCode');
        const code = codeInput ? codeInput.value.trim() : '';
        const statusLabel = document.getElementById('sikesOtpStatusLabel');

        if (!code || code.length !== 6) {
            alert('Harap masukkan 6 digit kode OTP secara lengkap.');
            return;
        }

        try {
            const res = await fetch(BASE_URL + '/ai/otp/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code })
            });
            const data = await res.json();

            if (data.ok) {
                if (statusLabel) {
                    statusLabel.className = 'text-success fw-bold';
                    statusLabel.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Terverifikasi Penuh';
                }
                const successToast = {
                    level: 'safe',
                    icon: 'bi-check-circle-fill',
                    title: 'Otorisasi Berhasil',
                    detail: 'Kode OTP valid. Akses konfigurasi & aksi penting kini telah disetujui.'
                };
                showRealtimeToast(successToast);
                if (codeInput) codeInput.value = '';
                document.getElementById('sikesOtpInputArea').classList.add('d-none');
            } else {
                alert(data.error || 'Verifikasi OTP gagal.');
            }
        } catch (e) {
            console.error(e);
            alert('Koneksi terputus saat verifikasi OTP.');
        }
    };

    window.sikesAiSaveConfig = async function(e) {
        e.preventDefault();
        const notice = document.getElementById('sikesCfgNotice');
        const btn = document.getElementById('sikesCfgSaveBtn');

        const payload = {
            csrf_token: CSRF_TOKEN,
            chat_model: document.getElementById('sikesCfgChatModel').value.trim(),
            model:      document.getElementById('sikesCfgProModel').value.trim(),
            endpoint:   document.getElementById('sikesCfgEndpoint').value.trim(),
            api_key:    document.getElementById('sikesCfgApiKey').value.trim(),
        };

        btn.disabled = true;
        notice.classList.add('d-none');

        try {
            const res = await fetch(BASE_URL + '/ai/config', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            notice.classList.remove('d-none');
            if (data.ok) {
                notice.className = 'alert alert-success py-1 px-2 mt-2 mb-0';
                notice.textContent = data.message || 'Konfigurasi berhasil disimpan.';
                document.getElementById('sikesCfgApiKey').value = '';
                sikesAiFetchConfig();
            } else {
                notice.className = 'alert alert-danger py-1 px-2 mt-2 mb-0';
                notice.textContent = data.error || 'Gagal menyimpan konfigurasi.';
            }
        } catch (err) {
            notice.classList.remove('d-none');
            notice.className = 'alert alert-danger py-1 px-2 mt-2 mb-0';
            notice.textContent = 'Koneksi gagal saat menyimpan.';
        } finally {
            btn.disabled = false;
        }
    };

    // Bersihkan sesi percakapan (refresh chat)
    window.sikesAiClearChat = async function() {
        try {
            await fetch(BASE_URL + '/ai/chat/clear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: CSRF_TOKEN })
            });
            document.getElementById('sikesChatList').innerHTML = `
            <div class="sikes-bubble-wrap ai">
                <div class="sikes-bubble">
                    Riwayat percakapan telah dibersihkan. Ada yang bisa saya bantu terkait data kesehatan pondok?
                    <span class="time-stamp">Baru saja</span>
                </div>
            </div>`;
            document.getElementById('sikesProChatList').innerHTML = `
            <div class="sikes-bubble-wrap ai">
                <div class="sikes-bubble pro-style">
                    Sesi Chatbot+ di-reset. Siap untuk analisis data baru.
                </div>
            </div>`;
        } catch (e) {
            console.error(e);
        }
    };

    // Muat notifikasi di background saat pertama kali halaman terbuka dan aktifkan polling realtime
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            sikesAiFetchNotifications(false);
            startRealtimeNotifPolling();
        }, 800);
    });

    // Perbarui otomatis saat tab browser kembali aktif
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            sikesAiFetchNotifications(true);
        }
    });
})();
</script>
