<?php use function App\Core\csrf_field; ?>

<!-- Maintenance Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">System Maintenance</h1>
    <p class="text-muted mb-0">Database optimization and system utilities</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-info" onclick="refreshStats()">
      <i class="bi bi-arrow-clockwise me-2"></i>Refresh Stats
    </button>
  </div>
</div>

<!-- Sub Tabs -->
<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">Overview</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-actions" type="button" role="tab">Actions</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-backup" type="button" role="tab">Backup & Restore</button>
  </li>
</ul>
<div class="tab-content">
  <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
    <!-- System Statistics -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
              <i class="bi bi-box fs-4 text-primary"></i>
            </div>
            <h3 class="h4 mb-1"><?= number_format($stats['products'] ?? 0) ?></h3>
            <p class="text-muted mb-0">Products</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
              <i class="bi bi-cart-check fs-4 text-success"></i>
            </div>
            <h3 class="h4 mb-1"><?= number_format($stats['orders'] ?? 0) ?></h3>
            <p class="text-muted mb-0">Orders</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
              <i class="bi bi-grid fs-4 text-info"></i>
            </div>
            <h3 class="h4 mb-1"><?= number_format($stats['collections'] ?? 0) ?></h3>
            <p class="text-muted mb-0">Collections</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
              <i class="bi bi-people fs-4 text-warning"></i>
            </div>
            <h3 class="h4 mb-1"><?= number_format($stats['users'] ?? 0) ?></h3>
            <p class="text-muted mb-0">Users</p>
          </div>
        </div>
      </div>
    </div>

    <!-- System Health Check -->
    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom">
            <h5 class="card-title mb-0">
              <i class="bi bi-shield-check me-2"></i>System Health Check
            </h5>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              <?php foreach ($checks as $item => $status): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span><?= htmlspecialchars($item) ?></span>
                  <?php if ($status): ?>
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>OK</span>
                  <?php else: ?>
                    <span class="badge bg-warning"><i class="bi bi-exclamation-triangle me-1"></i>Missing</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom">
            <h5 class="card-title mb-0">
              <i class="bi bi-hdd me-2"></i>Database Table Sizes
            </h5>
          </div>
          <div class="card-body">
            <?php if (empty($table_sizes)): ?>
              <div class="alert alert-light border">Not available on this host.</div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th>Table</th>
                    <th class="text-end">Size (MB)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (array_slice($table_sizes, 0, 8) as $table): ?>
                    <tr>
                      <td><?= htmlspecialchars($table['table_name'] ?? ($table['TABLE_NAME'] ?? ($table[0] ?? ''))) ?></td>
                      <td class="text-end"><span class="badge bg-light text-dark"><?= htmlspecialchars((string)($table['size_mb'] ?? ($table['SIZE_MB'] ?? ($table[1] ?? '')))) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Variant Debug Section -->
    <?php if (!empty($variant_debug) && empty($variant_debug['error'])): ?>
    <div class="row g-4 mt-1">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bi bi-bug me-2"></i>Variant Relationship Debug</h5>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#variantDebugBody">
              <i class="bi bi-chevron-down"></i> Toggle
            </button>
          </div>
          <div class="collapse show" id="variantDebugBody">
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <h6 class="text-muted mb-3">Database Summary</h6>
                  <table class="table table-sm table-bordered">
                    <tr><th>Total Products</th><td><?= number_format($variant_debug['summary']['total_products'] ?? 0) ?></td></tr>
                    <tr><th>Parent Products</th><td class="text-success"><?= number_format($variant_debug['summary']['parent_products'] ?? 0) ?></td></tr>
                    <tr><th>Variant Products</th><td class="text-info"><?= number_format($variant_debug['summary']['variant_products'] ?? 0) ?></td></tr>
                  </table>
                </div>
                <div class="col-md-8">
                  <h6 class="text-muted mb-3">
                    Products With Variant Patterns (should be linked)
                    <?php if (count($variant_debug['unlinked_variants'] ?? []) > 0): ?>
                      <span class="badge bg-danger ms-2"><?= count($variant_debug['unlinked_variants']) ?> FOUND</span>
                    <?php else: ?>
                      <span class="badge bg-success ms-2">ALL LINKED</span>
                    <?php endif; ?>
                  </h6>
                  <?php if (!empty($variant_debug['unlinked_variants'])): ?>
                    <div class="table-responsive" style="max-height: 200px;">
                      <table class="table table-sm table-bordered table-striped">
                        <thead class="table-dark">
                          <tr><th>ID</th><th>Title</th><th>parent_product_id</th></tr>
                        </thead>
                        <tbody>
                          <?php foreach ($variant_debug['unlinked_variants'] as $p): ?>
                            <tr>
                              <td><?= $p['id'] ?></td>
                              <td><?= htmlspecialchars($p['title']) ?></td>
                              <td class="text-danger"><?= $p['parent_product_id'] ?? 'NULL' ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    <div class="alert alert-warning mt-2 mb-0 small">
                      <i class="bi bi-exclamation-triangle me-1"></i>
                      <strong>These products should have parent_product_id set!</strong> Run "Reset & Re-detect Variants" to fix.
                    </div>
                  <?php else: ?>
                    <div class="alert alert-success mb-0 small">
                      <i class="bi bi-check-circle me-1"></i>
                      All products with variant patterns are correctly linked to their parents!
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="tab-pane fade" id="tab-actions" role="tabpanel">
    <!-- Maintenance Actions -->
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom"><h5 class="card-title mb-0"><i class="bi bi-tools me-2"></i>Database Optimization</h5></div>
          <div class="card-body">
            <p class="text-muted mb-4">Optimize database structure, add missing columns and indexes for better performance.</p>
            <form method="post" action="/admin/maintenance/optimize" onsubmit="return confirmAction('optimize the database')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-warning w-100"><i class="bi bi-gear me-2"></i>Optimize Database</button>
            </form>
            <div class="mt-3"><small class="text-muted"><i class="bi bi-info-circle me-1"></i>This will add missing columns, tables, and indexes. Safe to run multiple times.</small></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom"><h5 class="card-title mb-0"><i class="bi bi-patch-exclamation me-2"></i>Fix FSC Duplicates</h5></div>
          <div class="card-body">
            <p class="text-muted mb-4">Fix "Duplicate entry for key products.fsc" error by converting empty FSC strings to NULL.</p>
            <form method="post" action="/admin/maintenance/fix-fsc" onsubmit="return confirmAction('fix FSC duplicates')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle me-2"></i>Fix FSC Issues</button>
            </form>
            <div class="mt-3"><small class="text-muted"><i class="bi bi-lightbulb me-1"></i>Resolves import errors when products have empty FSC values. Also optimizes the table.</small></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom"><h5 class="card-title mb-0"><i class="bi bi-arrow-counterclockwise me-2"></i>Reset & Re-detect Variants</h5></div>
          <div class="card-body">
            <?php if (!empty($_SESSION['variant_reset_backup'])): ?>
              <div class="alert alert-warning mb-3">
                <strong><i class="bi bi-shield-exclamation me-2"></i>Backup Available!</strong>
                <p class="mb-2 small">You can undo the last reset if something went wrong.</p>
                <form method="post" action="/admin/maintenance/undo-reset-variants" onsubmit="return confirmAction('undo the last variant reset')">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-danger btn-sm w-100"><i class="bi bi-arrow-counterclockwise me-2"></i>Undo Last Reset</button>
                </form>
              </div>
            <?php endif; ?>
            <p class="text-muted mb-4">Clear all variant relationships and re-detect product variants from scratch.</p>
            <form method="post" action="/admin/maintenance/reset-variants" onsubmit="return confirmAction('reset and re-detect all variants')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-info w-100"><i class="bi bi-arrow-clockwise me-2"></i>Reset & Re-detect Variants</button>
            </form>
            <div class="mt-3"><small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Use this if variants were merged incorrectly. This will clear all parent_product_id values and re-run detection.</small></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom"><h5 class="card-title mb-0"><i class="bi bi-database-add me-2"></i>Demo Data</h5></div>
          <div class="card-body">
            <p class="text-muted mb-4">Add sample products, orders, and collections for testing and demonstration purposes.</p>
            <form method="post" action="/admin/maintenance/seed-demo" onsubmit="return confirmAction('add demo data')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-info w-100"><i class="bi bi-plus-circle me-2"></i>Add Demo Data</button>
            </form>
            <div class="mt-3"><small class="text-muted"><i class="bi bi-exclamation-triangle me-1"></i>This will add 300+ demo products and sample orders. Use only for testing.</small></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100 border-danger">
          <div class="card-header bg-white border-bottom border-danger"><h5 class="card-title mb-0 text-danger"><i class="bi bi-trash3 me-2"></i>Delete All Products</h5></div>
          <div class="card-body">
            <p class="text-muted mb-4">Permanently delete all products including images, tags, and stock events.</p>
            <form method="post" action="/admin/maintenance/delete-all-products" onsubmit="return confirmAction('delete ALL products. This cannot be undone!')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger w-100"><i class="bi bi-trash me-2"></i>Delete All Products</button>
            </form>
            <div class="mt-3"><small class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i><strong>Warning:</strong> This will permanently delete all products and their related data. This cannot be undone!</small></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reset Database, Wipe, Wipe Demo -->
    <div class="row g-4 mt-4">
      <div class="col-12">
        <h6 class="text-muted text-uppercase fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Danger Zone</h6>
      </div>
    </div>

    <div class="row g-4">
      <!-- Reset Database -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 border-warning">
          <div class="card-header bg-white border-bottom border-warning">
            <h5 class="card-title mb-0 text-warning-emphasis">
              <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Database
            </h5>
          </div>
          <div class="card-body">
            <p class="text-muted small mb-3">Reinstall schema + seed samples. Wipes catalog/orders but preserves admin users and settings.</p>
            <form method="post" action="/admin/maintenance/reset-db" onsubmit="return confirmAction('reset the database. This will DELETE all catalog/order data and reinstall the schema with sample data.')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-outline-warning w-100">
                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Database
              </button>
            </form>
            <div class="mt-3"><small class="text-muted"><i class="bi bi-info-circle me-1"></i>Use only if database is corrupted or migrations failed.</small></div>
          </div>
        </div>
      </div>

      <!-- Wipe Catalog & Orders -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 border-danger">
          <div class="card-header bg-white border-bottom border-danger">
            <h5 class="card-title mb-0 text-danger">
              <i class="bi bi-trash3 me-2"></i>Wipe Catalog & Orders
            </h5>
          </div>
          <div class="card-body">
            <p class="text-muted small mb-3">Permanently delete all products, collections, orders, addresses, coupons, and customer profiles.</p>
            <form method="post" action="/admin/maintenance/wipe" onsubmit="return confirmAction('wipe ALL catalog and order data. This cannot be undone!')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger w-100">
                <i class="bi bi-exclamation-triangle me-2"></i>Wipe All Data
              </button>
            </form>
            <div class="mt-3"><small class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Users and settings are preserved.</small></div>
          </div>
        </div>
      </div>

      <!-- Remove Demo Data -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom">
            <h5 class="card-title mb-0">
              <i class="bi bi-eraser me-2"></i>Remove Demo Data
            </h5>
          </div>
          <div class="card-body">
            <p class="text-muted small mb-3">Remove "Demo Product ..." items and seeded test orders only. Real data remains intact.</p>
            <form method="post" action="/admin/maintenance/wipe-demo" onsubmit="return confirmAction('remove demo data added by the seeder')">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-outline-danger w-100">
                <i class="bi bi-eraser me-2"></i>Remove Demo Data
              </button>
            </form>
            <div class="mt-3"><small class="text-muted"><i class="bi bi-info-circle me-1"></i>Safely removes only demo/test data.</small></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tab-backup" role="tabpanel">
    <div class="row g-4">
      <!-- Create Backup -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom">
            <h5 class="card-title mb-0">
              <i class="bi bi-cloud-download me-2"></i>Create Backup
            </h5>
          </div>
          <div class="card-body">
            <form method="post" action="/admin/maintenance/backup">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label fw-semibold">Backup Type</label>
                <select class="form-select" name="backup_type" required>
                  <option value="full">Full Database (All Tables)</option>
                  <option value="products">Products Only (Products, Images, Tags)</option>
                  <option value="orders">Orders Only (Orders, Items, Addresses)</option>
                  <option value="users">Users Only (Users, Roles, Profiles)</option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-download me-2"></i>Create Backup
              </button>
            </form>
            <div class="mt-3">
              <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Backups are saved as SQL files and can be restored later.
              </small>
            </div>
          </div>
        </div>
      </div>

      <!-- Restore Backup -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom">
            <h5 class="card-title mb-0">
              <i class="bi bi-cloud-upload me-2"></i>Restore Backup
            </h5>
          </div>
          <div class="card-body">
            <form method="post" action="/admin/maintenance/restore" onsubmit="return confirmRestore()">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label fw-semibold">Select Backup File</label>
                <select class="form-select" name="backup_file" required>
                  <option value="">Choose a backup...</option>
                  <?php foreach ($backup_files ?? [] as $backup): ?>
                    <option value="<?= htmlspecialchars($backup['filename']) ?>">
                      <?= htmlspecialchars($backup['filename']) ?>
                      (<?= number_format($backup['size'] / 1024 / 1024, 2) ?> MB, <?= $backup['created'] ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" class="btn btn-warning w-100" <?= empty($backup_files) ? 'disabled' : '' ?>>
                <i class="bi bi-arrow-counterclockwise me-2"></i>Restore Backup
              </button>
            </form>
            <?php if (empty($backup_files)): ?>
              <div class="mt-3">
                <small class="text-muted">No backup files available. Create a backup first.</small>
              </div>
            <?php else: ?>
              <div class="mt-3">
                <small class="text-danger">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  <strong>Warning:</strong> Restoring will replace current data!
                </small>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Backup Files List -->
    <?php if (!empty($backup_files)): ?>
    <div class="row g-4 mt-1">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white border-bottom">
            <h5 class="card-title mb-0">
              <i class="bi bi-archive me-2"></i>Backup Files
            </h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Created</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($backup_files as $backup): ?>
                    <tr>
                      <td>
                        <i class="bi bi-file-earmark-text me-2"></i>
                        <code><?= htmlspecialchars($backup['filename']) ?></code>
                      </td>
                      <td><?= number_format($backup['size'] / 1024 / 1024, 2) ?> MB</td>
                      <td><?= $backup['created'] ?></td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <form method="post" action="/admin/maintenance/restore" class="d-inline" onsubmit="return confirmRestore()">
                            <?= csrf_field() ?>
                            <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filename']) ?>">
                            <button type="submit" class="btn btn-outline-warning" title="Restore">
                              <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                          </form>
                          <form method="post" action="/admin/maintenance/delete-backup" class="d-inline" onsubmit="return confirm('Delete this backup file?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filename']) ?>">
                            <button type="submit" class="btn btn-outline-danger" title="Delete">
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
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

  </div>
</div>

</div>

<!-- System Information -->
<div class="card border-0 shadow-sm mt-4">
  <div class="card-header bg-white border-bottom">
    <h5 class="card-title mb-0">
      <i class="bi bi-info-circle me-2"></i>System Information
    </h5>
  </div>
  <div class="card-body">
    <div class="row g-4">
      <div class="col-md-4">
        <h6 class="fw-semibold">PHP Version</h6>
        <p class="text-muted mb-0"><?= PHP_VERSION ?></p>
      </div>
      <div class="col-md-4">
        <h6 class="fw-semibold">Database</h6>
        <p class="text-muted mb-0">
          <?php
          try {
            $version = \App\Core\DB::pdo()->query('SELECT VERSION()')->fetchColumn();
            echo htmlspecialchars($version);
          } catch (\Exception $e) {
            echo 'Unknown';
          }
          ?>
        </p>
      </div>
      <div class="col-md-4">
        <h6 class="fw-semibold">Memory Limit</h6>
        <p class="text-muted mb-0"><?= ini_get('memory_limit') ?></p>
      </div>
    </div>
  </div>
</div>

<style>
.card {
  transition: all 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.list-group-item {
  border: none !important;
  padding: 0.75rem 0;
}

.list-group-item:not(:last-child) {
  border-bottom: 1px solid rgba(0,0,0,0.125) !important;
}

@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }
}
</style>

<script>
function confirmAction(action) {
  return confirm(`Are you sure you want to ${action}? This action cannot be undone.`);
}

function confirmRestore() {
  return confirm('⚠️ WARNING: Restoring a backup will replace ALL current data in the selected tables.\n\nThis action cannot be undone.\n\nAre you sure you want to continue?');
}

function refreshStats() {
  window.location.reload();
}

document.addEventListener('DOMContentLoaded', function() {
  // Add loading state to forms
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
      }
    });
  });

  // Handle tab selection from URL parameter
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (tab === 'backup') {
    const backupTab = document.querySelector('button[data-bs-target="#tab-backup"]');
    if (backupTab) {
      backupTab.click();
    }
  }
});
</script>
