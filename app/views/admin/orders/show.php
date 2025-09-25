<?php use function App\Core\csrf_field; ?>
<?php include BASE_PATH . '/app/views/admin/_nav.php'; ?>
<h1 class="h4 mb-3">Order #<?= (int)$order['id'] ?></h1>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">Items</div>
      <div class="card-body p-0">
        <table class="table m-0">
          <thead><tr><th>Product</th><th>Qty</th><th class="text-end">Unit</th><th class="text-end">Line</th></tr></thead>
          <tbody>
          <?php $subtotal=0; foreach ($items as $it): $line=$it['unit_price']*$it['quantity']; $subtotal+=$line; ?>
            <tr>
              <td><?= htmlspecialchars($it['title']) ?></td>
              <td><?= (int)$it['quantity'] ?></td>
              <td class="text-end"><?= number_format((float)$it['unit_price'],2) ?></td>
              <td class="text-end"><?= number_format((float)$line,2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-end gap-4">
        <div><span class="text-muted">Subtotal:</span> <strong><?= number_format((float)$subtotal,2) ?></strong></div>
        <?php if (!empty($order['discount'])): ?>
        <div><span class="text-muted">Discount:</span> <strong>-<?= number_format((float)$order['discount'],2) ?></strong></div>
        <?php endif; ?>
        <div><span class="text-muted">Shipping:</span> <strong><?= number_format((float)$order['shipping_fee'],2) ?></strong></div>
        <div><span class="text-muted">Total:</span> <strong><?= number_format((float)$order['total'],2) ?></strong></div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header">Status</div>
      <div class="card-body">
        <form method="post" action="/admin/orders/<?= (int)$order['id'] ?>/status" class="d-flex gap-2">
          <?= csrf_field() ?>
          <select class="form-select" name="status">
            <?php $st = $order['status']; foreach (['pending','processing','shipped','completed','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $st===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-dark">Update</button>
        </form>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <form method="post" action="/admin/orders/<?= (int)$order['id'] ?>/fulfill">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-dark">Mark as Fulfilled</button>
          </form>
          <form method="post" action="/admin/orders/<?= (int)$order['id'] ?>/refund" onsubmit="return confirm('Refund this order?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-secondary">Refund</button>
          </form>
          <a class="btn btn-sm btn-outline-secondary" href="/admin/orders/<?= (int)$order['id'] ?>/invoice" target="_blank">Print Invoice</a>
          <form method="post" action="/admin/orders/<?= (int)$order['id'] ?>/delete" onsubmit="return confirm('Delete this order?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </div>

      </div>
    </div>
    <div class="card mt-3">
      <div class="card-header">Notes</div>
      <div class="card-body">
        <form method="post" action="/admin/orders/<?= (int)$order['id'] ?>/note" class="d-flex gap-2">
          <?= csrf_field() ?>
          <textarea class="form-control" name="note" rows="2" placeholder="Internal notes..."><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
          <button class="btn btn-dark">Save</button>
        </form>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-header">Timeline</div>
      <div class="card-body">
        <?php if (!empty($events)): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($events as $ev): ?>
              <li class="list-group-item small d-flex justify-content-between">
                <span><?= htmlspecialchars($ev['message']) ?><?php if(!empty($ev['user_name'])): ?> · <em><?= htmlspecialchars($ev['user_name']) ?></em><?php endif; ?></span>
                <span class="text-muted"><?= htmlspecialchars($ev['created_at']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-muted">No events yet.</div>
        <?php endif; ?>
      </div>
    </div>


    <div class="card">
      <div class="card-header">Customer</div>
      <div class="card-body">
        <div><strong>Email:</strong> <?= htmlspecialchars($order['email'] ?? 'Guest') ?></div>
        <div><strong>Method:</strong> <?= htmlspecialchars(strtoupper($order['shipping_method'])) ?></div>
      </div>
    </div>
    <?php if ($order['shipping_method']==='cod' && $address): ?>
    <div class="card mt-3">
      <div class="card-header">Shipping Address</div>
      <div class="card-body">
        <div><?= htmlspecialchars($address['name']) ?> (<?= htmlspecialchars($address['phone']) ?>)</div>
        <div><?= htmlspecialchars($address['street']) ?></div>
        <div><?= htmlspecialchars($address['barangay']) ?>, <?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['province']) ?></div>
        <div><?= htmlspecialchars($address['region']) ?> <?= htmlspecialchars($address['postal_code']) ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

