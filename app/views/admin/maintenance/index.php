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

<!-- System Statistics -->
<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-box fs-4 text-primary"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($stats['products']) ?></h3>
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
        <h3 class="h4 mb-1"><?= number_format($stats['orders']) ?></h3>
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
        <h3 class="h4 mb-1"><?= number_format($stats['collections']) ?></h3>
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
        <h3 class="h4 mb-1"><?= number_format($stats['users']) ?></h3>
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
                <span class="badge bg-success">
                  <i class="bi bi-check-circle me-1"></i>OK
                </span>
              <?php else: ?>
                <span class="badge bg-warning">
                  <i class="bi bi-exclamation-triangle me-1"></i>Missing
                </span>
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
                  <td><?= htmlspecialchars($table['table_name']) ?></td>
                  <td class="text-end">
                    <span class="badge bg-light text-dark"><?= $table['size_mb'] ?></span>
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

<!-- Maintenance Actions -->
<div class="row g-4">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-tools me-2"></i>Database Optimization
        </h5>
      </div>
      <div class="card-body">
        <p class="text-muted mb-4">
          Optimize database structure, add missing columns and indexes for better performance.
        </p>
        <form method="post" action="/admin/maintenance/optimize" onsubmit="return confirmAction('optimize the database')">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-warning w-100">
            <i class="bi bi-gear me-2"></i>Optimize Database
          </button>
        </form>
        <div class="mt-3">
          <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            This will add missing columns, tables, and indexes. Safe to run multiple times.
          </small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-database-add me-2"></i>Demo Data
        </h5>
      </div>
      <div class="card-body">
        <p class="text-muted mb-4">
          Add sample products, orders, and collections for testing and demonstration purposes.
        </p>
        <form method="post" action="/admin/maintenance/seed-demo" onsubmit="return confirmAction('add demo data')">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-info w-100">
            <i class="bi bi-plus-circle me-2"></i>Add Demo Data
          </button>
        </form>
        <div class="mt-3">
          <small class="text-muted">
            <i class="bi bi-exclamation-triangle me-1"></i>
            This will add 300+ demo products and sample orders. Use only for testing.
          </small>
        </div>
      </div>
    </div>
  </div>

  </div>
</div>

<!-- Reset Database (Reinstall schema + seed samples) -->
<div class="row g-4 mt-1">
  <div class="col-lg-12">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Database (Reinstall schema + seed samples)
        </h5>
      </div>
      <div class="card-body">
        <p class="text-danger">
          This will reinstall database structures and wipe catalog, orders, addresses, tags and related data, then seed sample data.
          Admin users and settings are preserved. Use only if your database is corrupted or migrations failed.
        </p>
        <form method="post" action="/admin/maintenance/reset-db" onsubmit="return confirm('This will REINSTALL the database schema and DELETE all catalog/order data, then add sample data. Admin users and settings are preserved. Proceed?');">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline-warning">
            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Database
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Dangerous: Wipe Data -->
<div class="row g-4 mt-1">
  <div class="col-lg-12">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-trash3 me-2"></i>Wipe Catalog & Orders
        </h5>
      </div>
      <div class="card-body">
        <p class="text-danger">This permanently deletes all products, images, collections, orders, addresses, coupons, delivery fees, and customer profiles. Users and settings are preserved.</p>
        <form method="post" action="/admin/maintenance/wipe" onsubmit="return confirm('This will permanently DELETE all catalog and order data. Are you absolutely sure?');">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>Delete All Catalog & Orders
          </button>
        </form>
      </div>
    </div>

<!-- Wipe Demo Data -->
<div class="row g-4 mt-1">
  <div class="col-lg-12">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-eraser me-2"></i>Remove Demo Data Only
        </h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Removes products named "Demo Product ..." and seeded test orders. Real data remains.</p>
        <form method="post" action="/admin/maintenance/wipe-demo" onsubmit="return confirm('Remove demo data added by the seeder?');">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline-danger">
            <i class="bi bi-eraser me-2"></i>Remove Demo Data
          </button>
        </form>
      </div>
    </div>
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
});
</script>
