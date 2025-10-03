<?php use function App\Core\csrf_field; ?>

<!-- Order Detail Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Order #<?= (int)$order['id'] ?></h1>
    <p class="text-muted mb-0">
      <i class="bi bi-calendar me-2"></i><?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="/admin/orders">
      <i class="bi bi-arrow-left me-2"></i>Back to Orders
    </a>
    <a class="btn btn-outline-info" href="/admin/orders/<?= (int)$order['id'] ?>/invoice">
      <i class="bi bi-file-earmark-text me-2"></i>View Invoice
    </a>
    <div class="btn-group">
      <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="bi bi-gear me-2"></i>Actions
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header">Order Actions</h6></li>
        <li>
          <form method="post" action="/admin/orders/<?= (int)$order['id'] ?>/fulfill" class="dropdown-item p-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-link text-decoration-none text-start w-100 p-3">
              <i class="bi bi-check-circle me-2"></i>Mark as Fulfilled
            </button>
          </form>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <form method="post" action="/admin/orders/<?= (int)$order['id'] ?>/delete" class="dropdown-item p-0" onsubmit="return confirm('Delete this order?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-link text-decoration-none text-danger text-start w-100 p-3">
              <i class="bi bi-trash me-2"></i>Delete Order
            </button>
          </form>
        </li>
      </ul>
    </div>
  </div>
</div>
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

