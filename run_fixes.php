<?php
// Temporary script to run the database fixes
// Upload this to your server, access it once, then DELETE IT!

require_once 'index.php';

echo "<h1>Database Fixes</h1>";

try {
    $pdo = DB::pdo();

    echo "<h2>1. Fixing FSC Column...</h2>";

    // Add fsc column if it doesn't exist
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS fsc VARCHAR(64) NULL UNIQUE");
    echo "✓ FSC column checked/added<br>";

    // Add index
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_products_fsc ON products(fsc)");
    echo "✓ FSC index created<br>";

    echo "<h2>2. Fixing Image URLs...</h2>";

    // Count images that need fixing
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE url NOT LIKE '/public%' AND url LIKE '/uploads/%'");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "Found $count images to update<br>";

        // Update the URLs
        $pdo->exec("UPDATE product_images SET url = CONCAT('/public', url) WHERE url NOT LIKE '/public%' AND url LIKE '/uploads/%'");
        echo "✓ Image URLs updated<br>";
    } else {
        echo "✓ No image URLs need fixing<br>";
    }

    echo "<h2>✅ All fixes completed successfully!</h2>";
    echo "<p><strong>IMPORTANT:</strong> Delete this file (run_fixes.php) from your server immediately!</p>";

} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>