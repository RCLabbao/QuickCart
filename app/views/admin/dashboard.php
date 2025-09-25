<?php use function App\Core\e; ?>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-3"><div class="text-muted">Orders</div><div class="fs-3 fw-bold"><?= (int)$orders ?></div></div>
  </div>
  <div class="col-md-4">
    <div class="card p-3"><div class="text-muted">Revenue</div><div class="fs-3 fw-bold">₱<?= number_format((float)$revenue,2) ?></div></div>
  </div>
  <div class="col-md-4">
    <div class="card p-3"><div class="text-muted">Products</div><div class="fs-3 fw-bold"><?= (int)$products ?></div></div>
  </div>
</div>
<canvas id="salesChart" height="120" class="mt-4"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('salesChart');
  const lbls = <?= json_encode($sales_labels ?? []) ?>;
  const vals = <?= json_encode($sales_values ?? []) ?>;
  new Chart(ctx, { type:'line', data:{ labels: lbls, datasets:[{ label:'Sales (₱)', data: vals, borderColor:'#212529', backgroundColor:'rgba(33,37,41,.1)', tension:.3 }] }, options:{ scales:{ y:{ beginAtZero:true } } } });
</script>
<div class="row g-3 mt-1">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">Low stock (≤3)</div>
      <ul class="list-group list-group-flush">
        <?php foreach (($low_stock ?? []) as $p): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-truncate" style="max-width:70%">#<?= (int)$p['id'] ?> — <?= e($p['title']) ?></span>
          <span class="badge bg-warning text-dark"><?= (int)$p['stock'] ?></span>
        </li>
        <?php endforeach; if (empty($low_stock)): ?>
        <li class="list-group-item text-muted">All good!</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">Top sellers (30d)</div>
      <ul class="list-group list-group-flush">
        <?php foreach (($top_sellers ?? []) as $t): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span class="text-truncate" style="max-width:70%"><?= e($t['title']) ?></span>
          <span class="badge bg-dark"><?= (int)$t['qty'] ?></span>
        </li>
        <?php endforeach; if (empty($top_sellers)): ?>
        <li class="list-group-item text-muted">No data yet</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>


