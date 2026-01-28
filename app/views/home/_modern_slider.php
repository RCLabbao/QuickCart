<!-- Modern Banner Carousel - Each image becomes a slide -->
<?php if (!empty($banners ?? [])): ?>
<section class="banner-carousel-section my-4 my-md-5">
  <div class="banner-carousel" id="modernBannerSlider">
    <!-- Carousel Track -->
    <div class="carousel-track-container">
      <div class="carousel-track">
        <?php foreach (($banners ?? []) as $b): ?>
          <?php
            $carouselImages = $b['carousel_images'] ?? [];
            // If banner has carousel_images, use each as a separate slide
            // Otherwise use the single image_url
            $imagesToUse = !empty($carouselImages) ? $carouselImages : [$b['image_url'] ?? ''];
            $linkUrl = $b['link_url'] ?? '';
            $altText = $b['alt_text'] ?? $b['title'] ?? 'Banner';
            $bannerId = $b['id'];
          ?>
          <?php foreach ($imagesToUse as $img): ?>
            <div class="carousel-slide">
              <?php if (!empty($linkUrl)): ?>
                <a href="<?= htmlspecialchars($linkUrl) ?>" class="banner-card banner-card-link">
              <?php else: ?>
                <div class="banner-card">
              <?php endif; ?>
                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($altText) ?>" loading="lazy">
              <?php if (!empty($linkUrl)): ?>
                </a>
              <?php else: ?>
              </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Navigation Arrows -->
    <button class="carousel-nav carousel-nav-prev" type="button" aria-label="Previous">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"></polyline>
      </svg>
    </button>
    <button class="carousel-nav carousel-nav-next" type="button" aria-label="Next">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="9 18 15 12 9 6"></polyline>
      </svg>
    </button>

    <!-- Dots Indicators -->
    <div class="carousel-dots"></div>
  </div>
</section>

<style>
/* Banner Carousel Section */
.banner-carousel-section {
  width: 100%;
  padding: 0 1rem;
}

.banner-carousel {
  position: relative;
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
}

/* Track Container - clips the overflow */
.carousel-track-container {
  overflow: hidden;
  width: 100%;
  padding: 0.5rem 0;
}

/* Track - holds all slides and moves */
.carousel-track {
  display: flex;
  gap: 1rem;
  transition: transform 0.5s ease-out;
  will-change: transform;
}

/* Individual Slide */
.carousel-slide {
  flex: 0 0 auto;
  width: calc(50% - 0.5rem); /* 2 slides visible on desktop */
}

@media (min-width: 992px) {
  .carousel-slide {
    width: calc(33.333% - 0.67rem); /* 3 slides visible on large screens */
  }
}

@media (max-width: 768px) {
  .carousel-slide {
    width: calc(100% - 0.5rem); /* 1 slide on mobile */
  }
}

/* Banner Card */
.banner-card {
  display: block;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  position: relative;
}

.banner-card-link {
  color: inherit;
  text-decoration: none;
}

.banner-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.banner-card > img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* Navigation Arrows */
.carousel-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 44px;
  height: 44px;
  border: none;
  background: #fff;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #212529;
  transition: all 0.3s ease;
  z-index: 20;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
}

.carousel-nav:hover {
  background: var(--bs-primary);
  color: #fff;
  transform: translateY(-50%) scale(1.1);
}

.carousel-nav:active {
  transform: translateY(-50%) scale(0.95);
}

.carousel-nav-prev {
  left: -22px;
}

.carousel-nav-next {
  right: -22px;
}

@media (max-width: 1200px) {
  .carousel-nav-prev {
    left: 0;
  }
  .carousel-nav-next {
    right: 0;
  }
}

@media (max-width: 768px) {
  .carousel-nav {
    width: 36px;
    height: 36px;
  }

  .carousel-nav-prev {
    left: -10px;
  }

  .carousel-nav-next {
    right: -10px;
  }
}

/* Dots Indicators */
.carousel-dots {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1rem;
}

.carousel-dot {
  width: 8px;
  height: 8px;
  border: none;
  border-radius: 50%;
  background: #dee2e6;
  cursor: pointer;
  transition: all 0.3s ease;
  padding: 0;
}

.carousel-dot:hover {
  background: #adb5bd;
}

.carousel-dot.active {
  background: var(--bs-primary);
  width: 24px;
  border-radius: 4px;
}

/* Touch support */
.banner-carousel {
  touch-action: pan-y;
  user-select: none;
}
</style>

<script>
(function() {
  const carousel = document.getElementById('modernBannerSlider');
  if (!carousel) return;

  const track = carousel.querySelector('.carousel-track');
  const slides = carousel.querySelectorAll('.carousel-slide');
  const prevBtn = carousel.querySelector('.carousel-nav-prev');
  const nextBtn = carousel.querySelector('.carousel-nav-next');
  const dotsContainer = carousel.querySelector('.carousel-dots');

  const totalSlides = slides.length;
  if (totalSlides === 0) return;

  // Calculate slides per viewport based on screen width
  function getSlidesPerView() {
    if (window.innerWidth >= 992) return 3;
    if (window.innerWidth >= 768) return 2;
    return 1;
  }

  let currentIndex = 0;
  let slidesPerView = getSlidesPerView();
  let autoPlayInterval;
  const autoPlayDelay = 4000;

  // Create dots
  function createDots() {
    const totalPages = Math.ceil(totalSlides / slidesPerView);
    dotsContainer.innerHTML = '';

    for (let i = 0; i < totalPages; i++) {
      const dot = document.createElement('button');
      dot.className = 'carousel-dot';
      dot.type = 'button';
      dot.ariaLabel = `Go to slide ${i + 1}`;
      if (i === 0) dot.classList.add('active');

      dot.addEventListener('click', () => {
        goToPage(i);
        resetAutoPlay();
      });

      dotsContainer.appendChild(dot);
    }
  }

  // Update track position
  function updateTrack() {
    const slideWidth = slides[0].offsetWidth + 16; // width + gap
    const maxIndex = Math.max(0, totalSlides - slidesPerView);
    currentIndex = Math.min(currentIndex, maxIndex);

    track.style.transform = `translateX(-${currentIndex * slideWidth}px)`;

    // Update dots
    const currentPage = Math.floor(currentIndex / slidesPerView);
    const dots = dotsContainer.querySelectorAll('.carousel-dot');
    dots.forEach((dot, index) => {
      dot.classList.toggle('active', index === currentPage);
    });

    // Update button states
    prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
    prevBtn.style.pointerEvents = currentIndex === 0 ? 'none' : 'auto';
    nextBtn.style.opacity = currentIndex >= maxIndex ? '0.5' : '1';
    nextBtn.style.pointerEvents = currentIndex >= maxIndex ? 'none' : 'auto';
  }

  // Next slide
  function nextSlide() {
    const maxIndex = Math.max(0, totalSlides - slidesPerView);
    if (currentIndex >= maxIndex) {
      currentIndex = 0; // Loop back to start
    } else {
      currentIndex++;
    }
    updateTrack();
  }

  // Previous slide
  function prevSlide() {
    if (currentIndex <= 0) {
      currentIndex = Math.max(0, totalSlides - slidesPerView); // Loop to end
    } else {
      currentIndex--;
    }
    updateTrack();
  }

  // Go to specific page
  function goToPage(pageIndex) {
    currentIndex = pageIndex * slidesPerView;
    updateTrack();
  }

  // Auto play
  function startAutoPlay() {
    autoPlayInterval = setInterval(nextSlide, autoPlayDelay);
  }

  function stopAutoPlay() {
    clearInterval(autoPlayInterval);
  }

  function resetAutoPlay() {
    stopAutoPlay();
    startAutoPlay();
  }

  // Event listeners
  prevBtn.addEventListener('click', () => {
    prevSlide();
    resetAutoPlay();
  });

  nextBtn.addEventListener('click', () => {
    nextSlide();
    resetAutoPlay();
  });

  // Keyboard navigation
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
      prevSlide();
      resetAutoPlay();
    } else if (e.key === 'ArrowRight') {
      nextSlide();
      resetAutoPlay();
    }
  });

  // Touch swipe support
  let touchStartX = 0;
  let touchEndX = 0;

  track.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
    stopAutoPlay();
  }, { passive: true });

  track.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
    startAutoPlay();
  }, { passive: true });

  function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;

    if (Math.abs(diff) > swipeThreshold) {
      if (diff > 0) {
        nextSlide();
      } else {
        prevSlide();
      }
    }
  }

  // Handle resize
  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      const newSlidesPerView = getSlidesPerView();
      if (newSlidesPerView !== slidesPerView) {
        slidesPerView = newSlidesPerView;
        createDots();
        currentIndex = 0;
      }
      updateTrack();
    }, 250);
  });

  // Pause on hover
  carousel.addEventListener('mouseenter', stopAutoPlay);
  carousel.addEventListener('mouseleave', startAutoPlay);

  // Initialize
  createDots();
  updateTrack();
  startAutoPlay();
})();
</script>
<?php endif; ?>
