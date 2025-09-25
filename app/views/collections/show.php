<?php use function App\Core\e; ?>
<h1 class="h4 mb-3"><?= e($collection['title']) ?></h1>
<?php if (!empty($collection['image_url'])): ?>
  <div class="ratio ratio-21x9 mb-3" style="background:#f8f9fa; overflow:hidden; border-radius:.5rem">
    <img src="<?= e($collection['image_url']) ?>" class="w-100 h-100 object-fit-cover" alt="<?= e($collection['title']) ?>"/>
  </div>
<?php endif; ?>
<?php if (!empty($collection['description'])): ?>
  <p class="text-muted"><?= nl2br(e($collection['description'])) ?></p>
<?php endif; ?>
<div class="row g-3">
  <?php foreach ($products as $p) { include BASE_PATH . '/app/views/products/_card.php'; } ?>
</div>

