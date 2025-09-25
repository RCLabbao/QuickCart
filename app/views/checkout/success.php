<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-lg-6">
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

          <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a class="btn btn-primary btn-lg" href="/products">
              <i class="bi bi-arrow-left me-2"></i>Continue Shopping
            </a>
            <a class="btn btn-outline-secondary btn-lg" href="/admin/orders/<?= (int)$orderId ?>" target="_blank">
              <i class="bi bi-receipt me-2"></i>View Order
            </a>
          </div>

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
  </div>
</div>

