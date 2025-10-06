<?php use function App\Core\e; $cartCount = array_sum($_SESSION['cart'] ?? []); ?>
<nav class="navbar navbar-expand-lg bg-light border-bottom sticky-top">
  <div class="container">
    <!-- Mobile search bar - always visible on top -->
    <div class="w-100 d-lg-none mb-2">
      <form action="/search" method="get" class="position-relative">
        <div class="input-group">
          <input type="text" class="form-control" name="q" placeholder="Search products..." id="mobileSearchInput">
          <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </div>
        <!-- Mobile search results dropdown -->
        <div id="mobileSearchResults" class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-sm" style="top: 100%; z-index: 1050; display: none; max-height: 300px; overflow-y: auto;"></div>
      </form>
    </div>

    <!-- Main navbar row -->
    <div class="d-flex align-items-center w-100">
      <a class="navbar-brand fw-bold" href="/"><?= e(App\Core\setting('store_name','QuickCart')) ?></a>

      <!-- Mobile: Cart icon and hamburger menu side by side -->
      <div class="d-lg-none ms-auto d-flex align-items-center gap-2">
        <button class="btn btn-dark position-relative" id="openCart" aria-label="Open cart">
          <i class="bi bi-bag"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount"><?= (int)$cartCount ?></span>
        </button>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
      </div>

      <!-- Desktop navigation -->
      <div id="nav" class="collapse navbar-collapse">
        <!-- Mobile nav links -->
        <ul class="navbar-nav d-lg-none me-auto mb-2">
          <li class="nav-item"><a class="nav-link" href="/collections">Collections</a></li>
          <li class="nav-item"><a class="nav-link" href="/products">All Products</a></li>
        </ul>
        <form action="/search" method="get" class="ms-auto me-3 w-50 d-none d-lg-block position-relative">
          <div class="input-group">
            <input type="text" class="form-control" name="q" placeholder="Search products..." id="desktopSearchInput">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
          </div>
          <!-- Desktop search results dropdown -->
          <div id="desktopSearchResults" class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-sm" style="top: 100%; z-index: 1050; display: none; max-height: 300px; overflow-y: auto;"></div>
        </form>
        <button class="btn btn-dark position-relative d-none d-lg-block" id="openCartDesktop" aria-label="Open cart">
          <i class="bi bi-bag"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCountDesktop"><?= (int)$cartCount ?></span>
        </button>
      </div>
    </div>
  </div>
</nav>

