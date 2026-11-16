<?php
/**
 * Generator hash password (jalankan via CLI).
 * Contoh: php database/generate_password_hash.php admin123
 */
if (PHP_SAPI !== 'cli') {
    exit('Script ini hanya dapat dijalankan lewat CLI.');
}

 $password = $argv[1] ?? 'password';
echo "Password : {$password}\n";
echo 'Hash     : ' . password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
echo "Salin hash di atas ke kolom `password` pada tabel users.\n";