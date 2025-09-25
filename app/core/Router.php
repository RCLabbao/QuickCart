<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $pattern, string $handler, array $opts = []): void { $this->add('GET', $pattern, $handler, $opts); }
    public function post(string $pattern, string $handler, array $opts = []): void { $this->add('POST', $pattern, $handler, $opts); }

    private function add(string $method, string $pattern, string $handler, array $opts): void
    {
        $regex = '#^' . $pattern . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'opts');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim($uri, '/');
        if ($path === '') { $path = '/'; }
        foreach ($this->routes as $r) {
            if ($method !== $r['method']) continue;
            if (preg_match($r['regex'], $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->run($r['handler'], $params, $r['opts']);
                return;
            }
        }
        http_response_code(404);
        echo View::render('errors/404');
    }

    private function run(string $handler, array $params, array $opts): void
    {
        [$controllerName, $method] = explode('@', $handler);
        $fqcn = 'App\\Controllers\\' . $controllerName;
        if (!class_exists($fqcn)) { http_response_code(500); echo 'Controller not found'; return; }
        $controller = new $fqcn();

        // Auth/permission guard
        if (isset($opts['auth']) && !Auth::checkRole($opts['auth'])) {
            header('Location: /admin/login');
            return;
        }
        if (isset($opts['perm'])) {
            if (!Auth::check()) { header('Location: /admin/login'); return; }
            if (!Auth::hasPermission($opts['perm'])) { http_response_code(403); echo 'Forbidden'; return; }
        }

        if (!method_exists($controller, $method)) { http_response_code(500); echo 'Action not found'; return; }
        $controller->$method($params);
    }
}

