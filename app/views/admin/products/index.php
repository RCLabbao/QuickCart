<?php use function App\Core\csrf_field; ?>

<!-- Products Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Product Management</h1>
    <p class="text-muted mb-0">Manage your store inventory and pricing</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-primary" href="/admin/products/create">
      <i class="bi bi-plus-circle me-2"></i>Add Product
    </a>
    <a class="btn btn-outline-warning" href="/admin/products/duplicates">
      <i class="bi bi-exclamation-diamond me-2"></i>Find Duplicates
    </a>
    <?php $showVariants = isset($_GET['show_variants']) && $_GET['show_variants'] === '1'; ?>
    <a class="btn btn-outline-<?= $showVariants ? 'primary' : 'secondary' ?>"
       href="/admin/products?show_variants=<?= $showVariants ? '0' : '1' ?>">
      <i class="bi bi-diagram-3 me-2"></i><?= $showVariants ? 'Hide Variants' : 'Show Variants' ?>
    </a>

    <div class="btn-group">
      <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="bi bi-download me-2"></i>Export
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/admin/products/export">
          <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export CSV with Images
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <form class="dropdown-item p-0" method="post" action="/admin/products/import-stock" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <label class="d-block px-3 py-2 mb-0" style="cursor: pointer;">
              <i class="bi bi-upload me-2"></i>Import Stock CSV
              <input type="file" name="csv" accept=".csv" onchange="this.form.submit()" hidden>
            </label>
          </form>
        </li>
      </ul>
    </div>
  </div>
</div>

<!-- Product Statistics -->
<?php
// Use the statistics passed from the controller instead of counting current page products
$totalProducts = $stats['total_products'] ?? 0;
$activeProducts = $stats['active_products'] ?? 0;
$lowStockProducts = $stats['low_stock_products'] ?? 0;
$draftProducts = $stats['draft_products'] ?? 0;
?>
<div class="row g-4 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-box fs-4 text-primary"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($totalProducts) ?></h3>
        <p class="text-muted mb-0">Total Products</p>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-check-circle fs-4 text-success"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($activeProducts) ?></h3>
        <p class="text-muted mb-0">Active Products</p>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-exclamation-triangle fs-4 text-warning"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($lowStockProducts) ?></h3>
        <p class="text-muted mb-0">Low Stock</p>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-secondary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-file-earmark fs-4 text-secondary"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($draftProducts) ?></h3>
        <p class="text-muted mb-0">Draft Products</p>
      </div>
    </div>
  </div>
</div>
<!-- Search and Filters -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Search Products</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input class="form-control" type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Search by title, FSC, or barcode (scan to search)..." autofocus>
        </div>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold">Status</label>
        <select class="form-select" name="status">
          <?php $sel=$_GET['status']??''; foreach(['','active','draft'] as $s): ?>
            <option value="<?= $s ?>" <?= $sel===$s?'selected':'' ?>><?= $s?ucfirst($s):'All Statuses' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">Collection</label>
        <select class="form-select" name="collection_id">
          <option value="">All Collections</option>
          <?php foreach(($collections ?? []) as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (isset($_GET['collection_id']) && (int)$_GET['collection_id']===(int)$c['id'])?'selected':'' ?>><?= htmlspecialchars($c['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-funnel me-2"></i>Apply Filters
          </button>
          <a class="btn btn-outline-warning" href="/admin/products?low=1">
            <i class="bi bi-exclamation-triangle me-2"></i>Low Stock
          </a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
  <!-- Price Adjustment Tool -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-calculator me-2"></i>Bulk Price Adjustment
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="/admin/products/adjust-prices">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Scope</label>
              <select class="form-select" name="collection_id">
                <option value="">All Collections</option>
                <?php foreach(($collections ?? []) as $c): ?>
                  <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Status</label>
              <select class="form-select" name="status">
                <option value="">Any Status</option>
                <option value="active">Active Only</option>
                <option value="draft">Draft Only</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Type</label>
              <select class="form-select" name="type">
                <option value="percent">Percentage</option>
                <option value="fixed">Fixed Amount</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Direction</label>
              <select class="form-select" name="dir">
                <option value="inc">Increase</option>
                <option value="dec">Decrease</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Amount</label>
              <input class="form-control" type="number" step="0.01" name="amount" required placeholder="0.00">
            </div>
            <div class="col-12">
              <button class="btn btn-warning w-100" onclick="return confirm('Apply price adjustment to selected products?')">
                <i class="bi bi-calculator me-2"></i>Apply Price Adjustment
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bulk Actions -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-lightning me-2"></i>Bulk Actions
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="/admin/products/bulk" id="bulkActionsForm">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold">Action</label>
              <select class="form-select" name="action" required id="bulkAction">
                <option value="">Select Action...</option>
                <option value="activate">Set as Active</option>
                <option value="draft">Set as Draft</option>
                <option value="assign_collection">Assign to Collection</option>
                <option value="delete">Delete Products</option>
              </select>
            </div>
            <div class="col-12" id="collectionSelect" style="display: none;">
              <label class="form-label fw-semibold">Target Collection</label>
              <select class="form-select" name="collection_id">
                <option value="">Select Collection...</option>
                <?php foreach(($collections ?? []) as $c): ?>
                  <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <button class="btn btn-primary w-100" type="submit" onclick="return validateBulkAction()">
                <i class="bi bi-lightning me-2"></i>Apply to Selected
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Products Table -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-bottom">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">
        <i class="bi bi-table me-2"></i>Products List
      </h5>
      <span class="badge bg-secondary"><?= count($products) ?> products</span>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;">
              <input type="checkbox" class="form-check-input" onclick="toggleAllProducts(this)" id="selectAll">
            </th>
            <th style="width: 80px;">ID</th>
            <th>Product</th>
            <th style="width: 120px;">Price</th>
            <th style="width: 100px;">Stock</th>
            <th style="width: 100px;">Status</th>
            <th style="width: 200px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
            <tr>
              <td>
                <input type="checkbox" class="form-check-input product-checkbox" name="ids[]" value="<?= (int)$p['id'] ?>">
              </td>
              <td>
                <span class="badge bg-light text-dark">#<?= (int)$p['id'] ?></span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="bg-light rounded me-3" style="width: 50px; height: 50px; overflow: hidden;">
                    <?php if (!empty($p['image_url'])): ?>
                      <img src="<?= htmlspecialchars($p['image_url']) ?>"
                           class="w-100 h-100 object-fit-cover"
                           alt="Product image">
                    <?php else: ?>
                      <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                        <i class="bi bi-image" style="font-size: 20px;"></i>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div>
                    <h6 class="mb-1">
                      <?= htmlspecialchars($p['title']) ?>
                      <?php if (!empty($p['variant_attributes'])): ?>
                        <span class="badge bg-info ms-2"><?= htmlspecialchars($p['variant_attributes']) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($p['parent_product_id'])): ?>
                        <span class="badge bg-secondary ms-1"><i class="bi bi-diagram-3 me-1"></i>Variant</span>
                      <?php endif; ?>
                    </h6>
                    <?php if (!empty($p['sku']) || !empty($p['barcode'])): ?>
                      <div class="small text-muted mb-1">
                        <?php if (!empty($p['sku'])): ?><span class="me-2"><i class="bi bi-upc-scan me-1"></i>FSC: <?= htmlspecialchars($p['sku']) ?></span><?php endif; ?>
                        <?php if (!empty($p['barcode'])): ?><span class="me-2"><i class="bi bi-upc me-1"></i><?= htmlspecialchars($p['barcode']) ?></span><?php endif; ?>
                      </div>
                    <?php endif; ?>
                    <small class="text-muted">
                      <?php if (!empty($p['collection_id'])): ?>
                        <?php
                        $collection = array_filter($collections, fn($c) => $c['id'] == $p['collection_id']);
                        $collectionName = !empty($collection) ? reset($collection)['title'] : 'Unknown';
                        ?>
                        <i class="bi bi-grid me-1"></i><?= htmlspecialchars($collectionName) ?>
                      <?php else: ?>
                        <i class="bi bi-dash-circle me-1"></i>No collection
                      <?php endif; ?>
                    </small>
                  </div>
                </div>
              </td>
              <td>
                <form action="/admin/products/quick-update" method="post" class="quick-update-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text">₱</span>
                    <input class="form-control" type="number" step="0.01" name="price"
                           value="<?= number_format((float)$p['price'],2,'.','') ?>"
                           onchange="this.form.submit()">
                  </div>
              </td>
              <td>
                  <div class="input-group input-group-sm">
                    <input class="form-control" type="number" name="stock"
                           value="<?= (int)($p['stock'] ?? 0) ?>"
                           onchange="this.form.submit()">
                    <?php if (($p['stock'] ?? 0) <= 3): ?>
                      <span class="input-group-text text-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                      </span>
                    <?php endif; ?>
                  </div>
                </form>
              </td>
              <td>
                <?php if ($p['status'] === 'active'): ?>
                  <span class="badge bg-success">Active</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Draft</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a class="btn btn-outline-primary" href="/admin/products/<?= (int)$p['id'] ?>/edit" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a class="btn btn-outline-info" href="/products/<?= (int)$p['id'] ?>" target="_blank" title="View">
                    <i class="bi bi-eye"></i>
                  </a>
                  <form action="/admin/products/<?= (int)$p['id'] ?>/delete" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-danger" onclick="return confirm('Delete this product?')" title="Delete">
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

    <?php if (empty($products)): ?>
      <div class="text-center py-5">
        <i class="bi bi-box fs-1 text-muted"></i>
        <h5 class="mt-3 text-muted">No products found</h5>
        <p class="text-muted">Try adjusting your filters or add your first product.</p>
        <a href="/admin/products/create" class="btn btn-primary">
          <i class="bi bi-plus-circle me-2"></i>Add Your First Product
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Pagination -->
<?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
<div class="card border-0 shadow-sm mt-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center">
      <div class="text-muted">
        Showing <?= number_format($pagination['start_item']) ?> to <?= number_format($pagination['end_item']) ?>
        of <?= number_format($pagination['total_products']) ?> products
      </div>

      <nav aria-label="Products pagination">
        <ul class="pagination mb-0">
          <!-- Previous Page -->
          <?php if ($pagination['has_prev']): ?>
            <li class="page-item">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>">
                <i class="bi bi-chevron-left"></i> Previous
              </a>
            </li>
          <?php else: ?>
            <li class="page-item disabled">
              <span class="page-link">
                <i class="bi bi-chevron-left"></i> Previous
              </span>
            </li>
          <?php endif; ?>

          <!-- Page Numbers -->
          <?php
          $start = max(1, $pagination['current_page'] - 2);
          $end = min($pagination['total_pages'], $pagination['current_page'] + 2);

          // Show first page if not in range
          if ($start > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
            </li>
            <?php if ($start > 2): ?>
              <li class="page-item disabled">
                <span class="page-link">...</span>
              </li>
            <?php endif; ?>
          <?php endif; ?>

          <!-- Current range -->
          <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
              <?php if ($i === $pagination['current_page']): ?>
                <span class="page-link"><?= $i ?></span>
              <?php else: ?>
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
              <?php endif; ?>
            </li>
          <?php endfor; ?>

          <!-- Show last page if not in range -->
          <?php if ($end < $pagination['total_pages']): ?>
            <?php if ($end < $pagination['total_pages'] - 1): ?>
              <li class="page-item disabled">
                <span class="page-link">...</span>
              </li>
            <?php endif; ?>
            <li class="page-item">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['total_pages']])) ?>"><?= $pagination['total_pages'] ?></a>
            </li>
          <?php endif; ?>

          <!-- Next Page -->
          <?php if ($pagination['has_next']): ?>
            <li class="page-item">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])) ?>">
                Next <i class="bi bi-chevron-right"></i>
              </a>
            </li>
          <?php else: ?>
            <li class="page-item disabled">
              <span class="page-link">
                Next <i class="bi bi-chevron-right"></i>
              </span>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>

    <!-- Page Size Selector -->
    <div class="mt-3 pt-3 border-top">
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">
          Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small">Items per page:</span>
          <select class="form-select form-select-sm" style="width: auto;" onchange="changePageSize(this.value)">
            <option value="25" <?= ($pagination['per_page'] ?? 50) == 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= ($pagination['per_page'] ?? 50) == 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= ($pagination['per_page'] ?? 50) == 100 ? 'selected' : '' ?>>100</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
.card {
  transition: all 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.table-hover tbody tr:hover {
  background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.quick-update-form {
  margin: 0;
}

.quick-update-form input:focus {
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

.product-checkbox:checked {
  background-color: var(--bs-primary);
  border-color: var(--bs-primary);
}

@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }

  .btn-group-sm .btn {
    padding: 0.125rem 0.25rem;
  }

  .input-group-sm .form-control {
    font-size: 0.75rem;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Toggle all products checkbox
  window.toggleAllProducts = function(checkbox) {
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    productCheckboxes.forEach(cb => {
      cb.checked = checkbox.checked;
    });
    updateBulkActionsState();
  };

  // Update bulk actions state based on selected products
  function updateBulkActionsState() {
    const selectedProducts = document.querySelectorAll('.product-checkbox:checked');
    const bulkForm = document.getElementById('bulkActionsForm');
    const bulkButton = bulkForm.querySelector('button[type="submit"]');

    if (selectedProducts.length > 0) {
      bulkButton.disabled = false;
      bulkButton.innerHTML = `<i class="bi bi-lightning me-2"></i>Apply to ${selectedProducts.length} Selected`;
    } else {
      bulkButton.disabled = true;
      bulkButton.innerHTML = '<i class="bi bi-lightning me-2"></i>Apply to Selected';
    }
  }

  // Listen for checkbox changes
  document.querySelectorAll('.product-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActionsState);
  });

  // Show/hide collection select based on bulk action
  const bulkActionSelect = document.getElementById('bulkAction');
  const collectionSelect = document.getElementById('collectionSelect');

  bulkActionSelect.addEventListener('change', function() {
    if (this.value === 'assign_collection') {
      collectionSelect.style.display = 'block';
      collectionSelect.querySelector('select').required = true;
    } else {
      collectionSelect.style.display = 'none';
      collectionSelect.querySelector('select').required = false;
    }
  });

  // Validate bulk action
  window.validateBulkAction = function() {
    const selectedProducts = document.querySelectorAll('.product-checkbox:checked');
    const action = document.getElementById('bulkAction').value;
    const submitButton = document.querySelector('#bulkActionsForm button[type="submit"]');

    if (selectedProducts.length === 0) {
      alert('Please select at least one product.');
      return false;
    }

    if (!action) {
      alert('Please select an action.');
      return false;
    }

    let confirmMessage = `Apply "${action}" to ${selectedProducts.length} selected product(s)?`;

    if (action === 'delete') {
      confirmMessage = `Are you sure you want to delete ${selectedProducts.length} selected product(s)? This action cannot be undone.`;
    }

    if (!confirm(confirmMessage)) {
      return false;
    }

    // Add selected product IDs to the form before submission
    const bulkForm = document.getElementById('bulkActionsForm');

    // Remove any existing ids[] inputs from previous submissions
    const existingInputs = bulkForm.querySelectorAll('input[name="ids[]"]');
    existingInputs.forEach(input => input.remove());

    // Add new hidden inputs for each selected product
    selectedProducts.forEach(checkbox => {
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'ids[]';
      hiddenInput.value = checkbox.value;
      bulkForm.appendChild(hiddenInput);
    });

    // Show loading state with a small delay to allow form to submit
    if (submitButton) {
      setTimeout(() => {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
      }, 10);
    }

    // Allow form to submit
    return true;
  };

  // Auto-submit quick update forms with debouncing
  let updateTimeout;
  document.querySelectorAll('.quick-update-form input[type="number"]').forEach(input => {
    input.addEventListener('input', function() {
      clearTimeout(updateTimeout);
      updateTimeout = setTimeout(() => {
        this.form.submit();
      }, 1000); // Wait 1 second after user stops typing
    });
  });

  // Initialize bulk actions state
  updateBulkActionsState();

  // Add loading state to forms (exclude bulk actions form which has its own handling)
  document.querySelectorAll('form:not(#bulkActionsForm)').forEach(form => {
    form.addEventListener('submit', function() {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
      }
    });
  });
});

// Change page size function
function changePageSize(perPage) {
  const url = new URL(window.location);
  url.searchParams.set('per_page', perPage);
  url.searchParams.delete('page'); // Reset to first page
  window.location.href = url.toString();
}
</script>

