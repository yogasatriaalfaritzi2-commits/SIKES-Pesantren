<?php
use App\Core\Session;

 $flashTypes = [
    'success' => 'success',
    'error'   => 'danger',
    'warning' => 'warning',
    'info'    => 'info',
];
foreach ($flashTypes as $key => $bsClass):
    $message = Session::getFlash($key);
    if ($message): ?>
<div class="alert alert-<?= $bsClass ?> alert-dismissible fade show" role="alert">
    <?= e($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
<?php endif;
endforeach; ?>