<?php use function App\Core\csrf_field; ?>

<!-- Collections Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Collections</h1>
    <p class="text-muted mb-0">Group products into curated sets</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-primary" href="/admin/collections/create">
      <i class="bi bi-plus-circle me-2"></i>Create Collection
    </a>
  </div>
</div>

<!-- Collections Table -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 80px;">ID</th>
            <th style="width:100px;">Cover</th>
            <th>Title</th>
            <th>Category Code</th>
            <th>Slug</th>
            <th style="width: 140px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($collections as $c): ?>
            <tr>
              <td><span class="badge bg-primary">#<?= (int)$c['id'] ?></span></td>
              <td>
                <?php if (!empty($c['image_url'])): ?>
                  <img src="<?= htmlspecialchars($c['image_url']) ?>" alt="Cover" class="rounded border" style="width:64px;height:64px;object-fit:cover;">
                <?php else: ?>
                  <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                    <i class="bi bi-image text-muted"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($c['title']) ?></td>
              <td>
                <?php if (!empty($c['category_code'])): ?>
                  <span class="badge bg-secondary"><?= htmlspecialchars($c['category_code']) ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td><code><?= htmlspecialchars($c['slug']) ?></code></td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a class="btn btn-outline-primary btn-icon" href="/admin/collections/<?= (int)$c['id'] ?>/edit" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="post" action="/admin/collections/<?= (int)$c['id'] ?>/delete" onsubmit="return confirm('Delete this collection?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-danger btn-icon" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
/* Equal-size action buttons */
.btn-icon { width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
.btn-group-sm .btn-icon { width: 32px; height: 32px; }

.card { transition: all 0.2s ease; }
.card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; }
</style>
