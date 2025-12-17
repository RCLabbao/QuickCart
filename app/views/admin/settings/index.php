<?php use function App\Core\csrf_field; ?>
<?php use function App\Core\setting; ?>
<?php if (isset($flash)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<ul class="nav nav-tabs" role="tablist">
  <li class="nav-item" role="presentation"><a class="nav-link <?= $activeTab==='general'?'active':'' ?>" href="/admin/settings?tab=general" data-bs-toggle="tab" data-bs-target="#tab-general" role="tab">General</a></li>
  <li class="nav-item" role="presentation"><a class="nav-link <?= $activeTab==='checkout'?'active':'' ?>" href="/admin/settings?tab=checkout" data-bs-toggle="tab" data-bs-target="#tab-checkout" role="tab">Checkout</a></li>
  <li class="nav-item" role="presentation"><a class="nav-link <?= $activeTab==='shipping'?'active':'' ?>" href="/admin/settings?tab=shipping" data-bs-toggle="tab" data-bs-target="#tab-shipping" role="tab">Shipping</a></li>
  <li class="nav-item" role="presentation"><a class="nav-link <?= $activeTab==='email'?'active':'' ?>" href="/admin/settings?tab=email" data-bs-toggle="tab" data-bs-target="#tab-email" role="tab">Email</a></li>
  <li class="nav-item" role="presentation"><a class="nav-link <?= $activeTab==='catalog'?'active':'' ?>" href="/admin/settings?tab=catalog" data-bs-toggle="tab" data-bs-target="#tab-catalog" role="tab">Catalog</a></li>
</ul>
<div class="tab-content mt-3">

<div class="tab-pane fade <?= $activeTab==='general'?'show active':'' ?>" id="tab-general" role="tabpanel">
<form method="post" action="/admin/settings" class="row g-3">
  <?= csrf_field() ?>
  <input type="hidden" name="scope" value="general">
  <div class="col-md-6">
    <label class="form-label">Store Name</label>
    <input class="form-control" name="store_name" value="<?= htmlspecialchars($settings['store_name'] ?? 'QuickCart') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">Currency</label>
    <input class="form-control" name="currency" value="<?= htmlspecialchars($settings['currency'] ?? 'PHP') ?>">
  </div>
  <div class="col-12">
    <label class="form-label">Pickup Location</label>
    <input class="form-control" name="pickup_location" value="<?= htmlspecialchars($settings['pickup_location'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">Brand Color</label>
    <input class="form-control form-control-color" type="color" name="brand_color" value="<?= htmlspecialchars($settings['brand_color'] ?? '#212529') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">Today's Orders Cutoff Time</label>
    <input class="form-control" type="time" name="today_cutoff" value="<?= htmlspecialchars($settings['today_cutoff'] ?? '00:00') ?>">
    <small class="text-muted">Orders placed after this time will appear in the next day's "Today" view.</small>
  </div>
  <div class="col-12">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="debug" id="debug" <?= !empty($settings['debug']) && $settings['debug'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="debug">Enable Debug Mode</label>
    </div>
  </div>
  <div class="col-12">
    <button class="btn btn-primary">Save General Settings</button>
  </div>
</form>
</div>

<div class="tab-pane fade <?= $activeTab==='checkout'?'show active':'' ?>" id="tab-checkout" role="tabpanel">
<form method="post" action="/admin/settings" class="row g-3">
  <?= csrf_field() ?>
  <input type="hidden" name="scope" value="checkout">
  <div class="col-12">
    <h5 class="mb-3">Required Checkout Fields</h5>
    <p class="text-muted mb-3">Toggle which address fields are required during checkout.</p>
  </div>
  <div class="col-md-6">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="checkout_enable_phone" id="checkout_enable_phone" <?= !empty($settings['checkout_enable_phone']) && $settings['checkout_enable_phone'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="checkout_enable_phone">Phone Number</label>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="checkout_enable_region" id="checkout_enable_region" <?= !empty($settings['checkout_enable_region']) && $settings['checkout_enable_region'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="checkout_enable_region">Region</label>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="checkout_enable_province" id="checkout_enable_province" <?= !empty($settings['checkout_enable_province']) && $settings['checkout_enable_province'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="checkout_enable_province">Province</label>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="checkout_enable_city" id="checkout_enable_city" <?= !empty($settings['checkout_enable_city']) && $settings['checkout_enable_city'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="checkout_enable_city">City/Municipality</label>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="checkout_enable_barangay" id="checkout_enable_barangay" <?= !empty($settings['checkout_enable_barangay']) && $settings['checkout_enable_barangay'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="checkout_enable_barangay">Barangay</label>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="checkout_enable_street" id="checkout_enable_street" <?= !empty($settings['checkout_enable_street']) && $settings['checkout_enable_street'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="checkout_enable_street">Street Address</label>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="checkout_enable_postal" id="checkout_enable_postal" <?= !empty($settings['checkout_enable_postal']) && $settings['checkout_enable_postal'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="checkout_enable_postal">Postal Code</label>
    </div>
  </div>
  <div class="col-12">
    <button class="btn btn-primary">Save Checkout Settings</button>
  </div>
</form>
</div>

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

<div class="tab-pane fade <?= $activeTab==='email'?'show active':'' ?>" id="tab-email" role="tabpanel">
<form method="post" action="/admin/settings" class="row g-3">
  <?= csrf_field() ?>
  <input type="hidden" name="scope" value="email">
  <div class="col-12">
    <div class="form-check form-switch mb-3">
      <input class="form-check-input" type="checkbox" name="smtp_enabled" id="smtp_enabled" <?= !empty($settings['smtp_enabled']) && $settings['smtp_enabled'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="smtp_enabled">Enable SMTP Email</label>
    </div>
  </div>
  <div class="col-md-6">
    <label class="form-label">SMTP Host</label>
    <input class="form-control" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">SMTP Port</label>
    <input class="form-control" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">SMTP Username</label>
    <input class="form-control" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">SMTP Password</label>
    <input class="form-control" type="password" name="smtp_pass" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">SMTP Secure</label>
    <select class="form-control" name="smtp_secure">
      <option value="tls" <?= ($settings['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
      <option value="ssl" <?= ($settings['smtp_secure'] ?? 'tls') === 'ssl' ? 'selected' : '' ?>>SSL</option>
      <option value="none" <?= ($settings['smtp_secure'] ?? 'tls') === 'none' ? 'selected' : '' ?>>None</option>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">From Email</label>
    <input class="form-control" type="email" name="smtp_from_email" value="<?= htmlspecialchars($settings['smtp_from_email'] ?? '') ?>">
  </div>
  <div class="col-12">
    <label class="form-label">Order Email Subject</label>
    <input class="form-control" name="email_order_subject" value="<?= htmlspecialchars($settings['email_order_subject'] ?? 'Your order {{order_id}} at {{store_name}}') ?>">
  </div>
  <div class="col-12">
    <label class="form-label">Order Email Template</label>
    <textarea class="form-control" name="email_order_template" rows="10"><?= htmlspecialchars($settings['email_order_template'] ?? '') ?></textarea>
    <small class="text-muted">Available variables: {{order_id}}, {{store_name}}, {{customer_name}}, {{total}}, {{order_items_html}}</small>
  </div>
  <div class="col-12">
    <button class="btn btn-primary">Save Email Settings</button>
  </div>
</form>
</div>

<div class="tab-pane fade <?= $activeTab==='catalog'?'show active':'' ?>" id="tab-catalog" role="tabpanel">
<form method="post" action="/admin/settings" class="row g-3">
  <?= csrf_field() ?>
  <input type="hidden" name="scope" value="catalog">

  <?php
  $hiddenParts = preg_split('/[\s,]+/', trim((string)($settings['hidden_collections'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
  $hiddenSet = array_flip(array_map('strval', $hiddenParts)); ?>

  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom"><strong>Collections Visibility</strong></div>
      <div class="card-body" style="max-height:280px; overflow:auto;">
        <?php if (empty($collections)): ?>
          <p class="text-muted">No collections found. Create collections first in <a href="/admin/collections">Collections</a>.</p>
        <?php else: ?>
        <div class="row g-2">
          <?php foreach ($collections as $c): $id=(string)$c['id']; $slug=(string)$c['slug'];
            $checked = isset($hiddenSet[$id]) || isset($hiddenSet[$slug]); ?>
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input coll-hide" type="checkbox" data-slug="<?= htmlspecialchars($slug) ?>" id="coll<?= (int)$c['id'] ?>" <?= $checked?'checked':'' ?>>
                <label class="form-check-label" for="coll<?= (int)$c['id'] ?>">
                  <?= htmlspecialchars($c['title']) ?> <small class="text-muted">(<?= htmlspecialchars($slug) ?>)</small>
                </label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="form-text mt-2">Checked collections will be hidden sitewide (from home, collections page, search, etc.).</div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <label class="form-label fw-semibold">Hidden Collections (IDs or slugs)</label>
    <textarea class="form-control" id="hiddenCollections" name="hidden_collections" rows="3" placeholder="e.g. 3, clearance, archived&#10;One per line or comma-separated"><?php echo htmlspecialchars($settings['hidden_collections'] ?? ''); ?></textarea>
    <small class="text-muted">You can paste slugs/IDs directly, or use the checkboxes above.</small>
  </div>

  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom"><strong>Product Visibility Rules</strong></div>
      <div class="card-body">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="hide_zero_price" id="hide_zero_price" <?= !empty($settings['hide_zero_price']) && $settings['hide_zero_price']=='1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="hide_zero_price">Hide products with 0.00 price (unless on sale with compare price)</label>
        </div>
        <small class="text-muted d-block mt-1">When enabled, products with price = 0.00 will be completely hidden from listings, search, and collections unless they have a valid sale price with a compare/original price greater than 0.</small>
      </div>
    </div>
  </div>

  <div class="col-12">
    <button class="btn btn-primary">Save Catalog Settings</button>
  </div>
</form>
  <script>
    (function(){
      const textarea = document.getElementById('hiddenCollections');
      const boxes = Array.from(document.querySelectorAll('.coll-hide'));
      function syncFromBoxes(){
        const slugs = boxes.filter(b=>b.checked).map(b=>b.getAttribute('data-slug')).filter(Boolean);
        textarea.value = slugs.join(', ');
      }
      boxes.forEach(b=>b.addEventListener('change', syncFromBoxes));
    })();
  </script>
</div>

</div>