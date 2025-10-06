<?php use function App\Core\asset; use function App\Core\e; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Admin Panel') ?> - QuickCart Admin</title>
  <meta name="robots" content="noindex, nofollow">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= asset('css/styles.css') ?>" rel="stylesheet">
  <style>
    :root{ --brand: <?= e(App\Core\setting('brand_color','#212529')) ?>; }
    .btn-dark{ background:var(--brand); border-color:var(--brand); }
    .btn-dark:hover{ filter:brightness(.95); }
    .badge.text-bg-secondary{ background: var(--brand) !important; }
    .navbar .btn-dark{ background:var(--brand); border-color:var(--brand); }
    a{ color: var(--brand); }
    a.link-secondary{ color:#6c757d !important; }

    /* Admin Layout Styles */
    .admin-sidebar {
      min-height: 100vh;
      background: #f8f9fa;
      border-right: 1px solid #dee2e6;
    }
    .admin-sidebar .nav-link {
      color: #495057;
      padding: 0.75rem 1rem;
      border-radius: 0;
    }
    .admin-sidebar .nav-link:hover,
    .admin-sidebar .nav-link.active {
      background: var(--brand);
      color: white;
    }
    .admin-sidebar .nav-link i {
      width: 20px;
      margin-right: 0.5rem;
    }
    .admin-content {
      min-height: 100vh;
    }
    .admin-header {
      background: white;
      border-bottom: 1px solid #dee2e6;
      padding: 1rem 0;
    }
    @media (max-width: 767.98px) {
      .admin-sidebar {
        min-height: auto;
      }
      .admin-content {
        min-height: auto;
      }
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <nav class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse" id="adminSidebar">
        <div class="position-sticky pt-3">
          <div class="px-3 mb-3">
            <h5 class="text-muted">QuickCart Admin</h5>
          </div>

          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin') === 0 && strpos($_SERVER['REQUEST_URI'], '/admin/') === false ? 'active' : '' ?>" href="/admin">
                <i class="bi bi-speedometer2"></i>Dashboard
              </a>
            </li>
            <?php if (\App\Core\Auth::hasPermission('products.read') || \App\Core\Auth::hasPermission('products.write')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/products') === 0 ? 'active' : '' ?>" href="/admin/products">
                <i class="bi bi-box"></i>Products
              </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('orders.read')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/orders') === 0 ? 'active' : '' ?>" href="/admin/orders">
                <i class="bi bi-receipt"></i>Orders
              </a>
              <?php if (strpos($_SERVER['REQUEST_URI'], '/admin/orders') === 0): ?>
              <ul class="nav flex-column ms-3">
                <li class="nav-item">
                  <a class="nav-link py-1 <?= $_SERVER['REQUEST_URI'] === '/admin/orders' ? 'active' : '' ?>" href="/admin/orders">
                    <i class="bi bi-list"></i>All Orders
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link py-1 <?= $_SERVER['REQUEST_URI'] === '/admin/orders/today' ? 'active' : '' ?>" href="/admin/orders/today">
                    <i class="bi bi-calendar-day"></i>Today's Orders
                  </a>
                </li>
              </ul>
              <?php endif; ?>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('users.read')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/customers') === 0 ? 'active' : '' ?>" href="/admin/customers">
                <i class="bi bi-people"></i>Customers
              </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('collections.write')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/collections') === 0 ? 'active' : '' ?>" href="/admin/collections">
                <i class="bi bi-grid"></i>Collections
              </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('settings.read')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/coupons') === 0 ? 'active' : '' ?>" href="/admin/coupons">
                <i class="bi bi-tag"></i>Coupons
              </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('orders.read')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/reports') === 0 ? 'active' : '' ?>" href="/admin/reports">
                <i class="bi bi-graph-up"></i>Reports
              </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('products.write')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/sync') === 0 ? 'active' : '' ?>" href="/admin/sync">
                <i class="bi bi-arrow-repeat"></i>Sync
              </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('users.read')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/users') === 0 ? 'active' : '' ?>" href="/admin/users">
                <i class="bi bi-person-gear"></i>Admin Users
              </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('roles.write')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/roles') === 0 ? 'active' : '' ?>" href="/admin/roles">
                <i class="bi bi-shield-check"></i>Roles
              </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('settings.read')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/settings') === 0 ? 'active' : '' ?>" href="/admin/settings">
                <i class="bi bi-gear"></i>Settings
              </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('settings.read')): ?>
            <li class="nav-item">
              <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/maintenance') === 0 ? 'active' : '' ?>" href="/admin/maintenance">
                <i class="bi bi-tools"></i>Maintenance
              </a>
            </li>
            <?php endif; ?>
          </ul>

          <hr>

          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link" href="/" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i>View Store
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/admin/logout">
                <i class="bi bi-box-arrow-right"></i>Logout
              </a>
            </li>
          </ul>
        </div>
      </nav>

      <!-- Main content -->
      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
        <!-- Admin Header -->
        <div class="admin-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <button class="btn btn-outline-secondary d-md-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar">
              <i class="bi bi-list"></i>
            </button>
            <h1 class="h4 mb-0"><?= e($title ?? 'Admin Panel') ?></h1>
          </div>
          <div class="d-flex align-items-center">
            <span class="text-muted me-3">Welcome, <?= e(App\Core\Auth::user()['name'] ?? 'Admin') ?></span>
          </div>
        </div>
        <?php if (!empty($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="py-4">
          <?php include $viewPath; ?>
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>window.CSRF='<?= e(\App\Core\CSRF::token()) ?>';</script>
  <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
