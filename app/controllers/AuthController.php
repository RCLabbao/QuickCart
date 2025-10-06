<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\Auth; use App\Core\CSRF;

class AuthController extends Controller
{
    public function login(): void
    {
        $this->view('admin/login');
    }

    public function doLogin(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/login'); }
        $email = trim($_POST['email'] ?? ''); $password = trim($_POST['password'] ?? '');

        // Simple rate limit: 5 failed attempts -> 10 min lockout per email+IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = hash('sha256', strtolower($email).'|'.$ip);
        $_SESSION['auth_throttle'] = $_SESSION['auth_throttle'] ?? [];
        $rec = $_SESSION['auth_throttle'][$key] ?? ['fails'=>0,'until'=>0];
        if ($rec['until'] && time() < $rec['until']) {
            $mins = ceil(($rec['until'] - time())/60);
            $this->view('admin/login', ['error' => "Too many attempts. Try again in {$mins} minute(s)."]); return;
        }

        if (Auth::attempt($email, $password)) {
            unset($_SESSION['auth_throttle'][$key]);
            $_SESSION['success'] = 'Login successful. Welcome back, '.(\App\Core\Auth::user()['name'] ?? 'Admin').'.';
            // Admin role goes to the dashboard; others go to their first allowed page
            if (\App\Core\Auth::checkRole('admin')) { $this->redirect('/admin'); return; }
            $target = $this->firstAllowedAdminPage();
            if ($target) { $this->redirect($target); return; }
            $this->view('admin/login', ['error' => 'Your account has no admin access assigned.']);
            return;
        }
        // record failure
        $rec['fails'] = ($rec['fails'] ?? 0) + 1;
        if ($rec['fails'] >= 5) { $rec['until'] = time() + 10*60; $rec['fails'] = 0; }
        $_SESSION['auth_throttle'][$key] = $rec;
        $this->view('admin/login', ['error' => 'Invalid credentials']);
    }

    private function firstAllowedAdminPage(): ?string
    {
        $candidates = [
            ['perm' => 'products.read',     'path' => '/admin/products'],
            ['perm' => 'orders.read',       'path' => '/admin/orders'],
            ['perm' => 'settings.read',     'path' => '/admin/settings'],
            ['perm' => 'collections.read',  'path' => '/admin/collections'],
            ['perm' => 'coupons.read',      'path' => '/admin/coupons'],
            ['perm' => 'reports.read',      'path' => '/admin/reports'],
            ['perm' => 'sync.read',         'path' => '/admin/sync'],
            ['perm' => 'roles.write',       'path' => '/admin/roles'],
        ];
        foreach ($candidates as $c) {
            if (\App\Core\Auth::hasPermission($c['perm'])) { return $c['path']; }
        }
        return null;
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }
}

