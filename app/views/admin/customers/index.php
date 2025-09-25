
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0">Customers</h1>
  <a class="btn btn-sm btn-outline-secondary" href="/admin/customers/export">Export CSV</a>
</div>
<table class="table align-middle">
  <thead class="table-light"><tr><th>Email</th><th class="text-end">Orders</th><th class="text-end">Total Spent</th><th></th></tr></thead>
  <tbody>
  <?php foreach (($customers ?? []) as $c): ?>
    <tr>
      <td><?= htmlspecialchars($c['email']) ?></td>
      <td class="text-end"><?= (int)$c['orders'] ?></td>
      <td class="text-end">₱<?= number_format((float)$c['spent'],2) ?></td>
      <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="/admin/customers/view?email=<?= urlencode($c['email']) ?>">View</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

