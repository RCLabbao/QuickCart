<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class AdminCollectionsController extends Controller
{
    public function index(): void
    {
        $hasCategoryCode = $this->hasCategoryCode();
        $hasStatus = $this->hasStatus();
        if ($hasCategoryCode && $hasStatus) {
            $rows = DB::pdo()->query('SELECT id, title, slug, image_url, category_code, status FROM collections ORDER BY id DESC')->fetchAll();
        } elseif ($hasCategoryCode) {
            $rows = DB::pdo()->query('SELECT id, title, slug, image_url, category_code FROM collections ORDER BY id DESC')->fetchAll();
        } elseif ($hasStatus) {
            $rows = DB::pdo()->query('SELECT id, title, slug, image_url, status FROM collections ORDER BY id DESC')->fetchAll();
        } else {
            $rows = DB::pdo()->query('SELECT id, title, slug, image_url FROM collections ORDER BY id DESC')->fetchAll();
        }
        $this->adminView('admin/collections/index', ['title' => 'Collections', 'collections'=>$rows]);
    }
    public function create(): void { $this->adminView('admin/collections/form', ['title' => 'Create Collection']); }
    private function hasCategoryCode(): bool {
        try {
            return (int)DB::pdo()->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'collections' AND COLUMN_NAME = 'category_code'")->fetchColumn() > 0;
        } catch (\Throwable $e) { return false; }
    }
    private function hasStatus(): bool {
        try {
            return (int)DB::pdo()->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'collections' AND COLUMN_NAME = 'status'")->fetchColumn() > 0;
        } catch (\Throwable $e) { return false; }
    }
    public function store(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/collections'); }
        $title = trim($_POST['title']);
        $slug = preg_replace('/[^a-z0-9]+/','-', strtolower($title));
        $slug = trim($slug, '-');
        // If slug is empty or just hyphens, generate from category_code or use a hash
        if ($slug === '' || $slug === '-') {
            $code = trim($_POST['category_code'] ?? '');
            if ($code !== '') {
                $slug = preg_replace('/[^a-z0-9]+/','-', strtolower($code));
                $slug = trim($slug, '-');
            }
            if ($slug === '' || $slug === '-') {
                $slug = 'collection-' . substr(md5($title . microtime(true)), 0, 8);
            }
        }
        // Ensure slug is unique
        $pdo = DB::pdo();
        $baseSlug = $slug; $suffix = 1;
        while ((int)$pdo->query('SELECT COUNT(*) FROM collections WHERE slug='.$pdo->quote($slug))->fetchColumn() > 0) {
            $slug = $baseSlug . '-' . $suffix++;
            if ($suffix > 1000) break;
        }
        $imageUrl = $this->handleImageUpload();
        $hasCode = $this->hasCategoryCode();
        $hasStatus = $this->hasStatus();
        $code = strtoupper(trim($_POST['category_code'] ?? ''));
        if ($code === '') { $code = null; }
        $status = isset($_POST['status']) && in_array($_POST['status'], ['active', 'draft']) ? $_POST['status'] : 'active';

        if ($hasCode && $hasStatus) {
            $stmt = $pdo->prepare('INSERT INTO collections (title, slug, description, image_url, category_code, status) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$title,$slug, $_POST['description'] ?? '', $imageUrl, $code, $status]);
        } elseif ($hasCode) {
            $stmt = $pdo->prepare('INSERT INTO collections (title, slug, description, image_url, category_code) VALUES (?,?,?,?,?)');
            $stmt->execute([$title,$slug, $_POST['description'] ?? '', $imageUrl, $code]);
        } elseif ($hasStatus) {
            $stmt = $pdo->prepare('INSERT INTO collections (title, slug, description, image_url, status) VALUES (?,?,?,?,?)');
            $stmt->execute([$title,$slug, $_POST['description'] ?? '', $imageUrl, $status]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO collections (title, slug, description, image_url) VALUES (?,?,?,?)');
            $stmt->execute([$title,$slug, $_POST['description'] ?? '', $imageUrl]);
        }
        $this->redirect('/admin/collections');
    }
    public function edit(array $params): void
    {
        $st = DB::pdo()->prepare('SELECT * FROM collections WHERE id=?'); $st->execute([$params['id']]); $c = $st->fetch(); if(!$c){ $this->redirect('/admin/collections'); }
        $this->adminView('admin/collections/form', ['title' => 'Edit Collection', 'collection'=>$c]);
    }
    public function update(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/collections'); }
        $title = trim($_POST['title']);
        $slug = preg_replace('/[^a-z0-9]+/','-', strtolower($title));
        $slug = trim($slug, '-');
        // If slug is empty or just hyphens, generate from category_code or use a hash
        if ($slug === '' || $slug === '-') {
            $code = trim($_POST['category_code'] ?? '');
            if ($code !== '') {
                $slug = preg_replace('/[^a-z0-9]+/','-', strtolower($code));
                $slug = trim($slug, '-');
            }
            if ($slug === '' || $slug === '-') {
                $slug = 'collection-' . substr(md5($title . microtime(true)), 0, 8);
            }
        }
        // Ensure slug is unique (excluding current record)
        $pdo = DB::pdo();
        $baseSlug = $slug; $suffix = 1;
        while ((int)$pdo->query('SELECT COUNT(*) FROM collections WHERE slug='.$pdo->quote($slug).' AND id!='.(int)$params['id'])->fetchColumn() > 0) {
            $slug = $baseSlug . '-' . $suffix++;
            if ($suffix > 1000) break;
        }
        $imageUrl = $this->handleImageUpload();
        $hasCode = $this->hasCategoryCode();
        $hasStatus = $this->hasStatus();
        $code = strtoupper(trim($_POST['category_code'] ?? ''));
        if ($code === '') { $code = null; }
        $status = isset($_POST['status']) && in_array($_POST['status'], ['active', 'draft']) ? $_POST['status'] : 'active';

        if ($imageUrl) {
            if ($hasCode && $hasStatus) {
                $pdo->prepare('UPDATE collections SET title=?, slug=?, description=?, image_url=?, category_code=?, status=? WHERE id=?')
                    ->execute([$title,$slug,$_POST['description'] ?? '', $imageUrl, $code, $status, $params['id']]);
            } elseif ($hasCode) {
                $pdo->prepare('UPDATE collections SET title=?, slug=?, description=?, image_url=?, category_code=? WHERE id=?')
                    ->execute([$title,$slug,$_POST['description'] ?? '', $imageUrl, $code, $params['id']]);
            } elseif ($hasStatus) {
                $pdo->prepare('UPDATE collections SET title=?, slug=?, description=?, image_url=?, status=? WHERE id=?')
                    ->execute([$title,$slug,$_POST['description'] ?? '', $imageUrl, $status, $params['id']]);
            } else {
                $pdo->prepare('UPDATE collections SET title=?, slug=?, description=?, image_url=? WHERE id=?')
                    ->execute([$title,$slug,$_POST['description'] ?? '', $imageUrl, $params['id']]);
            }
        } else {
            if ($hasCode && $hasStatus) {
                $pdo->prepare('UPDATE collections SET title=?, slug=?, description=?, category_code=?, status=? WHERE id=?')
                    ->execute([$title,$slug,$_POST['description'] ?? '', $code, $status, $params['id']]);
            } elseif ($hasCode) {
                $pdo->prepare('UPDATE collections SET title=?, slug=?, description=?, category_code=? WHERE id=?')
                    ->execute([$title,$slug,$_POST['description'] ?? '', $code, $params['id']]);
            } elseif ($hasStatus) {
                $pdo->prepare('UPDATE collections SET title=?, slug=?, description=?, status=? WHERE id=?')
                    ->execute([$title,$slug,$_POST['description'] ?? '', $status, $params['id']]);
            } else {
                $pdo->prepare('UPDATE collections SET title=?, slug=?, description=? WHERE id=?')
                    ->execute([$title,$slug,$_POST['description'] ?? '', $params['id']]);
            }
        }
        $this->redirect('/admin/collections');
    }
    private function handleImageUpload(): ?string
    {
        if (empty($_FILES['image']['name']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
        $tmp = $_FILES['image']['tmp_name']; $size = (int)$_FILES['image']['size'];
        if ($size > 5*1024*1024) return null;
        $mime = function_exists('finfo_open') ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp) : mime_content_type($tmp);
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'])) return null;
        $ext = $mime==='image/png'?'png':($mime==='image/webp'?'webp':'jpg');
        $safe = preg_replace('/[^a-zA-Z0-9-_\.]/','_', pathinfo($_FILES['image']['name'], PATHINFO_FILENAME));
        $final = $safe . '-' . uniqid() . '.' . $ext;
        $base = BASE_PATH . '/public/uploads/collections'; if(!is_dir($base)) @mkdir($base,0775,true);
        $dest = $base . '/' . $final; if (@move_uploaded_file($tmp,$dest)) return '/uploads/collections/' . $final; return null;
    }

    public function destroy(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/collections'); }
        DB::pdo()->prepare('DELETE FROM collections WHERE id=?')->execute([$params['id']]);
        $this->redirect('/admin/collections');
    }
}

