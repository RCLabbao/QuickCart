<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB;

class ProductController extends Controller
{
    private int $pageSize = 24;

    public function index(): void
    {
        $pdo = DB::pdo();
        [$where,$params] = $this->buildFilters();
        $st = $pdo->prepare('SELECT COUNT(*) FROM products p '.$where);
        $st->execute($params);
        $count = (int)$st->fetchColumn();
        $page = 1;
        $products = $this->fetchPage($page, $where, $params);
        $collections = $pdo->query('SELECT id,title FROM collections ORDER BY title')->fetchAll();
        $this->view('products/index', compact('products', 'count','collections'));
    }

    public function loadMore(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        [$where,$params] = $this->buildFilters();
        $products = $this->fetchPage($page, $where, $params);
        ob_start();
        foreach ($products as $p) { include BASE_PATH . '/app/views/products/_card.php'; }
        $html = ob_get_clean();
        $this->json(['html' => $html, 'hasMore' => count($products) === $this->pageSize]);
    }

    public function show(array $params): void
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE slug = ? AND status = "active"');
        $stmt->execute([$params['slug']]);
        $product = $stmt->fetch();
        // Hide if product belongs to a hidden collection
        $hidden = \App\Core\hidden_collection_ids();
        if (!$product || (!empty($hidden) && !empty($product['collection_id']) && in_array((int)$product['collection_id'], $hidden, true))) {
            http_response_code(404); $this->view('errors/404'); return;
        }
        $images = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order');
        $images->execute([$product['id']]);
        $gallery = $images->fetchAll();
        $this->view('products/show', compact('product', 'gallery'));
    }

    public function search(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page-1)*$this->pageSize;
        $pdo = DB::pdo();
        if ($q === '') { $products = []; $count = 0; }
        else {
            $like = "%$q%";
            $hidden = \App\Core\hidden_collection_ids();
            $exSql = '';
            $exParams = [];
            if (!empty($hidden)) { $exSql = ' AND (p.collection_id IS NULL OR p.collection_id NOT IN ('.implode(',', array_fill(0, count($hidden), '?')).'))'; $exParams = $hidden; }
            $sql = 'SELECT SQL_CALC_FOUND_ROWS p.*, (SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status = "active" AND (title LIKE ? OR description LIKE ?)' . $exSql . ' ORDER BY created_at DESC LIMIT ' . $this->pageSize . ' OFFSET ' . $offset;
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge([$like,$like], $exParams));
            $products = $stmt->fetchAll();
            $count = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
        }
        $this->view('search/index', compact('q','products','count','page'));
    }

    public function searchApi(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $limit = min(10, max(1, (int)($_GET['limit'] ?? 5))); // Limit to 5-10 results for realtime
        $pdo = DB::pdo();

        if ($q === '' || strlen($q) < 2) {
            $this->json(['products' => [], 'count' => 0]);
            return;
        }

        $like = "%$q%";
        $hidden = \App\Core\hidden_collection_ids();
        $exSql = '';
        $exParams = [];
        if (!empty($hidden)) { $exSql = ' AND (p.collection_id IS NULL OR p.collection_id NOT IN ('.implode(',', array_fill(0, count($hidden), '?')).'))'; $exParams = $hidden; }
        try {
            $stmt = $pdo->prepare('SELECT p.id, p.title, p.slug, p.price, p.sale_price, p.sale_start, p.sale_end, COALESCE(p.stock,0) AS stock, (SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status = "active" AND (title LIKE ? OR description LIKE ?)' . $exSql . ' ORDER BY title ASC LIMIT ' . $limit);
            $stmt->execute(array_merge([$like, $like], $exParams));
        } catch (\Throwable $e) {
            $stmt = $pdo->prepare('SELECT p.id, p.title, p.slug, p.price, COALESCE(p.stock,0) AS stock, (SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p WHERE status = "active" AND (title LIKE ? OR description LIKE ?)' . $exSql . ' ORDER BY title ASC LIMIT ' . $limit);
            $stmt->execute(array_merge([$like, $like], $exParams));
        }
        $products = $stmt->fetchAll();

        // Get total count for the query
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products p WHERE status = "active" AND (title LIKE ? OR description LIKE ?)' . $exSql);
        $countStmt->execute(array_merge([$like, $like], $exParams));
        $count = (int)$countStmt->fetchColumn();

        $this->json(['products' => $products, 'count' => $count, 'query' => $q]);
    }

    private function fetchPage(int $page, string $where, array $params): array
    {
        $offset = ($page-1)*$this->pageSize;
        $order = $this->orderBy();
        $sql = 'SELECT p.*, (SELECT url FROM product_images WHERE product_id=p.id ORDER BY sort_order LIMIT 1) AS image_url FROM products p ' . $where . ' ' . $order . ' LIMIT ' . $this->pageSize . ' OFFSET ' . $offset;
        $st = DB::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    private function buildFilters(): array
    {
        // Pull filters from GET, or fallback to referring page query if this is an AJAX load-more without params
        $q = $_GET;
        if ((empty($q) || (count($q)===1 && isset($q['page']))) && !empty($_SERVER['HTTP_REFERER'])) {
            $ref = parse_url($_SERVER['HTTP_REFERER']);
            if (!empty($ref['query'])) { parse_str($ref['query'], $q); }
        }
        $where = 'WHERE p.status = "active"'; $params = [];
        if (isset($q['min_price']) && $q['min_price'] !== '') { $where .= ' AND p.price >= ?'; $params[] = (float)$q['min_price']; }
        if (isset($q['max_price']) && $q['max_price'] !== '') { $where .= ' AND p.price <= ?'; $params[] = (float)$q['max_price']; }
        if (isset($q['collection_id']) && $q['collection_id'] !== '') { $where .= ' AND p.collection_id = ?'; $params[] = (int)$q['collection_id']; }
        $hidden = \App\Core\hidden_collection_ids();
        if (!empty($hidden)) {
            $where .= ' AND (p.collection_id IS NULL OR p.collection_id NOT IN ('.implode(',', array_fill(0, count($hidden), '?')).'))';
            foreach ($hidden as $hid) { $params[] = (int)$hid; }
        }
        return [$where, $params];
    }

    private function orderBy(): string
    {
        $sort = $_GET['sort'] ?? 'new';
        if ($sort === 'price_asc') return 'ORDER BY p.price ASC';
        if ($sort === 'price_desc') return 'ORDER BY p.price DESC';
        return 'ORDER BY p.created_at DESC';
    }
}

