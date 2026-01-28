<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class AdminProductsController extends Controller
{
    public function index(): void
    {
        $pdo = DB::pdo();
        $where = ['1=1'];
        $params = [];

        // Build WHERE conditions
        if (!empty($_GET['status'])) {
            $where[] = 'status=?';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['collection_id'])) {
            $where[] = 'collection_id=?';
            $params[] = (int)$_GET['collection_id'];
        }
        if (!empty($_GET['q'])) {
            $q = trim((string)$_GET['q']);
            // Detect optional FSC/barcode columns
            $hasSku = $pdo->query("SHOW COLUMNS FROM products LIKE 'fsc'")->rowCount() > 0;
            $hasBarcode = $pdo->query("SHOW COLUMNS FROM products LIKE 'barcode'")->rowCount() > 0;
            $like = '%'.$q.'%';

            // Build search condition - always search by title and available fields
            if ($hasSku && $hasBarcode) {
                $where[] = "(title LIKE ? OR fsc LIKE ? OR barcode LIKE ?)";
                $params[]=$like; $params[]=$like; $params[]=$like;
            }
            elseif ($hasSku) {
                $where[] = "(title LIKE ? OR fsc LIKE ?)";
                $params[]=$like; $params[]=$like;
            }
            elseif ($hasBarcode) {
                $where[] = "(title LIKE ? OR barcode LIKE ?)";
                $params[]=$like; $params[]=$like;
            }
            else {
                $where[] = "title LIKE ?";
                $params[]=$like;
            }

            // If it's a pure number, also search by ID (as an additional OR condition)
            if (ctype_digit($q)) {
                // Wrap the previous condition with ID search
                $lastIndex = count($where) - 1;
                $where[$lastIndex] = "(id = ? OR " . $where[$lastIndex] . ")";
                // Add ID parameter at the beginning
                array_unshift($params, (int)$q);
            }
        }
        if (!empty($_GET['low'])) {
            $where[] = 'COALESCE(stock,0) <= 3';
        }

        // Variant filter - by default hide variants, only show parent products
        $hasVariants = $pdo->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        $showVariants = isset($_GET['show_variants']) && $_GET['show_variants'] === '1';
        if ($hasVariants && !$showVariants) {
            $where[] = '(parent_product_id IS NULL OR parent_product_id = 0)';
        }

        // Pagination setup
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [25, 50, 100]) ? (int)$_GET['per_page'] : 50;
        $offset = ($page - 1) * $perPage;

        // Count total products for pagination
        $countSql = 'SELECT COUNT(*) FROM products WHERE ' . implode(' AND ', $where);
        $countSt = $pdo->prepare($countSql);
        $countSt->execute($params);
        $totalProducts = (int)$countSt->fetchColumn();
        $totalPages = $perPage > 0 ? ceil($totalProducts / $perPage) : 1;

        // Get overall statistics (not filtered by current search/filters)
        $stats = [
            'total_products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'active_products' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE status = "active"')->fetchColumn(),
            'draft_products' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE status = "draft"')->fetchColumn(),
            'low_stock_products' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE COALESCE(stock,0) <= 3')->fetchColumn()
        ];

        // Get products for current page
        // Include FSC/barcode columns when available for display
        $hasSku = $pdo->query("SHOW COLUMNS FROM products LIKE 'fsc'")->rowCount() > 0;
        $hasBarcode = $pdo->query("SHOW COLUMNS FROM products LIKE 'barcode'")->rowCount() > 0;
        $hasVariants = $pdo->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        $cols = ['id','title','price','status','COALESCE(stock,0) AS stock','collection_id', '(SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url'];
        if ($hasSku) { $cols[] = 'fsc AS sku'; }
        if ($hasBarcode) { $cols[] = 'barcode'; }
        if ($hasVariants) {
            $cols[] = 'parent_product_id';
            $cols[] = 'variant_attributes';
        }
        $sql = 'SELECT '.implode(',', $cols).' FROM products p WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        // Get collections for filters
        $collections = $pdo->query('SELECT id,title FROM collections ORDER BY title')->fetchAll();

        // Pagination data
        $pagination = [
            'current_page' => $page,
            'total_pages' => max(1, $totalPages),
            'total_products' => $totalProducts,
            'per_page' => $perPage,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => max(1, $page - 1),
            'next_page' => min($totalPages, $page + 1),
            'start_item' => $totalProducts > 0 ? $offset + 1 : 0,
            'end_item' => min($offset + $perPage, $totalProducts)
        ];

        $this->adminView('admin/products/index', [
            'title' => 'Products',
            'products' => $rows,
            'collections' => $collections,
            'pagination' => $pagination,
            'stats' => $stats
        ]);
    }

    public function create(): void
    {
        $collections = DB::pdo()->query('SELECT id,title FROM collections ORDER BY title')->fetchAll();
        $hasVariants = DB::pdo()->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        // Initialize empty product for create form
        $product = [
            'id' => null,
            'title' => '',
            'slug' => '',
            'description' => '',
            'price' => 0,
            'sale_price' => 0,
            'sale_start' => null,
            'sale_end' => null,
            'stock' => 0,
            'sku' => '',
            'barcode' => '',
            'status' => 'active',
            'collection_id' => null,
            'featured' => 0,
            'created_at' => null,
            'updated_at' => null,
            'parent_product_id' => null
        ];
        $this->adminView('admin/products/form', [
            'title' => 'Add Product',
            'product' => $product,
            'collections' => $collections,
            'images' => [],
            'events' => [],
            'tagsCsv' => '',
            'variants' => [],
            'parentProduct' => null,
            'hasVariants' => $hasVariants
        ]);
    }

    public function store(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/products'); }
        $title = trim($_POST['title'] ?? ''); $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $title));
        $price = (float)($_POST['price'] ?? 0); $status = $_POST['status'] ?? 'active';
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $collection_id = !empty($_POST['collection_id']) ? (int)$_POST['collection_id'] : null;

        // Check if slug already exists
        $slugCheck = DB::pdo()->prepare('SELECT id FROM products WHERE slug=? LIMIT 1');
        $slugCheck->execute([$slug]);
        if ($slugCheck->fetch()) {
            // Make slug unique by appending random string
            $slug = $slug . '-' . substr(md5(uniqid('', true)), 0, 6);
        }

        // Optional FSC/Barcode + duplicate checks
        $pdo = DB::pdo();
        $hasSku = $pdo->query("SHOW COLUMNS FROM products LIKE 'fsc'")->rowCount() > 0;
        $hasBarcode = $pdo->query("SHOW COLUMNS FROM products LIKE 'barcode'")->rowCount() > 0;
        $sku = trim((string)($_POST['fsc'] ?? '')) ?: null;
        $barcode = trim((string)($_POST['barcode'] ?? '')) ?: null;
        if ($hasSku && $sku) {
            $chk = $pdo->prepare('SELECT id FROM products WHERE fsc=? LIMIT 1'); $chk->execute([$sku]);
            if ($chk->fetch()) { $_SESSION['error'] = 'FSC already exists.'; $this->redirect('/admin/products/create'); }
        }
        if ($hasBarcode && $barcode) {
            $chk = $pdo->prepare('SELECT id FROM products WHERE barcode=? LIMIT 1'); $chk->execute([$barcode]);
            if ($chk->fetch()) { $_SESSION['error'] = 'Barcode already exists.'; $this->redirect('/admin/products/create'); }
        }

        if ($hasSku || $hasBarcode) {
            $cols = ['title','slug','description','price','status','stock','collection_id','created_at'];
            $vals = [$title,$slug,$_POST['description'] ?? '',$price,$status,$stock,$collection_id];
            $placeholders = '?,?,?,?,?,?,?,NOW()';
            if ($hasBarcode) { array_unshift($cols,'barcode'); array_unshift($vals,$barcode); $placeholders = '?,'.$placeholders; }
            if ($hasSku) { array_unshift($cols,'fsc'); array_unshift($vals,$sku); $placeholders = '?,'.$placeholders; }
            $stmt = $pdo->prepare('INSERT INTO products ('.implode(',',$cols).') VALUES ('.$placeholders.')');
            $stmt->execute($vals);
        } else {
            $stmt = $pdo->prepare('INSERT INTO products (title, slug, description, price, status, stock, collection_id, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
            $stmt->execute([$title,$slug,$_POST['description'] ?? '',$price,$status,$stock,$collection_id]);
        }
        $pid = (int)$pdo->lastInsertId();

        // Try to set sale fields if supported
        try {
            $sale_price = ($_POST['sale_price'] === '' ? 0 : (float)$_POST['sale_price']);
            $sale_start = ($_POST['sale_start'] === '' ? null : $_POST['sale_start']);
            $sale_end   = ($_POST['sale_end'] === '' ? null : $_POST['sale_end']);

            $pdo->prepare('UPDATE products SET sale_price=?, sale_start=?, sale_end=? WHERE id=?')->execute([$sale_price,$sale_start,$sale_end,$pid]);
        } catch (\Throwable $e) {}
        // Update tags
        if (isset($_POST['tags'])) { $this->updateTags($pid, (string)$_POST['tags']); }
        $this->handleUploads($pid);
        $this->redirect('/admin/products');
    }

    public function edit(array $params): void
    {
        $stmt = DB::pdo()->prepare('SELECT * FROM products WHERE id=?'); $stmt->execute([$params['id']]);
        $p = $stmt->fetch(); if(!$p){ $this->redirect('/admin/products'); }
        $collections = DB::pdo()->query('SELECT id,title FROM collections ORDER BY title')->fetchAll();
        $imgs = DB::pdo()->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order');
        $imgs->execute([(int)$params['id']]);

        // Fetch variants and parent product info
        $hasVariants = DB::pdo()->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        $variants = [];
        $parentProduct = null;
        $suggestedVariants = [];

        if ($hasVariants) {
            // If this is a variant, get parent info
            if (!empty($p['parent_product_id'])) {
                $parentStmt = DB::pdo()->prepare('SELECT id,title FROM products WHERE id=?');
                $parentStmt->execute([$p['parent_product_id']]);
                $parentProduct = $parentStmt->fetch();
            }

            // Get all variants of this product (if it's a parent)
            $variantStmt = DB::pdo()->prepare('
                SELECT p.*, i.url as image_url
                FROM products p
                LEFT JOIN (SELECT product_id, url FROM product_images GROUP BY product_id) i ON i.product_id = p.id
                WHERE p.parent_product_id = ?
                ORDER BY p.variant_attributes
            ');
            $variantStmt->execute([(int)$params['id']]);
            $variants = $variantStmt->fetchAll();

            // Find potential variants - products with similar base title but not already linked
            // Extract base title by removing common variant patterns from the end
            $baseTitle = $this->extractBaseTitle($p['title']);
            if ($baseTitle && strlen($baseTitle) >= 5) {
                // Find products whose title starts with the base title but are different
                $suggestStmt = DB::pdo()->prepare('
                    SELECT p.*, i.url as image_url
                    FROM products p
                    LEFT JOIN (SELECT product_id, url FROM product_images GROUP BY product_id) i ON i.product_id = p.id
                    WHERE p.id != ? AND p.parent_product_id IS NULL
                    AND (p.title LIKE ? OR p.title LIKE ?)
                    ORDER BY p.title
                    LIMIT 20
                ');
                $likePattern = $baseTitle . ' %';
                $suggestStmt->execute([(int)$params['id'], $likePattern, $baseTitle . '%']);
                $suggestedVariants = $suggestStmt->fetchAll();
            }
        }

        // Tags
        $tagsCsv = '';
        try {
            $tg = DB::pdo()->prepare('SELECT t.name FROM product_tags pt JOIN tags t ON t.id=pt.tag_id WHERE pt.product_id=? ORDER BY t.name');
            $tg->execute([(int)$params['id']]);
            $tagsCsv = implode(', ', array_map(fn($r)=>$r['name'], $tg->fetchAll()));
        } catch (\Throwable $e) {}
        // Stock events (best-effort)
        try {
            $ev = DB::pdo()->prepare('SELECT * FROM product_stock_events WHERE product_id=? ORDER BY id DESC LIMIT 20');
            $ev->execute([(int)$params['id']]); $events = $ev->fetchAll();
        } catch (\Throwable $e) { $events = []; }
        $this->adminView('admin/products/form', [
            'title' => 'Edit Product',
            'product'=>$p,
            'collections'=>$collections,
            'images'=>$imgs->fetchAll(),
            'events'=>$events,
            'tagsCsv'=>$tagsCsv,
            'variants' => $variants,
            'parentProduct' => $parentProduct,
            'hasVariants' => $hasVariants,
            'suggestedVariants' => $suggestedVariants
        ]);
    }

    public function update(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/products'); }
        $title = trim($_POST['title'] ?? ''); $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $title));
        $price = (float)($_POST['price'] ?? 0); $status = $_POST['status'] ?? 'active';
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $collection_id = !empty($_POST['collection_id']) ? (int)$_POST['collection_id'] : null;

        // Check if slug already exists for another product
        $slugCheck = DB::pdo()->prepare('SELECT id FROM products WHERE slug=? AND id<>? LIMIT 1');
        $slugCheck->execute([$slug, (int)$params['id']]);
        if ($slugCheck->fetch()) {
            // Make slug unique by appending ID
            $slug = $slug . '-' . (int)$params['id'];
        }

        DB::pdo()->prepare('UPDATE products SET title=?, slug=?, description=?, price=?, status=?, stock=?, collection_id=? WHERE id=?')
            ->execute([$title,$slug,$_POST['description'] ?? '',$price,$status,$stock,$collection_id,$params['id']]);
        // Optional: SKU / Barcode with duplicate check
        try {
            $pdo = DB::pdo();
            $hasSku = $pdo->query("SHOW COLUMNS FROM products LIKE 'fsc'")->rowCount() > 0;
            $hasBarcode = $pdo->query("SHOW COLUMNS FROM products LIKE 'barcode'")->rowCount() > 0;
            $sku = trim((string)($_POST['fsc'] ?? '')) ?: null;
            $barcode = trim((string)($_POST['barcode'] ?? '')) ?: null;
            if ($hasSku && $sku) {
                $chk = $pdo->prepare('SELECT id FROM products WHERE fsc=? AND id<>? LIMIT 1'); $chk->execute([$sku,(int)$params['id']]);
                if ($chk->fetch()) { $_SESSION['error'] = 'FSC already exists.'; $this->redirect('/admin/products/'.(int)$params['id'].'/edit'); }
            }
            if ($hasBarcode && $barcode) {
                $chk = $pdo->prepare('SELECT id FROM products WHERE barcode=? AND id<>? LIMIT 1'); $chk->execute([$barcode,(int)$params['id']]);
                if ($chk->fetch()) { $_SESSION['error'] = 'Barcode already exists.'; $this->redirect('/admin/products/'.(int)$params['id'].'/edit'); }
            }
            if ($hasSku || $hasBarcode) {
                $set = [];$vals=[];
                if ($hasSku) { $set[]='fsc=?'; $vals[]=$sku; }
                if ($hasBarcode) { $set[]='barcode=?'; $vals[]=$barcode; }
                if ($set) { $pdo->prepare('UPDATE products SET '.implode(',', $set).' WHERE id=?')->execute(array_merge($vals, [(int)$params['id']])); }
            }
        } catch (\Throwable $e) { /* ignore */ }
        // Try to set sale fields if supported
        try {
            $sale_price = ($_POST['sale_price'] === '' ? 0 : (float)$_POST['sale_price']);
            $sale_start = ($_POST['sale_start'] === '' ? null : $_POST['sale_start']);
            $sale_end   = ($_POST['sale_end'] === '' ? null : $_POST['sale_end']);

            DB::pdo()->prepare('UPDATE products SET sale_price=?, sale_start=?, sale_end=? WHERE id=?')
                ->execute([$sale_price,$sale_start,$sale_end,(int)$params['id']]);
        } catch (\Throwable $e) {}
        // Update tags
        if (isset($_POST['tags'])) { $this->updateTags((int)$params['id'], (string)$_POST['tags']); }

        $this->handleUploads((int)$params['id']);
        $this->redirect('/admin/products/'.(int)$params['id'].'/edit');
    }

    public function destroy(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/products'); }
        $pdo = DB::pdo();
        $productId = (int)$params['id'];

        // Check if product is in draft status - if so, preserve images by not deleting
        $stmt = $pdo->prepare('SELECT status FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($product && $product['status'] === 'draft') {
            // Don't delete draft products - they should be kept to preserve images
            $_SESSION['error'] = 'Cannot delete draft products. Change status to active first or use bulk delete to permanently remove.';
            $this->redirect('/admin/products');
        }

        // For active products, proceed with deletion (CASCADE will handle related records)
        $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$productId]);
        $_SESSION['success'] = 'Product deleted successfully.';
        $this->redirect('/admin/products');
    }

    private function handleUploads(int $productId): void
    {
        if (empty($_FILES['images']['name'])) return;
        $base = BASE_PATH . '/public/uploads/products/' . $productId;
        if (!is_dir($base)) @mkdir($base, 0775, true);
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $count = count($_FILES['images']['name']);
        $st = DB::pdo()->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM product_images WHERE product_id=?');
        $st->execute([$productId]); $sort = (int)$st->fetchColumn();
        for ($i=0; $i<$count; $i++) {
            if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            $tmp = $_FILES['images']['tmp_name'][$i]; $name = $_FILES['images']['name'][$i]; $size = (int)$_FILES['images']['size'][$i];
            if ($size > 5*1024*1024) continue;
            $mime = $finfo ? finfo_file($finfo, $tmp) : mime_content_type($tmp);
            if (!in_array($mime, ['image/jpeg','image/png','image/webp'])) continue;
            $ext = $mime==='image/png'?'png':($mime==='image/webp'?'webp':'jpg');
            $safe = preg_replace('/[^a-zA-Z0-9-_\.]/','_', pathinfo($name, PATHINFO_FILENAME));
            $final = $safe . '-' . uniqid() . '.' . $ext;
            $dest = $base . '/' . $final;
            if (@move_uploaded_file($tmp, $dest)) {
                // Optional: create optimized main and thumbnail variants when GD/Imagick available
                try {
                    $this->makeVariants($dest, $mime);
                } catch (\Throwable $e) { /* ignore */ }
                $url = '/public/uploads/products/' . $productId . '/' . $final;
                DB::pdo()->prepare('INSERT INTO product_images (product_id,url,sort_order) VALUES (?,?,?)')->execute([$productId,$url,$sort++]);
            }
        }
        if ($finfo) finfo_close($finfo);
    }

    private function makeVariants(string $path, string $mime): void
    {
        // Create thumbnail at 400x400 (cover) and ensure main fits within 1600x1600
        $create = null; $save = null; $quality = 85;
        if (extension_loaded('gd')) {
            if ($mime==='image/jpeg') { $create='imagecreatefromjpeg'; $save=function($im,$p) use($quality){ imagejpeg($im,$p,$quality); }; }
            elseif ($mime==='image/png') { $create='imagecreatefrompng'; $save=function($im,$p){ imagepng($im,$p,6); }; }
            elseif ($mime==='image/webp' && function_exists('imagewebp')) { $create='imagecreatefromwebp'; $save=function($im,$p) use($quality){ imagewebp($im,$p,$quality); }; }
        }
        if ($create && $save) {
            $src = @$create($path); if (!$src) return;
            $w = imagesx($src); $h = imagesy($src);
            // Resize main if larger than 1600
            $max = 1600; if ($w>$max || $h>$max) {
                $scale = min($max/$w, $max/$h); $nw=(int)($w*$scale); $nh=(int)($h*$scale);
                $main = imagecreatetruecolor($nw,$nh); imagecopyresampled($main,$src,0,0,0,0,$nw,$nh,$w,$h);
                $save($main, $path); imagedestroy($main);
                $w=$nw; $h=$nh;
            }
            // Create square thumbnail 400x400 (center crop)
            $size = 400; $thumb = imagecreatetruecolor($size,$size);
            $side = min($w,$h); $sx=(int)(($w-$side)/2); $sy=(int)(($h-$side)/2);
            imagecopyresampled($thumb,$src,0,0,$sx,$sy,$size,$size,$side,$side);
            $dot = strrpos($path,'.'); if ($dot!==false) { $thumbPath = substr($path,0,$dot).'.thumb'.substr($path,$dot); $save($thumb,$thumbPath); }
            imagedestroy($thumb); imagedestroy($src);
        } elseif (class_exists('Imagick')) {
            $im = new \Imagick($path);
            // Resize main
            $im->thumbnailImage(1600,1600,true);
            $im->writeImage($path);
            // Thumb square
            $thumb = clone $im; $thumb->cropThumbnailImage(400,400);
            $dot = strrpos($path,'.'); if ($dot!==false) { $thumbPath = substr($path,0,$dot).'.thumb'.substr($path,$dot); $thumb->writeImage($thumbPath); }
            $thumb->destroy(); $im->destroy();
        }
    }

    public function deleteImage(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/products/'.$params['id'].'/edit'); }
        $st = DB::pdo()->prepare('SELECT * FROM product_images WHERE id=? AND product_id=?');
        $st->execute([(int)$params['image_id'], (int)$params['id']]);
        $img = $st->fetch();
        if ($img) {
            $path = BASE_PATH . '/public' . $img['url'];
            if (is_file($path)) @unlink($path);
            DB::pdo()->prepare('DELETE FROM product_images WHERE id=?')->execute([(int)$img['id']]);
        }
        $this->redirect('/admin/products/'.$params['id'].'/edit');
    }

    public function bulk(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/products'); }
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if (!$ids) { $this->redirect('/admin/products'); }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $action = $_POST['action'] ?? '';
        if ($action === 'activate' || $action === 'draft') {
            $st = DB::pdo()->prepare("UPDATE products SET status=? WHERE id IN ($in)");
            $params = array_merge([$action], $ids); $st->execute($params);
            $_SESSION['success'] = 'Updated '.count($ids).' product(s).';
        } elseif ($action === 'delete') {
            $pdo = DB::pdo();
            // Find products that are referenced by order_items and skip them (preserve order history)
            $ref = $pdo->prepare("SELECT DISTINCT product_id FROM order_items WHERE product_id IN ($in)");
            $ref->execute($ids);
            $blocked = array_map('intval', $ref->fetchAll(\PDO::FETCH_COLUMN));

            // Find draft products and skip them (preserve images for draft products)
            $draftStmt = $pdo->prepare("SELECT id FROM products WHERE id IN ($in) AND status = 'draft'");
            $draftStmt->execute($ids);
            $draftProducts = array_map('intval', $draftStmt->fetchAll(\PDO::FETCH_COLUMN));

            // Combine blocked products (order references + draft products)
            $allBlocked = array_values(array_unique(array_merge($blocked, $draftProducts)));
            $deletable = array_values(array_diff($ids, $allBlocked));

            if ($deletable) {
                $in2 = implode(',', array_fill(0, count($deletable), '?'));
                // Clean related rows first
                try { $pdo->prepare("DELETE FROM product_images WHERE product_id IN ($in2)")->execute($deletable); } catch (\Throwable $e) {}
                try { $pdo->prepare("DELETE FROM product_tags WHERE product_id IN ($in2)")->execute($deletable); } catch (\Throwable $e) {}
                try { $pdo->prepare("DELETE FROM product_stock_events WHERE product_id IN ($in2)")->execute($deletable); } catch (\Throwable $e) {}
                // Finally delete products
                $st = $pdo->prepare("DELETE FROM products WHERE id IN ($in2)");
                $st->execute($deletable);
            }

            // Build appropriate message
            $messages = [];
            if (count($deletable) > 0) {
                $messages[] = count($deletable) . ' product(s) deleted.';
            }
            if (count($blocked) > 0) {
                $messages[] = count($blocked) . ' product(s) were not deleted because they are referenced by existing orders.';
            }
            if (count($draftProducts) > 0) {
                $messages[] = count($draftProducts) . ' draft product(s) were preserved with their images.';
            }

            if (!empty($messages)) {
                $_SESSION['success'] = implode(' ', $messages);
            } else {
                $_SESSION['success'] = 'No products were deleted.';
            }
        } elseif ($action === 'assign_collection') {
            $cid = !empty($_POST['collection_id']) ? (int)$_POST['collection_id'] : null;
            $st = DB::pdo()->prepare("UPDATE products SET collection_id=? WHERE id IN ($in)");
            $params = array_merge([$cid], $ids); $st->execute($params);
            $_SESSION['success'] = 'Assigned '.count($ids).' product(s) to collection.';
        } else {
            $_SESSION['error'] = 'Unknown bulk action.';
        }
        $this->redirect('/admin/products');
    }

    public function duplicate(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/products'); }
        $id = (int)$params['id'];
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT * FROM products WHERE id=?'); $st->execute([$id]); $p = $st->fetch();
        if (!$p) { $this->redirect('/admin/products'); }
        $title = ($p['title'] ?? 'Product') . ' (Copy)';
        $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/','-', $title));
        $slug = $baseSlug . '-' . substr(md5(uniqid('', true)),0,6);
        $ins = $pdo->prepare('INSERT INTO products (title,slug,description,price,status,stock,collection_id,created_at) VALUES (?,?,?,?,?,?,?,NOW())');
        $ins->execute([$title,$slug,$p['description'],$p['price'],$p['status'],$p['stock'],$p['collection_id']]);
        $newId = (int)$pdo->lastInsertId();
        // Copy sale fields (best effort)
        try {
            $pdo->prepare('UPDATE products SET sale_price=?, sale_start=?, sale_end=? WHERE id=?')
                ->execute([$p['sale_price'] ?? null, $p['sale_start'] ?? null, $p['sale_end'] ?? null, $newId]);
        } catch (\Throwable $e) {}
        // Copy images
        try {
            $imgs = $pdo->prepare('SELECT url, sort_order FROM product_images WHERE product_id=? ORDER BY sort_order');
            $imgs->execute([$id]); $rows = $imgs->fetchAll();
            if ($rows) {
                $srcDir = BASE_PATH . '/public/uploads/products/' . $id;
                $dstDir = BASE_PATH . '/public/uploads/products/' . $newId;
                if (!is_dir($dstDir)) { @mkdir($dstDir, 0775, true); }
                $insImg = $pdo->prepare('INSERT INTO product_images (product_id,url,sort_order) VALUES (?,?,?)');
                foreach ($rows as $r) {
                    $url = $r['url'];
                    $basename = basename($url);
                    $src = $srcDir . '/' . $basename;
                    $dst = $dstDir . '/' . $basename;
                    if (is_file($src)) { @copy($src, $dst); }
                    $newUrl = '/public/uploads/products/' . $newId . '/' . $basename;
                    $insImg->execute([$newId, $newUrl, (int)$r['sort_order']]);
                }
            }
        } catch (\Throwable $e) {}
        $this->redirect('/admin/products/' . $newId . '/edit');
    }
    public function adjustPrices(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('/admin/products'); }
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/products'); }
        $type = $_POST['type'] ?? 'percent';
        $dir  = $_POST['dir']  ?? 'inc';
        $amount = max(0, (float)($_POST['amount'] ?? 0));
        $collection_id = isset($_POST['collection_id']) && $_POST['collection_id']!=='' ? (int)$_POST['collection_id'] : null;
        $status = isset($_POST['status']) && $_POST['status']!=='' ? $_POST['status'] : null;
        $pdo = DB::pdo();
        $where = [];$params=[];
        if ($collection_id !== null) { $where[]='collection_id=?'; $params[]=$collection_id; }
        if ($status !== null) { $where[]='status=?'; $params[]=$status; }
        $whereSql = $where ? (' WHERE '.implode(' AND ',$where)) : '';
        if ($type === 'percent') {
            $factor = ($dir==='inc') ? (1 + $amount/100.0) : (1 - $amount/100.0);
            if ($factor < 0) { $factor = 0; }
            $sql = 'UPDATE products SET price = GREATEST(0, ROUND(price * ?, 2))' . $whereSql;
            $st = $pdo->prepare($sql); $st->execute(array_merge([$factor], $params));
        } else {
            $delta = ($dir==='inc') ? $amount : -$amount;
            $sql = 'UPDATE products SET price = GREATEST(0, ROUND(price + ?, 2))' . $whereSql;
            $st = $pdo->prepare($sql); $st->execute(array_merge([$delta], $params));
        }
        $this->redirect('/admin/products');
    }

    private function updateTags(int $productId, string $tagsCsv): void
    {
        $pdo = DB::pdo();
        $names = array_unique(array_map(function($s){ return trim($s); }, explode(',', $tagsCsv)));
        $names = array_values(array_filter($names, function($v){ return $v !== ''; }));
        // Clear existing
        $pdo->prepare('DELETE FROM product_tags WHERE product_id=?')->execute([$productId]);
        if (!$names) return;
        $sel = $pdo->prepare('SELECT id FROM tags WHERE slug=?');
        $ins = $pdo->prepare('INSERT INTO tags (name,slug) VALUES (?,?)');
        $map = $pdo->prepare('INSERT IGNORE INTO product_tags (product_id,tag_id) VALUES (?,?)');
        foreach ($names as $name) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $name)); if ($slug==='') continue;
            $sel->execute([$slug]); $tid = (int)$sel->fetchColumn();
            if (!$tid) { try { $ins->execute([$name,$slug]); $tid=(int)$pdo->lastInsertId(); } catch (\Throwable $e) { $sel->execute([$slug]); $tid=(int)$sel->fetchColumn(); } }
            if ($tid) { $map->execute([$productId,$tid]); }
        }
    }


    public function export(): void
    {
        // Disable output buffering to prevent issues
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=products_with_images.csv');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            die('Failed to open output stream');
        }

        try {
            $pdo = DB::pdo();

            // Detect optional columns
            $hasSku = $pdo->query("SHOW COLUMNS FROM products LIKE 'fsc'")->rowCount() > 0;
            $hasBarcode = $pdo->query("SHOW COLUMNS FROM products LIKE 'barcode'")->rowCount() > 0;
            $hasProductImages = $pdo->query("SHOW TABLES LIKE 'product_images'")->rowCount() > 0;
            $hasVariants = $pdo->query("SHOW COLUMNS FROM products LIKE 'variant_attributes'")->rowCount() > 0;

            // Headers including image links
            $headers = ['ID','Title','FSC','Barcode','Price','Sale Price','Status','Stock','Collection','Variant','Image URLs','Primary Image URL'];
            fputcsv($out, $headers);

            // Build query
            $cols = ['p.id','p.title'];
            if ($hasSku) { $cols[] = 'p.fsc'; } else { $cols[] = "'' AS fsc"; }
            if ($hasBarcode) { $cols[] = 'p.barcode'; } else { $cols[] = "'' AS barcode"; }
            $cols[] = 'p.price';
            $cols[] = 'p.sale_price';
            $cols[] = 'p.status';
            $cols[] = 'COALESCE(p.stock,0) AS stock';
            $cols[] = 'c.title AS collection';
            if ($hasVariants) { $cols[] = 'p.variant_attributes'; } else { $cols[] = "'' AS variant"; }

            $sql = 'SELECT '.implode(',', $cols).' FROM products p LEFT JOIN collections c ON c.id=p.collection_id ORDER BY p.id DESC';
            $stmt = $pdo->query($sql);

            if ($stmt === false) {
                throw new \Exception('Database query failed');
            }

            // Get images for each product (only prepare if table exists)
            $getImages = null;
            if ($hasProductImages) {
                $getImages = $pdo->prepare('SELECT url FROM product_images WHERE product_id = ? ORDER BY sort_order, id ASC');
            }

            // Helper function for PHP 7.x compatibility
            $startsWith = function($haystack, $needle) {
                return strncmp($haystack, $needle, strlen($needle)) === 0;
            };

            while ($row = $stmt->fetch()) {
                $productId = $row['id'];

                // Get all images for this product
                $imageUrls = [];
                if ($hasProductImages && $getImages) {
                    $getImages->execute([$productId]);
                    $images = $getImages->fetchAll(\PDO::FETCH_COLUMN);
                    $imageUrls = $images;
                }

                // Create comma-separated image URLs
                $allImages = !empty($imageUrls) ? implode(', ', $imageUrls) : '';

                // Get primary image (first image from product_images table)
                $primaryImage = '';
                if (!empty($imageUrls)) {
                    $primaryImage = $imageUrls[0];
                }

                // Make URLs absolute if they're relative
                if ($primaryImage && !$startsWith($primaryImage, 'http')) {
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? '';
                    $primaryImage = $protocol . '://' . $host . $primaryImage;
                }

                // Convert relative URLs to absolute in all images
                if ($allImages) {
                    $allImagesArray = explode(', ', $allImages);
                    $absoluteUrls = [];
                    foreach ($allImagesArray as $url) {
                        $url = trim($url);
                        if (!$startsWith($url, 'http')) {
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
                            $host = $_SERVER['HTTP_HOST'] ?? '';
                            $url = $protocol . '://' . $host . $url;
                        }
                        $absoluteUrls[] = $url;
                    }
                    $allImages = implode(', ', $absoluteUrls);
                }

                fputcsv($out, [
                    $row['id'],
                    $row['title'],
                    $row['fsc'] ?? '',
                    $row['barcode'] ?? '',
                    $row['price'],
                    $row['sale_price'],
                    $row['status'],
                    $row['stock'],
                    $row['collection'] ?? '',
                    $row['variant'] ?? '',
                    $allImages,
                    $primaryImage
                ]);
            }
        } catch (\Throwable $e) {
            // Write error as CSV comment
            fputcsv($out, ['Error: ' . $e->getMessage()]);
        }

        fclose($out);
        exit;
    }

    public function sortImages(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/products/'.$params['id'].'/edit'); }
        $order = $_POST['order'] ?? [];
        $i = 0; $st = DB::pdo()->prepare('UPDATE product_images SET sort_order=? WHERE id=? AND product_id=?');
        foreach ($order as $imgId) { $st->execute([$i++, (int)$imgId, (int)$params['id']]); }
        $this->redirect('/admin/products/'.$params['id'].'/edit');
    }
    public function quickUpdate(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/products'); }
        $id = (int)($_POST['id'] ?? 0);
        $price = isset($_POST['price']) ? (float)$_POST['price'] : null;
        $stock = isset($_POST['stock']) ? max(0,(int)$_POST['stock']) : null;
        if ($id && $price !== null && $stock !== null) {
            $pdo = DB::pdo();
            // Log stock delta
            try {
                $cur = $pdo->prepare('SELECT COALESCE(stock,0) FROM products WHERE id=?'); $cur->execute([$id]); $prev = (int)$cur->fetchColumn();
            } catch (\Throwable $e) { $prev = null; }
            $pdo->prepare('UPDATE products SET price=?, stock=? WHERE id=?')->execute([$price,$stock,$id]);
            if ($prev !== null && $stock !== $prev) {
                try { $pdo->prepare('INSERT INTO product_stock_events (product_id,user_id,delta,reason,created_at) VALUES (?,?,?,?,NOW())')
                        ->execute([$id, \App\Core\Auth::userId(), $stock-$prev, 'admin quick update']); } catch (\Throwable $e) {}
            }
        }
        $this->redirect('/admin/products');
    }

    public function importStock(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('/admin/products'); }
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/products'); }
        if (empty($_FILES['csv']['tmp_name'])) { $this->redirect('/admin/products'); }
        $f = fopen($_FILES['csv']['tmp_name'],'r'); if(!$f){ $this->redirect('/admin/products'); }
        // Expect headers: id,stock
        $pdo = DB::pdo(); $upd = $pdo->prepare('UPDATE products SET stock=? WHERE id=?');
        $log = $pdo->prepare('INSERT INTO product_stock_events (product_id,user_id,delta,reason,created_at) VALUES (?,?,?,?,NOW())');
        $get = $pdo->prepare('SELECT COALESCE(stock,0) FROM products WHERE id=?');
        $i=0; while (($row = fgetcsv($f)) !== false) {
            if ($i===0 && preg_match('/id/i', $row[0] ?? '') ) { $i++; continue; }
            $pid = (int)($row[0] ?? 0); $stk = max(0, (int)($row[1] ?? 0));
            if($pid>0){
                $get->execute([$pid]); $prev=(int)($get->fetchColumn() ?? 0);
                $upd->execute([$stk,$pid]);
                $delta = $stk - $prev; if ($delta !== 0) { try { $log->execute([$pid, \App\Core\Auth::userId(), $delta, 'csv import']); } catch (\Throwable $e) {} }
            }
            $i++;
        }
        fclose($f);
        $this->redirect('/admin/products');
    }

    public function search(): void
    {
        // Admin JSON search for scan/typeahead
        header('Content-Type: application/json');
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '') { echo json_encode(['items'=>[]]); return; }
        $pdo = DB::pdo();
        $hasSku = $pdo->query("SHOW COLUMNS FROM products LIKE 'fsc'")->rowCount() > 0;
        $hasBarcode = $pdo->query("SHOW COLUMNS FROM products LIKE 'barcode'")->rowCount() > 0;
        $where = [];$params = [];
        if (ctype_digit($q)) { $where[]='p.id=?'; $params[]=(int)$q; }
        $like = '%'.$q.'%';
        $titleCond = 'p.title LIKE ?';
        if ($hasSku && $hasBarcode) { $where[] = "($titleCond OR p.fsc LIKE ? OR p.barcode LIKE ?)"; $params[]=$like; $params[]=$like; $params[]=$like; }
        elseif ($hasSku) { $where[] = "($titleCond OR p.fsc LIKE ?)"; $params[]=$like; $params[]=$like; }
        elseif ($hasBarcode) { $where[] = "($titleCond OR p.barcode LIKE ?)"; $params[]=$like; $params[]=$like; }
        else { $where[] = $titleCond; $params[]=$like; }
        $cols = ['p.id','p.title','p.price','COALESCE(p.stock,0) AS stock'];
        if ($hasSku) { $cols[]='p.fsc'; }
        if ($hasBarcode) { $cols[]='p.barcode'; }
        $sql = 'SELECT '.implode(',', $cols).' FROM products p WHERE '.implode(' AND ',$where).' ORDER BY p.id DESC LIMIT 20';
        $st=$pdo->prepare($sql); $st->execute($params);
        $items = [];
        while ($r = $st->fetch()) {
            $items[] = [
                'id'=>(int)$r['id'],
                'title'=>$r['title'],
                'fsc'=>$r['fsc'] ?? '',
                'sku'=>$r['fsc'] ?? '',
                'barcode'=>$r['barcode'] ?? '',
                'price'=>(float)$r['price'],
                'stock'=>(int)$r['stock']
            ];
        }
        echo json_encode(['items'=>$items]);
    }

    public function duplicates(): void
    {
        $pdo = DB::pdo();
        // Find duplicate FSCs
        $skuGroups = [];
        try {
            $rs = $pdo->query("SELECT fsc AS sku, COUNT(*) c FROM products WHERE fsc IS NOT NULL AND fsc<>'' GROUP BY fsc HAVING c>1 ORDER BY c DESC, fsc");
            $skuGroups = $rs ? $rs->fetchAll() : [];
        } catch (\Throwable $e) { $skuGroups = []; }
        // Find duplicate barcodes
        $barcodeGroups = [];
        try {
            $rs = $pdo->query("SELECT barcode, COUNT(*) c FROM products WHERE barcode IS NOT NULL AND barcode<>'' GROUP BY barcode HAVING c>1 ORDER BY c DESC, barcode");
            $barcodeGroups = $rs ? $rs->fetchAll() : [];
        } catch (\Throwable $e) { $barcodeGroups = []; }
        // Fetch items for each group (capped)
        $itemsByKey = ['sku'=>[], 'barcode'=>[]];
        $fetch = function(string $col, string $val) use ($pdo){
            $st = $pdo->prepare("SELECT id,title,fsc AS sku,barcode,price,COALESCE(stock,0) stock FROM products WHERE $col=? ORDER BY id DESC LIMIT 50");
            $st->execute([$val]); return $st->fetchAll();
        };
        foreach ($skuGroups as $g) { $itemsByKey['sku'][$g['sku']] = $fetch('fsc',$g['sku']); }
        foreach ($barcodeGroups as $g) { $itemsByKey['barcode'][$g['barcode']] = $fetch('barcode',$g['barcode']); }
        $this->adminView('admin/products/duplicates', [
            'title' => 'Duplicate FSC/Barcodes',
            'skuGroups' => $skuGroups,
            'barcodeGroups' => $barcodeGroups,
            'itemsByKey' => $itemsByKey,
        ]);
    }

    // Get variants as JSON
    public function variants(array $params): void
    {
        header('Content-Type: application/json');
        $productId = (int)$params['id'];

        $stmt = DB::pdo()->prepare('
            SELECT p.*, i.url as image_url
            FROM products p
            LEFT JOIN (SELECT product_id, url FROM product_images GROUP BY product_id) i ON i.product_id = p.id
            WHERE p.parent_product_id = ?
            ORDER BY p.variant_attributes
        ');
        $stmt->execute([$productId]);
        $variants = $stmt->fetchAll();

        echo json_encode(['variants' => $variants]);
    }

    // Create new variant
    public function storeVariant(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $productId = (int)$params['id'];
        $pdo = DB::pdo();

        // Get parent product info
        $parentStmt = $pdo->prepare('SELECT * FROM products WHERE id=?');
        $parentStmt->execute([$productId]);
        $parent = $parentStmt->fetch();

        if (!$parent) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Parent product not found']);
            return;
        }

        $variantAttr = trim($_POST['variant_attributes'] ?? '');
        $fsc = trim($_POST['fsc'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');
        $price = (float)($_POST['price'] ?? $parent['price']);
        $stock = (int)($_POST['stock'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        // Create variant title: parent_title + variant_attribute
        $title = $parent['title'] . ' ' . $variantAttr;
        $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $title)) . '-' . substr(md5(uniqid()), 0, 6);

        // Insert variant
        $stmt = $pdo->prepare('
            INSERT INTO products (title, slug, fsc, barcode, price, stock, status, collection_id, parent_product_id, variant_attributes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$title, $slug, $fsc ?: null, $barcode ?: null, $price, $stock, $status, $parent['collection_id'], $productId, $variantAttr]);

        $variantId = (int)$pdo->lastInsertId();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'variant_id' => $variantId,
            'message' => 'Variant created successfully'
        ]);
    }

    // Update variant
    public function updateVariant(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $variantId = (int)$params['variant_id'];
        $pdo = DB::pdo();

        $fsc = trim($_POST['fsc'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $variantAttr = trim($_POST['variant_attributes'] ?? '');

        $set = ['fsc=?', 'barcode=?', 'price=?', 'stock=?', 'status=?', 'variant_attributes=?'];
        $vals = [$fsc ?: null, $barcode ?: null, $price, $stock, $status, $variantAttr, $variantId];

        $pdo->prepare('UPDATE products SET ' . implode(',', $set) . ' WHERE id=?')
            ->execute($vals);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Variant updated']);
    }

    // Delete variant
    public function deleteVariant(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $variantId = (int)$params['variant_id'];
        $pdo = DB::pdo();

        // Verify it's actually a variant
        $stmt = $pdo->prepare('SELECT parent_product_id FROM products WHERE id=?');
        $stmt->execute([$variantId]);
        $variant = $stmt->fetch();

        if (!$variant || empty($variant['parent_product_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Variant not found']);
            return;
        }

        $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$variantId]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Variant deleted']);
    }

    // Extract base title by removing variant patterns from the end
    private function extractBaseTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') return '';

        // Common variant patterns to remove from the end
        $patterns = [
            '\b\d{2,3}[A-Z]{1,3}\s*$',           // Bra sizes: 38A, 36B, 34C, 32DD
            '\b\d{1,2}\.\d{1,2}\s*$',            // Decimal: 28.5
            '\b\d+(?:XL|XS|L|M|S)\b$',          // 2XL, 3XL, 2XS
            '\b(EXTRA LARGE|EXTRA SMALL|EXTRA LONG|EXTRA SHORT)\s*$',
            '\b(XXXXL|XXXXXL|2XL|3XL|4XL|5XL|2XS|3XS)\s*$',
            '\b(XL|XS)\s*$',
            '\b(LARGE|MEDIUM|SMALL)\s*$',
            '\b([LMS])\s*$',
            '\b(\d{1,2})\s*$',                 // Standalone numbers
        ];

        foreach ($patterns as $pattern) {
            $newTitle = preg_replace('/' . $pattern . '/i', '', $title);
            $newTitle = trim($newTitle);
            if ($newTitle !== $title && strlen($newTitle) >= 5) {
                return $newTitle;
            }
        }

        return $title; // Return original if no pattern matched
    }

    // Merge suggested products as variants
    public function mergeVariants(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $parentId = (int)$params['id'];
        $variantIds = array_map('intval', $_POST['variant_ids'] ?? []);

        if (empty($variantIds)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No variants selected']);
            return;
        }

        $pdo = DB::pdo();

        // Get parent product info
        $parentStmt = $pdo->prepare('SELECT * FROM products WHERE id=?');
        $parentStmt->execute([$parentId]);
        $parent = $parentStmt->fetch();

        if (!$parent) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Parent product not found']);
            return;
        }

        // Extract variant attributes from each product title
        $baseTitle = $this->extractBaseTitle($parent['title']);
        $mergedCount = 0;

        foreach ($variantIds as $variantId) {
            if ($variantId === $parentId) continue; // Skip parent itself

            // Get variant product
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id=?');
            $stmt->execute([$variantId]);
            $variant = $stmt->fetch();

            if (!$variant) continue;

            // Extract variant attribute from the title
            $variantAttr = $variant['title'];
            if (stripos($variantAttr, $baseTitle) === 0) {
                $variantAttr = trim(substr($variantAttr, strlen($baseTitle)));
            }

            // Update product to be a variant
            $updateStmt = $pdo->prepare('
                UPDATE products
                SET parent_product_id = ?, variant_attributes = ?
                WHERE id = ?
            ');
            $updateStmt->execute([$parentId, $variantAttr, $variantId]);
            $mergedCount++;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Merged {$mergedCount} variants successfully"
        ]);
    }

    // Bulk variant detection - scan all products and find potential variant groups
    public function bulkDetectVariants(): void
    {
        $pdo = DB::pdo();
        $hasVariantsColumn = false;
        try {
            $hasVariantsColumn = $pdo->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        } catch (\Throwable $e) { $hasVariantsColumn = false; }

        if (!$hasVariantsColumn) {
            $this->adminView('admin/products/bulk_variants', [
                'title' => 'Bulk Variant Detection',
                'error' => 'Variant support is not enabled in the database. Please ensure the parent_product_id column exists.'
            ]);
            return;
        }

        // Get all products that are not already variants (parent_product_id IS NULL)
        $stmt = $pdo->query('
            SELECT id, title, fsc, price, stock, status, collection_id
            FROM products
            WHERE parent_product_id IS NULL OR parent_product_id = 0
            ORDER BY title
        ');
        $products = $stmt->fetchAll();

        // Group products by base title
        $groups = [];
        $productGroups = [];

        foreach ($products as $product) {
            $extracted = $this->extractBaseTitle($product['title']);
            $baseTitle = $extracted;

            // Only group if base title is different from full title and meaningful
            if ($baseTitle !== $product['title'] && strlen($baseTitle) >= 5) {
                if (!isset($groups[$baseTitle])) {
                    $groups[$baseTitle] = [];
                }
                $groups[$baseTitle][] = $product;
            }
        }

        // Filter to only show groups with multiple products
        $variantGroups = array_filter($groups, function($group) {
            return count($group) > 1;
        });

        // Sort by group name
        ksort($variantGroups);

        $this->adminView('admin/products/bulk_variants', [
            'title' => 'Bulk Variant Detection',
            'variantGroups' => $variantGroups,
            'totalGroups' => count($variantGroups),
            'totalProducts' => array_sum(array_map('count', $variantGroups))
        ]);
    }

    // Bulk merge all detected variants
    public function bulkMergeVariants(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $pdo = DB::pdo();
        $hasVariantsColumn = false;
        try {
            $hasVariantsColumn = $pdo->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        } catch (\Throwable $e) { $hasVariantsColumn = false; }

        if (!$hasVariantsColumn) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Variant support not enabled']);
            return;
        }

        // Get all products that are not already variants
        $stmt = $pdo->query('
            SELECT id, title, fsc, price, stock, status, collection_id
            FROM products
            WHERE parent_product_id IS NULL OR parent_product_id = 0
            ORDER BY title
        ');
        $products = $stmt->fetchAll();

        // Group products by base title
        $groups = [];
        foreach ($products as $product) {
            $extracted = $this->extractBaseTitle($product['title']);
            $baseTitle = $extracted;

            if ($baseTitle !== $product['title'] && strlen($baseTitle) >= 5) {
                if (!isset($groups[$baseTitle])) {
                    $groups[$baseTitle] = [];
                }
                $groups[$baseTitle][] = $product;
            }
        }

        // Merge each group
        $mergedGroups = 0;
        $mergedProducts = 0;
        $errors = [];

        foreach ($groups as $baseTitle => $groupProducts) {
            if (count($groupProducts) <= 1) continue;

            // Sort by ID to use the first/oldest as parent
            usort($groupProducts, function($a, $b) {
                return $a['id'] - $b['id'];
            });

            $parentProduct = $groupProducts[0];
            $parentId = $parentProduct['id'];

            // Update parent title to base title if it's not already
            if ($parentProduct['title'] !== $baseTitle) {
                try {
                    $pdo->prepare('UPDATE products SET title=? WHERE id=?')
                        ->execute([$baseTitle, $parentId]);
                } catch (\Throwable $e) {
                    $errors[] = "Could not update parent product ID {$parentId}: " . $e->getMessage();
                }
            }

            // Link other products as variants
            foreach (array_slice($groupProducts, 1) as $variantProduct) {
                $variantId = $variantProduct['id'];

                // Extract variant attribute
                $variantAttr = trim(substr($variantProduct['title'], strlen($baseTitle)));
                if ($variantAttr === '') {
                    $variantAttr = $this->extractVariantFromTitle($variantProduct['title'])['variant'];
                }

                try {
                    $pdo->prepare('
                        UPDATE products
                        SET parent_product_id = ?, variant_attributes = ?
                        WHERE id = ?
                    ')->execute([$parentId, $variantAttr, $variantId]);
                    $mergedProducts++;
                } catch (\Throwable $e) {
                    $errors[] = "Could not link product ID {$variantId}: " . $e->getMessage();
                }
            }

            $mergedGroups++;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'merged_groups' => $mergedGroups,
            'merged_products' => $mergedProducts,
            'errors' => $errors
        ]);
    }

}


