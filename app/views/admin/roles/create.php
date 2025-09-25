<?php use function App\Core\csrf_field; ?>
<?php include BASE_PATH . '/app/views/admin/_nav.php'; ?>
<h1 class="h4 mb-3">Create Role</h1>
<form method="post" action="/admin/roles">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Slug</label>
      <input class="form-control" name="slug" placeholder="e.g. editor" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Name</label>
      <input class="form-control" name="name" placeholder="e.g. Editor" required>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-dark">Create Role</button>
    <a class="btn btn-link" href="/admin/roles">Cancel</a>
  </div>
</form>

