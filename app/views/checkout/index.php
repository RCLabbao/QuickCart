<?php use function App\Core\csrf_field; ?>

<!-- Checkout Header -->
<div class="d-flex align-items-center mb-4">
  <a href="/cart" class="btn btn-outline-secondary me-3">
    <i class="bi bi-arrow-left"></i> Back to Cart
  </a>
  <h1 class="h3 mb-0">Secure Checkout</h1>
</div>

      <!-- Progress Steps -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
              <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-check"></i>
              </div>
              <span class="ms-2 text-muted">Cart</span>
            </div>
            <div class="border-top mx-3" style="width: 50px;"></div>
            <div class="d-flex align-items-center">
              <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                2
              </div>
              <span class="ms-2 fw-bold">Checkout</span>
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

      <!-- Error/Success Messages -->
      <?php if (isset($_SESSION['checkout_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <?= htmlspecialchars($_SESSION['checkout_error']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['checkout_error']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['checkout_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle me-2"></i>
          <?= htmlspecialchars($_SESSION['checkout_success']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['checkout_success']); ?>
      <?php endif; ?>

      <form method="post" action="/checkout" id="checkoutForm">
        <?= csrf_field() ?>

        <div class="row g-4">
          <!-- Customer Information -->
          <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-bottom">
                <h5 class="card-title mb-0">
                  <i class="bi bi-person me-2"></i>Customer Information
                </h5>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input class="form-control form-control-lg" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required placeholder="Enter your full name">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input class="form-control form-control-lg" type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required placeholder="your@email.com">
                  </div>
                  <?php if ((bool)\App\Core\setting('checkout_enable_phone', 1)): ?>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                    <input class="form-control form-control-lg" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required placeholder="+63 9XX XXX XXXX">
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Shipping Method -->
            <div class="card border-0 shadow-sm mt-4">
              <div class="card-header bg-white border-bottom">
                <h5 class="card-title mb-0">
                  <i class="bi bi-truck me-2"></i>Delivery Method
                </h5>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-12">
                    <div class="form-check form-check-card">
                      <input class="form-check-input" type="radio" name="shipping_method" id="cod" value="cod" <?= ($_POST['shipping_method'] ?? 'cod') === 'cod' ? 'checked' : '' ?>>
                      <label class="form-check-label w-100" for="cod">
                        <div class="card">
                          <div class="card-body d-flex align-items-center">
                            <i class="bi bi-cash-coin fs-4 text-primary me-3"></i>
                            <div class="flex-grow-1">
                              <h6 class="mb-1">Cash on Delivery</h6>
                              <small class="text-muted">Pay when you receive your order</small>
                            </div>
                            <div class="text-end">
                              <span class="badge bg-success">Free</span>
                            </div>
                          </div>
                        </div>
                      </label>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="form-check form-check-card">
                      <input class="form-check-input" type="radio" name="shipping_method" id="pickup" value="pickup" <?= ($_POST['shipping_method'] ?? '') === 'pickup' ? 'checked' : '' ?>>
                      <label class="form-check-label w-100" for="pickup">
                        <div class="card">
                          <div class="card-body d-flex align-items-center">
                            <i class="bi bi-shop fs-4 text-primary me-3"></i>
                            <div class="flex-grow-1">
                              <h6 class="mb-1">Store Pickup</h6>
                              <small class="text-muted">Pick up from our store location</small>
                            </div>
                            <div class="text-end">
                              <span class="badge bg-success">Free</span>
                            </div>
                          </div>
                        </div>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Shipping Address -->
            <div class="card border-0 shadow-sm mt-4" id="addressCard">
              <div class="card-header bg-white border-bottom">
                <h5 class="card-title mb-0">
                  <i class="bi bi-geo-alt me-2"></i>Delivery Address
                </h5>
              </div>
              <div class="card-body">
                <div class="address-grid">
                  <?php if ((bool)\App\Core\setting('checkout_enable_region', 1)): ?>
                  <div>
                    <label class="form-label fw-semibold">Region <span class="text-danger address-required">*</span></label>
                    <input class="form-control" name="region" value="<?= htmlspecialchars($_POST['region'] ?? '') ?>" placeholder="e.g., Region II">
                  </div>
                  <?php endif; ?>
                  <?php if ((bool)\App\Core\setting('checkout_enable_province', 1)): ?>
                  <div>
                    <label class="form-label fw-semibold">Province <span class="text-danger address-required">*</span></label>
                    <input class="form-control" name="province" value="<?= htmlspecialchars($_POST['province'] ?? '') ?>" placeholder="e.g., Cagayan">
                  </div>
                  <?php endif; ?>
                  <?php if ((bool)\App\Core\setting('checkout_enable_city', 1)): ?>
                  <div>
                    <label class="form-label fw-semibold">City/Municipality <span class="text-danger address-required">*</span></label>
                    <input class="form-control" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" placeholder="e.g., Aparri City">
                  </div>
                  <?php endif; ?>
                  <?php if ((bool)\App\Core\setting('checkout_enable_barangay', 1)): ?>
                  <div>
                    <label class="form-label fw-semibold">Barangay <span class="text-danger address-required">*</span></label>
                    <input class="form-control" name="barangay" value="<?= htmlspecialchars($_POST['barangay'] ?? '') ?>" placeholder="e.g., Barangay 123">
                  </div>
                  <?php endif; ?>
                  <?php if ((bool)\App\Core\setting('checkout_enable_postal', 1)): ?>
                  <div>
                    <label class="form-label fw-semibold">Postal Code</label>
                    <input class="form-control" name="postal" value="<?= htmlspecialchars($_POST['postal'] ?? '') ?>" placeholder="1100">
                  </div>
                  <?php endif; ?>
                  <?php if ((bool)\App\Core\setting('checkout_enable_street', 1)): ?>
                  <div class="grid-span-2">
                    <label class="form-label fw-semibold">Street Address <span class="text-danger address-required">*</span></label>
                    <input class="form-control" name="street" value="<?= htmlspecialchars($_POST['street'] ?? '') ?>" placeholder="House/Unit No., Street Name">
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Coupon Code -->
            <div class="card border-0 shadow-sm mt-4">
              <div class="card-header bg-white border-bottom">
                <h5 class="card-title mb-0">
                  <i class="bi bi-tag me-2"></i>Discount Code
                </h5>
              </div>
              <div class="card-body">
                <div class="input-group">
                  <input class="form-control" name="coupon" value="<?= htmlspecialchars($_POST['coupon'] ?? '') ?>" placeholder="Enter coupon code">
                  <button class="btn btn-outline-secondary" type="button" onclick="applyCoupon()">Apply</button>
                </div>
                <small class="text-muted">Have a discount code? Enter it here to save on your order.</small>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="col-lg-5">
            <div class="card border-0 shadow-sm position-sticky" style="top: 20px;">
              <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0 text-white">
                  <i class="bi bi-receipt me-2"></i>Order Summary
                </h5>
              </div>
              <div class="card-body">
                <?php if (!empty($cart)): ?>
                  <?php
                  $subtotal = 0;
                  foreach ($cart as $productId => $quantity):
                    // Get product details (simplified for display)
                    $stmt = \App\Core\DB::pdo()->prepare('SELECT id, title, price, sale_price, sale_start, sale_end FROM products WHERE id = ?');
                    $stmt->execute([$productId]);
                    $product = $stmt->fetch();
                    if (!$product) continue;

                    $effectivePrice = \App\Core\effective_price($product);
                    $lineTotal = $effectivePrice * $quantity;
                    $subtotal += $lineTotal;
                  ?>
                  <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="flex-grow-1">
                      <h6 class="mb-1"><?= htmlspecialchars($product['title']) ?></h6>
                      <small class="text-muted">Qty: <?= (int)$quantity ?></small>
                    </div>
                    <div class="text-end">
                      <span class="fw-bold">₱<?= number_format($lineTotal, 2) ?></span>
                      <?php if ($product['sale_price'] && \App\Core\is_on_sale($product)): ?>
                        <br><small class="text-muted text-decoration-line-through">₱<?= number_format($product['price'] * $quantity, 2) ?></small>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>

                  <?php
                    $codFee = (float)\App\Core\setting('shipping_fee_cod', 0.00);
                    $pickupFee = (float)\App\Core\setting('shipping_fee_pickup', 0.00);
                    $selectedMethod = (($_POST['shipping_method'] ?? 'cod') === 'pickup') ? 'pickup' : 'cod';
                    $shippingFee = $selectedMethod === 'cod' ? $codFee : $pickupFee;
                    $initialTotal = max(0.0, $subtotal - 0.0) + $shippingFee;
                  ?>
                  <input type="hidden" id="subtotalValue" value="<?= number_format($subtotal,2,'.','') ?>">
                  <input type="hidden" id="discountValue" value="0">
                  <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>₱<?= number_format($subtotal, 2) ?></span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span>Shipping:</span>
                    <span id="shippingAmount"
                          data-cod-fee="<?= number_format($codFee,2,'.','') ?>"
                          data-pickup-fee="<?= number_format($pickupFee,2,'.','') ?>">
                      <?= $shippingFee > 0 ? '₱'.number_format($shippingFee, 2) : 'Free' ?>
                    </span>
                  </div>
                  <div class="d-flex justify-content-between mb-3">
                    <span>Discount:</span>
                    <span class="text-success" id="discountAmount">₱0.00</span>
                  </div>
                  <hr>
                  <div class="d-flex justify-content-between mb-4">
                    <span class="h5">Total:</span>
                    <span class="h5 text-primary" id="totalAmount">₱<?= number_format($initialTotal, 2) ?></span>
                  </div>
                <?php else: ?>
                  <div class="text-center py-4">
                    <i class="bi bi-cart-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">Your cart is empty</p>
                    <a href="/products" class="btn btn-primary">Continue Shopping</a>
                  </div>
                <?php endif; ?>

                <?php if (!empty($cart)): ?>
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                  <i class="bi bi-lock me-2"></i>Place Secure Order
                </button>
                <div class="text-center">
                  <small class="text-muted">
                    <i class="bi bi-shield-check me-1"></i>
                    Your information is secure and encrypted
                  </small>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </form>

<style>
.form-check-card .form-check-input:checked + .form-check-label .card {
  border-color: var(--bs-primary);
  background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.form-check-card .card {
  transition: all 0.2s ease;
  cursor: pointer;
}

.form-check-card .card:hover {
  border-color: var(--bs-primary);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.form-check-card .form-check-input {
  position: absolute;
  opacity: 0;
}

.progress-step {
  transition: all 0.3s ease;
}

.card {
  transition: all 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.address-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem}.address-grid .grid-span-2{grid-column:1 / -1}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const codRadio = document.getElementById('cod');
  const pickupRadio = document.getElementById('pickup');
  const addressCard = document.getElementById('addressCard');

  function toggleAddressFields() {
    if (pickupRadio.checked) {
      addressCard.style.display = 'none';
      // Remove required attributes from address fields
      addressCard.querySelectorAll('input').forEach(input => {
        input.removeAttribute('required');
      });
    } else {
      addressCard.style.display = 'block';
      // Add required attributes to address fields for COD
      const requiredFields = ['region', 'province', 'city', 'barangay', 'street'];
      requiredFields.forEach(fieldName => {
        const field = addressCard.querySelector(`input[name="${fieldName}"]`);
        if (field) field.setAttribute('required', 'required');
      });
    }
  }

  codRadio.addEventListener('change', toggleAddressFields);
  pickupRadio.addEventListener('change', toggleAddressFields);

  // Initialize
  toggleAddressFields();

  // Update order summary (shipping + total) when method changes
  async function fetchFeeAndUpdate() {
    const shipEl = document.getElementById('shippingAmount');
    if (!shipEl) return;
    const pickupFee = parseFloat(shipEl.dataset.pickupFee || '0');
    const subtotal = parseFloat(document.getElementById('subtotalValue')?.value || '0');
    const discount = parseFloat(document.getElementById('discountValue')?.value || '0');
    const method = (document.getElementById('pickup')?.checked) ? 'pickup' : 'cod';

    let fee = pickupFee; // default for pickup
    if (method === 'cod') {
      const cityInput = document.querySelector('input[name="city"]');
      const city = cityInput ? cityInput.value.trim() : '';
      try {
        const url = '/api/shipping-fee?method=cod&city=' + encodeURIComponent(city);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (res.ok) {
          const data = await res.json();
          if (typeof data.fee === 'number') fee = data.fee;
          else fee = parseFloat(shipEl.dataset.codFee || '0');
        } else {
          fee = parseFloat(shipEl.dataset.codFee || '0');
        }
      } catch (e) {
        fee = parseFloat(shipEl.dataset.codFee || '0');
      }
    }

    shipEl.textContent = fee > 0 ? '₱' + Number(fee).toFixed(2) : 'Free';
    const total = Math.max(0, subtotal - discount) + Number(fee);
    const totalEl = document.getElementById('totalAmount');
    if (totalEl) totalEl.textContent = '₱' + total.toFixed(2);
  }

  const updateSummary = () => fetchFeeAndUpdate();

  codRadio.addEventListener('change', updateSummary);
  pickupRadio.addEventListener('change', updateSummary);
  updateSummary();
  // Recalculate when city changes (debounced)
  const cityInput = document.querySelector('input[name="city"]');
  let debounceTimer;
  if (cityInput) {
    cityInput.addEventListener('input', function(){
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(updateSummary, 300);
    });
    cityInput.addEventListener('blur', updateSummary);
  }


  codRadio.addEventListener('change', toggleAddressFields);
  pickupRadio.addEventListener('change', toggleAddressFields);

  // Initialize
  toggleAddressFields();

  // Form validation
  const form = document.getElementById('checkoutForm');
  form.addEventListener('submit', function(e) {
    const requiredFields = form.querySelectorAll('input[required]');
    let isValid = true;

    requiredFields.forEach(field => {
      if (!field.value.trim()) {
        field.classList.add('is-invalid');
        isValid = false;
      } else {
        field.classList.remove('is-invalid');
      }
    });

    if (!isValid) {
      e.preventDefault();
      alert('Please fill in all required fields.');
    }
  });
});

function applyCoupon() {
  const couponInput = document.querySelector('input[name="coupon"]');
  const couponCode = couponInput.value.trim();

  if (!couponCode) {
    alert('Please enter a coupon code');
    return;
  }

  // Here you could add AJAX validation of the coupon
  // For now, just show a message
  alert('Coupon validation will be processed when you place your order.');
}
</script>

