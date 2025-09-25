<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class AdminCollectionsController extends Controller
{
    public function index(): void
    {
        $rows = DB::pdo()->query('SELECT id, title, slug FROM collections ORDER BY id DESC')->fetchAll();
        $this->adminView('admin/collections/index', ['title' => 'Collections', 'collections'=>$rows]);
    }
    public function create(): void { $this->adminView('admin/collections/form', ['title' => 'Create Collection']); }
    public function store(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) { $this->redirect('/admin/collections'); }
        $title = trim($_POST['title']); $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $title));
        $imageUrl = $this->handleImageUpload();
        $stmt = DB::pdo()->prepare('INSERT INTO collections (title, slug, description, image_url) VALUES (?,?,?,?)');
        $stmt->execute([$title,$slug, $_POST['description'] ?? '', $imageUrl]);
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
        $title = trim($_POST['title']); $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $title));
        $imageUrl = $this->handleImageUpload();
        if ($imageUrl) {
            DB::pdo()->prepare('UPDATE collections SET title=?, slug=?, description=?, image_url=? WHERE id=?')
                ->execute([$title,$slug,$_POST['description'] ?? '', $imageUrl, $params['id']]);
        } else {
            DB::pdo()->prepare('UPDATE collections SET title=?, slug=?, description=? WHERE id=?')
                ->execute([$title,$slug,$_POST['description'] ?? '', $params['id']]);
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

