<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class AdminSettingsController extends Controller
{
    public function index(): void
    {
        $pdo = DB::pdo();
        $stmt = $pdo->query("SELECT `key`,`value` FROM settings");
        $settings = [];
        foreach ($stmt->fetchAll() as $row) { $settings[$row['key']] = $row['value']; }
        $this->adminView('admin/settings/index', ['title' => 'Settings', 'settings' => $settings]);
    }

    public function update(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/settings'); }
        $pdo = DB::pdo();
        $pairs = [
            'store_name' => $_POST['store_name'] ?? 'QuickCart',
            'currency' => $_POST['currency'] ?? 'PHP',
            'pickup_location' => $_POST['pickup_location'] ?? '',
            'shipping_fee_cod' => $_POST['shipping_fee_cod'] ?? '0',
            'shipping_fee_pickup' => $_POST['shipping_fee_pickup'] ?? '0',
            'brand_color' => $_POST['brand_color'] ?? '#212529',
        ];
        foreach ($pairs as $k=>$v){
            $stmt = $pdo->prepare('INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
            $stmt->execute([$k, $v]);
        }
        if (function_exists('apcu_delete')) { @apcu_delete('settings'); }
        $this->redirect('/admin/settings');
    }
}

