<?php use function App\Core\csrf_field; ?>

<!-- Collections Form Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?= isset($collection) ? 'Edit Collection' : 'Create Collection' ?></h1>
    <p class="text-muted mb-0">
      <?= isset($collection) ? 'Update collection details and cover image' : 'Create a new collection to group products' ?>
    </p>
  </div>
  <div>
    <a class="btn btn-outline-secondary" href="/admin/collections">
      <i class="bi bi-arrow-left me-2"></i>Back to Collections
    </a>
  </div>
</div>

<form method="post" enctype="multipart/form-data" action="<?= isset($collection)?('/admin/collections/'.(int)$collection['id']):'/admin/collections' ?>" class="row g-4">
  <?= csrf_field() ?>

  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0"><i class="bi bi-collection me-2"></i>Collection Details</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Title</label>
          <input class="form-control" name="title" value="<?= htmlspecialchars($collection['title'] ?? '') ?>" required placeholder="e.g. New Arrivals">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Description</label>
          <textarea class="form-control" name="description" rows="4" placeholder="Describe this collection...">
<?= htmlspecialchars($collection['description'] ?? '') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0"><i class="bi bi-image me-2"></i>Cover Image</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <input class="form-control" type="file" name="image" accept="image/*">
          <div class="form-text">JPEG, PNG, or WEBP. Max 5 MB.</div>
        </div>
        <?php if (!empty($collection['image_url'])): ?>
          <div class="mb-3">
            <img src="<?= htmlspecialchars($collection['image_url']) ?>" class="img-fluid rounded border" alt="Collection cover">
          </div>
        <?php endif; ?>
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i><?= isset($collection) ? 'Update Collection' : 'Create Collection' ?>
          </button>
          <a class="btn btn-outline-secondary" href="/admin/collections">
            <i class="bi bi-x-circle me-2"></i>Cancel
          </a>
        </div>
      </div>
    </div>
  </div>
</form>

<style>
.card { transition: all 0.2s ease; }
.card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; }
.form-control:focus, .form-select:focus { border-color: var(--bs-primary); box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25); }
</style>
