<?php if (!empty($sale_items)): ?>
<section class="py-2">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h4 m-0">On Sale</h2>
    <a href="/products?sort=price_asc" class="link-secondary">Shop deals</a>
  </div>
  <div class="row g-3">
    <?php foreach (($sale_items ?? []) as $p) { $product=$p; include BASE_PATH . '/app/views/products/_card.php'; } ?>
  </div>
</section>
<?php endif; ?>

<section class="py-2">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h4 m-0">New Arrivals</h2>
    <a href="/products" class="link-secondary">Shop all</a>
  </div>
  <div class="row g-3">
    <?php foreach (($new_arrivals ?? []) as $p) { $product=$p; include BASE_PATH . '/app/views/products/_card.php'; } ?>
  </div>
</section>
<?php if (!empty($best_sellers)): ?>
<section class="py-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h4 m-0">Best Sellers</h2>
    <a href="/products?sort=price_desc" class="link-secondary">See trending</a>
  </div>
  <div class="row g-3">
    <?php foreach (($best_sellers ?? []) as $p) { $product=$p; include BASE_PATH . '/app/views/products/_card.php'; } ?>
  </div>
</section>
<?php endif; ?>

