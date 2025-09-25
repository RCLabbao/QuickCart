<?php include BASE_PATH . '/app/views/admin/_nav.php'; ?>
<h1 class="h4 mb-3">New Coupon</h1>
<form method="post" action="/admin/coupons">
  <?= App\Core\csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" required></div>
    <div class="col-md-4"><label class="form-label">Kind</label>
      <select class="form-select" name="kind">
        <option value="fixed">Fixed</option>
        <option value="percent">Percent</option>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label">Amount</label><input class="form-control" type="number" step="0.01" name="amount" required></div>
    <div class="col-md-4"><label class="form-label">Min spend</label><input class="form-control" type="number" step="0.01" name="min_spend"></div>
    <div class="col-md-4"><label class="form-label">Expires at</label><input class="form-control" type="datetime-local" name="expires_at"></div>
    <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="active" checked> <label class="form-check-label">Active</label></div></div>
    <div class="col-12"><button class="btn btn-dark">Save</button></div>
  </div>
</form>

