<?php use function App\Core\csrf_field; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Product Sync</h1>
    <p class="text-muted mb-0">Import via CSV/XLSX or sync from SQL Server. Inventory uses TotalSOH and products are matched by FSC.</p>
  </div>
</div>

<!-- Tabs: CSV first, then SQL Server -->
<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-csv" type="button" role="tab">CSV / XLSX Import</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sql" type="button" role="tab">SQL Server Sync</button>
  </li>
</ul>
<div class="tab-content">
  <!-- CSV/XLSX Import Tab -->
  <div class="tab-pane fade show active" id="tab-csv" role="tabpanel">
    <div class="row g-4">
      <div class="col-12 col-lg-8 col-xl-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white border-bottom"><h5 class="mb-0">CSV/XLSX Import</h5></div>
          <div class="card-body">
            <form method="post" action="/admin/sync/upload" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <div class="mb-2">
                <label class="form-label">Upload CSV or XLSX</label>
                <input type="file" class="form-control" name="csv" accept=".csv,.xlsx" required>
                <div class="form-text">Expected headers: FSC, Description, SM_SOH, WH_SOH, TotalSOH, UnitSold, Categorycode, ProductType, RegPrice, BrochurePrice, Status, Image URLs, Primary Image URL</div>
              </div>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="dry_run" id="csv_dry_run">
                <label class="form-check-label" for="csv_dry_run">Dry-run (preview changes without saving)</label>
              </div>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="debug" id="csv_debug">
                <label class="form-check-label" for="csv_debug">Debug mode (show parser mapping and per-row reasons)</label>
              </div>
              <div class="mt-3">
                <label class="form-label">Update Rules (for this import)</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="csv_sync_update_price" name="sync_update_price" <?= (($settings['sync_update_price'] ?? '1')==='1')?'checked':'' ?>>
                  <label class="form-check-label" for="csv_sync_update_price">Update price from RegPrice</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="csv_sync_update_title" name="sync_update_title" <?= (($settings['sync_update_title'] ?? '1')==='1')?'checked':'' ?>>
                  <label class="form-check-label" for="csv_sync_update_title">Update title from Description</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="csv_sync_update_collection" name="sync_update_collection" <?= (($settings['sync_update_collection'] ?? '1')==='1')?'checked':'' ?>>
                  <label class="form-check-label" for="csv_sync_update_collection">Update collection from Categorycode (auto-create if missing)</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="csv_sync_update_images" name="sync_update_images">
                  <label class="form-check-label" for="csv_sync_update_images">Update images (replaces existing images with Image URLs column content)</label>
                </div>
                <div class="alert alert-info mt-2 small">
                  <strong>Auto-Image Detection:</strong> Images are automatically attached based on FSC matching.
                  <ul class="mb-0 mt-1">
                    <li>Name images as: <code>{FSC}.jpg</code>, <code>{FSC}-1.jpg</code>, <code>{FSC}-2.jpg</code>, etc.</li>
                    <li>Upload to: <code>/public/uploads/products/{FSC}/</code> folder</li>
                    <li>For variants, first variant's FSC is also checked</li>
                    <li>Images are auto-attached when CSV is imported (if no images exist)</li>
                  </ul>
                </div>
                <div class="form-text">These rules apply only to this upload. To change defaults, use the SQL Server tab &gt; Connection Settings.</div>
              </div>

              <div class="mt-3">
                <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-2"></i>Import File</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SQL Server Sync Tab -->
  <div class="tab-pane fade" id="tab-sql" role="tabpanel">
    <div class="row g-4">
      <div class="col-12 col-xxl-10">
        <div class="row g-4">
          <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-header bg-white border-bottom"><h5 class="mb-0">Connection Settings</h5></div>
              <div class="card-body">
                <form method="post" action="/admin/sync/save">
                  <?= csrf_field() ?>
                  <div class="mb-3">
                    <label class="form-label">SQL Server (host\\instance)</label>
                    <input class="form-control" name="sqlsrv_server" value="<?= htmlspecialchars($settings['sqlsrv_server'] ?? '') ?>" placeholder="DLSMSPPHOMSDB1.na.avonet.net\\apphp">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Database</label>
                    <input class="form-control" name="sqlsrv_db" value="<?= htmlspecialchars($settings['sqlsrv_db'] ?? '') ?>" placeholder="DRMPOS_PH">
                  </div>
                  <div class="row g-3">
                    <div class="col">
                      <label class="form-label">Username</label>
                      <input class="form-control" name="sqlsrv_user" value="<?= htmlspecialchars($settings['sqlsrv_user'] ?? '') ?>" placeholder="AUTOMAILER">
                    </div>
                    <div class="col">
                      <label class="form-label">Password</label>
                      <input class="form-control" type="password" name="sqlsrv_pass" value="<?= htmlspecialchars($settings['sqlsrv_pass'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="row g-3 mt-1">
                    <div class="col">
                      <label class="form-label">Default Store ID</label>
                      <input class="form-control" name="sync_store_id" value="<?= htmlspecialchars($settings['sync_store_id'] ?? '') ?>" placeholder="0117">
                    </div>
                  </div>
                  <div class="mt-3">
                    <label class="form-label">Update Rules</label>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="sync_update_price" name="sync_update_price" <?= (($settings['sync_update_price'] ?? '1')==='1')?'checked':'' ?>>
                      <label class="form-check-label" for="sync_update_price">Update price from RegPrice</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="sync_update_title" name="sync_update_title" <?= (($settings['sync_update_title'] ?? '1')==='1')?'checked':'' ?>>
                      <label class="form-check-label" for="sync_update_title">Update title from Description</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="sync_update_collection" name="sync_update_collection" <?= (($settings['sync_update_collection'] ?? '1')==='1')?'checked':'' ?>>
                      <label class="form-check-label" for="sync_update_collection">Update collection from Categorycode (auto-create if missing)</label>
                    </div>
                  </div>
                  <div class="mt-3">
                    <label class="form-label">Webhook (optional)</label>
                    <div class="mb-2">
                      <label class="form-label">Webhook Key</label>
                      <input class="form-control" name="sync_webhook_key" value="<?= htmlspecialchars($settings['sync_webhook_key'] ?? '') ?>" placeholder="Random secret key">
                      <div class="form-text">Set a secret key to enable an external URL for automated stock syncs.</div>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="sync_webhook_update_price" name="sync_webhook_update_price" <?= (($settings['sync_webhook_update_price'] ?? '0')==='1')?'checked':'' ?>>
                      <label class="form-check-label" for="sync_webhook_update_price">Allow webhook to update price (otherwise it updates stock only)</label>
                    </div>
                    <?php if (!empty($settings['sync_webhook_key'])): $k=urlencode($settings['sync_webhook_key']); $store=urlencode($settings['sync_store_id'] ?? ''); ?>
                      <div class="alert alert-info mt-2 small">
                        <div><i class="bi bi-link-45deg me-2"></i>Webhook URL:</div>
                        <code><?= htmlspecialchars((isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http').'://'.($_SERVER['HTTP_HOST'] ?? 'localhost')."/sync/stock?k={$k}&store={$store}") ?></code>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-2"></i>Save Settings</button>
                    <button class="btn btn-outline-secondary" type="submit" formaction="/admin/sync/test" formmethod="post" <?= !$capabilities['sqlsrv_available'] ? 'disabled' : '' ?>><i class="bi bi-plug me-2"></i>Test Connection</button>
                  </div>
                  <?php if (!$capabilities['sqlsrv_available']): ?>
                    <div class="alert alert-warning mt-3"><i class="bi bi-exclamation-triangle me-2"></i>SQL Server PHP driver (pdo_sqlsrv/sqlsrv) is not available on this server.</div>
                  <?php endif; ?>
                </form>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-header bg-white border-bottom"><h5 class="mb-0">Run Sync (SQL Server)</h5></div>
              <div class="card-body">
                <form method="post" action="/admin/sync/run">
                  <?= csrf_field() ?>
                  <div class="row g-3">
                    <div class="col-4">
                      <label class="form-label">From</label>
                      <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($_GET['from'] ?? date('Y-m-01')) ?>">
                    </div>
                    <div class="col-4">
                      <label class="form-label">To</label>
                      <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($_GET['to'] ?? date('Y-m-t')) ?>">
                    </div>
                    <div class="col-4">
                      <label class="form-label">Store ID</label>
                      <input class="form-control" name="store_id" value="<?= htmlspecialchars($settings['sync_store_id'] ?? '') ?>" placeholder="0117">
                    </div>
                  </div>
                  <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="dry_run" id="dry_run">
                    <label class="form-check-label" for="dry_run">Dry-run (preview changes without saving)</label>
                  </div>
                  <div class="mt-3">
                    <button class="btn btn-success" type="submit" <?= !$capabilities['sqlsrv_available'] ? 'disabled' : '' ?>><i class="bi bi-arrow-repeat me-2"></i>Sync Now</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

