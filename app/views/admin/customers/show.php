<!-- Customer Details Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h3 mb-1">Customer</h1>
    <div class="text-muted"><?= htmlspecialchars($email) ?></div>
  </div>
  <div>
    <a class="btn btn-outline-secondary" href="/admin/customers"><i class="bi bi-arrow-left me-2"></i>Back to Customers</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm"><div class="card-body">
      <div class="d-flex justify-content-between"><span>Orders</span> <strong><?= (int)($stats['orders'] ?? 0) ?></strong></div>
      <div class="d-flex justify-content-between"><span>Total Spent</span> <strong>₱<?= number_format((float)($stats['spent'] ?? 0),2) ?></strong></div>
      <div class="d-flex justify-content-between"><span>Last order</span> <strong><?= htmlspecialchars($stats['last_order'] ?? '—') ?></strong></div>
    </div></div>
  </div>
  <div class="col-md-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom"><strong>Edit Customer</strong></div>
      <div class="card-body">
        <form method="post" action="/admin/customers/profile" class="row g-3">
          <?= App\Core\csrf_field() ?>
          <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
          <div class="col-md-6">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" placeholder="Customer name">
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input class="form-control" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="09XXXXXXXXX">
          </div>
          <div class="col-12">
            <button class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="card mt-3 border-0 shadow-sm">
  <div class="card-header bg-white"><strong>Orders</strong></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table m-0 align-middle">
        <thead class="table-light"><tr><th>ID</th><th>Status</th><th>Created</th><th class="text-end">Total</th><th></th></tr></thead>
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

