<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class AdminCouponsController extends Controller
{
    public function index(): void
    {
        $rows = DB::pdo()->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll();
        $this->adminView('admin/coupons/index', ['title' => 'Coupons', 'coupons'=>$rows]);
    }
    public function create(): void
    {
        $this->adminView('admin/coupons/create', ['title' => 'Create Coupon']);
    }
    public function store(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/coupons'); }
        $code = trim($_POST['code'] ?? ''); $kind = $_POST['kind'] ?? 'fixed';
        $amount = (float)($_POST['amount'] ?? 0); $min = $_POST['min_spend'] !== '' ? (float)$_POST['min_spend'] : null;
        $exp = $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null; $active = isset($_POST['active']) ? 1 : 0;
        DB::pdo()->prepare('INSERT INTO coupons (code,kind,amount,min_spend,expires_at,active,created_at) VALUES (?,?,?,?,?,?,NOW())')
            ->execute([$code,$kind,$amount,$min,$exp,$active]);
        $this->redirect('/admin/coupons');
    }
    public function edit(array $params): void
    {
        $st = DB::pdo()->prepare('SELECT * FROM coupons WHERE id=?'); $st->execute([(int)$params['id']]);
        $row = $st->fetch(); if(!$row){ $this->redirect('/admin/coupons'); }
        $this->adminView('admin/coupons/edit', ['title' => 'Edit Coupon', 'coupon'=>$row]);
    }
    public function update(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/coupons'); }
        $id=(int)$params['id']; $code = trim($_POST['code'] ?? ''); $kind = $_POST['kind'] ?? 'fixed';
        $amount = (float)($_POST['amount'] ?? 0); $min = $_POST['min_spend'] !== '' ? (float)$_POST['min_spend'] : null;
        $exp = $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null; $active = isset($_POST['active']) ? 1 : 0;
        DB::pdo()->prepare('UPDATE coupons SET code=?, kind=?, amount=?, min_spend=?, expires_at=?, active=? WHERE id=?')
            ->execute([$code,$kind,$amount,$min,$exp,$active,$id]);
        $this->redirect('/admin/coupons');
    }
    public function delete(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/coupons'); }
        DB::pdo()->prepare('DELETE FROM coupons WHERE id=?')->execute([(int)$params['id']]);
        $this->redirect('/admin/coupons');
    }
}

