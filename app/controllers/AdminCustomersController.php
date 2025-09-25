<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB;

class AdminCustomersController extends Controller
{
    public function index(): void
    {
        $sql = "SELECT email, COUNT(*) as orders, COALESCE(SUM(total),0) as spent FROM orders WHERE email IS NOT NULL AND email <> '' GROUP BY email ORDER BY spent DESC LIMIT 200";
        $rows = DB::pdo()->query($sql)->fetchAll();
        $this->adminView('admin/customers/index', ['title' => 'Customers', 'customers'=>$rows]);
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
        $st = $pdo->prepare('SELECT COUNT(*) orders, COALESCE(SUM(total),0) spent, MAX(created_at) last_order FROM orders WHERE email=?');
        $st->execute([$email]); $stats = $st->fetch();
        $orders = $pdo->prepare('SELECT id,total,status,created_at FROM orders WHERE email=? ORDER BY id DESC');
        $orders->execute([$email]); $orders = $orders->fetchAll();
        $this->adminView('admin/customers/show', ['title' => 'Customer Details', 'email' => $email, 'stats' => $stats, 'orders' => $orders]);
    }
}
