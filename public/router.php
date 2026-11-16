<?php
// Router untuk PHP built-in server: php -S localhost:8080 router.php
 $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false; // biarkan server menyajikan file statis (css/js)
}
require __DIR__ . '/index.php';