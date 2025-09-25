
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0">Roles & Permissions</h1>
  <a class="btn btn-dark" href="/admin/roles/create">Create Role</a>
</div>
<table class="table align-middle">
  <thead class="table-light"><tr><th>ID</th><th>Slug</th><th>Name</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($roles as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><span class="badge text-bg-secondary"><?= htmlspecialchars($r['slug']) ?></span></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="/admin/roles/<?= (int)$r['id'] ?>">Edit Permissions</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

