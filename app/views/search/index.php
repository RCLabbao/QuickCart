<?php use function App\Core\e; use function App\Core\price; ?>
<h1 class="h4">Search results for "<?= e($q) ?>"</h1>
<?php if (empty($products)): ?>
  <p class="text-muted">No results found.</p>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($products as $p) { include BASE_PATH . '/app/views/products/_card.php'; } ?>
  </div>
<?php endif; ?>

