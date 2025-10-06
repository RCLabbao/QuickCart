<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB;

class AdminCustomersController extends Controller
{
    public function index(): void
    {
        $pdo = DB::pdo();
        // Ensure customer_profiles exists (avoid 500s on older databases)
        try { $pdo->exec('CREATE TABLE IF NOT EXISTS customer_profiles (email VARCHAR(191) NOT NULL PRIMARY KEY, name VARCHAR(191) NULL, phone VARCHAR(64) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'); } catch (\Throwable $e) {}
        $q = trim((string)($_GET['q'] ?? ''));
        $params = [];
        $where = "WHERE o.email IS NOT NULL AND o.email <> ''";
        if ($q !== '') {
            $where .= " AND (o.email LIKE ? OR o.email IN (SELECT DISTINCT o2.email FROM orders o2 JOIN addresses a2 ON a2.order_id=o2.id WHERE a2.name LIKE ?))";
            $like = "%$q%"; $params[] = $like; $params[] = $like;
        }
        $sql = "SELECT o.email,
                       COALESCE(MAX(cp.name), (SELECT a.name FROM addresses a WHERE a.order_id = (SELECT id FROM orders i WHERE i.email=o.email AND i.shipping_method='cod' ORDER BY id DESC LIMIT 1) LIMIT 1)) AS name,
                       COUNT(*) AS orders,
                       COALESCE(SUM(o.total),0) AS spent
                FROM orders o
                LEFT JOIN customer_profiles cp ON cp.email = o.email
                $where
                GROUP BY o.email
                ORDER BY spent DESC
                LIMIT 200";
        $st = $pdo->prepare($sql); $st->execute($params);
        $rows = $st->fetchAll();
        $this->adminView('admin/customers/index', ['title' => 'Customers', 'customers'=>$rows, 'q'=>$q]);
    }
    public function export(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=customers.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Email','Orders','Total Spent']);
        $sql = "SELECT email, COUNT(*) as orders, COALESCE(SUM(total),0) as spent FROM orders WHERE email IS NOT NULL AND email <> '' GROUP BY email ORDER BY spent DESC";
        $stmt = DB::pdo()->query($sql);
        while ($row = $stmt->fetch()) { fputcsv($out, [$row['email'], $row['orders'], $row['spent']]); }
        fclose($out); exit;
    }

    public function show(): void
    {
        $email = trim((string)($_GET['email'] ?? ''));
        if ($email==='') { $this->redirect('/admin/customers'); }
        $pdo = DB::pdo();
        // Ensure customer_profiles exists before queries
        try { $pdo->exec('CREATE TABLE IF NOT EXISTS customer_profiles (email VARCHAR(191) NOT NULL PRIMARY KEY, name VARCHAR(191) NULL, phone VARCHAR(64) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'); } catch (\Throwable $e) {}
        $st = $pdo->prepare('SELECT COUNT(*) orders, COALESCE(SUM(total),0) spent, MAX(created_at) last_order FROM orders WHERE email=?');
        $st->execute([$email]); $stats = $st->fetch();
        $orders = $pdo->prepare('SELECT id,total,status,created_at FROM orders WHERE email=? ORDER BY id DESC');
        $orders->execute([$email]); $orders = $orders->fetchAll();
        $prof = $pdo->prepare('SELECT email,name,phone FROM customer_profiles WHERE email=?');
        try { $prof->execute([$email]); $profile = $prof->fetch(); } catch (\Throwable $e) { $profile = null; }
        $this->adminView('admin/customers/show', ['title' => 'Customer Details', 'email' => $email, 'stats' => $stats, 'orders' => $orders, 'profile' => $profile]);
    }

    public function updateProfile(): void
    {
        if (!isset($_POST['_token']) || !\App\Core\CSRF::check($_POST['_token'])) { $this->redirect('/admin/customers'); }
        $email = trim((string)($_POST['email'] ?? ''));
        if ($email==='') { $this->redirect('/admin/customers'); }
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $pdo = DB::pdo();
        try {
            // Ensure table exists before upsert
            try { $pdo->exec('CREATE TABLE IF NOT EXISTS customer_profiles (email VARCHAR(191) NOT NULL PRIMARY KEY, name VARCHAR(191) NULL, phone VARCHAR(64) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'); } catch (\Throwable $e) {}
            $stmt = $pdo->prepare('INSERT INTO customer_profiles (email,name,phone,created_at,updated_at) VALUES (?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone), updated_at=NOW()');
            $stmt->execute([$email,$name,$phone,date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {}
        $this->redirect('/admin/customers/view?email='.urlencode($email));
    }

	    public function seedDummies(): void
	    {
	        if (!isset($_POST['_token']) || !\App\Core\CSRF::check($_POST['_token'])) { $this->redirect('/admin/customers'); }
	        $pdo = DB::pdo();
	        $pdo->beginTransaction();
	        try {
	            // Pick some products
	            $products = $pdo->query('SELECT id,title,price FROM products ORDER BY RAND() LIMIT 20')->fetchAll(\PDO::FETCH_ASSOC);
	            for ($i=0; $i<5; $i++) {
	                $email = 'dummy'.str_pad((string)rand(1,9999), 4, '0', STR_PAD_LEFT).'@example.com';
	                $name = 'Dummy Customer '.substr($email, 5, 4);
	                $phone = '+63 9'.rand(10,99).rand(100,999).rand(1000,9999);
	                $itemsN = max(1, rand(1,2)); $subtotal = 0.0; $chosen = [];
	                for ($k=0; $k<$itemsN; $k++) { $p=$products[array_rand($products)]; if(isset($chosen[$p['id']]))continue; $chosen[$p['id']]=true; $qty=rand(1,2); $subtotal += ((float)$p['price'])*$qty; }
	                $shipMethod = (rand(0,1)?'pickup':'cod'); $shipFee = $shipMethod==='cod' ? (float)($pdo->query("SELECT value FROM settings WHERE `key`='shipping_fee_cod'")->fetchColumn() ?: 0) : 0.0;
	                $total = $subtotal + $shipFee;
	                $pdo->prepare('INSERT INTO orders (email, shipping_method, subtotal, shipping_fee, total, status, notes, created_at) VALUES (?,?,?,?,?,"processing",?,NOW())')
	                    ->execute([$email, $shipMethod, $subtotal, $shipFee, $total, 'Dummy seed']);
	                $oid = (int)$pdo->lastInsertId();
	                foreach (array_keys($chosen) as $pid) {
	                    foreach ($products as $p) { if ((int)$p['id']===(int)$pid) { $qty=rand(1,2); $pdo->prepare('INSERT INTO order_items (order_id,product_id,title,unit_price,quantity) VALUES (?,?,?,?,?)')->execute([$oid,$pid,$p['title'],$p['price'],$qty]); break; } }
	                }
	                // Address
	                try { $pdo->prepare('INSERT INTO addresses (order_id, name, phone, region, province, city, barangay, street, postal_code) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$oid,$name,$phone,null,null,'Test City',null,'',null]); } catch (\Throwable $e) {}
	                // Upsert profile (best-effort)
	                try { $pdo->exec('CREATE TABLE IF NOT EXISTS customer_profiles (email VARCHAR(191) NOT NULL PRIMARY KEY, name VARCHAR(191) NULL, phone VARCHAR(64) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'); } catch (\Throwable $e) {}
	                try { $pdo->prepare('INSERT INTO customer_profiles (email,name,phone,created_at,updated_at) VALUES (?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone), updated_at=NOW()')->execute([$email,$name,$phone,date('Y-m-d H:i:s')]); } catch (\Throwable $e) {}
	            }
	            $pdo->commit();
	            $_SESSION['success'] = 'Seeded 5 dummy customers with sample orders.';
	        } catch (\Throwable $e) {
	            $pdo->rollBack();
	            $_SESSION['error'] = 'Failed to seed dummy customers: '.$e->getMessage();
	        }
	        $this->redirect('/admin/customers');
	    }
}
