<?php
// Simple diagnostic to check catalog tab rendering
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/app/core/DB.php';

use App\Core\DB;

$pdo = DB::pdo();
$stmt = $pdo->query("SELECT `key`,`value` FROM settings");
$settings = [];
foreach ($stmt->fetchAll() as $row) { 
    $settings[$row['key']] = $row['value']; 
}

$collections = [];
try { 
    $collections = $pdo->query('SELECT id, title, slug FROM collections ORDER BY title')->fetchAll(); 
} catch (\Throwable $e) { 
    echo "ERROR fetching collections: " . $e->getMessage() . "<br>";
    $collections = []; 
}

$activeTab = 'catalog';

echo "<h1>Catalog Tab Diagnostic</h1>";
echo "<p>Active Tab: " . htmlspecialchars($activeTab) . "</p>";
echo "<p>Collections count: " . count($collections) . "</p>";
echo "<p>Settings count: " . count($settings) . "</p>";

echo "<h2>Collections:</h2>";
echo "<pre>";
print_r($collections);
echo "</pre>";

echo "<h2>Hidden Collections Setting:</h2>";
echo "<pre>";
echo htmlspecialchars($settings['hidden_collections'] ?? 'NOT SET');
echo "</pre>";

echo "<h2>Rendered Catalog Tab HTML:</h2>";
?>

<div class="tab-pane fade show active" id="tab-catalog" role="tabpanel">
  <form method="post" action="/admin/settings" class="row g-3 mt-2">
    <input type="hidden" name="scope" value="catalog">

    <?php $hiddenRaw = (string)($settings['hidden_collections'] ?? '');
          $hiddenParts = preg_split('/[\s,]+/u', $hiddenRaw, -1, PREG_SPLIT_NO_EMPTY);
          $hiddenSet = array_flip(array_map('strval', $hiddenParts)); ?>

    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom"><strong>Collections Visibility</strong></div>
        <div class="card-body" style="max-height:280px; overflow:auto;">
          <?php if (empty($collections)): ?>
            <p class="text-muted">No collections found. Create collections first in <a href="/admin/collections">Collections</a>.</p>
          <?php else: ?>
          <div class="row g-2">
            <?php foreach ($collections as $c): $id=(string)$c['id']; $slug=(string)$c['slug'];
              $checked = isset($hiddenSet[$id]) || isset($hiddenSet[$slug]); ?>
              <div class="col-md-4">
                <div class="form-check">
                  <input class="form-check-input coll-hide" type="checkbox" data-slug="<?= htmlspecialchars($slug) ?>" id="coll<?= (int)$c['id'] ?>" <?= $checked?'checked':'' ?>>
                  <label class="form-check-label" for="coll<?= (int)$c['id'] ?>">
                    <?= htmlspecialchars($c['title']) ?> <small class="text-muted">(<?= htmlspecialchars($slug) ?>)</small>
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <div class="form-text mt-2">Checked collections will be hidden sitewide (from home, collections page, search, etc.).</div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <label class="form-label fw-semibold">Hidden Collections (IDs or slugs)</label>
      <textarea class="form-control" id="hiddenCollections" name="hidden_collections" rows="3" placeholder="e.g. 3, clearance, archived&#10;One per line or comma-separated"><?php echo htmlspecialchars($settings['hidden_collections'] ?? ''); ?></textarea>
      <small class="text-muted">You can paste slugs/IDs directly, or use the checkboxes above.</small>
    </div>

    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom"><strong>Product Visibility Rules</strong></div>
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="hide_zero_price" id="hide_zero_price" <?= !empty($settings['hide_zero_price']) && $settings['hide_zero_price']=='1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="hide_zero_price">Hide products with 0.00 price (unless on sale with compare price)</label>
          </div>
          <small class="text-muted d-block mt-1">When enabled, products with price = 0.00 will be completely hidden from listings, search, and collections unless they have a valid sale price with a compare/original price greater than 0.</small>
        </div>
      </div>
    </div>

    <div class="col-12">
      <button class="btn btn-primary">Save Catalog Settings</button>
    </div>
  </form>
</div>

