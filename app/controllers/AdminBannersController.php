<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class AdminBannersController extends Controller
{
    private const MAX_BANNERS = 60;

    public function index(): void
    {
        $rows = DB::pdo()->query('SELECT * FROM banners ORDER BY sort_order ASC, id DESC')->fetchAll();
        $activeCount = DB::pdo()->query('SELECT COUNT(*) FROM banners WHERE status="active"')->fetchColumn();
        $this->adminView('admin/banners/index', [
            'title' => 'Banner Slider',
            'banners' => $rows,
            'activeCount' => (int)$activeCount,
            'maxBanners' => self::MAX_BANNERS
        ]);
    }

    public function create(): void
    {
        $activeCount = DB::pdo()->query('SELECT COUNT(*) FROM banners WHERE status="active"')->fetchColumn();
        $this->adminView('admin/banners/form', [
            'title' => 'Add Banner',
            'activeCount' => (int)$activeCount,
            'maxBanners' => self::MAX_BANNERS
        ]);
    }

    public function store(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request.';
            $this->redirect('/admin/banners');
        }

        $pdo = DB::pdo();
        $title = trim($_POST['title'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $altText = trim($_POST['alt_text'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = isset($_POST['status']) && in_array($_POST['status'], ['active', 'draft']) ? $_POST['status'] : 'active';

        // Check active banner limit
        if ($status === 'active') {
            $activeCount = (int)$pdo->query('SELECT COUNT(*) FROM banners WHERE status="active"')->fetchColumn();
            if ($activeCount >= self::MAX_BANNERS) {
                $_SESSION['error'] = "Maximum limit of " . self::MAX_BANNERS . " active banners reached.";
                $this->redirect('/admin/banners');
            }
        }

        if ($title === '') {
            $_SESSION['error'] = 'Title is required.';
            $this->redirect('/admin/banners');
        }

        // Handle image uploads
        $imageUrl = $this->handleImageUpload('image');
        $mobileImageUrl = $this->handleImageUpload('mobile_image');

        if (!$imageUrl) {
            $_SESSION['error'] = 'Desktop image is required.';
            $this->redirect('/admin/banners');
        }

        $stmt = $pdo->prepare('INSERT INTO banners (title, image_url, mobile_image_url, link_url, alt_text, sort_order, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$title, $imageUrl, $mobileImageUrl ?: null, $linkUrl ?: null, $altText ?: null, $sortOrder, $status]);

        $_SESSION['success'] = 'Banner added successfully.';
        $this->redirect('/admin/banners');
    }

    public function edit(array $params): void
    {
        $st = DB::pdo()->prepare('SELECT * FROM banners WHERE id=?');
        $st->execute([$params['id']]);
        $banner = $st->fetch();
        if (!$banner) {
            $this->redirect('/admin/banners');
        }

        $activeCount = DB::pdo()->query('SELECT COUNT(*) FROM banners WHERE status="active"')->fetchColumn();
        $this->adminView('admin/banners/form', [
            'title' => 'Edit Banner',
            'banner' => $banner,
            'activeCount' => (int)$activeCount,
            'maxBanners' => self::MAX_BANNERS
        ]);
    }

    public function update(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request.';
            $this->redirect('/admin/banners');
        }

        $pdo = DB::pdo();
        $bannerId = (int)$params['id'];

        // Check if banner exists
        $stmt = $pdo->prepare('SELECT status FROM banners WHERE id=?');
        $stmt->execute([$bannerId]);
        $banner = $stmt->fetch();
        if (!$banner) {
            $_SESSION['error'] = 'Banner not found.';
            $this->redirect('/admin/banners');
        }

        $title = trim($_POST['title'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $altText = trim($_POST['alt_text'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = isset($_POST['status']) && in_array($_POST['status'], ['active', 'draft']) ? $_POST['status'] : 'active';

        // Check active banner limit (when changing from draft to active)
        if ($status === 'active' && $banner['status'] !== 'active') {
            $activeCount = (int)$pdo->query('SELECT COUNT(*) FROM banners WHERE status="active"')->fetchColumn();
            if ($activeCount >= self::MAX_BANNERS) {
                $_SESSION['error'] = "Maximum limit of " . self::MAX_BANNERS . " active banners reached.";
                $this->redirect('/admin/banners');
            }
        }

        if ($title === '') {
            $_SESSION['error'] = 'Title is required.';
            $this->redirect('/admin/banners');
        }

        // Handle image uploads
        $imageUrl = $this->handleImageUpload('image');
        $mobileImageUrl = $this->handleImageUpload('mobile_image');

        // Build update query dynamically based on what changed
        $fields = ['title = ?', 'link_url = ?', 'alt_text = ?', 'sort_order = ?', 'status = ?'];
        $values = [$title, $linkUrl ?: null, $altText ?: null, $sortOrder, $status];

        if ($imageUrl) {
            $fields[] = 'image_url = ?';
            $values[] = $imageUrl;
        }
        if ($mobileImageUrl) {
            $fields[] = 'mobile_image_url = ?';
            $values[] = $mobileImageUrl;
        }

        $values[] = $bannerId;
        $sql = 'UPDATE banners SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $pdo->prepare($sql)->execute($values);

        $_SESSION['success'] = 'Banner updated successfully.';
        $this->redirect('/admin/banners');
    }

    public function destroy(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request.';
            $this->redirect('/admin/banners');
        }

        DB::pdo()->prepare('DELETE FROM banners WHERE id=?')->execute([$params['id']]);
        $_SESSION['success'] = 'Banner deleted successfully.';
        $this->redirect('/admin/banners');
    }

    public function reorder(): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            http_response_code(403);
            echo 'Invalid request';
            return;
        }

        $order = $_POST['order'] ?? [];
        if (!is_array($order)) {
            http_response_code(400);
            echo 'Invalid data';
            return;
        }

        $pdo = DB::pdo();
        $stmt = $pdo->prepare('UPDATE banners SET sort_order = ? WHERE id = ?');

        foreach ($order as $index => $bannerId) {
            $stmt->execute([(int)$index, (int)$bannerId]);
        }

        echo 'OK';
    }

    private function handleImageUpload(string $field): ?string
    {
        if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = $_FILES[$field]['tmp_name'];
        $size = (int)$_FILES[$field]['size'];

        // Max 10MB per image
        if ($size > 10 * 1024 * 1024) {
            return null;
        }

        $mime = function_exists('finfo_open')
            ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp)
            : mime_content_type($tmp);

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            return null;
        }

        $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
        $safe = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', pathinfo($_FILES[$field]['name'], PATHINFO_FILENAME));
        $final = $safe . '-' . uniqid() . '.' . $ext;

        $base = BASE_PATH . '/public/uploads/banners';
        if (!is_dir($base)) {
            @mkdir($base, 0775, true);
        }

        $dest = $base . '/' . $final;
        if (@move_uploaded_file($tmp, $dest)) {
            return '/public/uploads/banners/' . $final;
        }

        return null;
    }
}
