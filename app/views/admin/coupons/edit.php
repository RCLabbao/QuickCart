<?php include BASE_PATH . '/app/views/admin/_nav.php'; ?>
<h1 class="h4 mb-3">Edit Coupon</h1>
<form method="post" action="/admin/coupons/<?= (int)$coupon['id'] ?>">
  <?= App\Core\csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" value="<?= htmlspecialchars($coupon['code']) ?>" required></div>
    <div class="col-md-4"><label class="form-label">Kind</label>
      <select class="form-select" name="kind">
        <option value="fixed" <?= $coupon['kind']==='fixed'?'selected':'' ?>>Fixed</option>
        <option value="percent" <?= $coupon['kind']==='percent'?'selected':'' ?>>Percent</option>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label">Amount</label><input class="form-control" type="number" step="0.01" name="amount" value="<?= number_format((float)$coupon['amount'],2,'.','') ?>" required></div>
    <div class="col-md-4"><label class="form-label">Min spend</label><input class="form-control" type="number" step="0.01" name="min_spend" value="<?= $coupon['min_spend']!==null?number_format((float)$coupon['min_spend'],2,'.',''):'' ?>"></div>
    <div class="col-md-4"><label class="form-label">Expires at</label><input class="form-control" type="datetime-local" name="expires_at" value="<?= htmlspecialchars(str_replace(' ','T',$coupon['expires_at'] ?? '')) ?>"></div>
    <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="active" <?= !empty($coupon['active'])?'checked':'' ?>> <label class="form-check-label">Active</label></div></div>
    <div class="col-12"><button class="btn btn-dark">Save</button></div>
  </div>
</form>

