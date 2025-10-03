<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB;

class HomeController extends Controller
{
    public function index(): void
    {
        $pdo = DB::pdo();
        // On Sale now
        $sale = $pdo->query('SELECT p.id,p.title,p.slug,p.price,p.sale_price,p.sale_start,p.sale_end,COALESCE(p.stock,0) AS stock,p.created_at,(SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status="active" AND sale_price IS NOT NULL AND sale_price < price AND (sale_start IS NULL OR sale_start <= NOW()) AND (sale_end IS NULL OR sale_end >= NOW()) ORDER BY created_at DESC LIMIT 12')->fetchAll();
        // New Arrivals
        $new = $pdo->query('SELECT p.id,p.title,p.slug,p.price,p.sale_price,p.sale_start,p.sale_end,COALESCE(p.stock,0) AS stock,p.created_at,(SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status="active" ORDER BY created_at DESC LIMIT 12')->fetchAll();
        // Best Sellers (last 30 days)
        $best = $pdo->query('SELECT p.id,p.title,p.slug,p.price,p.sale_price,p.sale_start,p.sale_end,COALESCE(p.stock,0) AS stock,p.created_at,(SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url
                             FROM products p
                             JOIN (
                               SELECT i.product_id, SUM(i.quantity) qty
                               FROM order_items i JOIN orders o ON o.id=i.order_id
                               WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                               GROUP BY i.product_id
                               ORDER BY qty DESC
                               LIMIT 12
                             ) t ON t.product_id = p.id
                             WHERE p.status="active"')->fetchAll();
        // Featured collections with images
        $feat = $pdo->query('SELECT id,title,slug,image_url FROM collections WHERE image_url IS NOT NULL AND image_url <> "" ORDER BY id DESC LIMIT 8')->fetchAll();
        $this->view('home/index', [
            'title' => 'QuickCart - Modern Shopping',
            'sale_items' => $sale,
            'new_arrivals' => $new,
            'best_sellers' => $best,
            'featured_collections' => $feat,
        ]);
    }
}

