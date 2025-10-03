<?php use function App\Core\e; ?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="d-flex align-items-center mb-4">
        <i class="bi bi-receipt fs-3 me-2 text-primary"></i>
        <h1 class="h4 mb-0">Order #<?= (int)$order['id'] ?> Status</h1>
      </div>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <h5 class="mb-3">Items</h5>
              <div class="table-responsive">
                <table class="table align-middle">
                  <thead>
                    <tr><th>Product</th><th class="text-center" style="width:120px">Qty</th><th class="text-end" style="width:140px">Price</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach (($items ?? []) as $it): ?>
                    <tr>
                      <td><?= e($it['title']) ?></td>
                      <td class="text-center"><?= (int)$it['quantity'] ?></td>
                      <td class="text-end">₱<?= number_format((float)$it['unit_price'] * (int)$it['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; if (empty($items)): ?>
                    <tr><td colspan="3" class="text-muted">No items</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2"><span class="text-muted">Status</span><span class="fw-semibold text-capitalize"><?= e($order['status']) ?></span></div>
              <div class="d-flex justify-content-between mb-2"><span class="text-muted">Placed</span><span><?= e($order['created_at']) ?></span></div>
              <hr>
              <div class="d-flex justify-content-between mb-1"><span class="text-muted">Subtotal</span><span>₱<?= number_format((float)$order['subtotal'],2) ?></span></div>
              <div class="d-flex justify-content-between mb-1"><span class="text-muted">Shipping</span><span>₱<?= number_format((float)$order['shipping_fee'],2) ?></span></div>
              <div class="d-flex justify-content-between mt-2"><span class="fw-semibold">Total</span><span class="fw-bold">₱<?= number_format((float)$order['total'],2) ?></span></div>
              <hr>
              <div class="small text-muted">Updates will be sent to <strong><?= e($order['email']) ?></strong></div>
            </div>
          </div>

          <?php if (!empty($addr)): ?>
          <div class="card border-0 shadow-sm mt-3">
            <div class="card-body">
              <h6 class="mb-2">Delivery Address</h6>
              <div class="small">
                <div><?= e($addr['name'] ?? '') ?></div>
                <div><?= e($addr['phone'] ?? '') ?></div>
                <div><?= e($addr['street'] ?? '') ?></div>
                <div><?= e($addr['barangay'] ?? '') ?> <?= e($addr['city'] ?? '') ?> <?= e($addr['province'] ?? '') ?></div>
                <div><?= e($addr['region'] ?? '') ?> <?= e($addr['postal_code'] ?? '') ?></div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-4">
        <a class="btn btn-outline-secondary" href="/products"><i class="bi bi-arrow-left me-1"></i> Continue Shopping</a>
      </div>
    </div>
  </div>
</div>

