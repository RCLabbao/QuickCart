<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class AdminRolesController extends Controller
{
    public function create(): void
    {
        $this->adminView('admin/roles/create', ['title' => 'Create Role']);
    }

    public function store(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/roles'); }
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        if($slug && $name){ DB::pdo()->prepare('INSERT INTO roles (slug,name) VALUES (?,?)')->execute([$slug,$name]); }
        $this->redirect('/admin/roles');
    }

    public function index(): void
    {
        $pdo = DB::pdo();
        $roles = $pdo->query('SELECT id, slug, name FROM roles ORDER BY id')->fetchAll();
        $this->adminView('admin/roles/index', ['title' => 'Roles & Permissions', 'roles' => $roles]);
    }

    public function edit(array $params): void
    {
        $pdo = DB::pdo(); $id = (int)$params['id'];
        $role = $pdo->prepare('SELECT * FROM roles WHERE id=?'); $role->execute([$id]); $role = $role->fetch();
        if(!$role){ header('Location: /admin/roles'); return; }
        $perms = $pdo->query('SELECT id, slug, name FROM permissions ORDER BY slug')->fetchAll();
        $assigned = $pdo->prepare('SELECT permission_id FROM role_permissions WHERE role_id=?');
        $assigned->execute([$id]);
        $assignedIds = array_map('intval', $assigned->fetchAll(\PDO::FETCH_COLUMN));
        $this->adminView('admin/roles/edit', ['title' => 'Edit Role', 'role' => $role, 'perms' => $perms, 'assignedIds' => $assignedIds]);
    }

    public function update(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/roles'); }
        $pdo = DB::pdo(); $id = (int)$params['id'];
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$id]);
        $perms = $_POST['perms'] ?? [];
        $ins = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
        foreach ($perms as $pid) { $ins->execute([$id, (int)$pid]); }
        $this->redirect('/admin/roles/'.$id);
    }
}

