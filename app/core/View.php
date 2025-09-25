<?php
namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): string
    {
        $viewPath = BASE_PATH . '/app/views/' . $template . '.php';
        if (!file_exists($viewPath)) return 'View not found';
        extract($data, EXTR_SKIP);
        ob_start();
        include BASE_PATH . '/app/views/layouts/main.php';
        return ob_get_clean();
    }

    public static function renderAdmin(string $template, array $data = []): string
    {
        $viewPath = BASE_PATH . '/app/views/' . $template . '.php';
        if (!file_exists($viewPath)) return 'View not found';
        extract($data, EXTR_SKIP);
        ob_start();
        include BASE_PATH . '/app/views/layouts/admin.php';
        return ob_get_clean();
    }
}

