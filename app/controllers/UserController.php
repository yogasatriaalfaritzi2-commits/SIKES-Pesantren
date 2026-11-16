<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
    private User $users;
    private Role $roles;

    public function __construct()
    {
        $this->users = new User();
        $this->roles = new Role();
    }

    public function index(): void
    {
        $q    = trim($_GET['q'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->view('users/index', [
            'title' => 'Manajemen Pengguna',
            'user'  => Auth::user(),
            'data'  => $this->users->paginate($page, 10, $q),
            'q'     => $q,
        ]);
    }

    public function create(): void
    {
        $this->view('users/form', [
            'title' => 'Tambah Pengguna',
            'user'  => Auth::user(),
            'item'  => null,
            'roles' => $this->roles->allOrdered(),
        ]);
    }

    public function store(): void
    {
        $this->checkCsrf('/users/create');

        $nama     = $this->input('nama') ?? '';
        $username = $this->input('username') ?? '';
        $email    = $this->input('email');
        $roleId   = (int) ($this->input('role_id') ?? 0);
        $password = $_POST['password'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $errors = $this->validate($nama, $username, $email, $password);
        $role = $this->roles->find($roleId);
        if (!$role) {
            $errors[] = 'Role tidak valid.';
        } elseif ((int) $role['is_system'] === 1 && !Auth::isSuper()) {
            $errors[] = 'Hanya Super Admin yang dapat menetapkan role Super Admin.';
        }

        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect('/users/create');
        }

        $this->users->create([
            'nama'     => $nama,
            'username' => $username,
            'email'    => ($email === '' ? null : $email),
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role_id'  => $roleId,
            'is_active'=> $isActive,
        ]);

        log_activity('create', 'pengguna', "Menambah pengguna: {$username} (role: {$role['name']})");
        Session::setFlash('success', "Pengguna {$nama} berhasil ditambahkan.");
        $this->redirect('/users');
    }

    public function edit($id): void
    {
        $item = $this->users->findWithRole((int) $id);
        if (!$item) {
            Session::setFlash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/users');
        }

        $this->view('users/form', [
            'title' => 'Edit Pengguna',
            'user'  => Auth::user(),
            'item'  => $item,
            'roles' => $this->roles->allOrdered(),
        ]);
    }

    public function update($id): void
    {
        $this->checkCsrf('/users');
        $id   = (int) $id;
        $item = $this->users->find($id);
        if (!$item) {
            Session::setFlash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/users');
        }

        $isSelf   = $id === Auth::id();
        $nama     = $this->input('nama') ?? '';
        $username = $this->input('username') ?? '';
        $email    = $this->input('email');
        $password = $_POST['password'] ?? '';

        // Pengguna tidak boleh mengubah role/status dirinya sendiri (cegah lockout)
        $roleId   = $isSelf ? (int) $item['role_id'] : (int) ($this->input('role_id') ?? 0);
        $isActive = $isSelf ? 1 : (isset($_POST['is_active']) ? 1 : 0);

        $errors = $this->validate($nama, $username, $email, $password, true, $id);
        $role = $this->roles->find($roleId);
        if (!$role) {
            $errors[] = 'Role tidak valid.';
        } elseif ((int) $role['is_system'] === 1 && !Auth::isSuper() && $roleId !== (int) $item['role_id']) {
            $errors[] = 'Hanya Super Admin yang dapat menetapkan role Super Admin.';
        }

        if ($errors) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect("/users/{$id}/edit");
        }

        $payload = [
            'nama'     => $nama,
            'username' => $username,
            'email'    => ($email === '' ? null : $email),
            'role_id'  => $roleId,
            'is_active'=> $isActive,
        ];
        if ($password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->users->update($id, $payload);
        log_activity('update', 'pengguna', "Mengubah pengguna: {$username}");
        Session::setFlash('success', "Pengguna {$nama} berhasil diperbarui.");
        $this->redirect('/users');
    }

    public function destroy($id): void
    {
        $this->checkCsrf('/users');
        $id   = (int) $id;
        $item = $this->users->findWithRole($id);
        if (!$item) {
            Session::setFlash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/users');
        }

        if ($id === Auth::id()) {
            Session::setFlash('error', 'Anda tidak dapat menghapus akun sendiri.');
            $this->redirect('/users');
        }
        if ((int) $item['role_is_system'] === 1) {
            Session::setFlash('error', 'Pengguna dengan role Super Admin tidak dapat dihapus.');
            $this->redirect('/users');
        }

        $this->users->delete($id);
        log_activity('delete', 'pengguna', "Menghapus pengguna: {$item['username']}");
        Session::setFlash('success', "Pengguna {$item['nama']} berhasil dihapus.");
        $this->redirect('/users');
    }

    /** @return string[] daftar pesan error validasi */
    private function validate(string $nama, string $username, ?string $email, string $password,
                              bool $optionalPassword = false, int $id = 0): array
    {
        $errors = [];
        if ($nama === '') {
            $errors[] = 'Nama wajib diisi.';
        }
        if ($username === '' || !preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) {
            $errors[] = 'Username 3-50 karakter (huruf, angka, titik, underscore).';
        } elseif ($this->users->usernameExists($username, $id ?: null)) {
            $errors[] = 'Username sudah digunakan.';
        }
        if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }
        if (!($optionalPassword && $password === '') && strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }
        return $errors;
    }
}