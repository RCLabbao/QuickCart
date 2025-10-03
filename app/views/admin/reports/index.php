<?php use function App\Core\csrf_field; ?>

<!-- Reports Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Sales Reports</h1>
    <p class="text-muted mb-0">Analyze your store performance and trends</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-secondary" onclick="exportReport()">
      <i class="bi bi-download me-2"></i>Export
    </button>
    <button class="btn btn-outline-secondary" onclick="printReport()">
      <i class="bi bi-printer me-2"></i>Print
    </button>
  </div>
</div>

<!-- Date Range Filter -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label fw-semibold">From Date</label>
        <input class="form-control" type="date" name="from" value="<?= htmlspecialchars($from) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">To Date</label>
        <input class="form-control" type="date" name="to" value="<?= htmlspecialchars($to) ?>">
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary" type="submit">
          <i class="bi bi-funnel me-2"></i>Apply Filter
        </button>
      </div>
      <div class="col-md-3 text-end">
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('today')">Today</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('week')">This Week</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('month')">This Month</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Summary Cards -->
<?php
$totalRevenue = array_sum(array_column($salesByDay ?? [], 'revenue'));
$totalOrders = array_sum(array_column($salesByDay ?? [], 'orders'));
$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
$daysInRange = count($salesByDay ?? []);
?>
<div class="row g-4 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-currency-dollar fs-4 text-primary"></i>
        </div>
        <h3 class="h4 mb-1">₱<?= number_format($totalRevenue, 2) ?></h3>
        <p class="text-muted mb-0">Total Revenue</p>
        <small class="text-success">
          <i class="bi bi-arrow-up"></i> +12.5% vs last period
        </small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-receipt fs-4 text-success"></i>
        </div>
        <h3 class="h4 mb-1"><?= number_format($totalOrders) ?></h3>
        <p class="text-muted mb-0">Total Orders</p>
        <small class="text-success">
          <i class="bi bi-arrow-up"></i> +8.3% vs last period
        </small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-graph-up fs-4 text-info"></i>
        </div>
        <h3 class="h4 mb-1">₱<?= number_format($avgOrderValue, 2) ?></h3>
        <p class="text-muted mb-0">Avg Order Value</p>
        <small class="text-info">
          <i class="bi bi-dash"></i> +2.1% vs last period
        </small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center">
        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-calendar-day fs-4 text-warning"></i>
        </div>
        <h3 class="h4 mb-1"><?= $daysInRange ?></h3>
        <p class="text-muted mb-0">Days in Range</p>
        <small class="text-muted">
          <?= date('M j', strtotime($from)) ?> - <?= date('M j', strtotime($to)) ?>
        </small>
      </div>
    </div>
  </div>
</div>

<!-- Charts and Data -->
<div class="row g-4">
  <!-- Sales Trend Chart -->
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="bi bi-graph-up me-2"></i>Sales Trend
          </h5>
          <div class="btn-group btn-group-sm" role="group">
            <input type="radio" class="btn-check" name="chartType" id="revenue" checked>
            <label class="btn btn-outline-primary" for="revenue">Revenue</label>
            <input type="radio" class="btn-check" name="chartType" id="orders">
            <label class="btn btn-outline-primary" for="orders">Orders</label>
          </div>
        </div>
      </div>
      <div class="card-body">
        <canvas id="salesChart" height="100"></canvas>
      </div>
    </div>
  </div>

  <!-- Sales by Day Table -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-calendar3 me-2"></i>Daily Sales
        </h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th class="text-end">Orders</th>
                <th class="text-end">Revenue</th>
                <th class="text-end">Avg Order</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($salesByDay ?? []) as $r):
                $avgOrder = $r['orders'] > 0 ? $r['revenue'] / $r['orders'] : 0;
              ?>
                <tr>
                  <td>
                    <span class="fw-semibold"><?= date('M j', strtotime($r['d'])) ?></span>
                    <br><small class="text-muted"><?= date('D', strtotime($r['d'])) ?></small>
                  </td>
                  <td class="text-end">
                    <span class="badge bg-primary"><?= (int)$r['orders'] ?></span>
                  </td>
                  <td class="text-end fw-semibold">₱<?= number_format((float)$r['revenue'],2) ?></td>
                  <td class="text-end text-muted">₱<?= number_format($avgOrder, 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Sales by Collection -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
          <i class="bi bi-grid me-2"></i>Sales by Collection
        </h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Collection</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Revenue</th>
                <th class="text-end">Share</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $totalCollectionRevenue = array_sum(array_column($byCollection ?? [], 'revenue'));
              foreach (($byCollection ?? []) as $r):
                $share = $totalCollectionRevenue > 0 ? ($r['revenue'] / $totalCollectionRevenue) * 100 : 0;
              ?>
                <tr>
                  <td>
                    <span class="fw-semibold"><?= htmlspecialchars($r['collection']) ?></span>
                  </td>
                  <td class="text-end">
                    <span class="badge bg-info"><?= (int)$r['qty'] ?></span>
                  </td>
                  <td class="text-end fw-semibold">₱<?= number_format((float)$r['revenue'],2) ?></td>
                  <td class="text-end">
                    <div class="d-flex align-items-center justify-content-end">
                      <div class="progress me-2" style="width: 50px; height: 8px;">
                        <div class="progress-bar" style="width: <?= $share ?>%"></div>
                      </div>
                      <small class="text-muted"><?= number_format($share, 1) ?>%</small>
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

  <!-- Top Products -->
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="bi bi-star me-2"></i>Top Performing Products
          </h5>
          <span class="badge bg-secondary">Top 20</span>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Rank</th>
                <th>Product</th>
                <th class="text-end">Qty Sold</th>
                <th class="text-end">Revenue</th>
                <th class="text-end">Avg Price</th>
                <th class="text-end">Performance</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $maxRevenue = !empty($topProducts) ? max(array_column($topProducts, 'revenue')) : 1;
              foreach (($topProducts ?? []) as $index => $r):
                $avgPrice = $r['qty'] > 0 ? $r['revenue'] / $r['qty'] : 0;
                $performance = $maxRevenue > 0 ? ($r['revenue'] / $maxRevenue) * 100 : 0;
                $rank = $index + 1;
              ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <?php if ($rank <= 3): ?>
                        <i class="bi bi-trophy-fill text-warning me-2"></i>
                      <?php endif; ?>
                      <span class="fw-bold">#<?= $rank ?></span>
                    </div>
                  </td>
                  <td>
                    <span class="fw-semibold"><?= htmlspecialchars($r['title']) ?></span>
                  </td>
                  <td class="text-end">
                    <span class="badge bg-success"><?= (int)$r['qty'] ?></span>
                  </td>
                  <td class="text-end fw-semibold">₱<?= number_format((float)$r['revenue'],2) ?></td>
                  <td class="text-end text-muted">₱<?= number_format($avgPrice, 2) ?></td>
                  <td class="text-end">
                    <div class="d-flex align-items-center justify-content-end">
                      <div class="progress me-2" style="width: 60px; height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?= $performance ?>%"></div>
                      </div>
                      <small class="text-muted"><?= number_format($performance, 1) ?>%</small>
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

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Prepare data for charts
  const salesData = <?= json_encode($salesByDay ?? []) ?>;
  const labels = salesData.map(item => {
    const date = new Date(item.d);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  });
  const revenueData = salesData.map(item => parseFloat(item.revenue));
  const ordersData = salesData.map(item => parseInt(item.orders));

  // Chart configuration
  const ctx = document.getElementById('salesChart').getContext('2d');
  let currentChart = null;

  function createChart(type) {
    if (currentChart) {
      currentChart.destroy();
    }

    const data = type === 'revenue' ? revenueData : ordersData;
    const label = type === 'revenue' ? 'Revenue (₱)' : 'Orders';
    const color = type === 'revenue' ? 'rgb(54, 162, 235)' : 'rgb(75, 192, 192)';

    currentChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: label,
          data: data,
          borderColor: color,
          backgroundColor: color + '20',
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: color,
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 6,
          pointHoverRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: color,
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: false,
            callbacks: {
              label: function(context) {
                if (type === 'revenue') {
                  return 'Revenue: ₱' + context.parsed.y.toLocaleString();
                } else {
                  return 'Orders: ' + context.parsed.y.toLocaleString();
                }
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false
            },
            ticks: {
              color: '#6c757d'
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.1)'
            },
            ticks: {
              color: '#6c757d',
              callback: function(value) {
                if (type === 'revenue') {
                  return '₱' + value.toLocaleString();
                } else {
                  return value.toLocaleString();
                }
              }
            }
          }
        },
        interaction: {
          intersect: false,
          mode: 'index'
        }
      }
    });
  }

  // Initialize chart with revenue data
  createChart('revenue');

  // Chart type toggle
  document.querySelectorAll('input[name="chartType"]').forEach(radio => {
    radio.addEventListener('change', function() {
      createChart(this.id);
    });
  });

  // Date range quick selectors
  window.setDateRange = function(range) {
    const today = new Date();
    const fromInput = document.querySelector('input[name="from"]');
    const toInput = document.querySelector('input[name="to"]');

    let fromDate, toDate;

    switch(range) {
      case 'today':
        fromDate = toDate = today;
        break;
      case 'week':
        fromDate = new Date(today);
        fromDate.setDate(today.getDate() - 7);
        toDate = today;
        break;
      case 'month':
        fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
        toDate = today;
        break;
    }

    fromInput.value = fromDate.toISOString().split('T')[0];
    toInput.value = toDate.toISOString().split('T')[0];

    // Auto-submit form
    fromInput.closest('form').submit();
  };

  // Export functionality
  window.exportReport = function() {
    const dateRange = `${document.querySelector('input[name="from"]').value} to ${document.querySelector('input[name="to"]').value}`;
    alert(`Export functionality would generate a CSV/PDF report for ${dateRange}`);
  };

  // Print functionality
  window.printReport = function() {
    window.print();
  };

  // Add print styles
  const printStyles = `
    @media print {
      .btn, .card-header .btn-group { display: none !important; }
      .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
      .card-body { padding: 1rem !important; }
      body { font-size: 12px; }
      .table { font-size: 11px; }
    }
  `;

  const styleSheet = document.createElement('style');
  styleSheet.textContent = printStyles;
  document.head.appendChild(styleSheet);
});
</script>

<style>
.card {
  transition: all 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.progress {
  background-color: rgba(0,0,0,0.1);
}

.table-hover tbody tr:hover {
  background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.badge {
  font-size: 0.75em;
}

@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }

  .card-title {
    font-size: 1rem;
  }

  .btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
}
</style>
<style>
@media (max-width: 576px) {
  .row.g-4 .card .rounded-circle { width: 44px !important; height: 44px !important; }
  .row.g-4 .card h3 { font-size: 1.25rem; }
}
</style>


