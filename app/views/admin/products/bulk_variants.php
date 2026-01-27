<?php use function App\Core\csrf_field; ?>
<?= csrf_field() ?>

<!-- Bulk Variants Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Bulk Variant Detection</h1>
    <p class="text-muted mb-0">
      Automatically detect and merge product variants based on title patterns
    </p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="/admin/products">
      <i class="bi bi-arrow-left me-2"></i>Back to Products
    </a>
    <?php if (!empty($variantGroups)): ?>
      <button class="btn btn-primary" onclick="bulkMergeAllVariants()">
        <i class="bi bi-diagram-3 me-2"></i>Merge All Variants
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($error)): ?>
<!-- Error Message -->
<div class="alert alert-danger d-flex align-items-center">
  <i class="bi bi-exclamation-triangle fs-4 me-3"></i>
  <div>
    <strong>Variant support not enabled</strong>
    <p class="mb-0"><?= htmlspecialchars($error) ?></p>
    <form method="post" action="/admin/maintenance/optimize" class="mt-3">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-warning">
        <i class="bi bi-tools me-2"></i>Enable Variant Support
      </button>
      <small class="text-muted d-block mt-2">This will add the necessary database columns to support product variants.</small>
    </form>
  </div>
</div>
<?php elseif (empty($variantGroups)): ?>
<!-- No Variants Found -->
<div class="alert alert-info">
  <div class="d-flex align-items-center">
    <i class="bi bi-check-circle fs-4 me-3"></i>
    <div>
      <strong>No variant groups detected!</strong>
      <p class="mb-0">All products appear to be properly organized. Products that are already variants or don't match variant patterns are not shown.</p>
    </div>
  </div>
</div>
<?php else: ?>
<!-- Variants Found -->
<div class="alert alert-success">
  <div class="d-flex align-items-center">
    <i class="bi bi-lightbulb fs-4 me-3"></i>
    <div>
      <strong>Detected <?= $totalGroups ?> variant groups</strong>
      <p class="mb-0">Found <?= $totalProducts ?> products that can be merged as variants. Review the groups below and click "Merge All Variants" to combine them.</p>
    </div>
  </div>
</div>

<!-- Variant Groups List -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-bottom">
    <h5 class="card-title mb-0">
      <i class="bi bi-diagram-3 me-2"></i>Variant Groups
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="accordion" id="variantGroupsAccordion">
      <?php $groupNum = 0; foreach ($variantGroups as $baseTitle => $products): $groupNum++; ?>
      <div class="accordion-item">
        <h2 class="accordion-header" id="heading<?= $groupNum ?>">
          <button class="accordion-button <?= $groupNum > 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $groupNum ?>">
            <span class="badge bg-primary me-2"><?= count($products) ?> items</span>
            <?= htmlspecialchars($baseTitle) ?>
          </button>
        </h2>
        <div id="collapse<?= $groupNum ?>" class="accordion-collapse collapse <?= $groupNum === 1 ? 'show' : '' ?>" data-bs-parent="#variantGroupsAccordion">
          <div class="accordion-body">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 50px;"></th>
                    <th>Product Title</th>
                    <th>FSC</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Will Become</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $isFirst = true;
                  foreach ($products as $product):
                    $variantAttr = trim(substr($product['title'], strlen($baseTitle)));
                    if ($variantAttr === '') {
                        $variantAttr = 'Parent';
                    }
                  ?>
                  <tr>
                    <td>
                      <?php if ($isFirst): ?>
                        <i class="bi bi-star-fill text-warning"></i>
                      <?php else: ?>
                        <i class="bi bi-circle text-muted"></i>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?= htmlspecialchars($product['title']) ?>
                      <?php if ($isFirst): ?>
                        <span class="badge bg-info ms-2">Oldest (Parent)</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($product['fsc'] ?? '-') ?></td>
                    <td>₱<?= number_format((float)$product['price'], 2) ?></td>
                    <td><?= (int)$product['stock'] ?></td>
                    <td>
                      <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>">
                        <?= ucfirst($product['status']) ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($isFirst): ?>
                        <span class="text-muted">Parent Product</span>
                      <?php else: ?>
                        <span class="badge bg-warning">Variant: <?= htmlspecialchars($variantAttr) ?></span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php $isFirst = false; endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Merging Variants...</h5>
      </div>
      <div class="modal-body text-center py-4">
        <div id="loadingSpinner" class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mb-2" id="progressStatus">Processing <?= $totalGroups ?> variant groups with <?= $totalProducts ?> products...</p>
        <div class="progress" style="height: 6px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
        </div>
      </div>
      <div class="modal-footer d-none" id="resultFooter">
        <button type="button" class="btn btn-primary" onclick="location.reload()">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Results Modal -->
<div class="modal fade" id="resultsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Merge Complete!</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <div class="display-4 text-success mb-2">
            <i class="bi bi-diagram-3"></i>
          </div>
          <h4>Variants Merged Successfully</h4>
        </div>
        <div class="row text-center">
          <div class="col-6">
            <div class="display-6 fw-bold text-primary" id="resultGroups">0</div>
            <div class="text-muted">Variant Groups</div>
          </div>
          <div class="col-6">
            <div class="display-6 fw-bold text-success" id="resultProducts">0</div>
            <div class="text-muted">Products Merged</div>
          </div>
        </div>
        <?php if (!empty($variantGroups)): ?>
        <div class="alert alert-info mt-3 mb-0">
          <i class="bi bi-info-circle me-2"></i>
          <strong>What's Next?</strong><br>
          <small>Your products have been organized as variants. You can now edit the parent products to manage pricing, stock, and images for all variants at once.</small>
        </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Stay Here</button>
        <button type="button" class="btn btn-primary" onclick="location.reload()">Refresh Page</button>
      </div>
    </div>
  </div>
</div>

<script>
function bulkMergeAllVariants() {
  if (!confirm(`Are you sure you want to merge all <?= $totalGroups ?> detected variant groups?\n\nThis will link <?= $totalProducts ?> products together as variants.`)) {
    return;
  }

  const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
  const resultsModal = new bootstrap.Modal(document.getElementById('resultsModal'));

  // Get CSRF token
  const tokenInput = document.querySelector('input[name="_token"]');
  if (!tokenInput) {
    alert('CSRF token not found. Please refresh the page and try again.');
    return;
  }

  progressModal.show();

  // Simulate progress updates
  let progress = 0;
  const progressInterval = setInterval(() => {
    progress += 10;
    if (progress > 90) clearInterval(progressInterval);
  }, 500);

  fetch('/admin/products/bulk-merge-variants', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: '_token=' + encodeURIComponent(tokenInput.value)
  })
  .then(r => {
    clearInterval(progressInterval);
    if (!r.ok) {
      throw new Error('Server returned ' + r.status);
    }
    return r.json();
  })
  .then(data => {
    progressModal.hide();
    clearInterval(progressInterval);

    if (data.success) {
      // Update results modal
      document.getElementById('resultGroups').textContent = data.merged_groups || 0;
      document.getElementById('resultProducts').textContent = data.merged_products || 0;

      // Show results modal
      resultsModal.show();

      if (data.errors && data.errors.length > 0) {
        console.warn('Some errors occurred:', data.errors);
        // Optionally show errors to user
      }
    } else {
      alert(data.error || 'Failed to merge variants');
    }
  })
  .catch(err => {
    progressModal.hide();
    clearInterval(progressInterval);
    console.error('Error:', err);
    alert('Error: ' + err.message + '\n\nPlease check the console for details and try again.');
  });
}
</script>

<style>
.accordion-button:not(.collapsed) {
  background-color: rgba(13, 110, 253, 0.1);
}
</style>
