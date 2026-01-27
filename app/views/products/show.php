<?php use function App\Core\price; use function App\Core\e; use function App\Core\is_on_sale; use function App\Core\effective_price; ?>
<div class="row g-4">
  <div class="col-md-6">
    <?php if (is_on_sale($product)): ?>
      <div class="mb-2">
        <span class="badge bg-danger fs-6 px-3 py-2">
          <i class="bi bi-tag-fill me-1"></i>ON SALE
        </span>
      </div>
    <?php endif; ?>

    <?php
    $images = !empty($gallery) ? $gallery : [];
    $hasMultipleImages = count($images) > 1;
    ?>

    <?php if (!empty($images)): ?>
      <div class="position-relative">
        <div id="productCarousel" class="carousel slide" data-bs-ride="false">
          <div class="carousel-inner">
            <?php foreach ($images as $index => $img): ?>
              <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                <div class="ratio ratio-1x1 bg-light rounded" style="overflow:hidden;">
                  <img loading="lazy"
                       src="<?= e($img['url']) ?>"
                       class="w-100 h-100 object-fit-cover"
                       alt="<?= e($product['title']) ?> - Image <?= $index + 1 ?>"
                       onclick="openImageModal(<?= $index ?>)"/>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if ($hasMultipleImages): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
              <div class="bg-dark bg-opacity-75 rounded-circle p-2">
                <i class="bi bi-chevron-left text-white"></i>
              </div>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
              <div class="bg-dark bg-opacity-75 rounded-circle p-2">
                <i class="bi bi-chevron-right text-white"></i>
              </div>
            </button>

            <div class="position-absolute bottom-0 end-0 m-3">
              <span class="badge bg-dark bg-opacity-75 px-3 py-2">
                <span id="currentImageIndex">1</span> / <?= count($images) ?>
              </span>
            </div>
          <?php endif; ?>

          <div class="position-absolute top-0 end-0 m-3">
            <button class="btn btn-dark btn-sm rounded-circle" onclick="openImageModal(0)" title="View full size">
              <i class="bi bi-arrows-fullscreen"></i>
            </button>
          </div>
        </div>
      </div>

      <?php if ($hasMultipleImages): ?>
      <div class="mt-3">
        <div class="d-flex gap-2 flex-wrap" id="thumbnailNav">
          <?php foreach ($images as $index => $img): ?>
            <div class="thumbnail-item <?= $index === 0 ? 'active' : '' ?>"
                 onclick="goToSlide(<?= $index ?>)"
                 style="cursor: pointer;">
              <img loading="lazy"
                   src="<?= e($img['url'] ?: ('https://picsum.photos/seed/'.(int)$product['id'].'/1000/1000')) ?>"
                   class="rounded border"
                   style="width:80px;height:80px;object-fit:cover;transition:all 0.2s ease;"
                   alt="Thumbnail <?= $index + 1 ?>"/>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content bg-transparent border-0">
            <div class="modal-header border-0 pb-0">
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
              <div id="modalCarousel" class="carousel slide" data-bs-ride="false">
                <div class="carousel-inner">
                  <?php foreach ($images as $index => $img): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                      <img src="<?= e($img['url']) ?>"
                           class="img-fluid rounded"
                           alt="<?= e($product['title']) ?> - Full Size"
                           style="max-height: 80vh; object-fit: contain;"/>
                    </div>
                  <?php endforeach; ?>
                </div>

                <?php if ($hasMultipleImages): ?>
                  <button class="carousel-control-prev" type="button" data-bs-target="#modalCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                  </button>
                  <button class="carousel-control-next" type="button" data-bs-target="#modalCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

    <?php else: ?>
      <div class="ratio ratio-1x1 bg-light rounded d-flex align-items-center justify-content-center">
        <div class="text-center">
          <i class="bi bi-image text-muted" style="font-size: 64px;"></i>
          <p class="text-muted mt-3">No images available</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-md-6">
    <h1 class="h3 mb-1"><?= e($product['title']) ?></h1>
    <?php $stk=(int)($product['stock']??0); ?>
    <div class="small mb-2">
      <?php if($stk<=0): ?>
        <span class="badge bg-secondary">Out of stock</span>
      <?php elseif($stk<=3): ?>
        <span class="badge bg-warning text-dark">Low stock: <?= $stk ?> units</span>
      <?php else: ?>
        <span class="badge bg-success text-white">In stock: <?= $stk ?> units</span>
      <?php endif; ?>
      <span class="text-muted"> • Free pickup available</span>
    </div>
    <div class="fs-3 fw-bold mb-3">
      <?php if (is_on_sale($product)): ?>
        <span class="text-danger me-2"><?= price(effective_price($product)) ?></span>
        <s class="text-muted fs-5"><?= price((float)$product['price']) ?></s>
      <?php elseif ((float)($product['price'] ?? 0) > 0): ?>
        <?= price((float)$product['price']) ?>
      <?php endif; ?>
    </div>

    <?php if (!empty($variants) && $hasVariants): ?>
    <!-- Variant Selector -->
    <div class="mb-3 p-3 bg-light rounded">
      <h6 class="mb-2"><i class="bi bi-diagram-3 me-1"></i>Select Variant:</h6>
      <div class="d-flex flex-wrap gap-2" id="variantSelector">
        <?php foreach ($variants as $v): ?>
          <?php
          $isSelected = (int)$v['id'] === (int)$product['id'];
          $variantId = (int)$v['id'];
          $variantAttr = htmlspecialchars($v['variant_attributes'] ?? '');
          $variantPrice = (float)$v['price'];
          $variantStock = (int)$v['stock'];
          $outOfStock = $variantStock <= 0;
          ?>
          <button class="variant-btn btn btn-sm <?= $isSelected ? 'btn-dark' : 'btn-outline-dark' ?>"
                  data-variant-id="<?= $variantId ?>"
                  data-variant-price="<?= $variantPrice ?>"
                  data-variant-stock="<?= $variantStock ?>"
                  data-variant-fsc="<?= htmlspecialchars($v['fsc'] ?? '') ?>"
                  <?= $outOfStock ? 'disabled' : '' ?>>
            <span class="badge bg-info"><?= $variantAttr ?></span>
            <?php if (!$isSelected): ?>
              <small class="d-block text-muted">₱<?= number_format($variantPrice, 2) ?></small>
            <?php endif; ?>
            <?php if ($outOfStock): ?>
              <small class="d-block text-danger">Out of stock</small>
            <?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    <form id="pdpAddToCartForm" class="addToCart mb-3" method="post" action="/cart/add">
      <?= \App\Core\csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"/>
      <div class="input-group">
        <button class="btn btn-outline-secondary" type="button" data-qty="-1" <?= $stk<=0?'disabled':'' ?>>-</button>
        <input class="form-control text-center" name="qty" value="1" style="max-width:80px" <?= $stk<=0?'disabled':'' ?>>
        <button class="btn btn-outline-secondary" type="button" data-qty="+1" <?= $stk<=0?'disabled':'' ?>>+</button>
        <button class="btn btn-dark ms-2" type="submit" <?= $stk<=0?'disabled':'' ?>><?= $stk<=0?'Sold out':'Add to cart' ?></button>
      </div>
    </form>
    <div class="row trust-badges g-3 mt-3">
      <div class="col-6 col-md-4 d-flex align-items-center gap-2"><i class="bi bi-box-seam"></i><div>Cash on Delivery</div></div>
      <div class="col-6 col-md-4 d-flex align-items-center gap-2"><i class="bi bi-shop"></i><div>Pickup in Store</div></div>
      <div class="col-6 col-md-4 d-flex align-items-center gap-2"><i class="bi bi-arrow-repeat"></i><div>Easy Returns</div></div>
    </div>
    <?php $desc = trim((string)($product['description'] ?? '')); if ($desc !== ''): ?>
    <div class="mt-4">
      <h2 class="h5">Product details</h2>
      <p><?= nl2br(e($product['description'])) ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="sticky-cta d-md-none">
  <button class="btn btn-dark w-100" data-submit-form="#pdpAddToCartForm">Add to cart</button>
</div>

<style>
.thumbnail-item {
  transition: all 0.2s ease;
  opacity: 0.7;
}

.thumbnail-item.active {
  opacity: 1;
  transform: scale(1.05);
}

.thumbnail-item:hover {
  opacity: 1;
  transform: scale(1.02);
}

.carousel-control-prev,
.carousel-control-next {
  width: auto;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.carousel:hover .carousel-control-prev,
.carousel:hover .carousel-control-next {
  opacity: 1;
}

.carousel-control-prev {
  left: 10px;
}

.carousel-control-next {
  right: 10px;
}

.carousel-item img {
  cursor: zoom-in;
  transition: transform 0.2s ease;
}

.carousel-item img:hover {
  transform: scale(1.02);
}

#imageModal .modal-content {
  background: rgba(0, 0, 0, 0.9) !important;
}

#imageModal .carousel-control-prev,
#imageModal .carousel-control-next {
  opacity: 0.8;
}

#imageModal .carousel-control-prev:hover,
#imageModal .carousel-control-next:hover {
  opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const productCarousel = document.getElementById('productCarousel');
  const modalCarousel = document.getElementById('modalCarousel');
  const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
  const currentImageIndex = document.getElementById('currentImageIndex');
  const thumbnails = document.querySelectorAll('.thumbnail-item');

  // Update image counter and thumbnail active state
  function updateImageDisplay(index) {
    if (currentImageIndex) {
      currentImageIndex.textContent = index + 1;
    }

    // Update thumbnail active state
    thumbnails.forEach((thumb, i) => {
      thumb.classList.toggle('active', i === index);
    });
  }

  // Go to specific slide
  window.goToSlide = function(index) {
    const carousel = bootstrap.Carousel.getInstance(productCarousel);
    carousel.to(index);
    updateImageDisplay(index);
  };

  // Open image modal at specific index
  window.openImageModal = function(index) {
    const modalCarouselInstance = bootstrap.Carousel.getInstance(modalCarousel) || new bootstrap.Carousel(modalCarousel);
    modalCarouselInstance.to(index);
    imageModal.show();
  };

  // Listen for carousel slide events
  if (productCarousel) {
    productCarousel.addEventListener('slid.bs.carousel', function(event) {
      updateImageDisplay(event.to);
    });
  }

  // Sync modal carousel with main carousel
  if (modalCarousel) {
    modalCarousel.addEventListener('slid.bs.carousel', function(event) {
      const mainCarousel = bootstrap.Carousel.getInstance(productCarousel);
      if (mainCarousel) {
        mainCarousel.to(event.to);
      }
    });
  }

  // Keyboard navigation for modal
  document.addEventListener('keydown', function(e) {
    if (document.getElementById('imageModal').classList.contains('show')) {
      const modalCarouselInstance = bootstrap.Carousel.getInstance(modalCarousel);
      if (e.key === 'ArrowLeft') {
        modalCarouselInstance.prev();
      } else if (e.key === 'ArrowRight') {
        modalCarouselInstance.next();
      } else if (e.key === 'Escape') {
        imageModal.hide();
      }
    }
  });

  // Initialize carousel instances
  if (productCarousel) {
    new bootstrap.Carousel(productCarousel, {
      interval: false, // Disable auto-play
      wrap: true
    });
  }

  if (modalCarousel) {
    new bootstrap.Carousel(modalCarousel, {
      interval: false,
      wrap: true
    });
  }

  // Variant Selection
  const variantBtns = document.querySelectorAll('.variant-btn');
  variantBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const variantId = this.dataset.variantId;
      const variantPrice = parseFloat(this.dataset.variantPrice);
      const variantStock = parseInt(this.dataset.variantStock);
      const variantFsc = this.dataset.variantFsc;

      // Update selected button style
      variantBtns.forEach(b => {
        b.classList.remove('btn-dark');
        b.classList.add('btn-outline-dark');
      });
      this.classList.remove('btn-outline-dark');
      this.classList.add('btn-dark');

      // Update form
      const form = document.getElementById('pdpAddToCartForm');
      const productIdInput = form.querySelector('input[name="product_id"]');
      const qtyInput = form.querySelector('input[name="qty"]');
      const submitBtn = form.querySelector('button[type="submit"]');

      productIdInput.value = variantId;

      // Update price display and add to cart button
      const priceDisplay = document.querySelector('.fs-3.fw-bold');
      const outOfStock = variantStock <= 0;

      // Create new price HTML
      let priceHtml = '';
      if (outOfStock) {
        priceHtml = '<span class="text-danger me-2">₱' + number_format(variantPrice, 2) + '</span>';
      } else {
        priceHtml = '₱' + number_format(variantPrice, 2);
      }
      priceDisplay.innerHTML = priceHtml;

      // Update add to cart button
      submitBtn.textContent = outOfStock ? 'Sold out' : 'Add to cart';
      submitBtn.disabled = outOfStock;
      qtyInput.disabled = outOfStock;

      // Update quantity buttons
      const qtyBtns = form.querySelectorAll('button[data-qty]');
      qtyBtns.forEach(qtyBtn => {
        qtyBtn.disabled = outOfStock;
      });

      // Update stock badge if exists
      const stockInfo = document.querySelector('.small.mb-2');
      if (stockInfo) {
        if (outOfStock) {
          stockInfo.innerHTML = '<span class="badge bg-secondary">Out of stock</span><span class="text-muted"> • Free pickup available</span>';
        } else if (variantStock <= 3) {
          stockInfo.innerHTML = '<span class="badge bg-warning text-dark">Low stock: ' + variantStock + ' units</span><span class="text-muted"> • Free pickup available</span>';
        } else {
          stockInfo.innerHTML = '<span class="badge bg-success text-white">In stock: ' + variantStock + ' units</span><span class="text-muted"> • Free pickup available</span>';
        }
      }

      // Update page URL without reload
      const newUrl = '/products/' + (variantFsc || variantId);
      window.history.replaceState({}, '', newUrl);
    });
  });

  // Helper function for number formatting
  function number_format(number, decimals) {
    return number.toLocaleString('en-US', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    });
  }
});
</script>