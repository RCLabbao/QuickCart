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
            $this->redirect('/admin'); return;
        }
        // record failure
        $rec['fails'] = ($rec['fails'] ?? 0) + 1;
        if ($rec['fails'] >= 5) { $rec['until'] = time() + 10*60; $rec['fails'] = 0; }
        $_SESSION['auth_throttle'][$key] = $rec;
        $this->view('admin/login', ['error' => 'Invalid credentials']);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }
}

