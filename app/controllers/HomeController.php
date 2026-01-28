<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB;

class HomeController extends Controller
{
    public function index(): void
    {
        $pdo = DB::pdo();
        // Get active banners for slider
        $banners = [];
        try {
            $banners = $pdo->query('SELECT * FROM banners WHERE status="active" ORDER BY sort_order ASC')->fetchAll();
        } catch (\Throwable $e) { $banners = []; }

        $hidden = \App\Core\hidden_collection_ids();
        $exSql = '';
        $exParams = [];
        if (!empty($hidden)) { $exSql = ' AND (p.collection_id IS NULL OR p.collection_id NOT IN ('.implode(',', array_fill(0, count($hidden), '?')).'))'; $exParams = $hidden; }

        // Check if variant support is enabled and exclude variant products from listing
        $hasVariants = false;
        try {
            $hasVariants = $pdo->query("SHOW COLUMNS FROM products LIKE 'parent_product_id'")->rowCount() > 0;
        } catch (\Throwable $e) {}

        $variantFilter = '';
        if ($hasVariants) {
            $variantFilter = ' AND (p.parent_product_id IS NULL OR p.parent_product_id = 0)';
        }
        // On Sale now (fallback if sale columns are missing)
        try {
            $st = $pdo->prepare('SELECT p.id,p.title,p.slug,p.price,p.sale_price,p.sale_start,p.sale_end,COALESCE(p.stock,0) AS stock,p.created_at,(SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status="active"' . $variantFilter . ' AND sale_price IS NOT NULL AND sale_price < price AND (sale_start IS NULL OR sale_start <= NOW()) AND (sale_end IS NULL OR sale_end >= NOW())' . $exSql . ' ORDER BY created_at DESC LIMIT 12');
            $st->execute($exParams); $sale = $st->fetchAll();
        } catch (\Throwable $e) {
            $st = $pdo->prepare('SELECT p.id,p.title,p.slug,p.price,COALESCE(p.stock,0) AS stock,p.created_at,(SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status="active"' . $variantFilter . $exSql . ' ORDER BY created_at DESC LIMIT 12');
            $st->execute($exParams); $sale = $st->fetchAll();
        }
        // New Arrivals
        try {
            $st = $pdo->prepare('SELECT p.id,p.title,p.slug,p.price,p.sale_price,p.sale_start,p.sale_end,COALESCE(p.stock,0) AS stock,p.created_at,(SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status="active"' . $variantFilter . $exSql . ' ORDER BY created_at DESC LIMIT 12');
            $st->execute($exParams); $new = $st->fetchAll();
        } catch (\Throwable $e) {
            $st = $pdo->prepare('SELECT p.id,p.title,p.slug,p.price,COALESCE(p.stock,0) AS stock,p.created_at,(SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status="active"' . $variantFilter . $exSql . ' ORDER BY created_at DESC LIMIT 12');
            $st->execute($exParams); $new = $st->fetchAll();
        }
        // Best Sellers (last 30 days)
        try {
            $st = $pdo->prepare('SELECT p.id,p.title,p.slug,p.price,p.sale_price,p.sale_start,p.sale_end,COALESCE(p.stock,0) AS stock,p.created_at,(SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url
                                 FROM products p
                                 JOIN (
                                   SELECT i.product_id, SUM(i.quantity) qty
                                   FROM order_items i JOIN orders o ON o.id=i.order_id
                                   WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                   GROUP BY i.product_id
                                   ORDER BY qty DESC
                                   LIMIT 12
                                 ) t ON t.product_id = p.id
                                 WHERE p.status="active"' . $variantFilter . $exSql);
            $st->execute($exParams); $best = $st->fetchAll();
        } catch (\Throwable $e) {
            $st = $pdo->prepare('SELECT p.id,p.title,p.slug,p.price,COALESCE(p.stock,0) AS stock,p.created_at,(SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url
                                 FROM products p
                                 JOIN (
                                   SELECT i.product_id, SUM(i.quantity) qty
                                   FROM order_items i JOIN orders o ON o.id=i.order_id
                                   WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                   GROUP BY i.product_id
                                   ORDER BY qty DESC
                                   LIMIT 12
                                 ) t ON t.product_id = p.id
                                 WHERE p.status="active"' . $variantFilter . $exSql);
            $st->execute($exParams); $best = $st->fetchAll();
        }
        // Featured collections with images (respect hidden collections)
        if (!empty($hidden)) {
            $in = implode(',', array_fill(0, count($hidden), '?'));
            $st = $pdo->prepare('SELECT id,title,slug,image_url FROM collections WHERE image_url IS NOT NULL AND image_url <> "" AND id NOT IN (' . $in . ') ORDER BY id DESC LIMIT 8');
            $st->execute($hidden); $feat = $st->fetchAll();
        } else {
            $feat = $pdo->query('SELECT id,title,slug,image_url FROM collections WHERE image_url IS NOT NULL AND image_url <> "" ORDER BY id DESC LIMIT 8')->fetchAll();
        }
        $this->view('home/index', [
            'title' => 'QuickCart - Modern Shopping',
            'banners' => $banners,
            'sale_items' => $sale,
            'new_arrivals' => $new,
            'best_sellers' => $best,
            'featured_collections' => $feat,
        ]);
    }
}

