<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB;

class AdminReportsController extends Controller
{
    public function index(): void
    {
        $pdo = DB::pdo();
        $from = $_GET['from'] ?? '';
        $to   = $_GET['to']   ?? '';
        if ($from === '' || $to === '') {
            // Default to last 30 days
            $to = date('Y-m-d');
            $from = date('Y-m-d', strtotime('-30 days'));
        }
        $fromTs = $from . ' 00:00:00';
        $toTs   = $to   . ' 23:59:59';

        // Sales by day
        $byDay = $pdo->prepare('SELECT DATE(created_at) d, COUNT(*) orders, SUM(total) revenue FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 60');
        $byDay->execute([$fromTs, $toTs]);
        $salesByDay = $byDay->fetchAll();

        // Sales by collection
        $sqlCol = 'SELECT COALESCE(c.title, "(None/Deleted)") AS collection, SUM(oi.unit_price*oi.quantity) revenue, SUM(oi.quantity) qty
                   FROM order_items oi
                   JOIN orders o ON o.id = oi.order_id
                   LEFT JOIN products p ON p.id = oi.product_id
                   LEFT JOIN collections c ON c.id = p.collection_id
                   WHERE o.created_at BETWEEN ? AND ?
                   GROUP BY c.id, c.title
                   ORDER BY revenue DESC
                   LIMIT 20';
        $stCol = $pdo->prepare($sqlCol); $stCol->execute([$fromTs,$toTs]);
        $byCollection = $stCol->fetchAll();

        // Top products
        $sqlTop = 'SELECT COALESCE(p.title, oi.title) AS title, SUM(oi.quantity) qty, SUM(oi.unit_price*oi.quantity) revenue
                   FROM order_items oi
                   JOIN orders o ON o.id = oi.order_id
                   LEFT JOIN products p ON p.id = oi.product_id
                   WHERE o.created_at BETWEEN ? AND ?
                   GROUP BY p.id, title
                   ORDER BY revenue DESC
                   LIMIT 20';
        $stTop = $pdo->prepare($sqlTop); $stTop->execute([$fromTs,$toTs]);
        $topProducts = $stTop->fetchAll();

        $this->adminView('admin/reports/index', ['title' => 'Reports', 'from' => $from, 'to' => $to, 'salesByDay' => $salesByDay, 'byCollection' => $byCollection, 'topProducts' => $topProducts]);
    }
}

