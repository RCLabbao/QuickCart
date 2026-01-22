<?php use function App\Core\csrf_field; ?>

<!-- Banners Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Banner Slider</h1>
    <p class="text-muted mb-0">
      Manage homepage slider banners
      <?php if ($activeCount >= $maxBanners): ?>
        <span class="badge bg-danger ms-2">Limit Reached (<?= $activeCount ?>/<?= $maxBanners ?>)</span>
      <?php else: ?>
        <span class="badge bg-info ms-2"><?= $activeCount ?>/<?= $maxBanners ?> Active</span>
      <?php endif; ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-primary<?= $activeCount >= $maxBanners ? ' disabled' : '' ?>" href="/admin/banners/create">
      <i class="bi bi-plus-circle me-2"></i>Add Banner
    </a>
  </div>
</div>

<?php if (empty($banners)): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <i class="bi bi-images display-4 text-muted mb-3"></i>
      <h5 class="text-muted">No banners yet</h5>
      <p class="text-muted mb-3">Add your first banner to get started</p>
      <a class="btn btn-primary" href="/admin/banners/create">
        <i class="bi bi-plus-circle me-2"></i>Add Banner
      </a>
    </div>
  </div>
<?php else: ?>
  <!-- Banners Grid -->
  <div class="row g-3" id="banners-container">
    <?php foreach ($banners as $b): ?>
      <div class="col-12 col-md-6 col-lg-4 col-xl-3">
        <div class="card border-0 shadow-sm h-100 banner-card" data-banner-id="<?= (int)$b['id'] ?>" style="cursor: grab;">
          <div class="card-img-top position-relative" style="height: 180px; overflow: hidden;">
            <img src="<?= htmlspecialchars($b['image_url'] ?? '') ?>" alt="<?= htmlspecialchars($b['alt_text'] ?? $b['title']) ?>"
                 class="w-100 h-100" style="object-fit: cover;">
            <div class="position-absolute top-0 start-0 m-2">
              <span class="badge <?= ($b['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                <?= ucfirst($b['status'] ?? 'active') ?>
              </span>
            </div>
            <div class="position-absolute top-0 end-0 m-2">
              <i class="bi bi-grip-vertical text-white bg-dark bg-opacity-50 rounded p-1" style="cursor: grab;"></i>
            </div>
            <?php if (!empty($b['mobile_image_url'])): ?>
              <div class="position-absolute bottom-0 start-0 m-2">
                <span class="badge bg-info"><i class="bi bi-phone me-1"></i>Has Mobile</span>
              </div>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <h6 class="card-title mb-1 text-truncate"><?= htmlspecialchars($b['title']) ?></h6>
            <?php if (!empty($b['link_url'])): ?>
              <small class="text-muted d-block mb-2 text-truncate">
                <i class="bi bi-link-45deg me-1"></i><?= htmlspecialchars($b['link_url']) ?>
              </small>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">Sort: <?= (int)$b['sort_order'] ?></small>
            </div>
          </div>
          <div class="card-footer bg-white border-top">
            <div class="btn-group btn-group-sm w-100">
              <a class="btn btn-outline-primary" href="/admin/banners/<?= (int)$b['id'] ?>/edit">
                <i class="bi bi-pencil me-1"></i>Edit
              </a>
              <form method="post" action="/admin/banners/<?= (int)$b['id'] ?>/delete" onsubmit="return confirm('Delete this banner?');" class="d-inline">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<style>
.banner-card {
  transition: all 0.2s ease;
}
.banner-card.dragging {
  opacity: 0.5;
  transform: scale(0.95);
}
.banner-card:active {
  cursor: grabbing;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('banners-container');
  if (!container) return;

  let draggedItem = null;
  let placeholder = null;

  // Create placeholder element
  function createPlaceholder() {
    const div = document.createElement('div');
    div.className = 'col-12 col-md-6 col-lg-4 col-xl-3';
    div.innerHTML = '<div class="card border-0 shadow-sm h-100" style="border: 2px dashed #dee2e6; background: #f8f9fa;"><div class="card-body d-flex align-items-center justify-content-center" style="min-height: 280px;"><i class="bi bi-arrow-down-up text-muted"></i></div></div>';
    return div;
  }

  // Add drag handlers to each banner card
  const cards = container.querySelectorAll('.banner-card');
  cards.forEach(card => {
    card.setAttribute('draggable', 'true');

    card.addEventListener('dragstart', function(e) {
      draggedItem = this.closest('.col-12');
      this.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/html', this.innerHTML);
    });

    card.addEventListener('dragend', function() {
      this.classList.remove('dragging');
      if (placeholder && placeholder.parentNode) {
        placeholder.parentNode.removeChild(placeholder);
      }
      draggedItem = null;
      placeholder = null;

      // Save new order
      saveOrder();
    });

    card.addEventListener('dragover', function(e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';

      const cardColumn = this.closest('.col-12');
      if (cardColumn === draggedItem) return;

      // Get all card columns
      const columns = Array.from(container.children);
      const draggedIndex = columns.indexOf(draggedItem);
      const targetIndex = columns.indexOf(cardColumn);

      if (draggedIndex < targetIndex) {
        cardColumn.parentNode.insertBefore(draggedItem, cardColumn.nextSibling);
      } else {
        cardColumn.parentNode.insertBefore(draggedItem, cardColumn);
      }
    });
  });

  function saveOrder() {
    const order = [];
    container.querySelectorAll('.banner-card').forEach(card => {
      order.push(card.dataset.bannerId);
    });

    fetch('/admin/banners/reorder', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: '<?= csrf_field() ?>' + '&order=' + encodeURIComponent(JSON.stringify(order))
    }).then(() => {
      // Update sort order display
      container.querySelectorAll('.banner-card').forEach((card, index) => {
        const sortDisplay = card.querySelector('.card-body small.text-muted');
        if (sortDisplay && sortDisplay.textContent.includes('Sort:')) {
          sortDisplay.textContent = 'Sort: ' + index;
        }
      });
    });
  }
});
</script>
