<?php use function App\Core\csrf_field; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4">Admin Users</h1>
  <a class="btn btn-dark" href="/admin/users/create">Create Admin User</a>
</div>
<table class="table align-middle">
  <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Roles</th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= (int)$u['id'] ?></td>
      <td><?= htmlspecialchars($u['name']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['roles'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

