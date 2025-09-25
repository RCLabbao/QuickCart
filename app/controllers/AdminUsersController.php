<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

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
        $roles = DB::pdo()->query('SELECT id, name, slug FROM roles ORDER BY id')->fetchAll();
        $this->adminView('admin/users/form', ['title' => 'Create Admin User', 'roles' => $roles]);
    }

    public function store(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/users'); }
        $name = trim($_POST['name']); $email = trim($_POST['email']); $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $pdo = DB::pdo();
        $pdo->prepare('INSERT INTO users (name,email,password_hash,created_at) VALUES (?,?,?,NOW())')->execute([$name,$email,$pass]);
        $uid = (int)$pdo->lastInsertId();
        $roles = $_POST['roles'] ?? [];
        $ins = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
        foreach ($roles as $rid) { $ins->execute([$uid, (int)$rid]); }
        $this->redirect('/admin/users');
    }
}

