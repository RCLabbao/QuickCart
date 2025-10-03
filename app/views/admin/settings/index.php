<?php use function App\Core\csrf_field; $activeTab = $activeTab ?? ($_GET['tab'] ?? 'general'); $brand = htmlspecialchars($settings['brand_color'] ?? '#212529'); ?>
<style>
  .nav-tabs .nav-link:not(.active){ color: <?= $brand ?> !important; }
  .nav-tabs .nav-link.active{ color: #000 !important; }
</style>
<h1 class="h4 mb-3">Settings</h1>
<?php if (!empty($flash ?? null)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <?= htmlspecialchars($flash) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<ul class="nav nav-tabs" role="tablist">
  <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab==='general'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">General</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab==='checkout'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#tab-checkout" type="button" role="tab">Checkout</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab==='shipping'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#tab-shipping" type="button" role="tab">Shipping</button></li>
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
  <div class="col-12">
    <button class="btn btn-dark">Save General</button>
  </div>
</form>
</div>
<div class="tab-pane fade <?= $activeTab==='checkout'?'show active':'' ?>" id="tab-checkout" role="tabpanel">
<form method="post" action="/admin/settings" class="mt-2">
  <?= csrf_field() ?>
  <input type="hidden" name="scope" value="checkout">

<!-- Checkout Fields -->
<div class="card border-0 shadow-sm mt-4">
  <div class="card-header bg-white border-bottom"><strong>Checkout Fields</strong></div>
  <div class="card-body">
    <?php $on = fn($k)=> (isset($settings[$k]) ? (bool)$settings[$k] : true); ?>
    <div class="row g-2">
      <div class="col-6">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="checkout_enable_phone" id="f_phone" <?= $on('checkout_enable_phone')?'checked':'' ?>>
          <label class="form-check-label" for="f_phone">Phone</label>
        </div>
      </div>
      <div class="col-6">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="checkout_enable_postal" id="f_postal" <?= $on('checkout_enable_postal')?'checked':'' ?>>
          <label class="form-check-label" for="f_postal">Postal Code</label>
        </div>
      </div>
    </div>
    <div class="row g-2 mt-1">
      <div class="col-6">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="checkout_enable_region" id="f_region" <?= $on('checkout_enable_region')?'checked':'' ?>>
          <label class="form-check-label" for="f_region">Region</label>
        </div>
      </div>
      <div class="col-6">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="checkout_enable_province" id="f_province" <?= $on('checkout_enable_province')?'checked':'' ?>>
          <label class="form-check-label" for="f_province">Province</label>
        </div>
      </div>
    </div>
    <div class="row g-2 mt-1">
      <div class="col-6">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="checkout_enable_city" id="f_city" <?= $on('checkout_enable_city')?'checked':'' ?>>
          <label class="form-check-label" for="f_city">City</label>
        </div>
      </div>
      <div class="col-6">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="checkout_enable_barangay" id="f_barangay" <?= $on('checkout_enable_barangay')?'checked':'' ?>>
          <label class="form-check-label" for="f_barangay">Barangay</label>
        </div>
      </div>
    </div>
    <div class="mt-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="checkout_enable_street" id="f_street" <?= $on('checkout_enable_street')?'checked':'' ?>>
        <label class="form-check-label" for="f_street">Street Address</label>
      </div>
    </div>
  </div>
</div>
<div class="mt-3">
  <button class="btn btn-primary">Save Checkout Settings</button>
</div>
</form>

</div>
<div class="tab-pane fade <?= $activeTab==='shipping'?'show active':'' ?>" id="tab-shipping" role="tabpanel">
  <form method="post" action="/admin/settings" class="row g-3">
    <?= csrf_field() ?>
    <input type="hidden" name="scope" value="shipping">
    <div class="col-md-6">
      <label class="form-label">General Shipping Fee (COD)</label>
      <input class="form-control" type="number" step="0.01" name="shipping_fee_cod" value="<?= htmlspecialchars($settings['shipping_fee_cod'] ?? '0.00') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">General Shipping Fee (Pickup)</label>
      <input class="form-control" type="number" step="0.01" name="shipping_fee_pickup" value="<?= htmlspecialchars($settings['shipping_fee_pickup'] ?? '0.00') ?>">
    </div>
    <div class="col-12">
      <div class="alert alert-info small">City-specific COD fees below override the General COD fee. Any city not listed will use the General COD fee.</div>
    </div>
    <div class="col-12">
      <button class="btn btn-primary">Save Shipping Fees</button>
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
</div>

    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light"><tr><th>City</th><th class="text-end">Fee</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
          <?php foreach (($fees ?? []) as $f): ?>
            <tr>
              <td><?= htmlspecialchars($f['city']) ?></td>
              <td class="text-end">₱<?= number_format((float)$f['fee'],2) ?></td>
              <td class="text-end">
                <form method="post" action="/admin/settings/fees/<?= (int)$f['id'] ?>/delete" onsubmit="return confirm('Delete this city fee?')" class="d-inline">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


</div>
</div>
