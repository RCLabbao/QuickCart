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
        DB::pdo()->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $oid]);
        // log event (best-effort)
        try { DB::pdo()->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())')->execute([$oid, Auth::userId(), 'status', 'Status changed to '. $status,]); } catch (\Throwable $e) {}
        $this->redirect('/admin/orders/'.$params['id']);
    }
    public function export(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=orders.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Email','Method','Subtotal','Discount','Shipping Fee','Total','Status','Created At']);
        $where=[]; $params=[];
        if (!empty($_GET['from'])) { $where[]='created_at >= ?'; $params[]=$_GET['from'].' 00:00:00'; }
        if (!empty($_GET['to'])) { $where[]='created_at <= ?'; $params[]=$_GET['to'].' 23:59:59'; }
        $sql = 'SELECT id,email,shipping_method,subtotal,discount,shipping_fee,total,status,created_at FROM orders';
        if ($where) { $sql .= ' WHERE '.implode(' AND ',$where); }
        $sql .= ' ORDER BY id DESC';
        $st = DB::pdo()->prepare($sql); $st->execute($params);
        while ($row = $st->fetch()) {
            fputcsv($out, [$row['id'],$row['email'],$row['shipping_method'],$row['subtotal'],$row['discount'],$row['shipping_fee'],$row['total'],$row['status'],$row['created_at']]);
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
                $evt = DB::pdo()->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())');
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
        $rows = $pdo->query('SELECT id, email, shipping_method, total, status, created_at FROM orders WHERE DATE(created_at)=CURDATE() ORDER BY id DESC')->fetchAll();

        // Get today's statistics
        $stats = [
            'today_orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
            'today_pending' => (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE() AND status = "pending"')->fetchColumn(),
            'today_completed' => (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE() AND status = "completed"')->fetchColumn(),
            'today_revenue' => (float)$pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE(created_at)=CURDATE() AND status = "completed"')->fetchColumn()
        ];

        $this->adminView('admin/orders/today', ['title' => 'Today\'s Orders', 'orders'=>$rows, 'stats'=>$stats]);
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

        $oid = (int)$params['id'];
        DB::pdo()->prepare('UPDATE orders SET status="completed" WHERE id=?')->execute([$oid]);

        try {
            DB::pdo()->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())')
                ->execute([$oid, Auth::userId(), 'status', 'Marked as fulfilled']);
        } catch (\Throwable $e) {}

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
        DB::pdo()->prepare('UPDATE orders SET status="cancelled" WHERE id=?')->execute([$oid]);
        try { DB::pdo()->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())')->execute([$oid, Auth::userId(), 'refund', 'Order refunded (placeholder)']); } catch (\Throwable $e) {}
        $this->redirect('/admin/orders/'.$oid);
    }
    public function resendEmail(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/orders'); }
        $oid = (int)$params['id'];
        try { DB::pdo()->prepare('INSERT INTO order_events (order_id,user_id,type,message,created_at) VALUES (?,?,?,?,NOW())')->execute([$oid, Auth::userId(), 'email', 'Order email re-sent (placeholder)']); } catch (\Throwable $e) {}
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

}

