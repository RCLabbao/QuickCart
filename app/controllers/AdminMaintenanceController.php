<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class AdminMaintenanceController extends Controller
{
    public function index(): void
    {
        $pdo = DB::pdo();

        // Get database statistics (safe defaults if tables are missing)
        $stats = [ 'products' => 0, 'orders' => 0, 'collections' => 0, 'users' => 0 ];
        foreach (['products','orders','collections','users'] as $t) {
            try { $stats[$t === 'users' ? 'users' : $t] = (int)$pdo->query('SELECT COUNT(*) FROM `'.$t.'`')->fetchColumn(); }
            catch (\Throwable $e) { /* ignore */ }
        }

        // Get table sizes (best-effort; some hosts restrict information_schema)
        try {
            $tableSizes = $pdo->query("
                SELECT
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $tableSizes = [];
        }

        // Check for missing columns/tables (guard each call)
        $checks = [];
        $checkItems = [
            ['products','stock','products.stock'],
            ['products','sale_price','products.sale_price'],
            ['orders','notes','orders.notes'],
            ['products','parent_product_id','variants support'],
        ];
        foreach ($checkItems as [$tbl,$col,$label]) {
            try { $checks[$label] = $this->columnExists($pdo, $tbl, $col); } catch (\Throwable $e) { $checks[$label] = false; }
        }
        foreach (['coupons','order_events','delivery_fees','customer_profiles'] as $tbl) {
            try { $checks[$tbl.' table'] = $this->tableExists($pdo, $tbl); } catch (\Throwable $e) { $checks[$tbl.' table'] = false; }
        }

        // Get backup files
        $backupFiles = [];
        $backupDir = BASE_PATH . '/backups';
        if (is_dir($backupDir)) {
            $files = scandir($backupDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && str_ends_with($file, '.sql')) {
                    $filepath = $backupDir . '/' . $file;
                    $backupFiles[] = [
                        'filename' => $file,
                        'size' => filesize($filepath),
                        'created' => date('Y-m-d H:i:s', filemtime($filepath))
                    ];
                }
            }
            // Sort by creation date (newest first)
            usort($backupFiles, function($a, $b) {
                return strcmp($b['created'], $a['created']);
            });
        }

        $this->adminView('admin/maintenance/index', [
            'title' => 'System Maintenance',
            'stats' => $stats,
            'table_sizes' => $tableSizes,
            'checks' => $checks,
            'backup_files' => $backupFiles
        ]);
    }

    public function optimize(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin'); }
        $pdo = DB::pdo();
        // Add products.stock if missing
        $this->ensureColumn($pdo, 'products','stock','ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 0 AFTER status');
        // Add promo/sale columns if missing
        $this->ensureColumn($pdo, 'products','brochure_selling_price','ALTER TABLE products ADD COLUMN brochure_selling_price DECIMAL(10,2) NULL AFTER price');
        $this->ensureColumn($pdo, 'products','sale_price','ALTER TABLE products ADD COLUMN sale_price DECIMAL(10,2) NULL AFTER brochure_selling_price');
        $this->ensureColumn($pdo, 'products','sale_start','ALTER TABLE products ADD COLUMN sale_start DATETIME NULL AFTER sale_price');
        $this->ensureColumn($pdo, 'products','sale_end','ALTER TABLE products ADD COLUMN sale_end DATETIME NULL AFTER sale_start');
        // Add collections.image_url if missing
        $this->ensureColumn($pdo, 'collections','image_url','ALTER TABLE collections ADD COLUMN image_url VARCHAR(255) NULL AFTER description');
        // Add orders.notes if missing
        $this->ensureColumn($pdo, 'orders','notes','ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER status');
        // Add orders.discount if missing
        $this->ensureColumn($pdo, 'orders','discount','ALTER TABLE orders ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER shipping_fee');
        // Add orders.coupon_code if missing
        $this->ensureColumn($pdo, 'orders','coupon_code','ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(64) NULL AFTER discount');

        // Add FSC and barcode columns if missing
        $this->ensureColumn($pdo, 'products','fsc','ALTER TABLE products ADD COLUMN fsc VARCHAR(64) NULL UNIQUE');
        $this->ensureColumn($pdo, 'products','barcode','ALTER TABLE products ADD COLUMN barcode VARCHAR(64) NULL UNIQUE');

        // Add variant support columns if missing (for product variants like sizes, colors)
        $this->ensureColumn($pdo, 'products','parent_product_id','ALTER TABLE products ADD COLUMN parent_product_id INT NULL AFTER collection_id');
        $this->ensureColumn($pdo, 'products','variant_attributes','ALTER TABLE products ADD COLUMN variant_attributes VARCHAR(500) NULL AFTER parent_product_id');

        // Add foreign key for parent_product_id if it doesn't exist
        try {
            $fkExists = $pdo->query("
                SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'products'
                AND COLUMN_NAME = 'parent_product_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ")->fetchColumn() > 0;

            if (!$fkExists) {
                $pdo->exec('ALTER TABLE products ADD FOREIGN KEY (parent_product_id) REFERENCES products(id) ON DELETE SET NULL');
            }
        } catch (\Throwable $e) { /* ignore foreign key errors */ }

        // Fix image URLs - add /public prefix if missing
        try {
            $pdo->exec("UPDATE product_images SET url = CONCAT('/public', url) WHERE url NOT LIKE '/public%' AND url LIKE '/uploads/%'");
        } catch (\Throwable $e) { /* ignore */ }

        // Rename products.sku -> products.fsc if needed
        try {
            $hasSku = $this->columnExists($pdo, 'products', 'sku');
            $hasFsc = $this->columnExists($pdo, 'products', 'fsc');
            if ($hasSku && !$hasFsc) {
                $pdo->exec("ALTER TABLE products CHANGE COLUMN sku fsc VARCHAR(64) NULL");
                try { $pdo->exec('ALTER TABLE products DROP INDEX idx_products_sku'); } catch (\Throwable $e) { /* ignore */ }
                try { $pdo->exec('ALTER TABLE products ADD UNIQUE INDEX idx_products_fsc (fsc)'); } catch (\Throwable $e) { /* ignore */ }
            }
        } catch (\Throwable $e) { /* ignore migration errors */ }

        // Fix FSC duplicate entry issue: convert empty strings to NULL
        $fscResult = $this->fixFscDuplicates($pdo);

        // Auto-detect and merge product variants
        $variantResult = $this->autoMergeVariants($pdo);

        // Ensure tables for future features
        $this->ensureTable($pdo, 'order_events',
            'CREATE TABLE order_events (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, user_id INT NULL, type VARCHAR(64) NOT NULL, message VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->ensureTable($pdo, 'coupons',
            'CREATE TABLE coupons (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(64) NOT NULL UNIQUE, kind ENUM("percent","fixed") NOT NULL, amount DECIMAL(10,2) NOT NULL, min_spend DECIMAL(10,2) NULL, expires_at DATETIME NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->ensureTable($pdo, 'product_stock_events',
            'CREATE TABLE product_stock_events (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, user_id INT NULL, delta INT NOT NULL, reason VARCHAR(255) NULL, created_at DATETIME NOT NULL, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->ensureTable($pdo, 'tags',
            'CREATE TABLE tags (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE, slug VARCHAR(120) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->ensureTable($pdo, 'product_tags',
            'CREATE TABLE product_tags (product_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(product_id, tag_id), FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->ensureTable($pdo, 'delivery_fees',
            'CREATE TABLE delivery_fees (id INT AUTO_INCREMENT PRIMARY KEY, city VARCHAR(191) NOT NULL UNIQUE, fee DECIMAL(10,2) NOT NULL DEFAULT 0.00) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->ensureTable($pdo, 'customer_profiles',
            'CREATE TABLE customer_profiles (email VARCHAR(191) NOT NULL PRIMARY KEY, name VARCHAR(191) NULL, phone VARCHAR(64) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        // Indexes (best effort)
        $this->tryExec($pdo, 'CREATE INDEX idx_products_status_created ON products (status, created_at)');
        $this->tryExec($pdo, 'CREATE INDEX idx_products_collection ON products (collection_id, created_at)');
        $this->tryExec($pdo, 'CREATE INDEX idx_product_images_pid_order ON product_images (product_id, sort_order)');
        $this->tryExec($pdo, 'CREATE INDEX idx_orders_created_at ON orders (created_at)');
        $this->tryExec($pdo, 'CREATE INDEX idx_orders_email ON orders (email)');
        $this->tryExec($pdo, 'CREATE INDEX idx_collections_slug ON collections (slug)');

        // Build success message with variant merge info
        $successMsg = 'Optimization complete.';
        if ($variantResult['merged_groups'] > 0) {
            $successMsg .= " Merged {$variantResult['merged_groups']} variant groups ({$variantResult['merged_products']} products).";
        }

        $_SESSION['success'] = $successMsg;
        $this->redirect('/admin/maintenance?tab=actions');
    }

    /**
     * Fix FSC duplicate entry issue
     * Converts empty FSC strings to NULL to avoid duplicate key constraint violations
     */
    public function fixFsc(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/maintenance'); }
        $pdo = DB::pdo();
        $result = $this->fixFscDuplicates($pdo);
        $_SESSION['success'] = "FSC optimization complete. Fixed {$result['fixed']} empty FSC values. Table optimized.";
        $this->redirect('/admin/maintenance?tab=actions');
    }

    /**
     * Reset all variant relationships and re-detect from scratch
     * This clears parent_product_id and variant_attributes, then re-runs the auto-merge
     */
    public function resetVariants(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/maintenance'); }
        $pdo = DB::pdo();

        try {
            // Check if variant support is enabled
            $hasVariants = $pdo->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;

            if (!$hasVariants) {
                $_SESSION['error'] = 'Variant support is not enabled in the database.';
                $this->redirect('/admin/maintenance?tab=actions');
            }

            // Reset all variant relationships
            $pdo->exec("UPDATE products SET parent_product_id = NULL, variant_attributes = NULL");

            // Clear any orphaned slugs that might cause issues
            $pdo->exec("UPDATE products SET slug = CONCAT(slug, '-', id) WHERE slug IN (SELECT slug FROM (SELECT slug, COUNT(*) as cnt FROM products GROUP BY slug HAVING cnt > 1) as duplicates)");

            // Re-run the auto-merge
            $variantResult = $this->autoMergeVariants($pdo);

            $message = "Variant relationships reset. ";
            if ($variantResult['merged_groups'] > 0) {
                $message .= "Re-detected and merged {$variantResult['merged_groups']} variant groups ({$variantResult['merged_products']} products).";
            } else {
                $message .= "No variant groups were found in product titles. Products may already be properly organized.";
            }

            $_SESSION['success'] = $message;
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to reset variants: ' . $e->getMessage();
        }

        $this->redirect('/admin/maintenance?tab=actions');
    }

    /**
     * Internal method to fix FSC duplicate entry issue
     * Returns array with results
     */
    private function fixFscDuplicates(\PDO $pdo): array
    {
        $result = ['fixed' => 0, 'error' => null];

        try {
            // Check if products table has FSC column
            $hasFsc = $this->columnExists($pdo, 'products', 'fsc');
            if (!$hasFsc) {
                $result['error'] = 'FSC column does not exist in products table';
                return $result;
            }

            // Count empty FSC strings before fix
            $emptyCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE fsc = '' OR fsc = '")->fetchColumn();

            if ($emptyCount > 0) {
                // Convert empty FSC strings to NULL
                $pdo->exec("UPDATE products SET fsc = NULL WHERE fsc = '' OR fsc = ' '");
                $result['fixed'] = $emptyCount;
            }

            // Optimize the table to reclaim space
            try {
                $pdo->exec("OPTIMIZE TABLE products");
            } catch (\Throwable $e) {
                // Optimization may fail on some systems, ignore
            }

        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public function seedDemo(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin'); }
        $pdo = DB::pdo();
        $this->seedSamples($pdo);
        echo '<div style="padding:20px;font-family:system-ui">Seeded demo data. <a href="/admin">Back to admin</a></div>';
    }

    public function resetDb(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/maintenance'); }
        $pdo = DB::pdo();
        try {
            // 1) Ensure schema is present/consistent (best-effort)
            $this->runSqlFile($pdo, BASE_PATH . '/installer/schema.sql');

            // 2) Wipe data tables (preserve users/roles/permissions/settings)
            $tables = [
                'order_items','addresses','orders',
                'product_images','product_tags','product_stock_events','products',
                'tags','collections',
                'coupons','delivery_fees',
                'customer_profiles','pickup_locations'
            ];
            foreach ($tables as $t) {
                try { $pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}
                try { $pdo->exec('TRUNCATE TABLE `'.$t.'`'); } catch (\Throwable $e) {}
                try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}
            }

            // 3) Apply structural optimizations (add missing columns/indexes)
            $this->ensureColumn($pdo, 'products','stock','ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 0 AFTER status');
            $this->ensureColumn($pdo, 'products','sale_price','ALTER TABLE products ADD COLUMN sale_price DECIMAL(10,2) NULL AFTER price');
            $this->ensureColumn($pdo, 'products','sale_start','ALTER TABLE products ADD COLUMN sale_start DATETIME NULL AFTER sale_price');
            $this->ensureColumn($pdo, 'products','sale_end','ALTER TABLE products ADD COLUMN sale_end DATETIME NULL AFTER sale_start');
            $this->ensureColumn($pdo, 'collections','image_url','ALTER TABLE collections ADD COLUMN image_url VARCHAR(255) NULL AFTER description');
            $this->ensureColumn($pdo, 'orders','notes','ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER status');
            $this->ensureColumn($pdo, 'orders','discount','ALTER TABLE orders ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER shipping_fee');
            $this->ensureColumn($pdo, 'orders','coupon_code','ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(64) NULL AFTER discount');
            $this->ensureTable($pdo, 'order_events',
                'CREATE TABLE order_events (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, user_id INT NULL, type VARCHAR(64) NOT NULL, message VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            $this->ensureTable($pdo, 'product_stock_events',
                'CREATE TABLE product_stock_events (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, user_id INT NULL, delta INT NOT NULL, reason VARCHAR(255) NULL, created_at DATETIME NOT NULL, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

            // 4) Seed samples (no admin/users touched)
            $this->seedSamples($pdo);

            echo '<div style="padding:20px;font-family:system-ui">Database reset complete. Schema reinstalled and demo data seeded. Users and settings preserved. <a href="/admin">Back to admin</a></div>';
        } catch (\Throwable $e) {
            echo '<div style="padding:20px;font-family:system-ui">Reset failed: '.htmlspecialchars($e->getMessage()).' <a href="/admin">Back to admin</a></div>';
        }
    }

    private function runSqlFile(\PDO $pdo, string $path): void
    {
        if (!is_file($path)) { return; }
        $sql = file_get_contents($path);
        if ($sql === false) { return; }
        // Naive splitter by semicolon; safe for our simple schema file
        $stmts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        foreach ($stmts as $stmt) {
            if ($stmt === '' || strpos(ltrim($stmt), '--') === 0) { continue; }
            try { $pdo->exec($stmt); } catch (\Throwable $e) { /* ignore individual failures */ }
        }
    }

    private function seedSamples(\PDO $pdo): void
    {
        // Ensure collections exist
        $existing = 0;
        try { $existing = (int)$pdo->query('SELECT COUNT(*) FROM collections')->fetchColumn(); } catch (\Throwable $e) {}
        if ($existing < 6) {
            try {
                $pdo->exec("INSERT IGNORE INTO collections (title,slug,description) VALUES
                  ('Apparel','apparel','Clothing and fashion'),
                  ('Accessories','accessories','Complete your look'),
                  ('Electronics','electronics','Gadgets and devices'),
                  ('Home & Living','home-living','For your space'),
                  ('Beauty','beauty','Skincare and wellness'),
                  ('Sports','sports','Gear and active wear'),
                  ('Kids','kids','For the little ones')");
            } catch (\Throwable $e) {}
        }
        $collectionIds = [];
        try { $collectionIds = $pdo->query('SELECT id FROM collections ORDER BY id')->fetchAll(\PDO::FETCH_COLUMN); } catch (\Throwable $e) {}
        // Seed products
        try {
            $stmt = $pdo->prepare('INSERT INTO products (title,slug,description,price,status,stock,collection_id,created_at) VALUES (?,?,?,?,"active",?, ?, NOW())');
            $insImg = $pdo->prepare('INSERT INTO product_images (product_id,url,sort_order) VALUES (?,?,?)');
            $start = (int)$pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM products')->fetchColumn();
            for ($i=$start; $i<$start+300; $i++) {
                $t = "Demo Product $i"; $s = strtolower(preg_replace('/[^a-z0-9]+/','-', $t));
                $d = 'Modern demo item for showcasing the catalog.';
                $price = rand(199, 9999)/1.0;
                $r = rand(0,100); $stock = $r<10 ? 0 : ($r<30 ? rand(1,3) : rand(4,20));
                $cid = $collectionIds ? ($collectionIds[array_rand($collectionIds)] ?? null) : null;
                $stmt->execute([$t,$s,$d,$price,$stock,$cid]);
                $pid = (int)$pdo->lastInsertId();
                $count = rand(1,2); for($k=0;$k<$count;$k++){ $insImg->execute([$pid, 'https://picsum.photos/seed/'.($pid*10+$k).'/1000/1000', $k]); }
            }
        } catch (\Throwable $e) {}
        // Seed a few orders as well
        try {
            $productIds = $pdo->query('SELECT id,title,price FROM products ORDER BY RAND() LIMIT 100')->fetchAll(\PDO::FETCH_ASSOC);
            $insOrder = $pdo->prepare('INSERT INTO orders (user_id,email,shipping_method,subtotal,shipping_fee,total,status,notes,created_at) VALUES (?,?,?,?,?,?,?,?,?)');
            $insItem  = $pdo->prepare('INSERT INTO order_items (order_id,product_id,title,unit_price,quantity) VALUES (?,?,?,?,?)');
            for ($i=0;$i<10;$i++){
                $created = (new \DateTime('-'.rand(0,7).' days'))->format('Y-m-d '.sprintf('%02d:%02d:%02d', rand(9,20), rand(0,59), rand(0,59)));
                $itemsN = rand(1,3); $subtotal = 0.0; $chosen = [];
                for ($k=0;$k<$itemsN;$k++){ $p=$productIds[array_rand($productIds)]; if(isset($chosen[$p['id']]))continue; $chosen[$p['id']]=true; $qty=rand(1,3); $subtotal += ((float)$p['price'])*$qty; }
                $ship = (rand(0,1)?120.00:0.00); $total=$subtotal+$ship;
                $insOrder->execute([null,'guest'.rand(1000,9999).'@example.com', ($ship>0?'cod':'pickup'), $subtotal,$ship,$total,'processing','Seed order', $created]);
                $oid=(int)$pdo->lastInsertId(); foreach(array_keys($chosen) as $pid){ foreach($productIds as $p){ if((int)$p['id']===(int)$pid){ $qty=rand(1,3); $insItem->execute([$oid,$pid,$p['title'],$p['price'],$qty]); break; } } }
            }
        } catch (\Throwable $e) {}
        // Seed a sample coupon
        try {
            $exists = (int)$pdo->query('SELECT COUNT(*) FROM coupons')->fetchColumn();
            if ($exists === 0) {
                $pdo->prepare('INSERT INTO coupons (code,kind,amount,min_spend,expires_at,active,created_at) VALUES (?,?,?,?,?,?,NOW())')
                    ->execute(['WELCOME10','percent',10.0, null, date('Y-m-d H:i:s', strtotime('+90 days')), 1]);
            }
        } catch (\Throwable $e) {}
    }

    public function wipe(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/maintenance'); }
        $pdo = DB::pdo();
        $tables = [
            'order_items','addresses','orders',
            'product_images','product_tags','product_stock_events','products',
            'tags','collections',
            'coupons','delivery_fees',
            'customer_profiles'
        ];
        // TRUNCATE causes implicit commits; do not use transactions here
        try {
            try { $pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}
            foreach ($tables as $t) {
                try { $pdo->exec('TRUNCATE TABLE `'.$t.'`'); } catch (\Throwable $e) {}
            }
            try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}
            $_SESSION['success'] = 'All catalog and order data wiped.';
        } catch (\Throwable $e) {
            try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e2) {}
            $_SESSION['error'] = 'Failed to wipe data: '.$e->getMessage();
        }
        $this->redirect('/admin/maintenance?tab=actions');

    }



    public function wipeDemo(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/maintenance'); }
        $pdo = DB::pdo();
        // No transaction needed here
        try {
            // Delete demo orders (seeded via seedDemo)
            try {
                $ids = $pdo->query("SELECT id FROM orders WHERE notes='Seed order'")->fetchAll(\PDO::FETCH_COLUMN);
                if ($ids) {
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $st = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($in)"); $st->execute($ids);
                    $st = $pdo->prepare("DELETE FROM addresses WHERE order_id IN ($in)"); $st->execute($ids);
                    $st = $pdo->prepare("DELETE FROM orders WHERE id IN ($in)"); $st->execute($ids);
                }
            } catch (\Throwable $e) {}
            // Delete demo products and their images (title starts with 'Demo Product ')
            $pids = $pdo->query("SELECT id FROM products WHERE title LIKE 'Demo Product %'")->fetchAll(\PDO::FETCH_COLUMN);
            if ($pids) {
                $in = implode(',', array_fill(0, count($pids), '?'));
                try { $pdo->prepare("DELETE FROM product_images WHERE product_id IN ($in)")->execute($pids); } catch (\Throwable $e) {}
                try { $pdo->prepare("DELETE FROM product_tags WHERE product_id IN ($in)")->execute($pids); } catch (\Throwable $e) {}
                try { $pdo->prepare("DELETE FROM product_stock_events WHERE product_id IN ($in)")->execute($pids); } catch (\Throwable $e) {}
                $pdo->prepare("DELETE FROM products WHERE id IN ($in)")->execute($pids);
            }
                $_SESSION['success'] = 'Demo data removed.';
            } catch (\Throwable $e) {
                $_SESSION['error'] = 'Failed to wipe demo data: '.$e->getMessage();
            }
                $this->redirect('/admin/maintenance?tab=actions');
            }

    public function createBackup(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/maintenance'); }

        $backupType = $_POST['backup_type'] ?? 'full';
        $pdo = DB::pdo();

        try {
            // Create backup directory if it doesn't exist
            $backupDir = BASE_PATH . '/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generate backup filename with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_{$backupType}_{$timestamp}.sql";
            $filepath = $backupDir . '/' . $filename;

            // Get all tables
            $tables = [];
            $result = $pdo->query("SHOW TABLES");
            while ($row = $result->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            // Filter tables based on backup type
            if ($backupType === 'products') {
                $productTables = ['products', 'product_images', 'product_tags', 'tags', 'product_stock_events'];
                $tables = array_intersect($tables, $productTables);
            } elseif ($backupType === 'orders') {
                $orderTables = ['orders', 'order_items', 'addresses', 'order_events'];
                $tables = array_intersect($tables, $orderTables);
            } elseif ($backupType === 'users') {
                $userTables = ['users', 'user_roles', 'roles', 'customer_profiles'];
                $tables = array_intersect($tables, $userTables);
            }

            // Start creating backup
            $output = "-- Database Backup\n";
            $output .= "-- Type: {$backupType}\n";
            $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- Database: " . DB::pdo()->query("SELECT DATABASE()")->fetchColumn() . "\n\n";

            foreach ($tables as $table) {
                // Drop table if exists (for restore)
                $output .= "DROP TABLE IF EXISTS `{$table}`;\n";

                // Get table creation statement
                $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
                $output .= $createTable[1] . ";\n\n";

                // Get table data
                $result = $pdo->query("SELECT * FROM `{$table}`");
                $columnCount = $result->columnCount();

                // Insert statements
                while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
                    $columns = array_keys($row);
                    $values = array_values($row);

                    // Escape values
                    $escapedValues = array_map(function($value) use ($pdo) {
                        if ($value === null) return 'NULL';
                        return $pdo->quote($value);
                    }, $values);

                    $output .= "INSERT INTO `{$table}` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $escapedValues) . ");\n";
                }
                $output .= "\n";
            }

            // Write backup to file
            file_put_contents($filepath, $output);

            // Set proper permissions
            chmod($filepath, 0644);

            $_SESSION['success'] = "Backup created successfully: {$filename}";

        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to create backup: ' . $e->getMessage();
        }

        $this->redirect('/admin/maintenance?tab=backup');
    }

    public function restoreBackup(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/maintenance'); }

        $backupFile = $_POST['backup_file'] ?? '';
        $pdo = DB::pdo();

        try {
            // Validate backup file path
            $backupDir = BASE_PATH . '/backups';
            $filepath = $backupDir . '/' . basename($backupFile);

            if (!file_exists($filepath)) {
                throw new \Exception('Backup file not found');
            }

            if (!is_readable($filepath)) {
                throw new \Exception('Backup file is not readable');
            }

            // Read backup file
            $sqlContent = file_get_contents($filepath);
            if ($sqlContent === false) {
                throw new \Exception('Failed to read backup file');
            }

            // Split SQL statements (simple approach)
            $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sqlContent)));

            // Begin transaction
            $pdo->beginTransaction();

            $executedCount = 0;
            foreach ($statements as $statement) {
                // Skip comments and empty lines
                if (empty($statement) || strpos(ltrim($statement), '--') === 0) {
                    continue;
                }

                try {
                    $pdo->exec($statement);
                    $executedCount++;
                } catch (\Throwable $e) {
                    // Log error but continue with other statements
                    error_log("Backup restore error: " . $e->getMessage() . " SQL: " . $statement);
                }
            }

            $pdo->commit();

            $_SESSION['success'] = "Backup restored successfully! Executed {$executedCount} statements.";

        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = 'Failed to restore backup: ' . $e->getMessage();
        }

        $this->redirect('/admin/maintenance?tab=backup');
    }

    public function deleteBackup(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/maintenance'); }

        $backupFile = $_POST['backup_file'] ?? '';

        try {
            // Validate backup file path
            $backupDir = BASE_PATH . '/backups';
            $filepath = $backupDir . '/' . basename($backupFile);

            if (!file_exists($filepath)) {
                throw new \Exception('Backup file not found');
            }

            if (!unlink($filepath)) {
                throw new \Exception('Failed to delete backup file');
            }

            $_SESSION['success'] = "Backup file deleted successfully.";

        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to delete backup: ' . $e->getMessage();
        }

        $this->redirect('/admin/maintenance?tab=backup');
    }

    /**
     * Automatically detect and merge product variants based on title patterns
     * This helps organize products that have size/color variants in their titles
     */
    private function autoMergeVariants(\PDO $pdo): array
    {
        $result = ['merged_groups' => 0, 'merged_products' => 0, 'errors' => []];

        // Check if variant support is enabled
        $hasVariantsColumn = false;
        try {
            $hasVariantsColumn = $pdo->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        } catch (\Throwable $e) { $hasVariantsColumn = false; }

        if (!$hasVariantsColumn) {
            return $result;
        }

        // Get all products that are not already variants (parent_product_id IS NULL or 0)
        $stmt = $pdo->query('
            SELECT id, title, slug, parent_product_id
            FROM products
            WHERE (parent_product_id IS NULL OR parent_product_id = 0)
            AND status = "active"
            ORDER BY title
        ');
        $products = $stmt->fetchAll();

        // Group products by base title
        $groups = [];
        foreach ($products as $product) {
            $baseTitle = $this->extractBaseTitle($product['title']);

            // Only group if base title is different from full title AND is meaningful
            if ($baseTitle !== $product['title'] && strlen($baseTitle) >= 5) {
                if (!isset($groups[$baseTitle])) {
                    $groups[$baseTitle] = [];
                }
                $groups[$baseTitle][] = $product;
            }
        }

        // Merge each group
        foreach ($groups as $baseTitle => $groupProducts) {
            if (count($groupProducts) <= 1) {
                continue; // Skip groups with only 1 product
            }

            // Sort by ID to use the first/oldest as parent
            usort($groupProducts, function($a, $b) {
                return $a['id'] - $b['id'];
            });

            $parentProduct = $groupProducts[0];
            $parentId = $parentProduct['id'];

            // Update parent title to base title if needed
            if ($parentProduct['title'] !== $baseTitle) {
                try {
                    $pdo->prepare('UPDATE products SET title = ? WHERE id = ?')
                        ->execute([$baseTitle, $parentId]);
                } catch (\Throwable $e) {
                    $result['errors'][] = "Could not update parent product ID {$parentId}: " . $e->getMessage();
                }
            }

            // Update parent slug if needed
            $newSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower($baseTitle));
            $newSlug = trim($newSlug, '-');
            if ($newSlug !== '' && $newSlug !== $parentProduct['slug']) {
                try {
                    // Ensure unique slug
                    $baseSlug = $newSlug;
                    $suffix = 1;
                    $checkSlug = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ? AND id != ?');
                    do {
                        $checkSlug->execute([$newSlug, $parentId]);
                        $count = (int)$checkSlug->fetchColumn();
                        if ($count > 0) {
                            $newSlug = $baseSlug . '-' . $suffix++;
                        }
                    } while ($count > 0 && $suffix <= 100);
                    $pdo->prepare('UPDATE products SET slug = ? WHERE id = ?')
                        ->execute([$newSlug, $parentId]);
                } catch (\Throwable $e) {
                    // Ignore slug errors
                }
            }

            // Link other products as variants
            foreach (array_slice($groupProducts, 1) as $variantProduct) {
                $variantId = $variantProduct['id'];

                // Extract variant attribute from the title
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
                    $result['merged_products']++;
                } catch (\Throwable $e) {
                    $result['errors'][] = "Could not link product ID {$variantId}: " . $e->getMessage();
                }
            }

            $result['merged_groups']++;
        }

        return $result;
    }

    /**
     * Extract base title by removing variant patterns from the end
     */
    private function extractBaseTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            return '';
        }

        // Common variant patterns to remove from the end
        // Use (?:\s+) to ensure we only match after a space, not mid-word
        $patterns = [
            '(?:\s+)\d{2,3}[A-Z]{1,3}\s*$',      // Bra sizes: 38A, 36B, 34C, 32DD
            '(?:\s+)\d{1,2}\.\d{1,2}\s*$',       // Decimal: 28.5
            '(?:\s+)\d+(?:XL|XS|L|M|S)\s*$',     // 2XL, 3XL, 2XS
            '(?:\s+)(?:EXTRA LARGE|EXTRA SMALL|EXTRA LONG|EXTRA SHORT)\s*$',
            '(?:\s+)(?:XXXXL|XXXXXL|2XL|3XL|4XL|5XL|2XS|3XS)\s*$',
            '(?:\s+)(?:XL|XS)\s*$',
            '(?:\s+)(?:LARGE|MEDIUM|SMALL)\s*$',
            '(?:\s+)[LMS]\s*$',                  // Single letter sizes
            '(?:\s+)\d{1,2}\s*$',               // Standalone numbers
        ];

        foreach ($patterns as $pattern) {
            $newTitle = preg_replace('/' . $pattern . '/i', '', $title);
            $newTitle = trim($newTitle);
            if ($newTitle !== $title && strlen($newTitle) >= 5) {
                return $newTitle;
            }
        }

        return $title;
    }

    /**
     * Extract variant from title (returns array with 'variant' key)
     */
    private function extractVariantFromTitle(string $title): array
    {
        $title = trim($title);
        if ($title === '') {
            return ['variant' => ''];
        }

        // Common variant patterns
        $patterns = [
            '(?:\s+)(\d{2,3}[A-Z]{1,3})\s*$'      => 1, // Bra sizes
            '(?:\s+)(\d{1,2}\.\d{1,2})\s*$'       => 1, // Decimal sizes
            '(?:\s+)(\d+(?:XL|XS|L|M|S))\s*$'     => 1, // 2XL, etc.
            '(?:\s+)(EXTRA LARGE|EXTRA SMALL)\s*$'  => 1,
            '(?:\s+)(XXXXL|2XL|3XL)\s*$'           => 1,
            '(?:\s+)(XL|XS)\s*$'                  => 1,
            '(?:\s+)(LARGE|MEDIUM|SMALL)\s*$'     => 1,
            '(?:\s+)([LMS])\s*$'                  => 1, // Single letter
            '(?:\s+)(\d{1,2})\s*$'                => 1, // Numbers
        ];

        foreach ($patterns as $pattern => $groupIndex) {
            if (preg_match('/' . $pattern . '/i', $title, $matches)) {
                return ['variant' => strtoupper(trim($matches[1]))];
            }
        }

        return ['variant' => ''];
    }

private function ensureColumn(\PDO $pdo, string $table, string $column, string $alterSql): void
    {
        $st = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $st->execute([$table,$column]);
        $exists = (int)$st->fetchColumn() > 0;
        if (!$exists) { $pdo->exec($alterSql); }
    }

    private function ensureTable(\PDO $pdo, string $table, string $createSql): void
    {
        $st = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $st->execute([$table]);
        $exists = (int)$st->fetchColumn() > 0;
        if (!$exists) { $pdo->exec($createSql); }
    }

    private function tryExec(\PDO $pdo, string $sql): void
    {
        try { $pdo->exec($sql); } catch (\Throwable $e) { /* ignore */ }
    }

    private function columnExists(\PDO $pdo, string $table, string $column): bool
    {
        $st = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $st->execute([$table, $column]);
        return (int)$st->fetchColumn() > 0;
    }

    private function tableExists(\PDO $pdo, string $table): bool
    {
        $st = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $st->execute([$table]);
        return (int)$st->fetchColumn() > 0;
    }
}

