<?php
namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        echo View::render($template, $data);
    }

    protected function adminView(string $template, array $data = []): void
    {
        echo View::renderAdmin($template, $data);
    }

    protected function redirect(string $to): void
    {
        header("Location: {$to}");
        exit;
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}

