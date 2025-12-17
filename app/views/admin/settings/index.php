<div class="tab-pane fade <?= $activeTab==='shipping'?'show active':'' ?>" id="tab-shipping" role="tabpanel">
  <form method="post" action="/admin/settings" class="row g-3">
    <?= csrf_field() ?>
    <input type="hidden" name="scope" value="shipping">

    <!-- Delivery Methods Settings -->
    <div class="col-12">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0">
            <i class="bi bi-truck me-2"></i>Delivery Methods
          </h5>
        </div>
        <div class="card-body">
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Enable or disable delivery methods. Disabled methods will be hidden from customers during checkout.
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="shipping_enable_cod" id="shipping_enable_cod" <?= ($settings['shipping_enable_cod'] ?? '1') == '1' ? 'checked' : '' ?>>
                <label class="form-check-label d-flex align-items-start" for="shipping_enable_cod">
                  <div>
                    <strong>Cash on Delivery (COD)</strong>
                    <small class="d-block text-muted">Customers pay when they receive their order</small>
                  </div>
                </label>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="shipping_enable_pickup" id="shipping_enable_pickup" <?= ($settings['shipping_enable_pickup'] ?? '1') == '1' ? 'checked' : '' ?>>
                <label class="form-check-label d-flex align-items-start" for="shipping_enable_pickup">
                  <div>
                    <strong>Store Pickup</strong>
                    <small class="d-block text-muted">Customers pick up from your store location</small>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Shipping Fees -->
    <div class="col-12">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0">
            <i class="bi bi-currency-dollar me-2"></i>Shipping Fees
          </h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <label class="form-label">General Shipping Fee (COD)</label>
              <input class="form-control" type="number" step="0.01" name="shipping_fee_cod" value="<?= htmlspecialchars($settings['shipping_fee_cod'] ?? '0.00') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">General Shipping Fee (Pickup)</label>
              <input class="form-control" type="number" step="0.01" name="shipping_fee_pickup" value="<?= htmlspecialchars($settings['shipping_fee_pickup'] ?? '0.00') ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- City Restrictions -->
    <div class="col-12">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0">
            <i class="bi bi-geo-alt me-2"></i>City Restrictions
          </h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <label class="form-label">COD available only in these cities</label>
              <textarea class="form-control" name="cod_city_whitelist" rows="3" placeholder="One city per line (leave empty to allow all)"><?= htmlspecialchars($settings['cod_city_whitelist'] ?? '') ?></textarea>
              <small class="text-muted">If not empty, Cash on Delivery will be available only when the customer's city matches one of these entries (case-insensitive).</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Pickup available only in these cities</label>
              <textarea class="form-control" name="pickup_city_whitelist" rows="3" placeholder="One city per line (leave empty to allow all)"><?= htmlspecialchars($settings['pickup_city_whitelist'] ?? '') ?></textarea>
              <small class="text-muted">If not empty, Store Pickup will be available only when the customer's city matches one of these entries. Otherwise it will be hidden.</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Save Button -->
    <div class="col-12">
      <div class="alert alert-info small">City-specific COD fees below override the General COD fee. Any city not listed will use the General COD fee.</div>
      <button class="btn btn-primary btn-lg">
        <i class="bi bi-check-circle me-2"></i>Save All Shipping Settings
      </button>
    </div>

  </form>

  <!-- Delivery Fees per City -->
  <div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
      <strong>Delivery Fees per City</strong>
    </div>
    <div class="card-body">
      <p class="text-muted small mb-2">City-specific COD fees override the General COD fee. Any city not listed will use the General COD fee.</p>

      <div class="mb-3">
        <form id="feeForm" method="post" action="/admin/settings/fees" class="row g-2">
          <?= csrf_field() ?>
          <div class="col-md-7"><input class="form-control" name="city" placeholder="City (e.g., Aparri City)"></div>
          <div class="col-md-3"><input class="form-control" name="fee" type="number" step="0.01" placeholder="Fee"></div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Add</button>
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>City</th>
              <th>Fee</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($fees as $fee): ?>
            <tr>
              <td><?= htmlspecialchars($fee['city']) ?></td>
              <td>₱<?= number_format($fee['fee'], 2) ?></td>
              <td class="text-end">
                <form method="post" action="/admin/settings/fees/<?= $fee['id'] ?>/delete" class="d-inline">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this city fee?')">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (empty($fees)): ?>
        <p class="text-muted text-center py-3">No city-specific fees added yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>