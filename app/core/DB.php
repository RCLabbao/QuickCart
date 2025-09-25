<?php
namespace App\Core;
use PDO; use PDOException;

class DB
{
    private static ?PDO $pdo = null;

    public static function init(array $cfg): void
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['name']);
        try {
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) { http_response_code(500); echo 'DB Connection failed'; exit; }
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) { throw new \RuntimeException('DB not initialized'); }
        return self::$pdo;
    }
}

