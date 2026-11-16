<?php

namespace App\Models;

use App\Core\Model;

class Permission extends Model
{
    protected string $table = 'permissions';

    public function all(): array
    {
        return $this->db->fetchAll("SELECT * FROM permissions ORDER BY module ASC, id ASC");
    }

    /** Permission terkelompok per modul: ['users' => [...], 'roles' => [...], ...] */
    public function allGrouped(): array
    {
        $grouped = [];
        foreach ($this->all() as $row) {
            $grouped[$row['module']][] = $row;
        }
        return $grouped;
    }
}