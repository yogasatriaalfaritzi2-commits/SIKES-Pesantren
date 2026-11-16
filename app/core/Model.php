<?php

namespace App\Core;

/**
 * Base Model: CRUD dasar generik berbasis PDO.
 * Turunkan class ini dan set properti $table.
 */
abstract class Model
{
    protected Database $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        return $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC");
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1", [$id]);
    }

    /** Hati-hati: $column harus hardcoded/whitelist, jangan dari input user. */
    public function where(string $column, $value): ?array
    {
        return $this->db->fetch("SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1", [$value]);
    }

    public function create(array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        $this->db->execute($sql, array_values($data));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        // rowCount bisa 0 jika data tidak berubah -> anggap sukses selama query tidak error
        return $this->db->execute(
            "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?",
            $values
        ) >= 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]) > 0;
    }

    public function count(): int
    {
        return (int) ($this->db->fetch("SELECT COUNT(*) AS total FROM {$this->table}")['total'] ?? 0);
    }
}