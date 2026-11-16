<?php
/**
 * Breadcrumb otomatis: [Beranda] > {Title}
 * ------------------------------------------------------------
 * Cara sembunyikan:
 * 1. OTOMATIS: halaman chat AI (/ai) memakai tampilan penuh,
 *    sehingga breadcrumb disembunyikan di sini.
 * 2. MANUAL: dari controller, kirim 'hideBreadcrumb' => true
 *    pada data view — partial ini akan berhenti tanpa render.
 * ------------------------------------------------------------
 */

// 1. Sembunyikan bila controller meminta (flag dari data view)
if (!empty($hideBreadcrumb)) {
    return; // menghentikan eksekusi partial ini saja — valid di file include
}

// 2. Sembunyikan otomatis di halaman chat AI (tampilan penuh)
if (str_starts_with(current_path(), '/ai')) {
    return;
}

 $bc = $breadcrumbs ?? [['label' => 'Beranda', 'url' => base_url('/dashboard')]];
 $bc[] = ['label' => $title ?? '', 'url' => null];
?>
<nav aria-label="breadcrumb" class="mb-2 no-print">
    <ol class="breadcrumb small mb-0">
        <?php foreach ($bc as $i => $bcItem): ?>
            <?php if ($i === count($bc) - 1 || empty($bcItem['url'])): ?>
                <li class="breadcrumb-item active" aria-current="page"><?= e($bcItem['label']) ?></li>
            <?php else: ?>
                <li class="breadcrumb-item"><a href="<?= e($bcItem['url']) ?>"><?= e($bcItem['label']) ?></a></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>