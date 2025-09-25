<?php include BASE_PATH . '/app/views/admin/_nav.php'; ?>
<h1 class="h4">Invoice #<?= (int)$order['id'] ?></h1>
<div class="mb-2 text-muted">Created: <?= htmlspecialchars($order['created_at']) ?> · Status: <?= htmlspecialchars($order['status']) ?></div>
<table class="table">
  <thead><tr><th>Item</th><th class="text-end">Unit</th><th class="text-end">Qty</th><th class="text-end">Line</th></tr></thead>
  <tbody>
  <?php $subtotal=0; foreach (($items ?? []) as $it): $line=(float)$it['unit_price']*(int)$it['quantity']; $subtotal+=$line; ?>
    <tr>
      <td><?= htmlspecialchars($it['title']) ?></td>
      <td class="text-end"><?= number_format((float)$it['unit_price'],2) ?></td>
      <td class="text-end"><?= (int)$it['quantity'] ?></td>
      <td class="text-end"><?= number_format($line,2) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<div class="d-flex flex-column align-items-end gap-1">
  <div>Subtotal: <strong><?= number_format($subtotal,2) ?></strong></div>
  <div>Discount: <strong><?= number_format((float)($order['discount'] ?? 0),2) ?></strong></div>
  <div>Shipping: <strong><?= number_format((float)($order['shipping_fee']),2) ?></strong></div>
  <div>Total: <strong><?= number_format((float)($order['total']),2) ?></strong></div>
</div>

