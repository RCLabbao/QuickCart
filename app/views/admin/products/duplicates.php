<?php /** @var array $skuGroups */ /** @var array $barcodeGroups */ /** @var array $itemsByKey */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Duplicate SKU/Barcodes</h1>
    <p class="text-muted mb-0">Review and fix products that share the same SKU or barcode</p>
  </div>
  <div>
    <a class="btn btn-outline-secondary" href="/admin/products"><i class="bi bi-arrow-left me-2"></i>Back to Products</a>
  </div>
</div>

<?php if (empty($skuGroups) && empty($barcodeGroups)): ?>
  <div class="alert alert-success">No duplicates found 🎉</div>
<?php endif; ?>

<?php if (!empty($skuGroups)): ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom"><h5 class="mb-0"><i class="bi bi-upc-scan me-2"></i>Duplicate SKUs</h5></div>
    <div class="card-body">
      <?php foreach ($skuGroups as $g): $key = $g['sku']; $items = $itemsByKey['sku'][$key] ?? []; ?>
        <div class="mb-4">
          <div class="d-flex align-items-center mb-2">
            <span class="badge bg-warning me-2">SKU: <?= htmlspecialchars($key) ?></span>
            <span class="text-muted small"><?= count($items) ?> products</span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead class="table-light"><tr><th>ID</th><th>Title</th><th>SKU</th><th>Barcode</th><th>Price</th><th>Stock</th><th class="text-end">Actions</th></tr></thead>
              <tbody>
              <?php foreach ($items as $r): ?>
                <tr>
                  <td class="text-muted">#<?= (int)$r['id'] ?></td>
                  <td><?= htmlspecialchars($r['title']) ?></td>
                  <td><?= htmlspecialchars($r['sku']) ?></td>
                  <td><?= htmlspecialchars($r['barcode']) ?></td>
                  <td>₱<?= number_format((float)$r['price'],2) ?></td>
                  <td><?= (int)$r['stock'] ?></td>
                  <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/admin/products/<?= (int)$r['id'] ?>/edit">Edit</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($barcodeGroups)): ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom"><h5 class="mb-0"><i class="bi bi-upc me-2"></i>Duplicate Barcodes</h5></div>
    <div class="card-body">
      <?php foreach ($barcodeGroups as $g): $key = $g['barcode']; $items = $itemsByKey['barcode'][$key] ?? []; ?>
        <div class="mb-4">
          <div class="d-flex align-items-center mb-2">
            <span class="badge bg-warning me-2">Barcode: <?= htmlspecialchars($key) ?></span>
            <span class="text-muted small"><?= count($items) ?> products</span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead class="table-light"><tr><th>ID</th><th>Title</th><th>SKU</th><th>Barcode</th><th>Price</th><th>Stock</th><th class="text-end">Actions</th></tr></thead>
              <tbody>
              <?php foreach ($items as $r): ?>
                <tr>
                  <td class="text-muted">#<?= (int)$r['id'] ?></td>
                  <td><?= htmlspecialchars($r['title']) ?></td>
                  <td><?= htmlspecialchars($r['sku']) ?></td>
                  <td><?= htmlspecialchars($r['barcode']) ?></td>
                  <td>₱<?= number_format((float)$r['price'],2) ?></td>
                  <td><?= (int)$r['stock'] ?></td>
                  <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/admin/products/<?= (int)$r['id'] ?>/edit">Edit</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

