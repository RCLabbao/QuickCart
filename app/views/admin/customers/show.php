<?php include BASE_PATH . '/app/views/admin/_nav.php'; ?>
<h1 class="h4 mb-1">Customer</h1>
<div class="text-muted mb-3"><?= htmlspecialchars($email) ?></div>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <div>Orders: <strong><?= (int)($stats['orders'] ?? 0) ?></strong></div>
      <div>Total spent: <strong><?= number_format((float)($stats['spent'] ?? 0),2) ?></strong></div>
      <div>Last order: <strong><?= htmlspecialchars($stats['last_order'] ?? '—') ?></strong></div>
    </div></div>
  </div>
</div>
<div class="card mt-3">
  <div class="card-header">Orders</div>
  <div class="card-body p-0">
    <table class="table m-0">
      <thead><tr><th>ID</th><th>Status</th><th>Created</th><th class="text-end">Total</th><th></th></tr></thead>
      <tbody>
      <?php foreach (($orders ?? []) as $o): ?>
        <tr>
          <td>#<?= (int)$o['id'] ?></td>
          <td><?= htmlspecialchars($o['status']) ?></td>
          <td><?= htmlspecialchars($o['created_at']) ?></td>
          <td class="text-end"><?= number_format((float)$o['total'],2) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="/admin/orders/<?= (int)$o['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

