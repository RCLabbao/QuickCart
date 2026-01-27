<?php use function App\Core\csrf_field; ?>

<!-- Hidden CSRF token for JavaScript -->
<input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">

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
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mb-0" id="progressStatus">Processing variant groups...</p>
      </div>
    </div>
  </div>
</div>

<script>
function bulkMergeAllVariants() {
  if (!confirm('Are you sure you want to merge all detected variants? This will link products together as variants.')) {
    return;
  }

  const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
  progressModal.show();

  fetch('/admin/products/bulk-merge-variants', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: '_token=' + document.querySelector('[name="_token"]').value
  })
  .then(r => r.json())
  .then(data => {
    progressModal.hide();
    if (data.success) {
      alert(`Successfully merged ${data.merged_groups} variant groups with ${data.merged_products} products!`);
      if (data.errors && data.errors.length > 0) {
        console.warn('Some errors occurred:', data.errors);
      }
      location.reload();
    } else {
      alert(data.error || 'Failed to merge variants');
    }
  })
  .catch(err => {
    progressModal.hide();
    alert('Error: ' + err.message);
  });
}
</script>

<style>
.accordion-button:not(.collapsed) {
  background-color: rgba(13, 110, 253, 0.1);
}
</style>
