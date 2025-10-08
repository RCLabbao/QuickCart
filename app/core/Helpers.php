<?php
namespace App\Core;

function asset(string $path): string { return '/assets/' . ltrim($path, '/'); }
function e(?string $s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function price(float $p): string { return '₱' . number_format($p, 2); }
function csrf_field(): string { return '<input type="hidden" name="_token" value="' . CSRF::token() . '">'; }

function is_on_sale(array $p): bool {
    if (!isset($p['sale_price']) || $p['sale_price'] === null) return false;
    $sp = (float)$p['sale_price']; $rp = isset($p['price']) ? (float)$p['price'] : $sp;
    // Require a valid compare/original price and allow 0.00 as a valid sale price when compare price > 0
    if ($rp <= 0) return false;
    if ($sp < 0 || $sp >= $rp) return false;
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

// List of hidden collection IDs from settings (comma/newline-separated list of IDs or slugs)
function hidden_collection_ids(): array {
    static $cache = null; if ($cache !== null) return $cache;
    $raw = (string)setting('hidden_collections','');
    if ($raw === '') { return $cache = []; }
    $parts = preg_split('/[\s,]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
    $ids = []; $slugs = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        if (ctype_digit($p)) { $ids[] = (int)$p; }
        else { $slugs[] = $p; }
    }
    if ($slugs) {
        try {
            $in = implode(',', array_fill(0, count($slugs), '?'));
            $st = DB::pdo()->prepare("SELECT id FROM collections WHERE slug IN ($in)");
            $st->execute($slugs);
            foreach ($st->fetchAll(\PDO::FETCH_COLUMN) as $cid) { $ids[] = (int)$cid; }
        } catch (\Throwable $e) {}
    }
    $ids = array_values(array_unique(array_map('intval', $ids)));
    return $cache = $ids;
}

function thumb_url(?string $url): string {
    if (!$url) return '';
    $dot = strrpos($url, '.'); if ($dot === false) return $url;
    $thumb = substr($url, 0, $dot) . '.thumb' . substr($url, $dot);
    $path = BASE_PATH . $thumb;
    return is_file($path) ? $thumb : $url;
}



// Application secret for signing links (falls back to DB pass if no app_key)
function app_secret(): string {
    if (defined('CONFIG') && isset(\CONFIG['app_key']) && \CONFIG['app_key']) { return (string)\CONFIG['app_key']; }
    if (defined('CONFIG') && isset(\CONFIG['db']['pass'])) { return hash('sha256', (string)\CONFIG['db']['pass']); }
    return hash('sha256', __FILE__);
}

// Deterministic token for public order links (no DB column needed)
// Use only immutable fields to keep the link stable even if admin edits email
function order_public_token_from_row(array $order): string {
    $id = (string)($order['id'] ?? '');
    $created = (string)($order['created_at'] ?? '');
    $payload = $id.'|'.$created;
    // 32-hex chars from HMAC-SHA256 provides strong security while being URL-friendly
    return substr(hash_hmac('sha256', $payload, app_secret()), 0, 32);
}

// Base64url helpers
function b64url_encode(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64url_decode(string $s): string { return (string)base64_decode(strtr($s, '-_', '+/')); }

// Build opaque slug for public order links: base64url("<id>:<token>")
function order_public_slug_from_row(array $order): string {
    $id = (string)($order['id'] ?? '');
    $token = order_public_token_from_row($order);
    return b64url_encode($id . ':' . $token);
}

// Parse slug back to [id, token]
function order_public_parts_from_slug(string $slug): array {
    $raw = b64url_decode($slug);
    $parts = explode(':', $raw, 2);
    if (count($parts) !== 2) return [null, null];
    return [$parts[0], $parts[1]];
}



// Ensure a set of permissions exist (idempotent)
function qc_ensure_permissions(array $slugs): void {
    try {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('INSERT INTO permissions (slug, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
        foreach ($slugs as $slug) {
            $slug = (string)$slug; if ($slug==='') continue;
            $name = ucwords(str_replace(['.','_'], ' ', $slug));
            $stmt->execute([$slug, $name]);
        }
    } catch (\Throwable $e) {}
}
