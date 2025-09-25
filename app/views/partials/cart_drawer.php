<?php use function App\Core\e; use function App\Core\price; ?>
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartDrawer">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Your Cart</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body" id="cartItems">
    <div class="text-muted">Your cart is empty.</div>
  </div>
  <div class="offcanvas-footer p-3 border-top">
    <a href="/checkout" class="btn btn-dark w-100">Checkout</a>
  </div>
</div>

