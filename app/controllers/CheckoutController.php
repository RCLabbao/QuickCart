<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class CheckoutController extends Controller
{
    public function index(): void
    {
        $cart = $_SESSION['cart'] ?? [];

        // Debug: Log cart contents
        error_log('Checkout - Cart contents: ' . json_encode($cart));
        error_log('Checkout - Session ID: ' . session_id());

        // Redirect to cart if cart is empty
        if (empty($cart)) {
            $_SESSION['checkout_error'] = 'Your cart is empty. Please add items before checkout.';
            $this->redirect('/cart');
            return;
        }

        $this->view('checkout/index', compact('cart'));
    }

    public function placeOrder(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            $_SESSION['checkout_error'] = 'Security token mismatch. Please try again.';
            $this->redirect('/checkout');
            return;
        }

        $method = $_POST['shipping_method'] ?? 'cod';
        $method = in_array($method, ['cod','pickup'], true) ? $method : 'cod';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $postal = trim($_POST['postal'] ?? '');

        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) {
            $_SESSION['checkout_error'] = 'Your cart is empty.';
            $this->redirect('/cart');
            return;
        }

        // Load checkout field toggles
        $st = DB::pdo()->query("SELECT `key`,`value` FROM settings");
        $settings = []; foreach($st->fetchAll() as $r){ $settings[$r['key']]=$r['value']; }
        $isEnabled = function($k, $default=true) use ($settings){ return isset($settings[$k]) ? (bool)$settings[$k] : $default; };

        // Validate required fields
        if (empty($name) || empty($email) || ($isEnabled('checkout_enable_phone', true) && empty($phone))) {
            $_SESSION['checkout_error'] = 'Please fill in all required fields.';
            $this->redirect('/checkout');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['checkout_error'] = 'Please enter a valid email address.';
            $this->redirect('/checkout');
            return;
        }

        if ($method === 'cod') {
            $missing = [];
            if ($isEnabled('checkout_enable_region', true) && empty($region)) $missing[]='region';
            if ($isEnabled('checkout_enable_province', true) && empty($province)) $missing[]='province';
            if ($isEnabled('checkout_enable_city', true) && empty($city)) $missing[]='city';
            if ($isEnabled('checkout_enable_barangay', true) && empty($barangay)) $missing[]='barangay';
            if ($isEnabled('checkout_enable_street', true) && empty($street)) $missing[]='street';
            if (!empty($missing)) {
                $_SESSION['checkout_error'] = 'Please fill in all required address fields.';
                $this->redirect('/checkout');
                return;
            }
        }
        $pdo = DB::pdo(); $pdo->beginTransaction();
        try {
            $subtotal = 0; $items = [];
            foreach ($cart as $pid=>$qty) {
                $stmt = $pdo->prepare('SELECT id, title, price, sale_price, sale_start, sale_end, COALESCE(stock,0) AS stock FROM products WHERE id=?'); $stmt->execute([$pid]); $p=$stmt->fetch();
                if(!$p) continue; $qty = max(0, min((int)$qty, (int)$p['stock'])); if ($qty<=0) continue;
                $unit = \App\Core\effective_price($p);
                $line = $unit*$qty; $subtotal += $line; $items[] = [$p,$qty,$line,$unit];
            }
            if (empty($items)) { $pdo->rollBack(); $_SESSION['checkout_error'] = 'Sorry, some items are no longer available or out of stock. Please review your cart.'; $this->redirect('/cart'); return; }
            // Load shipping fees from settings if set
            // Load settings
            $s = $pdo->query("SELECT `key`,`value` FROM settings")->fetchAll();
            $settings = []; foreach($s as $row){ $settings[$row['key']]=$row['value']; }
            $shippingFee = ($method==='cod') ? (float)($settings['shipping_fee_cod'] ?? 0.00) : (float)($settings['shipping_fee_pickup'] ?? 0.00);
            if ($method==='cod' && $city !== '') {
                try {
                    $feeStmt = $pdo->prepare('SELECT fee FROM delivery_fees WHERE city = ?');
                    $feeStmt->execute([$city]);
                    $rowFee = $feeStmt->fetch();
                    if ($rowFee) { $shippingFee = (float)$rowFee['fee']; }
                } catch (\Throwable $e) {}
            }
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
            // Backward compatible insert: support databases without discount/coupon_code columns
            $hasDiscount = $pdo->query("SHOW COLUMNS FROM orders LIKE 'discount'")->rowCount() > 0;
            $hasCoupon = $pdo->query("SHOW COLUMNS FROM orders LIKE 'coupon_code'")->rowCount() > 0;
            if ($hasDiscount && $hasCoupon) {
                $stmt = $pdo->prepare('INSERT INTO orders (email, shipping_method, subtotal, discount, shipping_fee, total, status, coupon_code, created_at) VALUES (?,?,?,?,?,? ,"pending", ?, NOW())');
                $stmt->execute([$email,$method,$subtotal,$discount,$shippingFee,$total,$couponCode?:null]);
            } elseif ($hasDiscount && !$hasCoupon) {
                $stmt = $pdo->prepare('INSERT INTO orders (email, shipping_method, subtotal, discount, shipping_fee, total, status, created_at) VALUES (?,?,?,?,?,? ,"pending", NOW())');
                $stmt->execute([$email,$method,$subtotal,$discount,$shippingFee,$total]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO orders (email, shipping_method, subtotal, shipping_fee, total, status, created_at) VALUES (?,?,?,?,? ,"pending", NOW())');
                $stmt->execute([$email,$method,$subtotal,$shippingFee,$total]);
            }
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
            $pdo->commit();
            // Only clear cart after successful order placement
            $_SESSION['cart'] = [];
            $_SESSION['checkout_success'] = 'Order placed successfully!';
            // Build rehashable token and redirect with it
            $st = $pdo->prepare('SELECT id,email,created_at FROM orders WHERE id=?');
            $st->execute([$orderId]);
            $row = $st->fetch();
            $slug = $row ? \App\Core\order_public_slug_from_row($row) : '';
            $this->redirect('/checkout/success/' . $slug);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['checkout_error'] = 'Failed to place order: ' . $e->getMessage();
            $this->redirect('/checkout');
        }
    }

    public function success(): void
    {
        $orderId = (int)($_GET['order_id'] ?? 0);
        $provided = (string)($_GET['token'] ?? '');
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT id,email,created_at FROM orders WHERE id=?');
        $st->execute([$orderId]);
        $row = $st->fetch();
        if (!$row) { http_response_code(404); echo 'Order not found'; return; }
        $token = \App\Core\order_public_token_from_row($row);
        if (!hash_equals($token, $provided)) { http_response_code(403); echo 'Invalid or expired link'; return; }
        $slug = \App\Core\order_public_slug_from_row($row);
        $this->view('checkout/success', compact('orderId','token','slug'));
    }

    // Success page using opaque slug
    public function successSlug(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        if ($slug === '') { http_response_code(404); echo 'Not found'; return; }
        $pdo = DB::pdo();
        [$id, $provided] = \App\Core\order_public_parts_from_slug($slug);
        $id = (int)$id;
        if ($id <= 0 || !$provided) { http_response_code(404); echo 'Not found'; return; }
        $st = $pdo->prepare('SELECT id,email,created_at FROM orders WHERE id=?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) { http_response_code(404); echo 'Order not found'; return; }
        $expected = \App\Core\order_public_token_from_row($row);
        if (!hash_equals($expected, $provided)) { http_response_code(403); echo 'Invalid link'; return; }
        $orderId = (int)$row['id'];
        $token = $expected;
        $slug = \App\Core\order_public_slug_from_row($row);
        $this->view('checkout/success', compact('orderId','token','slug'));
    }


    // Lightweight endpoint to calculate shipping fee given city and method
    public function fee(): void
    {
        header('Content-Type: application/json');
        $pdo = DB::pdo();
        $method = $_GET['method'] ?? 'cod';
        $method = in_array($method, ['cod','pickup'], true) ? $method : 'cod';
        $city = trim($_GET['city'] ?? '');

        // Load settings
        $s = $pdo->query("SELECT `key`,`value` FROM settings")->fetchAll();
        $settings = []; foreach($s as $row){ $settings[$row['key']]=$row['value']; }
        $fee = ($method==='cod') ? (float)($settings['shipping_fee_cod'] ?? 0.00)
                                 : (float)($settings['shipping_fee_pickup'] ?? 0.00);
        if ($method==='cod' && $city !== '') {
            try {
                $st = $pdo->prepare('SELECT fee FROM delivery_fees WHERE city = ?');
                $st->execute([$city]);
                $row = $st->fetch();
                if ($row) { $fee = (float)$row['fee']; }
            } catch (\Throwable $e) {}
        }
        echo json_encode(['fee' => round((float)$fee, 2)]);
    }
}

