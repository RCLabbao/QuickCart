<?php use function App\Core\csrf_field; ?>

<!-- Admin User Form Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?= isset($user) ? 'Edit Admin User' : 'Create Admin User' ?></h1>
    <p class="text-muted mb-0">
      <?= isset($user) ? 'Update profile, email and access roles' : 'Create a new admin account and assign roles' ?>
    </p>
  </div>
  <div>
    <a class="btn btn-outline-secondary" href="/admin/users">
      <i class="bi bi-arrow-left me-2"></i>Back to Users
    </a>
  </div>
</div>

<form method="post" action="<?= isset($user)?('/admin/users/'.(int)$user['id']):'/admin/users' ?>" class="row g-4">
  <?= csrf_field() ?>

  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0"><i class="bi bi-person me-2"></i>Account Details</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Name</label>
            <input class="form-control" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Email</label>
            <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Password <?= isset($user)?'<small class=\'text-muted\'>(leave blank to keep)</small>':'' ?></label>
            <input class="form-control" type="password" name="password" <?= isset($user)?'':'required' ?>>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0"><i class="bi bi-shield-lock me-2"></i>Roles & Access</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Assign Roles</label>
          <select class="form-select" name="roles[]" multiple size="6">
            <?php $assignedIds = $assignedIds ?? []; foreach ($roles as $r): ?>
              <option value="<?= (int)$r['id'] ?>" <?= in_array((int)$r['id'], $assignedIds, true) ? 'selected' : '' ?>>
                <?= htmlspecialchars($r['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">User permissions come from assigned roles.</div>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i><?= isset($user) ? 'Update User' : 'Create User' ?>
          </button>
          <?php if (isset($user)): ?>
          <button type="button" class="btn btn-outline-danger" onclick="if(confirm('Delete this admin user?')) document.getElementById('deleteUserForm').submit();">
            <i class="bi bi-trash me-2"></i>Delete User
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php if (isset($user)): ?>
<form id="deleteUserForm" method="post" action="/admin/users/<?= (int)$user['id'] ?>/delete" class="d-none">
  <?= csrf_field() ?>
</form>
<?php endif; ?>

<style>
.card { transition: all 0.2s ease; }
.card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; }
.form-control:focus, .form-select:focus { border-color: var(--bs-primary); box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25); }
</style>
