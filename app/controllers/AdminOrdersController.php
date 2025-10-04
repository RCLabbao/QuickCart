<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF; use App\Core\Auth;

class AdminOrdersController extends Controller
{
    public function index(): void
    {
        $pdo = DB::pdo(); $where=[]; $params=[];
        if (!empty($_GET['status'])) { $where[]='status=?'; $params[]=$_GET['status']; }
        if (!empty($_GET['from'])) { $where[]='created_at >= ?'; $params[]=$_GET['from'].' 00:00:00'; }
        if (!empty($_GET['to'])) { $where[]='created_at <= ?'; $params[]=$_GET['to'].' 23:59:59'; }
        $sql = 'SELECT id, email, shipping_method, total, status, created_at FROM orders';
        if ($where) { $sql .= ' WHERE '.implode(' AND ',$where); }
        $sql .= ' ORDER BY id DESC LIMIT 200';
        $st = $pdo->prepare($sql); $st->execute($params);
        $rows = $st->fetchAll();

        // Get overall statistics (not filtered)
        $stats = [
            'total_orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'pending_orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn(),
            'completed_orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE status = "completed"')->fetchColumn(),
            'total_revenue' => (float)$pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = "completed"')->fetchColumn()
        ];

        $this->adminView('admin/orders/index', ['title' => 'Orders', 'orders'=>$rows, 'stats'=>$stats]);
    }

    public function show(array $params): void
    {
        $pdo = DB::pdo(); $id = (int)$params['id'];
        $st = $pdo->prepare('SELECT * FROM orders WHERE id=?'); $st->execute([$id]); $order = $st->fetch();
        if(!$order){ header('Location: /admin/orders'); return; }
        $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id=?'); $items->execute([$id]); $items = $items->fetchAll();
        $addr = $pdo->prepare('SELECT * FROM addresses WHERE order_id=?'); $addr->execute([$id]); $address = $addr->fetch();
        // Events timeline (best-effort)
        try {
            $ev = $pdo->prepare('SELECT e.*, u.name AS user_name FROM order_events e LEFT JOIN users u ON u.id=e.user_id WHERE e.order_id=? ORDER BY e.id DESC');
            $ev->execute([$id]); $events = $ev->fetchAll();
        } catch (\Throwable $e) { $events = []; }
        $this->view('admin/orders/show', compact('order','items','address','events'));
    }

    public function updateStatus(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/orders'); }
        $status = $_POST['status'] ?? 'pending';
        $oid = (int)$params['id'];
        $pdo = DB::pdo();
        $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $oid]);
        // log event (best-effort)
        try {
            $this->ensureOrderEvents($pdo);
            $pdo->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())')
                ->execute([$oid, Auth::userId(), 'status', 'Status changed to '. $status]);
        } catch (\Throwable $e) {}
        $this->redirect('/admin/orders/'.$params['id']);
    }
    public function export(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=orders.csv');
        $out = fopen('php://output', 'w');
        // Detect optional columns
        $pdo = DB::pdo();
        $hasDiscount = $pdo->query("SHOW COLUMNS FROM orders LIKE 'discount'")->rowCount() > 0;
        $headers = ['ID','Email','Method','Subtotal'];
        if ($hasDiscount) { $headers[] = 'Discount'; }
        $headers = array_merge($headers, ['Shipping Fee','Total','Status','Created At']);
        fputcsv($out, $headers);
        $where=[]; $params=[];
        if (!empty($_GET['from'])) { $where[]='created_at >= ?'; $params[]=$_GET['from'].' 00:00:00'; }
        if (!empty($_GET['to'])) { $where[]='created_at <= ?'; $params[]=$_GET['to'].' 23:59:59'; }
        $sql = 'SELECT id,email,shipping_method,subtotal'.($hasDiscount?',discount':'').',shipping_fee,total,status,created_at FROM orders';
        if ($where) { $sql .= ' WHERE '.implode(' AND ',$where); }
        $sql .= ' ORDER BY id DESC';
        $st = $pdo->prepare($sql); $st->execute($params);
        while ($row = $st->fetch()) {
            $data = [$row['id'],$row['email'],$row['shipping_method'],$row['subtotal']];
            if ($hasDiscount) { $data[] = $row['discount']; }
            $data = array_merge($data, [$row['shipping_fee'],$row['total'],$row['status'],$row['created_at']]);
            fputcsv($out, $data);
        }
        fclose($out);
        exit;
    }
    public function bulkStatus(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/orders'); }
        $ids = array_map('intval', $_POST['ids'] ?? []); $status = $_POST['status'] ?? 'pending';
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $st = DB::pdo()->prepare("UPDATE orders SET status=? WHERE id IN ($in)");
            $params = array_merge([$status], $ids); $st->execute($params);
            try {
                $pdo = DB::pdo();
                $this->ensureOrderEvents($pdo);
                $evt = $pdo->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())');
                foreach ($ids as $oid) { $evt->execute([$oid, Auth::userId(), 'status', 'Bulk status set to '.$status]); }
            } catch (\Throwable $e) {}
        }
        // Redirect back to the referring page or default to orders
        $referer = $_SERVER['HTTP_REFERER'] ?? '/admin/orders';
        if (strpos($referer, '/admin/orders') !== false) {
            $this->redirect($referer);
        } else {
            $this->redirect('/admin/orders');
        }
    }

    public function today(): void
    {
        $pdo = DB::pdo();
        // Determine rolling window based on cutoff time
        $cutoff = (string)\App\Core\setting('today_cutoff', '00:00');
        if (!preg_match('/^\d{2}:\d{2}$/', $cutoff)) { $cutoff = '00:00'; }
        $now = new \DateTime('now');
        $todayCutoff = new \DateTime(date('Y-m-d') . ' ' . $cutoff);
        if ($now < $todayCutoff) {
            $start = (new \DateTime('yesterday ' . $cutoff));
            $end = $todayCutoff;
        } else {
            $start = $todayCutoff;
            $end = (new \DateTime('tomorrow ' . $cutoff));
        }
        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        $st = $pdo->prepare('SELECT id, email, shipping_method, total, status, created_at FROM orders WHERE created_at >= ? AND created_at < ? ORDER BY id DESC');
        $st->execute([$startStr, $endStr]);
        $rows = $st->fetchAll();

        $st2 = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at < ?');
        $st2->execute([$startStr, $endStr]);
        $today_orders = (int)$st2->fetchColumn();

        $st3 = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at < ? AND status = "pending"');
        $st3->execute([$startStr, $endStr]);
        $today_pending = (int)$st3->fetchColumn();

        $st4 = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at < ? AND status = "completed"');
        $st4->execute([$startStr, $endStr]);
        $today_completed = (int)$st4->fetchColumn();

        $st5 = $pdo->prepare('SELECT COALESCE(SUM(total),0) FROM orders WHERE created_at >= ? AND created_at < ? AND status = "completed"');
        $st5->execute([$startStr, $endStr]);
        $today_revenue = (float)$st5->fetchColumn();

        $stats = [
            'today_orders' => $today_orders,
            'today_pending' => $today_pending,
            'today_completed' => $today_completed,
            'today_revenue' => $today_revenue,
        ];

        $this->adminView('admin/orders/today', ['title' => 'Today\'s Orders', 'orders'=>$rows, 'stats'=>$stats, 'window_start'=>$startStr, 'window_end'=>$endStr]);
    }

    public function delete(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/orders'); }
        $pdo = DB::pdo(); $pdo->beginTransaction();
        try {
            $id=(int)$params['id'];
            $pdo->prepare('DELETE FROM order_items WHERE order_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM addresses WHERE order_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM orders WHERE id=?')->execute([$id]);
            $pdo->commit();
        } catch (\Throwable $e) { $pdo->rollBack(); }
        $this->redirect('/admin/orders');
    }

    public function fulfill(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            $this->redirect('/admin/orders');
            return;
        }

        $pdo = DB::pdo();
        $oid = (int)$params['id'];
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT status FROM orders WHERE id=? FOR UPDATE');
            $st->execute([$oid]);
            $status = $st->fetchColumn();
            if ($status === false) { throw new \RuntimeException('Order not found'); }
            if ($status === 'completed') { $pdo->commit(); $this->redirect('/admin/orders/'.$oid); return; }

            if ($status === 'draft') {
                // Decrement stock now (conversion from draft)
                $it = $pdo->prepare('SELECT product_id, quantity, title FROM order_items WHERE order_id=?');
                $it->execute([$oid]);
                $items = $it->fetchAll();
                if (!$items) { throw new \RuntimeException('No items to fulfill'); }
                foreach ($items as $row) {
                    $pid = (int)$row['product_id']; $qty = (int)$row['quantity'];
                    if ($pid) {
                        $u = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND COALESCE(stock,0) >= ?');
                        $u->execute([$qty, $pid, $qty]);
                        if ($u->rowCount() === 0) { throw new \RuntimeException('Insufficient stock for product ID '.$pid); }
                        try { $pdo->prepare('INSERT INTO product_stock_events (product_id,user_id,delta,reason,created_at) VALUES (?,?,?,?,NOW())')
                                ->execute([$pid, Auth::userId(), -$qty, 'fulfill order #'.$oid]); } catch (\Throwable $e) {}
                    }
                }
            }

            // Mark completed
            $pdo->prepare('UPDATE orders SET status="completed" WHERE id=?')->execute([$oid]);
            try {
                $this->ensureOrderEvents($pdo);
                $pdo->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())')
                    ->execute([$oid, Auth::userId(), 'status', 'Marked as fulfilled']);
            } catch (\Throwable $e) {}
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to fulfill order: '.$e->getMessage();
            $this->redirect('/admin/orders/'.$oid);
            return;
        }

        // Check if request came from today's orders page
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, '/admin/orders/today') !== false) {
            $this->redirect('/admin/orders/today');
        } else {
            $this->redirect('/admin/orders/'.$params['id']);
        }
    }

    public function note(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/orders/'.$params['id']); }
        $note = trim((string)($_POST['note'] ?? ''));
        DB::pdo()->prepare('UPDATE orders SET notes=? WHERE id=?')->execute([$note, (int)$params['id']]);
        $this->redirect('/admin/orders/'.$params['id']);
    }

    public function exportItems(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=orders_with_items.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Order ID','Email','Status','Created','Item Product ID','Item Title','Unit Price','Quantity','Line Total']);
        $sql = 'SELECT o.id AS order_id, o.email, o.status, o.created_at, i.product_id, i.title, i.unit_price, i.quantity
                FROM orders o JOIN order_items i ON i.order_id=o.id ORDER BY o.id DESC, i.id ASC';
        $stmt = DB::pdo()->query($sql);
        while ($row = $stmt->fetch()) {
            $line = (float)$row['unit_price'] * (int)$row['quantity'];
            fputcsv($out, [$row['order_id'],$row['email'],$row['status'],$row['created_at'],$row['product_id'],$row['title'],$row['unit_price'],$row['quantity'],$line]);
        }
        fclose($out); exit;
    }

    public function refund(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/orders'); }
        $oid = (int)$params['id'];
        // Simple refund: mark cancelled (placeholder)
        $pdo = DB::pdo();
        $pdo->prepare('UPDATE orders SET status="cancelled" WHERE id=?')->execute([$oid]);
        try { $this->ensureOrderEvents($pdo); $pdo->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())')->execute([$oid, Auth::userId(), 'refund', 'Order refunded (placeholder)']); } catch (\Throwable $e) {}
        $this->redirect('/admin/orders/'.$oid);
    }
    public function resendEmail(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/orders'); }
        $oid = (int)$params['id'];
        try { $pdo = DB::pdo(); $this->ensureOrderEvents($pdo); $pdo->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())')->execute([$oid, Auth::userId(), 'email', 'Order email re-sent (placeholder)']); } catch (\Throwable $e) {}
        $this->redirect('/admin/orders/'.$oid);
    }
    public function invoice(array $params): void
    {
        $oid = (int)$params['id'];
        $pdo = DB::pdo();
        $o = $pdo->prepare('SELECT * FROM orders WHERE id=?'); $o->execute([$oid]); $order = $o->fetch(); if(!$order){ $this->redirect('/admin/orders'); }
        $it = $pdo->prepare('SELECT * FROM order_items WHERE order_id=?'); $it->execute([$oid]); $items = $it->fetchAll();
        $this->view('admin/orders/invoice', compact('order','items'));
    }
    public function createManual(): void
    {
        $this->adminView('admin/orders/create_manual', ['title' => 'Create Manual Order']);
    }

    public function storeManual(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/orders'); }
        $email = trim((string)($_POST['email'] ?? ''));
        $shipping_method = in_array(($_POST['shipping_method'] ?? 'pickup'), ['pickup','cod']) ? $_POST['shipping_method'] : 'pickup';
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $address1 = trim((string)($_POST['address1'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $items = $_POST['items'] ?? [];
        if (!$items || !is_array($items)) { $_SESSION['error']='Add at least one item.'; $this->redirect('/admin/orders/create'); }
        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            $subtotal = 0.0; $lineItems = [];
            $get = $pdo->prepare('SELECT id,title,price,COALESCE(stock,0) AS stock FROM products WHERE id=?');
            foreach ($items as $it) {
                $pid = (int)($it['product_id'] ?? 0); $qty = max(1, (int)($it['quantity'] ?? 1));
                if ($pid<=0) continue;
                $get->execute([$pid]); $p = $get->fetch(); if(!$p){ continue; }
                if ((int)$p['stock'] < $qty) { throw new \Exception('Insufficient stock for product ID '.$pid); }
                $unit = (float)$p['price']; $subtotal += $unit * $qty;
                $lineItems[] = ['product_id'=>$pid,'title'=>$p['title'],'unit_price'=>$unit,'quantity'=>$qty];
            }
            if (!$lineItems) { throw new \Exception('No valid items'); }
            // Shipping fee
            $ship = 0.0;
            try {
                $ship = (float)\App\Core\setting($shipping_method==='cod'?'shipping_fee_cod':'shipping_fee_pickup', 0.0);
            } catch (\Throwable $e) { $ship = 0.0; }
            $total = $subtotal + $ship;
            // Insert order
            $ins = $pdo->prepare('INSERT INTO orders (email, shipping_method, subtotal, shipping_fee, total, status, notes, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
            $ins->execute([$email ?: 'manual@local', $shipping_method, $subtotal, $ship, $total, 'draft', 'Manual draft order']);
            $oid = (int)$pdo->lastInsertId();
            // Items
            $insItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, title, unit_price, quantity) VALUES (?,?,?,?,?)');
            foreach ($lineItems as $li) {
                $insItem->execute([$oid, $li['product_id'], $li['title'], $li['unit_price'], $li['quantity']]);
            }
            // Address (optional)
            if ($shipping_method === 'cod' && ($address1 || $city || $name)) {
                try {
                    $pdo->prepare('INSERT INTO addresses (order_id,name,phone,city,street) VALUES (?,?,?,?,?)')
                        ->execute([$oid, $name, $phone, $city, $address1]);
                } catch (\Throwable $e) {}
            }
            // Event
            try { $this->ensureOrderEvents($pdo); $pdo->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())')
                    ->execute([$oid, Auth::userId(), 'create', 'Manual order created']); } catch (\Throwable $e) {}
            $pdo->commit();
            $this->redirect('/admin/orders/'.$oid);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to create order: '.$e->getMessage();
            $this->redirect('/admin/orders/create');
        }
    }

    // Ensure order_events table exists (used by various actions)
    private function ensureOrderEvents(\PDO $pdo): void
    {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS order_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                user_id INT NULL,
                type VARCHAR(64) NOT NULL,
                message VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_order_events_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                CONSTRAINT fk_order_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) { /* ignore */ }
    }



}

