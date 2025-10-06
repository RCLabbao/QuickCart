<!-- Customers Header with Search -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
  <div>
    <h1 class="h3 m-0">Customers</h1>
    <p class="text-muted mb-0">Search by name or email; click to view order history</p>
  </div>
  <form class="d-flex" role="search" method="get" action="/admin/customers" style="min-width:280px;">
    <input class="form-control me-2" type="search" placeholder="Search name or email" name="q" value="<?= htmlspecialchars($q ?? '') ?>">
    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
    <a class="btn btn-outline-secondary ms-2" href="/admin/customers/export">Export CSV</a>
  </form>
  <form method="post" action="/admin/customers/seed-dummies" class="ms-2">
    <?= App\Core\csrf_field() ?>
    <button class="btn btn-sm btn-dark" type="submit"><i class="bi bi-people-fill me-1"></i>Seed Dummy Customers</button>
  </form>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th class="text-end">Orders</th>
            <th class="text-end">Total Spent</th>
            <th class="text-end" style="width: 120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach (($customers ?? []) as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['name'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td class="text-end"><?= (int)$c['orders'] ?></td>
            <td class="text-end">₱<?= number_format((float)$c['spent'],2) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="/admin/customers/view?email=<?= urlencode($c['email']) ?>">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
.card { transition: all .2s ease; }
.card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08) !important; }
</style>
