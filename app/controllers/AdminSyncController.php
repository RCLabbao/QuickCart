<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF; use App\Core\SQLServer;

class AdminSyncController extends Controller
{
    /** Rows processed per chunked-import HTTP request — kept small so each
     *  request finishes well under the web-server/FastCGI timeout. */
    public const CHUNK_ROWS = 200;

    /** Lifetime (seconds) of temp import files in sys_get_temp_dir(); older
     *  ones are garbage-collected at the start of the next init request. */
    public const IMPORT_TTL = 3600;

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
        // SQL Server syncs can process many rows and exceed PHP's default 30s limit.
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
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
        // CSV/XLSX imports can be large and exceed PHP's default 30s limit.
        // Remove the per-request time limit and bump memory for this request only.
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        if (empty($_FILES['csv']['tmp_name'])) { $_SESSION['error'] = 'No file uploaded.'; $this->redirect('/admin/sync'); return; }
        $dryRun = isset($_POST['dry_run']);
        $tmp = $_FILES['csv']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv']['name'] ?? '', PATHINFO_EXTENSION));
        $debugInfo = [];
        try {
            [$rows, $debugInfo] = $this->parseFile($tmp, $ext, isset($_POST['debug']));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/sync'); return;
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

    /**
     * Chunked CSV/XLSX import endpoint (JSON). Processes a large file across
     * many short HTTP requests so no single request approaches the web-server
     * timeout. Two modes:
     *
     *  - init:   POST with init=1 + the file. Parses it, stores rows in a temp
     *            JSON file keyed by a random token, returns {token, total, chunkSize}.
     *  - chunk:  POST with token + offset. Processes CHUNK_ROWS rows at that
     *            offset via processRows() (which commits its own transaction per
     *            chunk), accumulates counts, returns progress + a `continue` flag.
     *
     * Dry-run/debug are NOT supported here — those render an aggregated preview
     * page that can't be split across requests; the front-end falls back to the
     * single-shot /admin/sync/upload form for those modes.
     */
    public function uploadChunk(): void
    {
        header('Content-Type: application/json');
        if (!CSRF::check($_POST['_token'] ?? '')) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Invalid session token']); return; }
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        try {
            if (($_POST['init'] ?? '0') === '1') {
                $resp = $this->chunkInit();
            } else {
                $resp = $this->chunkStep();
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
            return;
        }
        echo json_encode($resp);
    }

    private function chunkInit(): array
    {
        if (empty($_FILES['csv']['tmp_name'])) { throw new \RuntimeException('No file uploaded.'); }
        // Dry-run/debug need the aggregated preview page — tell the browser to
        // fall back to the normal single-shot form.
        if (isset($_POST['dry_run']) || isset($_POST['debug'])) {
            return ['ok'=>true, 'fallback'=>true];
        }

        $this->gcImportTemp();

        $tmp = $_FILES['csv']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv']['name'] ?? '', PATHINFO_EXTENSION));
        [$rows] = $this->parseFile($tmp, $ext, false);
        if (empty($rows)) { throw new \RuntimeException('No rows found in file.'); }

        $token = bin2hex(random_bytes(16));
        $rowsPath = $this->importRowsPath($token);
        $metaPath = $this->importMetaPath($token);

        // Persist parsed rows so chunk requests don't re-parse the file. Use
        // JSON_INVALID_UTF8_SUBSTITUTE as a safety net so a stray non-UTF-8 byte
        // can never make json_encode return false (which would write an empty
        // file and surface as "Corrupt import rows" on the first chunk).
        $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) { throw new \RuntimeException('Failed to encode parsed rows: '.json_last_error_msg()); }
        $written = file_put_contents($rowsPath, $json, LOCK_EX);
        if ($written === false) { throw new \RuntimeException('Could not store parsed rows in temp dir.'); }

        $overrides = [
            'update_price' => isset($_POST['sync_update_price']),
            'update_title' => isset($_POST['sync_update_title']),
            'update_collection' => isset($_POST['sync_update_collection']),
            'update_images' => isset($_POST['sync_update_images']),
        ];
        $meta = [
            'total' => count($rows),
            'dryRun' => false,
            'overrides' => $overrides,
            'seen' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0,
            'created_at' => time(),
        ];
        file_put_contents($metaPath, json_encode($meta), LOCK_EX);

        return [
            'ok' => true,
            'token' => $token,
            'total' => $meta['total'],
            'chunkSize' => self::CHUNK_ROWS,
        ];
    }

    private function chunkStep(): array
    {
        $token = (string)($_POST['token'] ?? '');
        if (!preg_match('/^[0-9a-f]{32}$/', $token)) { throw new \RuntimeException('Invalid token.'); }
        $offset = max(0, (int)($_POST['offset'] ?? 0));

        $rowsPath = $this->importRowsPath($token);
        $metaPath = $this->importMetaPath($token);
        if (!is_file($rowsPath) || !is_file($metaPath)) { throw new \RuntimeException('Import session expired. Please re-upload the file.'); }

        $meta = json_decode((string)file_get_contents($metaPath), true);
        if (!is_array($meta)) { throw new \RuntimeException('Corrupt import session.'); }
        $total = (int)($meta['total'] ?? 0);

        $rows = json_decode((string)file_get_contents($rowsPath), true);
        if (!is_array($rows)) { throw new \RuntimeException('Corrupt import rows (' . json_last_error_msg() . ', ' . filesize($rowsPath) . ' bytes). Please re-upload the file.'); }

        $slice = array_slice($rows, $offset, self::CHUNK_ROWS);
        $res = $this->processRows($slice, (bool)($meta['dryRun'] ?? false), $meta['overrides'] ?? []);

        // Accumulate this chunk's counts into the manifest so a final summary survives.
        foreach (['seen','created','updated','errors'] as $k) {
            $meta[$k] = ($meta[$k] ?? 0) + (int)($res[$k] ?? 0);
        }
        $newOffset = $offset + self::CHUNK_ROWS;
        $done = $newOffset >= $total;

        if ($done) {
            // Persist final tallies before cleanup is pointless (file's going away),
            // but we return them from the in-memory $meta so the browser has the totals.
            @unlink($rowsPath);
            @unlink($metaPath);
        } else {
            file_put_contents($metaPath, json_encode($meta), LOCK_EX);
        }

        $continue = $this->shouldContinueAfterChunk($res);

        return [
            'ok' => true,
            'offset' => $newOffset,
            'total' => $total,
            'done' => $done,
            'continue' => $continue,
            'seen' => (int)$meta['seen'],
            'created' => (int)$meta['created'],
            'updated' => (int)$meta['updated'],
            'errors' => (int)$meta['errors'],
        ];
    }

    /**
     * Decide whether to keep processing subsequent chunks after this one finishes.
     * Called once per chunk with that chunk's processRows() result.
     *
     * Trade-offs to consider:
     *  - Always continue: import completes even if some rows error; you get the
     *    full summary and can fix bad rows later. Best when FSC idempotency makes
     *    re-runs safe (each chunk already committed, rows are INSERT/UPDATE-by-FSC).
     *  - Abort on first chunk with errors: stops early to surface data problems,
     *    but rows from earlier chunks are already committed.
     *  - Abort only on hard failure (server down / 5xx): network blips shouldn't
     *    lose work; row-level errors are reported in the summary regardless, and
     *    the browser already offers a retry button on network failure.
     *
     * Return true to continue, false to stop the loop (browser stops and shows the
     * summary so far).
     *
     * @param array $chunkResult  processRows() result for the chunk just processed
     */
    private function shouldContinueAfterChunk(array $chunkResult): bool
    {
        // TODO(user): implement your policy here. Default = always continue.
        return true;
    }

    private function importRowsPath(string $token): string
    {
        return sys_get_temp_dir() . '/qc_import_' . $token . '.json';
    }

    private function importMetaPath(string $token): string
    {
        return sys_get_temp_dir() . '/qc_import_' . $token . '.meta.json';
    }

    /** Remove stale import temp files older than IMPORT_TTL. */
    private function gcImportTemp(): void
    {
        $dir = sys_get_temp_dir();
        $cutoff = time() - self::IMPORT_TTL;
        foreach ((glob($dir . '/qc_import_*.json') ?: []) as $f) {
            if (is_file($f) && @filemtime($f) < $cutoff) { @unlink($f); }
        }
        foreach ((glob($dir . '/qc_import_*.meta.json') ?: []) as $f) {
            if (is_file($f) && @filemtime($f) < $cutoff) { @unlink($f); }
        }
    }

    /**
     * Parse an uploaded CSV/XLSX file into the normalized row shape used by
     * processRows(). Shared by the single-shot uploadCsv() and the chunked
     * uploadChunk() paths so header-mapping logic lives in one place.
     *
     * @param string $tmp      Uploaded file path ($_FILES['...']['tmp_name'])
     * @param string $ext      Lowercase extension: 'xlsx' or anything else => CSV
     * @param bool   $collectDebug  Build the parser debug info block (CSV only)
     * @return array{0:array,1:array}  [$rows, $debugInfo] — $debugInfo may be empty
     * @throws \RuntimeException  On empty/unsupported files (caller surfaces to user)
     */
    private function parseFile(string $tmp, string $ext, bool $collectDebug = false): array
    {
        $rows = [];
        $debugInfo = [];
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
                            // Normalize header keys to be robust (trim, lowercase, remove newlines)
                            $header = array_map(function($h) {
                                $h = (string)$h;
                                // Remove newlines and extra spaces
                                $h = preg_replace('/\s+/', ' ', $h);
                                // Trim and lowercase
                                return strtolower(trim($h));
                            }, $arr);
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
                            'Title' => $pick($arr,$map,['title','description','desc','product description','productdescription','name']),
                            'SM_SOH' => $pick($arr,$map,['sm_soh','sm soh','smqty','sm_qty','sm qty']),
                            'WH_SOH' => $pick($arr,$map,['wh_soh','wh soh','whqty','wh_qty','wh qty']),
                            'TotalSOH' => $pick($arr,$map,['totalsoh','total_soh','total soh','totalqty','total_qty','total qty','qty','quantity','stock']),
                            'UnitSold' => $pick($arr,$map,['unitsold','unit_sold','unit sold','sold']),
                            'Categorycode' => $pick($arr,$map,['categorycode','category_code','category code','category','categories','categoryname','category name','collection','collection_name','collection name']),
                            'ProductType' => $pick($arr,$map,['producttype','product_type','product type','type','variant']),
                            'RegPrice' => $pick($arr,$map,['regprice','listprice','list price','price']),
                            'BrochurePrice' => $pick($arr,$map,['brochureprice','brochure price','brochure_selling_price','brochure selling price','brochure','sale price','sale_price']),
                            'Status' => $pick($arr,$map,['status']),
                            'ImageURLs' => $pick($arr,$map,['image urls','image_urls','images','image urls','images url']),
                            'PrimaryImageURL' => $pick($arr,$map,['primary image url','primary_image_url','primary image','primary_image','image url','image_url']),
                        ];
                    }
                } catch (\Throwable $e) {
                    throw new \RuntimeException('XLSX import failed: '.$e->getMessage(), 0, $e);
                }
            } else {
                // Lightweight fallback using ZipArchive and SimpleXML (handles simple sheets)
                try {
                    $parsed = $this->parseXlsx($tmp);
                    if (!$parsed) { throw new \RuntimeException('Empty XLSX or unsupported format'); }
                    $rawHeader = array_shift($parsed);
                    $header = array_map(function($h) {
                        $h = (string)$h;
                        // Remove newlines and extra spaces
                        $h = preg_replace('/\s+/', ' ', $h);
                        // Trim and lowercase
                        return strtolower(trim($h));
                    }, $rawHeader);
                    $map = array_flip($header);
                    $pick = function(array $rowVals, array $map, array $keys) {
                        foreach ($keys as $k) { if (isset($map[$k])) { return $rowVals[$map[$k]] ?? ''; } }
                        return '';
                    };
                    foreach ($parsed as $arr) {
                        if (count(array_filter($arr, fn($v)=>$v!==null && $v!==''))===0) continue;
                        $rows[] = [
                            'FSC' => $pick($arr,$map,['fsc']),
                            'Title' => $pick($arr,$map,['title','description','desc','product description','productdescription','name']),
                            'SM_SOH' => $pick($arr,$map,['sm_soh','sm soh','smqty','sm_qty','sm qty']),
                            'WH_SOH' => $pick($arr,$map,['wh_soh','wh soh','whqty','wh_qty','wh qty']),
                            'TotalSOH' => $pick($arr,$map,['totalsoh','total_soh','total soh','totalqty','total_qty','total qty','qty','quantity','stock']),
                            'UnitSold' => $pick($arr,$map,['unitsold','unit_sold','unit sold','sold']),
                            'Categorycode' => $pick($arr,$map,['categorycode','category_code','category code','category','categories','categoryname','category name','collection','collection_name','collection name']),
                            'ProductType' => $pick($arr,$map,['producttype','product_type','product type','type','variant']),
                            'RegPrice' => $pick($arr,$map,['regprice','listprice','list price','price']),
                            'BrochurePrice' => $pick($arr,$map,['brochureprice','brochure price','brochure_selling_price','brochure selling price','brochure','sale price','sale_price']),
                            'Status' => $pick($arr,$map,['status']),
                            'ImageURLs' => $pick($arr,$map,['image urls','image_urls','images','image urls','images url']),
                            'PrimaryImageURL' => $pick($arr,$map,['primary image url','primary_image_url','primary image','primary_image','image url','image_url']),
                        ];
                    }
                } catch (\Throwable $e) {
                    throw new \RuntimeException('XLSX not supported on this server. Please upload CSV instead. Details: '.$e->getMessage(), 0, $e);
                }
            }
        } else {
            // CSV path (default) with delimiter auto-detection and BOM handling
            if (($h = fopen($tmp, 'r')) !== false) {
                $firstLine = fgets($h);
                if ($firstLine === false) { throw new \RuntimeException('Empty CSV.'); }
                // Pick the most likely delimiter
                $candidates = [",", "\t", ";", "|"];
                $bestDelim = ","; $bestCount = -1;
                foreach ($candidates as $d) { $cnt = substr_count($firstLine, $d); if ($cnt > $bestCount) { $bestCount = $cnt; $bestDelim = $d; } }
                // Rewind and parse header with chosen delimiter
                rewind($h);
                $header = fgetcsv($h, 0, $bestDelim);
                if (!$header) { throw new \RuntimeException('Empty CSV.'); }
                // Remove UTF-8 BOM if present on first cell
                if (isset($header[0])) { $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]); }
                // Clean up headers: remove newlines, extra spaces, and normalize
                $headerClean = array_map(function($v) {
                    $v = (string)$v;
                    // Remove newlines and extra spaces
                    $v = preg_replace('/\s+/', ' ', $v);
                    // Trim spaces
                    $v = trim($v);
                    return $v;
                }, $header);
                // Normalize header keys (trim + lowercase) and map flexible names
                $headerNorm = array_map(fn($v)=> strtolower(trim((string)$v)), $headerClean);
                $map = array_flip($headerNorm);
                $pick = function(array $rowVals, array $map, array $keys) {
                    foreach ($keys as $k) { if (isset($map[$k])) { return $rowVals[$map[$k]] ?? ''; } }
                    return '';
                };
                while (($r = fgetcsv($h, 0, $bestDelim)) !== false) {
                    $rows[] = [
                        'FSC' => $pick($r,$map,['fsc']),
                        'Title' => $pick($r,$map,['title','description','desc','product description','productdescription','name']),
                        'SM_SOH' => $pick($r,$map,['sm_soh','sm soh','smqty','sm_qty','sm qty']),
                        'WH_SOH' => $pick($r,$map,['wh_soh','wh soh','whqty','wh_qty','wh qty']),
                        'TotalSOH' => $pick($r,$map,['totalsoh','total_soh','total soh','totalqty','total_qty','total qty','qty','quantity','stock']),
                        'UnitSold' => $pick($r,$map,['unitsold','unit_sold','unit sold','sold']),
                        'Categorycode' => $pick($r,$map,['categorycode','category_code','category code','category','categories','categoryname','category name','collection','collection_name','collection name']),
                        'ProductType' => $pick($r,$map,['producttype','product_type','product type','type','variant']),
                        'RegPrice' => $pick($r,$map,['regprice','listprice','list price','price']),
                        'BrochurePrice' => $pick($r,$map,['brochureprice','brochure price','brochure_selling_price','brochure selling price','brochure','brochure\nselling price','brochure \nselling price','sale price','sale_price']),
                        'Status' => $pick($r,$map,['status']),
                        'ImageURLs' => $pick($r,$map,['image urls','image_urls','images','image urls','images url']),
                        'PrimaryImageURL' => $pick($r,$map,['primary image url','primary_image_url','primary image','primary_image','image url','image_url']),
                    ];
                }
                fclose($h);
                // Build parser debug info for CSV
                if ($collectDebug) {
                    $debugInfo = [
                        'source' => 'csv',
                        'delimiter' => $bestDelim,
                        'header_raw' => $header,
                        'header_clean' => $headerClean,
                        'header_norm' => $headerNorm,
                        'category_index' => $map['category'] ?? ($map['category code'] ?? ($map['category_code'] ?? ($map['categorycode'] ?? null))),
                        'brochure_price_index' => $map['brochure selling price'] ?? ($map['brochureprice'] ?? ($map['brochure price'] ?? null)),
                    ];
                }
            }
        }
        return [$this->sanitizeUtf8Rows($rows), $debugInfo];
    }

    /**
     * Normalize cell strings to valid UTF-8. Excel on Windows often exports
     * Windows-1252/Latin-1 rather than UTF-8; json_encode() (used by the chunked
     * import path) returns false on non-UTF-8 bytes, which previously wrote an
     * empty temp file and surfaced as "Corrupt import rows" on the first chunk.
     *
     * mb-safe: if the mbstring extension is absent (common on stripped Windows
     * PHP builds), fall back to a preg_match('//u') validity check and the
     * always-available utf8_encode() (ISO-8859-1 -> UTF-8).
     */
    private function sanitizeUtf8Rows(array $rows): array
    {
        $hasMb = function_exists('mb_check_encoding') && function_exists('mb_convert_encoding');
        foreach ($rows as &$row) {
            foreach ($row as $k => $v) {
                if (!is_string($v) || $v === '') continue;
                $isUtf8 = $hasMb ? mb_check_encoding($v, 'UTF-8') : (@preg_match('//u', $v) === 1);
                if (!$isUtf8) {
                    $row[$k] = $hasMb ? mb_convert_encoding($v, 'UTF-8', 'Windows-1252') : @utf8_encode($v);
                }
            }
        }
        unset($row);
        return $rows;
    }

    private function processRows(array $rows, bool $dryRun, array $overrides = []): array
    {
        $pdo = DB::pdo();

        // Load custom variant patterns from settings (comma or newline separated)
        $customVariantsRaw = trim((string)\App\Core\setting('custom_variants', ''));
        $customVariants = array_filter(array_map('trim', preg_split('/[\s,]+/', $customVariantsRaw)));
        // custom_colors is now JSON {"NAME":"#hex"} — extract just the names
        $customColorNames = array_keys(\App\Core\qc_parse_custom_colors());
        $allCustomVariants = array_values(array_unique(array_merge($customVariants, $customColorNames)));

        $updatePrice = array_key_exists('update_price', $overrides)
            ? (bool)$overrides['update_price']
            : ((string)\App\Core\setting('sync_update_price','1') === '1');
        $updateTitle = array_key_exists('update_title', $overrides)
            ? (bool)$overrides['update_title']
            : ((string)\App\Core\setting('sync_update_title','1') === '1');
        $updateCollection = array_key_exists('update_collection', $overrides)
            ? (bool)$overrides['update_collection']
            : ((string)\App\Core\setting('sync_update_collection','1') === '1');
        $updateImages = array_key_exists('update_images', $overrides)
            ? (bool)$overrides['update_images']
            : isset($_POST['sync_update_images']);
        $collectPreview = !empty($overrides['collect_preview']);
        $collectDebug = !empty($overrides['collect_debug']);

        // Detect if product_images table exists
        $hasProductImages = false;
        try {
            $hasProductImages = $pdo->query("SHOW TABLES LIKE 'product_images'")->rowCount() > 0;
        } catch (\Throwable $e) { $hasProductImages = false; }

        // Detect if collections has a category_code column (for CSV matching by code)
        $hasCategoryCode = false;
        try {
            $hasCategoryCode = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'collections' AND COLUMN_NAME = 'category_code'")->fetchColumn() > 0;
        } catch (\Throwable $e) { $hasCategoryCode = false; }

        // Detect if products has a parent_product_id column (variants support).
        // This is a schema fact — constant for the whole request — so detect it
        // ONCE here, not inside the per-row loop (a SHOW COLUMNS per row is a major
        // cause of import timeouts on large CSVs).
        $hasVariantsColumn = false;
        try {
            $hasVariantsColumn = $pdo->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        } catch (\Throwable $e) { $hasVariantsColumn = false; }

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
                // Convert empty FSC to NULL to avoid duplicate key constraint issues with UNIQUE index
                if ($fsc === '') {
                    $fsc = null;
                }
                $title = trim((string)($row['Title'] ?? ''));
                // If title is empty, use FSC as fallback (but prefer keeping existing title when updating)
                if ($title === '' && $fsc !== null) { $title = $fsc; }
                $price = (float)($row['RegPrice'] ?? 0);
                $stock = (int)($row['TotalSOH'] ?? 0);
                $category = trim((string)($row['Categorycode'] ?? ''));
                $ptype = trim((string)($row['ProductType'] ?? ''));

                // Resolve/ensure collection using category_code when available; otherwise fall back to slug/title
                // NOTE: This must happen BEFORE variant detection so parent products get the correct collection
                $collectionId = null; $catSlug = '';
                if ($category !== '') {
                    if ($hasCategoryCode) {
                        $code = strtoupper($category);
                        // Match by category_code (case-insensitive)
                        $cst = $pdo->prepare('SELECT id FROM collections WHERE UPPER(category_code)=? LIMIT 1');
                        $cst->execute([$code]);
                        $cid = $cst->fetchColumn();
                        if (!$cid && !$dryRun) {
                            // Create collection with code as title by default; user can rename title later for customers
                            $catSlug = preg_replace('/[^a-z0-9]+/','-', strtolower($code));
                            $catSlug = trim($catSlug, '-') ?: ('collection-'.substr(md5($code.microtime(true)),0,6));
                            $baseSlug = $catSlug; $suffix = 1;
                            while ((int)$pdo->query('SELECT COUNT(*) FROM collections WHERE slug='.$pdo->quote($catSlug))->fetchColumn() > 0) {
                                $catSlug = $baseSlug.'-'.$suffix++; if ($suffix>1000) break;
                            }
                            $ins = $pdo->prepare('INSERT INTO collections (title,slug,description,category_code) VALUES (?,?,?,?)');
                            $ins->execute([$code,$catSlug,null,$code]);
                            $cid = (int)$pdo->lastInsertId();
                        } else {
                            // Also compute slug for debug table
                            $catSlug = preg_replace('/[^a-z0-9]+/','-', strtolower($category));
                            $catSlug = trim($catSlug, '-');
                        }
                    } else {
                        // Legacy behavior: match by slug OR title, create if missing
                        $catSlug = preg_replace('/[^a-z0-9]+/','-', strtolower($category));
                        $catSlug = trim($catSlug, '-');
                        if ($catSlug !== '') {
                            $cst = $pdo->prepare('SELECT id FROM collections WHERE slug=? OR LOWER(title)=LOWER(?) LIMIT 1');
                            $cst->execute([$catSlug, $category]);
                        } else {
                            $cst = $pdo->prepare('SELECT id FROM collections WHERE LOWER(title)=LOWER(?) LIMIT 1');
                            $cst->execute([$category]);
                        }
                        $cid = $cst->fetchColumn();
                        if (!$cid && !$dryRun) {
                            $slugCandidate = $catSlug !== '' ? $catSlug : preg_replace('/[^a-z0-9]+/','-', strtolower($category));
                            $slugCandidate = trim($slugCandidate, '-') ?: ('collection-'.substr(md5($category.microtime(true)),0,6));
                            $baseSlug = $slugCandidate; $suffix = 1;
                            while ((int)$pdo->query('SELECT COUNT(*) FROM collections WHERE slug='.$pdo->quote($slugCandidate))->fetchColumn() > 0) {
                                $slugCandidate = $baseSlug+'-'.$suffix++; if ($suffix>1000) break;
                            }
                            $pdo->prepare('INSERT INTO collections (title,slug,description) VALUES (?,?,?)')
                                ->execute([$category,$slugCandidate,null]);
                            $cid = (int)$pdo->lastInsertId();
                        }
                    }
                    $collectionId = $cid ? (int)$cid : null;
                }
                // For variants, first try ProductType column, then extract from title
                // NOTE: This must happen AFTER collection resolution so parent products get the correct collection
                $variantAttributes = null;
                $parentProductId = null;
                $parentTitle = $title; // Default: use current title as parent

                // Auto-detect variants during CSV import using the shared helper functions
                if ($hasVariantsColumn) {
                    // First, try extracting variant from the title using shared function (handles sizes AND colors)
                    $extracted = \App\Core\qc_extract_variant_attribute($title, $allCustomVariants);
                    if ($extracted !== '') {
                        $variantAttributes = $extracted;
                        $parentTitle = \App\Core\qc_extract_base_title($title, $allCustomVariants);
                    } elseif ($ptype !== '' && strtoupper($ptype) !== strtoupper($title)) {
                        // Only use ProductType column if title-based extraction found nothing
                        // AND the ptype is different from the title
                        // Validate that ptype looks like a variant (not a generic product category)
                        $ptypeUpper = strtoupper(trim($ptype));
                        $genericTypes = ['BRIEF','BRA','PANTY','PANTIES','PANT','SHIRT','DRESS','SKIRT',
                            'UNDERWEAR','LINGERIE','SOCKS','HOSE','TOP','BOTTOM','SWIMWEAR','SWIMSUIT',
                            'TOWEL','BLANKET','SHEET','PILLOW','ROBE','GOWN','CAMISOLE','CORSET',
                            'GIRDLE','BODYSUIT','SLIP','PETTICOAT','NEGIGEE','PAJAMA','SHORTS','CAPRI'];
                        if (!in_array($ptypeUpper, $genericTypes) && strlen($ptypeUpper) <= 10) {
                            $variantAttributes = $ptype;
                        }
                    }

                    // If we have a variant, look up or create the parent product
                    if ($variantAttributes !== '' && $variantAttributes !== null && !$dryRun) {
                        $parentCheck = $pdo->prepare('SELECT id FROM products WHERE title=? AND (parent_product_id IS NULL OR parent_product_id=0) LIMIT 1');
                        $parentCheck->execute([$parentTitle]);
                        $existingParent = $parentCheck->fetchColumn();
                        if ($existingParent) {
                            $parentProductId = (int)$existingParent;
                        } else {
                            // Create parent product
                            $parentSlug = preg_replace('/[^a-z0-9]+/','-', strtolower((string)$parentTitle));
                            $parentSlug = trim($parentSlug, '-');
                            if ($parentSlug === '') {
                                $parentSlug = 'product-'.substr(md5($parentTitle . microtime(true)), 0, 6);
                            }
                            $baseSlug = $parentSlug; $suffix = 1;
                            while ((int)$pdo->query('SELECT COUNT(*) FROM products WHERE slug='.$pdo->quote($parentSlug))->fetchColumn() > 0) {
                                $parentSlug = $baseSlug.'-'.$suffix++;
                                if ($suffix > 1000) break;
                            }
                            $pdo->prepare('INSERT INTO products (title,slug,fsc,price,sale_price,status,stock,collection_id,parent_product_id,variant_attributes,created_at) VALUES (?,?,?,?,?,\'active\',0,?, NULL, NULL, NOW())')
                                ->execute([$parentTitle,$parentSlug,null,0,0,$collectionId]);
                            $parentProductId = (int)$pdo->lastInsertId();
                        }
                    }
                }
                // Find product by FSC
                $pst = $pdo->prepare('SELECT id, title, price, stock, collection_id FROM products WHERE fsc=?');
                $pst->execute([$fsc]);
                $p = $pst->fetch();

                // Fallback: try to match existing product by slug or exact title to preserve images
                if (!$p) {
                    $slugTitle = preg_replace('/[^a-z0-9]+/','-', strtolower((string)$title));
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
                    // Build a safe, unique slug - use full title for variants
                    $slugTitle = preg_replace('/[^a-z0-9]+/','-', strtolower((string)$title));
                    $slugTitle = trim($slugTitle, '-');
                    // For variants, append variant identifier to slug to ensure uniqueness
                    if ($variantAttributes !== '' && $variantAttributes !== null) {
                        $variantSlug = preg_replace('/[^a-z0-9]+/','-', strtolower((string)$variantAttributes));
                        $variantSlug = trim($variantSlug, '-');
                        if ($variantSlug !== '' && !str_ends_with($slugTitle, $variantSlug)) {
                            $slugTitle = $slugTitle . '-' . $variantSlug;
                        }
                    }
                    if ($slugTitle === '') {
                        $slugTitle = preg_replace('/[^a-z0-9]+/','-', strtolower((string)$fsc));
                        $slugTitle = trim($slugTitle, '-');
                    }
                    if ($slugTitle === '') { $slugTitle = 'product-'.substr(md5((string)$fsc.microtime(true)),0,6); }
                    $baseSlug = $slugTitle; $suffix = 1;
                    while ((int)$pdo->query('SELECT COUNT(*) FROM products WHERE slug='.$pdo->quote($slugTitle))->fetchColumn() > 0) {
                        $slugTitle = $baseSlug.'-'.$suffix++; if ($suffix>1000) break; // safety
                    }
                    // Map brochure price to sale_price field
                    $salePrice = !empty($row['BrochurePrice']) ? (float)$row['BrochurePrice'] : 0;
                    // Build INSERT - use detected parent_product_id and variant_attributes if found
                    if ($hasVariantsColumn) {
                        $pdo->prepare('INSERT INTO products (title,slug,fsc,price,sale_price,status,stock,collection_id,parent_product_id,variant_attributes,created_at) VALUES (?,?,?,?,?,\'active\',?,?,?,?, NOW())')
                            ->execute([$title,$slugTitle,$fsc,$price,$salePrice,$stock,$collectionId,$parentProductId,$variantAttributes]);
                    } else {
                        $pdo->prepare('INSERT INTO products (title,slug,fsc,price,sale_price,status,stock,collection_id,created_at) VALUES (?,?,?,?,?,\'active\',?, ?, NOW())')
                            ->execute([$title,$slugTitle,$fsc,$price,$salePrice,$stock,$collectionId]);
                    }
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
                    // IMPORTANT: Always update FSC to ensure variants have their correct FSC codes from CSV
                    $fields[] = 'fsc = ?';
                    $vals[] = $fsc;
                    if ($updatePrice) { $fields[] = 'price = ?'; $vals[] = $price; }
                    // Link variant to parent if variant was detected and product doesn't already have one
                    // IMPORTANT: Never link a product to itself as its own parent
                    if ($hasVariantsColumn && $parentProductId && $variantAttributes !== '' && $variantAttributes !== null
                        && (int)$p['id'] !== $parentProductId) {
                        $existingParentStmt = $pdo->prepare('SELECT parent_product_id FROM products WHERE id=?');
                        $existingParentStmt->execute([(int)$p['id']]);
                        $existingParent = $existingParentStmt->fetchColumn();
                        if (empty($existingParent) || $existingParent == 0) {
                            $fields[] = 'parent_product_id = ?';
                            $vals[] = $parentProductId;
                            $fields[] = 'variant_attributes = ?';
                            $vals[] = $variantAttributes;
                        }
                    }
                    // Always update sale price from brochure price in CSV
                    $salePrice = !empty($row['BrochurePrice']) ? (float)$row['BrochurePrice'] : 0;

                    // Debug: Log brochure price processing
                    if ($collectDebug && count($debugRows) < 500) {
                        if (!empty($row['BrochurePrice'])) {
                            error_log("FSC: $fsc, Raw BrochurePrice: '" . $row['BrochurePrice'] . "', Parsed Sale Price: $salePrice");
                        }
                    }
                    $fields[] = 'sale_price = ?';
                    $vals[] = $salePrice;
                    // Smart title update: only update if CSV has a meaningful title (not just FSC)
                    if ($updateTitle && $title !== '' && $title !== $fsc) {
                        $fields[] = 'title = ?';
                        $vals[] = $title;
                    }
                    if ($updateCollection && $collectionId) { $fields[] = 'collection_id = ?'; $vals[] = $collectionId; }
                    $vals[] = (int)$p['id'];
                    $sql = 'UPDATE products SET '.implode(',', $fields).' WHERE id = ?';
                    $pdo->prepare($sql)->execute($vals);
                    $updated++;
                }
                // Optional: tag with ProductType (no DDL here; DDL was done before transaction)
                if ($ptype !== '' && !$dryRun) {
                    try {
                        $slug = preg_replace('/[^a-z0-9]+/','-', strtolower($ptype));
                        $t = $pdo->prepare('INSERT INTO tags (name,slug) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
                        $t->execute([$ptype,$slug]);
                        $tagId = (int)$pdo->query('SELECT id FROM tags WHERE slug='.$pdo->quote($slug))->fetchColumn();
                        // Get product ID - try by FSC first, then fallback to slug/title
                        if ($fsc !== null) {
                            $pid = (int)$pdo->query('SELECT id FROM products WHERE fsc='.$pdo->quote($fsc))->fetchColumn();
                        } else {
                            $slugTitle = preg_replace('/[^a-z0-9]+/','-', strtolower((string)$title));
                            $pid = (int)$pdo->query('SELECT id FROM products WHERE slug='.$pdo->quote($slugTitle).' OR title='.$pdo->quote($title).' LIMIT 1')->fetchColumn();
                        }
                        if ($tagId && $pid) { $pdo->prepare('INSERT IGNORE INTO product_tags (product_id, tag_id) VALUES (?,?)')->execute([$pid,$tagId]); }
                    } catch (\Throwable $e) { /* ignore tags failures */ }
                }

                // Import images if available
                if ($hasProductImages && !$dryRun) {
                    try {
                        $imageUrls = trim((string)($row['ImageURLs'] ?? ''));
                        if ($fsc !== null || $imageUrls !== '') {
                            // Get product ID - try by FSC first, then fallback to slug/title
                            if ($fsc !== null) {
                                $pid = (int)$pdo->query('SELECT id FROM products WHERE fsc='.$pdo->quote($fsc))->fetchColumn();
                            } else {
                                $slugTitle = preg_replace('/[^a-z0-9]+/','-', strtolower((string)$title));
                                $pid = (int)$pdo->query('SELECT id FROM products WHERE slug='.$pdo->quote($slugTitle).' OR title='.$pdo->quote($title).' LIMIT 1')->fetchColumn();
                            }
                            if ($pid) {
                                $urlsToImport = [];

                                // If CSV has ImageURLs column, use those
                                if ($imageUrls !== '') {
                                    $urlsToImport = array_map('trim', explode(',', $imageUrls));
                                } else {
                                    // Auto-detect images from uploads folder based on FSC
                                    $urlsToImport = $this->autoDetectImagesForProduct($pdo, $pid, $fsc);
                                }

                                // For updates with update_images option, delete existing images first
                                if ($updateImages && $p && count($urlsToImport) > 0) {
                                    $pdo->prepare('DELETE FROM product_images WHERE product_id=?')->execute([$pid]);
                                }

                                // Only import images if this is a new product or update_images is enabled
                                if ((!$p || $updateImages) && count($urlsToImport) > 0) {
                                    $sortOrder = 0;
                                    foreach ($urlsToImport as $url) {
                                        if ($url === '') continue;
                                        // Store URL as-is (can be absolute or relative)
                                        $pdo->prepare('INSERT INTO product_images (product_id, url, sort_order) VALUES (?, ?, ?)')
                                            ->execute([$pid, $url, $sortOrder++]);
                                    }
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        error_log('Image import error for FSC ' . ($fsc ?? 'null') . ': ' . $e->getMessage());
                    }
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

    /**
     * Auto-detect images from uploads folder based on FSC
     * Scans /uploads/products/ folder for files matching patterns:
     * - {FSC}-{number}.{ext}
     * - {FSC}.{ext}
     * - {FSC}-{random}.{ext}
     *
     * For parent products with variants, will also check first variant's FSC
     *
     * @param \PDO $pdo Database connection
     * @param int $productId Product ID
     * @param string|null $fsc FSC code to match
     * @return array Array of image URLs found
     */
    private function autoDetectImagesForProduct(\PDO $pdo, int $productId, ?string $fsc): array
    {
        if ($fsc === null || $fsc === '') {
            return [];
        }

        $urls = [];
        $uploadsDir = BASE_PATH . '/public/uploads/products';

        if (!is_dir($uploadsDir)) {
            return [];
        }

        // First, check if images already exist for this product
        $existingStmt = $pdo->prepare('SELECT url FROM product_images WHERE product_id = ? ORDER BY sort_order');
        $existingStmt->execute([$productId]);
        $existingImages = $existingStmt->fetchAll(\PDO::FETCH_COLUMN);

        if (!empty($existingImages)) {
            // Images already exist for this product, return them
            return $existingImages;
        }

        // Check if this is a parent product with variants
        // If so, also look for images matching the first variant's FSC
        $hasVariantsColumn = false;
        try {
            $hasVariantsColumn = $pdo->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        } catch (\Throwable $e) { $hasVariantsColumn = false; }

        $fscList = [$fsc];

        if ($hasVariantsColumn) {
            // Check if this product is a parent (has variants)
            $variantStmt = $pdo->prepare('
                SELECT fsc
                FROM products
                WHERE parent_product_id = ?
                AND fsc IS NOT NULL
                AND fsc != ""
                ORDER BY id ASC
                LIMIT 1
            ');
            $variantStmt->execute([$productId]);
            $firstVariantFsc = $variantStmt->fetchColumn();

            if ($firstVariantFsc) {
                $fscList[] = $firstVariantFsc;
            }

            // Also check if this product itself is a variant
            // If so, get parent product info
            $parentStmt = $pdo->prepare('
                SELECT p.fsc
                FROM products p
                INNER JOIN products child ON child.parent_product_id = p.id
                WHERE child.id = ?
                AND p.fsc IS NOT NULL
                AND p.fsc != ""
                LIMIT 1
            ');
            $parentStmt->execute([$productId]);
            $parentFsc = $parentStmt->fetchColumn();

            if ($parentFsc) {
                $fscList[] = $parentFsc;
            }
        }

        // Build flexible patterns to match images
        // Supports: {FSC}.{ext}, {FSC}-{number}.{ext}, {FSC}-{random}.{ext}
        $patterns = [];
        foreach ($fscList as $fscCode) {
            $escapedFsc = preg_quote($fscCode, '/');
            // Pattern 1: {FSC}-{number}.{ext} (e.g., ABC123-1.jpg, ABC123-2.jpg)
            $patterns[] = '/' . $escapedFsc . '-\d+\.(jpg|jpeg|png|gif|webp)/i';
            // Pattern 2: {FSC}-{random}.{ext} (e.g., ABC123-abc123.jpg)
            $patterns[] = '/' . $escapedFsc . '-[^.]+\.(jpg|jpeg|png|gif|webp)/i';
            // Pattern 3: {FSC}.{ext} (e.g., ABC123.jpg)
            $patterns[] = '/' . $escapedFsc . '\.(jpg|jpeg|png|gif|webp)/i';
        }

        // Walk the uploads tree ONCE per request and reuse the flat path list.
        // Re-scanning the whole directory for every CSV row is O(rows x files)
        // and is the dominant cause of import timeouts on large catalogs. The
        // filesystem layout is fixed for the duration of the import, so we cache
        // it in a static variable; only the cheap per-FSC regex matching repeats.
        static $fileList = null;
        if ($fileList === null) {
            $fileList = [];
            if (is_dir($uploadsDir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($uploadsDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $fileList[] = $file->getPathname();
                    }
                }
            }
        }

        $foundFiles = [];

        foreach ($fileList as $pathname) {
            $filename = basename($pathname);

            // Check each pattern
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $filename)) {
                    // Determine sort order based on filename pattern
                    $sortOrder = 0;
                    if (preg_match('/-(\d+)\.(jpg|jpeg|png|gif|webp)$/i', $filename, $matches)) {
                        $sortOrder = (int)$matches[1];
                    }

                    $foundFiles[] = [
                        'path' => $pathname,
                        'filename' => $filename,
                        'sort' => $sortOrder,
                    ];
                    break; // Found a match, no need to check other patterns
                }
            }
        }

        // Sort by sort order
        usort($foundFiles, function($a, $b) {
            return $a['sort'] - $b['sort'];
        });

        // Ensure the product's upload folder exists
        $productUploadDir = BASE_PATH . '/public/uploads/products/' . $productId;
        if (!is_dir($productUploadDir)) {
            @mkdir($productUploadDir, 0755, true);
        }

        // Copy files to product's folder and create URLs
        foreach ($foundFiles as $fileInfo) {
            // Get relative path from public/uploads
            $relativePath = str_replace(BASE_PATH . '/public/', '', $fileInfo['path']);
            $url = '/' . $relativePath;

            // Copy image to product's folder for consistency
            $destPath = $productUploadDir . '/' . $fileInfo['filename'];
            if (!file_exists($destPath)) {
                @copy($fileInfo['path'], $destPath);
            }

            // Use the URL from product's folder for consistency
            $productUrl = '/public/uploads/products/' . $productId . '/' . $fileInfo['filename'];
            $urls[] = $productUrl;
        }

        return $urls;
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



