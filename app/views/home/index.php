<!-- Modern Banner Slider -->
<?php if (!empty($banners ?? [])): ?>
<?php include BASE_PATH . '/app/views/home/_modern_slider.php'; ?>
<?php else: ?>
<!-- Hero (fallback when no banners) -->
<section class="bg-light rounded-4 shadow-sm my-4 my-md-5" style="background:linear-gradient(135deg,#f8f9fa,#eef6ff)">
  <div class="container py-5 py-md-6 px-3 px-md-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-7 text-center text-lg-start">
        <h1 class="fw-semibold mb-2" style="font-size:clamp(1.75rem,4vw,2.5rem);">Discover products you'll love</h1>
        <p class="text-muted mb-4" style="font-size:clamp(1rem,2.2vw,1.25rem);">Fresh arrivals, curated collections, and the best deals—optimized for fast checkout.</p>
        <div class="d-flex gap-2 justify-content-center justify-content-lg-start flex-wrap">
          <a href="/products" class="btn btn-primary btn-lg"><i class="bi bi-shop me-2"></i>Shop Now</a>
          <a href="/collections" class="btn btn-outline-primary btn-lg">Browse Collections</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($featured_collections ?? [])): ?>
<section class="py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 m-0">Featured Collections</h2>
    <a href="/collections" class="link-secondary">View all</a>
  </div>
  <div class="row g-3">
    <?php foreach (($featured_collections ?? []) as $c): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="/collections/<?= htmlspecialchars($c['slug']) ?>" class="text-decoration-none">
          <div class="card border-0 shadow-sm h-100">
            <?php if (!empty($c['image_url'])): ?>
              <img src="<?= htmlspecialchars($c['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($c['title']) ?>" style="height:160px;object-fit:cover;">
            <?php endif; ?>
            <div class="card-body">
              <h3 class="h6 mb-0 text-dark"><?= htmlspecialchars($c['title']) ?></h3>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($sale_items)): ?>
<section class="py-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h4 m-0">On Sale</h2>
    <a href="/products?sort=price_asc" class="link-secondary">Shop deals</a>
  </div>
  <div class="row g-3">
    <?php foreach (($sale_items ?? []) as $p) { $product=$p; include BASE_PATH . '/app/views/products/_card.php'; } ?>
  </div>
</section>
<?php endif; ?>

<section class="py-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h4 m-0">New Arrivals</h2>
    <a href="/products" class="link-secondary">Shop all</a>
  </div>
  <div class="row g-3">
    <?php foreach (($new_arrivals ?? []) as $p) { $product=$p; include BASE_PATH . '/app/views/products/_card.php'; } ?>
  </div>
</section>

<?php if (!empty($best_sellers)): ?>
<section class="py-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h4 m-0">Best Sellers</h2>
    <a href="/products?sort=price_desc" class="link-secondary">See trending</a>
  </div>
  <div class="row g-3">
    <?php foreach (($best_sellers ?? []) as $p) { $product=$p; include BASE_PATH . '/app/views/products/_card.php'; } ?>
  </div>
</section>
<?php endif; ?>

<style>
.card { transition: all .2s ease; }
.card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.08) !important; }
</style>
