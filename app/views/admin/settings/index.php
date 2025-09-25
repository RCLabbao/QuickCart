<?php use function App\Core\csrf_field; ?>
<h1 class="h4 mb-3">Settings</h1>
<form method="post" action="/admin/settings" class="row g-3">
  <?= csrf_field() ?>
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
    <label class="form-label">Shipping Fee (COD)</label>
    <input class="form-control" type="number" step="0.01" name="shipping_fee_cod" value="<?= htmlspecialchars($settings['shipping_fee_cod'] ?? '0.00') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">Shipping Fee (Pickup)</label>
    <input class="form-control" type="number" step="0.01" name="shipping_fee_pickup" value="<?= htmlspecialchars($settings['shipping_fee_pickup'] ?? '0.00') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">Brand Color</label>
    <input class="form-control form-control-color" type="color" name="brand_color" value="<?= htmlspecialchars($settings['brand_color'] ?? '#212529') ?>">
  </div>
  <div class="col-12">
    <button class="btn btn-dark">Save Settings</button>
  </div>
</form>
<hr class="my-4">
<form method="post" action="/admin/maintenance/optimize" onsubmit="return confirm('Apply database optimizations and add missing columns/indexes?')">
  <?= csrf_field() ?>
  <button class="btn btn-outline-danger">Optimize Database (safe)</button>
  <div class="form-text">Adds stock/notes/sale/discount columns if missing and creates helpful indexes.</div>
</form>
<form method="post" action="/admin/maintenance/seed-demo" class="mt-3" onsubmit="return confirm('This will insert additional demo products and orders. Continue?')">
  <?= csrf_field() ?>
  <button class="btn btn-outline-primary">Seed Demo Data (products + orders)</button>
</form>


