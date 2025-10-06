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
        ];
        foreach ($checkItems as [$tbl,$col,$label]) {
            try { $checks[$label] = $this->columnExists($pdo, $tbl, $col); } catch (\Throwable $e) { $checks[$label] = false; }
        }
        foreach (['coupons','order_events','delivery_fees','customer_profiles'] as $tbl) {
            try { $checks[$tbl.' table'] = $this->tableExists($pdo, $tbl); } catch (\Throwable $e) { $checks[$tbl.' table'] = false; }
        }

        $this->adminView('admin/maintenance/index', [
            'title' => 'System Maintenance',
            'stats' => $stats,
            'table_sizes' => $tableSizes,
            'checks' => $checks
        ]);
    }

    public function optimize(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin'); }
        $pdo = DB::pdo();
        // Add products.stock if missing
        $this->ensureColumn($pdo, 'products','stock','ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 0 AFTER status');
        // Add promo/sale columns if missing
        $this->ensureColumn($pdo, 'products','sale_price','ALTER TABLE products ADD COLUMN sale_price DECIMAL(10,2) NULL AFTER price');
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

        $_SESSION['success'] = 'Optimization complete.';
        $this->redirect('/admin/maintenance?tab=actions');
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

