<?php

use App\Core\Router;

/** @var Router $router */

/* ============================================================
   PUBLIK (tanpa login)
   ============================================================ */
 $router->get('/login', 'AuthController@showLogin');
 $router->post('/login', 'AuthController@login');
 $router->get('/login/otp', 'AuthController@showLoginOtp');
 $router->post('/login/otp-send', 'AuthController@sendLoginOtp');
 $router->post('/login/otp-verify', 'AuthController@verifyLoginOtp');
 $router->get('/login/cancel', 'AuthController@cancelLogin');
 $router->get('/logout', 'AuthController@logout');

/* ============================================================
   VERIFIKASI QR E-SURAT (publik — tanpa login, permission null)
   ============================================================ */
 $router->get('/verifikasi/{token}', 'SuratIzinController@verify', null);

/* ============================================================
   WAJIB LOGIN
   ============================================================ */
 $router->get('/', 'DashboardController@index', 'auth');
 $router->get('/dashboard', 'DashboardController@index', 'auth');

/* ============================================================
   AJAX CASCADING DROPDOWN (Master Data)
   ============================================================ */
 $router->get('/master/blok-options', 'BlokController@options', 'auth');
 $router->get('/master/kamar-options', 'KamarController@options', 'auth');

/* ============================================================
   ASURANSI BPJS (baru v12) — literal SEBELUM rute {id}
   ============================================================ */
 $router->get('/bpjs', 'BpjsController@index', 'bpjs.view');
 $router->get('/bpjs/create', 'BpjsController@create', 'bpjs.manage');
 $router->post('/bpjs', 'BpjsController@store', 'bpjs.manage');
 $router->get('/bpjs/{id}/edit', 'BpjsController@edit', 'bpjs.manage');
 $router->post('/bpjs/{id}', 'BpjsController@update', 'bpjs.manage');
 $router->post('/bpjs/{id}/delete', 'BpjsController@destroy', 'bpjs.manage');

/* ============================================================
   SURAT RUJUKAN EKSTERNAL (baru v12) — literal SEBELUM rute {id}
   ============================================================ */
 $router->get('/rujukan', 'RujukanController@index', 'rujukan.view');
 $router->get('/rujukan/create', 'RujukanController@create', 'rujukan.create');
 $router->post('/rujukan', 'RujukanController@store', 'rujukan.create');
 $router->get('/rujukan/pemeriksaan-options', 'RujukanController@pemeriksaanOptions', 'rujukan.create');
 $router->get('/rujukan/{id}', 'RujukanController@show', 'rujukan.view');
 $router->get('/rujukan/{id}/cetak', 'RujukanController@cetak', 'rujukan.view');

/* ============================================================
   DAPUR & GIZI (Tahap 11)
   ============================================================ */
 $router->get('/gizi', 'GiziController@index', 'gizi.view');
 $router->get('/gizi/create', 'GiziController@create', 'gizi.rekomendasi');
 $router->post('/gizi', 'GiziController@store', 'gizi.rekomendasi');
 $router->get('/gizi/label-hari-ini', 'GiziController@labelHariIni', 'gizi.view');
 $router->post('/gizi/{id}/distribusi', 'GiziController@distribusi', 'gizi.distribusi');
 $router->get('/gizi/{id}/label', 'GiziController@label', 'gizi.view');
 $router->post('/gizi/{id}/delete', 'GiziController@destroy', 'gizi.rekomendasi');

/* ============================================================
   KEPULANGAN SANTRI SAKIT (Tahap 10)
   ============================================================ */
 $router->get('/kepulangan', 'KepulanganController@index', 'kepulangan.view');
 $router->get('/kepulangan/create', 'KepulanganController@create', 'kepulangan.manage');
 $router->post('/kepulangan', 'KepulanganController@store', 'kepulangan.manage');
 $router->get('/kepulangan/pemeriksaan-options', 'KepulanganController@pemeriksaanOptions', 'kepulangan.manage');
 $router->get('/kepulangan/{id}', 'KepulanganController@show', 'kepulangan.view');
 $router->post('/kepulangan/{id}/keamanan', 'KepulanganController@keamanan', 'kepulangan.manage');
 $router->post('/kepulangan/{id}/pengasuhan', 'KepulanganController@pengasuhan', 'kepulangan.manage');
 $router->post('/kepulangan/{id}/gerbang', 'KepulanganController@gerbang', 'kepulangan.manage');

/* ============================================================
   BUKU TAMU PENJENGUK (Tahap 10)
   ============================================================ */
 $router->get('/buku-tamu', 'BukuTamuController@index', 'buku_tamu.view');
 $router->get('/buku-tamu/create', 'BukuTamuController@create', 'buku_tamu.manage');
 $router->post('/buku-tamu', 'BukuTamuController@store', 'buku_tamu.manage');
 $router->post('/buku-tamu/{id}/pulang', 'BukuTamuController@pulang', 'buku_tamu.manage');
 $router->post('/buku-tamu/{id}/delete', 'BukuTamuController@destroy', 'buku_tamu.manage');

/* ============================================================
   E-SURAT IZIN SAKIT (Tahap 9)
   ============================================================ */
 $router->get('/surat', 'SuratIzinController@index', 'surat.view');
 $router->get('/surat/create', 'SuratIzinController@create', 'surat.create');
 $router->post('/surat', 'SuratIzinController@store', 'surat.create');
 $router->get('/surat/pemeriksaan-options', 'SuratIzinController@pemeriksaanOptions', 'surat.create');
 $router->get('/surat/{id}', 'SuratIzinController@show', 'surat.view');
 $router->get('/surat/{id}/cetak', 'SuratIzinController@cetak', 'surat.view');
 $router->post('/surat/{id}/batal', 'SuratIzinController@batal', 'surat.edit');

/* ============================================================
   PIKET KEBERSIHAN (Tahap 8)
   ============================================================ */
 $router->get('/piket', 'PiketController@index', 'piket.view');
 $router->get('/piket/create', 'PiketController@create', 'piket.manage');
 $router->post('/piket', 'PiketController@store', 'piket.manage');
 $router->get('/piket/{id}', 'PiketController@show', 'piket.view');
 $router->get('/piket/{id}/edit', 'PiketController@edit', 'piket.manage');
 $router->post('/piket/{id}', 'PiketController@update', 'piket.manage');
 $router->post('/piket/{id}/mulai', 'PiketController@mulai', 'piket.manage');
 $router->post('/piket/{id}/selesai', 'PiketController@selesai', 'piket.manage');
 $router->post('/piket/{id}/delete', 'PiketController@destroy', 'piket.manage');

/* ============================================================
   CONTACT TRACING PENULARAN (Tahap 7)
   ============================================================ */
 $router->get('/tracing', 'TracingController@index', 'pemeriksaan.view');
 $router->get('/tracing/kasus/create', 'TracingController@createKasus', 'pemeriksaan.create');
 $router->post('/tracing/kasus', 'TracingController@storeKasus', 'pemeriksaan.create');
 $router->get('/tracing/kasus/{id}', 'TracingController@show', 'pemeriksaan.view');
 $router->post('/tracing/kasus/{id}/tutup', 'TracingController@tutupKasus', 'pemeriksaan.edit');
 $router->post('/tracing/kasus/{id}/resync', 'TracingController@resyncKontak', 'pemeriksaan.edit');
 $router->post('/tracing/kontak/{id}/periksa', 'TracingController@mulaiPeriksa', 'pemeriksaan.edit');
 $router->post('/tracing/kontak/{id}/hasil', 'TracingController@catatHasil', 'pemeriksaan.edit');

/* ============================================================
   SANITASI & LINGKUNGAN (Tahap 6)
   ============================================================ */
 $router->get('/sanitasi', 'SanitasiController@dashboard', 'sanitasi.view');
 $router->get('/sanitasi/inspeksi', 'SanitasiController@histori', 'sanitasi.view');
 $router->get('/sanitasi/inspeksi/create', 'SanitasiController@create', 'sanitasi.create');
 $router->post('/sanitasi/inspeksi', 'SanitasiController@store', 'sanitasi.create');
 $router->get('/sanitasi/inspeksi/{id}', 'SanitasiController@show', 'sanitasi.view');
 $router->get('/sanitasi/inspeksi/{id}/edit', 'SanitasiController@edit', 'sanitasi.edit');
 $router->post('/sanitasi/inspeksi/{id}', 'SanitasiController@update', 'sanitasi.edit');
 $router->post('/sanitasi/inspeksi/{id}/delete', 'SanitasiController@destroy', 'sanitasi.edit');

/* ============================================================
   PEMERIKSAAN KESEHATAN (Tahap 5)
   ============================================================ */
 $router->get('/pemeriksaan', 'PemeriksaanController@index', 'pemeriksaan.view');
 $router->get('/pemeriksaan/create', 'PemeriksaanController@create', 'pemeriksaan.create');
 $router->post('/pemeriksaan', 'PemeriksaanController@store', 'pemeriksaan.create');
 $router->get('/pemeriksaan/riwayat/{santriId}', 'PemeriksaanController@riwayat', 'pemeriksaan.view');
 $router->get('/pemeriksaan/{id}', 'PemeriksaanController@show', 'pemeriksaan.view');
 $router->get('/pemeriksaan/{id}/periksa', 'PemeriksaanController@formPeriksa', 'pemeriksaan.edit');
 $router->post('/pemeriksaan/{id}/periksa', 'PemeriksaanController@periksa', 'pemeriksaan.edit');
 $router->get('/pemeriksaan/{id}/edit', 'PemeriksaanController@edit', 'pemeriksaan.edit');
 $router->post('/pemeriksaan/{id}', 'PemeriksaanController@update', 'pemeriksaan.edit');
 $router->post('/pemeriksaan/{id}/batal', 'PemeriksaanController@batal', 'pemeriksaan.edit');

/* ============================================================
   SKRINING KESEHATAN AWAL (Tahap 4)
   ============================================================ */
 $router->get('/skrining', 'SkriningController@index', 'pemeriksaan.view');
 $router->get('/skrining/create', 'SkriningController@create', 'pemeriksaan.create');
 $router->post('/skrining', 'SkriningController@store', 'pemeriksaan.create');
 $router->get('/skrining/riwayat', 'SkriningController@riwayat', 'pemeriksaan.view');
 $router->get('/skrining/{id}', 'SkriningController@show', 'pemeriksaan.view');
 $router->get('/skrining/{id}/edit', 'SkriningController@edit', 'pemeriksaan.edit');
 $router->post('/skrining/{id}', 'SkriningController@update', 'pemeriksaan.edit');
 $router->post('/skrining/{id}/delete', 'SkriningController@destroy', 'pemeriksaan.edit');

/* ============================================================
   DATA SANTRI (Tahap 3)
   ============================================================ */
 $router->get('/santri', 'SantriController@index', 'santri.view');
 $router->get('/santri/create', 'SantriController@create', 'santri.create');
 $router->post('/santri', 'SantriController@store', 'santri.create');
 $router->get('/santri/{id}', 'SantriController@show', 'santri.view');
 $router->get('/santri/{id}/edit', 'SantriController@edit', 'santri.edit');
 $router->post('/santri/{id}', 'SantriController@update', 'santri.edit');
 $router->post('/santri/{id}/delete', 'SantriController@destroy', 'santri.delete');

/* ============================================================
   WALI SANTRI (Tahap 3)
   ============================================================ */
 $router->get('/wali', 'WaliSantriController@index', 'wali.view');
 $router->get('/wali/create', 'WaliSantriController@create', 'wali.create');
 $router->post('/wali', 'WaliSantriController@store', 'wali.create');
 $router->get('/wali/{id}/edit', 'WaliSantriController@edit', 'wali.edit');
 $router->post('/wali/{id}', 'WaliSantriController@update', 'wali.edit');
 $router->post('/wali/{id}/delete', 'WaliSantriController@destroy', 'wali.delete');

/* ============================================================
   ASRAMA (Tahap 3)
   ============================================================ */
 $router->get('/asrama', 'AsramaController@index', 'asrama.view');
 $router->get('/asrama/create', 'AsramaController@create', 'asrama.manage');
 $router->post('/asrama', 'AsramaController@store', 'asrama.manage');
 $router->get('/asrama/{id}/edit', 'AsramaController@edit', 'asrama.manage');
 $router->post('/asrama/{id}', 'AsramaController@update', 'asrama.manage');
 $router->post('/asrama/{id}/delete', 'AsramaController@destroy', 'asrama.manage');

/* ============================================================
   BLOK (Tahap 3)
   ============================================================ */
 $router->get('/blok', 'BlokController@index', 'asrama.view');
 $router->get('/blok/create', 'BlokController@create', 'asrama.manage');
 $router->post('/blok', 'BlokController@store', 'asrama.manage');
 $router->get('/blok/{id}/edit', 'BlokController@edit', 'asrama.manage');
 $router->post('/blok/{id}', 'BlokController@update', 'asrama.manage');
 $router->post('/blok/{id}/delete', 'BlokController@destroy', 'asrama.manage');

/* ============================================================
   KAMAR (Tahap 3)
   ============================================================ */
 $router->get('/kamar', 'KamarController@index', 'asrama.view');
 $router->get('/kamar/create', 'KamarController@create', 'asrama.manage');
 $router->post('/kamar', 'KamarController@store', 'asrama.manage');
 $router->get('/kamar/{id}', 'KamarController@detail', 'asrama.view');
 $router->get('/kamar/{id}/edit', 'KamarController@edit', 'asrama.manage');
 $router->post('/kamar/{id}', 'KamarController@update', 'asrama.manage');
 $router->post('/kamar/{id}/delete', 'KamarController@destroy', 'asrama.manage');

/* ============================================================
   MANAJEMEN SISTEM (Tahap 2)
   ============================================================ */
 $router->get('/users', 'UserController@index', 'users.view');
 $router->get('/users/create', 'UserController@create', 'users.create');
 $router->post('/users', 'UserController@store', 'users.create');
 $router->get('/users/{id}/edit', 'UserController@edit', 'users.edit');
 $router->post('/users/{id}', 'UserController@update', 'users.edit');
 $router->post('/users/{id}/delete', 'UserController@destroy', 'users.delete');

 $router->get('/roles', 'RoleController@index', 'roles.view');
 $router->get('/roles/create', 'RoleController@create', 'roles.manage');
 $router->post('/roles', 'RoleController@store', 'roles.manage');
 $router->get('/roles/{id}/edit', 'RoleController@edit', 'roles.manage');
 $router->post('/roles/{id}', 'RoleController@update', 'roles.manage');
 $router->post('/roles/{id}/delete', 'RoleController@destroy', 'roles.manage');


/* ============================================================
   MANAJEMEN OBAT (baru v13) — literal SEBELUM rute {id}
   - Stok berubah HANYA lewat transaksi database (model).
   ============================================================ */
 $router->get('/obat', 'ObatController@index', 'obat.view');
 $router->get('/obat/create', 'ObatController@create', 'obat.manage');
 $router->post('/obat', 'ObatController@store', 'obat.manage');
 $router->get('/obat/transaksi', 'ObatController@transaksi', 'obat.view');
 $router->get('/obat/masuk', 'ObatController@masukForm', 'obat.manage');
 $router->post('/obat/masuk', 'ObatController@masukStore', 'obat.manage');
 $router->get('/obat/pemakaian', 'ObatController@pemakaianForm', 'obat.pemakaian');
 $router->post('/obat/pemakaian', 'ObatController@pemakaianStore', 'obat.pemakaian');
 $router->get('/obat/laporan', 'ObatController@laporan', 'obat.view');
 $router->get('/obat/{id}/edit', 'ObatController@edit', 'obat.manage');
 $router->post('/obat/{id}', 'ObatController@update', 'obat.manage');
 $router->post('/obat/{id}/delete', 'ObatController@destroy', 'obat.manage');


 /* ============================================================
   DASHBOARD EKSEKUTIF (baru v14) — khusus Kyai/Pimpinan
   (role-gate tambahan di controller; Super Admin bypass)
   ============================================================ */
 $router->get('/eksekutif', 'EksekutifController@index', 'laporan.view');
 $router->get('/eksekutif/export', 'EksekutifController@export', 'laporan.view');
 $router->get('/eksekutif/cetak', 'EksekutifController@cetak', 'laporan.view');

 /* ============================================================
   AI HEALTH ASSISTANT (baru v15) — asisten analisis data
   Role-gate tambahan di controller (kyai/tenaga-medis/super)
   ============================================================ */
 $router->get('/ai', 'AiAssistantController@index', 'laporan.view');
 $router->post('/ai/enhance', 'AiAssistantController@enhance', 'laporan.view');


 /* ============================================================
   REKAM MEDIS (baru v16) — konsolidasi read-only
   ============================================================ */
 $router->get('/rekam-medis', 'RekamMedisController@index', 'rekam_medis.view');
 $router->get('/rekam-medis/{id}', 'RekamMedisController@show', 'rekam_medis.view');

/* ============================================================
   PENGASUHAN (baru v16) — literal SEBELUM rute {id}
   ============================================================ */
 $router->get('/pengasuhan', 'PengasuhanController@index', 'pengasuhan.view');
 $router->get('/pengasuhan/create', 'PengasuhanController@create', 'pengasuhan.create');
 $router->post('/pengasuhan', 'PengasuhanController@store', 'pengasuhan.create');
 $router->get('/pengasuhan/{id}/edit', 'PengasuhanController@edit', 'pengasuhan.edit');
 $router->post('/pengasuhan/{id}', 'PengasuhanController@update', 'pengasuhan.edit');
 $router->post('/pengasuhan/{id}/selesai', 'PengasuhanController@selesai', 'pengasuhan.edit');
 $router->post('/pengasuhan/{id}/delete', 'PengasuhanController@destroy', 'pengasuhan.edit');

/* ============================================================
   KEUANGAN (baru v16) — literal SEBELUM rute {id}
   ============================================================ */
 $router->get('/keuangan', 'KeuanganController@index', 'keuangan.view');
 $router->get('/keuangan/create', 'KeuanganController@create', 'keuangan.create');
 $router->post('/keuangan', 'KeuanganController@store', 'keuangan.create');
 $router->get('/keuangan/{id}/edit', 'KeuanganController@edit', 'keuangan.manage');
 $router->post('/keuangan/{id}', 'KeuanganController@update', 'keuangan.manage');
 $router->post('/keuangan/{id}/delete', 'KeuanganController@destroy', 'keuangan.manage');

/* ============================================================
   PUSAT LAPORAN (baru v16)
   ============================================================ */
 $router->get('/laporan', 'LaporanController@index', 'laporan.view');
 $router->get('/laporan/export', 'LaporanController@export', 'laporan.export');
 $router->get('/laporan/cetak', 'LaporanController@cetak', 'laporan.view');

 $router->get('/activity', 'ActivityLogController@index', 'logs.view');

 /* =================================================================
   AI HEALTH ASSISTANT — Floating Widget & Assistant System
   Tersedia untuk semua akun yang telah login (auth)
   ================================================================= */
 $router->post('/ai/chat', 'AiAssistantController@chat', 'auth');
 $router->post('/ai/chat-pro', 'AiAssistantController@chatPro', 'auth');
 $router->post('/ai/chat/clear', 'AiAssistantController@chatClear', 'auth');
 $router->get('/ai/notifications', 'AiAssistantController@notifications', 'auth');
 $router->post('/ai/otp/request', 'AiAssistantController@otpRequest', 'auth');
 $router->post('/ai/otp/verify', 'AiAssistantController@otpVerify', 'auth');
 $router->get('/ai/config', 'AiAssistantController@configGet', 'auth');
 $router->post('/ai/config', 'AiAssistantController@configSave', 'auth');