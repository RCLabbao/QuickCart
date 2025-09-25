<?php
namespace App\Core;

class Auth
{
    public static function init(): void
    {
        if (!isset($_SESSION['user'])) $_SESSION['user'] = null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            if (session_status() === PHP_SESSION_ACTIVE) { @session_regenerate_id(true); }
            $_SESSION['user'] = [ 'id' => $user['id'], 'email' => $user['email'], 'name' => $user['name'] ];
            return true;
        }
        return false;
    }

    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function userId(): ?int { return self::user()['id'] ?? null; }
    public static function check(): bool { return (bool) self::user(); }

    public static function logout(): void { $_SESSION['user'] = null; }

    public static function checkRole(string $required): bool
    {
        if ($required !== 'admin') return true; // extend later
        if (!self::check()) return false;
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?');
        $stmt->execute([self::user()['id']]);
        $roles = $stmt->fetchAll();
        foreach ($roles as $r) if ($r['slug'] === 'admin' || $r['slug'] === 'superadmin') return true;
        return false;
    }

    public static function hasPermission(string $perm): bool
    {
        if (!self::check()) return false;
        $pdo = DB::pdo();
        // superadmin bypass
        $stmt = $pdo->prepare('SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?');
        $stmt->execute([self::user()['id']]);
        foreach ($stmt->fetchAll() as $r) if ($r['slug'] === 'superadmin') return true;
        // check role permissions
        $sql = 'SELECT 1 FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id JOIN user_roles ur ON ur.role_id = rp.role_id WHERE ur.user_id = ? AND p.slug = ? LIMIT 1';
        $st = $pdo->prepare($sql); $st->execute([self::user()['id'], $perm]);
        return (bool)$st->fetchColumn();
    }
}

