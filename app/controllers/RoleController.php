<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Role;
use App\Models\Permission;

class RoleController extends Controller
{
    private Role $roles;
    private Permission $permissions;

    public function __construct()
    {
        $this->roles       = new Role();
        $this->permissions = new Permission();
    }

    public function index(): void
    {
        $this->view('roles/index', [
            'title' => 'Role & Hak Akses',
            'user'  => Auth::user(),
            'roles' => $this->roles->allWithStats(),
        ]);
    }

    public function create(): void
    {
        $this->view('roles/form', [
            'title'                => 'Tambah Role',
            'user'                 => Auth::user(),
            'item'                 => null,
            'permissionsGrouped'   => $this->permissions->allGrouped(),
            'selectedPermissions'  => [],
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/roles/create');

        $name = $this->input('name') ?? '';
        $slug = $this->slugify($this->input('slug') ?: $name);

        if ($name === '' || $slug === '') {
            Session::setFlash('error', 'Nama role wajib diisi.');
            $this->redirect('/roles/create');
        }
        if ($this->roles->slugExists($slug)) {
            Session::setFlash('error', "Slug role \"{$slug}\" sudah digunakan.");
            $this->redirect('/roles/create');
        }

        $roleId = $this->roles->create([
            'name'        => $name,
            'slug'        => $slug,
            'description' => $this->input('description'),
            'is_system'   => 0,
        ]);
        $this->roles->syncPermissions($roleId, $_POST['permissions'] ?? []);

        log_activity('create', 'role', "Menambah role: {$name} ({$slug})");
        Session::setFlash('success', "Role \"{$name}\" berhasil dibuat.");
        $this->redirect('/roles');
    }

    public function edit($id): void
    {
        $role = $this->roles->find((int) $id);
        if (!$role) {
            Session::setFlash('error', 'Role tidak ditemukan.');
            $this->redirect('/roles');
        }

        $this->view('roles/form', [
            'title'                => 'Edit Role',
            'user'                 => Auth::user(),
            'item'                 => $role,
            'permissionsGrouped'   => $this->permissions->allGrouped(),
            'selectedPermissions'  => $this->roles->permissionIds((int) $role['id']),
        ]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/roles');
        $id   = (int) $id;
        $role = $this->roles->find($id);
        if (!$role) {
            Session::setFlash('error', 'Role tidak ditemukan.');
            $this->redirect('/roles');
        }

        // Role sistem (Super Admin) terkunci sepenuhnya
        if ((int) $role['is_system'] === 1) {
            Session::setFlash('error', 'Role sistem tidak dapat diubah.');
            $this->redirect('/roles');
        }

        $name = $this->input('name') ?? '';
        $slug = $this->slugify($this->input('slug') ?: $name);

        if ($name === '' || $slug === '') {
            Session::setFlash('error', 'Nama role wajib diisi.');
            $this->redirect("/roles/{$id}/edit");
        }
        if ($this->roles->slugExists($slug, $id)) {
            Session::setFlash('error', "Slug role \"{$slug}\" sudah digunakan.");
            $this->redirect("/roles/{$id}/edit");
        }

        $this->roles->update($id, [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $this->input('description'),
        ]);
        $this->roles->syncPermissions($id, $_POST['permissions'] ?? []);

        log_activity('update', 'role', "Mengubah role: {$name} ({$slug})");
        Session::setFlash('success', "Role \"{$name}\" berhasil diperbarui.");
        $this->redirect('/roles');
    }

    public function destroy($id): void
    {
        $this->checkCsrf('/roles');
        $id   = (int) $id;
        $role = $this->roles->find($id);
        if (!$role) {
            Session::setFlash('error', 'Role tidak ditemukan.');
            $this->redirect('/roles');
        }

        if ((int) $role['is_system'] === 1) {
            Session::setFlash('error', 'Role sistem tidak dapat dihapus.');
            $this->redirect('/roles');
        }

        $count = $this->roles->usersCount($id);
        if ($count > 0) {
            Session::setFlash('error', "Role masih dipakai {$count} pengguna. Pindahkan pengguna terlebih dahulu.");
            $this->redirect('/roles');
        }

        $this->roles->delete($id);
        log_activity('delete', 'role', "Menghapus role: {$role['name']} ({$role['slug']})");
        Session::setFlash('success', "Role \"{$role['name']}\" berhasil dihapus.");
        $this->redirect('/roles');
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}