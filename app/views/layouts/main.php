<?php use function App\Core\asset; use function App\Core\csrf_field; use function App\Core\e; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(App\Core\setting('store_name','QuickCart')) ?></title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="dns-prefetch" href="//cdn.jsdelivr.net">

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
  </style>
</head>
<body>
  <?php include BASE_PATH . '/app/views/partials/header.php'; ?>
  <main class="container py-4">
    <?php include $viewPath; ?>
  </main>
  <?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
  <?php include BASE_PATH . '/app/views/partials/cart_drawer.php'; ?>
  <!-- Global toast container -->
  <div class="position-fixed top-0 end-0 p-3" style="z-index: 2000">
    <div id="qcToast" class="toast align-items-center text-bg-dark border-0" role="status" aria-live="polite" aria-atomic="true" data-bs-delay="1800">
      <div class="d-flex">
        <div class="toast-body" id="qcToastBody">Added to cart</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>window.CSRF='<?= e(\App\Core\CSRF::token()) ?>';</script>
  <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>

