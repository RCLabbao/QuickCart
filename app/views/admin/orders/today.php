<?php use function App\Core\csrf_field; ?>

<!-- Today's Orders Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Today's Orders</h1>
    <p class="text-muted mb-0">
      <i class="bi bi-calendar-day me-2"></i><?= date('l, F j, Y') ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="/admin/orders">
      <i class="bi bi-arrow-left me-2"></i>All Orders
    </a>
    <div class="btn-group">
      <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="bi bi-download me-2"></i>Export Today
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="/admin/orders/export?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>">
          <i class="bi bi-file-earmark-spreadsheet me-2"></i>Basic CSV
        </a></li>
        <li><a class="dropdown-item" href="/admin/orders/export-items?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>">
          <i class="bi bi-file-earmark-text me-2"></i>Detailed CSV (with items)
        </a></li>
      </ul>
    </div>
  </div>
</div>

<!-- Today's Statistics -->
<?php
// Use the statistics passed from the controller instead of counting current page orders
$todayOrders = $stats['today_orders'] ?? 0;
$todayPending = $stats['today_pending'] ?? 0;
$todayCompleted = $stats['today_completed'] ?? 0;
$todayRevenue = $stats['today_revenue'] ?? 0;
?>
<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-cart-check fs-4 text-primary"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($todayOrders) ?></h3>
        <p class="text-muted mb-0">Orders Today</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-clock fs-4 text-warning"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($todayPending) ?></h3>
        <p class="text-muted mb-0">Pending</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-check-circle fs-4 text-success"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($todayCompleted) ?></h3>
        <p class="text-muted mb-0">Completed</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-currency-dollar fs-4 text-info"></i>
        </div>
        <h3 class="h4 mb-1">₱<?= number_format($todayRevenue, 2) ?></h3>
        <p class="text-muted mb-0">Today's Revenue</p>
      </div>
    </div>
  </div>
</div>
<!-- Bulk Actions -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom">
    <h5 class="card-title mb-0">
      <i class="bi bi-lightning me-2"></i>Quick Actions
    </h5>
  </div>
  <div class="card-body">
    <form method="post" action="/admin/orders/bulk-status" id="todayBulkForm">
      <?= csrf_field() ?>
      <div class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Change Status To</label>
          <select class="form-select" name="status" required>
            <option value="">Select New Status...</option>
            <?php foreach(['pending','processing','shipped','completed','cancelled'] as $s): ?>
              <option value="<?= $s ?>">
                <?= ucfirst($s) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <button class="btn btn-warning w-100" type="submit" onclick="return validateTodayBulkAction()">
            <i class="bi bi-lightning me-2"></i>Apply to Selected Orders
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Today's Orders Table -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-bottom">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">
        <i class="bi bi-calendar-day me-2"></i>Today's Orders
      </h5>
      <span class="badge bg-primary"><?= count($orders) ?> orders</span>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;">
              <input type="checkbox" class="form-check-input" onclick="toggleAllTodayOrders(this)" id="selectAllTodayOrders">
            </th>
            <th style="width: 100px;">Order ID</th>
            <th>Customer</th>
            <th style="width: 120px;">Method</th>
            <th style="width: 120px;">Total</th>
            <th style="width: 120px;">Status</th>
            <th style="width: 120px;">Time</th>
            <th style="width: 200px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>
                <input type="checkbox" class="form-check-input today-order-checkbox" name="ids[]" value="<?= (int)$o['id'] ?>" form="todayBulkForm">
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
                <div class="fw-semibold"><?= date('g:i A', strtotime($o['created_at'])) ?></div>
                <small class="text-muted"><?= date('M j', strtotime($o['created_at'])) ?></small>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a class="btn btn-outline-primary" href="/admin/orders/<?= (int)$o['id'] ?>" title="View Details">
                    <i class="bi bi-eye"></i>
                  </a>
                  <?php if ($o['status'] !== 'completed'): ?>
                    <form method="post" action="/admin/orders/<?= (int)$o['id'] ?>/fulfill" class="d-inline fulfill-form" data-order-id="<?= (int)$o['id'] ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-success" title="Mark as Fulfilled">
                        <i class="bi bi-check-circle"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="badge bg-success">
                      <i class="bi bi-check-circle me-1"></i>Fulfilled
                    </span>
                  <?php endif; ?>
                  <form method="post" action="/admin/orders/<?= (int)$o['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this order?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-danger" title="Delete Order">
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

    <?php if (empty($orders)): ?>
      <div class="text-center py-5">
        <i class="bi bi-calendar-x fs-1 text-muted"></i>
        <h5 class="mt-3 text-muted">No orders today</h5>
        <p class="text-muted">No orders have been placed today yet. Check back later!</p>
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

.today-order-checkbox:checked {
  background-color: var(--bs-primary);
  border-color: var(--bs-primary);
}

.badge {
  font-size: 0.75em;
}

@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }

  .btn-group-sm .btn {
    padding: 0.125rem 0.25rem;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Toggle all today's orders checkbox
  window.toggleAllTodayOrders = function(checkbox) {
    const orderCheckboxes = document.querySelectorAll('.today-order-checkbox');
    orderCheckboxes.forEach(cb => {
      cb.checked = checkbox.checked;
    });
    updateTodayBulkState();
  };

  // Update bulk status state based on selected orders
  function updateTodayBulkState() {
    const selectedOrders = document.querySelectorAll('.today-order-checkbox:checked');
    const bulkForm = document.getElementById('todayBulkForm');
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
  document.querySelectorAll('.today-order-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateTodayBulkState);
  });

  // Validate bulk action
  window.validateTodayBulkAction = function() {
    const selectedOrders = document.querySelectorAll('.today-order-checkbox:checked');
    const status = document.querySelector('#todayBulkForm select[name="status"]').value;

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
  updateTodayBulkState();

  // Add loading state to forms (except fulfill forms)
  document.querySelectorAll('form:not(.fulfill-form)').forEach(form => {
    form.addEventListener('submit', function() {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
      }
    });
  });

  // Handle fulfill forms with AJAX for real-time updates
  document.querySelectorAll('.fulfill-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      const orderId = this.dataset.orderId;
      const button = this.querySelector('button');
      const originalContent = button.innerHTML;

      // Show loading state
      button.disabled = true;
      button.innerHTML = '<i class="bi bi-hourglass-split"></i>';

      // Submit form via fetch
      fetch(this.action, {
        method: 'POST',
        body: new FormData(this)
      })
      .then(response => {
        if (response.ok) {
          // Update the UI immediately
          const row = this.closest('tr');
          const statusCell = row.querySelector('td:nth-child(6)'); // Status column
          const actionsCell = row.querySelector('td:last-child'); // Actions column

          // Update status badge
          statusCell.innerHTML = `
            <span class="badge bg-success">
              <i class="bi bi-check-circle me-1"></i>Completed
            </span>
          `;

          // Replace fulfill button with fulfilled badge
          this.outerHTML = `
            <span class="badge bg-success">
              <i class="bi bi-check-circle me-1"></i>Fulfilled
            </span>
          `;

          // Show success message
          showNotification('Order #' + orderId + ' marked as fulfilled!', 'success');

          // Update statistics (if visible)
          updateTodayStats();
        } else {
          throw new Error('Failed to fulfill order');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        button.disabled = false;
        button.innerHTML = originalContent;
        showNotification('Failed to fulfill order. Please try again.', 'error');
      });
    });
  });
});

// Legacy function for backward compatibility
function validateBulkAction() {
  return validateTodayBulkAction();
}

// Show notification function
function showNotification(message, type = 'info') {
  // Create notification element
  const notification = document.createElement('div');
  notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
  notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  notification.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;

  // Add to page
  document.body.appendChild(notification);

  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 5000);
}

// Update today's statistics
function updateTodayStats() {
  // This would typically fetch updated stats from the server
  // For now, we'll just update the completed count
  const completedCard = document.querySelector('.card-body h3:contains("Completed")');
  if (completedCard) {
    const currentCount = parseInt(completedCard.textContent) || 0;
    completedCard.textContent = (currentCount + 1).toLocaleString();
  }
}
</script>

