<?php use function App\Core\csrf_field; ?>
<h1 class="h4 mb-3">Edit Role: <?= htmlspecialchars($role['name']) ?></h1>
<form method="post" action="/admin/roles/<?= (int)$role['id'] ?>">
  <?= csrf_field() ?>
  <div class="row g-2">
    <?php foreach ($perms as $p): $checked = in_array((int)$p['id'], $assignedIds, true); ?>
      <div class="col-md-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="perms[]" value="<?= (int)$p['id'] ?>" id="perm<?= (int)$p['id'] ?>" <?= $checked?'checked':'' ?>>
          <label class="form-check-label" for="perm<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['slug']) ?></label>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="mt-3">
    <button class="btn btn-dark">Save</button>
    <a class="btn btn-link" href="/admin/roles">Back</a>
  </div>
</form>

