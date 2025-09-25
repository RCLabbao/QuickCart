<?php use function App\Core\csrf_field; ?>
<?php include BASE_PATH . '/app/views/admin/_nav.php'; ?>
<h1 class="h4 mb-3">Create Admin User</h1>
<form method="post" action="/admin/users" class="row g-3">
  <?= csrf_field() ?>
  <div class="col-md-6">
    <label class="form-label">Name</label>
    <input class="form-control" name="name" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Email</label>
    <input class="form-control" type="email" name="email" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Password</label>
    <input class="form-control" type="password" name="password" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Assign Roles</label>
    <select class="form-select" name="roles[]" multiple>
      <?php foreach ($roles as $r): ?>
        <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12">
    <button class="btn btn-dark">Create</button>
  </div>
</form>

