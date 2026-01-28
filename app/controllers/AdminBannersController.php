<?php
namespace App\Controllers;
use App\Core\Controller; use App\Core\DB; use App\Core\CSRF;

class AdminBannersController extends Controller
{
    private const MAX_BANNERS = 60;

    public function index(): void
    {
        $pdo = DB::pdo();
        $rows = $pdo->query('SELECT * FROM banners ORDER BY sort_order ASC, id DESC')->fetchAll();
        $activeCount = (int)$pdo->query('SELECT COUNT(*) FROM banners WHERE status="active"')->fetchColumn();

        // Get images for each banner
        foreach ($rows as &$banner) {
            $stmt = $pdo->prepare('SELECT url FROM banner_images WHERE banner_id = ? ORDER BY sort_order ASC');
            $stmt->execute([$banner['id']]);
            $banner['images'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        unset($banner);

        $this->adminView('admin/banners/index', [
            'title' => 'Banner Slider',
            'banners' => $rows,
            'activeCount' => $activeCount,
            'maxBanners' => self::MAX_BANNERS
        ]);
    }

    public function create(): void
    {
        $activeCount = (int)DB::pdo()->query('SELECT COUNT(*) FROM banners WHERE status="active"')->fetchColumn();
        $this->adminView('admin/banners/form', [
            'title' => 'Add Banner',
            'activeCount' => $activeCount,
            'maxBanners' => self::MAX_BANNERS,
            'bannerImages' => []
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

        // Handle multiple image uploads
        $uploadedImages = $this->handleMultipleImageUploads('images');

        if (empty($uploadedImages)) {
            $_SESSION['error'] = 'At least one image is required.';
            $this->redirect('/admin/banners');
        }

        // Insert banner with first image as the main image
        $stmt = $pdo->prepare('INSERT INTO banners (title, image_url, link_url, alt_text, sort_order, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$title, $uploadedImages[0], $linkUrl ?: null, $altText ?: null, $sortOrder, $status]);
        $bannerId = (int)$pdo->lastInsertId();

        // Insert all images into banner_images table
        $imgStmt = $pdo->prepare('INSERT INTO banner_images (banner_id, url, sort_order) VALUES (?, ?, ?)');
        foreach ($uploadedImages as $index => $imageUrl) {
            $imgStmt->execute([$bannerId, $imageUrl, $index]);
        }

        $_SESSION['success'] = 'Banner added successfully with ' . count($uploadedImages) . ' image(s).';
        $this->redirect('/admin/banners');
    }

    public function edit(array $params): void
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT * FROM banners WHERE id=?');
        $st->execute([$params['id']]);
        $banner = $st->fetch();
        if (!$banner) {
            $this->redirect('/admin/banners');
        }

        // Get banner images
        $imgStmt = $pdo->prepare('SELECT * FROM banner_images WHERE banner_id = ? ORDER BY sort_order ASC');
        $imgStmt->execute([$params['id']]);
        $bannerImages = $imgStmt->fetchAll();

        $activeCount = (int)$pdo->query('SELECT COUNT(*) FROM banners WHERE status="active"')->fetchColumn();
        $this->adminView('admin/banners/form', [
            'title' => 'Edit Banner',
            'banner' => $banner,
            'activeCount' => $activeCount,
            'maxBanners' => self::MAX_BANNERS,
            'bannerImages' => $bannerImages
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

        // Handle new image uploads
        $uploadedImages = $this->handleMultipleImageUploads('images');
        $imageCount = count($uploadedImages);

        // If new images uploaded, replace existing images
        if ($imageCount > 0) {
            // Delete old images from database
            $pdo->prepare('DELETE FROM banner_images WHERE banner_id = ?')->execute([$bannerId]);

            // Insert new images
            $imgStmt = $pdo->prepare('INSERT INTO banner_images (banner_id, url, sort_order) VALUES (?, ?, ?)');
            foreach ($uploadedImages as $index => $imageUrl) {
                $imgStmt->execute([$bannerId, $imageUrl, $index]);
            }

            // Update main image_url to first image
            $pdo->prepare('UPDATE banners SET image_url = ? WHERE id = ?')->execute([$uploadedImages[0], $bannerId]);
        }

        // Update banner details
        $pdo->prepare('UPDATE banners SET title = ?, link_url = ?, alt_text = ?, sort_order = ?, status = ? WHERE id = ?')
            ->execute([$title, $linkUrl ?: null, $altText ?: null, $sortOrder, $status, $bannerId]);

        $_SESSION['success'] = 'Banner updated successfully.' . ($imageCount > 0 ? " Updated with {$imageCount} image(s)." : '');
        $this->redirect('/admin/banners');
    }

    public function destroy(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request.';
            $this->redirect('/admin/banners');
        }

        $pdo = DB::pdo();
        $bannerId = (int)$params['id'];

        // Delete banner images (cascade will handle this)
        $pdo->prepare('DELETE FROM banner_images WHERE banner_id = ?')->execute([$bannerId]);

        // Delete banner
        $pdo->prepare('DELETE FROM banners WHERE id=?')->execute([$bannerId]);

        $_SESSION['success'] = 'Banner deleted successfully.';
        $this->redirect('/admin/banners');
    }

    public function deleteImage(array $params): void
    {
        if (!CSRF::check($_POST['_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }

        $pdo = DB::pdo();
        $imageId = (int)($params['id'] ?? 0);

        // Get image info
        $stmt = $pdo->prepare('SELECT banner_id, url FROM banner_images WHERE id = ?');
        $stmt->execute([$imageId]);
        $image = $stmt->fetch();

        if (!$image) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Image not found']);
            return;
        }

        // Delete image record
        $pdo->prepare('DELETE FROM banner_images WHERE id = ?')->execute([$imageId]);

        // Check if this was the main image, update if needed
        $bannerStmt = $pdo->prepare('SELECT image_url FROM banners WHERE id = ?');
        $bannerStmt->execute([$image['banner_id']]);
        $banner = $bannerStmt->fetch();

        if ($banner && $banner['image_url'] === $image['url']) {
            // Get new first image
            $newFirstStmt = $pdo->prepare('SELECT url FROM banner_images WHERE banner_id = ? ORDER BY sort_order ASC LIMIT 1');
            $newFirstStmt->execute([$image['banner_id']]);
            $newFirst = $newFirstStmt->fetchColumn();

            // Update banner with new first image or null
            $pdo->prepare('UPDATE banners SET image_url = ? WHERE id = ?')->execute([$newFirst ?: null, $image['banner_id']]);
        }

        echo json_encode(['success' => true]);
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

    /**
     * Handle multiple image uploads
     */
    private function handleMultipleImageUploads(string $field): array
    {
        if (empty($_FILES[$field]['name'][0])) {
            return [];
        }

        $uploadedFiles = [];
        $fileCount = count($_FILES[$field]['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES[$field]['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmp = $_FILES[$field]['tmp_name'][$i];
            $size = (int)$_FILES[$field]['size'][$i];

            // Max 10MB per image
            if ($size > 10 * 1024 * 1024) {
                continue;
            }

            $mime = function_exists('finfo_open')
                ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp)
                : mime_content_type($tmp);

            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                continue;
            }

            $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
            $safe = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', pathinfo($_FILES[$field]['name'][$i], PATHINFO_FILENAME));
            $final = $safe . '-' . uniqid() . '.' . $ext;

            $base = BASE_PATH . '/public/uploads/banners';
            if (!is_dir($base)) {
                @mkdir($base, 0775, true);
            }

            $dest = $base . '/' . $final;
            if (@move_uploaded_file($tmp, $dest)) {
                $uploadedFiles[] = '/public/uploads/banners/' . $final;
            }
        }

        return $uploadedFiles;
    }

    private function handleImageUpload(string $field): ?string
    {
        $files = $this->handleMultipleImageUploads($field);
        return $files[0] ?? null;
    }
}
