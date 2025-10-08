<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF; use App\Core\SQLServer;

class AdminSyncController extends Controller
{
    public function index(): void
    {
        $pdo = DB::pdo();
        $settings = [];
        try { foreach ($pdo->query("SELECT `key`,`value` FROM settings") as $r) { $settings[$r['key']] = $r['value']; } } catch (\Throwable $e) {}
        $capabilities = [ 'sqlsrv_available' => SQLServer::available() ];
        $this->adminView('admin/sync/index', [
            'title' => 'Product Sync',
            'settings' => $settings,
            'capabilities' => $capabilities,
        ]);
    }

    public function save(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/sync'); }
        $pdo = DB::pdo();
        $pairs = [
            'sqlsrv_server' => trim((string)($_POST['sqlsrv_server'] ?? '')),
            'sqlsrv_db' => trim((string)($_POST['sqlsrv_db'] ?? '')),
            'sqlsrv_user' => trim((string)($_POST['sqlsrv_user'] ?? '')),
            'sqlsrv_pass' => (string)($_POST['sqlsrv_pass'] ?? ''),
            'sync_store_id' => trim((string)($_POST['sync_store_id'] ?? '')),
            'sync_update_price' => isset($_POST['sync_update_price']) ? '1' : '0',
            'sync_update_title' => isset($_POST['sync_update_title']) ? '1' : '0',
            'sync_update_collection' => isset($_POST['sync_update_collection']) ? '1' : '0',
            'sync_webhook_key' => trim((string)($_POST['sync_webhook_key'] ?? '')),
            'sync_webhook_update_price' => isset($_POST['sync_webhook_update_price']) ? '1' : '0',
        ];
        foreach ($pairs as $k=>$v){
            $st = $pdo->prepare('INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
            $st->execute([$k,$v]);
        }
        $_SESSION['success'] = 'Sync settings saved.';
        $this->redirect('/admin/sync');
    }

    public function test(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/sync'); }
        $s = fn($k,$d='') => (string)\App\Core\setting($k,$d);
        try {
            if (!SQLServer::available()) { throw new \RuntimeException('SQL Server PHP driver not available (pdo_sqlsrv/sqlsrv).'); }
            $pdo2 = SQLServer::pdo($s('sqlsrv_server',''), $s('sqlsrv_db',''), $s('sqlsrv_user',''), $s('sqlsrv_pass',''));
            $row = $pdo2->query('SELECT GETDATE() as now')->fetch();
            $_SESSION['success'] = 'Connection OK. Server time: '.($row['now'] ?? 'unknown');
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Test failed: '.$e->getMessage();
        }
        $this->redirect('/admin/sync');
    }

    public function run(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/sync'); }
        $s = fn($k,$d='') => (string)\App\Core\setting($k,$d);
        $dateFrom = trim((string)($_POST['date_from'] ?? date('Y-m-01')));
        $dateTo   = trim((string)($_POST['date_to'] ?? date('Y-m-t')));
        $storeId  = trim((string)($_POST['store_id'] ?? $s('sync_store_id','')));
        $dryRun   = isset($_POST['dry_run']);
        try {
            if (!SQLServer::available()) { throw new \RuntimeException('SQL Server PHP driver not available (pdo_sqlsrv/sqlsrv).'); }
            $pdo2 = SQLServer::pdo($s('sqlsrv_server',''), $s('sqlsrv_db',''), $s('sqlsrv_user',''), $s('sqlsrv_pass',''));
            $sql = "SELECT PR.FSC, Description, Isnull(SM_Qty,0) SM_SOH, Isnull(WH_Qty,0) WH_SOH,\n                           isnull(qty,0) TotalSOH, Isnull(UnitSold,0) UnitSold,\n                           Categorycode, ProductType, ListPrice RegPrice\n                    FROM Product PR\n                    LEFT JOIN (SELECT A.fsc,sum(a.salesqty) UnitSold\n                               FROM salesdetail a,salesorder b\n                               WHERE b.ordertype IN ('1','2') and b.status ='0' AND b.Voidstatus=0\n                               AND b.salesdate between ? and ?\n                               AND a.orderno = b.orderno AND b.StoreID=?\n                               GROUP BY A.fsc) S ON PR.FSC=S.FSC\n                    LEFT JOIN (SELECT FSC,SUM(Qty) qty,\n                               SUM(CASE WHEN Site IN ('W1','W4') THEN Qty ELSE 0 END) SM_Qty,\n                               SUM(CASE WHEN Site NOT IN ('W1','W4') THEN Qty ELSE 0 END) WH_Qty\n                               FROM StoreInventory WHERE StoreID=?\n                               GROUP BY FSC) SI ON PR.FSC=SI.FSC\n                    WHERE Isnull(qty,0)<>0 OR Isnull(UnitSold,0)<>0\n                    ORDER BY PR.FSC";
            $st = $pdo2->prepare($sql);
            $st->execute([$dateFrom, $dateTo, $storeId, $storeId]);
            $rows = $st->fetchAll();
            $result = $this->processRows($rows, $dryRun);
            $_SESSION['success'] = ($dryRun? 'Dry-run: ' : '') .
                "Products matched: {$result['seen']}, created: {$result['created']}, updated: {$result['updated']}, errors: {$result['errors']}";
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Sync failed: '.$e->getMessage();
        }
        $this->redirect('/admin/sync');
    }

    public function uploadCsv(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/sync'); }
        if (empty($_FILES['csv']['tmp_name'])) { $_SESSION['error'] = 'No file uploaded.'; $this->redirect('/admin/sync'); return; }
        $dryRun = isset($_POST['dry_run']);
        $rows = [];
        $tmp = $_FILES['csv']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv']['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext === 'xlsx') {
            // Support XLSX via PhpSpreadsheet if available, otherwise try lightweight Zip/XML fallback
            if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                try {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($tmp);
                    $sheet = $spreadsheet->getActiveSheet();
                    $header = null;
                    foreach ($sheet->toArray(null, true, true, true) as $row) {
                        $arr = array_values($row);
                        if ($header === null) {
                            // Normalize header keys to be robust (trim, lowercase)
                            $header = array_map(fn($h)=> strtolower(trim((string)$h)), $arr);
                            $map = array_flip($header);
                            // helper to pick value by any of the candidate keys
                            $pick = function(array $rowVals, array $map, array $keys) {
                                foreach ($keys as $k) { if (isset($map[$k])) { return $rowVals[$map[$k]] ?? ''; } }
                                return '';
                            };
                            continue;
                        }
                        if (count(array_filter($arr, fn($v)=>$v!==null && $v!==''))===0) continue; // skip empty lines
                        $rows[] = [
                            'FSC' => $pick($arr,$map,['fsc']),
                            'Description' => $pick($arr,$map,['description','desc','product description','productdescription']),
                            'SM_SOH' => $pick($arr,$map,['sm_soh','sm soh','smqty','sm_qty','sm qty']),
                            'WH_SOH' => $pick($arr,$map,['wh_soh','wh soh','whqty','wh_qty','wh qty']),
                            'TotalSOH' => $pick($arr,$map,['totalsoh','total_soh','total soh','totalqty','total_qty','total qty','qty','quantity']),
                            'UnitSold' => $pick($arr,$map,['unitsold','unit_sold','unit sold','sold']),
                            'Categorycode' => $pick($arr,$map,['categorycode','category_code','category code','category','categories','categoryname','category name','collection','collection_name','collection name']),
                            'ProductType' => $pick($arr,$map,['producttype','product_type','product type','type']),
                            'RegPrice' => $pick($arr,$map,['regprice','listprice','list price','price']),
                        ];
                    }
                } catch (\Throwable $e) {
                    $_SESSION['error'] = 'XLSX import failed: '.$e->getMessage();
                    $this->redirect('/admin/sync'); return;
                }
            } else {
                // Lightweight fallback using ZipArchive and SimpleXML (handles simple sheets)
                try {
                    $parsed = $this->parseXlsx($tmp);
                    if (!$parsed) { throw new \RuntimeException('Empty XLSX or unsupported format'); }
                    $header = array_map(fn($h)=> strtolower(trim((string)$h)), array_shift($parsed));
                    $map = array_flip($header);
                    $pick = function(array $rowVals, array $map, array $keys) {
                        foreach ($keys as $k) { if (isset($map[$k])) { return $rowVals[$map[$k]] ?? ''; } }
                        return '';
                    };
                    foreach ($parsed as $arr) {
                        if (count(array_filter($arr, fn($v)=>$v!==null && $v!==''))===0) continue;
                        $rows[] = [
                            'FSC' => $pick($arr,$map,['fsc']),
                            'Description' => $pick($arr,$map,['description','desc','product description','productdescription']),
                            'SM_SOH' => $pick($arr,$map,['sm_soh','sm soh','smqty','sm_qty','sm qty']),
                            'WH_SOH' => $pick($arr,$map,['wh_soh','wh soh','whqty','wh_qty','wh qty']),
                            'TotalSOH' => $pick($arr,$map,['totalsoh','total_soh','total soh','totalqty','total_qty','total qty','qty','quantity']),
                            'UnitSold' => $pick($arr,$map,['unitsold','unit_sold','unit sold','sold']),
                            'Categorycode' => $pick($arr,$map,['categorycode','category_code','category code','category','categories','categoryname','category name','collection','collection_name','collection name']),
                            'ProductType' => $pick($arr,$map,['producttype','product_type','product type','type']),
                            'RegPrice' => $pick($arr,$map,['regprice','listprice','list price','price']),
                        ];
                    }
                } catch (\Throwable $e) {
                    $_SESSION['error'] = 'XLSX not supported on this server. Please upload CSV instead. Details: '.$e->getMessage();
                    $this->redirect('/admin/sync'); return;
                }
            }
        } else {
            // CSV path (default) with delimiter auto-detection and BOM handling
            if (($h = fopen($tmp, 'r')) !== false) {
                $firstLine = fgets($h);
                if ($firstLine === false) { $_SESSION['error'] = 'Empty CSV.'; $this->redirect('/admin/sync'); return; }
                // Pick the most likely delimiter
                $candidates = [",", "\t", ";", "|"];
                $bestDelim = ","; $bestCount = -1;
                foreach ($candidates as $d) { $cnt = substr_count($firstLine, $d); if ($cnt > $bestCount) { $bestCount = $cnt; $bestDelim = $d; } }
                // Rewind and parse header with chosen delimiter
                rewind($h);
                $header = fgetcsv($h, 0, $bestDelim);
                if (!$header) { $_SESSION['error'] = 'Empty CSV.'; $this->redirect('/admin/sync'); return; }
                // Remove UTF-8 BOM if present on first cell
                if (isset($header[0])) { $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]); }
                // Normalize header keys (trim + lowercase) and map flexible names
                $headerNorm = array_map(fn($v)=> strtolower(trim((string)$v)), $header);
                $map = array_flip($headerNorm);
                $pick = function(array $rowVals, array $map, array $keys) {
                    foreach ($keys as $k) { if (isset($map[$k])) { return $rowVals[$map[$k]] ?? ''; } }
                    return '';
                };
                while (($r = fgetcsv($h, 0, $bestDelim)) !== false) {
                    $rows[] = [
                        'FSC' => $pick($r,$map,['fsc']),
                        'Description' => $pick($r,$map,['description','desc','product description','productdescription']),
                        'SM_SOH' => $pick($r,$map,['sm_soh','sm soh','smqty','sm_qty','sm qty']),
                        'WH_SOH' => $pick($r,$map,['wh_soh','wh soh','whqty','wh_qty','wh qty']),
                        'TotalSOH' => $pick($r,$map,['totalsoh','total_soh','total soh','totalqty','total_qty','total qty','qty','quantity']),
                        'UnitSold' => $pick($r,$map,['unitsold','unit_sold','unit sold','sold']),
                        'Categorycode' => $pick($r,$map,['categorycode','category_code','category code','category','categories','categoryname','category name','collection','collection_name','collection name']),
                        'ProductType' => $pick($r,$map,['producttype','product_type','product type','type']),
                        'RegPrice' => $pick($r,$map,['regprice','listprice','list price','price']),
                    ];
                }
                fclose($h);
                // Build parser debug info for CSV
                if (isset($_POST['debug'])) {
                    $debugInfo = [
                        'source' => 'csv',
                        'delimiter' => $bestDelim,
                        'header_norm' => $headerNorm,
                        'category_index' => $map['category'] ?? ($map['category code'] ?? ($map['category_code'] ?? ($map['categorycode'] ?? null))),
                    ];
                }
            }
        }
        try {
            $overrides = [
                'update_price' => isset($_POST['sync_update_price']),
                'update_title' => isset($_POST['sync_update_title']),
                'update_collection' => isset($_POST['sync_update_collection']),
            ];
            if ($dryRun) { $overrides['collect_preview'] = true; }
            if (isset($_POST['debug'])) { $overrides['collect_debug'] = true; }
            $result = $this->processRows($rows, $dryRun, $overrides);
            if (!empty($debugInfo)) { $result['debug'] = $result['debug'] ?? []; $result['debug']['info'] = $debugInfo; }
            if (($dryRun && !empty($result['preview'])) || (!empty($overrides['collect_debug']))) {
                $this->adminView('admin/sync/preview', [
                    'title' => $dryRun ? 'CSV/XLSX Dry-run Preview' : 'CSV/XLSX Import Debugger',
                    'result' => $result,
                    'overrides' => $overrides,
                ]);
                return;
            }
            $_SESSION['success'] = 'File processed. Products matched: ' . $result['seen'] . ', created: ' . $result['created'] . ', updated: ' . $result['updated'] . ', errors: ' . $result['errors'];
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Import failed: '.$e->getMessage();
        }
        $this->redirect('/admin/sync');
    }

    private function processRows(array $rows, bool $dryRun, array $overrides = []): array
    {
        $pdo = DB::pdo();
        $updatePrice = array_key_exists('update_price', $overrides)
            ? (bool)$overrides['update_price']
            : ((string)\App\Core\setting('sync_update_price','1') === '1');
        $updateTitle = array_key_exists('update_title', $overrides)
            ? (bool)$overrides['update_title']
            : ((string)\App\Core\setting('sync_update_title','1') === '1');
        $updateCollection = array_key_exists('update_collection', $overrides)
            ? (bool)$overrides['update_collection']
            : ((string)\App\Core\setting('sync_update_collection','1') === '1');
        $collectPreview = !empty($overrides['collect_preview']);
        $collectDebug = !empty($overrides['collect_debug']);

        $seen=0; $created=0; $updated=0; $errors=0;
        $preview = [];
        $debugRows = [];
        $collTitle = [];
        $collLabel = function(?int $id) use ($pdo, &$collTitle) {
            if (!$id) { return null; }
            if (!array_key_exists($id, $collTitle)) {
                $st = $pdo->prepare('SELECT title FROM collections WHERE id=?');
                $st->execute([$id]);
                $collTitle[$id] = $st->fetchColumn() ?: null;
            }
            return $collTitle[$id];
        };
        // Ensure tag tables exist outside of transaction to avoid implicit commits
        try {
            $pdo->exec('CREATE TABLE IF NOT EXISTS tags (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE, slug VARCHAR(120) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            $pdo->exec('CREATE TABLE IF NOT EXISTS product_tags (product_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(product_id, tag_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        } catch (\Throwable $e) { /* ignore */ }
        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $seen++;
                $fsc = trim((string)($row['FSC'] ?? ''));
                if ($fsc==='') { $errors++; if ($collectDebug && count($debugRows) < 500) { $debugRows[] = ['fsc'=>'','category_raw'=>'','slug'=>'','collection'=>null,'action'=>'skip','reason'=>'Missing FSC']; } continue; }
                $title = trim((string)($row['Description'] ?? '')) ?: $fsc;
                $price = (float)($row['RegPrice'] ?? 0);
                $stock = (int)($row['TotalSOH'] ?? 0);
                $category = trim((string)($row['Categorycode'] ?? ''));
                $ptype = trim((string)($row['ProductType'] ?? ''));
                // Resolve/ensure collection (match by slug OR title, create if missing and not dry-run)
                $collectionId = null; $catSlug = '';
                if ($category !== '') {
                    $catSlug = strtolower(preg_replace('/[^a-z0-9]+/','-', $category));
                    $catSlug = trim($catSlug, '-');
                    $cst = $pdo->prepare('SELECT id FROM collections WHERE slug=? OR LOWER(title)=LOWER(?) LIMIT 1');
                    $cst->execute([$catSlug, $category]); $cid = $cst->fetchColumn();
                    if (!$cid && !$dryRun) {
                        // Ensure a non-empty unique slug
                        $slugCandidate = $catSlug !== '' ? $catSlug : strtolower(preg_replace('/[^a-z0-9]+/','-', $category));
                        $slugCandidate = trim($slugCandidate, '-') ?: ('collection-'.substr(md5($category.microtime(true)),0,6));
                        $baseSlug = $slugCandidate; $suffix = 1;
                        while ((int)$pdo->query('SELECT COUNT(*) FROM collections WHERE slug='.$pdo->quote($slugCandidate))->fetchColumn() > 0) {
                            $slugCandidate = $baseSlug.'-'.$suffix++; if ($suffix>1000) break;
                        }
                        $pdo->prepare('INSERT INTO collections (title,slug,description) VALUES (?,?,?)')
                            ->execute([$category,$slugCandidate,null]);
                        $cid = (int)$pdo->lastInsertId();
                    }
                    $collectionId = $cid ? (int)$cid : null;
                }
                // Find product by FSC
                $pst = $pdo->prepare('SELECT id, title, price, stock, collection_id FROM products WHERE fsc=?');
                $pst->execute([$fsc]);
                $p = $pst->fetch();

                // Fallback: try to match existing product by slug or exact title to preserve images
                if (!$p) {
                    $slugTitle = strtolower(preg_replace('/[^a-z0-9]+/','-', (string)$title));
                    $slugTitle = trim($slugTitle, '-');
                    if ($slugTitle !== '') {
                        $alt = $pdo->prepare('SELECT id, title, price, stock, collection_id FROM products WHERE slug=? OR title=? LIMIT 1');
                        $alt->execute([$slugTitle, $title]);
                        $p = $alt->fetch();
                    }
                }
                // For debug: decide action and reason
                if ($collectDebug && count($debugRows) < 500) {
                    $reason = '';
                    $collLabelNow = $collectionId ? ($collLabel($collectionId) ?: (string)$collectionId) : '-';
                    if ($category === '') { $reason = 'Categorycode empty'; }
                    elseif (!$collectionId && $dryRun) { $reason = 'Would create collection (dry-run)'; }
                    elseif (!$collectionId && !$dryRun) { $reason = 'Created new collection'; }
                    elseif (!$updateCollection) { $reason = 'Update collection disabled by rule'; }
                    else { $reason = 'Collection resolved'; }
                    $debugRows[] = [
                        'fsc' => $fsc,
                        'category_raw' => $category,
                        'slug' => $catSlug,
                        'collection' => $collLabelNow,
                        'action' => $p ? 'update' : 'create',
                        'reason' => $reason,
                    ];
                }

                if (!$p) {
                    if ($collectPreview) {
                        $preview[] = [
                            'action' => 'create',
                            'fsc' => $fsc,
                            'title_old' => null,
                            'title_new' => $title,
                            'price_old' => null,
                            'price_new' => $price,
                            'stock_old' => null,
                            'stock_new' => $stock,
                            'collection_old' => null,
                            'collection_new' => $collLabel($collectionId),
                            'category' => $category,
                        ];
                    }
                    if ($dryRun) { $created++; continue; }
                    // Build a safe, unique slug
                    $slugTitle = strtolower(preg_replace('/[^a-z0-9]+/','-', (string)$title));
                    $slugTitle = trim($slugTitle, '-');
                    if ($slugTitle === '') {
                        $slugTitle = strtolower(preg_replace('/[^a-z0-9]+/','-', (string)$fsc));
                        $slugTitle = trim($slugTitle, '-');
                    }
                    if ($slugTitle === '') { $slugTitle = 'product-'.substr(md5((string)$fsc.microtime(true)),0,6); }
                    $baseSlug = $slugTitle; $suffix = 1;
                    while ((int)$pdo->query('SELECT COUNT(*) FROM products WHERE slug='.$pdo->quote($slugTitle))->fetchColumn() > 0) {
                        $slugTitle = $baseSlug.'-'.$suffix++; if ($suffix>1000) break; // safety
                    }
                    $pdo->prepare('INSERT INTO products (title,slug,fsc,price,status,stock,collection_id,created_at) VALUES (?,?,?,?,\'active\',?, ?, NOW())')
                        ->execute([$title,$slugTitle,$fsc,$price,$stock,$collectionId]);
                    $created++;
                } else {
                    // Update
                    if ($collectPreview) {
                        $oldTitle = (string)$p['title'];
                        $newTitle = $updateTitle ? $title : $oldTitle;
                        $oldPrice = (float)$p['price'];
                        $newPrice = $updatePrice ? $price : $oldPrice;
                        $oldStock = (int)$p['stock'];
                        $newStock = $stock; // stock always considered
                        $oldCollId = (int)($p['collection_id'] ?? 0);
                        $newCollId = ($updateCollection && $collectionId) ? (int)$collectionId : $oldCollId;
                        $preview[] = [
                            'action' => 'update',
                            'fsc' => $fsc,
                            'title_old' => $oldTitle,
                            'title_new' => $newTitle,
                            'price_old' => $oldPrice,
                            'price_new' => $newPrice,
                            'stock_old' => $oldStock,
                            'stock_new' => $newStock,
                            'collection_old' => $collLabel($oldCollId),
                            'collection_new' => $collLabel($newCollId),
                            'category' => $category,
                        ];
                    }
                    if ($dryRun) { $updated++; continue; }
                    $fields = ['stock = ?']; $vals = [$stock];
                    if ($updatePrice) { $fields[] = 'price = ?'; $vals[] = $price; }
                    if ($updateTitle) { $fields[] = 'title = ?'; $vals[] = $title; }
                    if ($updateCollection && $collectionId) { $fields[] = 'collection_id = ?'; $vals[] = $collectionId; }
                    $vals[] = (int)$p['id'];
                    $sql = 'UPDATE products SET '.implode(',', $fields).' WHERE id = ?';
                    $pdo->prepare($sql)->execute($vals);
                    $updated++;
                }
                // Optional: tag with ProductType (no DDL here; DDL was done before transaction)
                if ($ptype !== '' && !$dryRun) {
                    try {
                        $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $ptype));
                        $t = $pdo->prepare('INSERT INTO tags (name,slug) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
                        $t->execute([$ptype,$slug]);
                        $tagId = (int)$pdo->query('SELECT id FROM tags WHERE slug='.$pdo->quote($slug))->fetchColumn();
                        $pid = (int)$pdo->query('SELECT id FROM products WHERE fsc='.$pdo->quote($fsc))->fetchColumn();
                        if ($tagId && $pid) { $pdo->prepare('INSERT IGNORE INTO product_tags (product_id, tag_id) VALUES (?,?)')->execute([$pid,$tagId]); }
                    } catch (\Throwable $e) { /* ignore tags failures */ }
                }
            }
            if ($dryRun) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
            } else {
                if ($pdo->inTransaction()) { $pdo->commit(); }
            }
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
        $base = compact('seen','created','updated','errors');
        if ($collectPreview) { $base['preview'] = $preview; }
        if ($collectDebug) { $base['debug'] = ($base['debug'] ?? []) + ['rows' => $debugRows]; }
        return $base;
    }

    public function webhook(): void
    {
        // Public endpoint to update stock (and optionally price) via key
        $key = $_GET['k'] ?? '';
        $expected = (string)\App\Core\setting('sync_webhook_key','');
        header('Content-Type: application/json');
        if ($expected === '' || !hash_equals($expected, (string)$key)) {
            http_response_code(403);
            echo json_encode(['ok'=>false,'error'=>'Forbidden']);
            return;
        }
        $s = fn($k,$d='') => (string)\App\Core\setting($k,$d);
        $storeId = $_GET['store'] ?? $s('sync_store_id','');
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');
        $updatePriceSaved = (string)\App\Core\setting('sync_update_price','1');
        // For webhook, optionally override price update policy
        $webhookUpdatePrice = (string)\App\Core\setting('sync_webhook_update_price','0') === '1';
        try {
            if (!SQLServer::available()) { throw new \RuntimeException('SQL Server PHP driver not available'); }
            $pdo2 = SQLServer::pdo($s('sqlsrv_server',''), $s('sqlsrv_db',''), $s('sqlsrv_user',''), $s('sqlsrv_pass',''));
            $sql = "SELECT PR.FSC, Description, Isnull(SM_Qty,0) SM_SOH, Isnull(WH_Qty,0) WH_SOH,\n                           isnull(qty,0) TotalSOH, Isnull(UnitSold,0) UnitSold,\n                           Categorycode, ProductType, ListPrice RegPrice\n                    FROM Product PR\n                    LEFT JOIN (SELECT A.fsc,sum(a.salesqty) UnitSold\n                               FROM salesdetail a,salesorder b\n                               WHERE b.ordertype IN ('1','2') and b.status ='0' AND b.Voidstatus=0\n                               AND b.salesdate between ? and ?\n                               AND a.orderno = b.orderno AND b.StoreID=?\n                               GROUP BY A.fsc) S ON PR.FSC=S.FSC\n                    LEFT JOIN (SELECT FSC,SUM(Qty) qty,\n                               SUM(CASE WHEN Site IN ('W1','W4') THEN Qty ELSE 0 END) SM_Qty,\n                               SUM(CASE WHEN Site NOT IN ('W1','W4') THEN Qty ELSE 0 END) WH_Qty\n                               FROM StoreInventory WHERE StoreID=?\n                               GROUP BY FSC) SI ON PR.FSC=SI.FSC\n                    WHERE Isnull(qty,0)<>0 OR Isnull(UnitSold,0)<>0\n                    ORDER BY PR.FSC";
            $st = $pdo2->prepare($sql);
            $st->execute([$dateFrom, $dateTo, $storeId, $storeId]);
            $rows = $st->fetchAll();
            // Temporarily adjust price update policy if webhook wants only stock
            if (!$webhookUpdatePrice) {


                $this->setSetting('sync_update_price','0');
            }
            $res = $this->processRows($rows, false);
            if (!$webhookUpdatePrice) {
                $this->setSetting('sync_update_price',$updatePriceSaved);
            }
            echo json_encode(['ok'=>true,'seen'=>$res['seen'],'created'=>$res['created'],'updated'=>$res['updated']]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    private function setSetting(string $key, string $value): void
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare('INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
        $st->execute([$key,$value]);
    }

    // Minimal XLSX parser using ZipArchive + SimpleXML (Active sheet only)
    private function parseXlsx(string $file): array
    {
        if (!class_exists('ZipArchive')) { throw new \RuntimeException('ZipArchive not available'); }
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) { throw new \RuntimeException('Cannot open XLSX'); }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) { $sheetXml = $zip->getFromName('xl/worksheets/sheet.xml'); }
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();
        if ($sheetXml === false) { throw new \RuntimeException('Sheet XML not found'); }
        $shared = [];
        if ($sharedXml !== false) {
            $sx = @simplexml_load_string($sharedXml);
            if ($sx && isset($sx->si)) {
                foreach ($sx->si as $i => $si) {
                    // concatenate all t nodes
                    $texts = [];
                    foreach ($si->t as $t) { $texts[] = (string)$t; }
                    if (!$texts && isset($si->r)) {
                        foreach ($si->r as $r) { $texts[] = (string)$r->t; }
                    }
                    $shared[(int)$i] = implode('', $texts);
                }
            }
        }
        $sx = @simplexml_load_string($sheetXml);
        if (!$sx) { throw new \RuntimeException('Invalid sheet XML'); }
        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $line = [];
            foreach ($row->c as $c) {
                $t = (string)($c['t'] ?? '');
                $v = (string)($c->v ?? '');
                if ($t === 's') { // shared string
                    $idx = (int)$v; $line[] = $shared[$idx] ?? '';
                } else { $line[] = $v; }
            }
            $rows[] = $line;
        }
        return $rows;
    }

}

