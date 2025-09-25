<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class CheckoutController extends Controller
{
    public function index(): void
    {
        $cart = $_SESSION['cart'] ?? [];

        // Redirect to products if cart is empty
        if (empty($cart)) {
            $this->redirect('/products');
        }

        $this->view('checkout/index', compact('cart'));
    }

    public function placeOrder(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/checkout'); }
        $method = $_POST['shipping_method'] ?? 'cod';
        $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $phone = trim($_POST['phone'] ?? '');
        $region = trim($_POST['region'] ?? ''); $province = trim($_POST['province'] ?? ''); $city = trim($_POST['city'] ?? ''); $barangay = trim($_POST['barangay'] ?? ''); $street = trim($_POST['street'] ?? ''); $postal = trim($_POST['postal'] ?? '');
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) { $this->redirect('/products'); }
        $pdo = DB::pdo(); $pdo->beginTransaction();
        try {
            $subtotal = 0; $items = [];
            foreach ($cart as $pid=>$qty) {
                $stmt = $pdo->prepare('SELECT id, title, price, sale_price, sale_start, sale_end, COALESCE(stock,0) AS stock FROM products WHERE id=?'); $stmt->execute([$pid]); $p=$stmt->fetch();
                if(!$p) continue; $qty = max(0, min((int)$qty, (int)$p['stock'])); if ($qty<=0) continue;
                $unit = \App\Core\effective_price($p);
                $line = $unit*$qty; $subtotal += $line; $items[] = [$p,$qty,$line,$unit];
            }
            if (empty($items)) { $pdo->rollBack(); $this->redirect('/products'); }
            // Load shipping fees from settings if set
            // Load settings
            $s = $pdo->query("SELECT `key`,`value` FROM settings")->fetchAll();
            $settings = []; foreach($s as $row){ $settings[$row['key']]=$row['value']; }
            $shippingFee = ($method==='cod') ? (float)($settings['shipping_fee_cod'] ?? 0.00) : (float)($settings['shipping_fee_pickup'] ?? 0.00);
            // Apply coupon (best-effort)
            $discount = 0.0; $couponCode = trim($_POST['coupon'] ?? '');
            if ($couponCode !== '') {
                try {
                    $c = $pdo->prepare('SELECT * FROM coupons WHERE code=? AND active=1'); $c->execute([$couponCode]); $cp = $c->fetch();
                    if ($cp) {
                        $okDate = (empty($cp['expires_at']) || strtotime($cp['expires_at']) >= time());
                        $okMin = (empty($cp['min_spend']) || $subtotal >= (float)$cp['min_spend']);
                        if ($okDate && $okMin) {
                            if ($cp['kind']==='percent') { $discount = round($subtotal * ((float)$cp['amount']/100), 2); }
                            else { $discount = (float)$cp['amount']; }
                            if ($discount > $subtotal) { $discount = $subtotal; }
                        }
                    }
                } catch (\Throwable $e) {}
            }
            $total = max(0.0, $subtotal - $discount) + $shippingFee;
            $stmt = $pdo->prepare('INSERT INTO orders (email, shipping_method, subtotal, discount, shipping_fee, total, status, coupon_code, created_at) VALUES (?,?,?,?,?,? ,"pending", ?, NOW())');
            $stmt->execute([$email,$method,$subtotal,$discount,$shippingFee,$total,$couponCode?:null]);
            $orderId = (int)$pdo->lastInsertId();
            foreach ($items as [$p,$qty,$line,$unit]) {
                $pdo->prepare('INSERT INTO order_items (order_id, product_id, title, unit_price, quantity) VALUES (?,?,?,?,?)')
                    ->execute([$orderId,$p['id'],$p['title'],$unit,$qty]);
                // Decrement stock atomically; if it fails, rollback
                $u = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND COALESCE(stock,0) >= ?');
                $u->execute([$qty, $p['id'], $qty]);
                if ($u->rowCount() === 0) { throw new \RuntimeException('Insufficient stock'); }
                // Log stock event (best-effort)
                try { $pdo->prepare('INSERT INTO product_stock_events (product_id, user_id, delta, reason, created_at) VALUES (?,?,?,?,NOW())')
                        ->execute([$p['id'], null, -$qty, 'order #'.$orderId]); } catch (\Throwable $e) {}
            }
            if ($method==='cod') {
                $pdo->prepare('INSERT INTO addresses (order_id, name, phone, region, province, city, barangay, street, postal_code) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute([$orderId,$name,$phone,$region,$province,$city,$barangay,$street,$postal]);
            }
            $pdo->commit(); $_SESSION['cart'] = [];
            $this->redirect('/checkout/success?order_id='.$orderId);
        } catch (\Throwable $e) {
            $pdo->rollBack(); $this->redirect('/checkout');
        }
    }

    public function success(): void
    {
        $orderId = (int)($_GET['order_id'] ?? 0);
        $this->view('checkout/success', compact('orderId'));
    }
}

