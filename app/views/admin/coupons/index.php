<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0">Coupons</h1>
  <a href="/admin/coupons/create" class="btn btn-dark">New Coupon</a>
</div>
<table class="table">
  <thead><tr><th>Code</th><th>Kind</th><th>Amount</th><th>Min Spend</th><th>Expires</th><th>Active</th><th></th></tr></thead>
  <tbody>
    <?php foreach (($coupons ?? []) as $c): ?>
    <tr>
      <td><?= htmlspecialchars($c['code']) ?></td>
      <td><?= htmlspecialchars($c['kind']) ?></td>
      <td><?= number_format((float)$c['amount'],2) ?></td>
      <td><?= $c['min_spend']!==null?number_format((float)$c['min_spend'],2):'—' ?></td>
      <td><?= htmlspecialchars($c['expires_at'] ?? '—') ?></td>
      <td><?= !empty($c['active'])?'Yes':'No' ?></td>
      <td class="text-end">
        <a class="btn btn-sm btn-outline-secondary" href="/admin/coupons/<?= (int)$c['id'] ?>/edit">Edit</a>
        <form class="d-inline" method="post" action="/admin/coupons/<?= (int)$c['id'] ?>/delete" onsubmit="return confirm('Delete coupon?')">
          <?= App\Core\csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

