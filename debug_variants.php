<?php
// Quick diagnostic script to check variant relationships
// Access this file directly in your browser: http://your-domain/debug_variants.php

require_once __DIR__ . '/app/core/bootstrap.php';

use App\Core\DB;

$pdo = DB::pdo();

echo "<h2>Variant Relationship Diagnostic</h2>";
echo "<style>table{border-collapse:collapse;}td,th{border:1px solid #ccc;padding:8px;text-align:left;}</style>";

// Check for BEVERLY products
echo "<h3>BEVERLY UW FULL CUP LACE Products:</h3>";
$stmt = $pdo->prepare('
    SELECT id, title, slug, parent_product_id, variant_attributes, status
    FROM products
    WHERE title LIKE "%BEVERLY UW FULL CUP LACE%"
    ORDER BY id
');
$stmt->execute();
$products = $stmt->fetchAll();

if (count($products) > 0) {
    echo "<table><tr><th>ID</th><th>Title</th><th>parent_product_id</th><th>variant_attributes</th><th>status</th></tr>";
    foreach ($products as $p) {
        $parentClass = ($p['parent_product_id'] === null) ? 'background:#ffcccc;' : '';
        echo "<tr style='$parentClass'><td>{$p['id']}</td><td>{$p['title']}</td><td>" . ($p['parent_product_id'] ?? 'NULL') . "</td><td>{$p['variant_attributes']}</td><td>{$p['status']}</td></tr>";
    }
    echo "</table>";
    echo "<p><strong>Note:</strong> Products with RED background should be parent products (parent_product_id IS NULL)</p>";
} else {
    echo "<p>No BEVERLY products found</p>";
}

// Count total variants
echo "<h3>Database Summary:</h3>";
$stmt = $pdo->query('
    SELECT
        COUNT(*) as total_products,
        SUM(CASE WHEN parent_product_id IS NULL THEN 1 ELSE 0 END) as parent_products,
        SUM(CASE WHEN parent_product_id IS NOT NULL THEN 1 ELSE 0 END) as variant_products
    FROM products
');
$summary = $stmt->fetch();
echo "<ul>";
echo "<li>Total products: {$summary['total_products']}</li>";
echo "<li>Parent products (parent_product_id IS NULL): {$summary['parent_products']}</li>";
echo "<li>Variant products (parent_product_id IS NOT NULL): {$summary['variant_products']}</li>";
echo "</ul>";

// Check for products with variant patterns that aren't linked
echo "<h3>Products with Variant Patterns But No parent_product_id:</h3>";
$stmt = $pdo->query('
    SELECT id, title, slug, parent_product_id
    FROM products
    WHERE parent_product_id IS NULL
    AND (title REGEXP "(38A|36A|34A|32A|PACK [LMS]|LARGE|MEDIUM|SMALL|2XL|3XL|XL|XS|[0-9]A)$")
    ORDER BY title
    LIMIT 20
');
$unlinked = $stmt->fetchAll();
if (count($unlinked) > 0) {
    echo "<p style='color:red;'>Found " . count($unlinked) . " products with variant patterns but no parent_product_id:</p>";
    echo "<table><tr><th>ID</th><th>Title</th><th>Slug</th></tr>";
    foreach ($unlinked as $p) {
        echo "<tr><td>{$p['id']}</td><td>{$p['title']}</td><td>{$p['slug']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:green;'>All products with variant patterns are correctly linked!</p>";
}

echo "<hr><p><strong>If you see products with RED background above, the parent_product_id is NOT being set correctly during merge.</strong></p>";
echo "<p>Delete this file after debugging: <code>unlink(__DIR__ . '/debug_variants.php');</code></p>";
