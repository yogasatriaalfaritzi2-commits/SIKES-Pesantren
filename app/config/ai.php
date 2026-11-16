<?php
/**
 * Konfigurasi AI SIKES — dibaca dari file .env di root project.
 * ------------------------------------------------------------
 * API key GRATIS: https://console.groq.com (daftar -> API Keys -> Create)
 * Alternatif: Google Gemini https://aistudio.google.com
 *   (ubah AI_ENDPOINT ke:
 *    https://generativelanguage.googleapis.com/v1beta/openai/chat/completions
 *    dan AI_MODEL ke: gemini-2.0-flash)
 *
 * PRIVASI: hanya data AGREGAT (angka statistik, tanpa identitas santri)
 * yang dikirim ke API. AI dilarang memberi diagnosis medis.
 *
 * CATATAN: file ini TIDAK boleh memakai namespace.
 */

/* ---------- Aktif/nonaktif chatbot & naratif ---------- */
define('AI_ENABLED', filter_var(env('AI_ENABLED', 'false'), FILTER_VALIDATE_BOOL));

/* ---------- Endpoint API (format OpenAI-compatible) ---------- */
define('AI_ENDPOINT', env('AI_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions'));
define('AI_API_KEY',  env('AI_API_KEY', ''));

/* ---------- Model ---------- */
define('AI_MODEL',      env('AI_MODEL', 'llama-3.3-70b-versatile'));     // analisis/naratif
define('AI_CHAT_MODEL', env('AI_CHAT_MODEL', 'llama-3.1-8b-instant'));   // chatbot (cepat)

/* ---------- Batas ---------- */
define('AI_MAX_TOKENS', (int) env('AI_MAX_TOKENS', 700));
define('AI_TIMEOUT',    (int) env('AI_TIMEOUT', 45));

/* ---------- Riwayat percakapan (session) ---------- */
define('AI_CHAT_HISTORY_MAX', (int) env('AI_CHAT_HISTORY_MAX', 12));