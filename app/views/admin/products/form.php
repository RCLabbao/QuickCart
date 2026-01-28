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

<?php if ($hasVariants && !empty($parentProduct)): ?>
<!-- Variant Notice -->
<div class="alert alert-info d-flex align-items-center mb-4">
  <i class="bi bi-diagram-3 fs-4 me-3"></i>
  <div>
    <strong>This is a variant product.</strong>
    <p class="mb-0">It belongs to the parent product: <a href="/admin/products/<?= (int)$parentProduct['id'] ?>/edit" class="alert-link"><?= htmlspecialchars($parentProduct['title']) ?></a></p>
  </div>
</div>
<?php endif; ?>

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
            <?php
            // Check if this is a parent product with variants
            $hasVariantsStock = $hasVariants && !empty($product['parent_product_id']) === false && !empty($variants);
            ?>
            <label class="form-label fw-semibold">Stock Quantity</label>
            <?php if ($hasVariantsStock): ?>
              <input class="form-control" type="number" name="stock" value="<?= (int)($product['stock'] ?? 0) ?>" placeholder="0" readonly style="background-color: #f8f9fa;">
              <div class="form-text text-warning">
                <i class="bi bi-info-circle me-1"></i>This parent product has variants. Stock is managed at the variant level.
              </div>
            <?php else: ?>
              <input class="form-control" type="number" name="stock" value="<?= (int)($product['stock'] ?? 0) ?>" placeholder="0">
              <div class="form-text">Leave empty for unlimited stock</div>
            <?php endif; ?>
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
            <i class="bi bi-tag me-2"></i>Sale Pricing
          </h6>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Sale Price</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input class="form-control" type="number" step="0.01" name="sale_price" value="<?= number_format((float)($product['sale_price'] ?? 0), 2, '.', '') ?>" placeholder="0.00">
              </div>
              <div class="form-text">This is the brochure selling price from CSV. Leave blank or 0 for no sale pricing. Must be lower than regular price.</div>
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
  
  <?php if ($hasVariants && empty($product['parent_product_id'])): ?>
  <!-- Product Variants Section -->
  <div class="col-lg-12">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="bi bi-diagram-3 me-2"></i>Product Variants
          <span class="badge bg-primary ms-2"><?= count($variants) ?> variants</span>
        </h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVariantModal">
          <i class="bi bi-plus-circle me-1"></i>Add Variant
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($variants)): ?>
          <div class="text-center py-4">
            <i class="bi bi-diagram-3 fs-1 text-muted"></i>
            <p class="text-muted mb-0">No variants yet. Add your first variant.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0" id="variantsTable">
              <thead class="table-light">
                <tr>
                  <th style="width: 60px;"></th>
                  <th>Variant</th>
                  <th>FSC</th>
                  <th>Barcode</th>
                  <th>Price</th>
                  <th>Stock</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($variants as $v): ?>
                <tr data-variant-id="<?= (int)$v['id'] ?>">
                  <td>
                    <div class="bg-light rounded" style="width: 40px; height: 40px; overflow: hidden;">
                      <?php if (!empty($v['image_url'])): ?>
                        <img src="<?= htmlspecialchars($v['image_url']) ?>" class="w-100 h-100 object-fit-cover">
                      <?php else: ?>
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                          <i class="bi bi-image"></i>
                        </div>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-info"><?= htmlspecialchars($v['variant_attributes']) ?></span>
                  </td>
                  <td class="variant-fsc"><?= htmlspecialchars($v['fsc'] ?? '-') ?></td>
                  <td class="variant-barcode"><?= htmlspecialchars($v['barcode'] ?? '-') ?></td>
                  <td class="variant-price">₱<?= number_format((float)$v['price'], 2) ?></td>
                  <td class="variant-stock"><?= (int)$v['stock'] ?></td>
                  <td class="variant-status">
                    <span class="badge bg-<?= $v['status'] === 'active' ? 'success' : 'secondary' ?>">
                      <?= ucfirst($v['status']) ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="/admin/products/<?= (int)$v['id'] ?>/edit" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger delete-variant-btn" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (!empty($suggestedVariants)): ?>
  <!-- Suggested Variants Section -->
  <div class="col-lg-12">
    <div class="card border-0 shadow-sm mb-4 border-warning">
      <div class="card-header bg-warning bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="bi bi-lightbulb me-2"></i>Suggested Variants Found
          <span class="badge bg-warning ms-2"><?= count($suggestedVariants) ?> products</span>
        </h5>
        <button class="btn btn-sm btn-warning" onclick="mergeAllSuggestedVariants()">
          <i class="bi bi-diagram-3 me-1"></i>Merge All as Variants
        </button>
      </div>
      <div class="card-body p-0">
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle me-2"></i>
          These products appear to be variants of this product based on similar titles. Select which ones to merge, or click "Merge All" to combine them all.
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="suggestedVariantsTable">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">
                  <input type="checkbox" class="form-check-input" id="selectAllSuggested" onchange="toggleAllSuggested(this)">
                </th>
                <th style="width: 60px;"></th>
                <th>Product Title</th>
                <th>FSC</th>
                <th>Barcode</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Variant Attribute (auto-detected)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($suggestedVariants as $sv): ?>
                <?php
                // Extract variant attribute
                $variantAttr = $sv['title'];
                $baseTitle = preg_replace('/\s+(38A|36B|34C|32A|42B|40C|\d{2,3}[A-Z]{1,3}|XL|XS|LARGE|MEDIUM|SMALL|XXL|XXXL|2XL|3XL|4XL|5XL)$/i', '', $sv['title']);
                if (stripos($sv['title'], $baseTitle) === 0 && strlen($baseTitle) > 5) {
                  $variantAttr = trim(substr($sv['title'], strlen($baseTitle)));
                }
                ?>
              <tr data-variant-id="<?= (int)$sv['id'] ?>">
                <td>
                  <input type="checkbox" class="form-check-input suggested-variant-checkbox" value="<?= (int)$sv['id'] ?>">
                </td>
                <td>
                  <div class="bg-light rounded" style="width: 40px; height: 40px; overflow: hidden;">
                    <?php if (!empty($sv['image_url'])): ?>
                      <img src="<?= htmlspecialchars($sv['image_url']) ?>" class="w-100 h-100 object-fit-cover">
                    <?php else: ?>
                      <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                        <i class="bi bi-image"></i>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($sv['title']) ?></div>
                  <small class="text-muted">ID: #<?= (int)$sv['id'] ?></small>
                </td>
                <td><?= htmlspecialchars($sv['fsc'] ?? '-') ?></td>
                <td><?= htmlspecialchars($sv['barcode'] ?? '-') ?></td>
                <td>₱<?= number_format((float)$sv['price'], 2) ?></td>
                <td><?= (int)$sv['stock'] ?></td>
                <td><span class="badge bg-info"><?= htmlspecialchars($variantAttr) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3 text-end">
          <button class="btn btn-warning" onclick="mergeSelectedSuggestedVariants()">
            <i class="bi bi-diagram-3 me-1"></i>Merge Selected as Variants
          </button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Add Variant Modal -->
  <div class="modal fade" id="addVariantModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="/admin/products/<?= (int)$product['id'] ?>/variants" id="addVariantForm">
          <?= csrf_field() ?>
          <div class="modal-header">
            <h5 class="modal-title">Add New Variant</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Variant Attribute <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="variant_attributes" required placeholder="e.g., 38A, 36B, Small">
            </div>
            <div class="mb-3">
              <label class="form-label">FSC <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="fsc" placeholder="Enter unique FSC code for this variant" required>
              <div class="form-text">Each variant should have its own unique FSC code from the CSV/import.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Barcode</label>
              <input type="text" class="form-control" name="barcode" placeholder="Barcode">
            </div>
            <div class="row">
              <div class="col-6">
                <label class="form-label">Price</label>
                <div class="input-group">
                  <span class="input-group-text">₱</span>
                  <input type="number" step="0.01" class="form-control" name="price" value="<?= number_format((float)($product['price'] ?? 0), 2, '.', '') ?>">
                </div>
              </div>
              <div class="col-6">
                <label class="form-label">Stock</label>
                <input type="number" class="form-control" name="stock" value="0">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Variant</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
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

  // Variant Management
  const variantsTable = document.getElementById('variantsTable');
  if (variantsTable) {
    // Handle Add Variant form submission via AJAX
    const addVariantForm = document.getElementById('addVariantForm');
    if (addVariantForm) {
      addVariantForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch(this.action, {
          method: 'POST',
          body: formData
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            // Close modal and reload page
            const modalEl = document.getElementById('addVariantModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            location.reload();
          } else {
            alert(data.error || 'Failed to add variant');
          }
        })
        .catch(err => alert('Error: ' + err.message));
      });
    }

    // Handle variant deletion
    variantsTable.querySelectorAll('.delete-variant-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        if (!confirm('Are you sure you want to delete this variant?')) return;

        const row = this.closest('tr');
        const variantId = row.dataset.variantId;
        const productId = window.location.pathname.split('/')[3];

        fetch(`/admin/products/${productId}/variants/${variantId}/delete`, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: '_token=' + document.querySelector('[name="_token"]').value
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            row.remove();
            // Update badge count
            const badge = document.querySelector('.card-header .badge');
            if (badge) {
              const count = parseInt(badge.textContent) || 0;
              badge.textContent = Math.max(0, count - 1) + ' variants';
            }
          } else {
            alert(data.error || 'Failed to delete variant');
          }
        })
        .catch(err => alert('Error: ' + err.message));
      });
    });
  }

  // Suggested Variants Management
  window.toggleAllSuggested = function(checkbox) {
    document.querySelectorAll('.suggested-variant-checkbox').forEach(cb => {
      cb.checked = checkbox.checked;
    });
  };

  window.mergeSelectedSuggestedVariants = function() {
    const selected = Array.from(document.querySelectorAll('.suggested-variant-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
      alert('Please select at least one product to merge.');
      return;
    }
    mergeVariantsRequest(selected);
  };

  window.mergeAllSuggestedVariants = function() {
    const allIds = Array.from(document.querySelectorAll('.suggested-variant-checkbox')).map(cb => cb.value);
    if (allIds.length === 0) {
      alert('No suggested variants to merge.');
      return;
    }
    if (!confirm(`Merge all ${allIds.length} suggested products as variants?`)) {
      return;
    }
    mergeVariantsRequest(allIds);
  };

  function mergeVariantsRequest(variantIds) {
    const productId = window.location.pathname.split('/')[3];
    const formData = new FormData();
    formData.append('_token', document.querySelector('[name="_token"]').value);
    variantIds.forEach(id => formData.append('variant_ids[]', id));

    fetch(`/admin/products/${productId}/merge-variants`, {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        location.reload();
      } else {
        alert(data.error || 'Failed to merge variants');
      }
    })
    .catch(err => alert('Error: ' + err.message));
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


