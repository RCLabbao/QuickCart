
<!-- Orders Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Order Management</h1>
    <p class="text-muted mb-0">Track and manage all customer orders</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-success" href="/admin/orders/create">
      <i class="bi bi-plus-circle me-2"></i>Create Manual Order
    </a>
    <a class="btn btn-primary" href="/admin/orders/today">
      <i class="bi bi-calendar-day me-2"></i>Today's Orders
    </a>
    <div class="btn-group">
      <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="bi bi-download me-2"></i>Export
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header">Quick Export</h6></li>
        <li><a class="dropdown-item" href="/admin/orders/export">
          <i class="bi bi-file-earmark-spreadsheet me-2"></i>Basic CSV
        </a></li>
        <li><a class="dropdown-item" href="/admin/orders/export-items">
          <i class="bi bi-file-earmark-text me-2"></i>Detailed CSV (with items)
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header">Date Range</h6></li>
        <li><a class="dropdown-item" href="/admin/orders/export?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>">
          <i class="bi bi-calendar-day me-2"></i>Today
        </a></li>
        <li><a class="dropdown-item" href="/admin/orders/export?from=<?= date('Y-m-d', strtotime('-7 days')) ?>&to=<?= date('Y-m-d') ?>">
          <i class="bi bi-calendar-week me-2"></i>Last 7 days
        </a></li>
        <li><a class="dropdown-item" href="/admin/orders/export?from=<?= date('Y-m-d', strtotime('-30 days')) ?>&to=<?= date('Y-m-d') ?>">
          <i class="bi bi-calendar-month me-2"></i>Last 30 days
        </a></li>
      </ul>
    </div>
  </div>
</div>

<!-- Order Statistics -->
<?php
// Use the statistics passed from the controller instead of counting current page orders
$totalOrders = $stats['total_orders'] ?? 0;
$pendingOrders = $stats['pending_orders'] ?? 0;
$completedOrders = $stats['completed_orders'] ?? 0;
$totalRevenue = $stats['total_revenue'] ?? 0;
?>
<div class="row g-4 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-cart-check fs-4 text-primary"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($totalOrders) ?></h3>
        <p class="text-muted mb-0">Total Orders</p>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <a href="/admin/orders?status=pending" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
      <div class="card-body text-center">
        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-clock fs-4 text-warning"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($pendingOrders) ?></h3>
        <p class="text-muted mb-0">Pending Orders</p>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="/admin/orders?status=completed" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
      <div class="card-body text-center">
        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-check-circle fs-4 text-success"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($completedOrders) ?></h3>
        <p class="text-muted mb-0">Completed Orders</p>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-currency-dollar fs-4 text-info"></i>
        </div>
        <h3 class="h4 mb-1">₱<?= number_format($totalRevenue, 2) ?></h3>
        <p class="text-muted mb-0">Total Revenue</p>
      </div>
    </div>
  </div>
</div>
<!-- Search and Filters -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label fw-semibold">From Date</label>
        <input class="form-control" type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">To Date</label>
        <input class="form-control" type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">Order Status</label>
        <select class="form-select" name="status">
          <?php $sel=$_GET['status']??''; foreach(['','pending','processing','shipped','completed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $sel===$s?'selected':'' ?>><?= $s?ucfirst($s):'All Statuses' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-funnel me-2"></i>Apply Filters
          </button>
          <a class="btn btn-outline-secondary" href="/admin/orders">
            <i class="bi bi-arrow-clockwise me-2"></i>Reset
          </a>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- Bulk Actions -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom">
    <h5 class="card-title mb-0">
      <i class="bi bi-lightning me-2"></i>Bulk Actions
    </h5>
  </div>
  <div class="card-body">
    <form method="post" action="/admin/orders/bulk-status" id="bulkStatusForm">
      <?= App\Core\csrf_field() ?>
      <div class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Change Status To</label>
          <select class="form-select" name="status" required>
            <option value="">Select New Status...</option>
            <?php foreach(['pending','processing','shipped','completed','cancelled'] as $s): ?>
              <option value="<?= $s ?>">
                <i class="bi bi-<?= $s === 'completed' ? 'check-circle' : ($s === 'cancelled' ? 'x-circle' : 'clock') ?>"></i>
                <?= ucfirst($s) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <button class="btn btn-warning w-100" type="submit" onclick="return validateBulkStatus()">
            <i class="bi bi-lightning me-2"></i>Apply to Selected Orders
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Orders Table -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-bottom">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">
        <i class="bi bi-list-ul me-2"></i>All Orders
      </h5>
      <span class="badge bg-secondary"><?= count($orders) ?> orders</span>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;">
              <input type="checkbox" class="form-check-input" onclick="toggleAllOrders(this)" id="selectAllOrders">
            </th>
            <th style="width: 100px;">Order ID</th>
            <th>Customer</th>
            <th style="width: 120px;">Method</th>
            <th style="width: 120px;">Total</th>
            <th style="width: 120px;">Status</th>
            <th style="width: 150px;">Date</th>
            <th style="width: 120px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>
                <input type="checkbox" class="form-check-input order-checkbox" name="ids[]" value="<?= (int)$o['id'] ?>" form="bulkStatusForm">
              </td>
              <td>
                <span class="badge bg-primary">#<?= (int)$o['id'] ?></span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="bg-light rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-person text-muted"></i>
                  </div>
                  <div>
                    <h6 class="mb-0"><?= htmlspecialchars($o['email'] ?? 'Guest Customer') ?></h6>
                    <?php if (!empty($o['phone'])): ?>
                      <small class="text-muted"><?= htmlspecialchars($o['phone']) ?></small>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge bg-info">
                  <i class="bi bi-truck me-1"></i><?= htmlspecialchars(strtoupper($o['shipping_method'])) ?>
                </span>
              </td>
              <td>
                <strong class="text-success">₱<?= number_format((float)$o['total'], 2) ?></strong>
              </td>
              <td>
                <?php
                $statusColors = [
                  'pending' => 'warning',
                  'processing' => 'info',
                  'shipped' => 'primary',
                  'completed' => 'success',
                  'cancelled' => 'danger'
                ];
                $statusIcons = [
                  'pending' => 'clock',
                  'processing' => 'gear',
                  'shipped' => 'truck',
                  'completed' => 'check-circle',
                  'cancelled' => 'x-circle'
                ];
                $color = $statusColors[$o['status']] ?? 'secondary';
                $icon = $statusIcons[$o['status']] ?? 'circle';
                ?>
                <span class="badge bg-<?= $color ?>">
                  <i class="bi bi-<?= $icon ?> me-1"></i><?= ucfirst($o['status']) ?>
                </span>
              </td>
              <td>
                <div>
                  <div class="fw-semibold"><?= date('M j, Y', strtotime($o['created_at'])) ?></div>
                  <small class="text-muted"><?= date('g:i A', strtotime($o['created_at'])) ?></small>
                </div>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a class="btn btn-outline-primary btn-icon" href="/admin/orders/<?= (int)$o['id'] ?>" title="View Details">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a class="btn btn-outline-info btn-icon" href="/admin/orders/<?= (int)$o['id'] ?>/invoice" title="Invoice">
                    <i class="bi bi-file-earmark-text"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if (empty($orders)): ?>
      <div class="text-center py-5">
        <i class="bi bi-cart-x fs-1 text-muted"></i>
        <h5 class="mt-3 text-muted">No orders found</h5>
        <p class="text-muted">Try adjusting your filters or check back later for new orders.</p>
      </div>
    <?php endif; ?>
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

.table-hover tbody tr:hover {
  background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.order-checkbox:checked {
  background-color: var(--bs-primary);
  border-color: var(--bs-primary);
}

.badge {
  font-size: 0.75em;
}

/* Equal-size square action buttons */
.btn-icon { width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
.btn-group-sm .btn-icon { width: 32px; height: 32px; }

@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }

  .btn-group-sm .btn {
    padding: 0.125rem 0.25rem;
  }
}
</style>
<style>
@media (max-width: 576px) {
  .row.g-4 .card .rounded-circle { width: 44px !important; height: 44px !important; }
  .row.g-4 .card h3 { font-size: 1.25rem; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Toggle all orders checkbox
  window.toggleAllOrders = function(checkbox) {
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    orderCheckboxes.forEach(cb => {
      cb.checked = checkbox.checked;
    });
    updateBulkStatusState();
  };

  // Update bulk status state based on selected orders
  function updateBulkStatusState() {
    const selectedOrders = document.querySelectorAll('.order-checkbox:checked');
    const bulkForm = document.getElementById('bulkStatusForm');
    const bulkButton = bulkForm.querySelector('button[type="submit"]');

    if (selectedOrders.length > 0) {
      bulkButton.disabled = false;
      bulkButton.innerHTML = `<i class="bi bi-lightning me-2"></i>Apply to ${selectedOrders.length} Selected Orders`;
    } else {
      bulkButton.disabled = true;
      bulkButton.innerHTML = '<i class="bi bi-lightning me-2"></i>Apply to Selected Orders';
    }
  }

  // Listen for checkbox changes
  document.querySelectorAll('.order-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkStatusState);
  });

  // Validate bulk status action
  window.validateBulkStatus = function() {
    const selectedOrders = document.querySelectorAll('.order-checkbox:checked');
    const status = document.querySelector('select[name="status"]').value;

    if (selectedOrders.length === 0) {
      alert('Please select at least one order.');
      return false;
    }

    if (!status) {
      alert('Please select a status.');
      return false;
    }

    const confirmMessage = `Change status to "${status}" for ${selectedOrders.length} selected order(s)?`;
    return confirm(confirmMessage);
  };

  // Initialize bulk status state
  updateBulkStatusState();

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

