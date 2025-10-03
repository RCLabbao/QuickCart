<?php use function App\Core\csrf_field; ?>

<!-- Users Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Admin Users</h1>
    <p class="text-muted mb-0">Manage administrator accounts and access</p>
  </div>
  <div>
    <a class="btn btn-primary" href="/admin/users/create">
      <i class="bi bi-plus-circle me-2"></i>Create Admin User
    </a>
  </div>
</div>

<!-- Users Table -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:80px;">ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Roles</th>
            <th style="width:140px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><span class="badge bg-primary">#<?= (int)$u['id'] ?></span></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['roles'] ?? '') ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <a class="btn btn-outline-primary btn-icon" href="/admin/users/<?= (int)$u['id'] ?>/edit" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="post" action="/admin/users/<?= (int)$u['id'] ?>/delete" onsubmit="return confirm('Delete this admin user?')">
                  <?= csrf_field() ?>
                  <button class="btn btn-outline-danger btn-icon" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
.btn-icon { width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
.btn-group-sm .btn-icon { width: 32px; height: 32px; }
.card { transition: all 0.2s ease; }
.card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; }
</style>
