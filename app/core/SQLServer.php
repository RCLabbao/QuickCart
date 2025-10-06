<?php
namespace App\Core;

class SQLServer
{
    public static function available(): bool
    {
        return \extension_loaded('pdo_sqlsrv') || \extension_loaded('sqlsrv');
    }

    public static function pdo(string $server, string $database, string $user, string $pass): \PDO
    {
        // Accept server as "host" or "host\\instance"
        $dsn = "sqlsrv:Server={$server};Database={$database}";
        try {
            $pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('SQL Server connection failed: ' . $e->getMessage());
        }
        return $pdo;
    }
}

