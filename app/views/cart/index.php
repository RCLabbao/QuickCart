<?php use function App\Core\csrf_field; ?>

<div>
  <!-- Cart Header -->
  <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h1 class="h3 mb-1">Shopping Cart</h1>
          <p class="text-muted mb-0">Review your items before checkout</p>
        </div>
        <a href="/products" class="btn btn-outline-primary">
          <i class="bi bi-arrow-left me-2"></i>Continue Shopping
        </a>
      </div>

      <!-- Progress Steps -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
              <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                1
              </div>
              <span class="ms-2 fw-bold">Cart</span>
            </div>
            <div class="border-top mx-3" style="width: 50px;"></div>
            <div class="d-flex align-items-center">
              <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                2
              </div>
              <span class="ms-2 text-muted">Checkout</span>
            </div>
            <div class="border-top mx-3" style="width: 50px;"></div>
            <div class="d-flex align-items-center">
              <div class="rounded-circle bg-light text-muted d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                3
              </div>
              <span class="ms-2 text-muted">Complete</span>
            </div>
          </div>
        </div>
      </div>

      <?php if (empty($cart)): ?>
        <!-- Empty Cart -->
        <div class="card border-0 shadow-sm text-center py-5">
          <div class="card-body">
            <div class="mb-4">
              <i class="bi bi-cart-x display-1 text-muted"></i>
            </div>
            <h3 class="h4 mb-3">Your cart is empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
            <a href="/products" class="btn btn-primary btn-lg">
              <i class="bi bi-bag me-2"></i>Start Shopping
            </a>
          </div>
        </div>
      <?php else: ?>
        <!-- Cart Items -->
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-bottom">
                <h5 class="card-title mb-0">
                  <i class="bi bi-bag me-2"></i>Cart Items (<?= array_sum($cart) ?> items)
                </h5>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Product</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Total</th>
                        <th width="50"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      $subtotal = 0;
                      foreach ($cart as $productId => $quantity):
                        // Get product details
                        $stmt = \App\Core\DB::pdo()->prepare('SELECT id, title, price, sale_price, sale_start, sale_end, slug FROM products WHERE id = ?');
                        $stmt->execute([$productId]);
                        $product = $stmt->fetch();
                        if (!$product) continue;
                        
                        $effectivePrice = \App\Core\effective_price($product);
                        $lineTotal = $effectivePrice * $quantity;
                        $subtotal += $lineTotal;
                      ?>
                        <tr>
                          <td>
                            <div class="d-flex align-items-center">
                              <div class="me-3">
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                  <i class="bi bi-image text-muted"></i>
                                </div>
                              </div>
                              <div>
                                <h6 class="mb-1">
                                  <a href="/products/<?= htmlspecialchars($product['slug']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($product['title']) ?>
                                  </a>
                                </h6>
                                <small class="text-muted">SKU: #<?= $product['id'] ?></small>
                              </div>
                            </div>
                          </td>
                          <td class="text-center">
                            <div class="input-group input-group-sm justify-content-center" style="width: 120px; margin: 0 auto;">
                              <button class="btn btn-outline-secondary" type="button" data-cart-action="qty" data-dir="-1" data-product-id="<?= $product['id'] ?>">
                                <i class="bi bi-dash"></i>
                              </button>
                              <input type="text" class="form-control text-center" value="<?= $quantity ?>" id="cartQty_<?= $product['id'] ?>" data-cart-qty="<?= $quantity ?>" readonly>
                              <button class="btn btn-outline-secondary" type="button" data-cart-action="qty" data-dir="1" data-product-id="<?= $product['id'] ?>">
                                <i class="bi bi-plus"></i>
                              </button>
                            </div>
                          </td>
                          <td class="text-end">
                            <?php if ($effectivePrice < $product['price']): ?>
                              <div class="text-decoration-line-through text-muted small">₱<?= number_format($product['price'], 2) ?></div>
                              <div class="text-danger fw-semibold">₱<?= number_format($effectivePrice, 2) ?></div>
                            <?php else: ?>
                              <div class="fw-semibold">₱<?= number_format($effectivePrice, 2) ?></div>
                            <?php endif; ?>
                          </td>
                          <td class="text-end">
                            <div class="fw-bold">₱<?= number_format($lineTotal, 2) ?></div>
                          </td>
                          <td class="text-center">
                            <button class="btn btn-sm btn-outline-danger" data-cart-action="remove" data-product-id="<?= $product['id'] ?>" title="Remove item">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm position-sticky" style="top: 20px;">
              <div class="card-header bg-white border-bottom">
                <h5 class="card-title mb-0">
                  <i class="bi bi-receipt me-2"></i>Order Summary
                </h5>
              </div>
              <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                  <span>Subtotal (<?= array_sum($cart) ?> items)</span>
                  <span class="fw-semibold">₱<?= number_format($subtotal, 2) ?></span>
                </div>
                
                <div class="d-flex justify-content-between mb-3">
                  <span>Shipping</span>
                  <span class="text-muted">Calculated at checkout</span>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-4">
                  <span class="fw-bold">Total</span>
                  <span class="fw-bold fs-5">₱<?= number_format($subtotal, 2) ?></span>
                </div>
                
                <div class="d-grid gap-2">
                  <a href="/checkout" class="btn btn-primary btn-lg">
                    <i class="bi bi-lock me-2"></i>Proceed to Checkout
                  </a>
                  <a href="/products" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                  </a>
                </div>
                
                <div class="text-center mt-3">
                  <small class="text-muted">
                    <i class="bi bi-shield-check me-1"></i>
                    Secure checkout guaranteed
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
</div>

<style>
.table td {
  vertical-align: middle;
}

.input-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

.card {
  transition: all 0.2s ease;
}

@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }
  
  .input-group {
    width: 100px !important;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Cart quantity and remove actions
  document.body.addEventListener('click', async function(e) {
    const btn = e.target.closest('[data-cart-action]');
    if (!btn) return;
    
    const action = btn.getAttribute('data-cart-action');
    const pid = btn.getAttribute('data-product-id');
    
    if (action === 'remove') {
      if (!confirm('Remove this item from your cart?')) return;
      
      const fd = new FormData();
      fd.append('_token', window.CSRF || '');
      fd.append('product_id', pid);
      
      try {
        const res = await fetch('/cart/remove', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.ok) {
          location.reload(); // Reload to update the cart display
        }
      } catch (error) {
        console.error('Error removing item:', error);
      }
    } else if (action === 'qty') {
      const dir = parseInt(btn.getAttribute('data-dir'), 10);
      const input = document.querySelector(`#cartQty_${pid}`);
      let qty = parseInt(input.value || '1', 10) + dir;
      if (qty < 0) qty = 0;
      
      const fd = new FormData();
      fd.append('_token', window.CSRF || '');
      fd.append('product_id', pid);
      fd.append('qty', qty);
      
      try {
        const res = await fetch('/cart/update', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.ok) {
          if (qty === 0) {
            location.reload(); // Reload if item was removed
          } else {
            input.value = qty;
            input.setAttribute('data-cart-qty', qty);
            // Update line total and subtotal (simplified - reload for now)
            location.reload();
          }
        }
      } catch (error) {
        console.error('Error updating quantity:', error);
      }
    }
  });
});
</script>
