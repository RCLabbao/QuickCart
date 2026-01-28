<?php
namespace App\Core;

function asset(string $path): string { return '/assets/' . ltrim($path, '/'); }
function e(?string $s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function price(float $p): string { return '₱' . number_format($p, 2); }
function csrf_field(): string { return '<input type="hidden" name="_token" value="' . CSRF::token() . '">'; }

function is_on_sale(array $p): bool {
    // Rule 1: If sale_price is blank or zero, no sale
    if (!isset($p['sale_price']) || $p['sale_price'] === null || (float)$p['sale_price'] <= 0) {
        return false;
    }

    $regularPrice = isset($p['price']) ? (float)$p['price'] : 0;
    $salePrice = (float)$p['sale_price'];

    // Require a valid regular price
    if ($regularPrice <= 0) return false;

    // Rule 2: Don't show sale if prices are equal
    if (abs($regularPrice - $salePrice) < 0.01) return false;

    // Additional check: sale price must be less than regular price
    if ($salePrice >= $regularPrice) return false;

    // Check sale dates if they exist
    $now = time();
    $startOk = empty($p['sale_start']) || strtotime($p['sale_start']) <= $now;
    $endOk = empty($p['sale_end']) || strtotime($p['sale_end']) >= $now;
    return $startOk && $endOk;
}

function effective_price(array $p): float {
    // Use sale price if available and greater than 0
    if (isset($p['sale_price']) && $p['sale_price'] !== null && (float)$p['sale_price'] > 0) {
        return (float)$p['sale_price'];
    }
    return (float)($p['price'] ?? 0);
}

function get_sale_price(array $p): float {
    // Return the sale price
    return (float)($p['sale_price'] ?? 0);
}

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

// Get fresh settings without any caching - used for critical real-time data like shipping settings
function fresh_settings(): array {
    try {
        $pdo = DB::pdo();
        $rows = $pdo->query('SELECT `key`,`value` FROM settings')->fetchAll();
        $cache = [];
        foreach ($rows as $r) { $cache[$r['key']] = $r['value']; }
        return $cache;
    } catch (\Throwable $e) { return []; }
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

/**
 * Extract base title by removing variant patterns from the end
 * This is the SINGLE source of truth for variant detection
 */
function qc_extract_base_title(string $title): string {
    $title = trim($title);
    if ($title === '') return '';

    // Variant patterns to remove - ordered from most specific to least specific
    $patterns = [
        '/\s+\d{2,3}[A-Z]{1,3}\s*$/i',      // Bra sizes: 38A, 36B, 34C, 32DD, 40DD
        '/\s+\d{1,2}\.\d{1,2}\s*$/i',       // Decimal: 28.5, 29.5
        '/\s+\d+(?:XL|XS|L|M|S)\s*$/i',     // 2XL, 3XL, 4XL, 2XS, 3XS
        '/\s+(?:EXTRA LARGE|EXTRA SMALL|EXTRA LONG|EXTRA SHORT)\s*$/i',
        '/\s+(?:XXXXL|XXXXXL|2XL|3XL|4XL|5XL|2XS|3XS)\s*$/i',
        '/\s+(?:XL|XS)\s*$/i',
        '/\s+(?:LARGE|MEDIUM|SMALL)\s*$/i',
        '/\s+[LMS]\s*$/i',                  // Single letter sizes
        '/\s+PACK\s+[LMS]\s*$/i',           // PACK L, PACK M, PACK S (for bikini briefs)
        '/\s+\d{1,2}\s*$/i',               // Standalone numbers: 36, 37, 38
    ];

    $originalTitle = $title;
    foreach ($patterns as $pattern) {
        $newTitle = preg_replace($pattern, '', $title);
        if ($newTitle === null) continue; // preg_replace failed
        $newTitle = trim($newTitle);

        // Only accept if we removed something AND result is meaningful
        if ($newTitle !== $title && strlen($newTitle) >= 5) {
            // Verify we haven't removed too much (base title should have at least 2 words)
            $wordCount = count(preg_split('/\s+/', $newTitle));
            if ($wordCount >= 2) {
                return $newTitle;
            }
        }
    }

    return $originalTitle; // Return original if no pattern matched
}

/**
 * Extract variant attribute from title (e.g., "38A" from "Product 38A")
 */
function qc_extract_variant_attribute(string $title): string {
    $title = trim($title);
    if ($title === '') return '';

    // Variant patterns - must match the same ones used in qc_extract_base_title
    $patterns = [
        '/\s+(\d{2,3}[A-Z]{1,3})\s*$/i'      => 1, // Bra sizes: 38A
        '/\s+(\d{1,2}\.\d{1,2})\s*$/i'       => 1, // Decimal: 28.5
        '/\s+(\d+(?:XL|XS|L|M|S))\s*$/i'     => 1, // 2XL, 3XL, etc.
        '/\s+(EXTRA LARGE|EXTRA SMALL)\s*$/i'  => 1,
        '/\s+(XXXXL|2XL|3XL|4XL|5XL)\s*$/i'   => 1,
        '/\s+(XL|XS)\s*$/i'                  => 1,
        '/\s+(LARGE|MEDIUM|SMALL)\s*$/i'     => 1,
        '/\s+([LMS])\s*$/i'                  => 1, // Single letter
        '/\s+PACK\s+([LMS])\s*$/i'           => 1, // PACK L, PACK M, PACK S
        '/\s+(\d{1,2})\s*$/i'                => 1, // Numbers
    ];

    foreach ($patterns as $pattern => $groupIndex) {
        if (preg_match($pattern, $title, $matches)) {
            return strtoupper(trim($matches[$groupIndex]));
        }
    }

    return '';
}

/**
 * Auto-detect and merge product variants
 * This is the SINGLE unified function used by both AdminProducts and AdminMaintenance
 *
 * @param \PDO $pdo Database connection
 * @return array Result with merged_groups, merged_products, and debug info
 */
function qc_auto_merge_variants(\PDO $pdo): array {
    $result = [
        'merged_groups' => 0,
        'merged_products' => 0,
        'errors' => [],
        'debug' => []
    ];

    // Check if variant support is enabled
    try {
        $hasVariants = $pdo->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        if (!$hasVariants) {
            return $result;
        }
    } catch (\Throwable $e) {
        return $result;
    }

    // Get all products that are not already variants
    $stmt = $pdo->query('
        SELECT id, title, slug, parent_product_id
        FROM products
        WHERE (parent_product_id IS NULL OR parent_product_id = 0)
        ORDER BY title
    ');
    $products = $stmt->fetchAll();

    $result['debug']['total_products'] = count($products);

    // Group products by base title using the SHARED function
    // CRITICAL: Store original titles BEFORE any updates
    $groups = [];
    $skipped = 0;
    foreach ($products as $product) {
        $baseTitle = qc_extract_base_title($product['title']);

        // Only group if base title is different from full title AND is meaningful
        if ($baseTitle !== $product['title'] && strlen($baseTitle) >= 5) {
            if (!isset($groups[$baseTitle])) {
                $groups[$baseTitle] = [];
            }
            // Store BOTH original title and base title for each product
            $groups[$baseTitle][] = [
                'id' => $product['id'],
                'original_title' => $product['title'],  // Store original!
                'slug' => $product['slug']
            ];
        } else {
            $skipped++;
        }
    }

    $result['debug']['skipped_products'] = $skipped;
    $result['debug']['variant_groups_found'] = count($groups);

    // Merge each group
    foreach ($groups as $baseTitle => $groupProducts) {
        if (count($groupProducts) <= 1) {
            continue; // Skip groups with only 1 product
        }

        // Sort by ID to use the first/oldest as parent
        usort($groupProducts, function($a, $b) {
            return $a['id'] - $b['id'];
        });

        $parentProduct = $groupProducts[0];
        $parentId = $parentProduct['id'];

        // DON'T update parent title or slug - keep original to preserve variant info!
        // Only set parent_product_id and variant_attributes

        // Set parent product to active status so it shows on frontend
        try {
            $pdo->prepare('UPDATE products SET status = "active" WHERE id = ?')
                ->execute([$parentId]);
        } catch (\Throwable $e) {
            // Ignore status errors
        }

        // Link other products as variants
        foreach (array_slice($groupProducts, 1) as $variantProduct) {
            $variantId = $variantProduct['id'];

            // Extract variant attribute from ORIGINAL title (before it was changed)
            $variantAttr = qc_extract_variant_attribute($variantProduct['original_title']);

            try {
                $pdo->prepare('
                    UPDATE products
                    SET parent_product_id = ?, variant_attributes = ?
                    WHERE id = ?
                ')->execute([$parentId, $variantAttr, $variantId]);
                $result['merged_products']++;
            } catch (\Throwable $e) {
                $result['errors'][] = "Could not link product ID {$variantId}: " . $e->getMessage();
            }
        }

        $result['merged_groups']++;
    }

    return $result;
}
