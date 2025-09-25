<?php use function App\Core\price; use function App\Core\e; use function App\Core\thumb_url; use function App\Core\is_on_sale; use function App\Core\effective_price; ?>
<div class="col-6 col-md-4 col-lg-3">
  <a class="card h-100 text-decoration-none text-dark shadow-sm hover-lift" href="/products/<?= e($p['slug']) ?>">
    <div class="position-relative">
      <?php $img = e($p['image_url'] ?? '') ?: 'https://picsum.photos/seed/'.(int)$p['id'].'/600/600'; ?>
      <img loading="lazy" src="<?= thumb_url($img) ?>" class="card-img-top" alt="<?= e($p['title']) ?>"/>

      <!-- Badge container with proper spacing -->
      <div class="position-absolute top-0 start-0 end-0 p-2 d-flex justify-content-between align-items-start flex-wrap" style="gap: 0.25rem;">
        <!-- Left side badges -->
        <div class="d-flex flex-column" style="gap: 0.25rem;">
          <?php $isNew = !empty($p['created_at']) && strtotime($p['created_at']) > (time()-14*86400); if ($isNew): ?>
            <span class="badge bg-dark">New</span>
          <?php endif; ?>
          <?php if (is_on_sale($p)): ?>
            <span class="badge bg-danger">Sale</span>
          <?php endif; ?>
        </div>

        <!-- Right side badges -->
        <div class="d-flex flex-column" style="gap: 0.25rem;">
          <?php $stk = (int)($p['stock'] ?? 0); if ($stk <= 0): ?>
            <span class="badge bg-secondary">Out of stock</span>
          <?php elseif ($stk <= 3): ?>
            <span class="badge bg-warning text-dark">Low stock</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="fw-semibold text-truncate" title="<?= e($p['title']) ?>"><?= e($p['title']) ?></div>
      <div class="mt-2 d-flex justify-content-between align-items-center">
        <div class="fw-bold">
          <?php if (is_on_sale($p)): ?>
            <span class="text-danger me-1"><?= price(effective_price($p)) ?></span>
            <s class="text-muted small"><?= price((float)$p['price']) ?></s>
          <?php else: ?>
            <?= price((float)$p['price']) ?>
          <?php endif; ?>
        </div>
        <form class="addToCart">
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"/>
          <?php $oos = (int)($p['stock'] ?? 0) <= 0; ?>
          <button class="btn btn-sm btn-dark" type="submit" <?= $oos?'disabled':'' ?>><?= $oos?'Sold out':'Add' ?></button>
        </form>
      </div>
    </div>
  </a>
</div>

