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
        $fees = [];
        try { $fees = $pdo->query('SELECT id, city, fee FROM delivery_fees ORDER BY city')->fetchAll(); } catch (\Throwable $e) { $fees = []; }
        $activeTab = $_GET['tab'] ?? 'general';
        $flash = $_SESSION['settings_flash'] ?? null; if ($flash) { unset($_SESSION['settings_flash']); }
        $this->adminView('admin/settings/index', ['title' => 'Settings', 'settings' => $settings, 'fees' => $fees, 'activeTab' => $activeTab, 'flash' => $flash]);
    }

    public function update(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/settings'); }
        $pdo = DB::pdo();
        $scope = $_POST['scope'] ?? 'general';
        $pairs = [];
        if ($scope === 'general') {
            $pairs = [
                'store_name' => $_POST['store_name'] ?? 'QuickCart',
                'currency' => $_POST['currency'] ?? 'PHP',
                'pickup_location' => $_POST['pickup_location'] ?? '',
                'brand_color' => $_POST['brand_color'] ?? '#212529',
                'today_cutoff' => $_POST['today_cutoff'] ?? '00:00',
            ];
        } elseif ($scope === 'checkout') {
            $pairs = [
                'checkout_enable_phone' => isset($_POST['checkout_enable_phone']) ? '1' : '0',
                'checkout_enable_region' => isset($_POST['checkout_enable_region']) ? '1' : '0',
                'checkout_enable_province' => isset($_POST['checkout_enable_province']) ? '1' : '0',
                'checkout_enable_city' => isset($_POST['checkout_enable_city']) ? '1' : '0',
                'checkout_enable_barangay' => isset($_POST['checkout_enable_barangay']) ? '1' : '0',
                'checkout_enable_street' => isset($_POST['checkout_enable_street']) ? '1' : '0',
                'checkout_enable_postal' => isset($_POST['checkout_enable_postal']) ? '1' : '0',
            ];
        } elseif ($scope === 'shipping') {
            $pairs = [
                'shipping_fee_cod' => $_POST['shipping_fee_cod'] ?? '0',
                'shipping_fee_pickup' => $_POST['shipping_fee_pickup'] ?? '0',
            ];
        } elseif ($scope === 'email') {
            $pairs = [
                'smtp_enabled' => isset($_POST['smtp_enabled']) ? '1' : '0',
                'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
                'smtp_user' => trim($_POST['smtp_user'] ?? ''),
                'smtp_pass' => trim($_POST['smtp_pass'] ?? ''),
                'smtp_secure' => in_array(strtolower($_POST['smtp_secure'] ?? 'tls'), ['tls','ssl','none'], true) ? strtolower($_POST['smtp_secure']) : 'tls',
                'smtp_from_email' => trim($_POST['smtp_from_email'] ?? ''),
                'smtp_from_name' => trim($_POST['smtp_from_name'] ?? ''),
                'email_order_subject' => trim($_POST['email_order_subject'] ?? 'Your order {{order_id}} at {{store_name}}'),
                'email_order_template' => (string)($_POST['email_order_template'] ?? ''),
            ];
        }
        foreach ($pairs as $k=>$v){
            $stmt = $pdo->prepare('INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
            $stmt->execute([$k, $v]);
        }
        if (function_exists('apcu_delete')) { @apcu_delete('settings'); }
        $_SESSION['settings_flash'] = 'Settings saved successfully.';
        $tab = $scope;
        if (!in_array($tab, ['general','checkout','shipping','email'], true)) { $tab = 'general'; }
        $this->redirect('/admin/settings?tab=' . $tab);
    }

    public function addCityFee(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/settings'); }
        $city = trim($_POST['city'] ?? '');
        $fee = (float)($_POST['fee'] ?? 0);
        if ($city !== '') {
            $pdo = DB::pdo();
            try {
                $pdo->exec('CREATE TABLE IF NOT EXISTS delivery_fees (id INT AUTO_INCREMENT PRIMARY KEY, city VARCHAR(191) NOT NULL UNIQUE, fee DECIMAL(10,2) NOT NULL DEFAULT 0.00) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            } catch (\Throwable $e) {}
            try {
                $stmt = $pdo->prepare('INSERT INTO delivery_fees (city, fee) VALUES (?, ?) ON DUPLICATE KEY UPDATE fee=VALUES(fee)');
                $stmt->execute([$city, $fee]);
            } catch (\Throwable $e) {}
        }
        $_SESSION['settings_flash'] = 'City fee saved.';
        $this->redirect('/admin/settings?tab=shipping');
    }

    public function deleteCityFee(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/settings'); }
        $id = (int)($params['id'] ?? 0);
        if ($id > 0) {
            $pdo = DB::pdo();
            try { $pdo->exec('CREATE TABLE IF NOT EXISTS delivery_fees (id INT AUTO_INCREMENT PRIMARY KEY, city VARCHAR(191) NOT NULL UNIQUE, fee DECIMAL(10,2) NOT NULL DEFAULT 0.00) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'); } catch (\Throwable $e) {}
            try { $pdo->prepare('DELETE FROM delivery_fees WHERE id=?')->execute([$id]); } catch (\Throwable $e) {}
        }
        $_SESSION['settings_flash'] = 'City fee deleted.';
        $this->redirect('/admin/settings?tab=shipping');
    }
}

