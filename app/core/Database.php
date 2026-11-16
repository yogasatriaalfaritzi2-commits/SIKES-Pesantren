<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Koneksi database tunggal (singleton) berbasis PDO.
 * v13: + beginTransaction/commit/rollBack untuk konsistensi stok obat.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                exit('Koneksi database gagal: ' . $e->getMessage()
                    . ' — periksa app/config/config.php dan pastikan database sudah diimpor.');
            }
            exit('Terjadi kesalahan pada server. Silakan coba lagi nanti.');
        }
    }

    public static function getInstance(): Database
    {
        return self::$instance ??= new self();
    }

    /** Jalankan query ter-prepared dan kembalikan statement-nya. */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Ambil satu baris atau null. */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    /** Ambil semua baris. */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /** Jalankan INSERT/UPDATE/DELETE, kembalikan jumlah baris terpengaruh. */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /* ---------- Transaction (BARU v13) ---------- */

    public function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}