<?php use function App\Core\csrf_field; ?>

<!-- Product Form Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?= isset($product) ? 'Edit Product' : 'Add Product' ?></h1>
    <p class="text-muted mb-0">
      <?= isset($product) ? 'Update product information and settings' : 'Add a new product to your catalog' ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="/admin/products">
      <i class="bi bi-arrow-left me-2"></i>Back to Products
    </a>
    <?php if (isset($product)): ?>
      <a class="btn btn-outline-info" href="/products/<?= htmlspecialchars($product['slug']) ?>" target="_blank">
        <i class="bi bi-eye me-2"></i>View Product
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Product Form -->
<form method="post" enctype="multipart/form-data" action="<?= isset($product)?('/admin/products/'.(int)$product['id']):'/admin/products' ?>" class="row g-4">
  <?= csrf_field() ?>

  <!-- Main Product Information -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-info-circle me-2"></i>Product Information
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label fw-semibold">Product Title</label>
            <input class="form-control" name="title" value="<?= htmlspecialchars($product['title'] ?? '') ?>" required placeholder="Enter product name">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Description</label>
            <textarea class="form-control" name="description" rows="4" placeholder="Describe your product..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Price</label>
            <div class="input-group">
              <span class="input-group-text">₱</span>
              <input class="form-control" type="number" step="0.01" name="price" value="<?= number_format((float)($product['price'] ?? 0), 2, '.', '') ?>" required placeholder="0.00">
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Stock Quantity</label>
            <input class="form-control" type="number" name="stock" value="<?= (int)($product['stock'] ?? 0) ?>" placeholder="0">
            <div class="form-text">Leave empty for unlimited stock</div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">FSC</label>
            <input class="form-control" type="text" name="fsc" value="<?= htmlspecialchars($product['fsc'] ?? '') ?>" placeholder="e.g. FSC-1234">
            <div class="form-text">Product code (FSC). Scannable.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Barcode</label>
            <input class="form-control" type="text" name="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>" placeholder="UPC/EAN/Code128...">
          </div>
        </div>

        <!-- Sale Pricing Section -->
        <div class="mt-4 pt-4 border-top">
          <h6 class="fw-semibold mb-3">
            <i class="bi bi-tag me-2"></i>Sale Pricing (Optional)
          </h6>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Sale Price</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input class="form-control" type="number" step="0.01" name="sale_price" value="<?= number_format((float)($product['sale_price'] ?? 0), 2, '.', '') ?>" placeholder="0.00">
              </div>
              <div class="form-text">Must be lower than regular price</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Sale Start Date</label>
              <input class="form-control" type="datetime-local" name="sale_start" value="<?= $product['sale_start'] ? date('Y-m-d\TH:i', strtotime($product['sale_start'])) : '' ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Sale End Date</label>
              <input class="form-control" type="datetime-local" name="sale_end" value="<?= $product['sale_end'] ? date('Y-m-d\TH:i', strtotime($product['sale_end'])) : '' ?>">
            </div>
          </div>
        </div>

        <!-- Tags Section -->
        <div class="mt-4 pt-4 border-top">
          <h6 class="fw-semibold mb-3">
            <i class="bi bi-tags me-2"></i>Product Tags
          </h6>
          <label class="form-label fw-semibold">Tags (comma-separated)</label>
          <input class="form-control" type="text" name="tags" value="<?= htmlspecialchars($tagsCsv ?? '') ?>" placeholder="e.g. summer, clearance, gift">
          <div class="form-text">Add tags to help customers find this product</div>
        </div>

      </div>
    </div>
  </div>

  <!-- Product Settings Sidebar -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-gear me-2"></i>Product Settings
        </h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Status</label>
          <select class="form-select" name="status">
            <option value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
              Active (Visible to customers)
            </option>
            <option value="draft" <?= ($product['status'] ?? '') === 'draft' ? 'selected' : '' ?>>
              Draft (Hidden from customers)
            </option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Collection</label>
          <select class="form-select" name="collection_id">
            <option value="">— No Collection —</option>
            <?php foreach ($collections as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (isset($product) && (int)$product['collection_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Product Images</label>
          <input class="form-control" type="file" name="images[]" multiple accept="image/*">
          <div class="form-text">
            <i class="bi bi-info-circle me-1"></i>
            Upload multiple images. First image will be the main product image.
          </div>
        </div>

        <?php if (!empty($images)): ?>
        <div class="mb-3">
          <label class="form-label fw-semibold">Current Images</label>
          <div class="row g-2">
            <?php foreach ($images as $index => $img): ?>
              <div class="col-6">
                <div class="position-relative">
                  <img src="<?= htmlspecialchars($img['url']) ?>" class="img-fluid rounded border" alt="Product image">
                  <?php if ($index === 0): ?>
                    <span class="position-absolute top-0 start-0 badge bg-primary m-1">Main</span>
                  <?php endif; ?>
                  <form method="post" action="/admin/products/<?= (int)$product['id'] ?>/images/<?= (int)$img['id'] ?>/delete" class="position-absolute top-0 end-0 m-1">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this image?')">&times;</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>
            <?= isset($product) ? 'Update Product' : 'Create Product' ?>
          </button>
          <?php if (isset($product)): ?>
            <a href="/admin/products/<?= (int)$product['id'] ?>/delete"
               class="btn btn-outline-danger"
               onclick="return confirm('Are you sure you want to delete this product?')">
              <i class="bi bi-trash me-2"></i>Delete Product
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</form>
<style>
.card {
  transition: all 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
}

@media (max-width: 768px) {
  .col-lg-8, .col-lg-4 {
    margin-bottom: 1rem;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Add loading state to form submission
  const form = document.querySelector('form');
  form.addEventListener('submit', function() {
    const submitButton = this.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Saving...';
    }
  });

  // Validate sale price
  const priceInput = document.querySelector('input[name="price"]');
  const salePriceInput = document.querySelector('input[name="sale_price"]');

  if (priceInput && salePriceInput) {
    salePriceInput.addEventListener('input', function() {
      const price = parseFloat(priceInput.value) || 0;
      const salePrice = parseFloat(this.value) || 0;

      if (salePrice > 0 && salePrice >= price) {
        this.setCustomValidity('Sale price must be lower than regular price');
      } else {
        this.setCustomValidity('');
      }
    });
  }
});
</script>

<?php if (!empty($events)): ?>
<div class="mt-4">
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
      <h5 class="card-title mb-0">
        <i class="bi bi-clock-history me-2"></i>Recent Stock Changes
      </h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Change</th>
              <th>Reason</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $ev): ?>
              <tr>
                <td><?= date('M j, Y g:i A', strtotime($ev['created_at'])) ?></td>
                <td>
                  <span class="badge <?= (int)$ev['delta'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                    <?= (int)$ev['delta'] > 0 ? '+' : '' ?><?= (int)$ev['delta'] ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($ev['reason'] ?? 'Manual adjustment') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>


