<?php use function App\Core\csrf_field; ?>
<?php include BASE_PATH . '/app/views/admin/_nav.php'; ?>

<h1 class="h4 mb-3"><?= isset($product)?'Edit':'Add' ?> Product</h1>
<form method="post" enctype="multipart/form-data" action="<?= isset($product)?('/admin/products/'.(int)$product['id']):'/admin/products' ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input class="form-control" name="title" value="<?= htmlspecialchars($product['title'] ?? '') ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea class="form-control" rows="5" name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
  </div>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Price</label>
      <input class="form-control" type="number" step="0.01" name="price" value="<?= htmlspecialchars((string)($product['price'] ?? '0.00')) ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Stock</label>
      <input class="form-control" type="number" min="0" name="stock" value="<?= htmlspecialchars((string)($product['stock'] ?? '0')) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select class="form-select" name="status">
        <?php $st=$product['status'] ?? 'active'; ?>
        <option value="active" <?= $st==='active'?'selected':'' ?>>Active</option>
        <option value="draft" <?= $st==='draft'?'selected':'' ?>>Draft</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Collection</label>
      <select class="form-select" name="collection_id">
        <option value="">— None —</option>
        <?php foreach (($collections ?? []) as $c): $sel = isset($product['collection_id']) && (int)$product['collection_id']===(int)$c['id']; ?>
          <option value="<?= (int)$c['id'] ?>" <?= $sel?'selected':'' ?>><?= htmlspecialchars($c['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="row g-3 mt-1">
    <div class="col-md-3">
      <label class="form-label">Sale price</label>
      <input class="form-control" type="number" step="0.01" name="sale_price" value="<?= htmlspecialchars((string)($product['sale_price'] ?? '')) ?>" placeholder="Leave blank for none">
    </div>
    <div class="col-md-3">
      <label class="form-label">Sale start</label>
      <input class="form-control" type="datetime-local" name="sale_start" value="<?= htmlspecialchars(isset($product['sale_start'])?str_replace(' ','T',$product['sale_start']):'') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Sale end</label>
      <input class="form-control" type="datetime-local" name="sale_end" value="<?= htmlspecialchars(isset($product['sale_end'])?str_replace(' ','T',$product['sale_end']):'') ?>">
    </div>
  </div>
  <div class="mb-3 mt-3">
    <label class="form-label">Tags (comma-separated)</label>
    <input class="form-control" type="text" name="tags" value="<?= htmlspecialchars($tagsCsv ?? '') ?>" placeholder="e.g. summer, clearance, gift">
  </div>


  <div class="mb-3">
    <label class="form-label">Images</label>
    <input class="form-control" type="file" name="images[]" accept="image/*" multiple>
    <?php if (!empty($images)): ?>
      <div class="d-flex flex-wrap gap-2 mt-2">
        <?php foreach ($images as $img): ?>
          <div class="position-relative img-thumb" style="width:100px" draggable="true" data-img-id="<?= (int)$img['id'] ?>">
            <img src="<?= htmlspecialchars($img['url']) ?>" class="rounded w-100" alt="">
            <form method="post" action="/admin/products/<?= (int)$product['id'] ?>/images/<?= (int)$img['id'] ?>/delete" class="position-absolute top-0 end-0 m-1">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-danger">&times;</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
      <form method="post" action="/admin/products/<?= (int)$product['id'] ?>/images/sort" class="mt-2" id="imgSortForm">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-secondary">Save Image Order</button>
      </form>
      <script>
      (function(){
        const wrap = document.currentScript.previousElementSibling.previousElementSibling; // the thumbs container
        let dragSrc;


        wrap.addEventListener('dragstart', e=>{ const t=e.target.closest('.img-thumb'); if(!t) return; dragSrc=t; e.dataTransfer.effectAllowed='move'; });
        wrap.addEventListener('dragover', e=>{ e.preventDefault(); const t=e.target.closest('.img-thumb'); if(!t||t===dragSrc) return; const rect=t.getBoundingClientRect(); const next=(e.clientX - rect.left)/(rect.width) > .5; wrap.insertBefore(dragSrc, next? t.nextSibling : t); });
        document.getElementById('imgSortForm').addEventListener('submit', function(e){
          const ids=[...wrap.querySelectorAll('.img-thumb')].map(el=>el.dataset.imgId);
          ids.forEach(id=>{ const inp=document.createElement('input'); inp.type='hidden'; inp.name='order[]'; inp.value=id; this.appendChild(inp); });
        });
      })();
      </script>
    <?php endif; ?>
  </div>
  <div class="mt-3 d-flex gap-2 align-items-center">
    <button class="btn btn-dark">Save</button>
    <a class="btn btn-link" href="/admin/products">Cancel</a>
    <?php if (!empty($product)): ?>
      <form method="post" action="/admin/products/<?= (int)$product['id'] ?>/duplicate" onsubmit="return confirm('Duplicate this product?')">
        <?= csrf_field() ?>
        <button class="btn btn-outline-secondary" type="submit">Duplicate</button>
      </form>
    <?php endif; ?>
  </div>
</form>
<?php if (!empty($events)): ?>
<hr>
<h2 class="h6">Recent stock changes</h2>
<table class="table table-sm">
  <thead><tr><th>Date</th><th>Delta</th><th>Reason</th></tr></thead>
  <tbody>
    <?php foreach ($events as $ev): ?>
      <tr>
        <td><?= htmlspecialchars($ev['created_at']) ?></td>
        <td><?= (int)$ev['delta'] ?></td>
        <td><?= htmlspecialchars($ev['reason'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>


