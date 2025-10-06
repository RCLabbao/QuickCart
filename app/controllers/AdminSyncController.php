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
        if (empty($_FILES['csv']['tmp_name'])) { $_SESSION['error'] = 'No CSV uploaded.'; $this->redirect('/admin/sync'); return; }
        $dryRun = isset($_POST['dry_run']);
        $rows = [];
        if (($h = fopen($_FILES['csv']['tmp_name'], 'r')) !== false) {
            $header = fgetcsv($h);
            if (!$header) { $_SESSION['error'] = 'Empty CSV.'; $this->redirect('/admin/sync'); return; }
            $map = array_flip($header);
            while (($r = fgetcsv($h)) !== false) {
                $rows[] = [
                    'FSC' => $r[$map['FSC']] ?? '',
                    'Description' => $r[$map['Description']] ?? '',
                    'SM_SOH' => $r[$map['SM_SOH']] ?? 0,
                    'WH_SOH' => $r[$map['WH_SOH']] ?? 0,
                    'TotalSOH' => $r[$map['TotalSOH']] ?? 0,
                    'UnitSold' => $r[$map['UnitSold']] ?? 0,
                    'Categorycode' => $r[$map['Categorycode']] ?? '',
                    'ProductType' => $r[$map['ProductType']] ?? '',
                    'RegPrice' => $r[$map['RegPrice']] ?? 0,
                ];
            }
            fclose($h);
        }
        try {
            $result = $this->processRows($rows, $dryRun);
            $_SESSION['success'] = ($dryRun? 'Dry-run: ' : '') .
                "CSV processed. Products matched: {$result['seen']}, created: {$result['created']}, updated: {$result['updated']}, errors: {$result['errors']}";
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'CSV import failed: '.$e->getMessage();
        }
        $this->redirect('/admin/sync');
    }

    private function processRows(array $rows, bool $dryRun): array
    {
        $pdo = DB::pdo();
        $updatePrice = (string)\App\Core\setting('sync_update_price','1') === '1';
        $updateTitle = (string)\App\Core\setting('sync_update_title','1') === '1';
        $updateCollection = (string)\App\Core\setting('sync_update_collection','1') === '1';
        $seen=0; $created=0; $updated=0; $errors=0;
        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $seen++;
                $sku = trim((string)($row['FSC'] ?? ''));
                if ($sku==='') { $errors++; continue; }
                $title = trim((string)($row['Description'] ?? '')) ?: $sku;
                $price = (float)($row['RegPrice'] ?? 0);
                $stock = (int)($row['TotalSOH'] ?? 0);
                $category = trim((string)($row['Categorycode'] ?? ''));
                $ptype = trim((string)($row['ProductType'] ?? ''));
                // Resolve/ensure collection
                $collectionId = null;
                if ($category !== '') {
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $category));
                    $cst = $pdo->prepare('SELECT id FROM collections WHERE slug=?');
                    $cst->execute([$slug]); $cid = $cst->fetchColumn();
                    if (!$cid && !$dryRun) {
                        $pdo->prepare('INSERT INTO collections (title,slug,description) VALUES (?,?,?)')
                            ->execute([$category,$slug,null]);
                        $cid = (int)$pdo->lastInsertId();
                    }
                    $collectionId = $cid ? (int)$cid : null;
                }
                // Find product by SKU
                $pst = $pdo->prepare('SELECT id, title, price, stock, collection_id FROM products WHERE sku=?');
                $pst->execute([$sku]);
                $p = $pst->fetch();
                if (!$p) {
                    if ($dryRun) { $created++; continue; }
                    $slugTitle = strtolower(preg_replace('/[^a-z0-9]+/','-', $title));
                    $pdo->prepare('INSERT INTO products (title,slug,sku,price,status,stock,collection_id,created_at) VALUES (?,?,?,?,"active",?,?,NOW())')
                        ->execute([$title,$slugTitle,$sku,$price,$stock,$collectionId]);
                    $created++;
                } else {
                    // Update
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
                // Optional: tag with ProductType
                if ($ptype !== '' && !$dryRun) {
                    try {
                        $pdo->exec('CREATE TABLE IF NOT EXISTS tags (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE, slug VARCHAR(120) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
                        $pdo->exec('CREATE TABLE IF NOT EXISTS product_tags (product_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(product_id, tag_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
                        $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $ptype));
                        $t = $pdo->prepare('INSERT INTO tags (name,slug) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
                        $t->execute([$ptype,$slug]);
                        $tagId = (int)$pdo->query('SELECT id FROM tags WHERE slug='.$pdo->quote($slug))->fetchColumn();
                        $pid = (int)$pdo->query('SELECT id FROM products WHERE sku='.$pdo->quote($sku))->fetchColumn();
                        if ($tagId && $pid) { $pdo->prepare('INSERT IGNORE INTO product_tags (product_id, tag_id) VALUES (?,?)')->execute([$pid,$tagId]); }
                    } catch (\Throwable $e) { /* ignore tags failures */ }
                }
            }
            if ($dryRun) { $pdo->rollBack(); } else { $pdo->commit(); }
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return compact('seen','created','updated','errors');
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
}

