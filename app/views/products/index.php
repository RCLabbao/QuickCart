<?php use function App\Core\price; use function App\Core\e; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3">All Products</h1>
  <div class="text-muted" id="productCount"><?= (int)$count ?> items</div>
<!--<form class="row g-2 align-items-end mb-3" method="get">
  <div class="col-6 col-md-3"><label class="form-label">Min price</label><input class="form-control" type="number" step="0.01" name="min_price" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>"></div>
  <div class="col-6 col-md-3"><label class="form-label">Max price</label><input class="form-control" type="number" step="0.01" name="max_price" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>"></div>
  <div class="col-12 col-md-3"><label class="form-label">Collection</label>
    <select class="form-select" name="collection_id">
      <option value="">All collections</option>
      <?php foreach(($collections ?? []) as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (isset($_GET['collection_id']) && (int)$_GET['collection_id']===(int)$c['id'])?'selected':'' ?>><?= e($c['title']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-3"><label class="form-label">Sort</label>
    <select class="form-select" name="sort">
      <?php $s=$_GET['sort']??'new'; ?>
      <option value="new" <?= $s==='new'?'selected':'' ?>>Newest</option>
      <option value="price_asc" <?= $s==='price_asc'?'selected':'' ?>>Price: Low to High</option>
      <option value="price_desc" <?= $s==='price_desc'?'selected':'' ?>>Price: High to Low</option>
    </select>
  </div>
  <div class="col-12 col-md-3"><button class="btn btn-outline-secondary w-100">Apply</button></div>
</form>-->

</div>
<div class="row g-3" id="productGrid">
  <?php foreach ($products as $p) { include BASE_PATH . '/app/views/products/_card.php'; } ?>
</div>
<div class="text-center py-4" id="loadMoreWrap">
  <div class="spinner-border" role="status" id="loader" style="display:none"></div>
</div>
<script>
  window.INF_SCROLL = { page: 1, busy: false, hasMore: true };
</script>

