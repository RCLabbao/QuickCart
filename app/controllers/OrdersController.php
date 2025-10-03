<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB;

class OrdersController extends Controller
{
    // Public read-only order status page
    public function show(array $params): void
    {
        $pdo = DB::pdo();
        $id = (int)($params['id'] ?? 0);
        $token = (string)($params['token'] ?? '');
        if ($id <= 0 || !$token) { http_response_code(404); $this->view('errors/404'); return; }

        $st = $pdo->prepare('SELECT id, email, shipping_method, subtotal, shipping_fee, total, status, created_at FROM orders WHERE id=?');
        $st->execute([$id]);
        $order = $st->fetch();
        if (!$order) { http_response_code(404); $this->view('errors/404'); return; }
        // Verify token using deterministic HMAC based on order row
        $expected = \App\Core\order_public_token_from_row($order);
        if (!hash_equals($expected, $token)) { http_response_code(404); $this->view('errors/404'); return; }

        $items = $pdo->prepare('SELECT title, unit_price, quantity FROM order_items WHERE order_id=? ORDER BY id ASC');
        $items->execute([$id]);
        $items = $items->fetchAll();

        $addr = null;
        if (($order['shipping_method'] ?? '') === 'cod') {
            $a = $pdo->prepare('SELECT name, phone, region, province, city, barangay, street, postal_code FROM addresses WHERE order_id=?');
            $a->execute([$id]);
            $addr = $a->fetch();
        }

        $this->view('orders/show', compact('order','items','addr'));
    }

    // Public order page via opaque slug
    public function showSlug(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        if ($slug === '') { http_response_code(404); $this->view('errors/404'); return; }
        $pdo = DB::pdo();
        [$id, $provided] = \App\Core\order_public_parts_from_slug($slug);
        $id = (int)$id;
        if ($id <= 0 || !$provided) { http_response_code(404); $this->view('errors/404'); return; }
        $st = $pdo->prepare('SELECT id, email, shipping_method, subtotal, shipping_fee, total, status, created_at FROM orders WHERE id=?');
        $st->execute([$id]);
        $order = $st->fetch();
        if (!$order) { http_response_code(404); $this->view('errors/404'); return; }
        $expected = \App\Core\order_public_token_from_row($order);
        if (!hash_equals($expected, $provided)) { http_response_code(404); $this->view('errors/404'); return; }
        $items = $pdo->prepare('SELECT title, unit_price, quantity FROM order_items WHERE order_id=? ORDER BY id ASC');
        $items->execute([$id]);
        $items = $items->fetchAll();
        $addr = null;
        if (($order['shipping_method'] ?? '') === 'cod') {
            $a = $pdo->prepare('SELECT name, phone, region, province, city, barangay, street, postal_code FROM addresses WHERE order_id=?');
            $a->execute([$id]);
            $addr = $a->fetch();
        }
        $this->view('orders/show', compact('order','items','addr'));
    }

}

