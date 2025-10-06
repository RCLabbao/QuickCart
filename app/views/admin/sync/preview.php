<?php /** @var array $result */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">CSV/XLSX Dry-run Preview</h1>
    <p class="text-muted mb-0">No data has been saved. Review the planned changes below.</p>
  </div>
  <div>
    <a class="btn btn-secondary" href="/admin/sync"><i class="bi bi-arrow-left me-2"></i>Back to Sync</a>
  </div>
</div>

<?php $counts = ['seen'=>$result['seen']??0,'created'=>$result['created']??0,'updated'=>$result['updated']??0,'errors'=>$result['errors']??0]; ?>
<div class="row g-3 mb-3">
  <div class="col-auto"><span class="badge bg-secondary">Seen: <?= (int)$counts['seen'] ?></span></div>
  <div class="col-auto"><span class="badge bg-success">Create: <?= (int)$counts['created'] ?></span></div>
  <div class="col-auto"><span class="badge bg-primary">Update: <?= (int)$counts['updated'] ?></span></div>
  <div class="col-auto"><span class="badge bg-danger">Errors/Skipped: <?= (int)$counts['errors'] ?></span></div>
</div>

<?php if (!empty($result['debug'])): $dbg = $result['debug']; ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-bottom"><h5 class="mb-0">Parser Debug</h5></div>
    <div class="card-body">
      <?php if (!empty($dbg['info'])): ?>
        <div class="mb-2 small text-muted">
          <?php foreach ($dbg['info'] as $k => $v): ?>
            <div><strong><?= htmlspecialchars($k) ?>:</strong> <code><?= htmlspecialchars(is_array($v)? json_encode($v) : (string)$v) ?></code></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($dbg['rows'])): ?>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead class="table-light"><tr>
              <th>FSC</th><th>Category (raw)</th><th>Slug</th><th>Found Coll</th><th>Action</th><th>Reason</th>
            </tr></thead>
            <tbody>
              <?php foreach (array_slice($dbg['rows'],0,200) as $r): ?>
                <tr>
                  <td><code><?= htmlspecialchars((string)($r['fsc'] ?? '')) ?></code></td>
                  <td><?= htmlspecialchars((string)($r['category_raw'] ?? '')) ?></td>
                  <td><code><?= htmlspecialchars((string)($r['slug'] ?? '')) ?></code></td>
                  <td><?= htmlspecialchars((string)($r['collection'] ?? '-')) ?></td>
                  <td><?= htmlspecialchars((string)($r['action'] ?? '')) ?></td>
                  <td class="small text-muted"><?= htmlspecialchars((string)($r['reason'] ?? '')) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php $rows = array_slice(($result['preview'] ?? []), 0, 200); ?>
<?php if (!$rows): ?>
  <div class="alert alert-info">Nothing to preview.</div>
<?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom"><h5 class="mb-0">Planned Changes (showing first <?= count($rows) ?> rows)</h5></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>FSC</th>
              <th>Action</th>
              <th>Title</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Collection</th>
              <th>Categorycode</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <?php
              $titleChanged = ($r['title_old'] ?? null) !== ($r['title_new'] ?? null);
              $priceChanged = (string)($r['price_old'] ?? '') !== (string)($r['price_new'] ?? '');
              $stockChanged = (string)($r['stock_old'] ?? '') !== (string)($r['stock_new'] ?? '');
              $collChanged = ($r['collection_old'] ?? null) !== ($r['collection_new'] ?? null);
            ?>
            <tr>
              <td><code><?= htmlspecialchars((string)($r['fsc'] ?? '')) ?></code></td>
              <td>
                <?php if (($r['action'] ?? '') === 'create'): ?>
                  <span class="badge bg-success">Create</span>
                <?php elseif (($r['action'] ?? '') === 'update'): ?>
                  <span class="badge bg-primary">Update</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Other</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($titleChanged): ?>
                  <span class="text-muted"><?= htmlspecialchars((string)($r['title_old'] ?? '')) ?></span>
                  <span class="mx-1">→</span>
                  <span class="fw-semibold text-danger"><?= htmlspecialchars((string)($r['title_new'] ?? '')) ?></span>
                <?php else: ?>
                  <?= htmlspecialchars((string)($r['title_new'] ?? $r['title_old'] ?? '')) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($priceChanged): ?>
                  <span class="text-muted"><?= is_null($r['price_old'] ?? null) ? '-' : number_format((float)$r['price_old'], 2) ?></span>
                  <span class="mx-1">→</span>
                  <span class="fw-semibold text-danger"><?= number_format((float)($r['price_new'] ?? 0), 2) ?></span>
                <?php else: ?>
                  <?= is_null($r['price_new'] ?? null) ? '-' : number_format((float)$r['price_new'], 2) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($stockChanged): ?>
                  <span class="text-muted"><?= is_null($r['stock_old'] ?? null) ? '-' : (int)$r['stock_old'] ?></span>
                  <span class="mx-1">→</span>
                  <span class="fw-semibold text-danger"><?= (int)($r['stock_new'] ?? 0) ?></span>
                <?php else: ?>
                  <?= is_null($r['stock_new'] ?? null) ? '-' : (int)$r['stock_new'] ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($collChanged): ?>
                  <span class="text-muted"><?= htmlspecialchars((string)($r['collection_old'] ?? '-')) ?></span>
                  <span class="mx-1">→</span>
                  <span class="fw-semibold text-danger"><?= htmlspecialchars((string)($r['collection_new'] ?? '-')) ?></span>
                <?php else: ?>
                  <?= htmlspecialchars((string)($r['collection_new'] ?? $r['collection_old'] ?? '-')) ?>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars((string)($r['category'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

