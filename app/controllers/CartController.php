<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\CSRF; use App\Core\DB;

class CartController extends Controller
{
    public function index(): void
    {
        $cart = $_SESSION['cart'] ?? [];
        $this->view('cart/index', compact('cart'));
    }

    public function summary(): void
    {
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) { echo '<div class="text-muted">Your cart is empty.</div>'; return; }
        $pdo = DB::pdo(); $total = 0; ob_start();
        echo '<ul class="list-group mb-3">';
        foreach ($cart as $pid=>$qty){
            $stmt = $pdo->prepare('SELECT id,title,price,sale_price,sale_start,sale_end,slug FROM products WHERE id=?'); $stmt->execute([$pid]); $p=$stmt->fetch(); if(!$p) continue;
            $unit = \App\Core\effective_price($p);
            $line = $unit*$qty; $total += $line;
            echo '<li class="list-group-item d-flex align-items-center gap-2">';
            echo '<div class="flex-fill">'.htmlspecialchars($p['title']).'</div>';
            echo '<div class="input-group input-group-sm" style="width:120px">';
            echo '<button class="btn btn-outline-secondary" data-cart-action="qty" data-dir="-1" data-product-id="'.$p['id'].'">-</button>';
            echo '<input id="cartQty_'.$p['id'].'" class="form-control text-center" value="'.$qty.'" data-cart-qty="'.$qty.'" readonly>';
            echo '<button class="btn btn-outline-secondary" data-cart-action="qty" data-dir="1" data-product-id="'.$p['id'].'">+</button>';
            echo '</div>';
            echo '<div class="ms-auto fw-semibold">₱'.number_format($line,2).'</div>';
            echo '<button class="btn btn-sm btn-link text-danger" data-cart-action="remove" data-product-id="'.$p['id'].'"><i class="bi bi-x"></i></button>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<div class="d-flex justify-content-between"><div class="text-muted">Subtotal</div><div class="fw-bold">₱'.number_format($total,2).'</div></div>';
        $html = ob_get_clean();
        echo $html;
    }

    public function add(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->json(['ok'=>false], 400); return; }
        $id = (int)($_POST['product_id'] ?? 0); $qty = max(1, (int)($_POST['qty'] ?? 1));
        $stmt = DB::pdo()->prepare('SELECT id, title, price, slug, COALESCE(stock,0) AS stock FROM products WHERE id=?');
        $stmt->execute([$id]); $p = $stmt->fetch(); if (!$p) { $this->json(['ok'=>false],404); return; }
        $_SESSION['cart'] = $_SESSION['cart'] ?? [];
        $current = (int)($_SESSION['cart'][$id] ?? 0);
        $max = (int)$p['stock'];
        if ($max <= 0) { $this->json(['ok'=>true,'count'=>array_sum($_SESSION['cart'])]); return; }
        $new = min($current + $qty, $max);
        $_SESSION['cart'][$id] = $new;

        // Debug: Log cart addition
        error_log('Cart add - Product ID: ' . $id . ', Quantity: ' . $new . ', Session ID: ' . session_id());
        error_log('Cart add - Full cart: ' . json_encode($_SESSION['cart']));

        $this->json(['ok'=>true,'count'=>array_sum($_SESSION['cart'])]);
    }

    public function update(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->json(['ok'=>false],400); return; }
        $id = (int)($_POST['product_id'] ?? 0); $qty = max(0, (int)($_POST['qty'] ?? 1));
        $_SESSION['cart'] = $_SESSION['cart'] ?? [];
        if ($qty === 0) { unset($_SESSION['cart'][$id]); $this->json(['ok'=>true]); return; }
        $stmt = DB::pdo()->prepare('SELECT COALESCE(stock,0) AS stock FROM products WHERE id=?'); $stmt->execute([$id]);
        $max = (int)($stmt->fetchColumn() ?: 0);
        $_SESSION['cart'][$id] = min($qty, $max);
        $this->json(['ok'=>true]);
    }

    public function remove(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->json(['ok'=>false],400); return; }
        $id = (int)($_POST['product_id'] ?? 0);
        if (isset($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
        $this->json(['ok'=>true]);
    }
}

