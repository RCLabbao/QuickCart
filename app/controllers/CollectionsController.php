<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB;

class CollectionsController extends Controller
{
    private int $pageSize = 24;

    public function index(): void
    {
        $rows = DB::pdo()->query('SELECT id, title, slug, description, image_url FROM collections ORDER BY title')->fetchAll();
        $this->view('collections/index', ['collections'=>$rows]);
    }

    public function show(array $params): void
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT * FROM collections WHERE slug=?'); $st->execute([$params['slug']]);
        $c = $st->fetch(); if(!$c){ http_response_code(404); $this->view('errors/404'); return; }
        $ps = $pdo->prepare('SELECT p.*, (SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status="active" AND collection_id = ? ORDER BY created_at DESC LIMIT ' . $this->pageSize);
        $ps->execute([$c['id']]); $products = $ps->fetchAll();
        $this->view('collections/show', ['collection'=>$c, 'products'=>$products]);
    }

    public function loadMore(array $params): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page-1)*$this->pageSize;
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT id FROM collections WHERE slug=?'); $st->execute([$params['slug']]);
        $col = $st->fetch(); if(!$col){ $this->json(['html'=>'','hasMore'=>false]); return; }
        $q = 'SELECT p.*, (SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status="active" AND collection_id = ? ORDER BY created_at DESC LIMIT ' . $this->pageSize . ' OFFSET ' . $offset;
        $ps = $pdo->prepare($q); $ps->execute([$col['id']]); $products = $ps->fetchAll();
        ob_start(); foreach ($products as $p) { include BASE_PATH . '/app/views/products/_card.php'; } $html = ob_get_clean();
        $this->json(['html'=>$html, 'hasMore'=>count($products)===$this->pageSize]);
    }
}

