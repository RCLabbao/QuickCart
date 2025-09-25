<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB;

class AdminController extends Controller
{
    public function dashboard(): void
    {
        $pdo = DB::pdo();
        $orders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $revenue = (float)$pdo->query('SELECT COALESCE(SUM(total),0) FROM orders')->fetchColumn();
        $products = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        // last 7 days sales totals
        $labels = []; $values = [];
        for ($i=6; $i>=0; $i--) {
            $d = new \DateTime("-$i day"); $labels[] = $d->format('M j');
            $st = $pdo->prepare('SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=?');
            $st->execute([$d->format('Y-m-d')]);
            $values[] = (float)$st->fetchColumn();
        }
        // Low stock (<=3)
        $low = $pdo->query('SELECT id,title,COALESCE(stock,0) AS stock FROM products WHERE COALESCE(stock,0) <= 3 AND status="active" ORDER BY stock ASC, id DESC LIMIT 5')->fetchAll();
        // Top sellers (last 30 days)
        $since = (new \DateTime('-30 days'))->format('Y-m-d 00:00:00');
        $top = $pdo->prepare('SELECT i.product_id, i.title, SUM(i.quantity) qty FROM order_items i JOIN orders o ON o.id=i.order_id WHERE o.created_at >= ? GROUP BY i.product_id,i.title ORDER BY qty DESC LIMIT 5');
        $top->execute([$since]); $top = $top->fetchAll();
        $this->adminView('admin/dashboard', [
            'title' => 'Dashboard',
            'orders'=>$orders,
            'revenue'=>$revenue,
            'products'=>$products,
            'sales_labels'=>$labels,
            'sales_values'=>$values,
            'low_stock'=>$low,
            'top_sellers'=>$top,
        ]);
    }
}

