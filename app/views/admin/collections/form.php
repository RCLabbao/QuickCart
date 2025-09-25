<?php use function App\Core\csrf_field; ?>
<?php include BASE_PATH . '/app/views/admin/_nav.php'; ?>
<h1 class="h4 mb-3"><?= isset($collection)?'Edit':'Create' ?> Collection</h1>
<form method="post" enctype="multipart/form-data" action="<?= isset($collection)?('/admin/collections/'.(int)$collection['id']):'/admin/collections' ?>">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= htmlspecialchars($collection['title'] ?? '') ?>" required></div>
  <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($collection['description'] ?? '') ?></textarea></div>
  <div class="mb-3">
    <label class="form-label">Cover Image</label>
    <input class="form-control" type="file" name="image" accept="image/*">
    <?php if (!empty($collection['image_url'])): ?>
      <div class="mt-2"><img src="<?= htmlspecialchars($collection['image_url']) ?>" class="rounded" style="max-width:200px"></div>
    <?php endif; ?>
  </div>

  <div class="mt-2"><button class="btn btn-dark">Save</button> <a class="btn btn-link" href="/admin/collections">Cancel</a></div>
</form>

