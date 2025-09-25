<h1 class="h4 mb-3">Collections</h1>
<div class="row g-3">
  <?php foreach ($collections as $c): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <a class="card h-100 text-decoration-none text-dark" href="/collections/<?= htmlspecialchars($c['slug']) ?>">
        <div class="ratio ratio-1x1 bg-light rounded" style="overflow:hidden">
          <?php if (!empty($c['image_url'])): ?>
            <img loading="lazy" src="<?= htmlspecialchars($c['image_url']) ?>" class="w-100 h-100 object-fit-cover" alt="<?= htmlspecialchars($c['title']) ?>">
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="fw-semibold text-truncate" title="<?= htmlspecialchars($c['title']) ?>"><?= htmlspecialchars($c['title']) ?></div>
          <div class="small text-muted">Explore</div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

