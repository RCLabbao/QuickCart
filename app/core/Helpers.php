<?php
namespace App\Core;

function asset(string $path): string { return '/assets/' . ltrim($path, '/'); }
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function price(float $p): string { return '₱' . number_format($p, 2); }
function csrf_field(): string { return '<input type="hidden" name="_token" value="' . CSRF::token() . '">'; }

function is_on_sale(array $p): bool {
    if (!isset($p['sale_price']) || $p['sale_price'] === null) return false;
    $sp = (float)$p['sale_price']; $rp = isset($p['price']) ? (float)$p['price'] : $sp;
    if ($sp <= 0 || $sp >= $rp) return false;
    $now = time();
    $startOk = empty($p['sale_start']) || strtotime($p['sale_start']) <= $now;
    $endOk = empty($p['sale_end']) || strtotime($p['sale_end']) >= $now;
    return $startOk && $endOk;
}
function effective_price(array $p): float { return is_on_sale($p) ? (float)$p['sale_price'] : (float)$p['price']; }

function settings(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    // APCu cache (if available)
    if (function_exists('apcu_fetch')) {
        $ap = apcu_fetch('settings');
        if ($ap !== false) { return $cache = $ap; }
    }
    try {
        $pdo = DB::pdo();
        $rows = $pdo->query('SELECT `key`,`value` FROM settings')->fetchAll();
        $cache = [];
        foreach ($rows as $r) { $cache[$r['key']] = $r['value']; }
        if (function_exists('apcu_store')) { @apcu_store('settings', $cache, 60); }
        return $cache;
    } catch (\Throwable $e) { return $cache = []; }
}
function setting(string $key, $default = '') {
    $s = settings(); return $s[$key] ?? $default;
}

function thumb_url(?string $url): string {
    if (!$url) return '';
    $dot = strrpos($url, '.'); if ($dot === false) return $url;
    $thumb = substr($url, 0, $dot) . '.thumb' . substr($url, $dot);
    $path = BASE_PATH . $thumb;
    return is_file($path) ? $thumb : $url;
}

