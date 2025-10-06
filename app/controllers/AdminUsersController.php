<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF; use App\Core\Auth;

class AdminUsersController extends Controller
{
    public function index(): void
    {
        $pdo = DB::pdo();
        $users = $pdo->query('SELECT u.id, u.name, u.email, GROUP_CONCAT(r.name SEPARATOR ", ") as roles FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r ON r.id=ur.role_id GROUP BY u.id ORDER BY u.created_at DESC')->fetchAll();
        $this->adminView('admin/users/index', ['title' => 'Admin Users', 'users' => $users]);
    }

    public function create(): void
    {
        $pdo = DB::pdo();
        $roles = $pdo->query('SELECT id, name, slug FROM roles ORDER BY id')->fetchAll();
        $this->adminView('admin/users/form', ['title' => 'Create Admin User', 'roles' => $roles]);
    }

    public function store(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/users'); }
        $name = trim($_POST['name']); $email = trim($_POST['email']); $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO users (name,email,password_hash,created_at) VALUES (?,?,?,NOW())')->execute([$name,$email,$pass]);
            $uid = (int)$pdo->lastInsertId();
            $roles = $_POST['roles'] ?? [];
            $ins = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
            foreach ($roles as $rid) { $ins->execute([$uid, (int)$rid]); }

            $pdo->commit();
        } catch (\Throwable $e) { $pdo->rollBack(); }
        $this->redirect('/admin/users');
    }

    public function edit(array $params): void
    {
        $pdo = DB::pdo(); $id = (int)$params['id'];
        $st = $pdo->prepare('SELECT id, name, email FROM users WHERE id=?'); $st->execute([$id]); $user = $st->fetch();
        if(!$user){ $this->redirect('/admin/users'); }
        $roles = $pdo->query('SELECT id, name, slug FROM roles ORDER BY id')->fetchAll();
        $assigned = $pdo->prepare('SELECT role_id FROM user_roles WHERE user_id=?'); $assigned->execute([$id]);
        $assignedIds = array_map('intval', $assigned->fetchAll(\PDO::FETCH_COLUMN));
        $this->adminView('admin/users/form', [
            'title' => 'Edit Admin User',
            'user'=>$user,
            'roles'=>$roles,
            'assignedIds'=>$assignedIds
        ]);
    }

    public function update(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/users'); }
        $pdo = DB::pdo(); $id = (int)$params['id'];
        $name = trim($_POST['name']); $email = trim($_POST['email']); $password = trim($_POST['password'] ?? '');
        // Superadmin safeguard on role removal
        $cur = $pdo->prepare('SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?');
        $cur->execute([$id]); $currentRoleSlugs = array_map(fn($r)=>$r['slug'],$cur->fetchAll());
        $newRoleIds = array_map('intval', $_POST['roles'] ?? []);
        $hasSuperNow = in_array('superadmin', $currentRoleSlugs, true);
        $willHaveSuper = false;
        if (!empty($newRoleIds)) {
            $in = implode(',', array_fill(0, count($newRoleIds), '?'));
            $q = $pdo->prepare("SELECT slug FROM roles WHERE id IN ($in)");
            $q->execute($newRoleIds);
            $newSlugs = array_map(fn($r)=>$r['slug'],$q->fetchAll());
            $willHaveSuper = in_array('superadmin', $newSlugs, true);
        }
        if ($hasSuperNow && !$willHaveSuper) {
            $cnt = (int)$pdo->query("SELECT COUNT(DISTINCT ur.user_id) FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.slug='superadmin' AND ur.user_id <> $id")->fetchColumn();
            if ($cnt === 0) { $_SESSION['error'] = 'Cannot remove the last remaining superadmin role.'; $this->redirect("/admin/users/$id/edit"); }
        }

        // update user basics
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET name=?, email=?, password_hash=? WHERE id=?')->execute([$name,$email,$hash,$id]);
        } else {
            $pdo->prepare('UPDATE users SET name=?, email=? WHERE id=?')->execute([$name,$email,$id]);
        }
        // update roles
        $pdo->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$id]);
        $ins = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
        foreach ($newRoleIds as $rid) { $ins->execute([$id, (int)$rid]); }

        $this->redirect('/admin/users');
    }

    public function destroy(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/users'); }
        $id = (int)$params['id'];
        if ($id === (int)Auth::userId()) { $_SESSION['error'] = 'You cannot delete your own account.'; $this->redirect('/admin/users'); }
        $pdo = DB::pdo();
        // Superadmin safeguard on deletion
        $isSuper = (bool)$pdo->query("SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=$id AND r.slug='superadmin' LIMIT 1")->fetchColumn();
        if ($isSuper) {
            $cnt = (int)$pdo->query("SELECT COUNT(DISTINCT ur.user_id) FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.slug='superadmin' AND ur.user_id <> $id")->fetchColumn();
            if ($cnt === 0) { $_SESSION['error'] = 'Cannot delete the last remaining superadmin.'; $this->redirect('/admin/users'); }
        }
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM user_permissions WHERE user_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            $pdo->commit();
        } catch (\Throwable $e) { $pdo->rollBack(); }
        $this->redirect('/admin/users');
    }
}

