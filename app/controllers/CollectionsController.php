<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB;

class CollectionsController extends Controller
{
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
        $ps = $pdo->prepare('SELECT p.*, (SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status="active" AND collection_id = ? ORDER BY created_at DESC LIMIT 48');
        $ps->execute([$c['id']]); $products = $ps->fetchAll();
        $this->view('collections/show', ['collection'=>$c, 'products'=>$products]);
    }
}

