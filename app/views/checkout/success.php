<?php use function App\Core\e; ?>

<div>
      <!-- Progress Steps -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
              <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-check"></i>
              </div>
              <span class="ms-2 text-muted">Cart</span>
            </div>
            <div class="border-top mx-3" style="width: 50px;"></div>
            <div class="d-flex align-items-center">
              <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-check"></i>
              </div>
              <span class="ms-2 text-muted">Checkout</span>
            </div>
            <div class="border-top mx-3" style="width: 50px;"></div>
            <div class="d-flex align-items-center">
              <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-check"></i>
              </div>
              <span class="ms-2 fw-bold text-success">Complete</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Card -->
      <div class="card border-0 shadow-lg text-center">
        <div class="card-body py-5">
          <div class="mb-4">
            <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
              <i class="bi bi-check-lg fs-1"></i>
            </div>
          </div>

          <h1 class="h3 text-success mb-3">Order Placed Successfully!</h1>
          <p class="text-muted mb-4">Thank you for your purchase. Your order has been received and is being processed.</p>

          <div class="bg-light rounded p-4 mb-4">
            <h5 class="mb-2">Order Details</h5>
            <p class="mb-1"><strong>Order ID:</strong> #<?= (int)$orderId ?></p>
            <p class="mb-0 text-muted">You will receive an email confirmation shortly.</p>
          </div>

          <?php
            $slug = $slug ?? null;
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $orderPath = $slug ? '/orders/' . e($slug) : '/orders/' . ((int)$orderId) . '/' . e($token ?? '');
            $orderUrl = $scheme . '://' . $host . $orderPath;
          ?>
          <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a class="btn btn-primary btn-lg" href="/products">
              <i class="bi bi-arrow-left me-2"></i>Continue Shopping
            </a>
            <a class="btn btn-outline-secondary btn-lg" href="<?= e($orderPath) ?>" target="_blank">
              <i class="bi bi-receipt me-2"></i>View Order
            </a>
            <button type="button" id="copyLinkBtn" class="btn btn-outline-secondary btn-lg">
              <i class="bi bi-clipboard me-2"></i><span id="copyLinkText">Copy order link</span>
            </button>
          </div>
          <script>
            (function(){
              const btn = document.getElementById('copyLinkBtn');
              if (!btn) return;
              const label = document.getElementById('copyLinkText');
              const urlToCopy = <?= json_encode($orderUrl) ?>;
              btn.addEventListener('click', async function(){
                try {
                  if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(urlToCopy);
                  } else {
                    const ta = document.createElement('textarea');
                    ta.value = urlToCopy; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
                  }
                  if (label) { const prev = label.textContent; label.textContent = 'Copied!'; setTimeout(()=> label.textContent = prev, 1500); }
                } catch(e){ console.error('Copy failed', e); }
              });
            })();
          </script>

          <div class="mt-4 pt-4 border-top">
            <h6 class="text-muted mb-3">What happens next?</h6>
            <div class="row g-3 text-start">
              <div class="col-md-4">
                <div class="d-flex align-items-start">
                  <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                    1
                  </div>
                  <div>
                    <h6 class="mb-1">Order Confirmation</h6>
                    <small class="text-muted">We'll send you an email with order details</small>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="d-flex align-items-start">
                  <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                    2
                  </div>
                  <div>
                    <h6 class="mb-1">Processing</h6>
                    <small class="text-muted">We'll prepare your order for delivery</small>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="d-flex align-items-start">
                  <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                    3
                  </div>
                  <div>
                    <h6 class="mb-1">Delivery</h6>
                    <small class="text-muted">Your order will be delivered or ready for pickup</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
</div>

