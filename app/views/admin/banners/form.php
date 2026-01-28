<?php use function App\Core\csrf_field; ?>

<!-- Banner Form Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?= isset($banner) ? 'Edit Banner' : 'Add Banner' ?></h1>
    <p class="text-muted mb-0">
      <?= isset($banner) ? 'Update banner details and images' : 'Add a new banner with multiple images for auto-carousel' ?>
    </p>
  </div>
  <div>
    <a class="btn btn-outline-secondary" href="/admin/banners">
      <i class="bi bi-arrow-left me-2"></i>Back to Banners
    </a>
  </div>
</div>

<form method="post" enctype="multipart/form-data" action="<?= isset($banner) ? '/admin/banners/' . (int)$banner['id'] : '/admin/banners' ?>" class="row g-4">
  <?= csrf_field() ?>
  <?php if (isset($banner)): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0"><i class="bi bi-card-image me-2"></i>Banner Details</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Title</label>
          <input class="form-control" name="title" value="<?= htmlspecialchars($banner['title'] ?? '') ?>" required placeholder="e.g. Summer Sale 2024">
          <div class="form-text">Internal name for identifying this banner</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Link URL (optional)</label>
          <input class="form-control" name="link_url" value="<?= htmlspecialchars($banner['link_url'] ?? '') ?>" placeholder="e.g. /products/summer-sale">
          <div class="form-text">Where users go when they click the banner. Use relative URLs like /products/xyz or external URLs like https://example.com</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Alt Text (optional)</label>
          <input class="form-control" name="alt_text" value="<?= htmlspecialchars($banner['alt_text'] ?? '') ?>" placeholder="e.g. Summer sale banner with discounted products">
          <div class="form-text">Descriptive text for accessibility and SEO</div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="mb-3 mb-md-0">
              <label class="form-label fw-semibold">Sort Order</label>
              <input class="form-control" type="number" name="sort_order" value="<?= (int)($banner['sort_order'] ?? 0) ?>" min="0" max="1000">
              <div class="form-text">Lower numbers appear first. You can also drag to reorder on the list page.</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-0">
              <label class="form-label fw-semibold">Status</label>
              <select class="form-select" name="status" <?= (!isset($banner) && $activeCount >= $maxBanners) ? 'disabled' : '' ?>>
                <option value="active" <?= (isset($banner['status']) && $banner['status'] === 'active') ? 'selected' : (!isset($banner['status']) ? 'selected' : '') ?>>Active</option>
                <option value="draft" <?= (isset($banner['status']) && $banner['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
              </select>
              <?php if (!isset($banner) && $activeCount >= $maxBanners): ?>
                <input type="hidden" name="status" value="draft">
                <div class="form-text text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Maximum active banners reached. New banner will be draft.</div>
              <?php else: ?>
                <div class="form-text">Active banners are shown on the homepage. Draft banners are hidden.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0"><i class="bi bi-images me-2"></i>Carousel Images</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Select Images <span class="text-danger">*</span></label>
          <input class="form-control" type="file" name="images[]" accept="image/*" multiple <?= !isset($banner) ? 'required' : '' ?>>
          <div class="form-text">
            Select multiple images to create an auto-carousel. Recommended: 1920x600px for desktop, 1:1 ratio for square cards. JPEG, PNG, or WEBP. Max 10 MB each.
          </div>
        </div>

        <?php if (!empty($bannerImages)): ?>
          <div class="mb-3">
            <label class="form-label text-muted small">Current Carousel Images (<?= count($bannerImages) ?>):</label>
            <div class="row g-2" id="existingImages">
              <?php foreach ($bannerImages as $img): ?>
                <div class="col-4 col-md-3 position-relative">
                  <div class="border rounded p-1 bg-light position-relative">
                    <img src="<?= htmlspecialchars($img['url']) ?>" class="img-fluid rounded" alt="Banner image" style="aspect-ratio: 1/1; object-fit: cover;">
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle"
                            onclick="deleteBannerImage(<?= (int)$img['id'] ?>, this)"
                            style="width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center;">
                      <i class="bi bi-x" style="font-size: 14px;"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="form-text mt-2">
              <i class="bi bi-info-circle me-1"></i>Upload new images to replace all existing images.
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i><?= isset($banner) ? 'Update Banner' : 'Add Banner' ?>
          </button>
          <a class="btn btn-outline-secondary" href="/admin/banners">
            <i class="bi bi-x-circle me-2"></i>Cancel
          </a>
        </div>
      </div>
    </div>
  </div>
</form>

<style>
.card { transition: all 0.2s ease; }
.card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; }
.form-control:focus, .form-select:focus { border-color: var(--bs-primary); box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25); }
#existingImages img { transition: transform 0.2s ease; }
#existingImages img:hover { transform: scale(1.05); }
</style>

<script>
// Generic form handler - disable button on submit to prevent double submissions
document.querySelector('form').addEventListener('submit', function() {
  const submitButton = this.querySelector('button[type="submit"]');
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Saving...';
  }
});

// Delete banner image
function deleteBannerImage(imageId, button) {
  if (!confirm('Remove this image from the banner carousel?')) return;

  const container = button.closest('.col-4') || button.closest('.col-md-3');

  fetch('/admin/banners/images/' + imageId + '/delete', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: '<?= csrf_field() ?>'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      container.remove();
      // Update count
      const label = document.querySelector('#existingImages').previousElementSibling;
      const currentCount = label.textContent.match(/\d+/);
      if (currentCount) {
        const newCount = parseInt(currentCount[0]) - 1;
        if (newCount > 0) {
          label.textContent = label.textContent.replace(/\d+/, newCount);
        } else {
          label.parentElement.remove();
        }
      }
    } else {
      alert('Failed to delete image: ' + (data.error || 'Unknown error'));
    }
  })
  .catch(error => {
    alert('Error deleting image: ' + error.message);
  });
}
</script>
